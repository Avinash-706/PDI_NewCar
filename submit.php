<?php
/**
 * Main submission handler - Production Ready
 * Receives form data, validates, saves images, generates PDF, and sends email
 */

// Auto-configure PHP settings
require_once 'auto-config.php';
require_once 'init-directories.php';

// Force high memory and time for submission with many images
// CRITICAL: Support for 500+ image uploads
@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', '600');
@ini_set('max_file_uploads', '500');
@ini_set('post_max_size', '500M');
@ini_set('upload_max_filesize', '200M');
@ini_set('max_input_vars', '5000');
@set_time_limit(600);

define('APP_INIT', true);
require_once 'config.php';

// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');

// Response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Log submission details
    error_log("Form submission received. Files count: " . count($_FILES) . ", POST fields: " . count($_POST));
    
    // DEBUG: Log Step 4 checkbox data
    error_log("Step 4 Checkbox Debug:");
    error_log("  registration_certificate: " . (isset($_POST['registration_certificate']) ? print_r($_POST['registration_certificate'], true) : 'NOT SET'));
    error_log("  car_insurance: " . (isset($_POST['car_insurance']) ? print_r($_POST['car_insurance'], true) : 'NOT SET'));
    error_log("  car_finance_noc: " . (isset($_POST['car_finance_noc']) ? print_r($_POST['car_finance_noc'], true) : 'NOT SET'));
    error_log("  car_purchase_invoice: " . (isset($_POST['car_purchase_invoice']) ? print_r($_POST['car_purchase_invoice'], true) : 'NOT SET'));
    error_log("  bifuel_certification: " . (isset($_POST['bifuel_certification']) ? print_r($_POST['bifuel_certification'], true) : 'NOT SET'));
    
    // Validate required fields
    $requiredFields = ['booking_id', 'inspection_delayed'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Required field missing: $field");
        }
    }
    
    // Handle file uploads - Process ALL files
    $uploadedFiles = [];
    $fileCount = 0;
    
    // Handle all file uploads from $_FILES
    if (!empty($_FILES)) {
        foreach ($_FILES as $fieldName => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                try {
                    $uploadedPath = handleFileUpload($file, UPLOAD_DIR);
                    $uploadedFiles[$fieldName . '_path'] = $uploadedPath;
                    $fileCount++;
                } catch (Exception $e) {
                    error_log("Error uploading $fieldName: " . $e->getMessage());
                    // Continue with other files
                }
            } elseif ($file['error'] === UPLOAD_ERR_NO_FILE) {
                // Check if there's a saved draft file for this field
                $existingFileKey = 'existing_' . $fieldName;
                if (isset($_POST[$existingFileKey]) && !empty($_POST[$existingFileKey])) {
                    $draftPath = $_POST[$existingFileKey];
                    if (file_exists($draftPath)) {
                        // Use draft file directly (already uploaded)
                        $uploadedFiles[$fieldName . '_path'] = $draftPath;
                        $fileCount++;
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("File upload error for $fieldName: " . $file['error']);
                // Continue with other files instead of throwing exception
            }
        }
    }
    
    // Also check for existing_ fields in POST (from progressive upload)
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'existing_') === 0 && !empty($value)) {
            $fieldName = str_replace('existing_', '', $key);
            $pathKey = $fieldName . '_path';
            
            // Convert to absolute path and check existence
            $absolutePath = DirectoryManager::getAbsolutePath($value);
            
            // Only add if not already added from $_FILES
            if (!isset($uploadedFiles[$pathKey]) && file_exists($absolutePath)) {
                $uploadedFiles[$pathKey] = $absolutePath;
                $fileCount++;
            }
        }
    }
    
    error_log("Total files processed: $fileCount");
    
    // Prepare data for PDF
    $formData = $_POST;
    
    // Merge uploaded file paths
    $formData = array_merge($formData, $uploadedFiles);
    
    // Log data being sent to PDF
    error_log("Generating PDF with " . count($uploadedFiles) . " images");
    
    // Generate PDF
    require_once 'generate-pdf.php';
    $pdfPath = generatePDF($formData);
    
    if (!$pdfPath || !file_exists($pdfPath)) {
        throw new Exception('Failed to generate PDF');
    }
    
    error_log("PDF generated successfully: $pdfPath");
    
    // ========================================================================
    // PHASE 1: PDF GENERATED SUCCESSFULLY - NOW CLEANUP ALL USER MEDIA
    // ========================================================================
    
    error_log("=== CLEANUP PHASE 1: Starting immediate cleanup after PDF generation ===");
    
    $draftId = $_POST['draft_id'] ?? $formData['draft_id'] ?? null;
    $submissionFiles = $uploadedFiles; // Track files uploaded during submission
    
    // STEP 1: Delete draft JSON and all original uploaded images
    $cleanupStats = [
        'draft_deleted' => false,
        'images_deleted' => 0,
        'compressed_deleted' => 0,
        'uniform_deleted' => 0,
        'thumbnails_deleted' => 0,
        'total_freed' => 0
    ];
    
    if ($draftId) {
        error_log("Cleanup: Deleting draft and all images for draft ID: $draftId");
        require_once 'cleanup-after-submission.php';
        $draftCleanup = cleanupAfterSubmission($draftId, $formData);
        
        if ($draftCleanup['success']) {
            $cleanupStats['draft_deleted'] = true;
            $cleanupStats['images_deleted'] = $draftCleanup['deleted_images'];
            $cleanupStats['total_freed'] += $draftCleanup['freed_space'];
            error_log("Cleanup: Draft deleted - {$draftCleanup['deleted_images']} images, " . 
                     formatBytes($draftCleanup['freed_space']) . " freed");
        } else {
            error_log("Cleanup WARNING: Draft cleanup failed - " . $draftCleanup['message']);
        }
    }
    
    // STEP 2: Delete ALL compressed images from uploads/drafts/compressed/
    $compressedDir = DirectoryManager::getAbsolutePath('uploads/drafts/compressed');
    if (is_dir($compressedDir)) {
        $compressedFiles = glob($compressedDir . DIRECTORY_SEPARATOR . '*');
        foreach ($compressedFiles as $file) {
            if (is_file($file) && basename($file) !== '.gitkeep') {
                $fileSize = @filesize($file);
                if (@unlink($file)) {
                    $cleanupStats['compressed_deleted']++;
                    $cleanupStats['total_freed'] += $fileSize;
                    error_log("Cleanup: Deleted compressed image: " . basename($file));
                }
            }
        }
    }
    
    // STEP 3: Delete ALL uniform images from uploads/drafts/uniform/
    $uniformDir = DirectoryManager::getAbsolutePath('uploads/drafts/uniform');
    if (is_dir($uniformDir)) {
        $uniformFiles = glob($uniformDir . DIRECTORY_SEPARATOR . '*');
        foreach ($uniformFiles as $file) {
            if (is_file($file) && basename($file) !== '.gitkeep') {
                $fileSize = @filesize($file);
                if (@unlink($file)) {
                    $cleanupStats['uniform_deleted']++;
                    $cleanupStats['total_freed'] += $fileSize;
                    error_log("Cleanup: Deleted uniform image: " . basename($file));
                }
            }
        }
    }
    
    // STEP 4: Delete ALL thumbnails from uploads/drafts/
    $draftsDir = DirectoryManager::getAbsolutePath('uploads/drafts');
    $thumbnails = glob($draftsDir . DIRECTORY_SEPARATOR . 'thumb_*');
    foreach ($thumbnails as $thumb) {
        if (is_file($thumb)) {
            $fileSize = @filesize($thumb);
            if (@unlink($thumb)) {
                $cleanupStats['thumbnails_deleted']++;
                $cleanupStats['total_freed'] += $fileSize;
                error_log("Cleanup: Deleted thumbnail: " . basename($thumb));
            }
        }
    }
    
    // STEP 5: Delete any remaining images in uploads/drafts/ (except .gitkeep and .json)
    $remainingImages = glob($draftsDir . DIRECTORY_SEPARATOR . '*');
    foreach ($remainingImages as $file) {
        $basename = basename($file);
        if (is_file($file) && 
            $basename !== '.gitkeep' && 
            !preg_match('/\.json$/', $basename)) {
            $fileSize = @filesize($file);
            if (@unlink($file)) {
                $cleanupStats['images_deleted']++;
                $cleanupStats['total_freed'] += $fileSize;
                error_log("Cleanup: Deleted remaining image: $basename");
            }
        }
    }
    
    error_log("=== CLEANUP PHASE 1 COMPLETE ===");
    error_log("Draft deleted: " . ($cleanupStats['draft_deleted'] ? 'Yes' : 'No'));
    error_log("Images deleted: {$cleanupStats['images_deleted']}");
    error_log("Compressed deleted: {$cleanupStats['compressed_deleted']}");
    error_log("Uniform deleted: {$cleanupStats['uniform_deleted']}");
    error_log("Thumbnails deleted: {$cleanupStats['thumbnails_deleted']}");
    error_log("Total space freed: " . formatBytes($cleanupStats['total_freed']));
    
    // ========================================================================
    // PHASE 2: SEND RESPONSE TO USER (User can start new inspection now)
    // ========================================================================
    
    $response['success'] = true;
    $response['message'] = SUCCESS_MESSAGE;
    $response['pdf_path'] = $pdfPath;
    $response['images_processed'] = $fileCount;
    $response['cleanup_stats'] = $cleanupStats;
    
    // Prepare JSON response
    $jsonResponse = json_encode($response);
    
    // Clear any previous output
    ob_end_clean();
    
    // Send response to user
    echo $jsonResponse;
    
    // Close connection to user but continue script execution
    if (function_exists('fastcgi_finish_request')) {
        // PHP-FPM: Best method - closes connection immediately
        fastcgi_finish_request();
    } else {
        // Apache/Other: Manual connection close
        header('Connection: close');
        header('Content-Length: ' . strlen($jsonResponse));
        ob_end_flush();
        flush();
    }
    
    error_log("=== USER RESPONSE SENT - User can now start new inspection ===");
    
    // ========================================================================
    // PHASE 3: SEND EMAIL IN BACKGROUND (Non-blocking)
    // ========================================================================
    
    error_log("=== SMTP PHASE: Starting email sending in background ===");
    
    try {
        require_once 'send-email.php';
        $emailSent = sendEmail($pdfPath, $formData);
        
        if ($emailSent) {
            error_log('SMTP: Email sent successfully for: ' . $pdfPath);
            
            // OPTIONAL: Delete PDF after successful email (uncomment if needed)
            // if (file_exists($pdfPath)) {
            //     @unlink($pdfPath);
            //     error_log('SMTP: PDF deleted after email: ' . $pdfPath);
            // }
        } else {
            error_log('SMTP WARNING: Email sending failed for: ' . $pdfPath);
        }
    } catch (Exception $emailError) {
        error_log('SMTP ERROR: ' . $emailError->getMessage());
    }
    
    error_log("=== SMTP PHASE COMPLETE ===");
    error_log("=== SUBMISSION WORKFLOW COMPLETE ===");
    
    exit;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['error_details'] = [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log('Submission error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    if (function_exists('logError')) {
        logError('Submission error: ' . $e->getMessage(), $_POST);
    }
} catch (Error $e) {
    $response['message'] = 'PHP Error: ' . $e->getMessage();
    $response['error_details'] = [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log('PHP Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
}

// Clean output and send JSON (only for error cases)
if (!$response['success']) {
    ob_end_clean();
    echo json_encode($response);
}
exit;

/**
 * Format bytes to human-readable size
 */
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

/**
 * Handle file upload with validation
 * IMPORTANT: All uploads during submission should go to uploads/drafts/
 */
function handleFileUpload($file, $uploadDir) {
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file extension (more reliable than MIME type)
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.');
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File size exceeds 15MB limit');
    }
    
    // ALWAYS use uploads/drafts/ directory for submission files
    $draftDir = DirectoryManager::getAbsolutePath('uploads/drafts') . DIRECTORY_SEPARATOR;
    
    // Generate unique filename
    $filename = 'submission_' . time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $draftDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    return $targetPath;
}
?>
