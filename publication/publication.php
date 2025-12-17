<?php
session_start();
include "../database/database.php";

// Redirect to login if not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

function generateDocumentPreview($filePath, $previewPath) {
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // Define absolute paths from document root
    $baseDir = realpath(__DIR__ . '/../');
    $documentsDir = $baseDir . '/uploads/documents';
    $previewsDir = $baseDir . '/uploads/previews';
    $coversDir = $baseDir . '/uploads/publications/covers';

    // Ensure all required directories exist with proper permissions
    $directories = [$documentsDir, $previewsDir, $coversDir];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("Failed to create directory: $dir");
                return 'error';
            }
        }
        // Ensure directory is writable
        if (!is_writable($dir)) {
            error_log("Directory not writable: $dir");
            return 'error';
        }
    }

    // Convert relative paths to absolute paths
    if (!realpath($filePath)) {
        $filePath = $baseDir . '/' . ltrim($filePath, '/');
    }
    if (!realpath(dirname($previewPath))) {
        $previewPath = $baseDir . '/' . ltrim($previewPath, '/');
    }

    error_log("Processing file: $filePath");
    error_log("Preview path: $previewPath");

    if ($fileExtension === 'pdf') {
        // Verify file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            error_log("PDF file not accessible: $filePath");
            createPlaceholderPreview($previewPath, 'PDF Document', 'File not accessible');
            return 'placeholder';
        }

        // Verify file is not empty and has minimum size
        if (filesize($filePath) < 100) {
            error_log("PDF file is too small: $filePath");
            createPlaceholderPreview($previewPath, 'PDF Document', 'File appears to be corrupted');
            return 'placeholder';
        }

        // Try to validate PDF header
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            $header = fread($handle, 8);
            fclose($handle);
            if (strpos($header, '%PDF-') !== 0) {
                error_log("File does not appear to be a valid PDF: $filePath");
                createPlaceholderPreview($previewPath, 'PDF Document', 'Invalid PDF format');
                return 'placeholder';
            }
        }

        // Method 1: PHP Imagick
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            try {
                $imagick = new Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($filePath . '[0]');

                // Check if image was loaded successfully
                if ($imagick->getNumberImages() > 0) {
                    // Get image dimensions to verify it's not empty
                    $geometry = $imagick->getImageGeometry();
                    if ($geometry['width'] > 0 && $geometry['height'] > 0) {
                        $imagick->setImageFormat('jpg');
                        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                        $imagick->setImageCompressionQuality(90);
                        $imagick->thumbnailImage(200, 150, true);

                        // Verify the thumbnail was created successfully
                        $thumbGeometry = $imagick->getImageGeometry();
                        if ($thumbGeometry['width'] > 0 && $thumbGeometry['height'] > 0) {
                            $imagick->writeImage($previewPath);

                            // Verify file was written and has content
                            if (file_exists($previewPath) && filesize($previewPath) > 1000) {
                                $imagick->clear();
                                $imagick->destroy();
                                error_log("PDF preview generated using PHP Imagick: " . $previewPath);
                                return 'success';
                            } else {
                                error_log("PHP Imagick: Generated file is too small or missing");
                                @unlink($previewPath); // Remove failed file
                            }
                        } else {
                            error_log("PHP Imagick: Thumbnail has invalid dimensions");
                        }
                    } else {
                        error_log("PHP Imagick: Source image has invalid dimensions");
                    }
                } else {
                    error_log("PHP Imagick: No images loaded from PDF");
                }

                $imagick->clear();
                $imagick->destroy();
            } catch (Exception $e) {
                error_log("PHP Imagick failed: " . $e->getMessage());
            }
        }

        // Method 2: ImageMagick convert command
        if (function_exists('exec')) {
            try {
                // Escape paths for Windows/Linux compatibility
                $escapedFilePath = escapeshellarg($filePath);
                $escapedPreviewPath = escapeshellarg($previewPath);
                $command = "convert -density 150 {$escapedFilePath}[0] -quality 90 -resize 200x150 -background white -alpha remove -alpha off {$escapedPreviewPath} 2>nul";
                exec($command, $output, $returnCode);
                if ($returnCode === 0 && file_exists($previewPath) && filesize($previewPath) > 1000) {
                    error_log("PDF preview generated using convert: " . $previewPath);
                    return 'success';
                } else {
                    error_log("Convert command failed with return code: $returnCode");
                    @unlink($previewPath); // Remove failed file
                }
            } catch (Exception $e) {
                error_log("Convert command exception: " . $e->getMessage());
            }
        }

        // Method 3: Ghostscript
        if (function_exists('exec')) {
            try {
                $escapedFilePath = escapeshellarg($filePath);
                $escapedPreviewPath = escapeshellarg($previewPath);
                $command = "gswin64c -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -dJPEGQ=90 -dFirstPage=1 -dLastPage=1 -sOutputFile={$escapedPreviewPath} -dPDFFitPage -dDEVICEWIDTHPOINTS=200 -dDEVICEHEIGHTPOINTS=150 {$escapedFilePath} 2>nul";
                exec($command, $output, $returnCode);
                if ($returnCode === 0 && file_exists($previewPath) && filesize($previewPath) > 1000) {
                    error_log("PDF preview generated using Ghostscript: " . $previewPath);
                    return 'success';
                } else {
                    error_log("Ghostscript command failed with return code: $returnCode");
                    @unlink($previewPath); // Remove failed file
                }
            } catch (Exception $e) {
                error_log("Ghostscript exception: " . $e->getMessage());
            }
        }

        // Method 4: pdftoppm
        if (function_exists('exec')) {
            try {
                $tempBase = tempnam(sys_get_temp_dir(), 'pdf_preview');
                $escapedFilePath = escapeshellarg($filePath);
                $command = "pdftoppm -f 1 -l 1 -scale-to-x 200 -scale-to-y 280 -jpeg {$escapedFilePath} \"{$tempBase}\" 2>nul";
                exec($command, $output, $returnCode);
                if ($returnCode === 0) {
                    $ppmFile = $tempBase . '-1.jpg';
                    if (file_exists($ppmFile)) {
                        rename($ppmFile, $previewPath);
                        // Clean up temp files
                        $tempFiles = glob($tempBase . '*');
                        foreach ($tempFiles as $tempFile) {
                            @unlink($tempFile);
                        }
                        error_log("PDF preview generated using pdftoppm: " . $previewPath);
                        return 'success';
                    }
                }
                @unlink($tempBase);
                error_log("pdftoppm command failed with return code: $returnCode");
            } catch (Exception $e) {
                error_log("pdftoppm exception: " . $e->getMessage());
            }
        }

        // All methods failed -> placeholder
        error_log("All PDF preview methods failed, creating placeholder");
        createPlaceholderPreview($previewPath, 'PDF Document', 'First page preview could not be generated.');
        return 'placeholder';
    } else {
        // Unsupported file type
        error_log("Unsupported file type: $fileExtension");
        createPlaceholderPreview($previewPath, 'Unsupported File', 'This file type is not supported for preview generation.');
        return 'placeholder';
    }
}

function createPlaceholderPreview($previewPath, $documentType, $additionalText = '') {
    try {
        // Ensure directory exists
        $previewDir = dirname($previewPath);
        if (!is_dir($previewDir)) {
            mkdir($previewDir, 0755, true);
        }

        // Create a placeholder image with better styling
        $image = imagecreatetruecolor(200, 150);
        if (!$image) {
            error_log("Failed to create image resource for placeholder: $previewPath");
            return false;
        }

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
        } elseif ($documentType === 'Unsupported File') {
            // Draw a generic file icon
            imagefilledrectangle($image, 80, 40, 120, 70, imagecolorallocate($image, 100, 100, 100));
            imagestring($image, 2, 85, 50, 'FILE', imagecolorallocate($image, 255, 255, 255));
        }

        $result = imagejpeg($image, $previewPath, 85);
        imagedestroy($image);

        if ($result) {
            error_log("Placeholder preview created: $previewPath");
            return true;
        } else {
            error_log("Failed to save placeholder preview: $previewPath");
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception creating placeholder preview: " . $e->getMessage());
        return false;
    }
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure all required directories exist
function ensureDirectoriesExist() {
    $baseDir = realpath(__DIR__ . '/../');
    $directories = [
        $baseDir . '/uploads',
        $baseDir . '/uploads/documents',
        $baseDir . '/uploads/previews',
        $baseDir . '/uploads/publications',
        $baseDir . '/uploads/publications/covers'
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("Failed to create directory: $dir");
            } else {
                error_log("Created directory: $dir");
            }
        } elseif (!is_writable($dir)) {
            error_log("Directory not writable: $dir");
        }
    }
}

// Initialize directories
ensureDirectoriesExist();

// Ensure default cover exists
$defaultCoverPath = realpath(__DIR__ . '/../uploads/publications/covers/default_cover.jpg');
if (!$defaultCoverPath || !file_exists($defaultCoverPath)) {
    error_log("Default cover image not found, creating placeholder");
    createPlaceholderPreview(realpath(__DIR__ . '/../uploads/publications/covers/default_cover.jpg'), 'Default Cover', 'Default publication cover');
}

// Handle publication upload
if (isset($_POST['publish'])) {
  $studentID = $_SESSION['studentID'];
  $title = $_POST['title'];
  $published = date('Y-m-d H:i:s', strtotime($_POST['published']));
  $authors = $_POST['authors'];
  $department = $_POST['department'];
  $type = $_POST['type'];
  $abstract = $_POST['abstract'];

  // Handle PDF/Word file upload
  $fileName = $_FILES['file']['name'];
  $fileTmp = $_FILES['file']['tmp_name'];

  // Define absolute paths
  $baseDir = realpath(__DIR__ . '/../');
  $documentsDir = $baseDir . '/uploads/documents';
  $previewsDir = $baseDir . '/uploads/previews';

  // Ensure directories exist
  if (!is_dir($documentsDir)) {
      mkdir($documentsDir, 0755, true);
  }
  if (!is_dir($previewsDir)) {
      mkdir($previewsDir, 0755, true);
  }

  // Generate unique filename to avoid conflicts
  $timestamp = time();
  $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

  // Validate file type - only PDF allowed
  if ($fileExtension !== 'pdf') {
    echo "<script>alert('Only PDF files are allowed. Please upload a PDF file.');</script>";
    exit();
  }

  $originalName = pathinfo($fileName, PATHINFO_FILENAME);
  $uniqueFileName = $timestamp . '_' . preg_replace('/[^a-zA-Z0-9\-_]/', '_', $originalName) . '.' . $fileExtension;
  $filePath = $documentsDir . '/' . $uniqueFileName;

  if (move_uploaded_file($fileTmp, $filePath)) {
    error_log("File uploaded successfully to: $filePath");

    // Generate thumbnail from first page
    $previewFileName = $timestamp . '_' . preg_replace('/[^a-zA-Z0-9\-_]/', '_', $originalName) . '_preview.jpg';
    $previewPath = $previewsDir . '/' . $previewFileName;

    $previewGenerated = generateDocumentPreview($filePath, $previewPath);
    $thumbnailPath = ($previewGenerated === 'success') ? 'uploads/previews/' . $previewFileName : 'uploads/publications/covers/default_cover.jpg';

    error_log("Upload processing complete - File: $filePath, Thumbnail: $thumbnailPath, Preview status: $previewGenerated");
  } else {
    $thumbnailPath = 'uploads/publications/covers/default_cover.jpg';
    $uploadError = error_get_last();
    error_log("File upload failed for: $fileName - Error: " . ($uploadError ? $uploadError['message'] : 'Unknown error'));
  }

  // Insert into publications table with consistent relative paths
  $stmt = $conn->prepare("INSERT INTO publications
        (studentID, title, published_datetime, authors, department, type, abstract, file_path, thumbnail)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $documentPath = 'uploads/documents/' . $uniqueFileName; // Relative path for web access
  $stmt->bind_param("sssssssss", $studentID, $title, $published, $authors, $department, $type, $abstract, $documentPath, $thumbnailPath);
  $stmt->execute();

  $publicationID = $stmt->insert_id;

  // Insert into library
  $stmt2 = $conn->prepare("INSERT INTO library (studentID, publicationID) VALUES (?, ?)");
  $stmt2->bind_param("si", $studentID, $publicationID);
  $stmt2->execute();

  $stmt->close();
  $stmt2->close();

  echo "<script>alert('Publication uploaded successfully!'); location.href='publication.php';</script>";
}

// Search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Set layout variables
$pageTitle = 'Publication';
$activeNav = 'publication';
$additionalCSS = ['publication_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Top Bar -->
<div class="top-bar">
  <span class="back" onclick="goBack()">&larr; Back</span>
  <div class="top-bar-right">
    <h1>Publication</h1>
    <div class="search-box">
      <input type="text" id="searchPublicationInput" placeholder="Search titles..." value="<?php echo htmlspecialchars($searchQuery); ?>">
      <img src="../icons/authors/search.png" class="search-icon" alt="Search">
    </div>
  </div>
</div>

<!-- Scroll Area -->
<div class="scroll-area">
  <!-- Research Titles -->
  <section class="section-box">
    <h2>Research Titles</h2>
    <div class="card-grid">
      <div class="card upload-card" onclick="openModal()">
        <div class="upload-card-content">
          <i class="fa fa-plus"></i>
          <span>Upload more books</span>
        </div>
      </div>

      <?php
      $studentID = $_SESSION['studentID'];
  $where = "WHERE p.studentID='$studentID'";
  if ($searchQuery !== '') {
    $safeSearch = $conn->real_escape_string($searchQuery);
    $where .= " AND p.title LIKE '%$safeSearch%'";
  }
  $result = $conn->query("SELECT p.*, r.firstName, r.lastName FROM publications p JOIN registration r ON p.studentID = r.studentID $where ORDER BY p.published_datetime DESC");
      if ($result) {
        while ($row = $result->fetch_assoc()) {
          echo '<div class="card">';
          $imageSrc = isset($row['thumbnail']) && !empty($row['thumbnail']) ? '../' . $row['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
          $imageSrc .= '?t=' . time(); // Cache busting
          echo '<img src="' . htmlspecialchars($imageSrc) . '" class="cover-img" alt="Publication cover">';
          echo '<div class="card-info">';
          echo '<h4 class="card-title">' . htmlspecialchars($row['title']) . '</h4>';
          echo '<div class="posted-by">';
          echo 'Posted by: <a href="../profile/profile_view.php?studentID=' . htmlspecialchars($row['studentID']) . '">' . htmlspecialchars($row['firstName'] . ' ' . $row['lastName']) . '</a>';
          echo '</div>';
          echo '<div class="posted-by">Published: ' . date("M d, Y", strtotime($row['published_datetime'])) . '</div>';
          echo '<div class="card-actions">';
          echo '<button onclick="previewPublication(\'' . htmlspecialchars($row['file_path']) . '\', \'' . htmlspecialchars(addslashes($row['title'])) . '\', \'' . htmlspecialchars(addslashes($row['firstName'] . ' ' . $row['lastName'])) . '\', \'' . htmlspecialchars(addslashes($row['published_datetime'])) . '\', \'' . htmlspecialchars(addslashes($row['abstract'] ?? '')) . '\', \'' . htmlspecialchars(addslashes($row['department'] ?? '')) . '\', \'' . htmlspecialchars(addslashes($row['type'] ?? '')) . '\', \'' . htmlspecialchars(addslashes($row['thumbnail'] ?? '')) . '\')" class="btn btn-primary btn-sm">';
          echo '<i class="fas fa-eye"></i> Preview';
          echo '</button>';
          echo '<button onclick="deletePublication(' . $row['publicationID'] . ', \'' . htmlspecialchars(addslashes($row['title'])) . '\')" class="btn btn-danger btn-sm">';
          echo '<i class="fas fa-trash"></i>';
          echo '</button>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
        }
      }
      ?>
    </div>
  </section>

  <!-- Other Documents -->
  <section class="section-box">
    <h2>Other Documents</h2>
    <div class="card-grid">
      <?php
      $where2 = "WHERE p.studentID<>'$studentID'";
      if ($searchQuery !== '') {
        $safeSearch2 = $conn->real_escape_string($searchQuery);
        $where2 .= " AND p.title LIKE '%$safeSearch2%'";
      }
      $result2 = $conn->query("SELECT p.*, r.firstName, r.lastName FROM publications p JOIN registration r ON p.studentID = r.studentID $where2 ORDER BY p.published_datetime DESC");
      if ($result2) {
        while ($row2 = $result2->fetch_assoc()) {
          echo '<div class="card">';
          $imageSrc = isset($row2['thumbnail']) && !empty($row2['thumbnail']) ? '../' . $row2['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
          $imageSrc .= '?t=' . time(); // Cache busting
          echo '<img src="' . htmlspecialchars($imageSrc) . '" class="cover-img" alt="Publication cover">';
          echo '<div class="card-info">';
          echo '<h4 class="card-title">' . htmlspecialchars($row2['title']) . '</h4>';
          echo '<div class="posted-by">';
          echo 'Posted by: <a href="../profile/profile_view.php?studentID=' . htmlspecialchars($row2['studentID']) . '">' . htmlspecialchars($row2['firstName'] . ' ' . $row2['lastName']) . '</a>';
          echo '</div>';
          echo '<div class="posted-by">' . date("M d, Y", strtotime($row2['published_datetime'])) . '</div>';
          echo '<div class="card-actions">';
          echo '<button onclick="previewPublication(\'' . htmlspecialchars($row2['file_path']) . '\', \'' . htmlspecialchars(addslashes($row2['title'])) . '\', \'' . htmlspecialchars(addslashes($row2['firstName'] . ' ' . $row2['lastName'])) . '\', \'' . htmlspecialchars(addslashes($row2['published_datetime'])) . '\', \'' . htmlspecialchars(addslashes($row2['abstract'] ?? '')) . '\', \'' . htmlspecialchars(addslashes($row2['department'] ?? '')) . '\', \'' . htmlspecialchars(addslashes($row2['type'] ?? '')) . '\')" class="btn btn-primary btn-sm">';
          echo '<i class="fas fa-eye"></i> Preview';
          echo '</button>';
          echo '<button onclick="savePublication(' . $row2['publicationID'] . ', \'' . htmlspecialchars(addslashes($row2['title'])) . '\')" class="btn btn-success btn-sm">';
          echo '<i class="fas fa-bookmark"></i> Save';
          echo '</button>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
        }
      }
      ?>
    </div>
    <p class="caption">CCSearch Research Records of CCSP</p>
  </section>
</div>

<!-- Modal -->
<div id="uploadModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <form method="post" enctype="multipart/form-data" class="white-box">
      <!-- LEFT SECTION -->
      <div class="left-section">
        <div class="back" onclick="closeModal()">&lt;&lt; Back</div>
        <div class="preview-container">
          <div id="preview-placeholder" class="preview-placeholder">
            <i class="fas fa-file-alt"></i>
            <span>Document Preview</span>
            <small>Upload a file to see preview</small>
          </div>
          <div id="file-preview" class="file-preview" style="display: none;">
            <div class="file-info">
              <i id="file-icon" class="fas fa-file-alt"></i>
              <div class="file-details">
                <span id="file-name">File Name</span>
                <small id="file-size">File Size</small>
                <small id="file-type">File Type</small>
              </div>
            </div>
          </div>
        </div>
        <!-- PDF/Word upload -->
        <button type="button" class="upload-btn" onclick="document.getElementById('file-input').click()">Upload
          File</button>
        <input type="file" id="file-input" name="file" style="display:none" accept=".pdf">
        <p style="font-size: 12px; color: #666; margin-top: 5px; text-align: center;">PDF files only</p>
      </div>
      <!-- RIGHT SECTION -->
      <div class="right-section">
        <div class="form-group">
          <label for="title">Title:</label>
          <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
          <label for="published">Published:</label>
          <input type="date" id="published" name="published" required>
        </div>
        <div class="form-group">
          <label for="authors">Authors:</label>
          <input type="text" id="authors" name="authors" required>
        </div>
        <div class="form-group">
          <label for="department">Department:</label>
          <select id="department" name="department">
            <option>Institute of Information Technology</option>
          </select>
        </div>
        <div class="form-group">
          <label for="types">Types:</label>
          <select id="types" name="type">
            <option>Reference Book</option>
          </select>
        </div>
        <div class="form-group">
          <label for="abstract">Abstract/Description:</label>
          <textarea id="abstract" name="abstract"></textarea>
        </div>
        <div class="publish-wrapper">
          <button type="submit" name="publish" class="publish-btn">Publish</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
    // Modal functionality
    function openModal() {
        document.getElementById("uploadModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("uploadModal").style.display = "none";
    }

    // File preview functionality
    document.getElementById("file-input").addEventListener("change", function(e) {
        const file = e.target.files[0];
        if (file) {
            showFilePreview(file);
        } else {
            hideFilePreview();
        }
    });

    function showFilePreview(file) {
        const fileName = file.name;
        const fileExtension = fileName.split(".").pop().toLowerCase();

        // Only allow PDF files
        if (fileExtension !== 'pdf') {
            alert('Only PDF files are allowed. Please select a PDF file.');
            hideFilePreview();
            // Clear the file input
            document.getElementById("file-input").value = '';
            return;
        }

        const fileSize = formatFileSize(file.size);
        const fileType = getFileTypeFromName(fileName);

        // Update file preview elements
        document.getElementById("file-name").textContent = fileName;
        document.getElementById("file-size").textContent = fileSize;
        document.getElementById("file-type").textContent = fileType;

        // Update file icon based on filename (more reliable than MIME type)
        updateFileIcon(fileName);

        // Show file preview, hide placeholder
        document.getElementById("preview-placeholder").style.display = "none";
        document.getElementById("file-preview").style.display = "flex";
    }

    function hideFilePreview() {
        document.getElementById("preview-placeholder").style.display = "flex";
        document.getElementById("file-preview").style.display = "none";
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    }

    function getFileTypeFromName(fileName) {
        const extension = fileName.split(".").pop().toLowerCase();
        const typeMap = {
            "pdf": "PDF Document"
        };
        return typeMap[extension] || extension.toUpperCase() + " File";
    }

    function updateFileIcon(fileName) {
        const iconElement = document.getElementById("file-icon");
        iconElement.className = "fas"; // Reset classes

        const extension = fileName.split(".").pop().toLowerCase();

        switch (extension) {
            case "pdf":
                iconElement.classList.add("fa-file-pdf");
                break;
            default:
                iconElement.classList.add("fa-file");
                break;
        }
    }


    // Save publication functionality
    function savePublication(publicationID, title) {
        // Check if modal exists, create if not
        var modal = document.getElementById('saveModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'saveModal';
            modal.className = 'modal';
            modal.innerHTML = '<div class="modal-content"><div class="modal-header"><h3>Save Publication</h3><span class="close-modal" onclick="closeSaveModal()">&times;</span></div><div class="modal-body"><p>Do you want to save "<span id="saveTitle"></span>" to your saved publications?</p><p>You can view saved publications in your library.</p></div><div class="modal-footer"><button onclick="closeSaveModal()" class="btn btn-secondary">Cancel</button><button id="confirmSaveBtn" onclick="confirmSave()" class="btn btn-success">Save Publication</button></div></div>';
            document.body.appendChild(modal);
        }

        window.publicationToSave = publicationID;
        document.getElementById('saveTitle').textContent = title;
        modal.style.display = 'flex';
    }

    function closeSaveModal() {
        const modal = document.getElementById('saveModal');
        if (modal) {
            modal.style.display = 'none';
            window.publicationToSave = null;
        }
    }

    function confirmSave() {
        if (!window.publicationToSave) return;

        const btn = document.getElementById('confirmSaveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        fetch('../home/save_publication.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'publicationID=' + encodeURIComponent(window.publicationToSave)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Publication saved successfully! You can view it in your library.');
                closeSaveModal();
            } else {
                alert('Error saving publication: ' + (data.message || 'Unknown error'));
                closeSaveModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the publication.');
            closeSaveModal();
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Save Publication';
        });
    }

    // Delete publication functionality
    function deletePublication(publicationID, title) {
        // Check if modal exists, create if not
        var modal = document.getElementById('deleteModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'deleteModal';
            modal.className = 'modal';

            // Create modal content using DOM methods
            var modalContent = document.createElement('div');
            modalContent.className = 'modal-content';

            var modalHeader = document.createElement('div');
            modalHeader.className = 'modal-header';

            var modalTitle = document.createElement('h3');
            modalTitle.textContent = 'Delete Publication';
            modalHeader.appendChild(modalTitle);

            var closeBtn = document.createElement('span');
            closeBtn.className = 'close-modal';
            closeBtn.innerHTML = '&times;';
            closeBtn.onclick = closeDeleteModal;
            modalHeader.appendChild(closeBtn);

            var modalBody = document.createElement('div');
            modalBody.className = 'modal-body';

            var para1 = document.createElement('p');
            para1.innerHTML = 'Are you sure you want to delete "<span id="deleteTitle"></span>"?';
            modalBody.appendChild(para1);

            var para2 = document.createElement('p');
            para2.className = 'warning-text';
            para2.textContent = 'This action cannot be undone.';
            modalBody.appendChild(para2);

            var modalFooter = document.createElement('div');
            modalFooter.className = 'modal-footer';

            var cancelBtn = document.createElement('button');
            cancelBtn.className = 'btn btn-secondary';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = closeDeleteModal;
            modalFooter.appendChild(cancelBtn);

            var deleteBtn = document.createElement('button');
            deleteBtn.id = 'confirmDeleteBtn';
            deleteBtn.className = 'btn btn-danger';
            deleteBtn.textContent = 'Delete';
            deleteBtn.onclick = confirmDelete;
            modalFooter.appendChild(deleteBtn);

            modalContent.appendChild(modalHeader);
            modalContent.appendChild(modalBody);
            modalContent.appendChild(modalFooter);
            modal.appendChild(modalContent);

            document.body.appendChild(modal);
        }

        window.publicationToDelete = publicationID;
        document.getElementById('deleteTitle').textContent = title;
        modal.style.display = 'flex';
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        if (modal) {
            modal.style.display = 'none';
            window.publicationToDelete = null;
        }
    }

    function confirmDelete() {
        if (!window.publicationToDelete) return;

        const btn = document.getElementById('confirmDeleteBtn');
        btn.disabled = true;
        btn.textContent = 'Deleting...';

        fetch('../home/delete_publication.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'publicationID=' + encodeURIComponent(window.publicationToDelete)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting publication: ' + (data.message || 'Unknown error'));
                closeDeleteModal();
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            alert('An error occurred while deleting the publication. Check console for details.');
            closeDeleteModal();
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Delete';
        });
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
    const uploadModal = document.getElementById("uploadModal");
    const saveModal = document.getElementById('saveModal');
    const deleteModal = document.getElementById('deleteModal');
    const previewModal = document.getElementById('previewModal');

    if (event.target === uploadModal) uploadModal.style.display = "none";
    if (saveModal && event.target === saveModal) closeSaveModal();
    if (deleteModal && event.target === deleteModal) closeDeleteModal();
    if (previewModal && event.target === previewModal) closePreviewModal();
}

// Publication search (titles)
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchPublicationInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const q = searchInput.value.trim();
                if (q) {
                    window.location.href = 'publication.php?search=' + encodeURIComponent(q);
                } else {
                    window.location.href = 'publication.php';
                }
            }
        });
    }
});

// Publication preview functionality
function previewPublication(filePath, title, author, publishDate, abstract, department, type, thumbnail) {
    // Format the publication date
    const formattedDate = new Date(publishDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Create preview modal
    const modal = document.createElement('div');
    modal.id = 'previewModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content preview-modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close-modal" onclick="closePreviewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="preview-content-wrapper">
                    ${thumbnail ? `<div class="preview-thumbnail-container">
                        <img src="../${thumbnail}?t=${Date.now()}" alt="Document preview" class="preview-thumbnail">
                    </div>` : ''}
                    <div class="preview-details-container">
                <div class="publication-details">
                    <div class="detail-row">
                        <strong>Author:</strong> <span>${author}</span>
                    </div>
                    <div class="detail-row">
                        <strong>Published:</strong> <span>${formattedDate}</span>
                    </div>
                    ${department ? `<div class="detail-row"><strong>Department:</strong> <span>${department}</span></div>` : ''}
                    ${type ? `<div class="detail-row"><strong>Type:</strong> <span>${type}</span></div>` : ''}
                </div>
                <div class="abstract-section">
                    <div class="abstract-label"><strong>Abstract:</strong></div>
                    <div class="abstract-text">${abstract || 'No abstract available.'}</div>
                </div>
                    </div>
                </div>
                <div class="preview-actions">
                    <a href="../${filePath}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> View Full Document
                    </a>
                    <a href="../${filePath}" download class="btn btn-secondary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    modal.style.display = 'flex';
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.remove();
    }
}
</script>


