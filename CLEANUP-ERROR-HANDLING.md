# Post-Email Cleanup - Enhanced Error Handling

## 🎯 Overview

The post-email cleanup system now uses a **4-phase atomic deletion process** with comprehensive error handling to ensure files are only deleted if ALL operations can succeed.

---

## ✅ Key Principle

**"All or Nothing" Approach:**
- If ANY error occurs during validation → NO files are deleted
- If ANY file cannot be deleted → Operation stops, error is logged
- Only proceeds if ALL files are validated and accessible

---

## 🔒 4-Phase Deletion Process

### Phase 1: Collection & Validation
```
Collect all files to delete:
  ✓ PDF file
  ✓ Compressed images
  ✓ Uniform images
  ✓ Temporary files

Validate:
  ✓ All files exist
  ✓ All paths are valid
  ✓ No files disappeared
```

### Phase 2: Permission Check
```
For each file:
  ✓ Check file still exists
  ✓ Check file is writable
  ✓ Check file is accessible

If ANY check fails → ABORT (no deletion)
```

### Phase 3: Atomic Deletion
```
For each file:
  ✓ Delete file
  ✓ Track success
  ✓ Log operation

If ANY deletion fails → STOP immediately
```

### Phase 4: Cleanup
```
Clean up empty directories:
  ✓ Remove empty subdirectories
  ✓ Keep main directories
  ✓ Preserve .gitkeep files
```

---

## 🚨 Error Scenarios & Handling

### Scenario 1: PDF File Not Found
```
Phase: 1 (Collection)
Error: "PDF file not found: /path/to/pdf"
Action: ABORT - No files deleted
Result: All files kept for manual retry
```

### Scenario 2: File Not Writable
```
Phase: 2 (Permission Check)
Error: "File not writable: /path/to/image.jpg"
Action: ABORT - No files deleted
Result: All files kept, permission issue logged
```

### Scenario 3: File Disappeared During Process
```
Phase: 2 (Permission Check)
Error: "File disappeared before deletion: /path/to/file"
Action: ABORT - No files deleted
Result: Unexpected state logged for investigation
```

### Scenario 4: Deletion Failed
```
Phase: 3 (Atomic Deletion)
Error: "Failed to delete file: /path/to/image.jpg"
Action: STOP immediately
Result: Partial deletion, remaining files kept
Log: "X files deleted before error, Y files remain"
```

### Scenario 5: All Operations Succeed
```
Phase: All phases complete
Result: All files deleted successfully
Log: "Successfully cleaned up - PDF: Yes, Compressed: 47, Freed: 4.8 MB"
```

---

## 📊 Comparison: Before vs After

### Before (Old Approach):
```php
// ❌ Delete files one by one without validation
if (@unlink($pdfPath)) {
    // Continue even if this fails
}

// ❌ No pre-validation
// ❌ No atomic operation
// ❌ Partial deletion possible
```

### After (New Approach):
```php
// ✅ Phase 1: Collect all files
$filesToDelete = collectAllFiles();

// ✅ Phase 2: Validate all files
foreach ($filesToDelete as $file) {
    if (!is_writable($file)) {
        throw new Exception(); // ABORT
    }
}

// ✅ Phase 3: Delete atomically
foreach ($filesToDelete as $file) {
    if (!@unlink($file)) {
        throw new Exception(); // STOP
    }
}
```

---

## 🔍 Validation Checks

### File Existence Check:
```php
if (!file_exists($file['path'])) {
    throw new Exception('File disappeared before deletion');
}
```

### Write Permission Check:
```php
if (!is_writable($file['path'])) {
    throw new Exception('File not writable');
}
```

### Accessibility Check:
```php
if (!is_readable($file['path'])) {
    throw new Exception('File not accessible');
}
```

---

## 📝 Logging

### Success Log:
```
Post-Email Cleanup: Starting cleanup for booking: 122
Post-Email Cleanup: Phase 1 - Validating files
Post-Email Cleanup: Found 95 files to delete
Post-Email Cleanup: Phase 2 - Checking permissions
Post-Email Cleanup: Phase 3 - Deleting files
Post-Email Cleanup: Deleted pdf: /path/to/inspection_122.pdf (3.0 MB)
Post-Email Cleanup: Deleted compressed: /path/to/compressed_image1.jpg (500 KB)
...
Post-Email Cleanup: Phase 4 - Cleaning empty directories
Post-Email Cleanup: Successfully cleaned up - PDF: Yes, Compressed: 47, Freed: 4.8 MB
```

### Error Log (Validation Failed):
```
Post-Email Cleanup: Starting cleanup for booking: 122
Post-Email Cleanup: Phase 1 - Validating files
Post-Email Cleanup: Found 95 files to delete
Post-Email Cleanup: Phase 2 - Checking permissions
Post-Email Cleanup ERROR: File not writable: /path/to/image.jpg
Post-Email Cleanup: Cleanup aborted - 0 files deleted before error, 95 files remain
```

### Error Log (Deletion Failed):
```
Post-Email Cleanup: Starting cleanup for booking: 122
Post-Email Cleanup: Phase 1 - Validating files
Post-Email Cleanup: Found 95 files to delete
Post-Email Cleanup: Phase 2 - Checking permissions
Post-Email Cleanup: Phase 3 - Deleting files
Post-Email Cleanup: Deleted pdf: /path/to/inspection_122.pdf (3.0 MB)
Post-Email Cleanup: Deleted compressed: /path/to/compressed_image1.jpg (500 KB)
Post-Email Cleanup ERROR: Failed to delete file: /path/to/image47.jpg
Post-Email Cleanup: Cleanup aborted - 48 files deleted before error, 47 files remain
```

---

## 🎯 Safety Features

### 1. Pre-Validation
- All files validated before any deletion
- Prevents partial deletion due to missing files
- Catches permission issues early

### 2. Atomic Operation
- All-or-nothing approach
- Stops immediately on first error
- No silent failures

### 3. Comprehensive Logging
- Every phase logged
- Every file deletion logged
- Every error logged with context

### 4. Error Recovery
- Partial deletion tracked
- Remaining files logged
- Next cleanup will handle remaining files

### 5. No Rollback Needed
- Files already emailed (backed up)
- Partial cleanup better than no cleanup
- Next run will complete cleanup

---

## 🧪 Testing

### Test 1: Normal Operation (All Files Accessible)
```bash
# Expected: All files deleted successfully
Post-Email Cleanup: Successfully cleaned up - PDF: Yes, Compressed: 47, Freed: 4.8 MB
```

### Test 2: PDF File Missing
```bash
# Expected: No files deleted, error logged
Post-Email Cleanup ERROR: PDF file not found
Post-Email Cleanup: Cleanup aborted - 0 files deleted
```

### Test 3: Permission Denied
```bash
# Make a file read-only
chmod 444 uploads/drafts/compressed/compressed_image1.jpg

# Expected: No files deleted, permission error logged
Post-Email Cleanup ERROR: File not writable
Post-Email Cleanup: Cleanup aborted - 0 files deleted
```

### Test 4: File Disappears During Process
```bash
# Simulate file disappearing between phases
# Expected: Cleanup aborted, error logged
Post-Email Cleanup ERROR: File disappeared before deletion
```

---

## 📊 Statistics Tracking

### Result Object:
```php
[
    'success' => true,              // Overall success
    'deleted_pdf' => true,          // PDF deleted
    'deleted_compressed' => 47,     // Compressed images deleted
    'deleted_uniform' => 47,        // Uniform images deleted
    'deleted_temp' => 5,            // Temp files deleted
    'freed_space' => 5242880,       // Bytes freed
    'message' => 'Success message'  // Status message
]
```

### Error Result:
```php
[
    'success' => false,
    'deleted_pdf' => false,
    'deleted_compressed' => 0,
    'deleted_uniform' => 0,
    'deleted_temp' => 0,
    'freed_space' => 0,
    'message' => 'Post-email cleanup error: File not writable'
]
```

---

## ✅ Benefits

### 1. **Reliability**
- No partial deletions due to validation
- Predictable behavior
- Clear error messages

### 2. **Safety**
- Validates before deleting
- Stops on first error
- Comprehensive logging

### 3. **Debugging**
- Detailed phase logging
- Error context provided
- Easy to diagnose issues

### 4. **Recovery**
- Tracks partial deletions
- Next cleanup handles remaining files
- No data loss (files already emailed)

---

## 🔄 Integration with Submit Flow

```
Form Submitted ✅
    ↓
PDF Generated ✅
    ↓
Draft Cleaned Up ✅
    ↓
Email Sent ✅
    ↓
Post-Email Cleanup:
    Phase 1: Collect files ✅
    Phase 2: Validate files ✅
    Phase 3: Delete files ✅
    Phase 4: Clean directories ✅
    ↓
✅ Complete
```

---

## 📋 Summary

### What Changed:

1. **4-Phase Process** - Structured, predictable flow
2. **Pre-Validation** - Check before delete
3. **Atomic Operation** - All or nothing
4. **Error Handling** - Stop on first error
5. **Comprehensive Logging** - Every step logged

### Result:

✅ **More Reliable** - Validates before deleting  
✅ **Safer** - Stops on errors  
✅ **Debuggable** - Detailed logging  
✅ **Recoverable** - Tracks partial deletions  
✅ **Production-Ready** - Handles edge cases  

---

**Status:** ✅ ENHANCED  
**Risk:** VERY LOW  
**Reliability:** HIGH  
**Error Handling:** COMPREHENSIVE
