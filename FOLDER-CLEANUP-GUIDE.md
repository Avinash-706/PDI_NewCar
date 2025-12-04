# Folder Structure Cleanup - Implementation Guide

## 🎯 Quick Summary

**Goal:** Remove 6 unused/duplicate folders to optimize project structure

**Risk Level:** LOW ✅  
**Time Required:** 5-10 minutes  
**Backup Required:** YES (automatic)  

---

## 📋 What Will Be Removed

### Folders to Delete (6 total):
1. ❌ `drafts/logs/` - Duplicate of `/logs`
2. ❌ `drafts/pdfs/` - Duplicate of `/pdfs`
3. ❌ `drafts/uploads/` - Duplicate of `/uploads`
4. ❌ `uploads/compressed/` - Not used (empty)
5. ❌ `uploads/uniform/` - Not used (empty)
6. ❌ `templates/` - Empty, not referenced

### What Stays (8 folders):
1. ✅ `drafts/audit/` - Audit logs
2. ✅ `logs/` - Application logs
3. ✅ `pdfs/` - Generated PDFs
4. ✅ `scripts/` - Utility scripts
5. ✅ `tmp/mpdf/` - mPDF temporary files
6. ✅ `uploads/drafts/` - Draft JSON and images
7. ✅ `uploads/drafts/compressed/` - Compressed images
8. ✅ `uploads/drafts/uniform/` - Uniform-sized images

---

## 🚀 Implementation Steps

### Step 1: Run Dry Run (Safe - No Changes)

```bash
php cleanup-folder-structure.php --dry-run
```

**What it does:**
- Shows what would be removed
- Checks for any files in folders
- No actual changes made

**Expected output:**
```
=== Folder Structure Cleanup ===

🔍 DRY RUN MODE - No changes will be made

Step 1: Verifying essential folders...
  ✓ drafts/audit - Audit logs
  ✓ logs - Application logs
  ✓ pdfs - Generated PDFs
  ...

Step 2: Checking folders for files before removal...
  ✓ drafts/logs - Empty - Duplicate of /logs
  ✓ drafts/pdfs - Empty - Duplicate of /pdfs
  ...

Step 3: Would remove the following folders:
  ✓ Would remove drafts/logs
  ✓ Would remove drafts/pdfs
  ...

=== Summary ===
Folders checked: 8
Folders removed: 0
Folders skipped: 0
Errors: 0
Files found in folders: 0
```

---

### Step 2: Execute Cleanup

```bash
php cleanup-folder-structure.php
```

**What it does:**
- Removes empty unused folders
- Skips folders with files (unless --force)
- Creates automatic backup

**Expected output:**
```
=== Folder Structure Cleanup ===

Step 1: Verifying essential folders...
  ✓ drafts/audit - Audit logs
  ...

Step 2: Checking folders for files before removal...
  ✓ drafts/logs - Empty - Duplicate of /logs
  ...

Step 3: Removing unused folders...
  ✓ Removed drafts/logs
  ✓ Removed drafts/pdfs
  ✓ Removed drafts/uploads
  ✓ Removed uploads/compressed
  ✓ Removed uploads/uniform
  ✓ Removed templates

=== Summary ===
Folders checked: 8
Folders removed: 6
Folders skipped: 0
Errors: 0
Files found in folders: 0

✅ Cleanup complete! Folder structure optimized.
```

---

### Step 3: Verify Structure

```bash
php test-folder-structure.php
```

**What it does:**
- Checks all required folders exist
- Verifies removed folders are gone
- Tests folder permissions
- Validates DirectoryManager functions
- Checks code references

**Expected output:**
```
=== Folder Structure Test ===

Test 1: Checking required folders...
  ✓ uploads/drafts exists (Draft storage)
  ✓ uploads/drafts/compressed exists (Compressed images)
  ✓ uploads/drafts/uniform exists (Uniform images)
  ...

Test 2: Verifying removed folders are gone...
  ✓ drafts/logs removed (Should be removed)
  ✓ drafts/pdfs removed (Should be removed)
  ...

Test 3: Checking folder permissions...
  ✓ uploads/drafts is writable
  ...

Test 4: Testing DirectoryManager functions...
  ✓ getAbsolutePath() works
  ✓ getCompressedDir() works
  ✓ getUniformDir() works
  ✓ checkHealth() works

Test 5: Checking for orphaned files...
  ✓ No orphaned files found

Test 6: Verifying code references...
  ✓ upload-image.php has correct path references
  ✓ save-draft.php has correct path references
  ...

=== Test Summary ===
Passed: 25
Failed: 0
Warnings: 0

✅ All tests passed! Folder structure is optimized and working correctly.
```

---

### Step 4: Test Functionality

Test each major feature:

#### A. Upload Image
1. Go to the form
2. Upload an image in any step
3. Verify it saves to `uploads/drafts/`

#### B. Save Draft
1. Fill out some form fields
2. Click "Save Draft"
3. Verify draft JSON is created in `uploads/drafts/`

#### C. Load Draft
1. Reload the page
2. Draft should auto-load
3. Verify all data and images are restored

#### D. Generate PDF
1. Fill out the form
2. Click "T-Submit" (test submit)
3. Verify PDF is created in `pdfs/`

#### E. Discard Draft
1. Click "Discard Draft"
2. Verify draft JSON is deleted
3. Verify all images are deleted
4. Verify form resets

---

## 🔧 Troubleshooting

### Issue 1: "Folder contains files"

**Symptom:**
```
⚠ drafts/logs - Contains 2 file(s)
→ Use --force to delete folders with files
```

**Solution:**
```bash
# Check what files are in the folder
ls -la drafts/logs/

# If safe to delete, use --force
php cleanup-folder-structure.php --force
```

---

### Issue 2: "Permission denied"

**Symptom:**
```
✗ Failed to remove drafts/logs
```

**Solution:**
```bash
# Fix permissions
chmod -R 755 drafts/logs
chmod -R 755 uploads/compressed
chmod -R 755 uploads/uniform

# Try again
php cleanup-folder-structure.php
```

---

### Issue 3: "Test failed"

**Symptom:**
```
✗ uploads/drafts MISSING!
```

**Solution:**
```bash
# Reinitialize directories
php -r "require 'init-directories.php'; DirectoryManager::init();"

# Or manually create
mkdir -p uploads/drafts/compressed
mkdir -p uploads/drafts/uniform
```

---

## 📊 Before & After Comparison

### Before Cleanup:
```
project/
├── drafts/
│   ├── audit/              ✅ KEEP
│   ├── logs/               ❌ REMOVE (duplicate)
│   ├── pdfs/               ❌ REMOVE (duplicate)
│   └── uploads/            ❌ REMOVE (duplicate)
│       └── drafts/         ❌ REMOVE (nested)
├── logs/                   ✅ KEEP
├── pdfs/                   ✅ KEEP
├── templates/              ❌ REMOVE (empty)
├── tmp/                    ✅ KEEP
│   └── mpdf/              ✅ KEEP
└── uploads/
    ├── compressed/         ❌ REMOVE (not used)
    │   └── uniform/       ❌ REMOVE (wrong location)
    ├── drafts/             ✅ KEEP
    │   ├── compressed/    ✅ KEEP
    │   └── uniform/       ✅ KEEP
    └── uniform/            ❌ REMOVE (not used)
```

### After Cleanup:
```
project/
├── drafts/
│   ├── audit/              ✅ Audit logs
│   └── *.php              ✅ PHP scripts
├── logs/                   ✅ Application logs
├── pdfs/                   ✅ Generated PDFs
├── scripts/                ✅ Utility scripts
├── tmp/                    ✅ Temporary files
│   └── mpdf/              ✅ mPDF temp
└── uploads/
    └── drafts/             ✅ Draft storage
        ├── compressed/     ✅ Compressed images
        ├── uniform/        ✅ Uniform images
        ├── *.json         ✅ Draft JSON
        └── *.jpg          ✅ Draft images
```

---

## ✅ Verification Checklist

After cleanup, verify:

- [ ] All 6 folders removed
- [ ] All 8 essential folders still exist
- [ ] All folders are writable
- [ ] Upload image works
- [ ] Save draft works
- [ ] Load draft works
- [ ] Generate PDF works
- [ ] Discard draft works
- [ ] No errors in logs
- [ ] Test script passes all tests

---

## 🔄 Rollback (If Needed)

If something goes wrong:

### Option 1: Restore from Backup
```bash
# Backup is created automatically
tar -xzf folder-backup-YYYYMMDD.tar.gz
```

### Option 2: Recreate Folders
```bash
# Recreate removed folders
mkdir -p drafts/logs
mkdir -p drafts/pdfs
mkdir -p drafts/uploads
mkdir -p uploads/compressed
mkdir -p uploads/uniform
mkdir -p templates

# Reinitialize
php -r "require 'init-directories.php'; DirectoryManager::init();"
```

### Option 3: Revert Code Changes
```bash
# Revert init-directories.php
git checkout init-directories.php
```

---

## 📝 Files Modified

### 1. `init-directories.php` ✅
**Changed:**
- Removed `'uploads/compressed'` from required directories
- Removed `'uploads/uniform'` from required directories

**Why:**
- These folders are not used
- Images are stored in `uploads/drafts/compressed/` and `uploads/drafts/uniform/` instead

### 2. No Other Files Modified ✅
All other files already use correct paths!

---

## 🎉 Benefits

### 1. Cleaner Structure
- No duplicate folders
- Clear naming conventions
- Single source of truth

### 2. Easier Maintenance
- Less confusion
- Simpler backups
- Easier debugging

### 3. Better Performance
- Fewer directories to scan
- Faster file operations
- Reduced disk I/O

### 4. Improved Reliability
- No ambiguity
- Consistent paths
- Easier to understand

---

## 📞 Support

### If You Need Help:

1. **Check logs:**
   ```bash
   tail -f logs/error.log
   ```

2. **Run diagnostics:**
   ```bash
   php test-folder-structure.php
   ```

3. **Check folder health:**
   ```bash
   php -r "require 'init-directories.php'; print_r(DirectoryManager::checkHealth());"
   ```

4. **Verify permissions:**
   ```bash
   ls -la uploads/drafts/
   ls -la drafts/audit/
   ```

---

## 🎯 Quick Commands Reference

```bash
# Dry run (safe, no changes)
php cleanup-folder-structure.php --dry-run

# Execute cleanup
php cleanup-folder-structure.php

# Force delete folders with files
php cleanup-folder-structure.php --force

# Test structure
php test-folder-structure.php

# Check health
php -r "require 'init-directories.php'; print_r(DirectoryManager::checkHealth());"

# View logs
tail -f logs/error.log
```

---

**Status:** Ready for implementation  
**Risk:** LOW ✅  
**Time:** 5-10 minutes  
**Backup:** Automatic  
**Rollback:** Easy
