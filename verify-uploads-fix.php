<?php
/**
 * Verify Uploads Folder Fix
 * Comprehensive verification that all fixes are working correctly
 */

require_once __DIR__ . '/init-directories.php';

echo "=== VERIFYING UPLOADS FOLDER FIX ===\n\n";

$passed = 0;
$failed = 0;

// Test 1: Correct directories exist
echo "Test 1: Checking correct directories exist...\n";
$requiredDirs = [
    'uploads/drafts',
    'uploads/drafts/compressed',
    'uploads/drafts/uniform'
];

foreach ($requiredDirs as $dir) {
    $path = DirectoryManager::getAbsolutePath($dir);
    if (is_dir($path) && is_writable($path)) {
        echo "  ✓ $dir exists and is writable\n";
        $passed++;
    } else {
        echo "  ✗ $dir missing or not writable\n";
        $failed++;
    }
}

// Test 2: Wrong directories don't exist
echo "\nTest 2: Checking wrong directories don't exist...\n";
$wrongDirs = [
    'uploads/compressed',
    'uploads/uniform',
    'uploads/compressed/uniform'
];

foreach ($wrongDirs as $dir) {
    $path = DirectoryManager::getAbsolutePath($dir);
    if (!is_dir($path)) {
        echo "  ✓ $dir correctly doesn't exist\n";
        $passed++;
    } else {
        echo "  ✗ $dir still exists (should be removed)\n";
        $failed++;
    }
}

// Test 3: DirectoryManager methods return correct paths
echo "\nTest 3: Checking DirectoryManager methods...\n";

$compressedDir = DirectoryManager::getCompressedDir();
$expectedCompressed = DirectoryManager::getAbsolutePath('uploads/drafts/compressed');
if ($compressedDir === $expectedCompressed) {
    echo "  ✓ getCompressedDir() returns correct path\n";
    $passed++;
} else {
    echo "  ✗ getCompressedDir() returns wrong path: $compressedDir\n";
    $failed++;
}

$uniformDir = DirectoryManager::getUniformDir();
$expectedUniform = DirectoryManager::getAbsolutePath('uploads/drafts/uniform');
if ($uniformDir === $expectedUniform) {
    echo "  ✓ getUniformDir() returns correct path\n";
    $passed++;
} else {
    echo "  ✗ getUniformDir() returns wrong path: $uniformDir\n";
    $failed++;
}

// Test 4: No files in wrong locations
echo "\nTest 4: Checking no files in wrong locations...\n";
$uploadsRoot = DirectoryManager::getAbsolutePath('uploads');
$files = glob($uploadsRoot . DIRECTORY_SEPARATOR . '*');
$wrongFiles = array_filter($files, function($f) {
    return is_file($f) && basename($f) !== '.gitkeep';
});

if (empty($wrongFiles)) {
    echo "  ✓ No files in uploads/ root\n";
    $passed++;
} else {
    echo "  ✗ Found " . count($wrongFiles) . " files in uploads/ root:\n";
    foreach ($wrongFiles as $file) {
        echo "    - " . basename($file) . "\n";
    }
    $failed++;
}

// Test 5: Draft files have correct path format
echo "\nTest 5: Checking draft JSON files have correct paths...\n";
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts');
$draftFiles = glob($draftDir . DIRECTORY_SEPARATOR . '*.json');

if (empty($draftFiles)) {
    echo "  ℹ No draft files to check\n";
} else {
    $allCorrect = true;
    foreach ($draftFiles as $draftFile) {
        $draftData = json_decode(file_get_contents($draftFile), true);
        if (!$draftData || !isset($draftData['uploaded_files'])) {
            continue;
        }
        
        foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
            // Check if path starts with uploads/drafts/
            if (strpos($filePath, 'uploads/drafts/') !== 0) {
                echo "  ✗ Wrong path in " . basename($draftFile) . ": $filePath\n";
                $allCorrect = false;
                $failed++;
                break 2;
            }
        }
    }
    
    if ($allCorrect) {
        echo "  ✓ All draft files have correct path format\n";
        $passed++;
    }
}

// Test 6: Path conversion functions work correctly
echo "\nTest 6: Checking path conversion functions...\n";

$testAbsPath = DirectoryManager::getAbsolutePath('uploads/drafts/test.jpg');
$testRelPath = DirectoryManager::getRelativePath($testAbsPath);
$testWebPath = DirectoryManager::toWebPath($testRelPath);

if ($testRelPath === 'uploads' . DIRECTORY_SEPARATOR . 'drafts' . DIRECTORY_SEPARATOR . 'test.jpg') {
    echo "  ✓ getRelativePath() works correctly\n";
    $passed++;
} else {
    echo "  ✗ getRelativePath() returns wrong format: $testRelPath\n";
    $failed++;
}

if ($testWebPath === 'uploads/drafts/test.jpg') {
    echo "  ✓ toWebPath() works correctly\n";
    $passed++;
} else {
    echo "  ✗ toWebPath() returns wrong format: $testWebPath\n";
    $failed++;
}

// Summary
echo "\n=== SUMMARY ===\n";
echo "Tests passed: $passed\n";
echo "Tests failed: $failed\n";

if ($failed === 0) {
    echo "\n✅ ALL TESTS PASSED - Uploads folder is correctly fixed!\n";
    exit(0);
} else {
    echo "\n❌ SOME TESTS FAILED - Please review the errors above\n";
    exit(1);
}
