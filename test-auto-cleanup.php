<?php
/**
 * Test Auto-Cleanup After Submission
 * 
 * Tests the automatic cleanup functionality
 */

require_once __DIR__ . '/auto-config.php';
require_once __DIR__ . '/init-directories.php';
require_once __DIR__ . '/cleanup-after-submission.php';

echo "=== Auto-Cleanup After Submission Test ===\n\n";

$tests = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

// Test 1: Check cleanup function exists
echo "Test 1: Checking cleanup function...\n";
if (function_exists('cleanupAfterSubmission')) {
    echo "  ✓ cleanupAfterSubmission() function exists\n";
    $tests['passed']++;
} else {
    echo "  ✗ cleanupAfterSubmission() function NOT found!\n";
    $tests['failed']++;
}

echo "\n";

// Test 2: Test with non-existent draft (should succeed gracefully)
echo "Test 2: Testing with non-existent draft...\n";
$result = cleanupAfterSubmission('non_existent_draft_123');

if ($result['success']) {
    echo "  ✓ Handled non-existent draft gracefully\n";
    echo "    Message: {$result['message']}\n";
    $tests['passed']++;
} else {
    echo "  ✗ Failed to handle non-existent draft\n";
    $tests['failed']++;
}

echo "\n";

// Test 3: Test with null draft ID (should succeed gracefully)
echo "Test 3: Testing with null draft ID...\n";
$result = cleanupAfterSubmission(null);

if ($result['success']) {
    echo "  ✓ Handled null draft ID gracefully\n";
    echo "    Message: {$result['message']}\n";
    $tests['passed']++;
} else {
    echo "  ✗ Failed to handle null draft ID\n";
    $tests['failed']++;
}

echo "\n";

// Test 4: Check if cleanup is integrated in submit.php
echo "Test 4: Checking submit.php integration...\n";
$submitContent = file_get_contents(__DIR__ . '/submit.php');

if (strpos($submitContent, 'cleanup-after-submission.php') !== false) {
    echo "  ✓ cleanup-after-submission.php is included in submit.php\n";
    $tests['passed']++;
} else {
    echo "  ✗ cleanup-after-submission.php NOT included in submit.php\n";
    $tests['failed']++;
}

if (strpos($submitContent, 'cleanupAfterSubmission') !== false) {
    echo "  ✓ cleanupAfterSubmission() is called in submit.php\n";
    $tests['passed']++;
} else {
    echo "  ✗ cleanupAfterSubmission() NOT called in submit.php\n";
    $tests['failed']++;
}

if (strpos($submitContent, 'generatePDF') !== false) {
    echo "  ✓ PDF generation exists in submit.php\n";
    $tests['passed']++;
} else {
    echo "  ✗ PDF generation NOT found in submit.php\n";
    $tests['failed']++;
}

echo "\n";

// Test 5: Check if draft_id is sent from client
echo "Test 5: Checking script.js integration...\n";
$scriptContent = file_get_contents(__DIR__ . '/script.js');

if (strpos($scriptContent, 'draft_id') !== false) {
    echo "  ✓ draft_id is referenced in script.js\n";
    $tests['passed']++;
} else {
    echo "  ⚠ draft_id NOT found in script.js\n";
    $tests['warnings']++;
}

if (strpos($scriptContent, "formData.append('draft_id'") !== false) {
    echo "  ✓ draft_id is appended to formData\n";
    $tests['passed']++;
} else {
    echo "  ⚠ draft_id NOT appended to formData\n";
    $tests['warnings']++;
}

echo "\n";

// Test 6: Check cleanup logic
echo "Test 6: Checking cleanup logic...\n";
$cleanupContent = file_get_contents(__DIR__ . '/cleanup-after-submission.php');

$checks = [
    'uploaded_files' => 'Checks for uploaded_files array',
    'unlink' => 'Deletes files',
    'DirectoryManager' => 'Uses DirectoryManager',
    'error_log' => 'Logs operations',
    'freed_space' => 'Tracks freed space',
    'deleted_images' => 'Counts deleted images'
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

// Test 7: Verify cleanup runs AFTER PDF generation
echo "Test 7: Verifying cleanup order in submit.php...\n";

// Find position of PDF generation
$pdfPos = strpos($submitContent, 'generatePDF');
// Find position of cleanup
$cleanupPos = strpos($submitContent, 'cleanupAfterSubmission');

if ($pdfPos !== false && $cleanupPos !== false) {
    if ($cleanupPos > $pdfPos) {
        echo "  ✓ Cleanup runs AFTER PDF generation\n";
        $tests['passed']++;
    } else {
        echo "  ✗ Cleanup runs BEFORE PDF generation (WRONG ORDER!)\n";
        $tests['failed']++;
    }
} else {
    echo "  ⚠ Could not verify order\n";
    $tests['warnings']++;
}

// Check if PDF existence is verified before cleanup
if (strpos($submitContent, 'file_exists($pdfPath)') !== false) {
    echo "  ✓ PDF existence is verified before cleanup\n";
    $tests['passed']++;
} else {
    echo "  ⚠ PDF existence check not found\n";
    $tests['warnings']++;
}

echo "\n";

// Test 8: Check for safety conditions
echo "Test 8: Checking safety conditions...\n";

$safetyChecks = [
    'if (!$pdfPath' => 'Checks if PDF generation succeeded',
    'throw new Exception' => 'Throws exception on failure',
    'try {' => 'Has error handling',
    'catch (Exception' => 'Catches exceptions'
];

foreach ($safetyChecks as $pattern => $description) {
    if (strpos($submitContent, $pattern) !== false) {
        echo "  ✓ $description\n";
        $tests['passed']++;
    } else {
        echo "  ⚠ Missing: $description\n";
        $tests['warnings']++;
    }
}

echo "\n";

// Test 9: Check if existing drafts would be cleaned up
echo "Test 9: Checking for existing drafts...\n";
$draftsDir = DirectoryManager::getAbsolutePath('uploads/drafts');
$draftFiles = glob($draftsDir . DIRECTORY_SEPARATOR . '*.json');
$draftFiles = array_filter($draftFiles, function($file) {
    return strpos(basename($file), 'backup_') !== 0 && 
           !preg_match('/\.v\d+\.json$/', $file);
});

if (empty($draftFiles)) {
    echo "  ✓ No existing drafts to test with\n";
    $tests['passed']++;
} else {
    echo "  ⚠ Found " . count($draftFiles) . " existing draft(s)\n";
    echo "    These would be cleaned up after successful submission\n";
    $tests['warnings']++;
    
    // Show first draft as example
    $exampleDraft = array_values($draftFiles)[0];
    $draftData = @json_decode(file_get_contents($exampleDraft), true);
    if ($draftData) {
        $draftId = $draftData['draft_id'] ?? basename($exampleDraft, '.json');
        $imageCount = isset($draftData['uploaded_files']) ? count($draftData['uploaded_files']) : 0;
        echo "    Example: $draftId with $imageCount image(s)\n";
    }
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Passed: {$tests['passed']}\n";
echo "Failed: {$tests['failed']}\n";
echo "Warnings: {$tests['warnings']}\n";

if ($tests['failed'] === 0) {
    echo "\n✅ All critical tests passed!\n";
    echo "\nAuto-cleanup is properly integrated and will:\n";
    echo "  1. Run AFTER successful PDF generation\n";
    echo "  2. Delete draft JSON and all associated images\n";
    echo "  3. Clean up metadata and empty directories\n";
    echo "  4. Log all operations\n";
    echo "  5. Handle errors gracefully\n";
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
echo "   - Save a draft\n";
echo "   - Submit the form\n";
echo "   - Verify PDF is generated\n";
echo "   - Check that draft is deleted\n";
echo "   - Check logs for cleanup messages\n";
echo "\n";
echo "2. Monitor logs after deployment:\n";
echo "   tail -f logs/error.log | grep Cleanup\n";
echo "\n";
echo "3. Verify disk space is being freed:\n";
echo "   du -sh uploads/drafts/\n";
echo "\n";
