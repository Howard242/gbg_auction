<?php
session_start();
require __DIR__ . '/php/db_config.php'; 
require __DIR__ . '/vendor/autoload.php'; // Correct path

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form data
$auction_id = intval($_POST['auction_id']);
$payment_method = htmlspecialchars($_POST['payment_method']);
$visit_date = isset($_POST['visit_date']) ? htmlspecialchars($_POST['visit_date']) : null;
$address = isset($_POST['address']) ? htmlspecialchars($_POST['address']) : null;
$nearest_town = isset($_POST['nearest_town']) ? htmlspecialchars($_POST['nearest_town']) : null;
$phone_number = isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : null;
$delivery_instructions = isset($_POST['delivery_instructions']) ? htmlspecialchars($_POST['delivery_instructions']) : null;
$courier = isset($_POST['courier']) ? htmlspecialchars($_POST['courier']) : null;

// Validate auction_id
if ($auction_id <= 0) {
    die("Invalid auction ID.");
}

// Check if the buyer already exists in the `buyers` table
$buyer_query = "SELECT id FROM buyers WHERE user_id = ?";
$stmt = $conn->prepare($buyer_query);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}
$stmt->bind_result($buyer_id);
$stmt->fetch();
$stmt->close();

// If the buyer does not exist, create a new record in the `buyers` table
if (!$buyer_id) {
    $insert_buyer_query = "INSERT INTO buyers (user_id, shipping_address, nearest_town, phone_number, delivery_instructions) 
                           VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_buyer_query);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("issss", $user_id, $address, $nearest_town, $phone_number, $delivery_instructions);
    if (!$stmt->execute()) {
        die("Failed to create buyer record: " . $stmt->error);
    }
    $buyer_id = $stmt->insert_id; // Get the newly created buyer_id
    $stmt->close();
}

// Fetch auction details using prepared statements
$sql = "SELECT a.*, u.email AS seller_email FROM auctions a 
        JOIN users u ON a.seller_id = u.id 
        WHERE a.id = ? AND a.buyer_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("ii", $auction_id, $user_id);
if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid request.");
}

$auction = $result->fetch_assoc();
if (!$auction) {
    die("Invalid auction data.");
}

$seller_email = htmlspecialchars($auction['seller_email']);
$item_name = htmlspecialchars($auction['title']);
$final_price = htmlspecialchars($auction['final_price']);

// Update the auctions table with buyer_id and payment details
$update_auction_query = "UPDATE auctions 
                         SET buyer_id = ?, 
                             payment_method = ?, 
                             courier = ?, 
                             visit_date = ? 
                         WHERE id = ?";
$stmt = $conn->prepare($update_auction_query);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("isssi", $buyer_id, $payment_method, $courier, $visit_date, $auction_id);
if (!$stmt->execute()) {
    die("Failed to update auction details: " . $stmt->error);
}

// Send confirmation email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'petersongary252@gmail.com'; // Change to your email
    $mail->Password = 'jytc yuew hqkk tpsg'; // Use App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Send to Seller
    $mail->setFrom('petersongary252@gmail.com', 'GoBidGo Auctions');
    $mail->addAddress($seller_email);
    $mail->Subject = "Payment Received for Auction Item";
    $mail->Body = "Your item '$item_name' has been successfully purchased for KES $final_price. The buyer has completed the process.";

    $mail->send();
    $mail->clearAddresses();

    // Send to Buyer
    if (!isset($_SESSION['user_email'])) {
        die("User email not found in session. Please log in again.");
    }
    $buyer_email = $_SESSION['user_email'];
    $mail->addAddress($buyer_email);
    $mail->Subject = "Auction Purchase Confirmation";
    $mail->Body = "Thank you for your purchase! You have won '$item_name' for KES $final_price.";

    if ($auction['category'] === "Real Estate") {
        $mail->Body .= "\nVisit Scheduled on: $visit_date\nLocation: " . htmlspecialchars($auction['location']);
    } else {
        $mail->Body .= "\nShipping to: $address\nCourier: $courier";
    }

    $mail->send();
} catch (Exception $e) {
    error_log("Email sending failed: " . $mail->ErrorInfo);
}

echo "Purchase details submitted successfully!";
?>