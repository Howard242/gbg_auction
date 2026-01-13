<?php
// Include database connection
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if you have a password
$database = "gbg_auction"; // Your database name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all chatbot responses
$sql = "SELECT id, user_input, bot_response FROM chatbot_responses ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Admin Panel</title>
    <link rel="stylesheet" href="chatbot_admin.css">
</head>
<body>
    <div class="container">
        <h2>Chatbot Response Manager</h2>
        <a href="add_response.php" class="add-btn">➕ Add New Response</a>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Input</th>
                    <th>Bot Response</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['user_input']); ?></td>
                    <td><?php echo htmlspecialchars($row['bot_response']); ?></td>
                    <td>
                        <a href="edit_response.php?id=<?php echo $row['id']; ?>" class="edit-btn">✏️ Edit</a>
                        <a href="delete_response.php?id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure?');">❌ Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php $conn->close(); ?>
