<?php
include 'db_config.php'; // Ensure database connection is included

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 5; // Number of feedback entries to load per request

$feedback_query = "SELECT f.message, u.username, f.created_at FROM feedback f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($feedback_query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$feedback_result = $stmt->get_result();

while ($row = $feedback_result->fetch_assoc()): ?>
    <div class="card mb-2 feedback-card">
        <div class="card-body">
            <h6 class="card-title">@<?php echo htmlspecialchars($row['username']); ?></h6>
            <p class="card-text"><?php echo htmlspecialchars($row['message']); ?></p>
            <small class="text-muted"><?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?></small>
        </div>
    </div>
<?php endwhile;