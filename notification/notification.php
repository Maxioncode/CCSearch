<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
    header("Location: ../login/login.html");
    exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "../database/database.php";
include "../database/notifications.php";

// Helper functions (with existence checks to prevent redeclaration errors)
if (!function_exists('getTimeAgo')) {
    function getTimeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
}

if (!function_exists('getProfileImage')) {
    function getProfileImage($studentID) {
        if (!$studentID) {
            return "../uploads/profiles/profile.png?t=" . time();
        }

        // Check for profile image in user_profiles table
        global $conn;
        $stmt = $conn->prepare("SELECT profileImage FROM user_profiles WHERE studentID = ?");
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['profileImage'])) {
                $stmt->close();
                return "../" . $row['profileImage'] . "?t=" . time();
            }
        }
        $stmt->close();

        // Fallback to default
        return "../uploads/profiles/profile.png?t=" . time();
    }
}

$userID = $_SESSION['studentID'];

// Get notifications
$notifications = getUserNotifications($userID);

// Ensure notifications is an array
if (!is_array($notifications)) {
    $notifications = [];
}

// Group notifications by time periods
$today = [];
$earlier = [];
$yesterday = [];
$thisWeek = [];

$currentDate = date('Y-m-d');
$currentTime = time();

foreach ($notifications as $notification) {
    $notificationDate = date('Y-m-d', strtotime($notification['created_at']));
    $notificationTime = strtotime($notification['created_at']);

    if ($notificationDate === $currentDate) {
        $today[] = $notification;
    } elseif ($notificationDate === date('Y-m-d', strtotime('-1 day'))) {
        $yesterday[] = $notification;
    } elseif ($notificationTime >= strtotime('-7 days')) {
        $thisWeek[] = $notification;
    } else {
        $earlier[] = $notification;
    }
}

// Set layout variables
$pageTitle = 'Notification';
$activeNav = 'notification';
$additionalCSS = ['notification_page.css'];
$additionalScripts = ['../js/notifications.js'];

// Also include script in head to ensure it loads early
$pageScripts = '<script src="../js/notifications.js"></script>';

// Include layout header
include "../layout/layout.php";
?>

<!-- Notification Wrapper -->
<div class="notification-wrapper">
    <h2 class="notif-title">Notifications</h2>

    <?php if (empty($notifications)): ?>
    <div class="no-notifications">
        <i class="fas fa-bell-slash" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
        <h3>No notifications yet</h3>
        <p>When someone interacts with your content, you'll see notifications here.</p>
    </div>
    <?php else: ?>

    <!-- TODAY SECTION -->
    <?php if (!empty($today)): ?>
    <div class="notif-section">
        <h3>Today</h3>
        <div class="notif-box">
            <?php foreach ($today as $notification): ?>
            <div class="notif-row <?php echo (!empty($notification['is_read']) && $notification['is_read']) ? '' : 'unread'; ?>" data-id="<?php echo htmlspecialchars($notification['notificationID'] ?? ''); ?>">
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-type"><?php echo ucfirst(htmlspecialchars($notification['type'] ?? 'notification')); ?></span>
                        <span class="notif-time"><?php echo getTimeAgo($notification['created_at'] ?? date('Y-m-d H:i:s')); ?></span>
                    </div>
                    <p class="notif-text"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                    <?php if (!empty($notification['firstName']) && !empty($notification['lastName'])): ?>
                    <div class="notif-sender">
                        <img src="<?php echo getProfileImage($notification['senderStudentID'] ?? ''); ?>" class="notif-avatar" alt="Profile">
                        <span><?php echo htmlspecialchars($notification['firstName'] . ' ' . $notification['lastName']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="notif-actions">
                    <?php if (empty($notification['is_read']) || !$notification['is_read']): ?>
                    <button class="mark-read-btn" onclick="markAsRead(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                    <button class="delete-btn" onclick="deleteNotification(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- YESTERDAY SECTION -->
    <?php if (!empty($yesterday)): ?>
    <div class="notif-section">
        <h3>Yesterday</h3>
        <div class="notif-box">
            <?php foreach ($yesterday as $notification): ?>
            <div class="notif-row <?php echo (!empty($notification['is_read']) && $notification['is_read']) ? '' : 'unread'; ?>" data-id="<?php echo htmlspecialchars($notification['notificationID'] ?? ''); ?>">
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-type"><?php echo ucfirst(htmlspecialchars($notification['type'] ?? 'notification')); ?></span>
                        <span class="notif-time"><?php echo getTimeAgo($notification['created_at'] ?? date('Y-m-d H:i:s')); ?></span>
                    </div>
                    <p class="notif-text"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                    <?php if (!empty($notification['firstName']) && !empty($notification['lastName'])): ?>
                    <div class="notif-sender">
                        <img src="<?php echo getProfileImage($notification['senderStudentID'] ?? ''); ?>" class="notif-avatar" alt="Profile">
                        <span><?php echo htmlspecialchars($notification['firstName'] . ' ' . $notification['lastName']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="notif-actions">
                    <?php if (empty($notification['is_read']) || !$notification['is_read']): ?>
                    <button class="mark-read-btn" onclick="markAsRead(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                    <button class="delete-btn" onclick="deleteNotification(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- THIS WEEK SECTION -->
    <?php if (!empty($thisWeek)): ?>
    <div class="notif-section">
        <h3>This Week</h3>
        <div class="notif-box">
            <?php foreach ($thisWeek as $notification): ?>
            <div class="notif-row <?php echo (!empty($notification['is_read']) && $notification['is_read']) ? '' : 'unread'; ?>" data-id="<?php echo htmlspecialchars($notification['notificationID'] ?? ''); ?>">
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-type"><?php echo ucfirst(htmlspecialchars($notification['type'] ?? 'notification')); ?></span>
                        <span class="notif-time"><?php echo getTimeAgo($notification['created_at'] ?? date('Y-m-d H:i:s')); ?></span>
                    </div>
                    <p class="notif-text"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                    <?php if (!empty($notification['firstName']) && !empty($notification['lastName'])): ?>
                    <div class="notif-sender">
                        <img src="<?php echo getProfileImage($notification['senderStudentID'] ?? ''); ?>" class="notif-avatar" alt="Profile">
                        <span><?php echo htmlspecialchars($notification['firstName'] . ' ' . $notification['lastName']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="notif-actions">
                    <?php if (empty($notification['is_read']) || !$notification['is_read']): ?>
                    <button class="mark-read-btn" onclick="markAsRead(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                    <button class="delete-btn" onclick="deleteNotification(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- EARLIER SECTION -->
    <?php if (!empty($earlier)): ?>
    <div class="notif-section">
        <h3>Earlier</h3>
        <div class="notif-box">
            <?php foreach ($earlier as $notification): ?>
            <div class="notif-row <?php echo (!empty($notification['is_read']) && $notification['is_read']) ? '' : 'unread'; ?>" data-id="<?php echo htmlspecialchars($notification['notificationID'] ?? ''); ?>">
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-type"><?php echo ucfirst(htmlspecialchars($notification['type'] ?? 'notification')); ?></span>
                        <span class="notif-time"><?php echo getTimeAgo($notification['created_at'] ?? date('Y-m-d H:i:s')); ?></span>
                    </div>
                    <p class="notif-text"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                    <?php if (!empty($notification['firstName']) && !empty($notification['lastName'])): ?>
                    <div class="notif-sender">
                        <img src="<?php echo getProfileImage($notification['senderStudentID'] ?? ''); ?>" class="notif-avatar" alt="Profile">
                        <span><?php echo htmlspecialchars($notification['firstName'] . ' ' . $notification['lastName']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="notif-actions">
                    <?php if (empty($notification['is_read']) || !$notification['is_read']): ?>
                    <button class="mark-read-btn" onclick="markAsRead(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                    <button class="delete-btn" onclick="deleteNotification(<?php echo $notification['notificationID']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<!-- MODAL: Delete Notification Confirmation -->
<div id="deleteNotificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Notification</h3>
            <span class="close-modal" id="closeDeleteNotifModal">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this notification?</p>
            <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button id="cancelDeleteNotifBtn" class="modal-btn cancel-btn">Cancel</button>
            <button id="confirmDeleteNotifBtn" class="modal-btn danger-btn">Delete</button>
        </div>
    </div>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>

