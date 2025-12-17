<?php
session_start();
include("../database/database.php");

echo "<h1>Database Check</h1>";

// Check if user_profiles table exists
$result = $conn->query("SHOW TABLES LIKE 'user_profiles'");
if ($result->num_rows > 0) {
    echo "✅ user_profiles table exists<br>";

    // Check table structure
    $result = $conn->query("DESCRIBE user_profiles");
    echo "<h2>Table Structure:</h2>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }

    // Check if current user has a profile
    if (isset($_SESSION['studentID'])) {
        $studentID = $_SESSION['studentID'];
        echo "<h2>Current User Profile:</h2>";
        echo "Student ID: $studentID<br>";

        $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE studentID = ?");
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $profile = $result->fetch_assoc();
            echo "<pre>" . print_r($profile, true) . "</pre>";
        } else {
            echo "❌ No profile found for this user<br>";
        }
        $stmt->close();
    }
} else {
    echo "❌ user_profiles table does not exist<br>";
}

$conn->close();
?>




