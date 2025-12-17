<?php
session_start();
include "database/database.php";

// Test session
if (!isset($_SESSION['studentID'])) {
    echo "Session not set. Please login first.";
    exit();
}

echo "Session OK - User: " . $_SESSION['studentID'] . "<br>";

// Test database connection
if ($conn->connect_error) {
    echo "Database connection failed: " . $conn->connect_error;
    exit();
}

echo "Database connection OK<br>";

// Test publications table
$result = $conn->query("SELECT COUNT(*) as count FROM publications");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Publications table OK - Current count: " . $row['count'] . "<br>";
} else {
    echo "Publications table query failed: " . $conn->error . "<br>";
}

// Test upload directories
$uploadDir = "uploads/publications/";
$coversDir = "uploads/publications/covers/";

if (!is_dir($uploadDir)) {
    echo "Upload directory does not exist: $uploadDir<br>";
} else {
    echo "Upload directory exists<br>";
    if (!is_writable($uploadDir)) {
        echo "Upload directory is not writable<br>";
    } else {
        echo "Upload directory is writable<br>";
    }
}

if (!is_dir($coversDir)) {
    echo "Covers directory does not exist: $coversDir<br>";
} else {
    echo "Covers directory exists<br>";
    if (!is_writable($coversDir)) {
        echo "Covers directory is not writable<br>";
    } else {
        echo "Covers directory is writable<br>";
    }
}

echo "All basic checks passed. If upload still fails, check browser console for more details.";
?>
