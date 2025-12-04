<?php
/**
 * Draft Diagnostic and Repair Tool
 * Usage: php scripts/diagnose_draft.php <draft_json_path>
 */

require_once __DIR__ . '/../auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/../config.php';

if ($argc < 2) {
    die("Usage: php scripts/diagnose_draft.php <draft_json_path>\n");
}

$draftPath = $argv[1];

if (!file_exists($draftPath)) {
    die("Error: Draft file not found: $draftPath\n");
}

echo "=== Draft Diagnostic Tool ===\n";
echo "Analyzing: $draftPath\n\n";

// Load draft
$draftData = json_decode(file_get_contents($draftPath), true);
if (!$draftData) {
    die("Error: Invalid JSON in draft file\n");
}

$draftId = $draftData['draft_id'] ?? basename($draftPath, '.json');

// Create diagnostics directory
if (!file_exists('diagnostics')) {
    mkdir('diagnostics', 0755, true);
}

// Initialize report
$report = [
    'draft_id' => $draftId,
    'analyzed_at' => date('Y-m-d H:i:s'),
    'draft_path' => $draftPath,
    'foundFiles' => [],
    'missingFiles' => [],
    'repairedFiles' => [],
    'warnings' => [],
    'errors' => [],
    'stats' => [
        'total_fields' => 0,
        'total_images' => 0,
        'found_images' => 0,
        'missing_images' => 0,
        'repaired_images' => 0
    ]
];

// Backup original
$backupPath = dirname($draftPath) . '/backup_' . basename($draftPath);
copy($draftPath, $backupPath);
echo "✓ Backup created: $backupPath\n\n";

// Normalize uploaded_files structure
if (!isset($draftData['uploaded_files'])) {
    $draftData['uploaded_files'] = [];
}

// Migrate existing_* fields to uploaded_files
$migrated = 0;
foreach ($draftData['form_data'] ?? [] as $key => $value) {
    if (strpos($key, 'existing_') === 0 && !empty($value)) {
        $fieldName = str_replace('existing_', '', $key);
        $pathKey = $fieldName . '_path';
        
        if (!isset($draftData['uploaded_files'][$fieldName])) {
            $draftData['uploaded_files'][$fieldName] = $value;
            $migrated++;
            $report['warnings'][] = "Migrated $key to uploaded_files[$fieldName]";
        }
    }
}

if ($migrated > 0) {
    echo "✓ Migrated $migrated existing_* fields to uploaded_files\n";
}

// Check all uploaded files
foreach ($draftData['uploaded_files'] ?? [] as $fieldName => $filePath) {
    $report['stats']['total_images']++;
    
    if (empty($filePath)) {
        $report['missingFiles'][] = [
            'field' => $fieldName,
            'reason' => 'Empty path'
        ];
        $report['stats']['missing_images']++;
        continue;
    }
    
    if (file_exists($filePath)) {
        $report['foundFiles'][] = [
            'field' => $fieldName,
            'path' => $filePath,
            'size' => filesize($filePath),
            'readable' => is_readable($filePath)
        ];
        $report['stats']['found_images']++;
    } else {
        // Try to find file by name
        $basename = basename($filePath);
        $found = findFileInUploads($basename);
        
        if ($found) {
            $draftData['uploaded_files'][$fieldName] = $found;
            $report['repairedFiles'][] = [
                'field' => $fieldName,
                'old_path' => $filePath,
                'new_path' => $found
            ];
            $report['stats']['repaired_images']++;
            echo "✓ Repaired: $fieldName -> $found\n";
        } else {
            $report['missingFiles'][] = [
                'field' => $fieldName,
                'path' => $filePath,
                'reason' => 'File not found'
            ];
            $report['stats']['missing_images']++;
            echo "✗ Missing: $fieldName ($filePath)\n";
        }
    }
}

// Update draft version
if (!isset($draftData['version'])) {
    $draftData['version'] = 1;
}

if ($migrated > 0 || $report['stats']['repaired_images'] > 0) {
    $draftData['version']++;
    $draftData['updated_at'] = time();
    $draftData['repaired_at'] = date('Y-m-d H:i:s');
    
    // Save updated draft
    file_put_contents($draftPath, json_encode($draftData, JSON_PRETTY_PRINT));
    echo "\n✓ Draft updated (version {$draftData['version']})\n";
}

// Save report
$reportPath = "diagnostics/{$draftId}_report.json";
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

echo "\n=== Diagnostic Summary ===\n";
echo "Total images: {$report['stats']['total_images']}\n";
echo "Found: {$report['stats']['found_images']}\n";
echo "Missing: {$report['stats']['missing_images']}\n";
echo "Repaired: {$report['stats']['repaired_images']}\n";
echo "\nReport saved: $reportPath\n";

/**
 * Find file in uploads directory
 */
function findFileInUploads($basename) {
    $searchDirs = ['uploads', 'uploads/drafts'];
    
    foreach ($searchDirs as $dir) {
        if (!file_exists($dir)) continue;
        
        $files = glob($dir . '/*' . $basename);
        if (!empty($files)) {
            return $files[0];
        }
        
        // Try fuzzy search
        $pattern = preg_replace('/[0-9_]+/', '*', $basename);
        $files = glob($dir . '/' . $pattern);
        if (!empty($files)) {
            return $files[0];
        }
    }
    
    return null;
}
