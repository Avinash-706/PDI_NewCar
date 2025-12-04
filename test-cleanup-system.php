<?php
/**
 * Test Cleanup System
 * 
 * This script tests the draft cleanup functionality
 * Run via: php test-cleanup-system.php
 */

require_once __DIR__ . '/auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/init-directories.php';
require_once __DIR__ . '/drafts/auto-cleanup.php';

echo "=== Draft Cleanup System Test ===\n\n";

// Test 1: Check drafts directory
echo "Test 1: Checking drafts directory...\n";
$draftsDir = DirectoryManager::getAbsolutePath('uploads/drafts');
if (is_dir($draftsDir)) {
    echo "✓ Drafts directory exists: $draftsDir\n";
} else {
    echo "✗ Drafts directory not found!\n";
    exit(1);
}

// Test 2: Count existing drafts
echo "\nTest 2: Counting existing drafts...\n";
$draftFiles = glob($draftsDir . DIRECTORY_SEPARATOR . '*.json');
$draftFiles = array_filter($draftFiles, function($file) {
    return strpos(basename($file), 'backup_') !== 0 && 
           !preg_match('/\.v\d+\.json$/', $file);
});
echo "✓ Found " . count($draftFiles) . " draft(s)\n";

// Test 3: Analyze draft ages
echo "\nTest 3: Analyzing draft ages...\n";
$currentTime = time();
$expiredCount = 0;
$activeCount = 0;

foreach ($draftFiles as $draftFile) {
    $draftData = @json_decode(file_get_contents($draftFile), true);
    if (!$draftData) continue;
    
    $draftId = $draftData['draft_id'] ?? basename($draftFile, '.json');
    $timestamp = $draftData['timestamp'] ?? $draftData['updated_at'] ?? filemtime($draftFile);
    $age = $currentTime - $timestamp;
    $ageDays = round($age / 86400, 1);
    
    if ($age > 259200) { // 3 days
        $expiredCount++;
        echo "  ⚠ EXPIRED: $draftId (age: {$ageDays} days)\n";
    } else {
        $activeCount++;
        echo "  ✓ ACTIVE: $draftId (age: {$ageDays} days)\n";
    }
    
    // Count images
    $imageCount = isset($draftData['uploaded_files']) ? count($draftData['uploaded_files']) : 0;
    echo "    Images: $imageCount\n";
}

echo "\nSummary:\n";
echo "  Active drafts: $activeCount\n";
echo "  Expired drafts: $expiredCount\n";

// Test 4: Dry run cleanup
echo "\nTest 4: Running dry-run cleanup...\n";
$stats = autoCleanupOldDrafts(259200, true);

echo "  Total drafts: {$stats['total_drafts']}\n";
echo "  Expired drafts: {$stats['expired_drafts']}\n";
echo "  Would delete: {$stats['expired_drafts']} drafts\n";

if (!empty($stats['errors'])) {
    echo "\n  Errors:\n";
    foreach ($stats['errors'] as $error) {
        echo "    - $error\n";
    }
}

// Test 5: Ask user if they want to execute cleanup
if ($expiredCount > 0) {
    echo "\n=== CLEANUP EXECUTION ===\n";
    echo "Found $expiredCount expired draft(s).\n";
    echo "Do you want to delete them? (yes/no): ";
    
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) === 'yes') {
        echo "\nExecuting cleanup...\n";
        $stats = autoCleanupOldDrafts(259200, false);
        
        echo "\n✓ Cleanup completed!\n";
        echo "  Deleted drafts: {$stats['deleted_drafts']}\n";
        echo "  Deleted images: {$stats['deleted_images']}\n";
        echo "  Deleted files: {$stats['deleted_files']}\n";
        echo "  Freed space: " . formatBytes($stats['freed_space']) . "\n";
        
        if (!empty($stats['errors'])) {
            echo "\n  Errors:\n";
            foreach ($stats['errors'] as $error) {
                echo "    - $error\n";
            }
        }
    } else {
        echo "\nCleanup cancelled.\n";
    }
} else {
    echo "\n✓ No expired drafts to clean up.\n";
}

echo "\n=== Test Complete ===\n";
