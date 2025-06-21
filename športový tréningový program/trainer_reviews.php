<?php
include 'db.php';             // Connect to the database
include 'Trainer.php';        // Include Trainer and Review classes

// Handle form submission (review form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainerId = $_POST['trainer_id'];            // Get selected trainer ID from form
    $username = $_POST['username'];               // Get username from form
    $rating = (int)$_POST['rating'];              // Get numeric rating (1-5)
    $comment = $_POST['comment'];                 // Get text comment

    // Save review to the database using the Review class
    Review::addReview($conn, $trainerId, $username, $rating, $comment);

    // Redirect to the same page with trainer_id parameter (to prevent resubmission)
    header("Location: trainer_reviews.php?trainer_id=" . $trainerId);
    exit;
}

$trainerId = $_GET['trainer_id'] ?? null;         // Get trainer ID from query param (if present)
$trainers = $conn->query("SELECT * FROM trainers");  // Get all trainers from DB
$currentTrainer = null;

if ($trainerId) {
    // Get selected trainer info
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE id = ?");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentTrainer = $result->fetch_assoc();

    // Get all reviews for selected trainer
    $reviews = Review::getReviews($conn, $trainerId);

    // Calculate average rating
    $average = Review::getAverageRating($conn, $trainerId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainer Reviews</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Link to CSS styling -->
</head>
<body>
<div class="container">
    <h2>Trainer Reviews</h2>

    <!-- Trainer selection dropdown -->
    <form method="get">
        <label>Select a trainer:</label>
        <select name="trainer_id" onchange="this.form.submit()">
            <option value="">-- select --</option>
            <?php while ($row = $trainers->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $trainerId ? 'selected' : '') ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <!-- Show reviews and review form if trainer selected -->
    <?php if ($currentTrainer): ?>
        <h3><?= htmlspecialchars($currentTrainer['name']) ?> (Average Rating: <?= $average ?> ⭐)</h3>

        <!-- List of existing reviews -->
        <?php while ($r = $reviews->fetch_assoc()): ?>
            <div class="review">
                <strong><?= htmlspecialchars($r['username']) ?></strong>
                <small style="color: #888;">(<?= date("Y-m-d H:i", strtotime($r['created_at'])) ?>)</small>
                <div class="stars"><?= str_repeat('★', $r['rating']) ?></div>
                <p><?= htmlspecialchars($r['comment']) ?></p>
            </div>
        <?php endwhile; ?>

        <!-- Review submission form -->
        <form method="POST" action="">
            <input type="hidden" name="trainer_id" value="<?= $trainerId ?>">
            <input type="text" name="username" placeholder="Your name" required>
            <select name="rating" required>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Okay</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Terrible</option>
            </select>
            <textarea name="comment" placeholder="Your review" required></textarea>
            <button type="submit">Submit Review</button>
        </form>
    <?php endif; ?>

    <!-- Back to main site button -->
    <div style="text-align: center; margin-top: 40px;">
        <a href="index.php" style="
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        ">⬅ Back to Site</a>
    </div>
</div>
</body>
</html>
