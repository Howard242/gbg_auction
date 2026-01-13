<?php
session_start();
include 'php/db_config.php'; 

// Fetch all categories
$categories = [
    'Electronics', 
    'Vehicles', 
    'Real Estate', 
    'Furniture', 
    'Fashion', 
    'Sports', 
    'Cutlery', 
    'Groceries', 
    'Animal Feeds', 
    'Stationeries', 
    'Foods', 
    'Pets', 
    'Animals',
    'Paintings',
    'Books',
    'jewilaries',
    'Watches',
    'Shoes',
    'Bags',
    'Phones',
    'Laptops',
    'Desktops',
    'Printers',
    'Scanners',
    'Projectors',
    'Cameras',
    'clothes'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - GoBidGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">GoBidGo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="auctions.php">Auctions</a></li>
                <li class="nav-item"><a class="nav-link active" href="categories.php">Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="templates/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="templates/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Categories Section -->
<div class="container my-5">
    <h2 class="text-center">Browse Auction Categories</h2>
    <div class="row mt-4">
        <?php foreach ($categories as $category): ?>
            <div class="col-md-3 mb-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($category); ?></h5>
                        <a href="category_items.php?category=<?php echo urlencode($category); ?>" class="btn btn-primary">View Items</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'templates/footer.php'; ?>
</body>
</html>
