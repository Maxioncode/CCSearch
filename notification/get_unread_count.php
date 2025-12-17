<?php
session_start();
include "../database/database.php";
include "../database/notifications.php";

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['studentID'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$userID = $_SESSION['studentID'];
$count = getUnreadNotificationCount($userID);

echo json_encode(['count' => $count]);

$conn->close();
?>
