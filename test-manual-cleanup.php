<?php
/**
 * Manual Cleanup Test
 * Tests the cleanup functions directly
 */

require_once 'auto-config.php';
require_once 'init-directories.php';
require_once 'cleanup-after-submission.php';
require_once 'cleanup-after-email.php';

echo "=== MANUAL CLEANUP TEST ===\n\n";

// Test 1: Check remaining draft files
echo "1. Checking remaining draft files:\n";
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts');
$draftFiles = glob($draftDir . '/*.json');
echo "Found " . count($draftFiles) . " draft files:\n";
foreach ($draftFiles as $file) {
    echo "  - " . basename($file) . " (" . filesize($file) . " bytes)\n";
}
echo "\n";

// Test 2: Check remaining PDFs
echo "2. Checking remaining PDF files:\n";
$pdfDir = DirectoryManager::getAbsolutePath('pdfs');
$pdfFiles = glob($pdfDir . '/*.pdf');
echo "Found " . count($pdfFiles) . " PDF files:\n";
foreach ($pdfFiles as $file) {
    echo "  - " . basename($file) . " (" . round(filesize($file)/1024/1024, 2) . " MB)\n";
}
echo "\n";

// Test 3: Try to delete one draft manually
if (!empty($draftFiles)) {
    $testDraft = $draftFiles[0];
    $draftId = basename($testDraft, '.json');
    
    echo "3. Testing cleanup for draft: $draftId\n";
    $result = cleanupAfterSubmission($draftId, []);
    
    echo "Result:\n";
    echo "  Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "  Message: " . $result['message'] . "\n";
    echo "  Deleted images: " . $result['deleted_images'] . "\n";
    echo "  Deleted files: " . $result['deleted_files'] . "\n";
    echo "  Freed space: " . round($result['freed_space']/1024, 2) . " KB\n";
    
    // Check if file still exists
    if (file_exists($testDraft)) {
        echo "  WARNING: Draft file STILL EXISTS after cleanup!\n";
        echo "  File path: $testDraft\n";
        echo "  File is readable: " . (is_readable($testDraft) ? 'Yes' : 'No') . "\n";
        echo "  File is writable: " . (is_writable($testDraft) ? 'Yes' : 'No') . "\n";
    } else {
        echo "  SUCCESS: Draft file was deleted\n";
    }
    echo "\n";
}

// Test 4: Try to delete one PDF manually
if (!empty($pdfFiles)) {
    $testPdf = $pdfFiles[0];
    
    echo "4. Testing PDF cleanup for: " . basename($testPdf) . "\n";
    $result = cleanupAfterEmail($testPdf, ['booking_id' => 'test']);
    
    echo "Result:\n";
    echo "  Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "  Message: " . $result['message'] . "\n";
    echo "  Deleted PDF: " . ($result['deleted_pdf'] ? 'Yes' : 'No') . "\n";
    echo "  Deleted compressed: " . $result['deleted_compressed'] . "\n";
    echo "  Deleted uniform: " . $result['deleted_uniform'] . "\n";
    echo "  Freed space: " . round($result['freed_space']/1024/1024, 2) . " MB\n";
    
    // Check if file still exists
    if (file_exists($testPdf)) {
        echo "  WARNING: PDF file STILL EXISTS after cleanup!\n";
        echo "  File path: $testPdf\n";
        echo "  File is readable: " . (is_readable($testPdf) ? 'Yes' : 'No') . "\n";
        echo "  File is writable: " . (is_writable($testPdf) ? 'Yes' : 'No') . "\n";
    } else {
        echo "  SUCCESS: PDF file was deleted\n";
    }
    echo "\n";
}

echo "=== TEST COMPLETE ===\n";
