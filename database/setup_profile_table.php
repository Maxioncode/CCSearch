<?php
// Script to create the user_profiles table
include("database.php");

echo "Setting up user_profiles table...<br>";

// Read the SQL file
$sql = file_get_contents('create_profile_table.sql');

// Execute the SQL
if ($conn->multi_query($sql)) {
    echo "✅ user_profiles table created successfully!<br>";

    // Check if we should populate existing users
    $result = $conn->query("SELECT COUNT(*) as count FROM user_profiles");
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        echo "Populating existing users from registration table...<br>";

        // Insert existing registration data into profiles
        $insert_sql = "
            INSERT INTO user_profiles (studentID, firstName, lastName, contactNumber, emailAddress, currentAddress, department, theme_preference)
            SELECT studentID, firstName, lastName, contactNumber, emailAddress, currentAddress, department, 'light'
            FROM registration
            WHERE studentID NOT IN (SELECT studentID FROM user_profiles)
        ";

        if ($conn->query($insert_sql)) {
            echo "✅ Existing user profiles created successfully!<br>";
        } else {
            echo "❌ Error populating profiles: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ Profiles already exist, skipping population.<br>";
    }
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

$conn->close();
echo "Setup complete!";
?>
