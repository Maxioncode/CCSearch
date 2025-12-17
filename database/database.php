<?php
$host = "localhost";     // Usually localhost
$port = 3306;            // Default MySQL port
$dbUser = "root";       // SQLyog default user (renamed to avoid conflict with page $user)
$pass = "";              // Your SQLyog password (if any)
$db = "research_repository";

// Create connection with port specification
$conn = new mysqli($host, $dbUser, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error . "<br>Please ensure MySQL is running in XAMPP Control Panel.");
}
?>