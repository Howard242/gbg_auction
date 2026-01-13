<?php
include 'templates/header.php';
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email, phone, profile_picture, auto_bidding_enabled FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch shipping and payment details if available
$shipping_sql = "SELECT shipping_address, nearest_town, postal_code, phone_number, delivery_instructions, payment_method, delivery_service_provider FROM buyers WHERE user_id = ?";
$shipping_stmt = $conn->prepare($shipping_sql);
$shipping_stmt->bind_param("i", $user_id);
$shipping_stmt->execute();
$shipping_result = $shipping_stmt->get_result();
$shipping_details = $shipping_result->fetch_assoc();

// Set existing values or default to empty
$shipping_address = $shipping_details['shipping_address'] ?? "";
$nearest_town = $shipping_details['nearest_town'] ?? "";
$postal_code = $shipping_details['postal_code'] ?? "";
$phone = $shipping_details['phone'] ?? "";
$delivery_instructions = $shipping_details['delivery_instructions'] ?? "";
$payment_method = $shipping_details['payment_method'] ?? "";
$delivery_service_provider = $shipping_details['delivery_service_provider'] ?? "";

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $update_sql = "UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $username, $email, $phone, $user_id);

        if ($update_stmt->execute()) {
            echo "<script>alert('Profile updated successfully!'); window.location.href='settings.php';</script>";
        }
    }

    // Handle auto-bidding toggle
    if (isset($_POST['toggle_auto_bidding'])) {
        $new_status = isset($_POST['auto_bidding']) ? 1 : 0;
        $update_auto_sql = "UPDATE users SET auto_bidding_enabled = ? WHERE id = ?";
        $update_auto_stmt = $conn->prepare($update_auto_sql);
        $update_auto_stmt->bind_param("ii", $new_status, $user_id);

        if ($update_auto_stmt->execute()) {
            echo "<script>alert('Auto-bidding preference updated!'); window.location.href='settings.php';</script>";
        }
    }

    // Handle profile picture upload
    if (isset($_POST['upload_picture'])) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check !== false) {
            if ($_FILES["profile_picture"]["size"] > 5000000) { // 5MB limit
                echo "<script>alert('Sorry, your file is too large.'); window.location.href='settings.php';</script>";
            } else {
                if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                    echo "<script>alert('Sorry, only JPG, JPEG, PNG & GIF files are allowed.'); window.location.href='settings.php';</script>";
                } else {
                    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                        $update_picture_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                        $update_picture_stmt = $conn->prepare($update_picture_sql);
                        $update_picture_stmt->bind_param("si", $target_file, $user_id);

                        if ($update_picture_stmt->execute()) {
                            echo "<script>alert('Profile picture updated successfully!'); window.location.href='settings.php';</script>";
                        } else {
                            echo "<script>alert('Error updating profile picture.'); window.location.href='settings.php';</script>";
                        }
                    } else {
                        echo "<script>alert('Sorry, there was an error uploading your file.'); window.location.href='settings.php';</script>";
                    }
                }
            }
        } else {
            echo "<script>alert('File is not an image.'); window.location.href='settings.php';</script>";
        }
    }

    // Handle shipping and payment details update
    if (isset($_POST['update_shipping_payment'])) {
        $shipping_address = trim($_POST['shipping_address']);
        $nearest_town = trim($_POST['nearest_town']);
        $postal_code = trim($_POST['postal_code']);
        $phone = trim($_POST['phone']);
        $delivery_instructions = trim($_POST['delivery_instructions']);
        $payment_method = trim($_POST['payment_method']);
        $delivery_service_provider = trim($_POST['delivery_service_provider']);

        // Check if the user already has shipping details
        $check_sql = "SELECT * FROM buyers WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing shipping details
            $update_shipping_sql = "UPDATE buyers SET shipping_address = ?, nearest_town = ?, postal_code = ?, phone = ?, delivery_instructions = ?, payment_method = ?, delivery_service_provider = ? WHERE user_id = ?";
            $update_shipping_stmt = $conn->prepare($update_shipping_sql);
            $update_shipping_stmt->bind_param("sssssssi", $shipping_address, $nearest_town, $postal_code, $phone, $delivery_instructions, $payment_method, $delivery_service_provider, $user_id);
        } else {
            // Insert new shipping details
            $insert_shipping_sql = "INSERT INTO buyers (user_id, shipping_address, nearest_town, postal_code, phone, delivery_instructions, payment_method, delivery_service_provider) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $update_shipping_stmt = $conn->prepare($insert_shipping_sql);
            $update_shipping_stmt->bind_param("isssssss", $user_id, $shipping_address, $nearest_town, $postal_code, $phone, $delivery_instructions, $payment_method, $delivery_service_provider);
        }

        if ($update_shipping_stmt->execute()) {
            echo "<script>alert('Shipping and payment details updated successfully!'); window.location.href='settings.php';</script>";
        } else {
            echo "<script>alert('Error updating shipping and payment details.'); window.location.href='settings.php';</script>";
        }
    }
}
?>

<div class="container my-5">
    <h2 class="text-center">Account Settings</h2>

    <div class="row">
        <!-- Profile Picture Section -->
        <div class="col-md-4 text-center">
            <h4>Profile Picture</h4>
            <img src="<?php echo isset($user['profile_picture']) ? $user['profile_picture'] : 'uploads/profiles/default.jpg'; ?>" class="rounded-circle img-thumbnail" width="150">
            <form action="settings.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" class="form-control my-2" required>
                <button type="submit" name="upload_picture" class="btn btn-primary">Upload</button>
            </form>
        </div>

        <div class="col-md-4">
            <h4>Update Profile</h4>
            <form action="settings.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone" value="<?php echo $user['phone']; ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

        <div class="col-md-4">
            <h4>Change Password</h4>
            <form action="settings.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
            </form>

            <h4 class="mt-4 text-danger">Delete Account</h4>
            <form action="settings.php" method="POST">
                <button type="submit" name="delete_account" class="btn btn-danger" onclick="return confirm('Are you sure? This action cannot be undone.');">Delete Account</button>
            </form>
        </div>
    </div>

    <!-- Shipping and Payment Details Section -->
    <div class="row mt-5">
        <div class="col-md-8 mx-auto">
            <h4>Update Shipping and Payment Details</h4>
            <form action="settings.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Shipping Address</label>
                    <input type="text" class="form-control" name="shipping_address" value="<?php echo $shipping_address; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nearest Town/City</label>
                    <input type="text" class="form-control" name="nearest_town" value="<?php echo $nearest_town; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Postal Code</label>
                    <input type="text" class="form-control" name="postal_code" value="<?php echo $postal_code; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone" value="<?php echo $phone; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Delivery Instructions</label>
                    <textarea class="form-control" name="delivery_instructions"><?php echo $delivery_instructions; ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Preferred Payment Method</label>
                    <select class="form-control" name="payment_method" required>
                        <option value="mpesa" <?php echo ($payment_method == 'mpesa') ? 'selected' : ''; ?>>M-Pesa</option>
                        <option value="paypal" <?php echo ($payment_method == 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                        <option value="bank_transfer" <?php echo ($payment_method == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Delivery Service Provider</label>
                    <select class="form-control" name="delivery_service_provider" required>
                        <option value="G4S" <?php echo ($delivery_service_provider == 'G4S') ? 'selected' : ''; ?>>G4S</option>
                        <option value="DHL" <?php echo ($delivery_service_provider == 'DHL') ? 'selected' : ''; ?>>DHL</option>
                        <option value="FedEx" <?php echo ($delivery_service_provider == 'FedEx') ? 'selected' : ''; ?>>FedEx</option>
                        <option value="Aramex" <?php echo ($delivery_service_provider == 'Aramex') ? 'selected' : ''; ?>>Aramex</option>
                        <option value="Other" <?php echo ($delivery_service_provider == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <button type="submit" name="update_shipping_payment" class="btn btn-primary">Save Shipping and Payment Details</button>
            </form>
        </div>
    </div>

    <!-- Auto Bidding AI Feature -->
    <div class="row mt-5">
        <div class="col-md-6 mx-auto">
            <h4>Auto Bidding Settings</h4>
            <form action="settings.php" method="POST">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="auto_bidding" id="autoBiddingSwitch" <?php echo $user['auto_bidding_enabled'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="autoBiddingSwitch">Enable Auto-Bidding</label>
                </div>
                <button type="submit" name="toggle_auto_bidding" class="btn btn-info mt-3">Save Preference</button>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>