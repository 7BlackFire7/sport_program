<?php
class Trainer {
    public $id, $name, $specialization, $description, $image;

    public function __construct($id, $name, $specialization, $description, $image) {
        $this->id = $id;
        $this->name = $name;
        $this->specialization = $specialization;
        $this->description = $description;
        $this->image = $image;
    }
}

class Review {
    public static function addReview($conn, $trainerId, $username, $rating, $comment) {
        $stmt = $conn->prepare("INSERT INTO reviews (trainer_id, username, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $trainerId, $username, $rating, $comment);
        $stmt->execute();
    }

    public static function getReviews($conn, $trainerId) {
        $stmt = $conn->prepare("SELECT * FROM reviews WHERE trainer_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $trainerId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public static function getAverageRating($conn, $trainerId) {
        $stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE trainer_id = ?");
        $stmt->bind_param("i", $trainerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['avg_rating'] !== null ? round($result['avg_rating'], 1) : 0;
    }
}
?>