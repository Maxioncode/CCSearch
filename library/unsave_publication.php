<?php
session_start();
include "../database/database.php";

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

// Check if publication ID is provided
if (!isset($_POST['publicationID']) || !is_numeric($_POST['publicationID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid publication ID']);
    exit();
}

$publicationID = (int)$_POST['publicationID'];
$userID = $_SESSION['studentID'];

try {
    // Delete from saved_publications table
    $deleteStmt = $conn->prepare("DELETE FROM saved_publications WHERE studentID = ? AND publicationID = ?");
    $deleteStmt->bind_param("si", $userID, $publicationID);

    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            $deleteStmt->close();
            echo json_encode(['success' => true, 'message' => 'Publication removed from saved list']);
        } else {
            $deleteStmt->close();
            echo json_encode(['success' => false, 'message' => 'Publication was not in your saved list']);
        }
    } else {
        $deleteStmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to remove publication']);
    }

} catch (Exception $e) {
    error_log("Unsave publication error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>



