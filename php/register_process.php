<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db_config.php'; // Ensure the database connection is included

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input values
    $username = trim($_POST["username"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $location = trim($_POST["location"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "⚠️ Passwords do not match!";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "⚠️ Username or Email already exists!";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, location, phone, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $username, $first_name, $last_name, $email, $location, $phone, $hashed_password);

            if ($stmt->execute()) {
                $success = "✅ Registration successful! You can now <a href='../templates/login.php'>Login</a>";
            } else {
                $error = "⚠️ Error: " . $stmt->error;
            }

            $stmt->close();
        }

        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            text-align: center;
        }
        .message-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        .error {
            color: red;
            font-size: 16px;
        }
        .success {
            color: green;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="message-box">
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
        <a href="../templates/register.php" class="btn btn-danger">Go Back</a>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <p class="success"><?php echo $success; ?></p>
        <a href="../templates/login.php" class="btn btn-success">Login</a>
    <?php endif; ?>
</div>

</body>
</html>
