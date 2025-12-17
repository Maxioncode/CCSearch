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

// Check if author ID is provided
if (!isset($_POST['authorID']) || empty($_POST['authorID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid author ID']);
    exit();
}

$authorID = $_POST['authorID'];
$userID = $_SESSION['studentID'];

try {
    // Create table if it doesn't exist
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS `favorite_authors` (
              `favoriteID` int(11) NOT NULL AUTO_INCREMENT,
              `studentID` varchar(20) NOT NULL,
              `favorite_studentID` varchar(20) NOT NULL,
              `added_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`favoriteID`),
              UNIQUE KEY `unique_favorite` (`studentID`, `favorite_studentID`),
              KEY `fk_favorite_student` (`studentID`),
              KEY `fk_favorite_author` (`favorite_studentID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
        ";
    $conn->query($createTableSQL);

    // Check if the author exists
    $checkAuthor = $conn->prepare("SELECT studentID FROM registration WHERE studentID = ?");
    $checkAuthor->bind_param("s", $authorID);
    $checkAuthor->execute();
    $authorResult = $checkAuthor->get_result();

    if ($authorResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Author not found']);
        exit();
    }
    $checkAuthor->close();

    // Check if user is trying to favorite themselves
    if ($authorID === $userID) {
        echo json_encode(['success' => false, 'message' => 'You cannot favorite yourself']);
        exit();
    }

    // Check if already favorited
    $checkFavorite = $conn->prepare("SELECT favoriteID FROM favorite_authors WHERE studentID = ? AND favorite_studentID = ?");
    $checkFavorite->bind_param("ss", $userID, $authorID);
    $checkFavorite->execute();
    $favoriteResult = $checkFavorite->get_result();
    $checkFavorite->close();

    if ($favoriteResult->num_rows > 0) {
        // Remove from favorites
        $removeFavorite = $conn->prepare("DELETE FROM favorite_authors WHERE studentID = ? AND favorite_studentID = ?");
        $removeFavorite->bind_param("ss", $userID, $authorID);

        if ($removeFavorite->execute()) {
            $removeFavorite->close();
            echo json_encode(['success' => true, 'message' => 'Author removed from favorites']);
        } else {
            $removeFavorite->close();
            echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
        }
    } else {
        // Add to favorites
        $addFavorite = $conn->prepare("INSERT INTO favorite_authors (studentID, favorite_studentID) VALUES (?, ?)");
        $addFavorite->bind_param("ss", $userID, $authorID);

        if ($addFavorite->execute()) {
            $addFavorite->close();

            // Create notification for the favorited author
            notifyFavorite($authorID, $userID);

            echo json_encode(['success' => true, 'message' => 'Author added to favorites']);
        } else {
            $addFavorite->close();
            echo json_encode(['success' => false, 'message' => 'Failed to add to favorites']);
        }
    }

} catch (Exception $e) {
    error_log("Toggle favorite author error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>
