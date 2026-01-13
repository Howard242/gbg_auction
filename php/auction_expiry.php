<?php
// Corrected path to db_config.php
require __DIR__ . '/db_config.php'; 

// Ensure this file connects to your database
require __DIR__ . '/../vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Africa/Nairobi'); // Set timezone

// Check if database connection is valid
if (!$conn) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch auctions that have ended but are still active
$sql = "
    SELECT 
        a.id, 
        a.seller_id, 
        a.category, 
        COALESCE(MAX(b.bid_amount), a.starting_price) AS final_price,
        (SELECT user_id FROM bids WHERE auction_id = a.id ORDER BY bid_amount DESC LIMIT 1) AS highest_bidder_id
    FROM auctions a
    LEFT JOIN bids b ON a.id = b.auction_id
    WHERE a.end_time <= NOW() AND a.status = 'active'
    GROUP BY a.id, a.seller_id;
";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

if ($result->num_rows > 0) {
    while ($auction = $result->fetch_assoc()) {
        $auction_id = $auction['id'];
        $seller_id = $auction['seller_id'];
        $buyer_id = $auction['highest_bidder_id']; // NULL if no bids
        $final_price = $auction['final_price'];
        $category = strtolower($auction['category']); // Category to determine next steps

        if (!empty($buyer_id)) {
            // Buyer found, mark auction as 'sold'
            $stmt = $conn->prepare("UPDATE auctions SET status = 'sold', final_price = ?, buyer_id = ? WHERE id = ?");
            $stmt->bind_param("dii", $final_price, $buyer_id, $auction_id);
            if (!$stmt->execute()) {
                error_log("Failed to update auction status: " . $stmt->error);
            }
            $stmt->close();

            // Send email to buyer with next steps
            sendEmail($seller_id, $buyer_id, $final_price, $auction_id, $category);
        } else {
            // No buyer, mark auction as 'expired'
            $stmt = $conn->prepare("UPDATE auctions SET status = 'expired' WHERE id = ?");
            $stmt->bind_param("i", $auction_id);
            if (!$stmt->execute()) {
                error_log("Failed to update auction status: " . $stmt->error);
            }
            $stmt->close();

            // Notify the seller
            notifySeller($seller_id, $auction_id);
        }
    }
}

// Function to notify seller and buyer on successful sale
function sendEmail($seller_id, $buyer_id, $final_price, $auction_id, $category) {
    global $conn;

    // Validate final price
    if ($final_price <= 0) {
        error_log("Invalid final price for auction ID: $auction_id");
        return;
    }

    // Get seller and buyer emails
    $stmt_seller = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_seller->bind_param("i", $seller_id);
    $stmt_seller->execute();
    $seller_result = $stmt_seller->get_result();
    $stmt_seller->close();

    $stmt_buyer = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_buyer->bind_param("i", $buyer_id);
    $stmt_buyer->execute();
    $buyer_result = $stmt_buyer->get_result();
    $stmt_buyer->close();

    if ($seller_result->num_rows > 0 && $buyer_result->num_rows > 0) {
        $seller_email = $seller_result->fetch_assoc()['email'];
        $buyer_email = $buyer_result->fetch_assoc()['email'];

        // Generate a link to the payment and delivery page
        $payment_link = "http://localhost/gbg_auction/payment_and_delivery.php?auction_id=$auction_id";

        // Email content based on auction type
        if ($category === "real estate") {
            $message = "Congratulations! You have won a real estate auction for KES $final_price. 
            \n\nPlease schedule a visit within 7 days to finalize the purchase. 
            \nClick here to proceed: $payment_link";
        } else {
            $message = "Congratulations! You have won the auction for KES $final_price. 
            \n\nPlease complete payment and provide shipping details to receive your item. 
            \nClick here to proceed: $payment_link";
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'petersongary252@gmail.com'; // Change to your email
            $mail->Password = 'jytc yuew hqkk tpsg'; // Use App Password for security
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Send to Buyer
            $mail->setFrom('petersongary252@gmail.com', 'GoBidGo Auctions');
            $mail->addAddress($buyer_email);
            $mail->Subject = "Congratulations! You Won the Auction";
            $mail->Body = $message;

            $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
        }
    }
}

// Function to notify seller about expired auction
function notifySeller($seller_id, $auction_id) {
    global $conn;

    // Get seller email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $seller_email = $result->fetch_assoc()['email'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'petersongary252@gmail.com'; // Change to your email
            $mail->Password = 'jytc yuew hqkk tpsg'; // Use App Password for security
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Send notification
            $mail->setFrom('petersongary252@gmail.com', 'GoBidGo Auctions');
            $mail->addAddress($seller_email);
            $mail->Subject = "Auction Expired: No Buyer Found";
            $mail->Body = "Your auction item #$auction_id did not receive any bids. You can relist the item or remove it from the auction.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Expiry</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <script>
        // JavaScript for Relist Button Functionality
        document.addEventListener("DOMContentLoaded", function() {
            const relistButtons = document.querySelectorAll(".relist-button");

            relistButtons.forEach(button => {
                button.addEventListener("click", function(event) {
                    event.preventDefault();

                    const title = button.getAttribute("data-title"); // Use title instead of auction_id
                    const sellerId = button.getAttribute("data-seller-id");
                    const currentUserId = "<?php echo $_SESSION['user_id'] ?? ''; ?>"; // Get current user ID from session

                    console.log("Relist button clicked"); // Debugging: Check if the button is clicked
                    console.log(Title: ${title}, Seller ID: ${sellerId}, Current User ID: ${currentUserId}); // Debugging: Log IDs

                    if (currentUserId === sellerId) {
                        // Seller is relisting the item
                        console.log("Seller is relisting the item"); // Debugging: Check if seller logic is triggered
                        relistItem(title); // Pass title instead of auction_id
                    } else {
                        // Buyer is requesting to relist the item
                        console.log("Buyer is requesting to relist the item"); // Debugging: Check if buyer logic is triggered
                        requestRelist(title); // Pass title instead of auction_id
                    }
                });
            });

            function relistItem(title) {
                console.log(Relisting item with title: ${title}); // Debugging: Log title
                fetch('/gbg_auction/php/relist_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ title: title }), // Send title in the request body
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Relist response:", data); // Debugging: Log the response
                    if (data.success) {
                        alert('Item has been relisted.');
                        window.location.reload(); // Refresh the page to reflect changes
                    } else {
                        alert(data.message || 'Failed to relist the item. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please check the console for details.');
                });
            }

            function requestRelist(title) {
                console.log(Requesting relist for item with title: ${title}); // Debugging: Log title
                fetch('/gbg_auction/php/request_relist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ title: title }), // Send title in the request body
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Request relist response:", data); // Debugging: Log the response
                    if (data.success) {
                        alert('A message has been sent to the seller to relist the item.');
                    } else {
                        alert(data.message || 'Failed to send the request. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please check the console for details.');
                });
            }
        });
    </script>
</body>
</html>