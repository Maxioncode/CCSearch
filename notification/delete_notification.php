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
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if notification ID is provided
if (!isset($_POST['notificationID']) || !is_numeric($_POST['notificationID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

$notificationID = (int)$_POST['notificationID'];
$userID = $_SESSION['studentID'];

if (deleteNotification($notificationID, $userID)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
}

$conn->close();
?>
