<?php
session_start();
include 'templates/header.php';
include 'php/db_config.php'; // Ensure database connection is included

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to submit feedback.";
        header("Location: contact.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $feedback = trim($_POST['feedback']);

    if (!empty($feedback)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $feedback);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Feedback submitted successfully!";
        } else {
            $_SESSION['error'] = "Error submitting feedback.";
        }
        $stmt->close();
    }
    header("Location: contact.php");
    exit();
}

// Fetch limited feedback initially
$limit = 5; // Number of feedback entries to show initially
$feedback_query = "SELECT f.message, u.username, f.created_at FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT ?";
$stmt = $conn->prepare($feedback_query);
$stmt->bind_param("i", $limit);
$stmt->execute();
$feedback_result = $stmt->get_result();
?>

<div class="container my-5">
    <h2 class="text-center">Contact Support Team</h2>
    <p class="text-center">If your seller application was rejected, you can contact us for further assistance.</p>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Contact Information -->
        <div class="col-md-6">
            <h4>Contact Details</h4>
            <p><strong>Email:</strong> <a href="mailto:howardmukoma242@gmail.com">howardmukoma242@gmail.com</a></p>
            <p><strong>Phone:</strong> <a href="tel:+254713592840">+254 713 592 840</a></p>
            <p><strong>Address:</strong> GoBidGo Headquarters, Laikipia University, Kenya</p>
            <p><strong>Working Hours:</strong> Monday - Friday (9:00 AM - 6:00 PM)</p>
            <a href="templates/seller_register.php" class="btn btn-secondary mt-3">Back to Seller Registration</a>
        </div>

        <!-- Contact Form -->
        <div class="col-md-6">
            <h4>Send Us a Message</h4>
            <p>If you believe your seller application was rejected by mistake or need further clarification, send us a message.</p>
            <form action="php/contact_process.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Your Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Your Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Your Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>

    <!-- Feedback Section -->
    <div class="mt-5">
        <h4 class="text-center">User Feedback</h4>
        <form method="POST" action="">
            <div class="mb-3">
                <textarea class="form-control" name="feedback" rows="3" placeholder="Leave your feedback..." required></textarea>
            </div>
            <button type="submit" class="btn btn-warning">Submit Feedback</button>
        </form>

        <div class="mt-4" id="feedback-container">
            <?php while ($row = $feedback_result->fetch_assoc()): ?>
                <div class="card mb-2 feedback-card">
                    <div class="card-body">
                        <h6 class="card-title">@<?php echo htmlspecialchars($row['username']); ?></h6>
                        <p class="card-text"><?php echo htmlspecialchars($row['message']); ?></p>
                        <small class="text-muted"><?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- See More Button -->
        <div class="text-center mt-3">
            <button id="see-more-btn" class="btn btn-outline-primary">See More</button>
        </div>
    </div>
</div>

<script>
    // JavaScript for dynamic loading of feedback
    let offset = <?php echo $limit; ?>; // Initial offset
    const feedbackContainer = document.getElementById('feedback-container');
    const seeMoreBtn = document.getElementById('see-more-btn');

    seeMoreBtn.addEventListener('click', async () => {
        try {
            const response = await fetch(`php/load_more_feedback.php?offset=${offset}`);
            const data = await response.text();

            if (data) {
                feedbackContainer.insertAdjacentHTML('beforeend', data);
                offset += <?php echo $limit; ?>; // Increment offset for next load
            } else {
                seeMoreBtn.disabled = true;
                seeMoreBtn.textContent = 'No more feedback';
            }
        } catch (error) {
            console.error('Error loading more feedback:', error);
        }
    });
</script>

<?php include 'templates/footer.php'; ?>