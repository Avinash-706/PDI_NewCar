# Testing Guide - Form Changes

## Quick Test Checklist

### Step 1: Car Inspection
1. ✅ Open the form in browser
2. ✅ Verify Step 1 shows "Car Inspection" title
3. ✅ Check all fields are present:
   - [ ] Booking ID (required)
   - [ ] Assigned Expert Name (required)
   - [ ] Inspection Expert City dropdown (required)
   - [ ] Customer Name (required)
   - [ ] Customer Phone Number (optional)
   - [ ] Date and Time side by side (both required)
   - [ ] Inspection Address (required)
   - [ ] OBD Scanning radio buttons (required)
   - [ ] Car (optional)
   - [ ] Lead Owner (optional)
   - [ ] Pending Amount (optional)
4. ✅ Test Expert City dropdown:
   - [ ] Shows "Select City" as default
   - [ ] Contains all 10 cities
   - [ ] "New Delhi A" is in the list
5. ✅ Try clicking "Next" without filling required fields
   - [ ] Should show validation errors
6. ✅ Fill all required fields and click "Next"
   - [ ] Should proceed to Step 2

### Step 2: Payment Taking
1. ✅ Verify Step 2 shows "Payment Taking" title
2. ✅ Test Payment radio buttons:
   - [ ] Select "No" - payment details section should stay hidden
   - [ ] Select "Yes" - payment details section should appear
3. ✅ With "Yes" selected, test Payment Type:
   - [ ] Select "Cash" - QR code section should stay hidden
   - [ ] Select "Online" - QR code section should appear
4. ✅ With "Online" selected:
   - [ ] QR code image should be visible
   - [ ] "Scan to pay via UPI" text should appear
   - [ ] Payment Proof upload field should be visible and required
5. ✅ With "Cash" selected:
   - [ ] QR code section should be hidden
   - [ ] Payment Proof should not be required
6. ✅ Test Amount Paid field:
   - [ ] Should accept decimal numbers
   - [ ] Should be required when Payment = "Yes"
7. ✅ Try clicking "Next" with incomplete payment details
   - [ ] Should show validation errors
8. ✅ Complete payment details and click "Next"
   - [ ] Should proceed to Step 3

### Step 3: Expert Details
1. ✅ Verify Step 3 shows "Expert Details" title
2. ✅ Check fields are present:
   - [ ] Inspection 45 Minutes Delayed? (required)
   - [ ] Your Photo with car number plate (required)
3. ✅ Test image upload:
   - [ ] Click "Choose Image" or "Take Photo"
   - [ ] Upload an image
   - [ ] Preview should appear
4. ✅ Try clicking "Next" without filling required fields
   - [ ] Should show validation errors
5. ✅ Complete all fields and click "Next"
   - [ ] Should proceed to Step 4

### Step 4 onwards
1. ✅ Verify Step 4 shows "Car Details"
2. ✅ Navigate through all steps
3. ✅ Verify step counter shows "Step X of 24"
4. ✅ Verify final step is Step 24

### Draft Functionality
1. ✅ Fill some fields in Steps 1-3
2. ✅ Click "Save Draft"
   - [ ] Should show success message
3. ✅ Refresh the page
4. ✅ Verify draft loads:
   - [ ] Step 1 fields are restored
   - [ ] Step 2 payment selection is restored
   - [ ] Step 3 fields are restored
   - [ ] Uploaded images are shown
5. ✅ Test "Discard Draft"
   - [ ] Should clear all data
   - [ ] Should return to Step 1

### Form Submission
1. ✅ Complete all 24 steps
2. ✅ Click "Submit" on final step
3. ✅ Verify submission:
   - [ ] Loading overlay appears
   - [ ] PDF is generated
   - [ ] Success message appears
4. ✅ Check submitted data includes:
   - [ ] All Step 1 fields
   - [ ] Payment information from Step 2
   - [ ] Expert details from Step 3

## Browser Testing

Test on multiple browsers:
- [ ] Chrome/Edge (Desktop)
- [ ] Firefox (Desktop)
- [ ] Safari (Desktop)
- [ ] Chrome (Mobile)
- [ ] Safari (Mobile)

## Mobile Responsiveness

1. ✅ Test on mobile device or responsive mode
2. ✅ Verify:
   - [ ] Date and Time fields are side by side (or stack on very small screens)
   - [ ] Dropdown is easy to use
   - [ ] Radio buttons are easy to tap
   - [ ] QR code is visible and properly sized
   - [ ] Image upload works with camera
   - [ ] All text is readable

## Edge Cases

1. ✅ Test with very long text in fields
2. ✅ Test with special characters in text fields
3. ✅ Test with large image files
4. ✅ Test rapid clicking of Next/Previous buttons
5. ✅ Test browser back button behavior
6. ✅ Test with slow internet connection

## Known Issues to Watch For

1. **Payment Toggle:**
   - Ensure payment details section properly shows/hides
   - Ensure required fields are dynamically managed

2. **Step Numbers:**
   - Verify all steps are numbered 1-24
   - Verify progress bar shows correct percentage

3. **Draft System:**
   - Ensure new fields are saved in drafts
   - Ensure payment selections are restored correctly

4. **Image Uploads:**
   - Ensure payment proof upload works
   - Ensure camera capture works on mobile

## Reporting Issues

If you find any issues, please report with:
1. Step number where issue occurred
2. Browser and device information
3. Screenshot or description of the issue
4. Steps to reproduce

## Success Criteria

All tests should pass with:
- ✅ No console errors
- ✅ All fields working as expected
- ✅ Smooth navigation between steps
- ✅ Draft save/load working correctly
- ✅ Form submission successful
- ✅ Mobile responsive design working
