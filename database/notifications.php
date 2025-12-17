<?php
include "database.php";

// Ensure notifications table exists (with existence check to prevent redeclaration)
if (!function_exists('ensureNotificationsTable')) {
    function ensureNotificationsTable() {
        global $conn;
        
        $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
            `notificationID` int(11) NOT NULL AUTO_INCREMENT,
            `recipientID` varchar(20) NOT NULL,
            `senderID` varchar(20) NOT NULL,
            `type` varchar(50) NOT NULL,
            `relatedID` int(11) DEFAULT NULL,
            `message` text NOT NULL,
            `is_read` tinyint(1) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`notificationID`),
            KEY `recipientID` (`recipientID`),
            KEY `senderID` (`senderID`),
            KEY `is_read` (`is_read`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
        
        $conn->query($sql);
    }
}

// Auto-create table on include (only once per request)
if (!defined('NOTIFICATIONS_TABLE_CREATED')) {
    ensureNotificationsTable();
    define('NOTIFICATIONS_TABLE_CREATED', true);
}

/**
 * Create a notification
 */
if (!function_exists('createNotification')) {
function createNotification($recipientID, $senderID, $type, $message, $relatedID = null) {
    global $conn;

    // Don't create notification for self-actions
    // senderID is required (NOT NULL in database)
    if ($recipientID === $senderID || empty($recipientID) || empty($senderID) || $senderID === null) {
        return false;
    }

    // Handle null relatedID
    if ($relatedID === null) {
        $stmt = $conn->prepare("INSERT INTO notifications (recipientID, senderID, type, message, relatedID) VALUES (?, ?, ?, ?, NULL)");
        $stmt->bind_param("ssss", $recipientID, $senderID, $type, $message);
    } else {
        $stmt = $conn->prepare("INSERT INTO notifications (recipientID, senderID, type, message, relatedID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $recipientID, $senderID, $type, $message, $relatedID);
    }

    if ($stmt->execute()) {
        $notificationID = $conn->insert_id;
        $stmt->close();
        return $notificationID;
    }

    $stmt->close();
    return false;
}
}

/**
 * Get notifications for a user
 */
if (!function_exists('getUserNotifications')) {
function getUserNotifications($userID, $limit = 50) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT n.*, r.firstName, r.lastName, r.studentID as senderStudentID
            FROM notifications n
            LEFT JOIN registration r ON n.senderID = r.studentID
            WHERE n.recipientID = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        
        if (!$stmt) {
            error_log("Failed to prepare getUserNotifications statement: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("si", $userID, $limit);
        
        if (!$stmt->execute()) {
            error_log("Failed to execute getUserNotifications: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $stmt->close();
        return $notifications;
    } catch (Exception $e) {
        error_log("Error in getUserNotifications: " . $e->getMessage());
        return [];
    }
}
}

/**
 * Get unread notification count
 */
if (!function_exists('getUnreadNotificationCount')) {
function getUnreadNotificationCount($userID) {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipientID = ? AND is_read = 0");
        
        if (!$stmt) {
            error_log("Failed to prepare getUnreadNotificationCount statement: " . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("s", $userID);
        
        if (!$stmt->execute()) {
            error_log("Failed to execute getUnreadNotificationCount: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)$row['count'];
    } catch (Exception $e) {
        error_log("Error in getUnreadNotificationCount: " . $e->getMessage());
        return 0;
    }
}
}

/**
 * Mark notification as read
 */
if (!function_exists('markNotificationAsRead')) {
function markNotificationAsRead($notificationID, $userID) {
    global $conn;

    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notificationID = ? AND recipientID = ?");
        
        if (!$stmt) {
            error_log("Failed to prepare markNotificationAsRead statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("is", $notificationID, $userID);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error in markNotificationAsRead: " . $e->getMessage());
        return false;
    }
}
}

/**
 * Delete notification
 */
if (!function_exists('deleteNotification')) {
function deleteNotification($notificationID, $userID) {
    global $conn;

    try {
        // First verify the notification belongs to the user
        $checkStmt = $conn->prepare("SELECT notificationID FROM notifications WHERE notificationID = ? AND recipientID = ?");
        if (!$checkStmt) {
            error_log("Failed to prepare check statement: " . $conn->error);
            return false;
        }
        $checkStmt->bind_param("is", $notificationID, $userID);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $checkStmt->close();
            error_log("Notification not found or doesn't belong to user");
            return false;
        }
        $checkStmt->close();
        
        // Now delete the notification
        $stmt = $conn->prepare("DELETE FROM notifications WHERE notificationID = ? AND recipientID = ?");
        
        if (!$stmt) {
            error_log("Failed to prepare deleteNotification statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("is", $notificationID, $userID);
        $result = $stmt->execute();
        $affectedRows = $conn->affected_rows;
        $stmt->close();
        
        return $affectedRows > 0;
    } catch (Exception $e) {
        error_log("Error in deleteNotification: " . $e->getMessage());
        return false;
    }
}
}

/**
 * Create notification when someone favorites a user
 */
if (!function_exists('notifyFavorite')) {
function notifyFavorite($favoritedUserID, $favoriterID) {
    global $conn;

    // Get favoriter's name
    $stmt = $conn->prepare("SELECT firstName, lastName FROM registration WHERE studentID = ?");
    $stmt->bind_param("s", $favoriterID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $message = $user['firstName'] . ' ' . $user['lastName'] . ' added you to their favorite authors.';
    return createNotification($favoritedUserID, $favoriterID, 'favorite', $message);
}
}

/**
 * Create notification when someone visits a profile
 */
if (!function_exists('notifyProfileVisit')) {
function notifyProfileVisit($profileOwnerID, $visitorID) {
    global $conn;

    // Get visitor's name
    $stmt = $conn->prepare("SELECT firstName, lastName FROM registration WHERE studentID = ?");
    $stmt->bind_param("s", $visitorID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $message = $user['firstName'] . ' ' . $user['lastName'] . ' visited your profile.';
    return createNotification($profileOwnerID, $visitorID, 'visit', $message);
}
}

/**
 * Create notification when someone saves a publication
 */
if (!function_exists('notifyPublicationSave')) {
function notifyPublicationSave($publicationOwnerID, $saverID, $publicationTitle) {
    global $conn;

    // Get saver's name
    $stmt = $conn->prepare("SELECT firstName, lastName FROM registration WHERE studentID = ?");
    $stmt->bind_param("s", $saverID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $message = $user['firstName'] . ' ' . $user['lastName'] . ' saved your publication: "' . $publicationTitle . '"';
    return createNotification($publicationOwnerID, $saverID, 'save', $message);
}
}

/**
 * Create notification when someone publishes new work
 */
if (!function_exists('notifyNewPublication')) {
function notifyNewPublication($publisherID, $publicationTitle, $followers = null) {
    global $conn;

    // Get publisher's name
    $stmt = $conn->prepare("SELECT firstName, lastName FROM registration WHERE studentID = ?");
    $stmt->bind_param("s", $publisherID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $message = $user['firstName'] . ' ' . $user['lastName'] . ' published a new work: "' . $publicationTitle . '"';

    // If followers array is provided, notify each follower
    if ($followers) {
        foreach ($followers as $followerID) {
            createNotification($followerID, $publisherID, 'publication', $message, null);
        }
    }

    return true;
}
}
?>
