<?php
session_start();
include 'php/db_config.php';
include 'templates/header.php';

// Fetch Buy Now items grouped by category
$query = "SELECT id, category, title, description, buy_now_price, image, quantity, rating 
          FROM auctions 
          WHERE auction_type = 'buy_now' 
          AND status = 'available' 
          ORDER BY category, created_at DESC";
$result = $conn->query($query);

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add item to cart
if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $item_title = $_POST['item_title'];
    $item_price = $_POST['item_price'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1; // Default to 1 if quantity is not set

    // Validate quantity
    if ($quantity < 1) {
        $quantity = 1; // Ensure quantity is at least 1
    }

    // Check if item is already in cart
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$item_id] = [
            'title' => $item_title,
            'price' => $item_price,
            'quantity' => $quantity
        ];
    }
}

// Calculate total price
$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// Group items by category
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[$row['category']][] = $row;
}

// Checkout logic
if (isset($_POST['cashout'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('You must be logged in to checkout.');</script>";
    } else {
        $_SESSION['total_price'] = $total_price;
        header("Location: add_shipping_details.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Now Items - GoBidGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .card {
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .description {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .read-more {
            color: blue;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">🛒 Buy Now Items</h2>

    <!-- Shopping Cart Summary -->
    <div class="text-end mb-3">
        <a href="cart.php" class="btn btn-warning">
            🛍 View Cart (<?php echo count($_SESSION['cart']); ?>) - Total: Ksh <?php echo number_format($total_price, 2); ?>
        </a>

        <!-- Cashout Button -->
        <?php if (!empty($_SESSION['cart'])): ?>
            <form method="POST" style="display:inline;">
                <button type="submit" name="cashout" class="btn btn-success">💵 Proceed to Checkout</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $category => $items): ?>
            <h3 class="mt-4"><?php echo htmlspecialchars($category); ?></h3>
            <div class="row">
                <?php foreach ($items as $row): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-4">
                            <?php 
                            $imagePath = !empty($row['image']) ? "uploads/" . htmlspecialchars($row['image']) : "assets/img/default-item.jpg"; 
                            ?>
                            <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="Item Image">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>

                                <!-- Expandable Description -->
                                <p class="card-text description"><?php echo htmlspecialchars($row['description']); ?></p>
                                <?php if (strlen($row['description']) > 100): ?>
                                    <span class="read-more" onclick="toggleDescription(this)">Read More</span>
                                <?php endif; ?>

                                <p><strong>💰 Price: Ksh <?php echo number_format($row['buy_now_price'], 2); ?></strong></p>
                                <p><strong>📦 Quantity Available: <?php echo htmlspecialchars($row['quantity']); ?></strong></p>
                                
                                <!-- Display Rating -->
                                <p><strong>⭐ Rating: <?php echo number_format($row['rating'], 1); ?> / 5</strong></p>

                                <!-- Add to Cart Form -->
                                <form method="POST">
                                    <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="item_title" value="<?php echo $row['title']; ?>">
                                    <input type="hidden" name="item_price" value="<?php echo $row['buy_now_price']; ?>">
                                    
                                    <label for="quantity-<?php echo $row['id']; ?>">Quantity:</label>
                                    <input type="number" name="quantity" id="quantity-<?php echo $row['id']; ?>" value="1" min="1" max="<?php echo $row['quantity']; ?>" class="form-control mb-2" required>

                                    <button type="submit" name="add_to_cart" class="btn btn-primary w-100">🛒 Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="alert alert-warning text-center">⚠️ No Buy Now items available at the moment.</p>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleDescription(element) {
        var description = element.previousElementSibling;
        if (description.style.webkitLineClamp) {
            description.style.webkitLineClamp = "unset";
            element.textContent = "Show Less";
        } else {
            description.style.webkitLineClamp = "2";
            element.textContent = "Read More";
        }
    }
</script>

</body>
</html>