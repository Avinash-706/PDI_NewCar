<?php
/**
 * Delete Draft Handler
 * Removes draft and associated uploaded images
 */

// Auto-configure PHP settings
require_once 'auto-config.php';
require_once 'init-directories.php';

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

$response = ['success' => false, 'message' => ''];

try {
    $draftId = $_GET['draft_id'] ?? '';
    
    if (empty($draftId)) {
        throw new Exception('Draft ID is required');
    }
    
    $draftFile = DirectoryManager::getAbsolutePath('uploads/drafts/' . $draftId . '.json');
    
    if (file_exists($draftFile)) {
        // Load draft to get file paths
        $draftData = json_decode(file_get_contents($draftFile), true);
        
        // Delete uploaded files
        if (isset($draftData['uploaded_files'])) {
            foreach ($draftData['uploaded_files'] as $filePath) {
                $absolutePath = DirectoryManager::getAbsolutePath($filePath);
                if (file_exists($absolutePath)) {
                    unlink($absolutePath);
                }
            }
        }
        
        // Delete draft file
        unlink($draftFile);
        
        $response['success'] = true;
        $response['message'] = 'Draft deleted successfully';
    } else {
        $response['success'] = true;
        $response['message'] = 'Draft not found (already deleted)';
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    if (function_exists('logError')) {
        logError('Draft delete error: ' . $e->getMessage(), $_GET);
    }
}

// Ensure clean JSON output
ob_clean();
echo json_encode($response);
exit;
