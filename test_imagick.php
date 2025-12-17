<?php
// Test Imagick PDF reading and thumbnail generation

echo "Testing Imagick PDF thumbnail generation...\n\n";

// Check if Imagick extension is loaded
if (!extension_loaded('imagick')) {
    echo "❌ Imagick extension is not loaded\n";
    exit(1);
}
echo "✅ Imagick extension is loaded\n";

// Check if we can create Imagick object
try {
    $imagick = new Imagick();
    echo "✅ Imagick object created successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to create Imagick object: " . $e->getMessage() . "\n";
    exit(1);
}

// Test PDF file path
$testPdfPath = __DIR__ . '/uploads/documents/1765210712_CRISP_DM.pdf';

echo "Looking for file: $testPdfPath\n";
if (!file_exists($testPdfPath)) {
    echo "❌ Test PDF file not found: $testPdfPath\n";

    // List available files
    echo "Available files in documents directory:\n";
    $dirPath = __DIR__ . '/uploads/documents';
    echo "Directory path: $dirPath\n";
    echo "Directory exists: " . (is_dir($dirPath) ? 'Yes' : 'No') . "\n";
    echo "Directory readable: " . (is_readable($dirPath) ? 'Yes' : 'No') . "\n";

    if (is_dir($dirPath)) {
        $files = scandir($dirPath);
        echo "Files found: " . count($files) . "\n";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "  - $file\n";
            }
        }
    }
    exit(1);
}
echo "✅ Test PDF file exists: $testPdfPath\n";

// Test reading PDF
try {
    $imagick->readImage($testPdfPath . '[0]'); // Read first page
    echo "✅ Successfully read first page of PDF\n";
} catch (Exception $e) {
    echo "❌ Failed to read PDF: " . $e->getMessage() . "\n";
    exit(1);
}

// Test thumbnail generation
try {
    $imagick->thumbnailImage(200, 280, true); // Resize maintaining aspect ratio
    echo "✅ Successfully created thumbnail\n";
} catch (Exception $e) {
    echo "❌ Failed to create thumbnail: " . $e->getMessage() . "\n";
    exit(1);
}

// Test saving thumbnail
$testOutputPath = __DIR__ . '/uploads/previews/test_thumbnail.jpg';
try {
    $imagick->writeImage($testOutputPath);
    echo "✅ Successfully saved thumbnail to: $testOutputPath\n";

    if (file_exists($testOutputPath)) {
        echo "✅ Thumbnail file exists and is readable\n";
        $fileSize = filesize($testOutputPath);
        echo "📊 Thumbnail file size: " . number_format($fileSize) . " bytes\n";
    } else {
        echo "❌ Thumbnail file was not created\n";
    }
} catch (Exception $e) {
    echo "❌ Failed to save thumbnail: " . $e->getMessage() . "\n";
}

// Clean up
$imagick->clear();
$imagick->destroy();

echo "\n🎉 Imagick PDF thumbnail generation test completed!\n";
?>
