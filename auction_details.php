<?php 
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';

date_default_timezone_set('Africa/Nairobi'); // Ensure correct timezone

// Get auction ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Auction ID missing or invalid.");
}

$auction_id = intval($_GET['id']);

// Fetch auction details
$query = "SELECT a.*, u.username AS seller_name 
          FROM auctions a
          JOIN users u ON a.seller_id = u.id
          WHERE a.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Auction not found.");
}

$auction = $result->fetch_assoc();
$stmt->close();

// Fetch highest bid
$highest_bid = floatval($auction['starting_price']); // Default to starting price
$bid_query = "SELECT MAX(bid_amount) AS highest_bid FROM bids WHERE auction_id = ?";
$bid_stmt = $conn->prepare($bid_query);
$bid_stmt->bind_param("i", $auction_id);
$bid_stmt->execute();
$bid_result = $bid_stmt->get_result();
$bid_row = $bid_result->fetch_assoc();

if ($bid_row && isset($bid_row['highest_bid']) && is_numeric($bid_row['highest_bid'])) {
    $highest_bid = floatval($bid_row['highest_bid']);
}

$bid_stmt->close();

// Ensure time consistency
$end_time = strtotime($auction['end_time']); // Convert auction end time to UNIX timestamp
$current_time = time();
$time_left = max(0, $end_time - $current_time); // Ensure non-negative values
$auction_ended = ($time_left <= 0);
?>
<?php include 'templates/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($auction['title']); ?> | Auction Details</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script>
        function startCountdown(endTime) {
            let countdownElement = document.getElementById("countdown");
            let interval = setInterval(function() {
                let now = Math.floor(Date.now() / 1000); // Get current time in seconds
                let timeLeft = endTime - now;

                if (timeLeft <= 0) {
                    clearInterval(interval);
                    countdownElement.innerHTML = "<span class='text-danger fw-bold'>Auction Ended</span>";
                    document.getElementById("bidForm").style.display = "none"; // Hide bid form after auction ends
                    return;
                }

                let days = Math.floor(timeLeft / (60 * 60 * 24));
                let hours = Math.floor((timeLeft % (60 * 60 * 24)) / (60 * 60));
                let minutes = Math.floor((timeLeft % (60 * 60)) / 60);
                let seconds = timeLeft % 60;

                countdownElement.innerHTML = `<span class='text-success fw-bold'>${days}d ${hours}h ${minutes}m ${seconds}s</span>`;
            }, 1000);
        }

        document.addEventListener("DOMContentLoaded", function() {
            let auctionEndTime = <?php echo $end_time; ?>;
            startCountdown(auctionEndTime);
        });
    </script>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center"><?php echo htmlspecialchars($auction['title']); ?></h1>
    
    <div class="row">
        <!-- Image -->
        <div class="col-md-6">
            <?php
            $image_path = "/gbg_auction/uploads/" . htmlspecialchars($auction['image']);
            $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;

            if (!file_exists($full_path) || empty($auction['image'])) {
                $image_path = "/gbg_auction/uploads/default.png"; // Default image if missing
            }
            ?>
            <img src="<?php echo $image_path; ?>" class="img-fluid rounded shadow" alt="Auction Image">
        </div>

        <!-- Auction Details -->
        <div class="col-md-6">
            <h4 class="mt-3">Seller: <span class="text-primary"><?php echo htmlspecialchars($auction['seller_name']); ?></span></h4>
            <p><strong>Starting Price:</strong> Ksh <?php echo number_format($auction['starting_price'], 2); ?></p>
            <p><strong>Current Highest Bid:</strong> Ksh <?php echo number_format($highest_bid, 2); ?></p>
            <p><strong>Auction Ends:</strong> <?php echo date("F j, Y, g:i a", $end_time); ?></p>
            <p><strong>Time Left:</strong> <span id="countdown"></span></p>
            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>

            <!-- Place Bid Form -->
            <?php if (!$auction_ended): ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="/gbg_auction/php/place_bid.php" method="post" id="bidForm">
                        <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Your Bid (Ksh)</label>
                            <input type="number" name="bid_amount" class="form-control" min="<?php echo $highest_bid + 1; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success">Place a Bid</button>
                    </form>
                <?php else: ?>
                    <a href="templates/login.php" class="btn btn-warning">Log in to place a bid</a>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-danger fw-bold">Auction has ended.</p>
            <?php endif; ?>

            <!-- Back to Auctions -->
            <a href="auctions.php" class="btn btn-secondary mt-2">Back to Auctions</a>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

</body>
</html>
