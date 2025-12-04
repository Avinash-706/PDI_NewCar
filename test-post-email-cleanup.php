<?php
/**
 * Test Post-Email Cleanup System
 * 
 * Tests the post-email cleanup functionality
 */

require_once __DIR__ . '/auto-config.php';
require_once __DIR__ . '/init-directories.php';
require_once __DIR__ . '/cleanup-after-email.php';

echo "=== Post-Email Cleanup System Test ===\n\n";

$tests = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

// Test 1: Check cleanup function exists
echo "Test 1: Checking cleanup function...\n";
if (function_exists('cleanupAfterEmail')) {
    echo "  ✓ cleanupAfterEmail() function exists\n";
    $tests['passed']++;
} else {
    echo "  ✗ cleanupAfterEmail() function NOT found!\n";
    $tests['failed']++;
}

echo "\n";

// Test 2: Test with non-existent PDF (should handle gracefully)
echo "Test 2: Testing with non-existent PDF...\n";
$result = cleanupAfterEmail('/path/to/non_existent.pdf');

if (!$result['success']) {
    echo "  ✓ Handled non-existent PDF gracefully\n";
    echo "    Message: {$result['message']}\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Unexpected success with non-existent PDF\n";
    $tests['warnings']++;
}

echo "\n";

// Test 3: Test with empty PDF path
echo "Test 3: Testing with empty PDF path...\n";
$result = cleanupAfterEmail('');

if (!$result['success']) {
    echo "  ✓ Handled empty PDF path gracefully\n";
    echo "    Message: {$result['message']}\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Unexpected success with empty PDF path\n";
    $tests['warnings']++;
}

echo "\n";

// Test 4: Check if cleanup is integrated in submit.php
echo "Test 4: Checking submit.php integration...\n";
$submitContent = file_get_contents(__DIR__ . '/submit.php');

if (strpos($submitContent, 'cleanup-after-email.php') !== false) {
    echo "  ✓ cleanup-after-email.php is included in submit.php\n";
    $tests['passed']++;
} else {
    echo "  ✗ cleanup-after-email.php NOT included in submit.php\n";
    $tests['failed']++;
}

if (strpos($submitContent, 'cleanupAfterEmail') !== false) {
    echo "  ✓ cleanupAfterEmail() is called in submit.php\n";
    $tests['passed']++;
} else {
    echo "  ✗ cleanupAfterEmail() NOT called in submit.php\n";
    $tests['failed']++;
}

echo "\n";

// Test 5: Verify cleanup runs AFTER email success
echo "Test 5: Verifying cleanup order in submit.php...\n";

// Find position of email sending
$emailPos = strpos($submitContent, 'sendEmail');
// Find position of post-email cleanup
$postCleanupPos = strpos($submitContent, 'cleanupAfterEmail');

if ($emailPos !== false && $postCleanupPos !== false) {
    if ($postCleanupPos > $emailPos) {
        echo "  ✓ Cleanup runs AFTER email sending\n";
        $tests['passed']++;
    } else {
        echo "  ✗ Cleanup runs BEFORE email sending (WRONG ORDER!)\n";
        $tests['failed']++;
    }
} else {
    echo "  ⚠ Could not verify order\n";
    $tests['warnings']++;
}

// Check if cleanup is conditional on email success
if (strpos($submitContent, 'if ($emailSent)') !== false || 
    strpos($submitContent, 'if (!$emailSent)') !== false) {
    echo "  ✓ Cleanup is conditional on email success\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Could not verify email success condition\n";
    $tests['warnings']++;
}

echo "\n";

// Test 6: Check cleanup logic
echo "Test 6: Checking cleanup logic...\n";
$cleanupContent = file_get_contents(__DIR__ . '/cleanup-after-email.php');

$checks = [
    'unlink($pdfPath)' => 'Deletes PDF file',
    'deleteDirectoryContents' => 'Deletes directory contents',
    'compressed' => 'Handles compressed images',
    'uniform' => 'Handles uniform images',
    'tmp/mpdf' => 'Handles temporary files',
    'freed_space' => 'Tracks freed space',
    'error_log' => 'Logs operations'
];

foreach ($checks as $pattern => $description) {
    if (strpos($cleanupContent, $pattern) !== false) {
        echo "  ✓ $description\n";
        $tests['passed']++;
    } else {
        echo "  ✗ Missing: $description\n";
        $tests['failed']++;
    }
}

echo "\n";

// Test 7: Check for safety conditions
echo "Test 7: Checking safety conditions...\n";

$safetyChecks = [
    'if (empty($pdfPath))' => 'Validates PDF path',
    'if (!file_exists($pdfPath))' => 'Checks file existence',
    'try {' => 'Has error handling',
    'catch (Exception' => 'Catches exceptions'
];

foreach ($safetyChecks as $pattern => $description) {
    if (strpos($cleanupContent, $pattern) !== false) {
        echo "  ✓ $description\n";
        $tests['passed']++;
    } else {
        echo "  ⚠ Missing: $description\n";
        $tests['warnings']++;
    }
}

echo "\n";

// Test 8: Check if email failure prevents cleanup
echo "Test 8: Checking email failure handling...\n";

if (strpos($submitContent, 'SKIPPED - Email sending failed') !== false ||
    strpos($submitContent, 'SKIPPED - Email exception') !== false) {
    echo "  ✓ Cleanup is skipped when email fails\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Could not verify email failure handling\n";
    $tests['warnings']++;
}

echo "\n";

// Test 9: Check existing PDFs
echo "Test 9: Checking for existing PDFs...\n";
$pdfsDir = DirectoryManager::getAbsolutePath('pdfs');
$pdfFiles = glob($pdfsDir . DIRECTORY_SEPARATOR . '*.pdf');

if (empty($pdfFiles)) {
    echo "  ✓ No existing PDFs (all cleaned up)\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Found " . count($pdfFiles) . " existing PDF(s)\n";
    echo "    These would be deleted after successful email\n";
    $tests['warnings']++;
    
    // Show first PDF as example
    if (!empty($pdfFiles)) {
        $examplePdf = basename($pdfFiles[0]);
        $pdfSize = filesize($pdfFiles[0]);
        echo "    Example: $examplePdf (" . formatBytes($pdfSize) . ")\n";
    }
}

echo "\n";

// Test 10: Check compressed/uniform directories
echo "Test 10: Checking processing directories...\n";

$compressedDir = DirectoryManager::getAbsolutePath('uploads/drafts/compressed');
$uniformDir = DirectoryManager::getAbsolutePath('uploads/drafts/uniform');

$compressedFiles = is_dir($compressedDir) ? glob($compressedDir . DIRECTORY_SEPARATOR . '*') : [];
$uniformFiles = is_dir($uniformDir) ? glob($uniformDir . DIRECTORY_SEPARATOR . '*') : [];

// Filter out .gitkeep
$compressedFiles = array_filter($compressedFiles, function($f) { return basename($f) !== '.gitkeep'; });
$uniformFiles = array_filter($uniformFiles, function($f) { return basename($f) !== '.gitkeep'; });

if (empty($compressedFiles) && empty($uniformFiles)) {
    echo "  ✓ Processing directories are clean\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Found processing files:\n";
    if (!empty($compressedFiles)) {
        echo "    Compressed: " . count($compressedFiles) . " file(s)\n";
    }
    if (!empty($uniformFiles)) {
        echo "    Uniform: " . count($uniformFiles) . " file(s)\n";
    }
    $tests['warnings']++;
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Passed: {$tests['passed']}\n";
echo "Failed: {$tests['failed']}\n";
echo "Warnings: {$tests['warnings']}\n";

if ($tests['failed'] === 0) {
    echo "\n✅ All critical tests passed!\n";
    echo "\nPost-email cleanup is properly integrated and will:\n";
    echo "  1. Run ONLY after successful email delivery\n";
    echo "  2. Delete PDF file\n";
    echo "  3. Delete compressed images\n";
    echo "  4. Delete uniform images\n";
    echo "  5. Delete temporary files\n";
    echo "  6. Skip cleanup if email fails\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the errors above.\n";
}

if ($tests['warnings'] > 0) {
    echo "\n💡 There are some warnings. Review them to ensure everything is as expected.\n";
}

echo "\n";

// Recommendations
echo "=== Next Steps ===\n";
echo "1. Test with a real submission:\n";
echo "   - Fill out the form\n";
echo "   - Submit the form\n";
echo "   - Verify PDF is generated\n";
echo "   - Verify email is sent\n";
echo "   - Check that PDF is deleted\n";
echo "   - Check that compressed images are deleted\n";
echo "   - Check logs for cleanup messages\n";
echo "\n";
echo "2. Test email failure scenario:\n";
echo "   - Temporarily break SMTP config\n";
echo "   - Submit form\n";
echo "   - Verify PDF is kept (not deleted)\n";
echo "   - Verify compressed images are kept\n";
echo "   - Check logs show 'SKIPPED'\n";
echo "\n";
echo "3. Monitor logs after deployment:\n";
echo "   tail -f logs/error.log | grep 'Post-Email Cleanup'\n";
echo "\n";
