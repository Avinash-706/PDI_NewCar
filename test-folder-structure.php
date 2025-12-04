<?php
/**
 * Test Folder Structure After Cleanup
 * 
 * Verifies that all functionality still works after folder optimization
 */

require_once __DIR__ . '/auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init-directories.php';

echo "=== Folder Structure Test ===\n\n";

$tests = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

// Test 1: Check required folders exist
echo "Test 1: Checking required folders...\n";
$requiredFolders = [
    'uploads/drafts' => 'Draft storage',
    'uploads/drafts/compressed' => 'Compressed images',
    'uploads/drafts/uniform' => 'Uniform images',
    'pdfs' => 'Generated PDFs',
    'tmp/mpdf' => 'mPDF temp',
    'logs' => 'Application logs',
    'drafts/audit' => 'Audit logs'
];

foreach ($requiredFolders as $folder => $purpose) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
    
    if (file_exists($path) && is_dir($path)) {
        echo "  ✓ $folder exists ($purpose)\n";
        $tests['passed']++;
    } else {
        echo "  ✗ $folder MISSING! ($purpose)\n";
        $tests['failed']++;
    }
}

echo "\n";

// Test 2: Check removed folders are gone
echo "Test 2: Verifying removed folders are gone...\n";
$removedFolders = [
    'drafts/logs' => 'Should be removed',
    'drafts/pdfs' => 'Should be removed',
    'drafts/uploads' => 'Should be removed',
    'uploads/compressed' => 'Should be removed',
    'uploads/uniform' => 'Should be removed',
    'templates' => 'Should be removed'
];

foreach ($removedFolders as $folder => $status) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
    
    if (!file_exists($path)) {
        echo "  ✓ $folder removed ($status)\n";
        $tests['passed']++;
    } else {
        echo "  ⚠ $folder still exists ($status)\n";
        $tests['warnings']++;
    }
}

echo "\n";

// Test 3: Check folder permissions
echo "Test 3: Checking folder permissions...\n";
foreach ($requiredFolders as $folder => $purpose) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
    
    if (!file_exists($path)) {
        continue;
    }
    
    if (is_writable($path)) {
        echo "  ✓ $folder is writable\n";
        $tests['passed']++;
    } else {
        echo "  ✗ $folder is NOT writable!\n";
        $tests['failed']++;
    }
}

echo "\n";

// Test 4: Test DirectoryManager functions
echo "Test 4: Testing DirectoryManager functions...\n";

try {
    // Test getAbsolutePath
    $draftPath = DirectoryManager::getAbsolutePath('uploads/drafts');
    if (file_exists($draftPath)) {
        echo "  ✓ getAbsolutePath() works\n";
        $tests['passed']++;
    } else {
        echo "  ✗ getAbsolutePath() returned invalid path\n";
        $tests['failed']++;
    }
    
    // Test getCompressedDir
    $testImagePath = $draftPath . DIRECTORY_SEPARATOR . 'test.jpg';
    $compressedDir = DirectoryManager::getCompressedDir($testImagePath);
    if (strpos($compressedDir, 'compressed') !== false) {
        echo "  ✓ getCompressedDir() works\n";
        $tests['passed']++;
    } else {
        echo "  ✗ getCompressedDir() returned invalid path\n";
        $tests['failed']++;
    }
    
    // Test getUniformDir
    $uniformDir = DirectoryManager::getUniformDir($testImagePath);
    if (strpos($uniformDir, 'uniform') !== false) {
        echo "  ✓ getUniformDir() works\n";
        $tests['passed']++;
    } else {
        echo "  ✗ getUniformDir() returned invalid path\n";
        $tests['failed']++;
    }
    
    // Test checkHealth
    $health = DirectoryManager::checkHealth();
    if (is_array($health) && !empty($health)) {
        echo "  ✓ checkHealth() works\n";
        $tests['passed']++;
    } else {
        echo "  ✗ checkHealth() failed\n";
        $tests['failed']++;
    }
    
} catch (Exception $e) {
    echo "  ✗ DirectoryManager error: " . $e->getMessage() . "\n";
    $tests['failed']++;
}

echo "\n";

// Test 5: Check for orphaned files
echo "Test 5: Checking for orphaned files...\n";
$orphanedFiles = [];

// Check if any files exist in removed folders
foreach ($removedFolders as $folder => $status) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $folder;
    
    if (file_exists($path) && is_dir($path)) {
        $files = scandir($path);
        $fileCount = count(array_diff($files, ['.', '..', '.gitkeep']));
        
        if ($fileCount > 0) {
            $orphanedFiles[$folder] = $fileCount;
        }
    }
}

if (empty($orphanedFiles)) {
    echo "  ✓ No orphaned files found\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Found orphaned files:\n";
    foreach ($orphanedFiles as $folder => $count) {
        echo "    - $folder: $count file(s)\n";
    }
    $tests['warnings']++;
}

echo "\n";

// Test 6: Verify path references in code
echo "Test 6: Verifying code references...\n";

$criticalFiles = [
    'upload-image.php' => ['uploads/drafts', 'drafts/audit'],
    'save-draft.php' => ['uploads/drafts'],
    'load-draft.php' => ['uploads/drafts'],
    'generate-pdf.php' => ['pdfs'],
    'generate-test-pdf.php' => ['pdfs'],
    'drafts/discard.php' => ['uploads/drafts', 'drafts/audit'],
    'drafts/auto-cleanup.php' => ['uploads/drafts']
];

foreach ($criticalFiles as $file => $expectedPaths) {
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . $file;
    
    if (!file_exists($filePath)) {
        echo "  ⚠ $file not found\n";
        $tests['warnings']++;
        continue;
    }
    
    $content = file_get_contents($filePath);
    $allPathsFound = true;
    
    foreach ($expectedPaths as $expectedPath) {
        if (strpos($content, $expectedPath) === false) {
            echo "  ✗ $file missing reference to $expectedPath\n";
            $allPathsFound = false;
            $tests['failed']++;
        }
    }
    
    if ($allPathsFound) {
        echo "  ✓ $file has correct path references\n";
        $tests['passed']++;
    }
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Passed: {$tests['passed']}\n";
echo "Failed: {$tests['failed']}\n";
echo "Warnings: {$tests['warnings']}\n";

if ($tests['failed'] === 0) {
    echo "\n✅ All tests passed! Folder structure is optimized and working correctly.\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
}

if ($tests['warnings'] > 0) {
    echo "\n💡 There are some warnings. Review them to ensure everything is as expected.\n";
}

echo "\n";

// Recommendations
echo "=== Recommendations ===\n";

if ($tests['failed'] === 0 && $tests['warnings'] === 0) {
    echo "✅ Folder structure is fully optimized!\n";
    echo "\nNext steps:\n";
    echo "  1. Test uploading an image\n";
    echo "  2. Test saving a draft\n";
    echo "  3. Test loading a draft\n";
    echo "  4. Test generating a PDF\n";
    echo "  5. Test discarding a draft\n";
    echo "  6. Monitor logs for any errors\n";
} else {
    if ($tests['failed'] > 0) {
        echo "⚠️  Fix the failed tests before proceeding.\n";
    }
    
    if ($tests['warnings'] > 0) {
        echo "💡 Review warnings and clean up if necessary.\n";
    }
}

echo "\n";
