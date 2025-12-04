# Uploads Folder - Quick Reference

## ✅ Correct Structure

```
uploads/
└── drafts/
    ├── compressed/      # Compressed images
    ├── uniform/         # Uniform-sized images
    ├── *.json          # Draft files
    ├── *.jpg           # Original images
    └── thumb_*.jpg     # Thumbnails
```

## 🚫 Wrong Locations (Don't Use)

- ❌ `uploads/` root (except .gitkeep)
- ❌ `uploads/compressed/`
- ❌ `uploads/uniform/`

## 📝 Code Examples

### Upload New Image
```php
// Get draft directory
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts/');

// Save file
$targetPath = $draftDir . $filename;
move_uploaded_file($tmpFile, $targetPath);

// Store relative path in JSON
$relativePath = DirectoryManager::toWebPath(
    DirectoryManager::getRelativePath($targetPath)
);
```

### Compress Image
```php
// Automatically saves to uploads/drafts/compressed/
$compressedPath = ImageOptimizer::compressToFile($imagePath, 1200, 70);
```

### Resize to Uniform
```php
// Automatically saves to uploads/drafts/uniform/
$uniformPath = ImageOptimizer::resizeToUniform($imagePath, 400, 300, 75);
```

### Get Correct Directories
```php
// Always returns uploads/drafts/compressed/
$compressedDir = DirectoryManager::getCompressedDir();

// Always returns uploads/drafts/uniform/
$uniformDir = DirectoryManager::getUniformDir();
```

## 🔧 Maintenance Commands

### Verify Structure
```bash
php verify-uploads-fix.php
```

### Fix Wrong Files
```bash
php fix-uploads-structure.php
```

### Test Directories
```bash
php test-directory-system.php
```

## 📋 File Naming

| Type | Pattern | Example |
|------|---------|---------|
| Original | `{field}_{time}_{random}.jpg` | `car_photo_1764784799_6834b6d2.jpg` |
| Compressed | `compressed_{original}` | `compressed_car_photo_1764784799_6834b6d2.jpg` |
| Uniform | `uniform_{w}x{h}_{original}` | `uniform_300x225_compressed_car_photo_1764784799_6834b6d2.jpg` |
| Thumbnail | `thumb_{original}` | `thumb_car_photo_1764784799_6834b6d2.jpg` |

## 🎯 Key Rules

1. **All uploads** → `uploads/drafts/`
2. **Compressed images** → `uploads/drafts/compressed/`
3. **Uniform images** → `uploads/drafts/uniform/`
4. **Store relative paths** in JSON (e.g., `uploads/drafts/file.jpg`)
5. **Use DirectoryManager** for all path operations

## 📚 Documentation

- `UPLOADS-FOLDER-STRUCTURE.md` - Complete guide
- `UPLOADS-FIX-SUMMARY.md` - What was fixed
- `UPLOADS-QUICK-REFERENCE.md` - This file
