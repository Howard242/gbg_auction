<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Get the title from the request body
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['title'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Title not provided.']);
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

// Debugging: Log the seller_id and current user_id for verification
error_log("Seller ID: " . $auction['seller_id']);
error_log("Current User ID: " . $_SESSION['user_id']);

// Check if the current user is the seller
if ($_SESSION['user_id'] != $auction['seller_id']) {
    echo json_encode(['success' => false, 'message' => 'You are not the seller of this item. Only the original seller can relist it.']);
    exit();
}

// Relist the item by updating its status and end time
$update_query = "UPDATE auctions SET status = 'active', end_time = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE title = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("s", $title);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item has been relisted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to relist the item. Database error.']);
}

$update_stmt->close();
$conn->close();
?>