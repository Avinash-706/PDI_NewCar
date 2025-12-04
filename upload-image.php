<?php
/**
 * Async Single Image Upload Handler
 * Uploads, compresses, and saves one image at a time
 * Cross-platform compatible with guaranteed JSON responses
 */

// Prevent any output before JSON
ob_start();

// Error handler to catch all errors and convert to exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Exception handler to ensure JSON response even on fatal errors
set_exception_handler(function($exception) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
        'error_type' => get_class($exception),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ]);
    exit;
});

try {
    require_once __DIR__ . '/auto-config.php';
    require_once __DIR__ . '/init-directories.php';
    
    // Force high limits for image uploads
    @ini_set('memory_limit', '2048M');
    @ini_set('max_execution_time', '600');
    @ini_set('upload_max_filesize', '200M');
    @ini_set('post_max_size', '500M');
    @ini_set('max_file_uploads', '500');

    define('APP_INIT', true);
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/image-optimizer.php';
    
    // Clear any output buffer
    ob_end_clean();
    
    // Set JSON header
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];

    // Check if GD extension is available
    if (!extension_loaded('gd')) {
        throw new Exception('GD extension is not installed. Please install php-gd to enable image processing.');
    }
    
    // Check required GD functions
    $requiredFunctions = ['imagecreatefromjpeg', 'imagecreatefrompng', 'imagecreatefromgif', 'imagecreatetruecolor'];
    foreach ($requiredFunctions as $func) {
        if (!function_exists($func)) {
            throw new Exception("Required GD function '$func' is not available. Please check your PHP GD installation.");
        }
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    if (empty($_FILES['image'])) {
        throw new Exception('No image uploaded');
    }
    
    $file = $_FILES['image'];
    $fieldName = $_POST['field_name'] ?? 'unknown';
    $draftId = $_POST['draft_id'] ?? uniqid('draft_', true);
    $step = $_POST['step'] ?? 'unknown';
    $userId = $_POST['user_id'] ?? 'guest';
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }
    
    // Validate size (20MB max before compression)
    if ($file['size'] > 20 * 1024 * 1024) {
        throw new Exception('File size exceeds 20MB limit');
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate extension
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, WebP allowed.');
    }
    
    // Validate mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
        throw new Exception('Invalid image file');
    }
    
    // Get drafts directory using DirectoryManager
    $draftDir = DirectoryManager::getAbsolutePath('uploads/drafts') . DIRECTORY_SEPARATOR;
    
    // Generate unique filename with proper prefix
    $timestamp = time();
    $random = substr(md5(uniqid()), 0, 8);
    $slug = preg_replace('/[^a-zA-Z0-9_.-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    $slug = substr($slug, 0, 50); // Limit length
    
    // Use field name as prefix for better organization
    $fieldPrefix = preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
    $uniqueName = "{$fieldPrefix}_{$timestamp}_{$random}.jpg"; // Always save as JPG
    $targetPath = $draftDir . $uniqueName;
    
    // Move uploaded file temporarily
    $tempPath = $file['tmp_name'];
    
    if (!file_exists($tempPath)) {
        throw new Exception('Uploaded file not found in temporary location');
    }
    
    // Simply move the uploaded file (no compression during upload)
    // Compression will happen during PDF generation
    if (!move_uploaded_file($tempPath, $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Verify file was saved
    if (!file_exists($targetPath)) {
        throw new Exception('File was not saved successfully');
    }
    
    // Get image dimensions and checksum
    $imageInfo = @getimagesize($targetPath);
    if ($imageInfo === false) {
        error_log('Warning: Could not get image size for: ' . $targetPath);
        $imageInfo = [0, 0]; // Default dimensions
    }
    
    $checksum = hash_file('sha256', $targetPath);
    
    // Create thumbnail (300px) using GD directly - no extra compressed files
    $thumbPath = $draftDir . "thumb_{$uniqueName}";
    try {
        $imageInfo = @getimagesize($targetPath);
        if ($imageInfo !== false) {
            list($width, $height) = $imageInfo;
            $mimeType = $imageInfo['mime'];
            
            // Create image resource
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $source = @imagecreatefromjpeg($targetPath);
                    break;
                case 'image/png':
                    $source = @imagecreatefrompng($targetPath);
                    break;
                case 'image/gif':
                    $source = @imagecreatefromgif($targetPath);
                    break;
                default:
                    $source = false;
            }
            
            if ($source) {
                // Calculate thumbnail dimensions
                $thumbWidth = 300;
                $thumbHeight = (int)($height * ($thumbWidth / $width));
                
                // Create thumbnail
                $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                
                // Save thumbnail
                imagejpeg($thumb, $thumbPath, 70);
                
                // Clean up
                imagedestroy($source);
                imagedestroy($thumb);
            }
        }
    } catch (Exception $e) {
        error_log('Thumbnail creation failed (non-critical): ' . $e->getMessage());
        // Thumbnail creation is optional, continue without it
    }
    
    // Update draft JSON with absolute path
    $draftFile = $draftDir . $draftId . '.json';
    $draftData = [];
    
    if (file_exists($draftFile)) {
        $draftData = json_decode(file_get_contents($draftFile), true) ?: [];
    } else {
        $draftData = [
            'draft_id' => $draftId,
            'version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'owner_id' => $userId,
            'archived' => false,
            'current_step' => $step,
            'form_data' => [],
            'uploaded_files' => []
        ];
    }
    
    // CLEANUP: Delete old image if this field already has one (user is replacing)
    $oldImageDeleted = false;
    if (isset($draftData['uploaded_files'][$fieldName])) {
        $oldFilePath = $draftData['uploaded_files'][$fieldName];
        
        // Try to get absolute path of old file
        if (file_exists($oldFilePath)) {
            $oldAbsolutePath = $oldFilePath;
        } else {
            $oldAbsolutePath = DirectoryManager::getAbsolutePath($oldFilePath);
        }
        
        // Delete old file if it exists and is different from new file
        if (file_exists($oldAbsolutePath) && $oldAbsolutePath !== $targetPath) {
            if (@unlink($oldAbsolutePath)) {
                $oldImageDeleted = true;
                error_log("Deleted replaced image: $oldAbsolutePath");
                
                // Also delete thumbnail if exists
                $oldThumbPath = dirname($oldAbsolutePath) . DIRECTORY_SEPARATOR . 'thumb_' . basename($oldAbsolutePath);
                if (file_exists($oldThumbPath)) {
                    @unlink($oldThumbPath);
                    error_log("Deleted replaced thumbnail: $oldThumbPath");
                }
            } else {
                error_log("Failed to delete old image: $oldAbsolutePath");
            }
        }
    }
    
    // Add file to uploaded_files - STORE RELATIVE WEB PATH, NOT ABSOLUTE
    $relativePath = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($targetPath));
    $draftData['uploaded_files'][$fieldName] = $relativePath;
    $draftData['updated_at'] = $timestamp;
    $draftData['version'] = ($draftData['version'] ?? 0) + 1;
    
    // Save draft
    if (file_put_contents($draftFile, json_encode($draftData, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Failed to save draft data');
    }
    
    // Convert absolute paths to relative for response
    $relativePath = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($targetPath));
    
    // Log to audit trail
    $auditDir = DirectoryManager::getAbsolutePath('drafts/audit');
    $auditLog = $auditDir . DIRECTORY_SEPARATOR . "{$draftId}.log";
    $auditEntry = date('Y-m-d H:i:s') . " - Image uploaded: $fieldName -> $relativePath";
    if ($oldImageDeleted) {
        $auditEntry .= " (replaced old image)";
    }
    $auditEntry .= "\n";
    @file_put_contents($auditLog, $auditEntry, FILE_APPEND);
    
    $relativeThumbPath = $relativePath;
    if (file_exists($thumbPath)) {
        $relativeThumbPath = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($thumbPath));
    }
    
    $response['success'] = true;
    $response['message'] = 'Image uploaded and compressed successfully';
    $response['file_path'] = $relativePath; // For backward compatibility
    $response['path'] = $relativePath;
    $response['thumb_path'] = $relativeThumbPath;
    $response['checksum'] = $checksum;
    $response['size'] = filesize($targetPath);
    $response['width'] = $imageInfo[0] ?? 0;
    $response['height'] = $imageInfo[1] ?? 0;
    $response['old_image_deleted'] = $oldImageDeleted;
    $response['draft_id'] = $draftId;
    $response['version'] = $draftData['version'];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['error_type'] = get_class($e);
    error_log('Image upload error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
} catch (Error $e) {
    $response['success'] = false;
    $response['message'] = 'PHP Error: ' . $e->getMessage();
    $response['error_type'] = get_class($e);
    error_log('Image upload PHP error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

// Ensure we always output valid JSON
echo json_encode($response);
exit;

