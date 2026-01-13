<?php  
session_start();

date_default_timezone_set('Africa/Nairobi');

// Ensure db_config.php is included correctly
$path = __DIR__ . '/php/db_config.php';
if (file_exists($path)) {
    include $path;
} else {
    die("❌ Database configuration file not found!");
}

$error_msg = "";
$success_msg = "";
$email = "";

// Check if the token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error_msg = "❌ No token provided!";
} else {
    $token = $_GET['token'];

    // Verify token exists and is not expired
    $stmt = $conn->prepare("SELECT email FROM password_reset WHERE token = ? AND expires_at > NOW()");
    if (!$stmt) {
        die("❌ SQL error - " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($email);
        $stmt->fetch();

        // Handle form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_password = trim($_POST["password"]);
            $confirm_password = trim($_POST["confirm_password"]);

            if (strlen($new_password) < 6) {
                $error_msg = "❌ Password must be at least 6 characters!";
            } elseif ($new_password !== $confirm_password) {
                $error_msg = "❌ Passwords do not match!";
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update user's password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                if (!$stmt) {
                    die("❌ SQL error - " . $conn->error);
                }
                $stmt->bind_param("ss", $hashed_password, $email);
                if ($stmt->execute()) {
                    // Delete token after successful reset
                    $stmt = $conn->prepare("DELETE FROM password_reset WHERE email = ?");
                    if (!$stmt) {
                        die("❌ SQL error - " . $conn->error);
                    }
                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    $success_msg = "✅ Password reset successful! <a href='login.php'>Login Now</a>";
                } else {
                    $error_msg = "❌ Error updating password. Please try again.";
                }
            }
        }
    } else {
        $error_msg = "❌ Invalid or expired token!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GoBidGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2); width: 400px; text-align: center; animation: fadeIn 0.5s ease-in-out; }
        .container h2 { color: #007bff; font-weight: bold; margin-bottom: 20px; }
        .container input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        .container button { width: 100%; padding: 12px; border: none; border-radius: 5px; background-color: #007bff; color: white; font-size: 18px; cursor: pointer; }
        .container button:hover { background-color: #0056b3; }
        .message { font-size: 14px; margin-bottom: 10px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    <h2>Reset Password</h2>

    <?php if (!empty($success_msg)): ?>
        <p class="message" style="color: green;"><?php echo htmlspecialchars($success_msg); ?></p>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <p class="message" style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <?php if (!empty($email) && empty($success_msg)): ?>
        <form action="" method="post">
            <input type="password" name="password" placeholder="Enter new password" required>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
