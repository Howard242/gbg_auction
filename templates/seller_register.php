<?php
session_start();
include '../php/db_config.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$seller_error = "";
$seller_success = "";
$rejected_message = false;
$user_id = $_SESSION['user_id'];

// Check if the user has applied before
$stmt = $conn->prepare("SELECT status FROM sellers WHERE user_id = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($status);
$stmt->fetch();

if ($stmt->num_rows > 0) {
    if ($status == 'approved') {
        $seller_error = "❌ You are already an approved seller.";
    } elseif ($status == 'pending') {
        $seller_error = "❌ Your seller application is still pending approval.";
    } elseif ($status == 'rejected') {
        $rejected_message = true; // Seller was rejected
    }
}

$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $business_name = trim($_POST['business_name']);
    $business_type = trim($_POST['business_type']);
    $location = trim($_POST['location']);
    $contact_info = trim($_POST['contact_info']);
    $description = trim($_POST['description']);

    if ($rejected_message) {
        // Update the existing record instead of inserting a new one
        $stmt = $conn->prepare("UPDATE sellers SET business_name=?, business_type=?, location=?, contact_info=?, description=?, status='pending' WHERE user_id=?");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("sssssi", $business_name, $business_type, $location, $contact_info, $description, $user_id);

        if ($stmt->execute()) {
            $seller_success = "✅ Reapplication successful! Please wait for admin approval.";
            $rejected_message = false; // Reset rejected flag
        } else {
            $seller_error = "❌ Something went wrong. Please try again.";
        }
        $stmt->close();
    } elseif (empty($seller_error)) {
        // Insert a new record if the user has never applied before
        $stmt = $conn->prepare("INSERT INTO sellers (user_id, business_name, business_type, location, contact_info, description, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("isssss", $user_id, $business_name, $business_type, $location, $contact_info, $description);

        if ($stmt->execute()) {
            $seller_success = "✅ Registration successful! Please wait for admin approval.";
        } else {
            $seller_error = "❌ Something went wrong. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="form-container">
        <h2 class="text-center mb-4">Register as a Seller</h2>

        <?php if (!empty($seller_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($seller_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($seller_success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($seller_success); ?></div>
        <?php endif; ?>

        <?php if ($rejected_message): ?>
            <div class="alert alert-warning">
                ❌ Your application was not approved. You can reapply with updated details.
            </div>
        <?php endif; ?>

        <form action="seller_register.php" method="post">
            <div class="mb-3">
                <label class="form-label">Business Name</label>
                <input type="text" name="business_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Business Type</label>
                <input type="text" name="business_type" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Info</label>
                <input type="text" name="contact_info" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <?php echo $rejected_message ? "Reapply as Seller" : "Submit Registration"; ?>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>