<?php
// Test script to verify publication setup and preview generation

echo "<h1>Publication Setup Test</h1>\n";

echo "<h2>Directory Check</h2>\n";
$baseDir = realpath(__DIR__ . '/../');
$directories = [
    'uploads' => $baseDir . '/uploads',
    'documents' => $baseDir . '/uploads/documents',
    'previews' => $baseDir . '/uploads/previews',
    'publications' => $baseDir . '/uploads/publications',
    'covers' => $baseDir . '/uploads/publications/covers'
];

foreach ($directories as $name => $path) {
    $exists = is_dir($path);
    $writable = is_writable($path);
    echo "<p><strong>$name:</strong> " . ($exists ? '✅ Exists' : '❌ Missing');
    echo " | " . ($writable ? '✅ Writable' : '❌ Not writable');
    echo " | Path: $path</p>\n";
}

echo "<h2>Default Cover Check</h2>\n";
$defaultCover = $baseDir . '/uploads/publications/covers/default_cover.jpg';
$exists = file_exists($defaultCover);
$size = $exists ? filesize($defaultCover) : 0;
echo "<p><strong>Default cover:</strong> " . ($exists ? '✅ Exists' : '❌ Missing');
echo " | Size: " . number_format($size) . " bytes</p>\n";

echo "<h2>PHP Extensions Check</h2>\n";
$extensions = ['imagick', 'gd'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p><strong>$ext:</strong> " . ($loaded ? '✅ Loaded' : '❌ Not loaded') . "</p>\n";
}

echo "<h2>Command Line Tools Check</h2>\n";
$tools = ['gswin64c', 'convert', 'pdftoppm'];
foreach ($tools as $tool) {
    $available = false;
    if (function_exists('exec')) {
        exec("$tool -v 2>nul", $output, $returnCode);
        $available = $returnCode === 0;
    }
    echo "<p><strong>$tool:</strong> " . ($available ? '✅ Available' : '❌ Not available') . "</p>\n";
}

echo "<h2>Test Files Check</h2>\n";
$testFiles = glob($baseDir . '/uploads/documents/*.pdf');
echo "<p><strong>PDF files found:</strong> " . count($testFiles) . "</p>\n";
if (count($testFiles) > 0) {
    echo "<ul>\n";
    foreach (array_slice($testFiles, 0, 3) as $file) {
        $size = filesize($file);
        echo "<li>" . basename($file) . " (" . number_format($size) . " bytes)</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2>Path Resolution Test</h2>\n";
$testRelative = '../uploads/previews/test.jpg';
$testAbsolute = $baseDir . '/uploads/previews/test.jpg';
echo "<p><strong>Relative path:</strong> $testRelative</p>\n";
echo "<p><strong>Absolute path:</strong> $testAbsolute</p>\n";
echo "<p><strong>Relative resolves to:</strong> " . realpath(__DIR__ . '/' . $testRelative) . "</p>\n";

echo "<h2>Database Connection Test</h2>\n";
try {
    include "../database/database.php";
    $testQuery = $conn->query("SELECT COUNT(*) as count FROM publications");
    if ($testQuery) {
        $result = $testQuery->fetch_assoc();
        echo "<p><strong>Publications in database:</strong> " . $result['count'] . "</p>\n";
    } else {
        echo "<p><strong>Database query failed</strong></p>\n";
    }
    $conn->close();
} catch (Exception $e) {
    echo "<p><strong>Database error:</strong> " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>



