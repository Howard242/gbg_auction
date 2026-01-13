<?php
// Start session only if it hasn't been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Check if user is an approved seller
$is_seller_approved = false;
if ($is_logged_in) {
    $seller_check_query = "SELECT status FROM sellers WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($seller_check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($status);
    if ($stmt->fetch() && $status === 'approved') {
        $is_seller_approved = true;
    }
    $stmt->close();
}
?>

<nav class="navbar navbar-expand-lg bg-light shadow">
    <div class="container">
        <a class="navbar-brand" href="index.php">GoBidGo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="auctions.php">Auctions</a></li>
                <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>

                <!-- Buy Now Button -->
                <li class="nav-item">
                    <a class="nav-link btn btn-success btn-sm text-white px-3" href="buy_now.php">Buy Now</a>
                </li>

                <?php if ($is_logged_in): ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-danger btn-sm text-white" href="logout.php">Logout</a></li>

                    <?php if (!$is_seller_approved): ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-warning btn-sm text-white" href="templates/seller_register.php">Register as a Seller</a>
                        </li>
                    <?php endif; ?>

                <?php else: ?>
                    <li class="nav-item"><a class="nav-link btn btn-primary btn-sm text-white" href="templates/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-success btn-sm text-white" href="templates/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
