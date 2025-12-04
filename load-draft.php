<?php
/**
 * Load Draft Handler
 * Retrieves saved draft including uploaded images
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

$response = ['success' => false, 'message' => '', 'draft_data' => null];

try {
    $draftId = $_GET['draft_id'] ?? '';
    
    if (empty($draftId)) {
        throw new Exception('Draft ID is required');
    }
    
    $draftFile = DirectoryManager::getAbsolutePath('uploads/drafts/' . $draftId . '.json');
    
    if (!file_exists($draftFile)) {
        throw new Exception('Draft not found');
    }
    
    // Load draft data
    $draftData = json_decode(file_get_contents($draftFile), true);
    
    if (!$draftData) {
        throw new Exception('Invalid draft data');
    }
    
    // Verify uploaded files still exist and convert to web paths
    $webAccessibleFiles = [];
    if (isset($draftData['uploaded_files'])) {
        foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
            // Handle both absolute and relative paths
            if (file_exists($filePath)) {
                // Already absolute path
                $absolutePath = $filePath;
            } else {
                // Try as relative path
                $absolutePath = DirectoryManager::getAbsolutePath($filePath);
            }
            
            if (file_exists($absolutePath)) {
                // Convert to web-accessible path
                $webPath = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($absolutePath));
                $webAccessibleFiles[$fieldName] = $webPath;
            } else {
                // Try the path as-is (might already be a web path)
                if (strpos($filePath, 'uploads/') === 0) {
                    // It's already a relative web path, verify it exists
                    $testPath = __DIR__ . '/' . $filePath;
                    if (file_exists($testPath)) {
                        $webAccessibleFiles[$fieldName] = $filePath;
                    } else {
                        error_log("Draft file missing during load: $filePath (tried: $absolutePath, $testPath)");
                    }
                } else {
                    error_log("Draft file missing during load: $filePath (tried: $absolutePath)");
                }
            }
        }
    }
    
    // Update draft data with verified web-accessible paths
    $draftData['uploaded_files'] = $webAccessibleFiles;
    
    $response['success'] = true;
    $response['message'] = 'Draft loaded successfully';
    $response['draft_data'] = $draftData;
    $response['files_loaded'] = count($webAccessibleFiles);
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    if (function_exists('logError')) {
        logError('Draft load error: ' . $e->getMessage(), $_GET);
    }
}

// Ensure clean JSON output
ob_clean();
echo json_encode($response);
exit;
