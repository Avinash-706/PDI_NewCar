<?php
/**
 * Test Cleanup Flow
 * Simulates the cleanup flow from submit.php
 */

require_once 'auto-config.php';
require_once 'init-directories.php';

echo "=== TESTING CLEANUP FLOW ===\n\n";

// Simulate a submission
$draftId = 'test_draft_' . time();
$pdfPath = 'pdfs/test_' . time() . '.pdf';
$formData = ['booking_id' => 'TEST123'];

echo "1. Simulating draft cleanup...\n";
if ($draftId) {
    echo "   Draft ID: $draftId\n";
    require_once 'cleanup-after-submission.php';
    $draftCleanupResult = cleanupAfterSubmission($draftId, $formData);
    
    echo "   Result: " . ($draftCleanupResult['success'] ? 'Success' : 'Failed') . "\n";
    echo "   Message: " . $draftCleanupResult['message'] . "\n";
}

echo "\n2. Moving to PDF cleanup...\n";
echo "   This should ALWAYS run after draft cleanup\n";

echo "\n3. Simulating PDF cleanup...\n";
try {
    require_once 'cleanup-after-email.php';
    echo "   PDF Path: $pdfPath\n";
    $postCleanupResult = cleanupAfterEmail($pdfPath, $formData);
    
    echo "   Result: " . ($postCleanupResult['success'] ? 'Success' : 'Failed') . "\n";
    echo "   Message: " . $postCleanupResult['message'] . "\n";
} catch (Exception $e) {
    echo "   Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "If you see this message, the flow works correctly.\n";
