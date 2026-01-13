<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

function sendMail($recipient, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'petersongary252@gmail.com'; // Replace with your Gmail
        $mail->Password = 'jytc yuew hqkk tpsg'; // Use App Password (not your normal Gmail password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email Details
        $mail->setFrom('petersongary252@gmail.com', 'GoBidGo Support');
        $mail->addAddress($recipient);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->isHTML(true);

        // Send Email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Add a simple SMS function (Modify it to use an actual SMS API)
function sendSMS($phone, $message) {
    $username = "your_username"; // Get this from Africa's Talking
    $apiKey = "your_api_key"; // Get this from Africa's Talking
    $from = "GoBidGo"; // Your sender name

    $data = array(
        "username" => $username,
        "to" => $phone,
        "message" => $message,
        "from" => $from
    );

    $url = "https://api.africastalking.com/version1/messaging";
    $headers = array(
        "apiKey: $apiKey",
        "Content-Type: application/x-www-form-urlencoded"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
 

?>
