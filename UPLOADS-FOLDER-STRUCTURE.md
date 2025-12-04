# Uploads Folder Structure - FIXED

## Correct Structure

```
uploads/
├── .gitkeep
└── drafts/
    ├── compressed/          # Compressed versions of images
    │   └── compressed_*.jpg
    ├── uniform/             # Uniform-sized images for PDFs
    │   └── uniform_*.jpg
    ├── *.json              # Draft JSON files
    ├── *.jpg               # Original uploaded images
    └── thumb_*.jpg         # Thumbnail images
```

## Rules

### 1. All User Uploads Go to `uploads/drafts/`
- Original images: `uploads/drafts/filename.jpg`
- Draft JSON files: `uploads/drafts/draft_*.json`
- Thumbnails: `uploads/drafts/thumb_*.jpg`

### 2. Processed Images Use Subdirectories
- Compressed images: `uploads/drafts/compressed/compressed_*.jpg`
- Uniform-sized images: `uploads/drafts/uniform/uniform_*.jpg`

### 3. NO Files Should Be In
- ❌ `uploads/` root (except .gitkeep)
- ❌ `uploads/compressed/` (this directory should NOT exist)
- ❌ `uploads/uniform/` (this directory should NOT exist)
- ❌ `uploads/drafts/compressed/uniform/` (no nested subdirectories)

## File Naming Conventions

### Original Uploads (upload-image.php)
```
{fieldName}_{timestamp}_{random}.jpg
Example: car_photo_1764784799_6834b6d2.jpg
```

### Compressed Images (image-optimizer.php)
```
compressed_{originalFilename}
Example: compressed_car_photo_1764784799_6834b6d2.jpg
```

### Uniform Images (image-optimizer.php)
```
uniform_{width}x{height}_{originalFilename}
Example: uniform_300x225_compressed_car_photo_1764784799_6834b6d2.jpg
```

### Thumbnails (upload-image.php)
```
thumb_{originalFilename}
Example: thumb_car_photo_1764784799_6834b6d2.jpg
```

## Path Handling

### Storage in Draft JSON
All paths in draft JSON files should be **relative web paths**:
```json
{
    "uploaded_files": {
        "car_photo": "uploads/drafts/car_photo_1764784799_6834b6d2.jpg"
    }
}
```

### Path Conversion Functions (DirectoryManager)
- `getAbsolutePath($relativePath)` - Convert to absolute filesystem path
- `getRelativePath($absolutePath)` - Convert to relative path from project root
- `toWebPath($path)` - Convert to web-accessible path (forward slashes)

## Key Files and Their Responsibilities

### upload-image.php
- Receives single image upload
- Saves to `uploads/drafts/`
- Creates thumbnail in `uploads/drafts/`
- Updates draft JSON with relative path
- Deletes old image if field is being replaced

### image-optimizer.php
- `compressToFile()` - Saves to `uploads/drafts/compressed/`
- `resizeToUniform()` - Saves to `uploads/drafts/uniform/`
- Uses `DirectoryManager::getCompressedDir()` and `getUniformDir()`

### init-directories.php (DirectoryManager)
- `getCompressedDir()` - Always returns `uploads/drafts/compressed/`
- `getUniformDir()` - Always returns `uploads/drafts/uniform/`
- Creates directories if they don't exist

### submit.php
- Uses existing draft files (already in `uploads/drafts/`)
- Any new uploads during submission go to `uploads/drafts/`
- Passes absolute paths to generate-pdf.php

### generate-pdf.php
- Receives absolute paths
- Uses image-optimizer to compress/resize
- Processed images saved to correct subdirectories

## Cleanup After Submission

After successful email send, cleanup should delete:
1. Draft JSON file: `uploads/drafts/draft_*.json`
2. Original images: `uploads/drafts/*.jpg`
3. Thumbnails: `uploads/drafts/thumb_*.jpg`
4. Compressed images: `uploads/drafts/compressed/compressed_*.jpg`
5. Uniform images: `uploads/drafts/uniform/uniform_*.jpg`

## Migration Complete

The following fixes have been applied:

✅ Moved all files from `uploads/compressed/` to `uploads/drafts/compressed/`
✅ Moved all files from `uploads/compressed/uniform/` to `uploads/drafts/uniform/`
✅ Moved all files from `uploads/` root to `uploads/drafts/`
✅ Removed empty wrong directories
✅ Updated `DirectoryManager::getCompressedDir()` to always use `uploads/drafts/compressed/`
✅ Updated `DirectoryManager::getUniformDir()` to always use `uploads/drafts/uniform/`
✅ Updated `submit.php` to use `uploads/drafts/` for submission uploads
✅ Updated `upload-image.php` to use better field-based naming

## Testing

Run these commands to verify structure:
```bash
php test-directory-system.php
php test-folder-structure.php
```

## Maintenance

If files end up in wrong locations again:
```bash
php fix-uploads-structure.php
```

This will automatically move files to correct locations and update draft JSON files.
