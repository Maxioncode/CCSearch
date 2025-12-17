<?php
session_start();
include "../database/database.php";
include "../database/notifications.php";

// Check if studentID parameter is provided
if (!isset($_GET['studentID'])) {
    die("Invalid profile.");
}

$studentID = $_GET['studentID'];

// Query user information from registration table
$sql = "SELECT * FROM registration WHERE studentID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Profile not found.");
}

$user = $result->fetch_assoc();
$stmt->close();

// Create notification for profile visit (if visitor is logged in and not visiting own profile)
if (isset($_SESSION['studentID']) && $_SESSION['studentID'] !== $studentID) {
    notifyProfileVisit($studentID, $_SESSION['studentID']);
}

// Set layout variables
$pageTitle = 'Publications by ' . htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
$activeNav = 'publication';
$additionalCSS = ['../profile/profile_page.css', '../home/home_page.css', '../publication/publication_page.css'];

// Include layout header
include "../layout/layout.php";
?>
<!-- Personal Info Section Consistent with Profile -->
<div class="content" style="max-width:1200px;margin:auto">
    <div class="profile-card">
        <div class="profile-image">
            <img src="<?php echo htmlspecialchars(isset($user['profileImage']) && !empty($user['profileImage']) ? '../' . $user['profileImage'] : '../uploads/profile.png'); ?>" alt="User" id="profilePic">
        </div>
        <h3><?php echo htmlspecialchars((isset($user['firstName']) ? $user['firstName'] : 'Unknown') . ' ' . (isset($user['lastName']) ? $user['lastName'] : 'User')); ?></h3>
    </div>
    <div class="info-section">
        <div class="tabs"><button class="tab active" data-tab="personal">Personal Information</button></div>
        <div class="tab-content" id="personal">
            <div class="form-grid">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastName" value="<?php echo htmlspecialchars(isset($user['lastName']) ? $user['lastName'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstName" value="<?php echo htmlspecialchars(isset($user['firstName']) ? $user['firstName'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contactNumber" value="<?php echo htmlspecialchars(isset($user['contactNumber']) ? $user['contactNumber'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="emailAddress" value="<?php echo htmlspecialchars(isset($user['emailAddress']) ? $user['emailAddress'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Current Address</label>
                    <input type="text" name="currentAddress" value="<?php echo htmlspecialchars(isset($user['currentAddress']) ? $user['currentAddress'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars(isset($user['department']) ? $user['department'] : ''); ?>" readonly />
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Personal Info Section -->

<!-- Author Publications Header -->
<div class="welcome-header">
    <img src="../image/home_images/welcome-header.png" class="welcome-image" alt="Welcome Header">
    <div class="welcome-content">
        <h2>Publications by <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></h2>

        <?php if (!empty($user['department'])): ?>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
        <?php endif; ?>

        <div class="author-stats">
            <?php
            // Get publication count
            $countSql = "SELECT COUNT(*) as pub_count FROM publications WHERE studentID = ?";
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param("s", $studentID);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $pubCount = $countRow['pub_count'];
            $countStmt->close();
            ?>

            <span class="stat-item">
                <strong><?php echo $pubCount; ?></strong> Publication<?php echo $pubCount != 1 ? 's' : ''; ?>
            </span>
        </div>
    </div>
</div>

<!-- Publications Content -->
<div class="content-section">

    <?php if ($pubCount > 0): ?>
        <div class="category-box">

            <?php
            // Fetch user's publications
            $pubSql = "SELECT p.*, r.firstName, r.lastName 
                       FROM publications p 
                       JOIN registration r ON p.studentID = r.studentID 
                       WHERE p.studentID = ? 
                       ORDER BY p.publicationID DESC";

            $pubStmt = $conn->prepare($pubSql);
            $pubStmt->bind_param("s", $studentID);
            $pubStmt->execute();
            $pubResult = $pubStmt->get_result();

            if ($pubResult->num_rows > 0):
                while ($pub = $pubResult->fetch_assoc()):
            ?>

                    <div class="card">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <a href="<?php echo htmlspecialchars($pub['file_path']); ?>" target="_blank">View Document</a>
                        </div>
                    </div>

            <?php
                endwhile;
            endif;
            ?>

            <?php $pubStmt->close(); ?>

        </div> <!-- END category-box -->

    <?php else: ?>

        <div class="empty-state">
            <i class="fas fa-book-open empty-icon"></i>
            <h3>No publications yet</h3>
            <p>This author hasn't published any documents.</p>
        </div>

    <?php endif; ?>

</div> <!-- END content-section -->

<?php
$conn->close();

// Include layout footer
include "../layout/layout_footer.php";
?>
