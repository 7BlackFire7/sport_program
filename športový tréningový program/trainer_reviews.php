<?php
session_start(); // Start the session to manage user login state
include 'db.php'; // Include database connection
include 'Trainer.php'; // Include Review and Trainer helper logic

// Controller to handle authentication logic
class AuthController {
    private $conn;
    public $error;
    public $success;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Main dispatcher for authentication actions
    public function handleRequests() {
        if (isset($_POST['register'])) $this->register(); // Handle user registration
        if (isset($_POST['login'])) $this->login();       // Handle user login
        if (isset($_GET['logout'])) $this->logout();      // Handle user logout
    }

    // Register a new user
    private function register() {
        $username = $_POST['reg_username'];

        // Check if username already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $this->error = "Username already exists.";
            return;
        }

        // Create hashed password and insert new user
        $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, 0)");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            $this->success = "Account created successfully!";
        } else {
            $this->error = "Registration failed.";
        }
    }

    // Log in an existing user
    private function login() {
        $username = $_POST['login_username'];
        $password = $_POST['login_password'];

        // Retrieve user data from database
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Verify password and log in
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header("Location: trainer_reviews.php");
            exit;
        } else {
            $this->error = "Invalid credentials.";
        }
    }

    // Log out the current user
    private function logout() {
        session_destroy(); // Destroy all session data
        header("Location: trainer_reviews.php");
        exit;
    }
}

// Controller to handle review-related actions (add, update, delete)
class ReviewController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Dispatcher for review actions
    public function handleRequests() {
        if (isset($_GET['delete_review'])) {
            $this->deleteReview(); // Handle review deletion
        }
        if (isset($_POST['update_review'])) {
            $this->updateReview(); // Handle review update
        }
        if (isset($_POST['add_review'])) {
            $this->addReview(); // Handle review submission
        }
    }

    // Delete a review by ID
    private function deleteReview() {
        $reviewId = $_GET['delete_review'];

        // Fetch the review author
        $stmt = $this->conn->prepare("SELECT username FROM reviews WHERE id = ?");
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $review = $result->fetch_assoc();

        if (!$review) return; // No review found

        // Allow deletion by admin or review author
        if ($_SESSION['user']['is_admin'] || $_SESSION['user']['username'] === $review['username']) {
            $stmt = $this->conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->bind_param("i", $reviewId);
            $stmt->execute();
        }

        // Redirect back to trainer's page
        header("Location: trainer_reviews.php?trainer_id=" . $_GET['trainer_id']);
        exit;
    }

    // Update a review's comment
    private function updateReview() {
        $reviewId = $_POST['review_id'];
        $comment = $_POST['updated_comment'];

        // Fetch the review author
        $stmt = $this->conn->prepare("SELECT username FROM reviews WHERE id = ?");
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $review = $result->fetch_assoc();

        if (!$review) return; // Review not found

        // Allow update by admin or review author
        if ($_SESSION['user']['is_admin'] || $_SESSION['user']['username'] === $review['username']) {
            $stmt = $this->conn->prepare("UPDATE reviews SET comment = ? WHERE id = ?");
            $stmt->bind_param("si", $comment, $reviewId);
            $stmt->execute();
        }

        // Redirect to the same trainer page
        header("Location: trainer_reviews.php?trainer_id=" . $_POST['trainer_id']);
        exit;
    }

    // Add a new review
    private function addReview() {
        if (!isset($_SESSION['user'])) {
            // Only logged-in users can post reviews
            header("Location: trainer_reviews.php");
            exit;
        }

        $username = $_SESSION['user']['username']; // Username from session
        $trainerId = (int)$_POST['trainer_id'];
        $rating = (int)$_POST['rating'];
        $comment = $_POST['comment'];

        // Use Review class to save review
        Review::addReview($this->conn, $trainerId, $username, $rating, $comment);
        header("Location: trainer_reviews.php?trainer_id=" . $trainerId);
        exit;
    }
}

// Controller to handle fetching trainer data
class TrainerController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Get all trainers
    public function getAll() {
        return $this->conn->query("SELECT * FROM trainers");
    }

    // Get a trainer by ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM trainers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

// === Application Bootstrapping ===
$auth = new AuthController($conn);
$reviewCtrl = new ReviewController($conn);
$trainerCtrl = new TrainerController($conn);

// Handle login/logout/register and review actions
$auth->handleRequests();
$reviewCtrl->handleRequests();

// Load data to display on the page
$trainerId = $_GET['trainer_id'] ?? null;
$trainers = $trainerCtrl->getAll();

if ($trainerId) {
    $trainer = $trainerCtrl->getById($trainerId);
    $reviews = Review::getReviews($conn, $trainerId);
    $average = Review::getAverageRating($conn, $trainerId);
}
?>


<!-- ====== HTML ======= -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainer Reviews</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <h2>Trainer Reviews</h2>

    <?php if (!isset($_SESSION['user'])): ?>
        <div class="register-form">
            <form method="POST">
                <h3>Register</h3>
                <input type="text" name="reg_username" placeholder="Username" required>
                <input type="password" name="reg_password" placeholder="Password" required>
                <button type="submit" name="register">Register</button>
            </form>
        </div>
        <div class="login-form">
            <form method="POST">
                <h3>Login</h3>
                <input type="text" name="login_username" placeholder="Username" required>
                <input type="password" name="login_password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <?php if (!empty($auth->error)) echo "<p class='error'>{$auth->error}</p>"; ?>
                <?php if (!empty($auth->success)) echo "<p class='success'>{$auth->success}</p>"; ?>
            </form>
        </div>
    <?php else: ?>
        <p>Welcome <strong><?= $_SESSION['user']['username'] ?></strong>
            <?php if ($_SESSION['user']['is_admin']): ?> (Admin)<?php endif; ?> —
            <a class="logout-link" href="?logout=1">Logout</a>
        </p>
    <?php endif; ?>

    <form method="get">
        <label>Select trainer:</label>
        <select name="trainer_id" onchange="this.form.submit()">
            <option value="">-- select --</option>
            <?php while ($row = $trainers->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" <?= ($row['id'] == $trainerId ? 'selected' : '') ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <?php if (!empty($trainer)): ?>
        <h3><?= htmlspecialchars($trainer['name']) ?> (Average: <?= $average ?> ⭐)</h3>

        <?php while ($r = $reviews->fetch_assoc()): ?>
            <div class="review">
                <strong><?= htmlspecialchars($r['username']) ?></strong>
                <small>(<?= date("Y-m-d H:i", strtotime($r['created_at'])) ?>)</small>
                <div class="stars"><?= str_repeat('★', $r['rating']) ?></div>
                <p><?= htmlspecialchars($r['comment']) ?></p>

                <?php if (isset($_SESSION['user']) && ($_SESSION['user']['is_admin'] || $_SESSION['user']['username'] === $r['username'])): ?>
                    <form method="POST">
                        <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="trainer_id" value="<?= $trainerId ?>">
                        <textarea name="updated_comment"><?= htmlspecialchars($r['comment']) ?></textarea>
                        <button name="update_review">Update</button>
                        <a href="?delete_review=<?= $r['id'] ?>&trainer_id=<?= $trainerId ?>" onclick="return confirm('Delete review?')">Delete</a>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>

        <form method="POST">
            <input type="hidden" name="trainer_id" value="<?= $trainerId ?>">
            <input type="hidden" name="username" value="<?= $_SESSION['user']['username'] ?>">
            <p><strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></p>
            <select name="rating">
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Okay</option>
                <option value="2">2 - Bad</option>
                <option value="1">1 - Terrible</option>
            </select>
            <textarea name="comment" placeholder="Your review" required></textarea>
            <button type="submit" name="add_review">Submit Review</button>
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
