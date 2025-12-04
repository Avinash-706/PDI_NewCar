# Folder Cleanup - Quick Reference Card

## 🚀 3-Step Process

### Step 1: Dry Run (Safe)
```bash
php cleanup-folder-structure.php --dry-run
```
✅ Shows what will be removed  
✅ No actual changes  

### Step 2: Execute
```bash
php cleanup-folder-structure.php
```
✅ Removes 6 unused folders  
✅ Creates automatic backup  

### Step 3: Test
```bash
php test-folder-structure.php
```
✅ Verifies everything works  
✅ 25+ test cases  

---

## 📁 What Gets Removed

| Folder | Reason | Files |
|--------|--------|-------|
| `drafts/logs/` | Duplicate of `/logs` | 0 |
| `drafts/pdfs/` | Duplicate of `/pdfs` | 0 |
| `drafts/uploads/` | Duplicate of `/uploads` | 0 |
| `uploads/compressed/` | Not used (empty) | 0 |
| `uploads/uniform/` | Not used (empty) | 0 |
| `templates/` | Empty, not referenced | 0 |

**Total:** 6 folders, 0 files

---

## ✅ What Stays

| Folder | Purpose |
|--------|---------|
| `drafts/audit/` | Audit logs |
| `logs/` | Application logs |
| `pdfs/` | Generated PDFs |
| `tmp/mpdf/` | mPDF temp |
| `uploads/drafts/` | Draft storage |
| `uploads/drafts/compressed/` | Compressed images |
| `uploads/drafts/uniform/` | Uniform images |

**Total:** 8 folders (all essential)

---

## 🔧 Troubleshooting

### "Folder contains files"
```bash
php cleanup-folder-structure.php --force
```

### "Permission denied"
```bash
chmod -R 755 drafts/ uploads/
php cleanup-folder-structure.php
```

### "Test failed"
```bash
php -r "require 'init-directories.php'; DirectoryManager::init();"
```

---

## 🔄 Rollback

### Option 1: Restore Backup
```bash
tar -xzf folder-backup-YYYYMMDD.tar.gz
```

### Option 2: Recreate Folders
```bash
mkdir -p drafts/logs drafts/pdfs drafts/uploads
mkdir -p uploads/compressed uploads/uniform templates
```

---

## 📊 Quick Stats

- **Folders removed:** 6
- **Files deleted:** 0
- **Code changes:** 2 lines
- **Risk level:** LOW ✅
- **Time required:** 5-10 min
- **Rollback:** Easy

---

## ✅ Verification

After cleanup, test:
- [ ] Upload image
- [ ] Save draft
- [ ] Load draft
- [ ] Generate PDF
- [ ] Discard draft

All should work perfectly!

---

## 📞 Help

```bash
# View logs
tail -f logs/error.log

# Check health
php -r "require 'init-directories.php'; print_r(DirectoryManager::checkHealth());"

# Run tests
php test-folder-structure.php
```

---

## 📚 Full Documentation

- `FOLDER-STRUCTURE-AUDIT.md` - Detailed audit
- `FOLDER-CLEANUP-GUIDE.md` - Step-by-step guide
- `FOLDER-OPTIMIZATION-SUMMARY.md` - Executive summary

---

**Status:** ✅ Ready  
**Risk:** LOW  
**Time:** 5-10 min
