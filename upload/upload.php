<?php
session_start();
include "../database/database.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
    header("Location: ../login/login.html");
    exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Function to generate document preview
function generateDocumentPreview($filePath, $previewPath) {
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // Ensure previews directory exists
    $previewDir = dirname($previewPath);
    if (!is_dir($previewDir)) {
        mkdir($previewDir, 0755, true);
    }

    if ($fileExtension === 'pdf') {
        // Method 1: Try ImageMagick if available
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            try {
                $imagick = new Imagick();
                $imagick->setResolution(150, 150); // Higher resolution for better quality
                $imagick->readImage($filePath . '[0]'); // First page
                $imagick->setImageFormat('jpg');
                $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                $imagick->setImageCompressionQuality(85);
                $imagick->thumbnailImage(200, 280, true); // Resize maintaining aspect ratio
                $imagick->writeImage($previewPath);
                $imagick->clear();
                $imagick->destroy();
                error_log("PDF preview generated using ImageMagick: " . $previewPath);
                return true;
            } catch (Exception $e) {
                error_log("ImageMagick preview generation failed: " . $e->getMessage());
            }
        }

        // Method 2: Try using pdftoppm (poppler-utils) if available
        if (function_exists('exec')) {
            try {
                $tempPpm = tempnam(sys_get_temp_dir(), 'pdf_preview');
                $command = "pdftoppm -f 1 -l 1 -scale-to 200 -jpeg \"$filePath\" \"$tempPpm\" 2>/dev/null";
                exec($command, $output, $returnCode);

                if ($returnCode === 0) {
                    $ppmFile = $tempPpm . '-1.jpg';
                    if (file_exists($ppmFile)) {
                        rename($ppmFile, $previewPath);
                        // Clean up temp files
                        $tempFiles = glob($tempPpm . '*');
                        foreach ($tempFiles as $tempFile) {
                            @unlink($tempFile);
                        }
                        error_log("PDF preview generated using pdftoppm: " . $previewPath);
                        return true;
                    }
                }
                // Clean up temp file
                @unlink($tempPpm);
            } catch (Exception $e) {
                error_log("pdftoppm preview generation failed: " . $e->getMessage());
            }
        }

        // Method 3: Try using convert (ImageMagick command line)
        if (function_exists('exec')) {
            try {
                $command = "convert \"$filePath\"[0] -quality 85 -resize 200x280 \"$previewPath\" 2>/dev/null";
                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($previewPath) && filesize($previewPath) > 0) {
                    error_log("PDF preview generated using convert command: " . $previewPath);
                    return true;
                }
            } catch (Exception $e) {
                error_log("Convert command preview generation failed: " . $e->getMessage());
            }
        }

        // Method 4: Try using Ghostscript
        if (function_exists('exec')) {
            try {
                $command = "gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -dJPEGQ=85 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"$previewPath\" -r150 \"$filePath\" 2>/dev/null";
                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($previewPath) && filesize($previewPath) > 0) {
                    // Resize if needed
                    if (filesize($previewPath) > 0) {
                        $command2 = "convert \"$previewPath\" -resize 200x280 \"$previewPath\" 2>/dev/null";
                        exec($command2);
                    }
                    error_log("PDF preview generated using Ghostscript: " . $previewPath);
                    return true;
                }
            } catch (Exception $e) {
                error_log("Ghostscript preview generation failed: " . $e->getMessage());
            }
        }

        // Method 5: PHP-only solution using PDF parsing (basic)
        try {
            if (function_exists('file_get_contents') && class_exists('Imagick')) {
                // Try a simpler ImageMagick approach
                $imagick = new Imagick();
                $imagick->readImage($filePath . '[0]');
                $imagick->setImageFormat('jpg');
                $imagick->setImageCompressionQuality(75);
                $imagick->scaleImage(200, 0); // Scale to width 200, maintain aspect ratio
                $imagick->writeImage($previewPath);
                $imagick->clear();
                $imagick->destroy();
                error_log("PDF preview generated using fallback ImageMagick: " . $previewPath);
                return true;
            }
        } catch (Exception $e) {
            error_log("Fallback ImageMagick failed: " . $e->getMessage());
        }

        // Final fallback: Create a PDF placeholder
        createPlaceholderPreview($previewPath, 'PDF Document', 'This preview could not be generated. The document was uploaded successfully.');
        return true;

    } elseif (in_array($fileExtension, ['doc', 'docx'])) {
        // For Word documents, try to extract preview if possible
        try {
            // Try using unoconv or libreoffice to convert first page to image
            if (function_exists('exec')) {
                $tempPdf = tempnam(sys_get_temp_dir(), 'doc_preview') . '.pdf';
                $command = "libreoffice --headless --convert-to pdf \"$filePath\" --outdir " . dirname($tempPdf) . " 2>/dev/null";
                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($tempPdf)) {
                    // Now convert the PDF to image using the same methods as above
                    return generateDocumentPreview($tempPdf, $previewPath);
                }
            }
        } catch (Exception $e) {
            error_log("Word document preview generation failed: " . $e->getMessage());
        }

        // Fallback: Create a Word document placeholder
        createPlaceholderPreview($previewPath, 'Word Document');
        return true;
    }

    return false;
}

function createPlaceholderPreview($previewPath, $documentType, $additionalText = '') {
    // Create a placeholder image with better styling
    $image = imagecreatetruecolor(200, 280);
    $bgColor = imagecolorallocate($image, 248, 248, 248);
    $textColor = imagecolorallocate($image, 80, 80, 80);
    $borderColor = imagecolorallocate($image, 220, 220, 220);
    $accentColor = imagecolorallocate($image, 70, 130, 180); // Steel blue

    imagefill($image, 0, 0, $bgColor);
    imagerectangle($image, 0, 0, 199, 279, $borderColor);

    // Add document type with better styling
    $fontSize = 3;
    $text = $documentType;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $x = (200 - $textWidth) / 2;
    $y = 140 - (imagefontheight($fontSize) / 2); // Center vertically

    // Add a colored background for the main text
    $bgWidth = $textWidth + 20;
    $bgHeight = imagefontheight($fontSize) + 10;
    $bgX = (200 - $bgWidth) / 2;
    $bgY = $y - 5;
    imagefilledrectangle($image, $bgX, $bgY, $bgX + $bgWidth, $bgY + $bgHeight, $accentColor);

    imagestring($image, $fontSize, $x, $y, $text, imagecolorallocate($image, 255, 255, 255));

    // Add additional text if provided
    if (!empty($additionalText)) {
        $smallFontSize = 2;
        $lines = explode("\n", wordwrap($additionalText, 20, "\n"));
        $lineY = $y + 30;

        foreach ($lines as $line) {
            $lineWidth = imagefontwidth($smallFontSize) * strlen($line);
            $lineX = (200 - $lineWidth) / 2;
            imagestring($image, $smallFontSize, $lineX, $lineY, $line, $textColor);
            $lineY += imagefontheight($smallFontSize) + 2;
        }
    }

    // Add file icon indicator
    if ($documentType === 'PDF Document') {
        // Draw a simple PDF icon
        imagefilledrectangle($image, 80, 50, 120, 80, imagecolorallocate($image, 220, 53, 69));
        imagestring($image, 2, 85, 60, 'PDF', imagecolorallocate($image, 255, 255, 255));
    } elseif ($documentType === 'Word Document') {
        // Draw a simple Word icon
        imagefilledrectangle($image, 80, 50, 120, 80, imagecolorallocate($image, 0, 123, 191));
        imagestring($image, 2, 82, 60, 'DOC', imagecolorallocate($image, 255, 255, 255));
    }

    imagejpeg($image, $previewPath, 85);
    imagedestroy($image);
}

// Handle publication upload
if (isset($_POST['upload'])) {
    $studentID = $_SESSION['studentID'];
    $title = trim($_POST['title']);
    $authors = trim($_POST['authors']);
    $department = trim($_POST['department']);
    $type = trim($_POST['type']);
    $abstract = trim($_POST['abstract']);

    // Validate required fields
    if (empty($title) || empty($authors) || empty($abstract)) {
        $errorMessage = "Please fill in all required fields (Title, Authors, Abstract).";
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Please select a file to upload.";
    } else {
        $fileName = $_FILES['file']['name'];
        $fileTmp = $_FILES['file']['tmp_name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validate file size (max 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            $errorMessage = "File size too large. Maximum size is 10MB.";
        } elseif (!in_array($fileExtension, ['pdf', 'doc', 'docx'])) {
            $errorMessage = "Invalid file type. Only PDF, DOC, and DOCX files are allowed.";
        } else {
            // Ensure directories exist
            $documentsDir = __DIR__ . '/../uploads/documents';
            $previewsDir = __DIR__ . '/../uploads/previews';
            if (!is_dir($documentsDir)) {
                mkdir($documentsDir, 0755, true);
            }
            if (!is_dir($previewsDir)) {
                mkdir($previewsDir, 0755, true);
            }

            // Generate unique filename with timestamp
            $timestamp = time();
            $originalName = pathinfo($fileName, PATHINFO_FILENAME);
            $uniqueFileName = $timestamp . '_' . preg_replace('/[^a-zA-Z0-9\-_]/', '_', $originalName) . '.' . $fileExtension;
            $filePath = $documentsDir . '/' . $uniqueFileName;

            if (move_uploaded_file($fileTmp, $filePath)) {
                // Generate preview
                $previewFileName = $timestamp . '_' . preg_replace('/[^a-zA-Z0-9\-_]/', '_', $originalName) . '_preview.jpg';
                $previewPath = $previewsDir . '/' . $previewFileName;

                $previewGenerated = generateDocumentPreview($filePath, $previewPath);

                // Use fallback if preview generation failed
                if (!$previewGenerated) {
                    $bgImagePath = 'assets/preview-fallback.jpg';
                    error_log("Preview generation failed for: " . $filePath);
                } else {
                    $bgImagePath = 'uploads/previews/' . $previewFileName;
                }

                // Insert into publications table
                $stmt = $conn->prepare("INSERT INTO publications
                    (studentID, title, published_datetime, authors, department, type, abstract, file_path, thumbnail, views)
                    VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 0)");

                $documentPath = 'uploads/documents/' . $uniqueFileName;
                $thumbnailPath = $previewGenerated ? 'uploads/previews/' . $previewFileName : 'uploads/publications/covers/default_cover.jpg';

                $stmt->bind_param("sssssss", $studentID, $title, $authors, $department, $type, $abstract, $documentPath, $thumbnailPath);

                if ($stmt->execute()) {
                    // Insert into library (user's own publications are automatically added to their library)
                    $publicationID = $stmt->insert_id;
                    $stmt2 = $conn->prepare("INSERT INTO library (studentID, publicationID) VALUES (?, ?)");
                    $stmt2->bind_param("si", $studentID, $publicationID);
                    $stmt2->execute();
                    $stmt2->close();

                    $successMessage = "Publication uploaded successfully! Your document is now available in your library and publications section.";
                } else {
                    $errorMessage = "Database error: " . $stmt->error;
                    // Clean up files if database insert failed
                    @unlink($filePath);
                    if (file_exists($previewPath)) @unlink($previewPath);
                }

                $stmt->close();
            } else {
                $errorMessage = "Failed to upload file. Please try again.";
            }
        }
    }
}

// Set layout variables
$pageTitle = 'Upload';
$activeNav = 'upload';
$additionalCSS = ['upload/upload.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Upload Document</h1>
</div>

<!-- Upload Form -->
<div class="content-section">
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="form-row">
            <div class="form-group">
                <label for="title">Title <span class="required">*</span>:</label>
                <input type="text" id="title" name="title" required placeholder="Enter publication title">
            </div>

            <div class="form-group">
                <label for="authors">Authors <span class="required">*</span>:</label>
                <input type="text" id="authors" name="authors" required placeholder="Enter author names">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" id="department" name="department" placeholder="Enter department (optional)">
            </div>

            <div class="form-group">
                <label for="type">Type:</label>
                <input type="text" id="type" name="type" placeholder="Enter publication type (optional)">
            </div>
        </div>

        <div class="form-group">
            <label for="abstract">Abstract <span class="required">*</span>:</label>
            <textarea id="abstract" name="abstract" rows="4" required placeholder="Enter publication abstract/description"></textarea>
        </div>

        <div class="form-group">
            <label for="file">Select File <span class="required">*</span>:</label>
            <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx">
            <small>Accepted formats: PDF, DOC, DOCX (Max: 10MB)</small>
        </div>

        <div class="form-actions">
            <button type="submit" name="upload" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Publication
            </button>
            <button type="reset" class="btn btn-secondary">
                <i class="fas fa-eraser"></i> Clear
            </button>
        </div>
    </form>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>

