<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "gbg_auction";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $stmt = $conn->prepare("SELECT user_input, bot_response FROM chatbot_responses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($user_input, $bot_response);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $user_input = strtolower(trim($_POST["user_input"]));
    $bot_response = trim($_POST["bot_response"]);

    $stmt = $conn->prepare("UPDATE chatbot_responses SET user_input=?, bot_response=? WHERE id=?");
    $stmt->bind_param("ssi", $user_input, $bot_response, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Response updated successfully!'); window.location.href='chatbot_admin.php';</script>";
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
    <title>Edit Response</title>
</head>
<body>
    <h2>Edit Chatbot Response</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $_GET["id"]; ?>">
        <label>User Input:</label>
        <input type="text" name="user_input" value="<?php echo htmlspecialchars($user_input); ?>" required>
        <br>
        <label>Bot Response:</label>
        <input type="text" name="bot_response" value="<?php echo htmlspecialchars($bot_response); ?>" required>
        <br>
        <button type="submit">Update Response</button>
    </form>
</body>
</html>
