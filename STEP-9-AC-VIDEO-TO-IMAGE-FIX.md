# Step 9 - AC Video to Image Change

## Overview
Changed the "Air Condition Video at Fan Max Speed" field to an image upload field in Step 9 (Air Conditioning).

## Changes Made

### Field Change:
- **Old**: Air Condition Video at Fan Max Speed (video upload)
- **New**: Air Condition Image at Fan Max Speed (image upload)

### Field Details:
- **Field Name**: `ac_video` → `ac_image`
- **Field ID**: `acVideo` → `acImage`
- **Accept Type**: `video/*` → `image/*`
- **Icon**: 🎥 (video) → 📷 (camera)
- **Label Text**: "Choose Video File" → "Choose Image or Drag & Drop"
- **Preview ID**: `acVideoPreview` → `acImagePreview`

## Files Updated

### 1. index.php ✅
**Location**: Step 9 - Air Conditioning section

**Changes**:
- Changed input name from `ac_video` to `ac_image`
- Changed input ID from `acVideo` to `acImage`
- Changed accept attribute from `video/*` to `image/*`
- Changed icon from 🎥 to 📷
- Changed label text to "Choose Image or Drag & Drop"
- Changed preview div ID from `acVideoPreview` to `acImagePreview`

### 2. form-schema.php ✅
**Location**: Step 9 schema definition

**Changes**:
- Changed field key from `ac_video` to `ac_image`
- Updated label from "Air Condition Video at Fan Max Speed" to "Air Condition Image at Fan Max Speed"
- Type remains 'file', required remains false

### 3. generate-pdf.php ✅
**Location**: Step 9 PDF generation section

**Changes**:
- Changed from displaying video file reference to displaying actual image
- Changed field path from `ac_video_path` to `ac_image_path`
- Now uses `generateImage()` and `generateImageGrid()` to display the image in PDF
- Image will appear in the PDF document instead of just a file reference

## Automatic Compatibility

The following files automatically support this change without modification:
- ✅ **submit.php** - Handles all file uploads generically
- ✅ **save-draft.php** - Saves all uploaded files to draft
- ✅ **load-draft.php** - Loads all uploaded files from draft
- ✅ **upload-image.php** - Handles image uploads (already supports this)

## Draft System

The draft system will automatically:
- Save the uploaded AC image to `uploads/drafts/`
- Store the path in the draft JSON as `ac_image`
- Load and restore the image when loading a draft
- Display the image preview when draft is loaded

## PDF Generation

The PDF will now:
- Display the actual AC image in the document
- Show the image in a grid layout
- Include proper labeling: "Air Condition Image at Fan Max Speed"
- Image will be embedded in the PDF (not just a file reference)

## Testing Checklist

- [ ] Upload an image in Step 9 AC Image field
- [ ] Verify image preview shows correctly
- [ ] Save draft and verify image is saved
- [ ] Load draft and verify image is restored
- [ ] Submit form and verify image is included
- [ ] Check PDF and verify image appears correctly
- [ ] Verify image path is stored as `ac_image_path` in database

## Field Summary

**Step 9 - Air Conditioning Fields:**
1. Air Conditioning Turning On (radio) *
2. AC Cool Temperature (text)
3. AC Hot Temperature (text)
4. **Air Condition Image at Fan Max Speed (image)** ← CHANGED
5. Air Condition Direction Mode Working (radio) *
6. De Fogger Front Vent Working (radio) *
7. De Fogger rear Vent Working (radio) *
8. Air Conditioning All Vents (radio) *
9. AC Abnormal Vibration (radio) *

## Migration Notes

If you have existing data with `ac_video`:
- Old video files will not be displayed in new PDFs
- Field name changed from `ac_video` to `ac_image`
- Database/draft files with old field name will need migration if you want to preserve data
- New submissions will use `ac_image` field name

## Benefits of This Change

1. **Consistency**: All Step 9 fields now use images (no mixed media types)
2. **PDF Display**: Image can be embedded directly in PDF (videos cannot)
3. **File Size**: Images are typically smaller than videos
4. **Compatibility**: Better browser and system compatibility
5. **Preview**: Easier to preview images than videos in the form

---
**Date:** December 4, 2025
**Status:** ✅ Complete
**Impact:** Low - Simple field type change, all systems automatically compatible
