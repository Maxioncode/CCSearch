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
    error_log("Delete publication: Invalid publication ID - " . print_r($_POST, true));
    echo json_encode(['success' => false, 'message' => 'Invalid publication ID']);
    exit();
}

$publicationID = (int)$_POST['publicationID'];
$userID = $_SESSION['studentID'];

error_log("Delete publication: User $userID attempting to delete publication $publicationID");

try {
    // First, verify that the publication belongs to the current user
    $checkStmt = $conn->prepare("SELECT studentID, file_path, thumbnail FROM publications WHERE publicationID = ?");
    if (!$checkStmt) {
        error_log("Delete publication: Prepare failed - " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }

    $checkStmt->bind_param("i", $publicationID);
    if (!$checkStmt->execute()) {
        error_log("Delete publication: Execute failed - " . $checkStmt->error);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }

    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        error_log("Delete publication: Publication $publicationID not found");
        echo json_encode(['success' => false, 'message' => 'Publication not found']);
        exit();
    }

    $publication = $result->fetch_assoc();
    $checkStmt->close();

    // Check if user owns this publication
    if ($publication['studentID'] !== $userID) {
        error_log("Delete publication: User $userID attempted to delete publication $publicationID owned by " . $publication['studentID']);
        echo json_encode(['success' => false, 'message' => 'You can only delete your own publications']);
        exit();
    }

    error_log("Delete publication: Starting transaction for publication $publicationID");

    // Start transaction for safe deletion
    $conn->begin_transaction();

    // Delete from library table first (due to foreign key constraints)
    $deleteLibraryStmt = $conn->prepare("DELETE FROM library WHERE publicationID = ?");
    $deleteLibraryStmt->bind_param("i", $publicationID);
    $deleteLibraryStmt->execute();
    $deleteLibraryStmt->close();

    // Delete from saved_publications if it exists
    $deleteSavedStmt = $conn->prepare("DELETE FROM saved_publications WHERE publicationID = ?");
    $deleteSavedStmt->bind_param("i", $publicationID);
    if ($deleteSavedStmt->execute()) {
        $deleteSavedStmt->close();
    } else {
        // Table might not exist, continue
        $deleteSavedStmt->close();
    }

    // Delete from publications table
    $deleteStmt = $conn->prepare("DELETE FROM publications WHERE publicationID = ? AND studentID = ?");
    $deleteStmt->bind_param("is", $publicationID, $userID);
    $deleteStmt->execute();

    if ($deleteStmt->affected_rows > 0) {
        // Commit transaction
        $conn->commit();
        $deleteStmt->close();

        // Delete physical files
        $filePath = "../" . $publication['file_path'];
        $thumbnailPath = "../" . $publication['thumbnail'];

        error_log("Delete publication: Attempting to delete files - Document: $filePath, Thumbnail: $thumbnailPath");

        // Delete document file
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                error_log("Delete publication: Document file deleted successfully");
            } else {
                error_log("Delete publication: Failed to delete document file");
            }
        } else {
            error_log("Delete publication: Document file not found: $filePath");
        }

        // Delete thumbnail file (but not the default cover)
        if (file_exists($thumbnailPath) && strpos($thumbnailPath, 'default_cover.jpg') === false) {
            if (unlink($thumbnailPath)) {
                error_log("Delete publication: Thumbnail file deleted successfully");
            } else {
                error_log("Delete publication: Failed to delete thumbnail file");
            }
        } else {
            error_log("Delete publication: Thumbnail file not deleted (default cover or not found): $thumbnailPath");
        }

        echo json_encode(['success' => true, 'message' => 'Publication deleted successfully']);
    } else {
        // Rollback if deletion failed
        $conn->rollback();
        $deleteStmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete publication']);
    }

} catch (Exception $e) {
    // Rollback on error
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    error_log("Delete publication error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>
