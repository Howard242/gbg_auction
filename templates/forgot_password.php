<?php 
session_start();

date_default_timezone_set('Africa/Nairobi');

require '../php/db_config.php'; 
require '../vendor/autoload.php';  // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "❌ Invalid email format!";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Generate unique token
            $token = bin2hex(random_bytes(50));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token valid for 1 hour

            // Store token in database
            $stmt = $conn->prepare("INSERT INTO password_reset (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token=?, expires_at=?");
            $stmt->bind_param("sssss", $email, $token, $expires, $token, $expires);
            $stmt->execute();

            // Email Setup
            $mail = new PHPMailer(true);
            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';  
                $mail->SMTPAuth   = true;
                $mail->Username   = 'petersongary252@gmail.com';  
                $mail->Password   = 'jytc yuew hqkk tpsg';  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Email Content
                $mail->setFrom('petersongary252@gmail.com', 'GoBidGo Support');  
                $mail->addAddress($email);  
                $mail->Subject = 'Password Reset Request';
                $reset_link = "http://localhost/gbg_auction/reset_password.php?token=" . urlencode($token);
                $mail->Body = "Click the link below to reset your password:\n\n$reset_link\n\nThis link will expire in 1 hour.";
                
                $mail->send();
                $success_msg = "✅ A password reset link has been sent to your email!";
            } catch (Exception $e) {
                $error_msg = "❌ Failed to send reset email!";
            }
        } else {
            $error_msg = "❌ No account found with that email!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - GoBidGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        .container h2 {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .container button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .container button:hover {
            background-color: #0056b3;
        }

        .message {
            font-size: 14px;
            margin-bottom: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Forgot Password</h2>

    <?php if (!empty($success_msg)): ?>
        <p class="message" style="color: green;"><?php echo htmlspecialchars($success_msg); ?></p>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <p class="message" style="color: red;"><?php echo htmlspecialchars($error_msg); ?></p>
    <?php endif; ?>

    <form action="forgot_password.php" method="post">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <p><a href="login.php">Back to Login</a></p>
</div>

</body>
</html>
