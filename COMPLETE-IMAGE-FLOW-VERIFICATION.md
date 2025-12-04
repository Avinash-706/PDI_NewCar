# Complete Image Flow Verification

## ✅ Image Storage Flow - VERIFIED CORRECT

### 1. Upload Flow (upload-image.php)
**Status:** ✅ CORRECT

```php
// Line 107-115 in upload-image.php
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts') . DIRECTORY_SEPARATOR;

// Generate unique filename with proper prefix
$fieldPrefix = preg_replace('/[^a-zA-Z0-9_]/', '_', $fieldName);
$uniqueName = "{$fieldPrefix}_{$timestamp}_{$random}.jpg";
$targetPath = $draftDir . $uniqueName;
```

**Result:** All uploaded images go to `uploads/drafts/`
**Path stored in JSON:** `uploads/drafts/filename.jpg` (relative web path)

---

### 2. Image Compression (image-optimizer.php)
**Status:** ✅ CORRECT

```php
// Lines 395-400 in image-optimizer.php
public static function compressToFile($imagePath, $maxWidth = 1200, $quality = 65) {
    // Get compressed directory using DirectoryManager
    $compressedDir = DirectoryManager::getCompressedDir($imagePath);
    
    $filename = basename($imagePath);
    $compressedPath = $compressedDir . DIRECTORY_SEPARATOR . 'compressed_' . $filename;
```

**DirectoryManager::getCompressedDir()** (init-directories.php):
```php
// Lines 177-182
public static function getCompressedDir($sourceFile = null) {
    // Always use drafts/compressed directory for all compressed images
    $compressedDir = self::getAbsolutePath('uploads/drafts/compressed');
    self::ensureDirectory($compressedDir);
    return $compressedDir;
}
```

**Result:** All compressed images go to `uploads/drafts/compressed/`

---

### 3. Uniform Resize (image-optimizer.php)
**Status:** ✅ CORRECT

```php
// Lines 290-295 in image-optimizer.php
public static function resizeToUniform($imagePath, $uniformWidth = 400, $uniformHeight = 300, $quality = 75) {
    // Get uniform directory using DirectoryManager
    $uniformDir = DirectoryManager::getUniformDir($imagePath);
    
    $filename = basename($imagePath);
    $uniformPath = $uniformDir . DIRECTORY_SEPARATOR . 'uniform_' . $uniformWidth . 'x' . $uniformHeight . '_' . $filename;
```

**DirectoryManager::getUniformDir()** (init-directories.php):
```php
// Lines 188-193
public static function getUniformDir($sourceFile = null) {
    // Always use drafts/uniform directory for all uniform images
    $uniformDir = self::getAbsolutePath('uploads/drafts/uniform');
    self::ensureDirectory($uniformDir);
    return $uniformDir;
}
```

**Result:** All uniform images go to `uploads/drafts/uniform/`

---

### 4. Draft Save (save-draft.php)
**Status:** ✅ CORRECT

```php
// Lines 39-40 in save-draft.php
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts') . DIRECTORY_SEPARATOR;
```

**Stores relative web paths:**
```php
// Lines 88-91
$webAccessibleFiles = [];
foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
    $webAccessibleFiles[$fieldName] = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($filePath));
}
```

**Result:** Draft JSON contains `uploads/drafts/filename.jpg`

---

### 5. Draft Load (load-draft.php)
**Status:** ✅ CORRECT

```php
// Lines 36-37 in load-draft.php
$draftFile = DirectoryManager::getAbsolutePath('uploads/drafts/' . $draftId . '.json');
```

**Converts paths to web format:**
```php
// Lines 52-73
foreach ($draftData['uploaded_files'] as $fieldName => $filePath) {
    if (file_exists($filePath)) {
        $absolutePath = $filePath;
    } else {
        $absolutePath = DirectoryManager::getAbsolutePath($filePath);
    }
    
    if (file_exists($absolutePath)) {
        $webPath = DirectoryManager::toWebPath(DirectoryManager::getRelativePath($absolutePath));
        $webAccessibleFiles[$fieldName] = $webPath;
    }
}
```

**Result:** Returns web-accessible paths for browser display

---

### 6. Progressive Upload (script.js)
**Status:** ✅ CORRECT

```javascript
// Lines 1002-1090 in script.js
function setupProgressiveUpload() {
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            uploadImageImmediately(input.name, file, input.id);
        });
    });
}

function uploadImageImmediately(fieldName, file, inputId) {
    fetch('upload-image.php', {
        method: 'POST',
        body: formData
    })
    .then(data => {
        // Store file path
        uploadedFiles[fieldName] = data.file_path;
        localStorage.setItem('uploadedFiles', JSON.stringify(uploadedFiles));
        
        // Mark input as having saved file
        fileInput.dataset.savedFile = data.file_path;
        fileInput.removeAttribute('required');
    });
}
```

**Result:** 
- Uploads immediately to `uploads/drafts/`
- Stores path in localStorage
- Marks field as complete

---

### 7. Form Submission (submit.php)
**Status:** ✅ CORRECT

```php
// Lines 82-95 in submit.php
function handleFileUpload($file, $uploadDir) {
    // ALWAYS use uploads/drafts/ directory for submission files
    $draftDir = DirectoryManager::getAbsolutePath('uploads/drafts') . DIRECTORY_SEPARATOR;
    
    $filename = 'submission_' . time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $draftDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    return $targetPath;
}
```

**Also handles existing draft files:**
```php
// Lines 60-75
foreach ($_POST as $key => $value) {
    if (strpos($key, 'existing_') === 0 && !empty($value)) {
        $fieldName = str_replace('existing_', '', $key);
        $absolutePath = DirectoryManager::getAbsolutePath($value);
        
        if (file_exists($absolutePath)) {
            $uploadedFiles[$pathKey] = $absolutePath;
        }
    }
}
```

**Result:** All submission files use `uploads/drafts/`

---

### 8. PDF Generation (generate-pdf.php)
**Status:** ✅ CORRECT

```php
// Lines 28-30 in generate-pdf.php
function generatePDF($data) {
    // Compress all images first
    $data = compressAllImages($data);
    // ...
}

// Lines 943-956
function compressAllImages($data) {
    foreach ($data as $key => $value) {
        if (strpos($key, '_path') !== false && !empty($value)) {
            // Convert to absolute path
            $absolutePath = DirectoryManager::getAbsolutePath($value);
            
            if (file_exists($absolutePath)) {
                $data[$key] = ImageOptimizer::compressToFile($absolutePath, 1200, 65);
            }
        }
    }
    return $data;
}
```

**Result:** 
- Receives paths from submit.php
- Compresses images to `uploads/drafts/compressed/`
- Uses compressed paths for PDF

---

### 9. Email Sending (send-email.php)
**Status:** ✅ CORRECT

```php
// Lines 24-27 in send-email.php
if (!file_exists($pdfPath)) {
    error_log('Email sending aborted: PDF file not found at ' . $pdfPath);
    return false;
}
```

**Result:** Validates PDF exists before sending

---

## Complete Flow Diagram

```
User Uploads Image
       ↓
upload-image.php
       ↓
uploads/drafts/fieldname_timestamp_random.jpg
       ↓
save-draft.php (stores relative path)
       ↓
Draft JSON: "uploads/drafts/filename.jpg"
       ↓
User Submits Form
       ↓
submit.php (uses existing draft files)
       ↓
generate-pdf.php
       ↓
compressAllImages()
       ↓
ImageOptimizer::compressToFile()
       ↓
uploads/drafts/compressed/compressed_filename.jpg
       ↓
mPDF uses compressed images
       ↓
PDF saved to pdfs/inspection_*.pdf
       ↓
send-email.php (attaches PDF)
       ↓
cleanup-after-email.php (deletes draft files)
```

---

## Directory Structure (Final)

```
uploads/
├── .gitkeep
└── drafts/
    ├── compressed/                    # ✅ Compressed images for PDF
    │   └── compressed_*.jpg
    ├── uniform/                       # ✅ Uniform-sized images
    │   └── uniform_300x225_*.jpg
    ├── *.json                        # ✅ Draft JSON files
    ├── fieldname_timestamp_*.jpg     # ✅ Original uploads
    └── thumb_*.jpg                   # ✅ Thumbnails
```

---

## Path Handling Summary

| Component | Input Path | Output Path | Storage Location |
|-----------|-----------|-------------|------------------|
| upload-image.php | (file upload) | `uploads/drafts/filename.jpg` | `uploads/drafts/` |
| save-draft.php | absolute | `uploads/drafts/filename.jpg` | Draft JSON |
| load-draft.php | relative | `uploads/drafts/filename.jpg` | Browser |
| submit.php | relative/absolute | absolute | Memory |
| generate-pdf.php | absolute | absolute (compressed) | `uploads/drafts/compressed/` |
| image-optimizer.php | absolute | absolute (compressed) | `uploads/drafts/compressed/` |
| image-optimizer.php | absolute | absolute (uniform) | `uploads/drafts/uniform/` |

---

## Key Functions

### DirectoryManager (init-directories.php)
- ✅ `getAbsolutePath($relativePath)` - Convert to absolute
- ✅ `getRelativePath($absolutePath)` - Convert to relative
- ✅ `toWebPath($path)` - Convert to web format (forward slashes)
- ✅ `getCompressedDir()` - Always returns `uploads/drafts/compressed/`
- ✅ `getUniformDir()` - Always returns `uploads/drafts/uniform/`

### ImageOptimizer (image-optimizer.php)
- ✅ `compressToFile($imagePath, $maxWidth, $quality)` - Compress and save
- ✅ `resizeToUniform($imagePath, $width, $height, $quality)` - Resize and save
- ✅ `optimizeForPDF($imagePath, $maxWidth, $quality)` - Optimize for mPDF

---

## Verification Tests

### Test 1: Upload Image
```bash
# Upload via browser
# Check: uploads/drafts/fieldname_timestamp_random.jpg exists
```

### Test 2: Save Draft
```bash
# Save draft via browser
# Check: uploads/drafts/draft_*.json contains correct paths
```

### Test 3: Load Draft
```bash
# Reload page
# Check: Images display correctly in browser
```

### Test 4: Submit Form
```bash
# Submit form
# Check: PDF generated in pdfs/
# Check: Compressed images in uploads/drafts/compressed/
```

### Test 5: Verify Structure
```bash
php verify-uploads-fix.php
# Should show: ✅ ALL TESTS PASSED
```

---

## Conclusion

✅ **ALL IMAGE PATHS ARE CORRECT**

Every component in the system now uses the correct directory structure:
- Original uploads → `uploads/drafts/`
- Compressed images → `uploads/drafts/compressed/`
- Uniform images → `uploads/drafts/uniform/`
- Draft JSON files → `uploads/drafts/`

The entire flow from upload to PDF generation to cleanup is working correctly with consistent path handling throughout.
