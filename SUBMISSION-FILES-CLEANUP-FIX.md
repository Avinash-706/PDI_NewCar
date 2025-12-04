# Submission Files Cleanup Fix

## Problem Discovered

### Orphaned Files in `uploads/` Directory
After successful form submission and email delivery, `car_photo_*` files were accumulating in the `uploads/` base directory and never being deleted.

**Files Found:**
- `car_photo_1764782044_69306fdc4a034.jpg` (589 KB)
- `car_photo_1764782552_693071d86288c.jpeg` (812 KB)
- `car_photo_1764782929_6930735115b43.png` (92 KB)
- `car_photo_1764783665_69307631baf09.png` (1930 KB)

**Total:** 3.34 MB of orphaned files

## Root Cause Analysis

### File Upload Flow

1. **Draft Stage**: Files uploaded via progressive upload → saved to `uploads/drafts/`
2. **Submission Stage**: NEW files uploaded during final submit → saved to `uploads/` (base directory)

### Why Files Were Created in `uploads/`

In `submit.php`, the `handleFileUpload()` function uses `UPLOAD_DIR` constant:

```php
function handleFileUpload($file, $uploadDir) {
    $filename = 'car_photo_' . time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;  // Uses UPLOAD_DIR = 'uploads/'
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    return $targetPath;
}
```

Called with:
```php
$uploadedPath = handleFileUpload($file, UPLOAD_DIR);  // UPLOAD_DIR = 'uploads/'
```

### Why Files Were NOT Deleted

The cleanup system had 3 steps:
1. **Step 1**: Delete draft files from `uploads/drafts/` ✓
2. **Step 2**: Delete PDF from `pdfs/` ✓
3. **Step 3**: Delete compressed/uniform images ✓

**Missing**: No step to delete submission files from `uploads/`

The cleanup only deleted files tracked in the draft JSON, which didn't include files uploaded during final submission.

## Solution Implemented

### 1. Track Submission Files

In `submit.php`, store uploaded files for cleanup:

```php
// Store draft ID and uploaded files for cleanup after email
$draftId = $_POST['draft_id'] ?? $formData['draft_id'] ?? null;
$submissionFiles = $uploadedFiles; // Track files uploaded during submission
```

### 2. Add Cleanup Step for Submission Files

Added new Step 2 in cleanup flow:

```php
// Step 2: Delete submission files (car_photo_* in uploads/)
if (!empty($submissionFiles)) {
    error_log('Post-Email Cleanup: STEP 2 - Deleting submission files');
    $deletedSubmissionFiles = 0;
    $freedSubmissionSpace = 0;
    
    foreach ($submissionFiles as $fieldName => $filePath) {
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
            if (@unlink($filePath)) {
                $deletedSubmissionFiles++;
                $freedSubmissionSpace += $fileSize;
                error_log("Post-Email Cleanup: Deleted submission file: $filePath");
            }
        }
    }
    
    error_log("Post-Email Cleanup: STEP 2 COMPLETE - Deleted $deletedSubmissionFiles submission files");
}
```

### 3. Update Emergency Cleanup Script

Added step 5 to `cleanup-all-now.php`:

```php
// 5. Delete submission files (car_photo_* in uploads/)
$uploadsDir = DirectoryManager::getAbsolutePath('uploads');
$submissionFiles = glob($uploadsDir . '/car_photo_*');
foreach ($submissionFiles as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
```

### 4. Update Status Check Script

Added check for submission files in `check-status.php`:

```php
// Check submission files (car_photo_* in uploads/)
$uploadsDir = DirectoryManager::getAbsolutePath('uploads');
$submissionFiles = glob($uploadsDir . '/car_photo_*');
echo "Submission files (car_photo_*): " . count($submissionFiles) . "\n";
```

## New Cleanup Flow

After successful email delivery:

1. **Step 1**: Delete draft files from `uploads/drafts/`
   - Draft JSON file
   - Draft images (tracked in JSON)
   - Thumbnails
   - Audit logs

2. **Step 2**: Delete submission files from `uploads/` ← **NEW**
   - `car_photo_*` files uploaded during final submission

3. **Step 3**: Delete PDF and processing files
   - PDF from `pdfs/`
   - Compressed images from `uploads/drafts/compressed/`
   - Uniform images from `uploads/drafts/uniform/` and `uploads/drafts/compressed/uniform/`

## Files Modified

1. **submit.php**
   - Added `$submissionFiles` tracking
   - Added Step 2 for submission file cleanup
   - Renumbered Step 3 (was Step 2)

2. **cleanup-all-now.php**
   - Added step 5 for submission files
   - Renumbered step 6 (was step 5)

3. **check-status.php**
   - Added submission files check
   - Updated clean status logic

## Testing Results

### Before Fix
```
Submission files (car_photo_*): 4
  - car_photo_1764782044_69306fdc4a034.jpg (589.18 KB)
  - car_photo_1764782552_693071d86288c.jpeg (812.31 KB)
  - car_photo_1764782929_6930735115b43.png (92.17 KB)
  - car_photo_1764783665_69307631baf09.png (1930.58 KB)

STATUS: NEEDS CLEANUP ✗
```

### After Fix
```
Submission files (car_photo_*): 0

STATUS: CLEAN ✓
```

### Manual Cleanup Results
```
5. Cleaning submission files (car_photo_*)...
  ✓ Deleted: car_photo_1764782044_69306fdc4a034.jpg (589.18 KB)
  ✓ Deleted: car_photo_1764782552_693071d86288c.jpeg (812.31 KB)
  ✓ Deleted: car_photo_1764782929_6930735115b43.png (92.17 KB)
  ✓ Deleted: car_photo_1764783665_69307631baf09.png (1930.58 KB)
  Total: 4 submission files deleted

Total space freed: 3.34 MB
```

## Expected Log Output

After successful submission and email:

```
Background email sent successfully for: [PDF_PATH]
Post-Email Cleanup: STEP 1 - Starting draft cleanup for: [DRAFT_ID]
Post-Email Cleanup: Draft deleted - Deleted X images, Freed X KB
Post-Email Cleanup: STEP 1 COMPLETE - Moving to step 2
Post-Email Cleanup: STEP 2 - Deleting submission files
Post-Email Cleanup: Deleted submission file: uploads/car_photo_[TIMESTAMP]_[UNIQID].jpg
Post-Email Cleanup: STEP 2 COMPLETE - Deleted X submission files, Freed X KB
Post-Email Cleanup: STEP 3 - Starting PDF and processing files cleanup
cleanupAfterEmail() CALLED with PDF: [PDF_PATH]
Post-Email Cleanup: PDF and processing files deleted - PDF: Yes, Compressed: X, Uniform: X
```

## Prevention

To prevent this issue in the future:

1. **Always track uploaded files** during submission
2. **Pass file list to cleanup functions** for deletion
3. **Use consistent directory structure** for all uploads
4. **Monitor `uploads/` directory** for orphaned files
5. **Run status check regularly** to detect accumulation

## Alternative Solutions Considered

### Option 1: Use Same Directory for Draft and Submission
- Change `UPLOAD_DIR` to `uploads/drafts/` for both
- **Rejected**: Would mix draft and submission files

### Option 2: Delete Immediately After PDF Generation
- Delete files right after PDF is created
- **Rejected**: If email fails, files are already gone (no retry possible)

### Option 3: Move Files Instead of Copy
- Move draft files to uploads/ during submission
- **Rejected**: Complicates draft management

### Selected: Track and Delete After Email ✓
- Keep files until email succeeds
- Delete only after confirmation
- Allows retry if email fails
- Clean separation of concerns

## Verification Commands

Check for orphaned files:
```bash
php check-status.php
```

Clean all orphaned files:
```bash
php cleanup-all-now.php
```

List submission files manually:
```bash
dir uploads\car_photo_*
```

## Summary

Fixed the issue where `car_photo_*` files were being created in `uploads/` during form submission but never deleted after successful email delivery. The cleanup system now properly tracks and deletes these files in Step 2 of the post-email cleanup process.

**Total space recovered**: 3.34 MB from 4 orphaned files
**Status**: System is now CLEAN ✓
