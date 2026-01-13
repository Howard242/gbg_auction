<?php
session_start();
include '../php/db_config.php'; // Ensure the correct database connection path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Check if the user exists
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $db_username, $db_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $db_password)) {
            // Secure session handling
            session_regenerate_id(true);
            $_SESSION["user_id"] = $id;
            $_SESSION["username"] = $db_username;

            header("Location: ../index.php"); // Redirect to homepage after login
            exit();
        } else {
            $_SESSION["error"] = "Incorrect password!";
            header("Location: ../templates/login.php");
            exit();
        }
    } else {
        $_SESSION["error"] = "User not found!";
        header("Location: ../templates/login.php");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
