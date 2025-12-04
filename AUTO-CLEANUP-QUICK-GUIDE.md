# Auto-Cleanup After Submission - Quick Guide

## 🎯 What It Does

Automatically deletes drafts and all associated images after successful form submission and PDF generation.

---

## ✅ When It Runs

**ONLY after:**
1. ✅ Form submission succeeds
2. ✅ PDF is generated successfully
3. ✅ PDF file exists on disk

**NEVER runs when:**
- ❌ Validation fails
- ❌ PDF generation fails
- ❌ Submission is interrupted
- ❌ Saving draft (not submitting)
- ❌ Test submissions (T-Submit)

---

## 📁 What Gets Deleted

For each successful submission:

1. **Draft JSON** - `draft_[ID].json`
2. **All Images** - Original + thumbnails + optimized
3. **Compressed Images** - In `compressed/` folder
4. **Uniform Images** - In `uniform/` folder
5. **Metadata** - Version files, backups, audit logs
6. **Empty Folders** - Cleaned up automatically

---

## 🔧 How It Works

### Workflow:
```
Submit Form
    ↓
Validate Data ✅
    ↓
Generate PDF ✅
    ↓
Save PDF ✅
    ↓
🧹 AUTO-CLEANUP 🧹
    ↓
Return Success
```

### Integration Points:

**1. Client Side (`script.js`):**
```javascript
// Sends draft_id with submission
formData.append('draft_id', draftId);
```

**2. Server Side (`submit.php`):**
```php
// After successful PDF generation
$pdfPath = generatePDF($formData);

if ($pdfPath && file_exists($pdfPath)) {
    // ✅ Cleanup draft
    cleanupAfterSubmission($draftId, $formData);
}
```

**3. Cleanup Function (`cleanup-after-submission.php`):**
```php
// Deletes everything
cleanupAfterSubmission($draftId, $formData);
```

---

## 📊 Example

### Before Submission:
```
uploads/drafts/
├── draft_123.json (5 KB)
├── image1.jpg (2 MB)
├── image2.jpg (2 MB)
├── thumb_image1.jpg (50 KB)
├── thumb_image2.jpg (50 KB)
├── compressed/
│   ├── compressed_image1.jpg (500 KB)
│   └── compressed_image2.jpg (500 KB)
└── uniform/
    ├── uniform_300x225_image1.jpg (100 KB)
    └── uniform_300x225_image2.jpg (100 KB)

Total: ~5.3 MB
```

### After Submission:
```
uploads/drafts/
└── (empty)

pdfs/
└── inspection_122_1234567890.pdf (3 MB) ✅

Space Freed: 5.3 MB
```

---

## 🧪 Testing

### Quick Test:
```bash
php test-auto-cleanup.php
```

### Manual Test:
1. Fill out form
2. Upload images
3. Save draft
4. Submit form
5. Check `uploads/drafts/` - should be empty
6. Check `pdfs/` - PDF should exist
7. Check logs - should show cleanup success

---

## 📝 Logs

### Success:
```
PDF generated successfully: /path/to/inspection_122.pdf
Cleanup: Starting cleanup for draft: draft_123
Cleanup: Deleted 47 images, 3 files, Freed 125.5 MB
```

### No Draft:
```
PDF generated successfully: /path/to/inspection_122.pdf
Cleanup: No draft ID provided, skipping cleanup
```

### Error (Non-Fatal):
```
PDF generated successfully: /path/to/inspection_122.pdf
Cleanup: Failed to clean up draft draft_123 - Permission denied
```

---

## 🔍 Monitoring

### Check Logs:
```bash
tail -f logs/error.log | grep Cleanup
```

### Check Disk Space:
```bash
du -sh uploads/drafts/
```

### Count Drafts:
```bash
ls -1 uploads/drafts/*.json | wc -l
```

---

## ⚙️ Configuration

### Disable Cleanup:
Comment out in `submit.php`:
```php
/*
$draftId = $_POST['draft_id'] ?? null;
if ($draftId) {
    require_once 'cleanup-after-submission.php';
    cleanupAfterSubmission($draftId, $formData);
}
*/
```

### Enable Debug:
Add to `cleanup-after-submission.php`:
```php
define('CLEANUP_DEBUG', true);
```

---

## 🎯 Benefits

✅ **Automatic** - No manual cleanup needed  
✅ **Safe** - Only runs after success  
✅ **Fast** - Immediate space recovery  
✅ **Reliable** - Comprehensive error handling  
✅ **Privacy** - Deletes customer data after submission  

---

## 📋 Files Modified

1. ✅ `cleanup-after-submission.php` - New cleanup function
2. ✅ `submit.php` - Integrated cleanup call
3. ✅ `script.js` - Sends draft_id with submission

---

## ✅ Status

**Implementation:** COMPLETE  
**Testing:** READY  
**Risk:** LOW  
**Impact:** HIGH  

---

## 📞 Quick Commands

```bash
# Test cleanup
php test-auto-cleanup.php

# Monitor logs
tail -f logs/error.log | grep Cleanup

# Check disk usage
du -sh uploads/drafts/

# Count drafts
ls -1 uploads/drafts/*.json 2>/dev/null | wc -l
```

---

**Ready for Production** ✅
