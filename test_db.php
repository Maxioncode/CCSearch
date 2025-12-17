<?php
include 'database/database.php';

echo 'Database connection: ' . ($conn ? 'OK' : 'FAILED') . '<br>';
if ($conn) {
    $result = $conn->query('SELECT COUNT(*) as count FROM publications');
    if ($result) {
        $row = $result->fetch_assoc();
        echo 'Publications table: OK (' . $row['count'] . ' records)<br>';
    } else {
        echo 'Publications table query: FAILED - ' . $conn->error . '<br>';
    }
}

// Test file permissions
$uploadDir = 'uploads/publications/';
$coverDir = 'uploads/publications/covers/';

echo 'Upload directory exists: ' . (is_dir($uploadDir) ? 'YES' : 'NO') . '<br>';
echo 'Upload directory writable: ' . (is_writable($uploadDir) ? 'YES' : 'NO') . '<br>';
echo 'Cover directory exists: ' . (is_dir($coverDir) ? 'YES' : 'NO') . '<br>';
echo 'Cover directory writable: ' . (is_writable($coverDir) ? 'YES' : 'NO') . '<br>';
?>
