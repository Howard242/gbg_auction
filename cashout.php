<?php
session_start();
include 'php/db_config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access!";
    exit();
}

$seller_id = $_SESSION['user_id'];

// Calculate total earnings from completed orders
$query = "SELECT SUM(price) AS total_earnings FROM buy_now_orders WHERE seller_id = ? AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_earnings = $row['total_earnings'] ?? 0;

// Check if seller has money to cash out
if ($total_earnings <= 0) {
    echo "<script>alert('No earnings available for cashout.'); window.location.href='dashboard.php';</script>";
    exit();
}

// Check if seller has payment & shipping details
$checkDetailsQuery = "SELECT payment_method, address, phone_number FROM users WHERE id = ?";
$stmt = $conn->prepare($checkDetailsQuery);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$userDetails = $result->fetch_assoc();

if (!$userDetails || empty($userDetails['payment_method']) || empty($userDetails['address']) || empty($userDetails['phone_number'])) {
    echo "<script>alert('Please provide your payment and shipping details before cashing out.'); window.location.href='payment_delivery.php';</script>";
    exit();
}

// Insert cashout request
$insertQuery = "INSERT INTO cashout_requests (seller_id, amount) VALUES (?, ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("id", $seller_id, $total_earnings);
if ($stmt->execute()) {
    echo "<script>alert('Cashout request submitted successfully!'); window.location.href='dashboard.php';</script>";
} else {
    echo "<script>alert('Error processing cashout request. Try again later.'); window.location.href='dashboard.php';</script>";
}

$stmt->close();
$conn->close();
?>
