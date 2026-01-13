<?php
session_start();
include '../php/db_config.php'; // Ensure this file contains the correct database connection setup
include 'header.php';// Include the header file

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the user is an approved seller
$stmt = $conn->prepare("SELECT id FROM sellers WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($seller_id);
$stmt->fetch();
$stmt->close();

if (!$seller_id) {
    echo "<script>alert('You are not an approved seller.'); window.location.href='../profile.php';</script>";
    exit();
}

// Handle Item Updates (Price & Description)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $new_price = $_POST['starting_price'];
    $new_description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE auctions SET starting_price = ?, description = ? WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("dsii", $new_price, $new_description, $item_id, $seller_id);
    if ($stmt->execute()) {
        echo "<script>alert('Item updated successfully!'); window.location.href='manage_sold_items.php';</script>";
    } else {
        echo "<script>alert('Error updating item.');</script>";
    }
    $stmt->close();
}

// Handle Item Deletion
if (isset($_GET['delete'])) {
    $item_id = $_GET['delete'];

    // Ensure the item belongs to the seller and is not sold
    $stmt = $conn->prepare("SELECT status FROM auctions WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $item_id, $seller_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    if ($status === 'sold') {
        echo "<script>alert('You cannot remove a sold item!'); window.location.href='manage_sold_items.php';</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM auctions WHERE id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $item_id, $seller_id);
        if ($stmt->execute()) {
            echo "<script>alert('Item removed successfully!'); window.location.href='manage_sold_items.php';</script>";
        } else {
            echo "<script>alert('Error removing item.');</script>";
        }
        $stmt->close();
    }
}

// Fetch seller's items with the highest bid
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.description, a.starting_price, 
           COALESCE(MAX(b.bid_amount), a.starting_price) AS current_bid, 
           a.status, a.buyer_id 
    FROM auctions a 
    LEFT JOIN bids b ON a.id = b.auction_id 
    WHERE a.seller_id = ? 
    GROUP BY a.id
");

$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stmt->bind_result($id, $title, $description, $starting_price, $current_bid, $status, $buyer_id);
$items = [];
while ($stmt->fetch()) {
    $items[] = [
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'starting_price' => $starting_price,
        'current_bid' => $current_bid, // Corrected to fetch the highest bid
        'status' => $status,
        'buyer_id' => $buyer_id
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Auction Items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { margin-top: 30px; }
        .table { background: white; border-radius: 8px; }
        .btn { margin: 2px; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Manage Your Auction Items</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Starting Price (Ksh)</th>
                <th>Current Bid (Ksh)</th>
                <th>Status</th>
                <th>Buyer</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>Ksh <?php echo number_format($item['starting_price'], 2); ?></td>
                    <td>Ksh <?php echo number_format($item['current_bid'], 2); ?></td> <!-- Corrected to display current bid -->
                    <td>
                        <?php if ($item['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php elseif ($item['status'] === 'expired'): ?>
                            <span class="badge bg-warning">Expired</span>
                        <?php elseif ($item['status'] === 'sold'): ?>
                            <span class="badge bg-danger">Sold</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        if ($item['buyer_id']) {
                            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                            $stmt->bind_param("i", $item['buyer_id']);
                            $stmt->execute();
                            $stmt->bind_result($buyer_name);
                            $stmt->fetch();
                            echo htmlspecialchars($buyer_name);
                            $stmt->close();
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($item['status'] !== 'sold'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="text" name="starting_price" value="<?php echo $item['starting_price']; ?>" class="form-control" style="width: 80px; display:inline;">
                                <input type="text" name="description" value="<?php echo htmlspecialchars($item['description']); ?>" class="form-control" style="width: 150px; display:inline;">
                                <button type="submit" name="update_item" class="btn btn-primary btn-sm">Update</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($item['status'] === 'expired'): ?>
                            <a href="../php/relist_item.php?item_id=<?php echo $item['id']; ?>&title=<?php echo urlencode($item['title']); ?>" class="btn btn-success btn-sm">Re-list</a>
                        <?php endif; ?>

                        <?php if ($item['status'] !== 'sold'): ?>
                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Remove</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; // Include the footer file ?>
</body>
</html>