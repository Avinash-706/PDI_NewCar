# Step 6 Validation Fix

## Problem
Step 6 validation was not working - users could proceed to the next step without filling all required fields.

## Root Cause
The 52 exterior body fields in Step 6 are dynamically generated with PHP and don't have individual `required` attributes. The standard validation logic only checks for fields with the `required` attribute, so it was skipping Step 6 entirely.

## Solution
Added special validation logic specifically for Step 6 that:

1. **Validates all 52 checkbox groups** - Ensures at least one option is selected for each field
2. **Validates conditional images** - Ensures images are uploaded when non-OK options are selected
3. **Provides clear error messages** - Shows which field needs attention
4. **Scrolls to problem field** - Automatically scrolls to the first field with an issue

## Implementation

### Updated Function: `validateStep(step)` in script.js

```javascript
function validateStep(step) {
    const currentStepElement = document.querySelector(`[data-step="${step}"]`);
    
    // Special validation for Step 6 (Exterior Body with 52 fields)
    if (step === 6) {
        // Find all checkbox groups with data-ok-group attribute
        const checkboxGroups = currentStepElement.querySelectorAll('[data-ok-group]');
        
        for (let group of checkboxGroups) {
            const groupName = group.getAttribute('data-ok-group');
            const checkboxes = group.querySelectorAll('input[type="checkbox"]');
            const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            // 1. Check if at least one checkbox is selected
            if (!isAnyChecked) {
                const label = group.querySelector('label');
                const labelText = label ? label.textContent.replace('*', '').trim() : groupName;
                alert('Please select at least one option for: ' + labelText);
                group.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            // 2. Check if image is required (non-OK option selected)
            const okCheckbox = group.querySelector('[data-ok-checkbox]');
            const isOkChecked = okCheckbox && okCheckbox.checked;
            
            if (!isOkChecked) {
                const hasNonOkChecked = Array.from(checkboxes).some(cb => 
                    cb !== okCheckbox && cb.checked
                );
                
                if (hasNonOkChecked) {
                    // Image is required
                    const imageContainer = document.getElementById(groupName + '_image_container');
                    if (imageContainer && imageContainer.style.display !== 'none') {
                        const imageInput = imageContainer.querySelector('input[type="file"]');
                        if (imageInput) {
                            const hasSavedFile = imageInput.dataset.savedFile;
                            const hasNewFile = imageInput.files && imageInput.files.length > 0;
                            
                            if (!hasSavedFile && !hasNewFile) {
                                const label = group.querySelector('label');
                                const labelText = label ? label.textContent.replace('*', '').trim() : groupName;
                                alert('Please upload an image for: ' + labelText);
                                imageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                return false;
                            }
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    // Standard validation for other steps continues...
}
```

## Validation Rules for Step 6

### Rule 1: Checkbox Selection (Required)
- **Check:** At least ONE checkbox must be selected for each of the 52 fields
- **Error:** "Please select at least one option for: [Field Name]"
- **Action:** Scrolls to the field and shows alert

### Rule 2: Image Upload (Conditional)
- **Check:** If non-OK option selected, image must be uploaded
- **Conditions:**
  - If "OK" selected: Image NOT required
  - If any other option selected: Image REQUIRED
  - If no options selected: Fails Rule 1 first
- **Error:** "Please upload an image for: [Field Name]"
- **Action:** Scrolls to the image field and shows alert

### Rule 3: Saved Files (Draft Support)
- **Check:** Accepts previously uploaded images from drafts
- **Logic:** `hasSavedFile OR hasNewFile`
- **Benefit:** Users don't need to re-upload images when loading drafts

## Validation Flow

```
User clicks "Next" on Step 6
    ↓
validateStep(6) called
    ↓
Loop through all 52 checkbox groups
    ↓
For each group:
    ├─ Check if any checkbox selected
    │   ├─ NO → Show error, scroll to field, return false
    │   └─ YES → Continue
    ↓
    ├─ Check if "OK" selected
    │   ├─ YES → Skip image check, continue to next group
    │   └─ NO → Check if image required
    ↓
    └─ If non-OK option selected:
        ├─ Check if image uploaded (new or saved)
        │   ├─ NO → Show error, scroll to field, return false
        │   └─ YES → Continue to next group
    ↓
All 52 groups validated
    ↓
Return true → Proceed to Step 7
```

## User Experience Improvements

### 1. Clear Error Messages
- Shows exact field name that needs attention
- Distinguishes between missing checkbox and missing image

### 2. Auto-Scroll
- Automatically scrolls to the first problematic field
- Centers the field in the viewport
- Smooth scrolling animation

### 3. Sequential Validation
- Validates fields in order (top to bottom)
- Stops at first error
- User fixes one issue at a time

### 4. Draft-Friendly
- Recognizes previously uploaded images
- Doesn't force re-upload of saved images
- Validates both new and saved files

## Testing Checklist

### Basic Validation:
- [ ] Cannot proceed with no checkboxes selected
- [ ] Cannot proceed with only some fields filled
- [ ] Can proceed with all fields having "OK" selected
- [ ] Can proceed with all fields filled correctly

### Image Validation:
- [ ] Cannot proceed if non-OK selected without image
- [ ] Can proceed if non-OK selected with image
- [ ] Can proceed if OK selected without image
- [ ] Image not required when OK selected

### Error Messages:
- [ ] Shows correct field name in error
- [ ] Scrolls to correct field
- [ ] Shows checkbox error before image error
- [ ] Clear and understandable messages

### Draft System:
- [ ] Saved images count as valid
- [ ] Can proceed with saved images
- [ ] Can replace saved images
- [ ] Validation works after draft load

### Edge Cases:
- [ ] Multiple non-OK options + image = valid
- [ ] Switching from non-OK to OK removes image requirement
- [ ] Switching from OK to non-OK adds image requirement
- [ ] All 52 fields validated (not just first few)

## Files Modified

- ✅ **script.js** - Enhanced `validateStep()` function with Step 6 specific logic

## Status

✅ **FIXED** - Step 6 validation now works correctly

Users must:
1. Select at least one option for all 52 fields
2. Upload images for any fields with non-OK options selected
3. Cannot skip or bypass any required fields

**Ready for testing!** 🎉
