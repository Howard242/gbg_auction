<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../php/db_config.php'; // Ensure correct DB connection

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "⚠️ Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "⚠️ Username or Email already exists!";
        } else {
            // Insert new admin
            $stmt = $conn->prepare("INSERT INTO admins (username, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                $success = "✅ Admin registered successfully!";
            } else {
                $error = "⚠️ Error: " . $stmt->error;
            }
        }

        $check_stmt->close();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Register Admin</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .register-container {
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
        .success {
            color: green;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2 class="text-center">Admin Registration</h2>

    <?php if (!empty($error)): ?>
        <p class="error text-center"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <p class="success text-center"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">👁️</button>
            </div>
        </div>
        <div class="mb-3">
            <label>Confirm Password</label>
            <div class="input-group">
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">👁️</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Register Admin</button>
    </form>
</div>

<script>
function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    field.type = (field.type === "password") ? "text" : "password";
}
</script>

</body>
</html>
