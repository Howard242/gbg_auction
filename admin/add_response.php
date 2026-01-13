<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "gbg_auction";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = strtolower(trim($_POST["user_input"]));
    $bot_response = trim($_POST["bot_response"]);

    $stmt = $conn->prepare("INSERT INTO chatbot_responses (user_input, bot_response) VALUES (?, ?)");
    $stmt->bind_param("ss", $user_input, $bot_response);

    if ($stmt->execute()) {
        echo "<script>alert('Response added successfully!'); window.location.href='chatbot_admin.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Response</title>
</head>
<body>
    <h2>Add New Chatbot Response</h2>
    <form method="POST">
        <label>User Input:</label>
        <input type="text" name="user_input" required>
        <br>
        <label>Bot Response:</label>
        <input type="text" name="bot_response" required>
        <br>
        <button type="submit">Add Response</button>
    </form>
</body>
</html>
