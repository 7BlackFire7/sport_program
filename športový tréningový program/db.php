<?php
// Class for managing database connection
class Database {
    private $host = 'localhost';
    private $dbName = 'gym_reviews';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Constructor automatically connects to the database
    public function __construct() {
        $this->connect();
    }

    // Establishes the database connection
    private function connect() {
        $this->conn = new mysqli(
            $this->host, 
            $this->username, 
            $this->password, 
            $this->dbName
        );

        // Check for connection errors
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }

    // Returns the active database connection
    public function getConnection() {
        return $this->conn;
    }

    // You can add more methods here for running queries or other DB operations
}

// Usage example:
$db = new Database();
$conn = $db->getConnection();

?>
