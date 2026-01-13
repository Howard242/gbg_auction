<?php include '../php/db_config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GoBidGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Page Styling */
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .register-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }

        .register-container h2 {
            margin-bottom: 20px;
            font-weight: bold;
            color: #333;
        }

        .register-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }

        .register-container input:focus {
            border-color: #007bff;
            outline: none;
        }

        .register-container button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }

        .register-container button:hover {
            background-color: #0056b3;
        }

        .register-container p {
            margin-top: 15px;
            font-size: 14px;
        }

        .register-container a {
            color: #007bff;
            text-decoration: none;
        }

        .register-container a:hover {
            text-decoration: underline;
        }

        .terms-container {
            text-align: left;
            font-size: 14px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2>Register for GoBidGo</h2>
    <form action="../php/register_process.php" method="post" onsubmit="return validateTerms()">
        <input type="text" name="username" placeholder="Username" required>
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="location" placeholder="Location" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

        <!-- Terms and Conditions Checkbox -->
        <div class="terms-container">
            <input type="checkbox" id="terms" name="terms">
            <label for="terms">
                I agree to the <a href="terms_and_conditions.php" target="_blank">Terms and Conditions</a> and 
                <a href="privacy_policy.php" target="_blank">Privacy Policy</a>.
            </label>
        </div>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

<!-- JavaScript Validation -->
<script>
    function validateTerms() {
        var termsCheckbox = document.getElementById("terms");
        if (!termsCheckbox.checked) {
            alert("You must agree to the Terms and Conditions before registering.");
            return false;
        }
        return true;
    }
</script>

</body>
</html>
