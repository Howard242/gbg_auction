<?php 
session_start();
include 'php/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: templates/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT username, first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();

// Check if user is a seller
$stmt = $conn->prepare("SELECT status FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($seller_status);
$stmt->fetch();
$stmt->close();
?>
<?php include 'templates/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GoBidGo</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/profile.css"> <!-- Link to external CSS -->
</head>
<body>

<div class="container profile-container">
    <div class="profile-card">
        <h2 class="text-center"><?php echo htmlspecialchars($first_name . " " . $last_name); ?>'s Profile</h2>
        <hr>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>

        <?php if ($seller_status === 'approved'): ?>
            <div class="alert alert-success text-center">✅ You are an approved seller.</div>
            <a href="templates/add_item.php" class="btn btn-primary w-100">Add Auction Item</a>
            <a href="templates/manage_sold_items.php" class="btn btn-success w-100 mt-2">Manage Sold Items</a>
        <?php elseif ($seller_status === 'pending'): ?>
            <div class="alert alert-warning text-center">⏳ Your seller application is pending approval.</div>
        <?php elseif ($seller_status === 'rejected'): ?>
            <div class="alert alert-danger text-center">❌ Your seller application was rejected.</div>
        <?php else: ?>
            <a href="templates/seller_register.php" class="btn btn-warning w-100">Become a Seller</a>
        <?php endif; ?>

        <a href="logout.php" class="btn btn-danger w-100 mt-3">Logout</a>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
</body>
</html>
