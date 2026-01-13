<?php
session_start();
session_unset(); // Clear session variables
session_destroy(); // Destroy old session to force fresh login
session_start(); // Start a new session

include '../php/db_config.php'; // Ensure correct database connection

$error = ""; // Store error messages

// If admin is already logged in, redirect to dashboard
if (!empty($_SESSION["admin_logged_in"])) {
    header("Location: index.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Set session variables
            $_SESSION["admin_logged_in"] = true;
            $_SESSION["admin_id"] = $id;
            $_SESSION["admin_username"] = $username;

            header("Location: index.php"); // Redirect to dashboard
            exit();
        } else {
            $error = "⚠️ Invalid password.";
        }
    } else {
        $error = "⚠️ Admin user not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin Login</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        .error {
            color: red;
            font-size: 14px;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="text-center">Admin Login</h2>

    <?php if (!empty($error)): ?>
        <p class="error text-center"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Admin Username" required>
        </div>
        <div class="mb-3 password-container">
            <label>Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Admin Password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>

<script>
    function togglePassword() {
        var passwordField = document.getElementById("password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
</script>

</body>
</html>
