<?php
header('Content-Type: application/json');
session_start();
include("../database/database.php");

// Initialize response
$response = array('status' => 'error', 'message' => 'Unknown error');

// Check if user is logged in
if (!isset($_SESSION['studentID'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit();
}

$studentID = $_SESSION['studentID'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'change_password':
            // Handle password change
            $currentPassword = isset($_POST['currentPassword']) ? trim($_POST['currentPassword']) : '';
            $newPassword = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : '';
            $confirmPassword = isset($_POST['confirmNewPassword']) ? trim($_POST['confirmNewPassword']) : '';

            // Validate inputs
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $response['message'] = 'All fields are required';
                echo json_encode($response);
                exit();
            }

            if ($newPassword !== $confirmPassword) {
                $response['message'] = 'New passwords do not match';
                echo json_encode($response);
                exit();
            }

            // Validate new password strength
            if (strlen($newPassword) < 8) {
                $response['message'] = 'New password must be at least 8 characters long';
                echo json_encode($response);
                exit();
            }

            if (!preg_match('/[A-Z]/', $newPassword)) {
                $response['message'] = 'New password must contain at least one uppercase letter';
                echo json_encode($response);
                exit();
            }

            if (!preg_match('/\d/', $newPassword)) {
                $response['message'] = 'New password must contain at least one number';
                echo json_encode($response);
                exit();
            }

            // Get current hashed password from database
            $stmt = $conn->prepare("SELECT password FROM registration WHERE studentID = ?");
            $stmt->bind_param("s", $studentID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $response['message'] = 'User not found';
                echo json_encode($response);
                exit();
            }

            $row = $result->fetch_assoc();
            $hashedPassword = $row['password'];

            // Verify current password
            if (!password_verify($currentPassword, $hashedPassword)) {
                $response['message'] = 'Current password is incorrect';
                echo json_encode($response);
                exit();
            }

            // Hash new password
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password in database
            $updateStmt = $conn->prepare("UPDATE registration SET password = ? WHERE studentID = ?");
            $updateStmt->bind_param("ss", $newHashedPassword, $studentID);

            if ($updateStmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Password changed successfully!';
            } else {
                $response['message'] = 'Failed to update password';
            }

            $updateStmt->close();
            $stmt->close();
            break;

        case 'delete_account':
            // Handle account deletion
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                echo json_encode($response);
                exit();
            }

            // Start transaction for safe deletion
            $conn->begin_transaction();

            try {
                // Delete from user_profiles table
                $stmt1 = $conn->prepare("DELETE FROM user_profiles WHERE studentID = ?");
                $stmt1->bind_param("s", $studentID);
                $stmt1->execute();

                // Delete from library table
                $stmt2 = $conn->prepare("DELETE FROM library WHERE studentID = ?");
                $stmt2->bind_param("s", $studentID);
                $stmt2->execute();

                // Delete from saved_publications table
                $stmt3 = $conn->prepare("DELETE FROM saved_publications WHERE studentID = ?");
                $stmt3->bind_param("s", $studentID);
                $stmt3->execute();

                // Delete from publications table (user's own publications)
                $stmt4 = $conn->prepare("DELETE FROM publications WHERE studentID = ?");
                $stmt4->bind_param("s", $studentID);
                $stmt4->execute();

                // Finally delete from registration table
                $stmt5 = $conn->prepare("DELETE FROM registration WHERE studentID = ?");
                $stmt5->bind_param("s", $studentID);
                $stmt5->execute();

                // Commit transaction
                $conn->commit();

                // Clear session
                session_destroy();

                $response['status'] = 'success';
                $response['message'] = 'Account deleted successfully';

                // Close statements
                $stmt1->close();
                $stmt2->close();
                $stmt3->close();
                $stmt4->close();
                $stmt5->close();

            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $response['message'] = 'Failed to delete account: ' . $e->getMessage();
                throw $e;
            }
            break;

        case 'save_theme':
            // Handle theme preference saving (JSON input)
            $input = json_decode(file_get_contents('php://input'), true);
            $theme = isset($input['theme']) ? trim($input['theme']) : '';

            // Validate theme
            if (!in_array($theme, ['light', 'dark'])) {
                $response['message'] = 'Invalid theme';
                echo json_encode($response);
                exit();
            }

            // Update theme preference in database
            $themeStmt = $conn->prepare("UPDATE user_profiles SET theme_preference = ? WHERE studentID = ?");
            $themeStmt->bind_param("ss", $theme, $studentID);

            if ($themeStmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Theme preference saved';
                $response['theme'] = $theme;
            } else {
                $response['message'] = 'Failed to save theme preference';
            }

            $themeStmt->close();
            break;

        case 'get_theme':
            // Handle theme preference retrieval
            $themeStmt = $conn->prepare("SELECT theme_preference FROM user_profiles WHERE studentID = ?");
            $themeStmt->bind_param("s", $studentID);
            $themeStmt->execute();
            $result = $themeStmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $response['status'] = 'success';
                $response['theme'] = $row['theme_preference'] ?: 'light';
            } else {
                $response['status'] = 'success';
                $response['theme'] = 'light';
            }

            $themeStmt->close();
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    error_log("Account action error: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>
