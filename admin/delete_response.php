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

    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM chatbot_responses WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Response deleted successfully!'); window.location.href='chatbot_admin.php';</script>";
    } else {
        echo "Error deleting response: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
