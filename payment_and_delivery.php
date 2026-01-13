<?php
session_start();
require __DIR__ . '/php/db_config.php'; 
require __DIR__ . '/vendor/autoload.php'; // Correct path

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../templates/login.php"); // Correct path to login.php
    exit();
}

$user_id = $_SESSION['user_id'];

// Get auction_id from GET or POST
$auction_id = isset($_POST['auction_id']) ? intval($_POST['auction_id']) : (isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0);

// Validate auction_id
if ($auction_id <= 0) {
    die("Invalid auction ID.");
}

// Fetch auction details using prepared statements
$sql = "SELECT a.*, u.email AS seller_email FROM auctions a 
        JOIN users u ON a.seller_id = u.id 
        WHERE a.id = ? AND a.buyer_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("ii", $auction_id, $user_id);
if (!$stmt->execute()) {
    die("Query execution failed: " . $stmt->error);
}
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("You are not the winner of this auction.");
}

$auction = $result->fetch_assoc();
if (!$auction) {
    die("Invalid auction data.");
}

$seller_email = htmlspecialchars($auction['seller_email']);
$item_name = htmlspecialchars($auction['title']);
$final_price = htmlspecialchars($auction['final_price']);
$auction_type = htmlspecialchars($auction['category']);
$location = isset($auction['location']) ? htmlspecialchars($auction['location']) : '';

// If the request is a GET request, display the form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment & Delivery - <?php echo $item_name; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <link rel="stylesheet" href="../css/style.css">
        <style>
            /* Add your CSS styles here */
            body {
                font-family: Arial, sans-serif;
                background-color: #f9f9f9;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            h2 {
                color: #007bff;
            }
            label {
                display: block;
                margin: 10px 0 5px;
                font-weight: bold;
            }
            input, select, textarea {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
            }
            .submit-button {
                background-color: #007bff;
                color: white;
                padding: 12px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                width: 100%;
            }
            .submit-button:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Complete Your Purchase</h2>
            <p>Item: <strong><?php echo $item_name; ?></strong></p>
            <p>Final Price: <strong>KES <?php echo number_format($final_price, 2); ?></strong></p>

            <form action="process_payment_delivery.php" method="post">
                <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">

                <!-- Shipping Address -->
                <div class="mb-3">
                    <label for="shipping_address" class="form-label">Shipping Address:</label>
                    <input type="text" name="shipping_address" id="shipping_address" class="form-control" required>
                </div>

                <!-- Nearest Town/City -->
                <div class="mb-3">
                    <label for="nearest_town" class="form-label">Nearest Town/City:</label>
                    <input type="text" name="nearest_town" id="nearest_town" class="form-control" required>
                </div>

                <!-- Postal Code -->
                <div class="mb-3">
                    <label for="postal_code" class="form-label">Postal Code:</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control">
                </div>

                <!-- Phone Number -->
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number:</label>
                    <input type="tel" name="phone_number" id="phone_number" class="form-control" required>
                </div>

                <!-- Delivery Instructions -->
                <div class="mb-3">
                    <label for="delivery_instructions" class="form-label">Delivery Instructions (Optional):</label>
                    <textarea name="delivery_instructions" id="delivery_instructions" class="form-control"></textarea>
                </div>

                <!-- Payment Method -->
                <div class="mb-3">
                    <label for="payment_method" class="form-label">Preferred Payment Method:</label>
                    <select name="payment_method" id="payment_method" class="form-select" required>
                        <option value="">--Select Payment Method--</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <!-- Delivery Service Provider -->
                <div class="mb-3">
                    <label for="delivery_service_provider" class="form-label">Delivery Service Provider:</label>
                    <select name="delivery_service_provider" id="delivery_service_provider" class="form-select" required>
                        <option value="">--Select Delivery Service Provider--</option>
                        <option value="G4S">G4S</option>
                        <option value="DHL">DHL</option>
                        <option value="FedEx">FedEx</option>
                        <option value="Aramex">Aramex</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-button">Submit</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit(); // Stop further execution
}

// If the request is a POST request, process the form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = htmlspecialchars($_POST['shipping_address']);
    $nearest_town = htmlspecialchars($_POST['nearest_town']);
    $postal_code = htmlspecialchars($_POST['postal_code']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $delivery_instructions = htmlspecialchars($_POST['delivery_instructions']);
    $payment_method = htmlspecialchars($_POST['payment_method']);
    $delivery_service_provider = htmlspecialchars($_POST['delivery_service_provider']);

    // Update the auctions table with buyer_id and payment details
    $update_auction_query = "UPDATE auctions 
                             SET buyer_id = ?, 
                                 payment_method = ?, 
                                 delivery_service_provider = ?, 
                                 shipping_address = ?, 
                                 nearest_town = ?, 
                                 postal_code = ?, 
                                 phone_number = ?, 
                                 delivery_instructions = ? 
                             WHERE id = ?";
    $stmt = $conn->prepare($update_auction_query);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("isssssssi", $user_id, $payment_method, $delivery_service_provider, $shipping_address, $nearest_town, $postal_code, $phone_number, $delivery_instructions, $auction_id);
    if (!$stmt->execute()) {
        die("Failed to update auction details: " . $stmt->error);
    }

    // Send confirmation email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'petersongary252@gmail.com'; // Change to your email
        $mail->Password = 'jytc yuew hqkk tpsg'; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Send to Seller
        $mail->setFrom('petersongary252@gmail.com', 'GoBidGo Auctions');
        $mail->addAddress($seller_email);
        $mail->Subject = "Payment Received for Auction Item";
        $mail->Body = "Your item '$item_name' has been successfully purchased for KES $final_price. The buyer has completed the process.
        \nShipping Address: $shipping_address\nNearest Town: $nearest_town\nPostal Code: $postal_code\nPhone Number: $phone_number\nDelivery Instructions: $delivery_instructions\nDelivery Service Provider: $delivery_service_provider";

        $mail->send();
        $mail->clearAddresses();

        // Send to Buyer
        if (!isset($_SESSION['user_email'])) {
            die("User email not found in session. Please log in again.");
        }
        $buyer_email = $_SESSION['user_email'];
        $mail->addAddress($buyer_email);
        $mail->Subject = "Auction Purchase Confirmation";
        $mail->Body = "Thank you for your purchase! You have won '$item_name' for KES $final_price.
        \nShipping Address: $shipping_address\nNearest Town: $nearest_town\nPostal Code: $postal_code\nPhone Number: $phone_number\nDelivery Instructions: $delivery_instructions\nDelivery Service Provider: $delivery_service_provider";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
    }

    // Store success message in session 
    $_SESSION['success_message'] = "Purchase details submitted successfully!";

    // Redirect to login page
    header("Location: http://localhost/gbg_auction/templates/login.php");
    exit();
}
?>