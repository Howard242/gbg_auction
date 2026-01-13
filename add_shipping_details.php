<?php
session_start();
include 'php/db_config.php';

if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access!";
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Fetch existing details if available
$query = "SELECT * FROM buyers WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userDetails = $result->fetch_assoc();
$stmt->close();

// Set existing values or default to empty
$shipping_address = $userDetails['shipping_address'] ?? "";
$nearest_town = $userDetails['nearest_town'] ?? "";
$postal_code = $userDetails['postal_code'] ?? "";
$phone_number = $userDetails['phone_number'] ?? "";
$delivery_instructions = $userDetails['delivery_instructions'] ?? "";
$payment_method = $userDetails['payment_method'] ?? "";
$payment_details = $userDetails['payment_details'] ?? "";
$delivery_service_provider = $userDetails['delivery_service_provider'] ?? "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $shipping_address = trim($_POST['shipping_address']);
    $nearest_town = trim($_POST['nearest_town']);
    $postal_code = trim($_POST['postal_code']);
    $phone_number = trim($_POST['phone_number']);
    $delivery_instructions = trim($_POST['delivery_instructions']);
    $payment_method = trim($_POST['payment_method']);
    $payment_details = trim($_POST['payment_details']);
    $delivery_service_provider = trim($_POST['delivery_service_provider']);

    // Validate required fields
    if (empty($shipping_address) || empty($nearest_town) || empty($phone_number) || empty($payment_method) || empty($payment_details) || empty($delivery_service_provider)) {
        $error = "All required fields must be filled!";
    } else {
        if ($userDetails) {
            // Update existing details
            $updateQuery = "UPDATE buyers SET shipping_address = ?, nearest_town = ?, postal_code = ?, phone_number = ?, 
                            delivery_instructions = ?, payment_method = ?, payment_details = ?, delivery_service_provider = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssssssssi", $shipping_address, $nearest_town, $postal_code, $phone_number, 
                              $delivery_instructions, $payment_method, $payment_details, $delivery_service_provider, $user_id);
        } else {
            // Insert new details
            $insertQuery = "INSERT INTO buyers (user_id, shipping_address, nearest_town, postal_code, phone_number, 
                            delivery_instructions, payment_method, payment_details, delivery_service_provider) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("issssssss", $user_id, $shipping_address, $nearest_town, $postal_code, $phone_number, 
                              $delivery_instructions, $payment_method, $payment_details, $delivery_service_provider);
        }

        if ($stmt->execute()) {
            $success = "Details saved successfully!";

            // Redirect based on payment method
            switch ($payment_method) {
                case 'mpesa':
                    header("Location: mpesa_payment.php"); // Redirect to M-Pesa payment page
                    exit();
                case 'paypal':
                    header("Location: paypal_payment.php"); // Redirect to PayPal payment page
                    exit();
                case 'bank_transfer':
                    header("Location: bank_transfer_payment.php"); // Redirect to Bank Transfer payment page
                    exit();
                default:
                    header("Location: index.php"); // Default redirect
                    exit();
            }
        } else {
            $error = "Error saving details. Please try again.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping & Payment Details</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'templates/header.php'; ?>

    <div class="container mt-4">
        <h2 class="text-center">Shipping & Payment Details</h2>

        <!-- Display error or success message -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card p-4 shadow">
            <form method="POST" action="add_shipping_details.php">
                <div class="mb-3">
                    <label for="shipping_address" class="form-label">Shipping Address:</label>
                    <input type="text" name="shipping_address" id="shipping_address" class="form-control" value="<?php echo htmlspecialchars($shipping_address); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nearest_town" class="form-label">Nearest Town/City:</label>
                    <input type="text" name="nearest_town" id="nearest_town" class="form-control" value="<?php echo htmlspecialchars($nearest_town); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="postal_code" class="form-label">Postal Code:</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control" value="<?php echo htmlspecialchars($postal_code); ?>">
                </div>

                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number:</label>
                    <input type="text" name="phone_number" id="phone_number" class="form-control" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="delivery_instructions" class="form-label">Delivery Instructions (Optional):</label>
                    <textarea name="delivery_instructions" id="delivery_instructions" class="form-control"><?php echo htmlspecialchars($delivery_instructions); ?></textarea>
                </div>

                <!-- Delivery Service Provider -->
                <div class="mb-3">
                    <label for="delivery_service_provider" class="form-label">Delivery Service Provider:</label>
                    <select name="delivery_service_provider" id="delivery_service_provider" class="form-select" required>
                        <option value="">--Select Delivery Service Provider--</option>
                        <option value="G4S" <?php echo ($delivery_service_provider == 'G4S') ? 'selected' : ''; ?>>G4S</option>
                        <option value="DHL" <?php echo ($delivery_service_provider == 'DHL') ? 'selected' : ''; ?>>DHL</option>
                        <option value="FedEx" <?php echo ($delivery_service_provider == 'FedEx') ? 'selected' : ''; ?>>FedEx</option>
                        <option value="Aramex" <?php echo ($delivery_service_provider == 'Aramex') ? 'selected' : ''; ?>>Aramex</option>
                        <option value="Other" <?php echo ($delivery_service_provider == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <h3 class="mt-4">Payment Details</h3>

                <div class="mb-3">
                    <label for="payment_method" class="form-label">Preferred Payment Method:</label>
                    <select name="payment_method" id="payment_method" class="form-select" required>
                        <option value="">--Select Payment Method--</option>
                        <option value="mpesa" <?php echo ($payment_method == 'mpesa') ? 'selected' : ''; ?>>M-Pesa</option>
                        <option value="paypal" <?php echo ($payment_method == 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                        <option value="bank_transfer" <?php echo ($payment_method == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="payment_details" class="form-label">Payment Details (Phone Number, Account No, PayPal Email, etc.):</label>
                    <input type="text" name="payment_details" id="payment_details" class="form-control" value="<?php echo htmlspecialchars($payment_details); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Save Details</button>
            </form>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'templates/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>