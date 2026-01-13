<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';
require $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Get the title from the request body
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['title'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}
$title = $input['title'];

// Fetch the auction details using the title
$query = "SELECT * FROM auctions WHERE title = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $title);
$stmt->execute();
$result = $stmt->get_result();
$auction = $result->fetch_assoc();

if (!$auction) {
    echo json_encode(['success' => false, 'message' => 'Auction not found.']);
    exit();
}

// Fetch the seller's email
$seller_query = "SELECT email FROM users WHERE id = ?";
$seller_stmt = $conn->prepare($seller_query);
$seller_stmt->bind_param("i", $auction['seller_id']);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();
$seller = $seller_result->fetch_assoc();

if (!$seller) {
    echo json_encode(['success' => false, 'message' => 'Seller not found.']);
    exit();
}

$seller_email = $seller['email'];

// Send email to the seller
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'petersongary252@gmail.com'; // Replace with your email
    $mail->Password = 'jytc yuew hqkk tpsg'; // Replace with your email password or app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('petersongary252@gmail.com', 'GoBidGo Auctions');
    $mail->addAddress($seller_email);
    $mail->Subject = 'Relist Request for Your Auction Item';
    $mail->Body = "Hello,\n\nA buyer has requested that you relist your auction item titled '$title'.\n\nPlease log in to your account to relist the item.\n\nBest regards,\nThe GoBidGo Team";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'A message has been sent to the seller to relist the item.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send the request. Please try again.']);
    error_log("Email sending failed: " . $mail->ErrorInfo);
}

$stmt->close();
$conn->close();
?>