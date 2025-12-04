<?php
/**
 * Cleanup Malformed Compressed/Uniform Files
 * 
 * Removes files created with missing directory separator bug:
 * - uploads/compressedcompressed_*.jpg (should be uploads/compressed/compressed_*.jpg)
 * - uploads/uniformuniform_*.jpg (should be uploads/uniform/uniform_*.jpg)
 * - uploads/drafts/compressedcompressed_*.jpg (should be uploads/drafts/compressed/compressed_*.jpg)
 * - uploads/drafts/uniformuniform_*.jpg (should be uploads/drafts/uniform/uniform_*.jpg)
 */

require_once __DIR__ . '/auto-config.php';
require_once __DIR__ . '/init-directories.php';

echo "=== Cleanup Malformed Files ===\n\n";

$stats = [
    'found' => 0,
    'deleted' => 0,
    'freed_space' => 0,
    'errors' => 0
];

// Directories to check
$dirsToCheck = [
    DirectoryManager::getAbsolutePath('uploads'),
    DirectoryManager::getAbsolutePath('uploads/drafts')
];

echo "Scanning for malformed files...\n\n";

foreach ($dirsToCheck as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        
        // Skip directories
        if (is_dir($filePath)) {
            continue;
        }
        
        // Check for malformed filenames
        $isMalformed = false;
        $reason = '';
        
        // Pattern 1: compressedcompressed_*.jpg (missing separator)
        if (strpos($file, 'compressedcompressed_') === 0) {
            $isMalformed = true;
            $reason = 'Missing separator before "compressed_"';
        }
        
        // Pattern 2: uniformuniform_*.jpg (missing separator)
        if (strpos($file, 'uniformuniform_') === 0) {
            $isMalformed = true;
            $reason = 'Missing separator before "uniform_"';
        }
        
        if ($isMalformed) {
            $stats['found']++;
            $fileSize = filesize($filePath);
            
            echo "Found malformed file:\n";
            echo "  Path: $filePath\n";
            echo "  Size: " . formatBytes($fileSize) . "\n";
            echo "  Reason: $reason\n";
            
            // Delete the file
            if (@unlink($filePath)) {
                $stats['deleted']++;
                $stats['freed_space'] += $fileSize;
                echo "  Status: ✓ Deleted\n";
            } else {
                $stats['errors']++;
                echo "  Status: ✗ Failed to delete\n";
            }
            
            echo "\n";
        }
    }
}

// Summary
echo "=== Summary ===\n";
echo "Malformed files found: {$stats['found']}\n";
echo "Files deleted: {$stats['deleted']}\n";
echo "Errors: {$stats['errors']}\n";
echo "Space freed: " . formatBytes($stats['freed_space']) . "\n";

if ($stats['deleted'] > 0) {
    echo "\n✅ Cleanup complete! Malformed files have been removed.\n";
} else if ($stats['found'] === 0) {
    echo "\n✅ No malformed files found. System is clean.\n";
} else {
    echo "\n⚠️  Some files could not be deleted. Check permissions.\n";
}

echo "\n";

// Recommendations
if ($stats['deleted'] > 0) {
    echo "=== Next Steps ===\n";
    echo "1. The bug has been fixed in image-optimizer.php\n";
    echo "2. New files will be created in correct locations:\n";
    echo "   - uploads/compressed/compressed_*.jpg\n";
    echo "   - uploads/uniform/uniform_*.jpg\n";
    echo "   - uploads/drafts/compressed/compressed_*.jpg\n";
    echo "   - uploads/drafts/uniform/uniform_*.jpg\n";
    echo "\n";
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
