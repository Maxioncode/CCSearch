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

$userID = $_SESSION['studentID'];

try {
    // Check if table exists, create if not
    $checkTable = $conn->query("SHOW TABLES LIKE 'favorite_authors'");
    if ($checkTable->num_rows == 0) {
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
    }

    // Get favorite authors
    $getFavorites = $conn->prepare("SELECT favorite_studentID FROM favorite_authors WHERE studentID = ?");
    $getFavorites->bind_param("s", $userID);
    $getFavorites->execute();
    $result = $getFavorites->get_result();

    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row['favorite_studentID'];
    }

    $getFavorites->close();

    echo json_encode(['success' => true, 'favorites' => $favorites]);

} catch (Exception $e) {
    error_log("Get favorite authors error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>
