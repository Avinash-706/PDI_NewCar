<?php
/**
 * Cleanup Orphaned Images
 * Finds and deletes images in drafts folder that are not referenced in any draft JSON
 */

require_once 'auto-config.php';
require_once 'init-directories.php';
define('APP_INIT', true);
require_once 'config.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'stats' => [
        'total_images' => 0,
        'referenced_images' => 0,
        'orphaned_images' => 0,
        'deleted_images' => 0,
        'failed_deletions' => 0,
        'space_freed' => 0
    ],
    'orphaned_files' => []
];

try {
    $draftDir = DirectoryManager::getAbsolutePath('uploads/drafts') . DIRECTORY_SEPARATOR;
    
    if (!is_dir($draftDir)) {
        throw new Exception('Drafts directory not found');
    }
    
    // Get all image files in drafts directory
    $imageFiles = glob($draftDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    $response['stats']['total_images'] = count($imageFiles);
    
    // Get all draft JSON files
    $draftFiles = glob($draftDir . '*.json');
    
    // Build list of referenced images
    $referencedImages = [];
    foreach ($draftFiles as $draftFile) {
        $draftData = json_decode(file_get_contents($draftFile), true);
        if ($draftData && isset($draftData['uploaded_files'])) {
            foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
                // Convert to absolute path
                if (file_exists($filePath)) {
                    $absolutePath = $filePath;
                } else {
                    $absolutePath = DirectoryManager::getAbsolutePath($filePath);
                }
                
                // Normalize path for comparison
                $normalizedPath = realpath($absolutePath);
                if ($normalizedPath) {
                    $referencedImages[$normalizedPath] = true;
                }
            }
        }
    }
    
    $response['stats']['referenced_images'] = count($referencedImages);
    
    // Find orphaned images
    $orphanedImages = [];
    foreach ($imageFiles as $imageFile) {
        $normalizedPath = realpath($imageFile);
        
        // Skip thumbnails (they're handled separately)
        if (strpos(basename($imageFile), 'thumb_') === 0) {
            continue;
        }
        
        if (!isset($referencedImages[$normalizedPath])) {
            $orphanedImages[] = $imageFile;
        }
    }
    
    $response['stats']['orphaned_images'] = count($orphanedImages);
    
    // Delete orphaned images (if dry_run is not set)
    $dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] === 'true';
    
    foreach ($orphanedImages as $orphanedImage) {
        $fileSize = filesize($orphanedImage);
        $fileName = basename($orphanedImage);
        
        $response['orphaned_files'][] = [
            'name' => $fileName,
            'size' => $fileSize,
            'size_formatted' => formatBytes($fileSize),
            'path' => $orphanedImage,
            'modified' => date('Y-m-d H:i:s', filemtime($orphanedImage))
        ];
        
        if (!$dryRun) {
            if (@unlink($orphanedImage)) {
                $response['stats']['deleted_images']++;
                $response['stats']['space_freed'] += $fileSize;
                
                // Also delete thumbnail if exists
                $thumbPath = dirname($orphanedImage) . DIRECTORY_SEPARATOR . 'thumb_' . basename($orphanedImage);
                if (file_exists($thumbPath)) {
                    $thumbSize = filesize($thumbPath);
                    if (@unlink($thumbPath)) {
                        $response['stats']['space_freed'] += $thumbSize;
                    }
                }
                
                error_log("Deleted orphaned image: $orphanedImage");
            } else {
                $response['stats']['failed_deletions']++;
                error_log("Failed to delete orphaned image: $orphanedImage");
            }
        }
    }
    
    $response['success'] = true;
    $response['message'] = $dryRun 
        ? 'Dry run completed. No files were deleted.' 
        : 'Cleanup completed successfully.';
    $response['dry_run'] = $dryRun;
    $response['stats']['space_freed_formatted'] = formatBytes($response['stats']['space_freed']);
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Orphaned image cleanup error: ' . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT);

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
