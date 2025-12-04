<?php
/**
 * Auto-Cleanup System for Old Drafts
 * 
 * Automatically deletes drafts older than 3 days (72 hours)
 * along with all their associated images
 * 
 * Can be run:
 * 1. On page load (lightweight check)
 * 2. Via cron job (recommended for production)
 * 3. Manually via direct access
 * 
 * Usage:
 * - Include in index.php: require_once 'drafts/auto-cleanup.php';
 * - Cron: php /path/to/drafts/auto-cleanup.php
 * - Direct: https://yoursite.com/drafts/auto-cleanup.php
 */

// Only run if called directly or explicitly included
if (!defined('AUTO_CLEANUP_ENABLED')) {
    define('AUTO_CLEANUP_ENABLED', true);
}

if (!AUTO_CLEANUP_ENABLED) {
    return;
}

require_once __DIR__ . '/../auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../init-directories.php';

/**
 * Main cleanup function
 * 
 * @param int $maxAge Maximum age in seconds (default: 3 days = 259200 seconds)
 * @param bool $dryRun If true, only report what would be deleted
 * @return array Cleanup statistics
 */
function autoCleanupOldDrafts($maxAge = 259200, $dryRun = false) {
    $stats = [
        'total_drafts' => 0,
        'expired_drafts' => 0,
        'deleted_drafts' => 0,
        'deleted_images' => 0,
        'deleted_files' => 0,
        'freed_space' => 0,
        'errors' => []
    ];
    
    try {
        $draftsDir = DirectoryManager::getAbsolutePath('uploads/drafts');
        
        if (!is_dir($draftsDir)) {
            error_log('Auto-cleanup: Drafts directory not found');
            return $stats;
        }
        
        // Get all draft JSON files
        $draftFiles = glob($draftsDir . DIRECTORY_SEPARATOR . '*.json');
        
        if (!$draftFiles) {
            error_log('Auto-cleanup: No drafts found');
            return $stats;
        }
        
        $stats['total_drafts'] = count($draftFiles);
        $currentTime = time();
        $cutoffTime = $currentTime - $maxAge;
        
        foreach ($draftFiles as $draftFile) {
            // Skip backup and version files
            if (strpos(basename($draftFile), 'backup_') === 0 || 
                preg_match('/\.v\d+\.json$/', $draftFile)) {
                continue;
            }
            
            // Load draft data
            $draftData = @json_decode(file_get_contents($draftFile), true);
            
            if (!$draftData) {
                $stats['errors'][] = "Invalid draft data: " . basename($draftFile);
                continue;
            }
            
            // Check draft age using timestamp or updated_at
            $draftTimestamp = $draftData['timestamp'] ?? 
                             $draftData['updated_at'] ?? 
                             filemtime($draftFile);
            
            // If draft is older than cutoff time, delete it
            if ($draftTimestamp < $cutoffTime) {
                $stats['expired_drafts']++;
                $draftId = $draftData['draft_id'] ?? basename($draftFile, '.json');
                
                $age_days = round(($currentTime - $draftTimestamp) / 86400, 1);
                error_log("Auto-cleanup: Found expired draft: $draftId (age: {$age_days} days)");
                
                if (!$dryRun) {
                    $result = deleteDraftCompletely($draftFile, $draftData);
                    
                    if ($result['success']) {
                        $stats['deleted_drafts']++;
                        $stats['deleted_images'] += $result['deleted_images'];
                        $stats['deleted_files'] += $result['deleted_files'];
                        $stats['freed_space'] += $result['freed_space'];
                    } else {
                        $stats['errors'][] = "Failed to delete: $draftId - " . $result['error'];
                    }
                }
            }
        }
        
        // Clean up empty directories
        if (!$dryRun && $stats['deleted_drafts'] > 0) {
            cleanupEmptyDirectories($draftsDir);
        }
        
        // Log summary
        if ($stats['expired_drafts'] > 0) {
            $mode = $dryRun ? 'DRY RUN' : 'EXECUTED';
            error_log("Auto-cleanup [$mode]: Found {$stats['expired_drafts']} expired drafts out of {$stats['total_drafts']} total");
            
            if (!$dryRun) {
                error_log("Auto-cleanup: Deleted {$stats['deleted_drafts']} drafts, {$stats['deleted_images']} images, freed " . formatBytes($stats['freed_space']));
            }
        }
        
    } catch (Exception $e) {
        $stats['errors'][] = $e->getMessage();
        error_log('Auto-cleanup error: ' . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Delete a draft and all its associated files
 * 
 * @param string $draftFile Path to draft JSON file
 * @param array $draftData Draft data array
 * @return array Result with success status and statistics
 */
function deleteDraftCompletely($draftFile, $draftData) {
    $result = [
        'success' => false,
        'deleted_images' => 0,
        'deleted_files' => 0,
        'freed_space' => 0,
        'error' => ''
    ];
    
    try {
        $draftId = $draftData['draft_id'] ?? basename($draftFile, '.json');
        
        // Delete all uploaded images
        if (isset($draftData['uploaded_files']) && is_array($draftData['uploaded_files'])) {
            foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
                if (empty($filePath)) continue;
                
                // Try multiple path resolution strategies
                $pathsToTry = [
                    $filePath,
                    DirectoryManager::getAbsolutePath($filePath),
                    dirname($draftFile) . DIRECTORY_SEPARATOR . basename($filePath),
                    __DIR__ . '/../' . $filePath,
                    __DIR__ . '/../' . ltrim($filePath, '/')
                ];
                
                foreach ($pathsToTry as $tryPath) {
                    if (file_exists($tryPath)) {
                        $fileSize = filesize($tryPath);
                        
                        if (@unlink($tryPath)) {
                            $result['deleted_images']++;
                            $result['freed_space'] += $fileSize;
                            
                            // Delete thumbnail
                            $thumbPath = dirname($tryPath) . DIRECTORY_SEPARATOR . 'thumb_' . basename($tryPath);
                            if (file_exists($thumbPath)) {
                                $result['freed_space'] += filesize($thumbPath);
                                @unlink($thumbPath);
                            }
                            
                            // Delete optimized version
                            $optimizedPath = dirname($tryPath) . DIRECTORY_SEPARATOR . 'optimized_' . basename($tryPath);
                            if (file_exists($optimizedPath)) {
                                $result['freed_space'] += filesize($optimizedPath);
                                @unlink($optimizedPath);
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
            }
        }
        
        // Delete audit log
        $auditFile = DirectoryManager::getAbsolutePath('drafts/audit/' . $draftId . '.log');
        if (file_exists($auditFile)) {
            $result['freed_space'] += filesize($auditFile);
            @unlink($auditFile);
        }
        
        // Delete draft JSON file
        if (file_exists($draftFile)) {
            $result['freed_space'] += filesize($draftFile);
            if (@unlink($draftFile)) {
                $result['deleted_files']++;
                $result['success'] = true;
            } else {
                $result['error'] = 'Failed to delete draft file';
            }
        }
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
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
    
    if (empty($files)) {
        @rmdir($dir);
        error_log("Auto-cleanup: Removed empty directory: $dir");
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

/**
 * Lightweight check - only runs occasionally
 * Use this for page load integration
 */
function lightweightCleanupCheck() {
    // Only run cleanup 5% of the time (1 in 20 page loads)
    if (rand(1, 20) !== 1) {
        return;
    }
    
    // Run cleanup in background (non-blocking)
    autoCleanupOldDrafts(259200, false); // 3 days
}

// If called directly (not included), run cleanup and output results
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === 'cleanup')) {
    $dryRun = isset($_GET['dry_run']) || (isset($argv[1]) && $argv[1] === '--dry-run');
    $maxAge = isset($_GET['max_age']) ? (int)$_GET['max_age'] : 259200; // 3 days default
    
    $stats = autoCleanupOldDrafts($maxAge, $dryRun);
    
    if (php_sapi_name() === 'cli') {
        // CLI output
        echo "=== Draft Auto-Cleanup " . ($dryRun ? '(DRY RUN)' : '') . " ===\n";
        echo "Total drafts: {$stats['total_drafts']}\n";
        echo "Expired drafts: {$stats['expired_drafts']}\n";
        echo "Deleted drafts: {$stats['deleted_drafts']}\n";
        echo "Deleted images: {$stats['deleted_images']}\n";
        echo "Deleted files: {$stats['deleted_files']}\n";
        echo "Freed space: " . formatBytes($stats['freed_space']) . "\n";
        
        if (!empty($stats['errors'])) {
            echo "\nErrors:\n";
            foreach ($stats['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    } else {
        // Web output
        header('Content-Type: application/json');
        echo json_encode($stats, JSON_PRETTY_PRINT);
    }
    
    exit;
}

// If included in another file, run lightweight check
if (defined('AUTO_CLEANUP_ENABLED') && AUTO_CLEANUP_ENABLED) {
    lightweightCleanupCheck();
}
