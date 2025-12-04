<?php
/**
 * Folder Structure Cleanup Script
 * 
 * Removes unused and duplicate folders to optimize the project structure
 * 
 * Usage: php cleanup-folder-structure.php [--dry-run] [--force]
 */

$dryRun = in_array('--dry-run', $argv ?? []);
$force = in_array('--force', $argv ?? []);

echo "=== Folder Structure Cleanup ===\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No changes will be made\n\n";
}

// Folders to remove
$foldersToRemove = [
    'drafts/logs' => 'Duplicate of /logs',
    'drafts/pdfs' => 'Duplicate of /pdfs',
    'drafts/uploads' => 'Duplicate of /uploads',
    'uploads/compressed' => 'Not used (empty)',
    'uploads/uniform' => 'Not used (empty)',
    'templates' => 'Empty, not referenced'
];

// Folders to keep (for verification)
$foldersToKeep = [
    'drafts/audit' => 'Audit logs',
    'logs' => 'Application logs',
    'pdfs' => 'Generated PDFs',
    'scripts' => 'Utility scripts',
    'tmp/mpdf' => 'mPDF temporary files',
    'uploads/drafts' => 'Draft JSON and images',
    'uploads/drafts/compressed' => 'Compressed images',
    'uploads/drafts/uniform' => 'Uniform-sized images'
];

$stats = [
    'checked' => 0,
    'removed' => 0,
    'skipped' => 0,
    'errors' => 0,
    'files_found' => 0
];

// Step 1: Verify folders to keep exist
echo "Step 1: Verifying essential folders...\n";
foreach ($foldersToKeep as $folder => $purpose) {
    $stats['checked']++;
    $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
    
    if (file_exists($path)) {
        echo "  ✓ $folder - $purpose\n";
    } else {
        echo "  ⚠ WARNING: $folder does not exist! ($purpose)\n";
    }
}

echo "\n";

// Step 2: Check folders to remove for any files
echo "Step 2: Checking folders for files before removal...\n";
foreach ($foldersToRemove as $folder => $reason) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
    
    if (!file_exists($path)) {
        echo "  ⊘ $folder - Already removed\n";
        $stats['skipped']++;
        continue;
    }
    
    // Count files in directory
    $fileCount = countFilesRecursive($path);
    $stats['files_found'] += $fileCount;
    
    if ($fileCount > 0) {
        echo "  ⚠ $folder - Contains $fileCount file(s) - $reason\n";
        
        if (!$force) {
            echo "    → Use --force to delete folders with files\n";
            $stats['skipped']++;
            continue;
        }
    } else {
        echo "  ✓ $folder - Empty - $reason\n";
    }
}

echo "\n";

// Step 3: Remove folders
if (!$dryRun) {
    echo "Step 3: Removing unused folders...\n";
    
    foreach ($foldersToRemove as $folder => $reason) {
        $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
        
        if (!file_exists($path)) {
            continue;
        }
        
        // Count files
        $fileCount = countFilesRecursive($path);
        
        if ($fileCount > 0 && !$force) {
            echo "  ⊘ Skipped $folder (contains files, use --force)\n";
            $stats['skipped']++;
            continue;
        }
        
        // Remove directory
        if (removeDirectory($path)) {
            echo "  ✓ Removed $folder\n";
            $stats['removed']++;
        } else {
            echo "  ✗ Failed to remove $folder\n";
            $stats['errors']++;
        }
    }
} else {
    echo "Step 3: Would remove the following folders:\n";
    
    foreach ($foldersToRemove as $folder => $reason) {
        $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
        
        if (!file_exists($path)) {
            continue;
        }
        
        $fileCount = countFilesRecursive($path);
        
        if ($fileCount > 0 && !$force) {
            echo "  ⊘ Would skip $folder (contains $fileCount files)\n";
        } else {
            echo "  ✓ Would remove $folder\n";
        }
    }
}

echo "\n";

// Step 4: Summary
echo "=== Summary ===\n";
echo "Folders checked: {$stats['checked']}\n";
echo "Folders removed: {$stats['removed']}\n";
echo "Folders skipped: {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";
echo "Files found in folders: {$stats['files_found']}\n";

if ($dryRun) {
    echo "\n💡 This was a dry run. Run without --dry-run to actually remove folders.\n";
}

if ($stats['files_found'] > 0 && !$force) {
    echo "\n⚠️  Some folders contain files. Use --force to delete them anyway.\n";
}

if ($stats['removed'] > 0) {
    echo "\n✅ Cleanup complete! Folder structure optimized.\n";
    echo "\n📝 Next steps:\n";
    echo "  1. Update init-directories.php (remove unused folder references)\n";
    echo "  2. Test all functionality (upload, save, load, generate PDF)\n";
    echo "  3. Check logs for any errors\n";
}

echo "\n";

/**
 * Count files recursively in a directory
 */
function countFilesRecursive($dir) {
    if (!is_dir($dir)) {
        return 0;
    }
    
    $count = 0;
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            $count += countFilesRecursive($path);
        } else {
            // Skip .gitkeep files
            if ($item !== '.gitkeep') {
                $count++;
            }
        }
    }
    
    return $count;
}

/**
 * Remove directory recursively
 */
function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            if (!removeDirectory($path)) {
                return false;
            }
        } else {
            if (!@unlink($path)) {
                return false;
            }
        }
    }
    
    return @rmdir($dir);
}
