<?php
/**
 * Unified Layout Template for CCSearch
 * 
 * This template provides a consistent layout structure across ALL pages
 * to prevent layout shifts when navigating between pages.
 * 
 * Usage:
 *   $pageTitle = 'Page Title';
 *   $activeNav = 'home'; // 'home', 'profile', 'library', 'publication', 'authors', 'notification'
 *   $additionalCSS = ['page-specific.css']; // Optional array of additional CSS files
 *   include '../layout/layout.php';
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'CCSearch'; ?></title>
    <link rel="stylesheet" href="../layout/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($additionalExternalCSS) && is_array($additionalExternalCSS)): ?>
        <?php foreach ($additionalExternalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Global Theme System -->
    <script src="../js/theme.js"></script>
</head>
<body>
    <div class="layout-wrapper">
        <!-- FIXED SIDEBAR - Same size on every page -->
        <aside class="sidebar">
            <div class="sidebar-content">
                <div class="logo-container">
                    <img id="main-logo" src="../icons/sidebar-icons/Icon.png" alt="CCSearch Logo" />
                    <h2>CCSEARCH</h2>
                </div>
                
                <nav class="nav-menu">
                    <a href="../home/home.php" class="<?php echo (isset($activeNav) && $activeNav === 'home') ? 'active' : ''; ?>">
                        <img src="../icons/sidebar-icons/home.png" alt=""> Home
                    </a>
                    <a href="../profile/profile.php" class="<?php echo (isset($activeNav) && $activeNav === 'profile') ? 'active' : ''; ?>">
                        <img src="../icons/sidebar-icons/profile.png" alt=""> Profile
                    </a>
                    <a href="../library/library.php" class="<?php echo (isset($activeNav) && $activeNav === 'library') ? 'active' : ''; ?>">
                        <img src="../icons/sidebar-icons/library.png" alt=""> My Library
                    </a>
                    <a href="../publication/publication.php" class="<?php echo (isset($activeNav) && $activeNav === 'publication') ? 'active' : ''; ?>">
                        <img src="../icons/sidebar-icons/publication.png" alt=""> Publication
                    </a>
                    <a href="../authors/authors.php" class="<?php echo (isset($activeNav) && $activeNav === 'authors') ? 'active' : ''; ?>">
                        <img src="../icons/sidebar-icons/authors.png" alt=""> Authors
                    </a>
                    <a href="../notification/notification.php" class="<?php echo (isset($activeNav) && $activeNav === 'notification') ? 'active' : ''; ?>">
                        <img src="../icons/sidebar-icons/notification.png" alt="">
                        Notification
                        <?php
                        if (isset($_SESSION['studentID'])) {
                            include "../database/notifications.php";
                            $unreadCount = getUnreadNotificationCount($_SESSION['studentID']);
                            if ($unreadCount > 0) {
                                echo '<span class="notification-badge">' . ($unreadCount > 99 ? '99+' : $unreadCount) . '</span>';
                            }
                        }
                        ?>
                    </a>
                </nav>
            </div>
            
            <div class="logout">
                <a href="../home/logout.php">
                    <img src="../icons/sidebar-icons/logout.png" alt=""> Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA - Fixed size, scrollable -->
        <main class="main-content">
            <div class="content-container">
