<?php
session_start();
include 'php/db_config.php';
include 'templates/header.php';

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Remove item from cart
if (isset($_GET['remove'])) {
    $item_id = $_GET['remove'];
    if (isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
        echo "<script>alert('Item removed from cart.'); window.location.href='cart.php';</script>";
    }
}

// Update item quantity in cart
if (isset($_POST['update_quantity'])) {
    $item_id = $_POST['item_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity < 1) {
        $quantity = 1; // Ensure quantity is at least 1
    }

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] = $quantity;
        echo "<script>alert('Quantity updated.'); window.location.href='cart.php';</script>";
    }
}

// Calculate total price
$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - GoBidGo</title>
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
    </style>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">🛒 Your Cart</h2>

    <?php if (!empty($_SESSION['cart'])): ?>
        <div class="row">
            <?php foreach ($_SESSION['cart'] as $item_id => $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p><strong>💰 Price: Ksh <?php echo number_format($item['price'], 2); ?></strong></p>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                <label for="quantity-<?php echo $item_id; ?>">Quantity:</label>
                                <input type="number" name="quantity" id="quantity-<?php echo $item_id; ?>" value="<?php echo $item['quantity']; ?>" min="1" class="form-control mb-2" required>
                                <button type="submit" name="update_quantity" class="btn btn-primary w-100">Update Quantity</button>
                            </form>
                            <a href="cart.php?remove=<?php echo $item_id; ?>" class="btn btn-danger w-100 mt-2">Remove</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <h4>Total: Ksh <?php echo number_format($total_price, 2); ?></h4>
            <form method="POST" action="buy_now.php">
                <button type="submit" name="cashout" class="btn btn-success">💵 Proceed to Checkout</button>
            </form>
        </div>
    <?php else: ?>
        <p class="alert alert-warning text-center">Your cart is empty. <a href="buy_now.php">Continue shopping</a>.</p>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>