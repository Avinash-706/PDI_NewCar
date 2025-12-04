<?php
/**
 * Cleanup After Successful Submission
 * 
 * Automatically deletes draft and all associated images after:
 * - Successful form submission
 * - Successful PDF generation
 * - PDF file saved correctly
 * 
 * This function should ONLY be called after confirming success
 */

require_once __DIR__ . '/auto-config.php';
require_once __DIR__ . '/init-directories.php';

/**
 * Clean up draft and all associated files after successful submission
 * 
 * @param string $draftId Draft ID to clean up
 * @param array $formData Form data (to extract draft_id if not provided)
 * @return array Result with success status and statistics
 */
function cleanupAfterSubmission($draftId = null, $formData = []) {
    $result = [
        'success' => false,
        'deleted_images' => 0,
        'deleted_files' => 0,
        'freed_space' => 0,
        'message' => ''
    ];
    
    try {
        // Try to get draft ID from multiple sources
        if (empty($draftId)) {
            $draftId = $formData['draft_id'] ?? 
                      $_POST['draft_id'] ?? 
                      $_GET['draft_id'] ?? 
                      null;
        }
        
        // If no draft ID, nothing to clean up (direct submission without draft)
        if (empty($draftId)) {
            $result['success'] = true;
            $result['message'] = 'No draft to clean up (direct submission)';
            error_log('Cleanup: No draft ID provided, skipping cleanup');
            return $result;
        }
        
        error_log("Cleanup: Starting cleanup for draft: $draftId");
        
        // Get draft file path
        $draftFile = DirectoryManager::getAbsolutePath('uploads/drafts/' . $draftId . '.json');
        
        // If draft doesn't exist, nothing to clean up
        if (!file_exists($draftFile)) {
            $result['success'] = true;
            $result['message'] = 'Draft already cleaned up or does not exist';
            error_log("Cleanup: Draft file not found: $draftFile");
            return $result;
        }
        
        // Load draft data to get image paths
        $draftData = @json_decode(file_get_contents($draftFile), true);
        
        if (!$draftData) {
            error_log("Cleanup: Invalid draft data for: $draftId");
            throw new Exception('Invalid draft data');
        }
        
        // Delete all uploaded images
        if (isset($draftData['uploaded_files']) && is_array($draftData['uploaded_files'])) {
            foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
                if (empty($filePath)) continue;
                
                // Try multiple path resolution strategies
                $pathsToTry = [
                    $filePath,
                    DirectoryManager::getAbsolutePath($filePath),
                    __DIR__ . '/' . $filePath,
                    __DIR__ . '/' . ltrim($filePath, '/')
                ];
                
                foreach ($pathsToTry as $tryPath) {
                    if (file_exists($tryPath)) {
                        $fileSize = filesize($tryPath);
                        
                        if (@unlink($tryPath)) {
                            $result['deleted_images']++;
                            $result['freed_space'] += $fileSize;
                            error_log("Cleanup: Deleted image: $tryPath");
                            
                            // Delete thumbnail
                            $thumbPath = dirname($tryPath) . DIRECTORY_SEPARATOR . 'thumb_' . basename($tryPath);
                            if (file_exists($thumbPath)) {
                                $result['freed_space'] += filesize($thumbPath);
                                @unlink($thumbPath);
                                error_log("Cleanup: Deleted thumbnail: $thumbPath");
                            }
                            
                            // Delete optimized version
                            $optimizedPath = dirname($tryPath) . DIRECTORY_SEPARATOR . 'optimized_' . basename($tryPath);
                            if (file_exists($optimizedPath)) {
                                $result['freed_space'] += filesize($optimizedPath);
                                @unlink($optimizedPath);
                            }
                            
                            // Delete compressed version
                            $compressedDir = dirname($tryPath) . DIRECTORY_SEPARATOR . 'compressed';
                            $compressedPath = $compressedDir . DIRECTORY_SEPARATOR . 'compressed_' . basename($tryPath);
                            if (file_exists($compressedPath)) {
                                $result['freed_space'] += filesize($compressedPath);
                                @unlink($compressedPath);
                            }
                            
                            // Delete uniform version
                            $uniformDir = dirname($tryPath) . DIRECTORY_SEPARATOR . 'uniform';
                            $uniformFiles = glob($uniformDir . DIRECTORY_SEPARATOR . 'uniform_*_' . basename($tryPath));
                            if ($uniformFiles) {
                                foreach ($uniformFiles as $uniformFile) {
                                    if (file_exists($uniformFile)) {
                                        $result['freed_space'] += filesize($uniformFile);
                                        @unlink($uniformFile);
                                    }
                                }
                            }
                            
                            break;
                        }
                    }
                }
            }
        }
        
        // Delete version files
        $draftDir = dirname($draftFile);
        $draftBasename = basename($draftFile, '.json');
        $versionPattern = $draftDir . DIRECTORY_SEPARATOR . $draftBasename . '.v*.json';
        $versionFiles = glob($versionPattern);
        
        if ($versionFiles) {
            foreach ($versionFiles as $versionFile) {
                if (file_exists($versionFile)) {
                    $result['freed_space'] += filesize($versionFile);
                    if (@unlink($versionFile)) {
                        $result['deleted_files']++;
                        error_log("Cleanup: Deleted version file: $versionFile");
                    }
                }
            }
        }
        
        // Delete backup file
        $backupFile = $draftDir . DIRECTORY_SEPARATOR . 'backup_' . basename($draftFile);
        if (file_exists($backupFile)) {
            $result['freed_space'] += filesize($backupFile);
            if (@unlink($backupFile)) {
                $result['deleted_files']++;
                error_log("Cleanup: Deleted backup file: $backupFile");
            }
        }
        
        // Delete audit log
        $auditFile = DirectoryManager::getAbsolutePath('drafts/audit/' . $draftId . '.log');
        if (file_exists($auditFile)) {
            $result['freed_space'] += filesize($auditFile);
            @unlink($auditFile);
            error_log("Cleanup: Deleted audit log: $auditFile");
        }
        
        // Delete draft JSON file
        if (file_exists($draftFile)) {
            $draftFileSize = filesize($draftFile);
            $result['freed_space'] += $draftFileSize;
            
            // Try to delete with error reporting
            if (unlink($draftFile)) {
                $result['deleted_files']++;
                error_log("Cleanup: Deleted draft file: $draftFile");
                
                // Verify deletion
                if (file_exists($draftFile)) {
                    error_log("Cleanup ERROR: Draft file still exists after deletion: $draftFile");
                }
            } else {
                error_log("Cleanup ERROR: Failed to delete draft file: $draftFile - " . error_get_last()['message']);
            }
        }
        
        // Clean up empty directories
        cleanupEmptyDirectories($draftDir);
        
        // Success
        $result['success'] = true;
        $result['message'] = 'Draft and all associated files cleaned up successfully';
        
        error_log("Cleanup: Successfully cleaned up draft $draftId - " . 
                 "Deleted {$result['deleted_images']} images, {$result['deleted_files']} files, " .
                 "Freed " . formatBytes($result['freed_space']));
        
    } catch (Exception $e) {
        $result['message'] = 'Cleanup error: ' . $e->getMessage();
        error_log('Cleanup error: ' . $e->getMessage());
    }
    
    return $result;
}

/**
 * Clean up empty directories recursively
 */
function cleanupEmptyDirectories($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    // Don't delete the main drafts directory
    if (basename($dir) === 'drafts') return;
    
    // Check if directory is empty (only .gitkeep allowed)
    $nonGitkeepFiles = array_filter($files, function($file) {
        return $file !== '.gitkeep';
    });
    
    if (empty($nonGitkeepFiles)) {
        @rmdir($dir);
        error_log("Cleanup: Removed empty directory: $dir");
    }
}

/**
 * Format bytes to human-readable size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
