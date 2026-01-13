<?php
session_start();
include 'php/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the user's phone number from the database
$query = "SELECT phone_number FROM buyers WHERE user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($phone_number);
$stmt->fetch();
$stmt->close();

include 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">M-Pesa Payment</h2>
        <p class="text-center">Please enter your M-Pesa PIN to complete the payment.</p>

        <!-- Display the user's phone number -->
        <div class="alert alert-info">
            <strong>Phone Number:</strong> <?php echo htmlspecialchars($phone_number); ?>
        </div>

        <!-- M-Pesa PIN Form -->
        <form method="POST" action="process_mpesa_payment.php">
            <div class="mb-3">
                <label for="mpesa_pin" class="form-label">M-Pesa PIN:</label>
                <input type="password" name="mpesa_pin" id="mpesa_pin" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit</button>
        </form>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>