<?php
/**
 * Draft Cleanup Script
 * Removes archived drafts older than retention period
 * Usage: php scripts/cleanup_drafts.php [--dry-run] [--days=180]
 */

require_once __DIR__ . '/../auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/../config.php';

// Parse arguments
$dryRun = in_array('--dry-run', $argv);
$retentionDays = 180;

foreach ($argv as $arg) {
    if (strpos($arg, '--days=') === 0) {
        $retentionDays = (int)substr($arg, 7);
    }
}

echo "=== Draft Cleanup Tool ===\n";
echo "Retention period: $retentionDays days\n";
echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE") . "\n\n";

$cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
$draftsDir = 'uploads/drafts';

if (!file_exists($draftsDir)) {
    die("Drafts directory not found\n");
}

$deleted = 0;
$kept = 0;
$errors = 0;

$files = glob($draftsDir . '/draft_*.json');

foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);
    
    if (!$data) {
        echo "✗ Invalid JSON: $file\n";
        $errors++;
        continue;
    }
    
    $isArchived = $data['archived'] ?? false;
    $archivedAt = $data['archived_at'] ?? $data['timestamp'] ?? 0;
    
    if ($isArchived && $archivedAt < $cutoffTime) {
        echo "Deleting: $file (archived " . date('Y-m-d', $archivedAt) . ")\n";
        
        if (!$dryRun) {
            // Delete associated images
            foreach ($data['uploaded_files'] ?? [] as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            
            // Delete draft file
            unlink($file);
        }
        
        $deleted++;
    } else {
        $kept++;
    }
}

echo "\n=== Summary ===\n";
echo "Deleted: $deleted\n";
echo "Kept: $kept\n";
echo "Errors: $errors\n";

if ($dryRun) {
    echo "\nThis was a dry run. Use without --dry-run to actually delete files.\n";
}
