<?php  
session_start();
include '../php/db_config.php'; 

// Allow user to stay on login page instead of redirecting
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); 
    exit();
}

// Initialize error message
$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Fetch user details including email
    $stmt = $conn->prepare("SELECT id, username, password, email FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $db_username, $db_password, $db_email); // Include email in the result
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            // Store user data in the session
            $_SESSION["user_id"] = $id;
            $_SESSION["username"] = $db_username;
            $_SESSION["user_email"] = $db_email; // Store the email in the session

            header("Location: ../index.php");
            exit();
        } else {
            $login_error = "❌ Incorrect password!";
        }
    } else {
        $login_error = "❌ User not found!";
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
    <title>Login - GoBidGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* General Body Styling */
        body {
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Login Container */
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        /* Login Header */
        .login-container h2 {
            margin-bottom: 20px;
            font-weight: bold;
            color: #007bff;
        }

        /* Input Fields */
        .login-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Login Button */
        .login-container button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
        }

        .login-container button:hover {
            background-color: #0056b3;
        }

        /* Error Message */
        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Links */
        .login-container p {
            margin-top: 15px;
            font-size: 14px;
        }

        .login-container a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        .login-container a:hover {
            text-decoration: underline;
        }

        /* Fade-in Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login to GoBidGo</h2>

    <?php if (!empty($login_error)): ?>
        <p class="error-message"><?php echo htmlspecialchars($login_error); ?></p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <p><a href="forgot_password.php">Forgot Password?</a></p> <!-- Added Forgot Password Link -->

    <p>Don't have an account? <a href="register.php">Register</a></p>
</div>

</body>
</html>