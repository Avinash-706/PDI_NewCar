<?php
/**
 * Post-Processing Cleanup After Email
 * 
 * Deletes compressed images and PDF file ONLY after:
 * 1. Successful form submission
 * 2. Successful PDF generation
 * 3. Successful SMTP email delivery
 * 
 * This keeps server storage clean by removing heavy files after they're emailed
 */

require_once __DIR__ . '/auto-config.php';
require_once __DIR__ . '/init-directories.php';

/**
 * Clean up PDF and compressed images after successful email delivery
 * 
 * @param string $pdfPath Path to the generated PDF file
 * @param array $formData Form data (for logging purposes)
 * @return array Result with success status and statistics
 */
function cleanupAfterEmail($pdfPath, $formData = []) {
    error_log("cleanupAfterEmail() CALLED with PDF: $pdfPath");
    
    $result = [
        'success' => false,
        'deleted_pdf' => false,
        'deleted_compressed' => 0,
        'deleted_uniform' => 0,
        'deleted_temp' => 0,
        'freed_space' => 0,
        'message' => ''
    ];
    
    // Track files to delete for rollback capability
    $filesToDelete = [];
    $deletedFiles = [];
    
    try {
        $bookingId = $formData['booking_id'] ?? 'unknown';
        error_log("Post-Email Cleanup: Starting cleanup for booking: $bookingId");
        error_log("Post-Email Cleanup: PDF path to delete: $pdfPath");
        
        // Validate PDF path
        if (empty($pdfPath)) {
            throw new Exception('PDF path is empty');
        }
        
        // Convert to absolute path if needed
        if (!file_exists($pdfPath)) {
            $pdfPath = DirectoryManager::getAbsolutePath($pdfPath);
        }
        
        if (!file_exists($pdfPath)) {
            throw new Exception('PDF file not found: ' . $pdfPath);
        }
        
        // PHASE 1: Collect all files to delete (validation phase)
        error_log("Post-Email Cleanup: Phase 1 - Validating files");
        
        // Add PDF to deletion list
        if (file_exists($pdfPath) && is_readable($pdfPath)) {
            $filesToDelete[] = [
                'path' => $pdfPath,
                'size' => filesize($pdfPath),
                'type' => 'pdf'
            ];
        } else {
            throw new Exception('PDF file not accessible: ' . $pdfPath);
        }
        
        // Collect compressed images
        $compressedDir = DirectoryManager::getAbsolutePath('uploads/drafts/compressed');
        if (is_dir($compressedDir)) {
            $compressedFiles = collectFilesFromDirectory($compressedDir);
            foreach ($compressedFiles as $file) {
                $filesToDelete[] = [
                    'path' => $file['path'],
                    'size' => $file['size'],
                    'type' => 'compressed'
                ];
            }
        }
        
        // Collect uniform images (check both possible locations)
        $uniformDir = DirectoryManager::getAbsolutePath('uploads/drafts/uniform');
        if (is_dir($uniformDir)) {
            $uniformFiles = collectFilesFromDirectory($uniformDir);
            foreach ($uniformFiles as $file) {
                $filesToDelete[] = [
                    'path' => $file['path'],
                    'size' => $file['size'],
                    'type' => 'uniform'
                ];
            }
        }
        
        // Also check compressed/uniform (wrong location but may exist)
        $uniformDirAlt = DirectoryManager::getAbsolutePath('uploads/drafts/compressed/uniform');
        if (is_dir($uniformDirAlt)) {
            $uniformFiles = collectFilesFromDirectory($uniformDirAlt);
            foreach ($uniformFiles as $file) {
                $filesToDelete[] = [
                    'path' => $file['path'],
                    'size' => $file['size'],
                    'type' => 'uniform'
                ];
            }
        }
        
        // Collect temporary files
        $tmpDir = DirectoryManager::getAbsolutePath('tmp/mpdf');
        if (is_dir($tmpDir)) {
            $tmpFiles = collectFilesFromDirectory($tmpDir);
            foreach ($tmpFiles as $file) {
                $filesToDelete[] = [
                    'path' => $file['path'],
                    'size' => $file['size'],
                    'type' => 'temp'
                ];
            }
        }
        
        error_log("Post-Email Cleanup: Found " . count($filesToDelete) . " files to delete");
        
        // PHASE 2: Validate all files are accessible and writable
        error_log("Post-Email Cleanup: Phase 2 - Checking permissions");
        
        foreach ($filesToDelete as $file) {
            if (!file_exists($file['path'])) {
                throw new Exception('File disappeared before deletion: ' . $file['path']);
            }
            
            if (!is_writable($file['path'])) {
                throw new Exception('File not writable: ' . $file['path']);
            }
        }
        
        // PHASE 3: Delete all files (atomic operation)
        error_log("Post-Email Cleanup: Phase 3 - Deleting files");
        
        foreach ($filesToDelete as $file) {
            if (@unlink($file['path'])) {
                $deletedFiles[] = $file;
                $result['freed_space'] += $file['size'];
                
                // Track by type
                switch ($file['type']) {
                    case 'pdf':
                        $result['deleted_pdf'] = true;
                        break;
                    case 'compressed':
                        $result['deleted_compressed']++;
                        break;
                    case 'uniform':
                        $result['deleted_uniform']++;
                        break;
                    case 'temp':
                        $result['deleted_temp']++;
                        break;
                }
                
                error_log("Post-Email Cleanup: Deleted {$file['type']}: {$file['path']} (" . formatBytes($file['size']) . ")");
            } else {
                // Deletion failed - this is critical
                throw new Exception('Failed to delete file: ' . $file['path']);
            }
        }
        
        // PHASE 4: Clean up empty directories
        error_log("Post-Email Cleanup: Phase 4 - Cleaning empty directories");
        
        if (isset($compressedDir)) {
            cleanupEmptySubdirectories($compressedDir);
        }
        if (isset($uniformDir)) {
            cleanupEmptySubdirectories($uniformDir);
        }
        if (isset($tmpDir)) {
            cleanupEmptySubdirectories($tmpDir);
        }
        
        // Success
        $result['success'] = true;
        $result['message'] = 'Post-email cleanup completed successfully';
        
        error_log("Post-Email Cleanup: Successfully cleaned up for booking $bookingId - " .
                 "PDF: " . ($result['deleted_pdf'] ? 'Yes' : 'No') . ", " .
                 "Compressed: {$result['deleted_compressed']}, " .
                 "Uniform: {$result['deleted_uniform']}, " .
                 "Temp: {$result['deleted_temp']}, " .
                 "Freed: " . formatBytes($result['freed_space']));
        
    } catch (Exception $e) {
        // ERROR OCCURRED - Log and return failure
        $result['success'] = false;
        $result['message'] = 'Post-email cleanup error: ' . $e->getMessage();
        error_log('Post-Email Cleanup ERROR: ' . $e->getMessage());
        error_log('Post-Email Cleanup: Cleanup aborted - ' . count($deletedFiles) . ' files deleted before error, ' . 
                 (count($filesToDelete) - count($deletedFiles)) . ' files remain');
        
        // Note: We don't rollback deleted files because:
        // 1. We can't restore them
        // 2. Partial cleanup is better than no cleanup
        // 3. Files are already emailed, so they're backed up
        // 4. Next cleanup will handle remaining files
    }
    
    return $result;
}

/**
 * Collect all files from a directory (non-recursive for safety)
 */
function collectFilesFromDirectory($dir) {
    $files = [];
    
    if (!is_dir($dir)) {
        return $files;
    }
    
    try {
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            // Only collect files, not subdirectories (for safety)
            if (is_file($path)) {
                $files[] = [
                    'path' => $path,
                    'size' => filesize($path)
                ];
            }
        }
    } catch (Exception $e) {
        error_log('Error collecting files from directory: ' . $e->getMessage());
    }
    
    return $files;
}

/**
 * Clean up empty subdirectories (but keep the main directory)
 */
function cleanupEmptySubdirectories($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    try {
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                // Check if directory is empty (only .gitkeep allowed)
                $subItems = array_diff(scandir($path), ['.', '..', '.gitkeep']);
                
                if (empty($subItems)) {
                    @rmdir($path);
                    error_log("Post-Email Cleanup: Removed empty directory: $path");
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error cleaning empty subdirectories: ' . $e->getMessage());
    }
}

/**
 * Delete all contents of a directory (DEPRECATED - kept for compatibility)
 * Use collectFilesFromDirectory() and atomic deletion instead
 * 
 * @param string $dir Directory path
 * @param bool $deleteDir Whether to delete the directory itself
 * @return array Statistics (files deleted, space freed)
 */
function deleteDirectoryContents($dir, $deleteDir = false) {
    $stats = [
        'files' => 0,
        'space' => 0,
        'errors' => []
    ];
    
    if (!is_dir($dir)) {
        return $stats;
    }
    
    try {
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                // Recursively delete subdirectory
                $subStats = deleteDirectoryContents($path, true);
                $stats['files'] += $subStats['files'];
                $stats['space'] += $subStats['space'];
                $stats['errors'] = array_merge($stats['errors'], $subStats['errors']);
            } else {
                // Validate file before deletion
                if (!file_exists($path)) {
                    $stats['errors'][] = "File disappeared: $path";
                    continue;
                }
                
                if (!is_writable($path)) {
                    $stats['errors'][] = "File not writable: $path";
                    continue;
                }
                
                // Delete file
                $fileSize = filesize($path);
                if (@unlink($path)) {
                    $stats['files']++;
                    $stats['space'] += $fileSize;
                } else {
                    $stats['errors'][] = "Failed to delete: $path";
                }
            }
        }
        
        // Delete the directory itself if requested
        if ($deleteDir) {
            // Check if directory is empty (only .gitkeep allowed)
            $remainingItems = array_diff(scandir($dir), ['.', '..', '.gitkeep']);
            if (empty($remainingItems)) {
                @rmdir($dir);
            }
        }
        
        // Log errors if any
        if (!empty($stats['errors'])) {
            foreach ($stats['errors'] as $error) {
                error_log("Post-Email Cleanup: $error");
            }
        }
        
    } catch (Exception $e) {
        error_log('Error deleting directory contents: ' . $e->getMessage());
        $stats['errors'][] = $e->getMessage();
    }
    
    return $stats;
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

/**
 * Clean up specific compressed/uniform images for a draft
 * (Alternative function for more targeted cleanup)
 * 
 * @param string $draftId Draft ID
 * @return array Result with statistics
 */
function cleanupDraftProcessingFiles($draftId) {
    $result = [
        'success' => false,
        'deleted_files' => 0,
        'freed_space' => 0,
        'message' => ''
    ];
    
    try {
        if (empty($draftId)) {
            throw new Exception('Draft ID is required');
        }
        
        // Get draft directory
        $draftDir = DirectoryManager::getAbsolutePath('uploads/drafts');
        
        // Delete compressed images for this draft
        $compressedDir = $draftDir . DIRECTORY_SEPARATOR . 'compressed';
        if (is_dir($compressedDir)) {
            $files = glob($compressedDir . DIRECTORY_SEPARATOR . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $fileSize = filesize($file);
                    if (@unlink($file)) {
                        $result['deleted_files']++;
                        $result['freed_space'] += $fileSize;
                    }
                }
            }
        }
        
        // Delete uniform images for this draft
        $uniformDir = $draftDir . DIRECTORY_SEPARATOR . 'uniform';
        if (is_dir($uniformDir)) {
            $files = glob($uniformDir . DIRECTORY_SEPARATOR . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $fileSize = filesize($file);
                    if (@unlink($file)) {
                        $result['deleted_files']++;
                        $result['freed_space'] += $fileSize;
                    }
                }
            }
        }
        
        $result['success'] = true;
        $result['message'] = 'Draft processing files cleaned up';
        
        error_log("Post-Email Cleanup: Cleaned up processing files for draft $draftId - " .
                 "Deleted {$result['deleted_files']} files, " .
                 "Freed " . formatBytes($result['freed_space']));
        
    } catch (Exception $e) {
        $result['message'] = 'Error: ' . $e->getMessage();
        error_log('Cleanup draft processing files error: ' . $e->getMessage());
    }
    
    return $result;
}
