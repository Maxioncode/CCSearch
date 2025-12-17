<?php
session_start();
include 'database/database.php';

echo "PHP script is working!<br>";
echo "Session ID: " . (isset($_SESSION['studentID']) ? $_SESSION['studentID'] : 'Not set') . "<br>";
echo "Database: " . ($conn ? 'Connected' : 'Failed') . "<br>";

// Test if POST data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST request received!<br>";
    echo "POST data: " . print_r($_POST, true) . "<br>";
    echo "FILES data: " . print_r($_FILES, true) . "<br>";
    exit;
}

echo "<form method='POST' enctype='multipart/form-data'>";
echo "Title: <input type='text' name='title' value='Test Title'><br>";
echo "File: <input type='file' name='file'><br>";
echo "<input type='submit' name='publish' value='Test Upload'>";
echo "</form>";
?>
