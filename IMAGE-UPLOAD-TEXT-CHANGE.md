# Image Upload Text Change - Steps 7-13

## Overview
Changed all image upload button text from "Choose Image or Drag & Drop" to "Choose Image" in Steps 7-13.

## Change Details

**Old Text**: "Choose Image or Drag & Drop"  
**New Text**: "Choose Image"

## Files Updated

### index.php ✅
Changed 13 image upload fields across Steps 7-13:

**Step 7 - OBD Scan (1 field):**
- OBD Scan Photo

**Step 9 - Air Conditioning (1 field):**
- Air Condition Image at Fan Max Speed

**Step 10 - Tyres (5 fields):**
- Driver Front Tyre Tread Depth
- Driver Back Tyre Tread Depth
- Passenger Back Tyre Tread Depth
- Passenger Front Tyre Tread Depth
- Stepney Tyre Tread Depth

**Step 11 - Under Body (4 fields):**
- Underbody Left
- Underbody Rear
- Underbody Front
- Underbody Right

**Step 12 - Equipments (1 field):**
- Tool Kit Image

**Step 13 - Final Result (1 field):**
- Photo of Issues

## Implementation

Used PowerShell find-and-replace to update all instances:
```powershell
(Get-Content "index.php" -Raw) -replace 'Choose Image or Drag & Drop', 'Choose Image' | Set-Content "index.php" -NoNewline
```

## Impact

- **User Experience**: Simpler, cleaner button text
- **Functionality**: No change - drag & drop still works (it's a browser feature)
- **Compatibility**: No impact on any other files or systems
- **Testing**: No testing required - purely cosmetic text change

## Notes

- Drag & drop functionality is still available (it's a native browser feature for file inputs)
- The text change is purely cosmetic to simplify the UI
- All other steps (1-6) retain their original text
- No changes needed to JavaScript, CSS, or backend files

---
**Date:** December 4, 2025  
**Status:** ✅ Complete  
**Files Changed:** 1 (index.php)  
**Fields Updated:** 13 image upload fields
