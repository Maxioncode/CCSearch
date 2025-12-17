<?php
session_start();
include("../database/database.php");

// Simple test page to check profile update functionality
if (!isset($_SESSION['studentID'])) {
    header("Location: ../login/login.html");
    exit();
}

$studentID = $_SESSION['studentID'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile Update Test</title>
</head>
<body>
    <h1>Profile Update Test</h1>
    <p>Student ID: <?php echo htmlspecialchars($studentID); ?></p>

    <form id="testForm">
        <input type="text" name="firstName" placeholder="First Name" value="Test"><br>
        <input type="text" name="lastName" placeholder="Last Name" value="User"><br>
        <input type="text" name="contactNumber" placeholder="Contact Number" value="09123456789"><br>
        <input type="email" name="emailAddress" placeholder="Email" value="test@example.com"><br>
        <input type="text" name="currentAddress" placeholder="Address" value="Test Address"><br>
        <input type="text" name="department" placeholder="Department" value="Test Dept"><br>
        <button type="submit">Test Update</button>
    </form>

    <div id="result"></div>

    <script>
    document.getElementById('testForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('profile_update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            console.log('Response:', data);
        })
        .catch(error => {
            document.getElementById('result').innerHTML = '<pre>Error: ' + error + '</pre>';
            console.error('Error:', error);
        });
    });
    </script>
</body>
</html>




