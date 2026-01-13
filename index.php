<?php
session_start();
include __DIR__ . '/php/db_config.php'; // Use absolute path

include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/templates/navbar.php'; // Use absolute path

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : ''; // Escape output
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Check if user is an approved seller
$is_seller_approved = false;
if ($is_logged_in) {
    $seller_check_query = "SELECT status FROM sellers WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($seller_check_query);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    $stmt->bind_result($status);
    if ($stmt->fetch() && $status === 'approved') {
        $is_seller_approved = true;
    }
    $stmt->close();
}

// Fetch unique categories from the auctions table
$categories_query = "SELECT DISTINCT category FROM auctions";
$categories_result = $conn->query($categories_query);

if (!$categories_result) {
    die("Error fetching categories: " . $conn->error);
}

$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    if (!empty($row['category'])) {
        $categories[] = $row['category'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoBidGo - Auctions</title>
    <!-- Load Bootstrap CSS first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Load your custom CSS after Bootstrap -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="chatbot.css">
</head>
<body>


<!-- Recommended for You Section -->
<!-- Styles for Transitions & Image Sizing -->
<style>
    .carousel-inner {
        position: relative;
        width: 100%;
        height: 400px; /* Set a reasonable height */
        overflow: hidden;
    }

    .carousel-item {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        transform: translateX(100%); /* Initial off-screen position */
        transition: transform 1s ease-in-out, opacity 1s ease-in-out;
    }

    .carousel-item.active {
        opacity: 1;
        transform: translateX(0);
        position: relative;
        z-index: 5;
    }

    /* Swipe Left Effect */
    .swipe-left {
        transform: translateX(-100%);
    }

    /* Petal Wind Effect */
    .petal-wind {
        transform: rotateY(180deg);
        opacity: 0;
    }

    /* 3D Heart Effect */
    .heart-3d {
        transform: scale(0) rotate(360deg);
        opacity: 0;
    }

    .carousel-item.active.heart-3d {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }

    /* Ensure Images Fit Nicely */
    .carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 10px;
    }

    /* Buttons Styling */
    .carousel-control-prev, .carousel-control-next {
        z-index: 10;
    }
</style>

<!-- Carousel Structure -->
<section class="recommended-section container my-5">
    <h2 class="text-center mb-4">Recommended for You</h2>
    <div id="recommended-carousel" class="carousel slide">
        <div class="carousel-inner">
            <?php 
            $recommend_query = "
                SELECT a.id, a.title, a.image 
                FROM auctions a
                WHERE a.status = 'active'
                ORDER BY RAND() LIMIT 6";  

            $result = $conn->query($recommend_query);
            $active = true;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()): ?>
                    <div class="carousel-item <?php echo $active ? 'active' : ''; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        <div class="carousel-caption d-none d-md-block">
                            <h5><?php echo htmlspecialchars($row['title']); ?></h5>
                            <a href="auction_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">View Item</a>
                        </div>
                    </div>
                    <?php 
                    $active = false;
                endwhile;
            } else {
                echo "<p class='text-center'>No active auctions available.</p>";
            }
            ?>
        </div>
        <button id="prevBtn" class="carousel-control-prev" type="button">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button id="nextBtn" class="carousel-control-next" type="button">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- JavaScript for Transitions & Buttons -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    let carouselItems = document.querySelectorAll(".carousel-item");
    let currentIndex = 0;
    let totalItems = carouselItems.length;
    let intervalTime = 7000; 
    let autoSlide;
    let effects = ["swipe-left", "petal-wind", "heart-3d"];
    
    function showSlide(index, effect) {
        carouselItems.forEach((item, i) => {
            item.classList.remove("active", "swipe-left", "petal-wind", "heart-3d");
            if (i === index) {
                item.classList.add("active", effect);
            }
        });
    }

    function nextSlide() {
        let prevIndex = currentIndex;
        currentIndex = (currentIndex + 1) % totalItems;
        let randomEffect = effects[Math.floor(Math.random() * effects.length)];
        carouselItems[prevIndex].classList.remove("active");
        showSlide(currentIndex, randomEffect);
    }

    function prevSlide() {
        let prevIndex = currentIndex;
        currentIndex = (currentIndex - 1 + totalItems) % totalItems;
        let randomEffect = effects[Math.floor(Math.random() * effects.length)];
        carouselItems[prevIndex].classList.remove("active");
        showSlide(currentIndex, randomEffect);
    }

    function startAutoSlide() {
        autoSlide = setInterval(nextSlide, intervalTime);
    }

    function stopAutoSlide() {
        clearInterval(autoSlide);
    }

    startAutoSlide();

    document.getElementById("nextBtn").addEventListener("click", function () {
        stopAutoSlide();
        nextSlide();
        startAutoSlide();
    });

    document.getElementById("prevBtn").addEventListener("click", function () {
        stopAutoSlide();
        prevSlide();
        startAutoSlide();
    });
});
</script>



<!-- Featured Auctions Section -->
<section class="featured-auctions container my-5">
    <h2 class="text-center mb-4">Featured Auctions</h2>

    <?php if (empty($categories)): ?>
        <p class="text-center">No featured auctions available at the moment.</p>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <?php 
            // Check if there are approved auctions in this category
            $count_query = "SELECT COUNT(*) FROM auctions a 
                            JOIN sellers s ON a.seller_id = s.user_id 
                            WHERE s.status = 'approved' AND a.category = ?";
            $stmt = $conn->prepare($count_query);
            if (!$stmt) {
                die("Database error: " . $conn->error);
            }
            $stmt->bind_param("s", $category);
            if (!$stmt->execute()) {
                die("Query execution failed: " . $stmt->error);
            }
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count == 0) continue; // Skip categories with no approved auctions
            ?>

            <h3 class="mt-4"><?php echo htmlspecialchars($category); ?></h3>
            <div class="row">
                <?php 
                $query = "SELECT a.id, a.title, a.image, a.starting_price 
                          FROM auctions a 
                          JOIN sellers s ON a.seller_id = s.user_id 
                          WHERE s.status = 'approved' AND a.category = ? 
                          ORDER BY a.created_at DESC LIMIT 8";

                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    die("Database error: " . $conn->error);
                }
                $stmt->bind_param("s", $category);
                if (!$stmt->execute()) {
                    die("Query execution failed: " . $stmt->error);
                }
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                                <p class="card-text">Starting Price: Ksh <?php echo number_format($row['starting_price'], 2); ?></p>
                                <a href="auction_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">View Auction</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; 
                
                $stmt->close(); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Floating Chat Widget -->
<div id="chat-container">
    <div id="chat-header">
        <span>Chat with GBG Assistant</span>
        <button id="close-chat">&times;</button>
    </div>
    <div id="chat-body">
        <div id="chat-messages"></div>
    </div>
    <div id="chat-footer">
        <input type="text" id="chat-input" placeholder="Type a message..." />
        <button id="send-chat">Send</button>
    </div>
</div>
<button id="chat-toggle">💬</button>

<!-- Load Bootstrap JS and your custom JS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="js/chatbot.js" defer></script>
<?php include 'templates/footer.php'; ?>

</body>
</html>