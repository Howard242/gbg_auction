<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(strip_tags($_POST["name"]));
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags($_POST["message"]));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("❌ Invalid email format!");
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Update if using another provider
        $mail->SMTPAuth   = true;
        $mail->Username   = 'petersongary252@gmail.com';  // Replace with your email
        $mail->Password   = 'jytc yuew hqkk tpsg';  // Use an App Password for Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email Content
        $mail->setFrom($email, $name);
        $mail->addAddress('howardmukoma242@gmail.com');  // Your email where messages are sent
        $mail->Subject = 'Support Inquiry from ' . $name;
        $mail->Body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

        $mail->send();
        echo "✅ Message sent successfully!";
    } catch (Exception $e) {
        echo "❌ Message could not be sent. Mailer Error: " . $mail->ErrorInfo;
    }
} else {
    header("Location: ../contact.php");
    exit();
}
?>
