<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "myshop";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $id = $_GET["id"];

    $sql = "DELETE FROM clients WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        header("Location: index.php?msg=Client deleted successfully!");
        exit;
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
