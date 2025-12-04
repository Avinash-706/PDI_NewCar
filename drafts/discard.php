<?php
/**
 * Discard Draft - Complete Cleanup System
 * POST /drafts/discard.php
 * 
 * Deletes draft JSON and ALL associated images
 * Ensures complete cleanup with no orphaned files
 */

require_once __DIR__ . '/../auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../init-directories.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $draftId = $_POST['draft_id'] ?? $_GET['draft_id'] ?? null;
    
    if (!$draftId) {
        throw new Exception('Draft ID required');
    }
    
    // Use DirectoryManager for consistent path handling
    $draftFile = DirectoryManager::getAbsolutePath('uploads/drafts/' . $draftId . '.json');
    
    if (!file_exists($draftFile)) {
        // Already deleted, return success
        $response['success'] = true;
        $response['message'] = 'Draft already deleted';
        echo json_encode($response);
        exit;
    }
    
    // Load draft to get image paths
    $draftData = json_decode(file_get_contents($draftFile), true);
    
    if (!$draftData) {
        throw new Exception('Invalid draft data');
    }
    
    $deletedImages = 0;
    $deletedFiles = 0;
    $errors = [];
    
    // Delete all uploaded images
    if (isset($draftData['uploaded_files']) && is_array($draftData['uploaded_files'])) {
        foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
            if (empty($filePath)) continue;
            
            // Try multiple path resolution strategies
            $pathsToTry = [
                $filePath, // Original path
                DirectoryManager::getAbsolutePath($filePath), // Via DirectoryManager
                __DIR__ . '/../' . $filePath, // Relative from project root
                __DIR__ . '/../' . ltrim($filePath, '/') // Remove leading slash
            ];
            
            $deleted = false;
            foreach ($pathsToTry as $tryPath) {
                if (file_exists($tryPath)) {
                    if (@unlink($tryPath)) {
                        $deletedImages++;
                        $deleted = true;
                        error_log("Deleted draft image: $tryPath");
                        
                        // Also delete thumbnail if exists
                        $thumbPath = dirname($tryPath) . DIRECTORY_SEPARATOR . 'thumb_' . basename($tryPath);
                        if (file_exists($thumbPath)) {
                            @unlink($thumbPath);
                            error_log("Deleted draft thumbnail: $thumbPath");
                        }
                        
                        // Delete optimized versions
                        $optimizedPath = dirname($tryPath) . DIRECTORY_SEPARATOR . 'optimized_' . basename($tryPath);
                        if (file_exists($optimizedPath)) {
                            @unlink($optimizedPath);
                        }
                        
                        break;
                    }
                }
            }
            
            if (!$deleted) {
                $errors[] = "Image not found: $filePath";
                error_log("Draft image not found for deletion: $filePath");
            }
        }
    }
    
    // Delete all version files
    $draftDir = dirname($draftFile);
    $draftBasename = basename($draftFile, '.json');
    $versionPattern = $draftDir . DIRECTORY_SEPARATOR . $draftBasename . '.v*.json';
    $versionFiles = glob($versionPattern);
    
    if ($versionFiles) {
        foreach ($versionFiles as $versionFile) {
            if (@unlink($versionFile)) {
                $deletedFiles++;
                error_log("Deleted version file: $versionFile");
            }
        }
    }
    
    // Delete backup files
    $backupFile = $draftDir . DIRECTORY_SEPARATOR . 'backup_' . basename($draftFile);
    if (file_exists($backupFile)) {
        if (@unlink($backupFile)) {
            $deletedFiles++;
            error_log("Deleted backup file: $backupFile");
        }
    }
    
    // Delete audit log
    $auditFile = DirectoryManager::getAbsolutePath('drafts/audit/' . $draftId . '.log');
    if (file_exists($auditFile)) {
        @unlink($auditFile);
        error_log("Deleted audit log: $auditFile");
    }
    
    // Delete draft JSON file
    if (@unlink($draftFile)) {
        $deletedFiles++;
        error_log("Deleted draft file: $draftFile");
    } else {
        throw new Exception('Failed to delete draft file');
    }
    
    // Clean up empty directories
    cleanupEmptyDirectories($draftDir);
    
    // Log the discard action
    error_log("Draft discarded: $draftId - Deleted $deletedImages images and $deletedFiles files");
    
    $response['success'] = true;
    $response['message'] = 'Draft and all associated images deleted successfully';
    $response['deleted_images'] = $deletedImages;
    $response['deleted_files'] = $deletedFiles;
    
    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Draft discard error: ' . $e->getMessage());
}

echo json_encode($response);
exit;

/**
 * Clean up empty directories recursively
 */
function cleanupEmptyDirectories($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    // Don't delete the main drafts directory
    if (basename($dir) === 'drafts') return;
    
    if (empty($files)) {
        @rmdir($dir);
        error_log("Removed empty directory: $dir");
    }
}
