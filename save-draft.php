<?php
/**
 * Save Draft Handler with Image Upload Support
 * Saves all form data including uploaded images
 */

// Auto-configure PHP settings
require_once 'auto-config.php';
require_once 'init-directories.php';

// Force high limits for draft saving with many images
@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', '600');
@ini_set('upload_max_filesize', '200M');
@ini_set('post_max_size', '500M');
@ini_set('max_file_uploads', '500');
@ini_set('max_input_vars', '5000');

// Prevent any output before JSON
ob_start();

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

define('APP_INIT', true);
require_once 'config.php';

// Clear any output that might have occurred
ob_end_clean();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'draft_id' => ''];

try {
    // Get drafts directory using DirectoryManager
    $draftDir = DirectoryManager::getAbsolutePath('uploads/drafts') . DIRECTORY_SEPARATOR;
    
    // Get JSON input
    $jsonInput = file_get_contents('php://input');
    $inputData = json_decode($jsonInput, true);
    
    // Fallback to POST if JSON parsing fails
    if (!$inputData) {
        $inputData = $_POST;
    }
    
    // Generate unique draft ID (or use existing one)
    $draftId = $inputData['draft_id'] ?? uniqid('draft_', true);
    
    // Prepare draft data with proper structure
    $draftData = [
        'draft_id' => $draftId,
        'timestamp' => time(),
        'current_step' => $inputData['current_step'] ?? 1,
        'form_data' => $inputData['form_data'] ?? [],
        'uploaded_files' => $inputData['uploaded_files'] ?? []
    ];
    
    // Load existing draft if it exists to preserve any additional data
    $draftFile = $draftDir . $draftId . '.json';
    if (file_exists($draftFile)) {
        $existingDraft = json_decode(file_get_contents($draftFile), true);
        if ($existingDraft && isset($existingDraft['uploaded_files'])) {
            // Verify existing files still exist before merging
            $verifiedFiles = [];
            foreach ($existingDraft['uploaded_files'] as $fieldName => $filePath) {
                // Handle both absolute and relative paths
                if (file_exists($filePath)) {
                    // Already absolute path
                    $absolutePath = $filePath;
                } else {
                    // Try as relative path
                    $absolutePath = DirectoryManager::getAbsolutePath($filePath);
                }
                
                if (file_exists($absolutePath)) {
                    // Store as relative web path
                    $relativePath = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($absolutePath));
                    $verifiedFiles[$fieldName] = $relativePath;
                } else {
                    error_log("Draft file missing during save: $filePath (tried: $absolutePath)");
                }
            }
            
            // Merge verified existing files with new ones (new ones take precedence)
            $draftData['uploaded_files'] = array_merge(
                $verifiedFiles,
                $draftData['uploaded_files']
            );
        }
    }
    
    // Handle any new file uploads (fallback, but should use upload-image.php instead)
    // REMOVED 20-FILE LIMIT - Now processes ALL uploaded files dynamically
    if (!empty($_FILES)) {
        foreach ($_FILES as $fieldName => $file) {
            // Handle both single file and array of files
            if (is_array($file['error'])) {
                // Multiple files in array format
                $fileCount = count($file['error']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($file['error'][$i] === UPLOAD_ERR_OK) {
                        $extension = strtolower(pathinfo($file['name'][$i], PATHINFO_EXTENSION));
                        $uniqueName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', basename($file['name'][$i]));
                        $targetPath = $draftDir . $uniqueName;
                        
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && $file['size'][$i] <= 20 * 1024 * 1024) {
                            if (move_uploaded_file($file['tmp_name'][$i], $targetPath)) {
                                $actualFieldName = $fieldName . '_' . $i;
                                $draftData['uploaded_files'][$actualFieldName] = $targetPath;
                            }
                        }
                    }
                }
            } else {
                // Single file
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $uniqueName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', basename($file['name']));
                    $targetPath = $draftDir . $uniqueName;
                    
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && $file['size'] <= 20 * 1024 * 1024) {
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $draftData['uploaded_files'][$fieldName] = $targetPath;
                        }
                    }
                }
            }
        }
    }
    
    // Save draft to JSON file
    $draftFile = $draftDir . $draftId . '.json';
    file_put_contents($draftFile, json_encode($draftData, JSON_PRETTY_PRINT));
    
    // Convert all file paths to web-accessible paths for response
    $webAccessibleFiles = [];
    foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
        // Ensure path is web-accessible (relative path starting from web root)
        $webAccessibleFiles[$fieldName] = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($filePath));
    }
    
    $response['success'] = true;
    $response['message'] = 'Draft saved successfully!';
    $response['draft_id'] = $draftId;
    $response['files_saved'] = count($draftData['uploaded_files']);
    $response['draft_data'] = $draftData;
    $response['draft_data']['uploaded_files'] = $webAccessibleFiles; // Override with web paths
    
} catch (Exception $e) {
    $response['message'] = 'Error saving draft: ' . $e->getMessage();
    if (function_exists('logError')) {
        logError('Draft save error: ' . $e->getMessage(), $_POST);
    }
}

// Ensure clean JSON output
ob_clean();
echo json_encode($response);
exit;
