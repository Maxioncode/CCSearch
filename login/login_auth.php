<?php
header('Content-Type: application/json');
session_start();  // Start session for user login tracking
include("../database/database.php");  // Your database connection

// Initialize response
$response = array('status' => 'error', 'message' => 'Unknown error');

// Receive POST values
$studentID = isset($_POST['studentID']) ? trim($_POST['studentID']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate required fields
if (empty($studentID) || empty($password)) {
    $response['message'] = 'Please fill in all fields.';
    echo json_encode($response);
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT password FROM registration WHERE studentID = ?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Invalid Student ID or Password.';
    echo json_encode($response);
    exit();
}

// Fetch hashed password
$row = $result->fetch_assoc();
$hashedPassword = $row['password'];

// Verify password
if (password_verify($password, $hashedPassword)) {
    $_SESSION['studentID'] = $studentID;  // Store session
    $response['status'] = 'success';
    $response['message'] = 'Let\'s go!';
    echo json_encode($response);
} else {
    $response['message'] = 'Invalid Student ID or Password.';
    echo json_encode($response);
}

$conn->close();
?>