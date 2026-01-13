<?php
session_start();
require_once __DIR__ . '/auction_expiry.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/send_email.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /gbg_auction/templates/login.php");
    exit();
}

// Validate POST request
if (!isset($_POST['auction_id'], $_POST['bid_amount'])) {
    die("<h2 style='color:red; text-align:center;'>❌ Invalid request. Missing auction details.</h2>");
}

$user_id = $_SESSION['user_id'];
$auction_id = intval($_POST['auction_id']);
$bid_amount = floatval($_POST['bid_amount']);

// Fetch auction details including category
$auction_stmt = $conn->prepare("SELECT id, starting_price, status, end_time, seller_id, item_name, category FROM auctions WHERE id = ?");
$auction_stmt->bind_param("i", $auction_id);
$auction_stmt->execute();
$auction_result = $auction_stmt->get_result();

// Check if auction exists
if ($auction_result->num_rows === 0) {
    die("<h2 style='color:red; text-align:center;'>❌ Auction not found.</h2>");
}

$auction = $auction_result->fetch_assoc(); // Fetch the auction data
$starting_price = $auction['starting_price'];
$auction_status = $auction['status'];
$auction_end_time = strtotime($auction['end_time']);
$seller_id = $auction['seller_id'];
$item_name = $auction['item_name'];
$category = $auction['category']; // Define $category here
$auction_stmt->close();

// Check if auction has ended
$current_time = time();
if ($current_time >= $auction_end_time) {
    echo "<h2 style='color:red; text-align:center;'>❌ This auction has ended. You cannot place a bid.</h2>";
    exit();
}

// Prevent bidding on sold items
if ($auction_status === 'sold') {
    echo "<h2 style='color:red; text-align:center;'>❌ This item has already been sold.</h2>";
    exit();
}

// Fetch buyer and seller details
$user_stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$buyer = $user_result->fetch_assoc();
$user_stmt->close();

$seller_stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
$seller_stmt->bind_param("i", $seller_id);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();
$seller = $seller_result->fetch_assoc();
$seller_stmt->close();

// Send notifications
$buyer_email = $buyer['email'];
$buyer_phone = $buyer['phone'];
$seller_email = $seller['email'];
$seller_phone = $seller['phone'];

$winner_subject = "🎉 You Won the Auction!";
$winner_message = "
    <h2>Congratulations!</h2>
    <p>You have won the auction for item ID: $auction_id.</p>
    <p>Please proceed with payment and delivery.</p>
    <p><a href='/gbg_auction/payment_and_delivery.php?auction_id=$auction_id'>Click here to complete the transaction</a></p>
";

$seller_subject = "📢 Your Item Has Been Sold!";
$seller_message = "
    <h2>Your item has been sold!</h2>
    <p>Item ID: $auction_id was sold for Ksh " . number_format($bid_amount, 2) . ".</p>
    <p>Contact the buyer to finalize the transaction.</p>
";

// Send Emails
sendEmail($buyer_email, $winner_subject, $winner_message, $auction_id, $category);
sendEmail($seller_email, $seller_subject, $seller_message, $auction_id, $category);

// Send SMS
sendSMS($buyer_phone, "🎉 You won auction #$auction_id! Complete the payment: /gbg_auction/payment_and_delivery.php?auction_id=$auction_id");
sendSMS($seller_phone, "📢 Your item #$auction_id sold for Ksh " . number_format($bid_amount, 2) . ". Contact the buyer.");

// Fetch the current highest bid
$highest_bid_stmt = $conn->prepare("SELECT MAX(bid_amount) as highest_bid FROM bids WHERE auction_id = ?");
$highest_bid_stmt->bind_param("i", $auction_id);
$highest_bid_stmt->execute();
$highest_bid_result = $highest_bid_stmt->get_result();
$highest_bid_row = $highest_bid_result->fetch_assoc();
$highest_bid = $highest_bid_row['highest_bid'] ?? $starting_price; // Default to starting price if no bids exist
$highest_bid_stmt->close();

if ($bid_amount <= $highest_bid) {
    echo "<h2 style='color:red; text-align:center;'>❌ Bid must be higher than Ksh " . number_format($highest_bid, 2) . "</h2>";
    echo "<script>setTimeout(() => { window.history.back(); }, 3000);</script>";
    exit();
}

// Insert bid into database
$insert_stmt = $conn->prepare("INSERT INTO bids (auction_id, user_id, bid_amount, bid_time) VALUES (?, ?, ?, NOW())");
$insert_stmt->bind_param("iid", $auction_id, $user_id, $bid_amount);

if ($insert_stmt->execute()) {
    echo "<h2 style='color:green; text-align:center;'>✅ Bid placed successfully!</h2>";

    // Check if auction has ended and if we have a winner
    if ($current_time >= $auction_end_time && $bid_amount > $starting_price) {
        // Mark auction as sold
        $update_auction_stmt = $conn->prepare("UPDATE auctions SET status = 'sold' WHERE id = ?");
        $update_auction_stmt->bind_param("i", $auction_id);
        $update_auction_stmt->execute();
        $update_auction_stmt->close();

        // Insert into auction_history
        $history_stmt = $conn->prepare("INSERT INTO auction_history (auction_id, item_name, final_price, buyer_id, seller_id) 
                                        VALUES (?, ?, ?, ?, ?)");
        $history_stmt->bind_param("isdii", $auction_id, $item_name, $bid_amount, $user_id, $seller_id);
        $history_stmt->execute();
        $history_stmt->close();

        echo "<h2 style='color:blue; text-align:center;'>🏆 Auction Ended! You are the highest bidder. Proceed to payment.</h2>";
        echo "<script>setTimeout(() => { window.location.href='/gbg_auction/payment_and_delivery.php?auction_id=$auction_id'; }, 3000);</script>";
    } else {
        echo "<script>setTimeout(() => { window.location.href='/gbg_auction/auction_details.php?id=$auction_id'; }, 2000);</script>";
    }
} else {
    echo "<h2 style='color:red; text-align:center;'>❌ Failed to place bid. Error: " . htmlspecialchars($conn->error) . "</h2>";
    echo "<script>setTimeout(() => { window.history.back(); }, 3000);</script>";
}

$insert_stmt->close();
$conn->close();
?>