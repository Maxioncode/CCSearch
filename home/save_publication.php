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

// Check if publication ID is provided
if (!isset($_POST['publicationID']) || !is_numeric($_POST['publicationID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid publication ID']);
    exit();
}

$publicationID = (int)$_POST['publicationID'];
$userID = $_SESSION['studentID'];

try {
    // First, verify that the publication exists and is not owned by the current user
    $checkStmt = $conn->prepare("SELECT studentID FROM publications WHERE publicationID = ?");
    $checkStmt->bind_param("i", $publicationID);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Publication not found']);
        exit();
    }

    $publication = $result->fetch_assoc();
    $checkStmt->close();

    // Allow saving own publications

    // Check if publication is already saved by this user
    $checkSavedStmt = $conn->prepare("SELECT savedID FROM saved_publications WHERE studentID = ? AND publicationID = ?");
    $checkSavedStmt->bind_param("si", $userID, $publicationID);
    $checkSavedStmt->execute();
    $savedResult = $checkSavedStmt->get_result();

    if ($savedResult->num_rows > 0) {
        $checkSavedStmt->close();
        echo json_encode(['success' => false, 'message' => 'Publication is already saved']);
        exit();
    }
    $checkSavedStmt->close();

    // Save the publication
    $saveStmt = $conn->prepare("INSERT INTO saved_publications (studentID, publicationID) VALUES (?, ?)");
    $saveStmt->bind_param("si", $userID, $publicationID);

    if ($saveStmt->execute()) {
        $saveStmt->close();

        // Create notification for the publication owner (if not saving own publication)
        if ($publication['studentID'] !== $userID) {
            // Get publication title
            $titleStmt = $conn->prepare("SELECT title FROM publications WHERE publicationID = ?");
            $titleStmt->bind_param("i", $publicationID);
            $titleStmt->execute();
            $titleResult = $titleStmt->get_result();
            $pubData = $titleResult->fetch_assoc();
            $titleStmt->close();

            notifyPublicationSave($publication['studentID'], $userID, $pubData['title']);
        }

        echo json_encode(['success' => true, 'message' => 'Publication saved successfully']);
    } else {
        $saveStmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to save publication']);
    }

} catch (Exception $e) {
    error_log("Save publication error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>