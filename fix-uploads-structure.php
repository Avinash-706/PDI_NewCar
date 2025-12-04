<?php
/**
 * Fix Uploads Folder Structure
 * Moves files to correct locations and removes wrong directories
 */

require_once __DIR__ . '/init-directories.php';

echo "=== FIXING UPLOADS FOLDER STRUCTURE ===\n\n";

$baseDir = DirectoryManager::getBaseDir();
$moved = 0;
$deleted = 0;
$errors = 0;

// Step 1: Move files from uploads/compressed/uniform/ to uploads/drafts/uniform/
echo "Step 1: Moving files from uploads/compressed/uniform/ to uploads/drafts/uniform/\n";
$wrongUniformDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'compressed' . DIRECTORY_SEPARATOR . 'uniform';
$correctUniformDir = DirectoryManager::getAbsolutePath('uploads/drafts/uniform');

if (is_dir($wrongUniformDir)) {
    $files = glob($wrongUniformDir . DIRECTORY_SEPARATOR . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitkeep') {
            $newPath = $correctUniformDir . DIRECTORY_SEPARATOR . basename($file);
            if (rename($file, $newPath)) {
                echo "  ✓ Moved: " . basename($file) . "\n";
                $moved++;
            } else {
                echo "  ✗ Failed to move: " . basename($file) . "\n";
                $errors++;
            }
        }
    }
}

// Step 2: Move files from uploads/compressed/ to uploads/drafts/compressed/
echo "\nStep 2: Moving files from uploads/compressed/ to uploads/drafts/compressed/\n";
$wrongCompressedDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'compressed';
$correctCompressedDir = DirectoryManager::getAbsolutePath('uploads/drafts/compressed');

if (is_dir($wrongCompressedDir)) {
    $files = glob($wrongCompressedDir . DIRECTORY_SEPARATOR . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitkeep') {
            $newPath = $correctCompressedDir . DIRECTORY_SEPARATOR . basename($file);
            if (rename($file, $newPath)) {
                echo "  ✓ Moved: " . basename($file) . "\n";
                $moved++;
            } else {
                echo "  ✗ Failed to move: " . basename($file) . "\n";
                $errors++;
            }
        }
    }
}

// Step 3: Move files from uploads/ root to uploads/drafts/
echo "\nStep 3: Moving files from uploads/ root to uploads/drafts/\n";
$uploadsRoot = $baseDir . DIRECTORY_SEPARATOR . 'uploads';
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts');

if (is_dir($uploadsRoot)) {
    $files = glob($uploadsRoot . DIRECTORY_SEPARATOR . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitkeep') {
            $newPath = $draftDir . DIRECTORY_SEPARATOR . basename($file);
            if (rename($file, $newPath)) {
                echo "  ✓ Moved: " . basename($file) . "\n";
                $moved++;
            } else {
                echo "  ✗ Failed to move: " . basename($file) . "\n";
                $errors++;
            }
        }
    }
}

// Step 4: Remove empty wrong directories
echo "\nStep 4: Removing empty wrong directories\n";

// Remove uploads/compressed/uniform/
if (is_dir($wrongUniformDir)) {
    $files = glob($wrongUniformDir . DIRECTORY_SEPARATOR . '*');
    $files = array_filter($files, function($f) { return basename($f) !== '.gitkeep'; });
    if (empty($files)) {
        if (rmdir($wrongUniformDir)) {
            echo "  ✓ Removed: uploads/compressed/uniform/\n";
            $deleted++;
        }
    }
}

// Remove uploads/compressed/
if (is_dir($wrongCompressedDir)) {
    $files = glob($wrongCompressedDir . DIRECTORY_SEPARATOR . '*');
    $files = array_filter($files, function($f) { return basename($f) !== '.gitkeep' && !is_dir($f); });
    if (empty($files)) {
        // Remove .gitkeep first
        $gitkeep = $wrongCompressedDir . DIRECTORY_SEPARATOR . '.gitkeep';
        if (file_exists($gitkeep)) {
            unlink($gitkeep);
        }
        if (rmdir($wrongCompressedDir)) {
            echo "  ✓ Removed: uploads/compressed/\n";
            $deleted++;
        }
    }
}

// Remove uploads/uniform/
$wrongUniformRoot = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'uniform';
if (is_dir($wrongUniformRoot)) {
    $files = glob($wrongUniformRoot . DIRECTORY_SEPARATOR . '*');
    $files = array_filter($files, function($f) { return basename($f) !== '.gitkeep'; });
    if (empty($files)) {
        // Remove .gitkeep first
        $gitkeep = $wrongUniformRoot . DIRECTORY_SEPARATOR . '.gitkeep';
        if (file_exists($gitkeep)) {
            unlink($gitkeep);
        }
        if (rmdir($wrongUniformRoot)) {
            echo "  ✓ Removed: uploads/uniform/\n";
            $deleted++;
        }
    }
}

// Step 5: Update draft JSON files with correct paths
echo "\nStep 5: Updating draft JSON files with correct paths\n";
$draftFiles = glob($draftDir . DIRECTORY_SEPARATOR . '*.json');
$updated = 0;

foreach ($draftFiles as $draftFile) {
    $draftData = json_decode(file_get_contents($draftFile), true);
    if (!$draftData || !isset($draftData['uploaded_files'])) {
        continue;
    }
    
    $changed = false;
    foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
        // Fix paths that point to wrong locations
        $oldPath = $filePath;
        
        // Convert to absolute path to check if file exists
        if (file_exists($filePath)) {
            $absolutePath = $filePath;
        } else {
            $absolutePath = DirectoryManager::getAbsolutePath($filePath);
        }
        
        // If file doesn't exist at current path, try to find it in correct location
        if (!file_exists($absolutePath)) {
            $filename = basename($filePath);
            
            // Check in uploads/drafts/
            $newPath = $draftDir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($newPath)) {
                $draftData['uploaded_files'][$fieldName] = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($newPath));
                $changed = true;
                echo "  ✓ Updated path in " . basename($draftFile) . ": $fieldName\n";
            }
        } else {
            // File exists, just ensure path is in correct format (relative web path)
            $relativePath = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($absolutePath));
            if ($relativePath !== $oldPath) {
                $draftData['uploaded_files'][$fieldName] = $relativePath;
                $changed = true;
            }
        }
    }
    
    if ($changed) {
        file_put_contents($draftFile, json_encode($draftData, JSON_PRETTY_PRINT));
        $updated++;
    }
}

echo "  Updated $updated draft files\n";

// Summary
echo "\n=== SUMMARY ===\n";
echo "Files moved: $moved\n";
echo "Directories removed: $deleted\n";
echo "Draft files updated: $updated\n";
echo "Errors: $errors\n";

if ($errors === 0) {
    echo "\n✓ Uploads folder structure fixed successfully!\n";
} else {
    echo "\n⚠ Completed with $errors errors. Please check manually.\n";
}

echo "\n=== CURRENT STRUCTURE ===\n";
echo "uploads/\n";
echo "  drafts/\n";
echo "    compressed/\n";
echo "    uniform/\n";
echo "    *.json (draft files)\n";
echo "    *.jpg (uploaded images)\n";
echo "\nAll images should now be in uploads/drafts/ or its subdirectories.\n";
