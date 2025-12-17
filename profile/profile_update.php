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

try {
    // Debug: Log the received data
    error_log("Profile update request received for studentID: $studentID");
    error_log("POST data: " . print_r($_POST, true));

    // Check if profile is in public mode - prevent updates
    $publicCheck = $conn->prepare("SELECT is_public FROM user_profiles WHERE studentID = ?");
    if (!$publicCheck) {
        error_log("Failed to prepare public check query: " . $conn->error);
        $response['message'] = 'Database error';
        echo json_encode($response);
        exit();
    }

    $publicCheck->bind_param("s", $studentID);
    if (!$publicCheck->execute()) {
        error_log("Failed to execute public check query: " . $publicCheck->error);
        $response['message'] = 'Database error';
        echo json_encode($response);
        exit();
    }

    $publicResult = $publicCheck->get_result();
    if ($publicResult->num_rows > 0) {
        $publicRow = $publicResult->fetch_assoc();
        if ($publicRow['is_public']) {
            $response['message'] = 'Cannot update profile in public view';
            echo json_encode($response);
            exit();
        }
    }
    $publicCheck->close();

    // Handle profile information update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
        $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
        $contactNumber = isset($_POST['contactNumber']) ? trim($_POST['contactNumber']) : '';
        $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
        $currentAddress = isset($_POST['currentAddress']) ? trim($_POST['currentAddress']) : '';
        $department = isset($_POST['department']) ? trim($_POST['department']) : '';

        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($contactNumber) || empty($emailAddress) || empty($currentAddress) || empty($department)) {
            $response['message'] = 'All fields are required';
            echo json_encode($response);
            exit();
        }

        // Validate email format
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Please enter a valid email address';
            echo json_encode($response);
            exit();
        }

        // Validate contact number (exactly 11 digits)
        if (!preg_match('/^[0-9]{11}$/', $contactNumber)) {
            $response['message'] = 'Contact number must be exactly 11 digits';
            echo json_encode($response);
            exit();
        }

        // Check if email is already used by another user
        $checkEmail = $conn->prepare("SELECT studentID FROM user_profiles WHERE emailAddress = ? AND studentID != ?");
        $checkEmail->bind_param("ss", $emailAddress, $studentID);
        $checkEmail->execute();
        $emailResult = $checkEmail->get_result();

        if ($emailResult->num_rows > 0) {
            $response['message'] = 'Email address is already in use by another account';
            echo json_encode($response);
            exit();
        }
        $checkEmail->close();

        // Handle profile image upload (optional)
        $profileImagePath = null;
        if (isset($_FILES['profileImage'])) {
            try {
                // Check for upload errors
                $uploadError = $_FILES['profileImage']['error'];
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds maximum size allowed by server',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];

                    $errorMessage = isset($errorMessages[$uploadError])
                        ? $errorMessages[$uploadError]
                        : 'Unknown upload error occurred';

                    error_log("Profile image upload error: $errorMessage (code: $uploadError)");
                    $response['message'] = 'Upload failed: ' . $errorMessage;
                    echo json_encode($response);
                    exit();
                }

                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $fileType = $_FILES['profileImage']['type'];
                $fileSize = $_FILES['profileImage']['size'];
                $fileName = $_FILES['profileImage']['name'];

                // Validate file type
                if (!in_array($fileType, $allowedTypes)) {
                    error_log("Invalid file type attempted: $fileType for file: $fileName");
                    $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
                    echo json_encode($response);
                    exit();
                }

                // Validate file size (max 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    error_log("File too large: $fileSize bytes for file: $fileName");
                    $response['message'] = 'File size too large. Maximum size is 5MB.';
                    echo json_encode($response);
                    exit();
                }

                // Validate file extension matches MIME type
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($fileExtension, $allowedExtensions)) {
                    error_log("Invalid file extension: $fileExtension for file: $fileName");
                    $response['message'] = 'Invalid file extension. Only JPG, PNG, and GIF files are allowed.';
                    echo json_encode($response);
                    exit();
                }

                // Additional security: Check if file is actually an image
                $imageInfo = getimagesize($_FILES['profileImage']['tmp_name']);
                if ($imageInfo === false) {
                    error_log("File is not a valid image: $fileName");
                    $response['message'] = 'Uploaded file is not a valid image.';
                    echo json_encode($response);
                    exit();
                }

                // Generate unique filename
                $uniqueFilename = $studentID . '_profile_' . time() . '.' . $fileExtension;
                $uploadDir = __DIR__ . '/../uploads/profiles/';

                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        error_log("Failed to create upload directory: $uploadDir");
                        $response['message'] = 'Server error: Could not create upload directory.';
                        echo json_encode($response);
                        exit();
                    }
                }

                // Check if directory is writable
                if (!is_writable($uploadDir)) {
                    error_log("Upload directory is not writable: $uploadDir");
                    $response['message'] = 'Server error: Upload directory is not writable.';
                    echo json_encode($response);
                    exit();
                }

                $uploadPath = $uploadDir . $uniqueFilename;

                // Attempt to move uploaded file
                if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadPath)) {
                    // Verify file was actually saved
                    if (file_exists($uploadPath) && filesize($uploadPath) > 0) {
                        $profileImagePath = 'uploads/profiles/' . $uniqueFilename;
                        error_log("Profile image uploaded successfully: $profileImagePath (size: " . filesize($uploadPath) . " bytes)");
                    } else {
                        error_log("File upload reported success but file not found or empty: $uploadPath");
                        $response['message'] = 'Upload failed: File was not saved properly.';
                        echo json_encode($response);
                        exit();
                    }
                } else {
                    $lastError = error_get_last();
                    error_log("Failed to move uploaded file to: $uploadPath. Last error: " . ($lastError ? $lastError['message'] : 'Unknown'));
                    $response['message'] = 'Failed to save uploaded profile image.';
                    echo json_encode($response);
                    exit();
                }

            } catch (Exception $e) {
                error_log("Exception during profile image upload: " . $e->getMessage());
                $response['message'] = 'An unexpected error occurred during image upload.';
                echo json_encode($response);
                exit();
            }
        }

        // Prepare and execute update query with comprehensive error handling
        try {
            if ($profileImagePath) {
                $updateStmt = $conn->prepare("UPDATE user_profiles SET firstName = ?, lastName = ?, contactNumber = ?, emailAddress = ?, currentAddress = ?, department = ?, profileImage = ?, updated_at = CURRENT_TIMESTAMP WHERE studentID = ?");
                if (!$updateStmt) {
                    throw new Exception("Failed to prepare update query with image: " . $conn->error);
                }
                $updateStmt->bind_param("ssssssss", $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department, $profileImagePath, $studentID);
            } else {
                $updateStmt = $conn->prepare("UPDATE user_profiles SET firstName = ?, lastName = ?, contactNumber = ?, emailAddress = ?, currentAddress = ?, department = ?, updated_at = CURRENT_TIMESTAMP WHERE studentID = ?");
                if (!$updateStmt) {
                    throw new Exception("Failed to prepare update query without image: " . $conn->error);
                }
                $updateStmt->bind_param("sssssss", $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department, $studentID);
            }

            error_log("Executing update query for studentID: $studentID with image: " . ($profileImagePath ? 'YES' : 'NO'));

            if ($updateStmt->execute()) {
                $affectedRows = $updateStmt->affected_rows;
                error_log("Update successful, affected rows: $affectedRows");

                if ($affectedRows === 0) {
                    // Check if the profile exists
                    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM user_profiles WHERE studentID = ?");
                    $checkStmt->bind_param("s", $studentID);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $checkRow = $checkResult->fetch_assoc();
                    $checkStmt->close();

                    if ($checkRow['count'] == 0) {
                        throw new Exception("Profile not found for studentID: $studentID");
                    } else {
                        error_log("Update executed but no rows affected - data may be unchanged");
                    }
                }

                $response['status'] = 'success';
                $response['message'] = $profileImagePath ? 'Profile updated successfully!' : 'Profile updated successfully!';
                $response['updated_at'] = date('Y-m-d H:i:s');
                if ($profileImagePath) {
                    $response['profile_image'] = $profileImagePath;
                }
            } else {
                throw new Exception("Database update failed: " . $updateStmt->error);
            }

            $updateStmt->close();

        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $response['message'] = 'Failed to update profile: ' . $e->getMessage();

            // If there was an image upload but the database update failed, we might want to clean up
            if ($profileImagePath && isset($uploadPath) && file_exists($uploadPath)) {
                error_log("Cleaning up uploaded file due to database error: $uploadPath");
                @unlink($uploadPath);
            }
        }
    }
    // Handle public view toggle
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($action === 'toggle_public') {
            try {
                // Get current is_public status
                $getStmt = $conn->prepare("SELECT is_public FROM user_profiles WHERE studentID = ?");
                if (!$getStmt) {
                    throw new Exception("Failed to prepare SELECT query: " . $conn->error);
                }

                $getStmt->bind_param("s", $studentID);
                if (!$getStmt->execute()) {
                    throw new Exception("Failed to execute SELECT query: " . $getStmt->error);
                }

                $result = $getStmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $currentStatus = $row['is_public'];
                    $newStatus = !$currentStatus;

                    // Update is_public status
                    $updateStmt = $conn->prepare("UPDATE user_profiles SET is_public = ? WHERE studentID = ?");
                    if (!$updateStmt) {
                        throw new Exception("Failed to prepare UPDATE query: " . $conn->error);
                    }

                    $updateStmt->bind_param("is", $newStatus, $studentID);

                    if ($updateStmt->execute()) {
                        $affectedRows = $updateStmt->affected_rows;
                        error_log("Public view toggle successful for studentID: $studentID, new status: $newStatus, affected rows: $affectedRows");

                        $response['status'] = 'success';
                        $response['message'] = $newStatus ? 'Profile is now public' : 'Profile is now private';
                        $response['is_public'] = $newStatus;
                    } else {
                        throw new Exception("Failed to execute UPDATE query: " . $updateStmt->error);
                    }

                    $updateStmt->close();
                } else {
                    error_log("Profile not found for public view toggle: $studentID");
                    $response['message'] = 'Profile not found';
                }

                $getStmt->close();

            } catch (Exception $e) {
                error_log("Public view toggle error for studentID $studentID: " . $e->getMessage());
                $response['message'] = 'Failed to update profile visibility: ' . $e->getMessage();
            }
        }
    }
    else {
        $response['message'] = 'Invalid request method';
    }

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
