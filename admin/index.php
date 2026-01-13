<?php   
session_start();
include '../php/db_config.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Seller Approval/Rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['seller_id']) && isset($_POST['action'])) {
    $seller_id = intval($_POST['seller_id']);
    $action = $_POST['action'];

    if ($action == 'approve') {
        $update_query = "UPDATE sellers SET status = 'approved' WHERE user_id = ?";
    } elseif ($action == 'reject') {
        $update_query = "UPDATE sellers SET status = 'rejected' WHERE user_id = ?";
    } elseif ($action == 'delete') {
        // Delete the seller from the database
        $delete_query = "DELETE FROM sellers WHERE user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $seller_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Seller deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting seller.";
        }
        $stmt->close();
        header("Location: index.php");
        exit();
    }

    if (isset($update_query)) {
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $seller_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Seller status updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating seller status.";
        }
        $stmt->close();
    }
    header("Location: index.php");
    exit();
}

// Fetch all seller applications (pending, rejected, and approved)
$query = "SELECT s.user_id, u.username, s.business_name, s.business_type, s.location, s.contact_info, s.status 
          FROM sellers s 
          JOIN users u ON s.user_id = u.id 
          ORDER BY FIELD(s.status, 'pending', 'rejected', 'approved') ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Seller Approvals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this seller? This action cannot be undone.");
        }
    </script>
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">Admin Panel - Manage Sellers</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Username</th>
                <th>Business Name</th>
                <th>Business Type</th>
                <th>Location</th>
                <th>Contact Info</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['business_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['business_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_info']); ?></td>
                    <td>
                        <?php if ($row['status'] == 'pending'): ?>
                            <span class="badge bg-warning">Pending</span>
                        <?php elseif ($row['status'] == 'approved'): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] == 'pending' || $row['status'] == 'rejected'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="seller_id" value="<?php echo $row['user_id']; ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($row['status'] == 'pending'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="seller_id" value="<?php echo $row['user_id']; ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" class="d-inline" onsubmit="return confirmDelete();">
                            <input type="hidden" name="seller_id" value="<?php echo $row['user_id']; ?>">
                            <button type="submit" name="action" value="delete" class="btn btn-outline-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
