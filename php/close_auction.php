<?php
include 'db_config.php';

// Get all expired auctions
$expired_stmt = $conn->prepare("SELECT id, title, seller_id, starting_price FROM auctions WHERE end_time <= NOW()");
$expired_stmt->execute();
$expired_result = $expired_stmt->get_result();

while ($auction = $expired_result->fetch_assoc()) {
    $auction_id = $auction['id'];
    $title = $auction['title'];
    $seller_id = $auction['seller_id'];
    $starting_price = $auction['starting_price'];

    // Get the highest bid
    $bid_stmt = $conn->prepare("SELECT user_id, amount FROM bids WHERE auction_id = ? ORDER BY amount DESC LIMIT 1");
    $bid_stmt->bind_param("i", $auction_id);
    $bid_stmt->execute();
    $bid_result = $bid_stmt->get_result();
    $winning_bid = $bid_result->fetch_assoc();
    $bid_stmt->close();

    if ($winning_bid && $winning_bid['amount'] > $starting_price) {
        $winner_id = $winning_bid['user_id'];
        $final_price = $winning_bid['amount'];

        // Save auction to history
        $history_stmt = $conn->prepare("INSERT INTO auction_history (auction_id, title, seller_id, winner_id, final_price, sold_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $history_stmt->bind_param("isiid", $auction_id, $title, $seller_id, $winner_id, $final_price);
        $history_stmt->execute();
        $history_stmt->close();

        // Notify the winner
        $msg = "🎉 You won the auction for '$title' at Ksh $final_price!";
        $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $notify_stmt->bind_param("is", $winner_id, $msg);
        $notify_stmt->execute();
        $notify_stmt->close();
    }

    // Remove the auction from active listings
    $delete_stmt = $conn->prepare("DELETE FROM auctions WHERE id = ?");
    $delete_stmt->bind_param("i", $auction_id);
    $delete_stmt->execute();
    $delete_stmt->close();
}

$expired_stmt->close();
?>
