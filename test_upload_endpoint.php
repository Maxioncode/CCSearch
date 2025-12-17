<?php
session_start();
include 'database/database.php';

// Simulate a logged-in user
if (!isset($_SESSION['studentID'])) {
    $_SESSION['studentID'] = '2023-00001'; // Test user
}

echo "Testing upload endpoint...<br>";

// Simulate POST data
$_POST['publish'] = '1';
$_POST['title'] = 'Test Publication';
$_POST['authors'] = 'Test Author';
$_POST['department'] = 'Test Department';
$_POST['type'] = 'Test Type';
$_POST['abstract'] = 'Test abstract';

// Create a dummy file
$tempFile = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($tempFile, 'test content');

$_FILES['file'] = array(
    'name' => 'test.pdf',
    'type' => 'application/pdf',
    'tmp_name' => $tempFile,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($tempFile)
);

echo "Simulated upload data set.<br>";

// Include the publication.php file to test the upload logic
include 'publication/publication.php';
?>
