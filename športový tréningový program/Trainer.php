<?php
// Class representing a Trainer entity
class Trainer {
    public $id, $name, $specialization, $description, $image;

    // Constructor initializes trainer properties
    public function __construct($id, $name, $specialization, $description, $image) {
        $this->id = $id;
        $this->name = $name;
        $this->specialization = $specialization;
        $this->description = $description;
        $this->image = $image;
    }
}

// Class handling review logic for trainers
class Review {

    // Adds a new review for a specific trainer
    public static function addReview($conn, $trainerId, $username, $rating, $comment) {
        $stmt = $conn->prepare("INSERT INTO reviews (trainer_id, username, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $trainerId, $username, $rating, $comment);
        $stmt->execute();
    }

    // Retrieves all reviews for a trainer, sorted by most recent
    public static function getReviews($conn, $trainerId) {
        $stmt = $conn->prepare("SELECT * FROM reviews WHERE trainer_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $trainerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Calculates and returns the average rating for a trainer
    public static function getAverageRating($conn, $trainerId) {
        $stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE trainer_id = ?");
        $stmt->bind_param("i", $trainerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['avg_rating'] !== null ? round($result['avg_rating'], 1) : 0;
    }
}
?>
