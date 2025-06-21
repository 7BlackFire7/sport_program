<?php
$host = 'localhost';
$db   = 'gym_reviews';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Ошибка подключения к БД: " . $conn->connect_error);
}
?>