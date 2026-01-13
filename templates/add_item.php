<?php 
session_start();
include '../php/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is an approved seller
$stmt = $conn->prepare("SELECT status FROM sellers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

if ($status !== 'approved') {
    echo "<script>alert('❌ You must be an approved seller to add items.'); window.location.href='../profile.php';</script>";
    exit();
}

// Define categories
$categories = [
    'Electronics', 'Vehicles', 'Real Estate', 'Furniture',  'Fashion', 'Sports',  
    'Cutlery', 'Groceries',  'Animal Feeds',  'Stationeries',  'Foods',  'Pets',  
    'Animals','Paintings', 'Books','jewelries', 'Watches', 'Shoes', 'Bags', 
    'Phones', 'Laptops', 'Desktops', 'Printers', 'Scanners', 'Projectors', 
    'Cameras','Clothes'
];

$item_error = "";
$item_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $auction_type = trim($_POST['auction_type']);
    $buy_now_price = isset($_POST['buy_now_price']) ? floatval($_POST['buy_now_price']) : null;
    $starting_price = isset($_POST['starting_price']) ? floatval($_POST['starting_price']) : null;
    $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : null;
    $image_name = "";

    // Validate category
    if (!in_array($category, $categories)) {
        $item_error = "❌ Invalid category selected.";
    }

    // Validate price based on auction type
    if ($auction_type === 'buy_now' && (!$buy_now_price || $buy_now_price <= 0)) {
        $item_error = "❌ Please enter a valid Buy Now price.";
    }
    if (($auction_type === 'live' || $auction_type === 'timed') && (!$starting_price || $starting_price <= 0)) {
        $item_error = "❌ Please enter a valid Starting Price.";
    }
    if (($auction_type === 'live' || $auction_type === 'timed') && empty($end_time)) {
        $item_error = "❌ Please select an auction end time.";
    }

    // Validate quantity for Buy Now
    if ($auction_type === 'buy_now' && (!$quantity || $quantity <= 0)) {
        $item_error = "❌ Please enter a valid quantity.";
    }

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = time() . "_" . basename($_FILES['image']['name']);
        $target_path = __DIR__ . "/../uploads/" . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $item_error = "❌ Failed to upload image.";
        }
    } else {
        $item_error = "❌ Image is required.";
    }

    // Set status for Buy Now items
    $status = ($auction_type === 'buy_now') ? 'available' : 'active';

    if (!$item_error) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO auctions (seller_id, title, description, starting_price, buy_now_price, quantity, image, category, auction_type, end_time, created_at, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("issdddsssss", $user_id, $title, $description, $starting_price, $buy_now_price, $quantity, $image_name, $category, $auction_type, $end_time, $status);
        
        if ($stmt->execute()) {
            $item_success = "✅ Item added successfully!";
        } else {
            $item_error = "❌ Error adding item.";
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
    <title>Add Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Add an Item</h2>

    <?php if (!empty($item_error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($item_error); ?></div>
    <?php endif; ?>

    <?php if (!empty($item_success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($item_success); ?></div>
    <?php endif; ?>

    <form action="add_item.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Item Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Select Category</label>
            <select name="category" class="form-control" required>
                <option value="" disabled selected>Select a category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Item Type</label>
            <select name="auction_type" class="form-control" id="auctionType" required>
                <option value="live">Live Auction</option>
                <option value="timed">Timed Auction</option>
                <option value="buy_now">Buy Now</option>
            </select>
        </div>

        <div class="mb-3" id="startingPriceField">
            <label class="form-label">Starting Price (Ksh)</label>
            <input type="number" name="starting_price" class="form-control" step="0.01">
        </div>

        <div class="mb-3 d-none" id="buyNowPriceField">
            <label class="form-label">Buy Now Price (Ksh)</label>
            <input type="number" name="buy_now_price" class="form-control" step="0.01">
        </div>

        <div class="mb-3 d-none" id="quantityField">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control">
        </div>

        <!-- New End Time Field (only for Live or Timed Auctions) -->
        <div class="mb-3 d-none" id="endTimeField">
            <label class="form-label">Auction End Time</label>
            <input type="datetime-local" name="end_time" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Upload Image</label>
            <input type="file" name="image" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Submit Item</button>
    </form>
</div>

<script>
    document.getElementById('auctionType').addEventListener('change', function() {
        let auctionType = this.value;

        document.getElementById("startingPriceField").classList.toggle("d-none", auctionType === "buy_now");
        document.getElementById("buyNowPriceField").classList.toggle("d-none", auctionType !== "buy_now");
        document.getElementById("quantityField").classList.toggle("d-none", auctionType !== "buy_now");

        // Show/hide end time based on auction type
        document.getElementById("endTimeField").classList.toggle("d-none", auctionType === "buy_now");
    });
</script>

</body>
</html>

