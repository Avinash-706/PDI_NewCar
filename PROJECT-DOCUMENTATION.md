# Car Inspection System - Complete Documentation

**Project:** USED CAR 3.0 - Car Inspection Expert System  
**Version:** 1.0  
**Last Updated:** November 22, 2025

---

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Core Features](#core-features)
4. [File Structure](#file-structure)
5. [System Flow](#system-flow)
6. [Key Components](#key-components)
7. [Database & Storage](#database--storage)
8. [Configuration](#configuration)
9. [Deployment](#deployment)
10. [Maintenance](#maintenance)

---

## Project Overview

A comprehensive web-based car inspection system that allows experts to conduct detailed 23-step vehicle inspections, capture images, generate PDF reports, and email results to customers.

### Key Capabilities
- **23-Step Inspection Process** - Comprehensive vehicle evaluation
- **Progressive Image Upload** - Real-time image upload with compression
- **Draft System** - Save and resume inspections
- **PDF Generation** - Professional inspection reports
- **Email Delivery** - Automatic report distribution
- **Mobile Responsive** - Works on all devices
- **Geolocation** - Capture inspection location

---

## System Architecture

### Technology Stack
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 7.4+
- **PDF Generation:** mPDF library
- **Email:** PHPMailer
- **Image Processing:** GD Library
- **Storage:** File-based (JSON for drafts)

### Directory Structure
```
project/
├── index.php              # Main inspection form
├── script.js              # Frontend logic
├── style.css              # Styling
├── config.php             # Configuration
├── auto-config.php        # Auto PHP settings
├── init-directories.php   # Directory initialization
├── .htaccess              # Apache configuration
├── composer.json          # Dependencies
├── logo.png               # Company logo
│
├── Core PHP Files
├── submit.php             # Form submission handler
├── generate-pdf.php       # PDF generation
├── send-email.php         # Email delivery
├── upload-image.php       # Progressive image upload
├── save-draft.php         # Draft saving
├── load-draft.php         # Draft loading
├── delete-draft.php       # Draft deletion
├── t-submit.php           # Test PDF generation
│
├── Image Processing
├── image-optimizer.php    # Image compression & resizing
├── form-schema.php        # Form field definitions
│
├── Utilities
├── cleanup-orphaned-images.php  # Image cleanup utility
├── generate-pdf-worker.php      # Background PDF worker
│
├── drafts/                # Draft management
│   ├── create.php
│   ├── update.php
│   ├── discard.php
│   ├── load.php
│   └── archive.php
│
├── scripts/               # Maintenance scripts
│   ├── cleanup_drafts.php
│   └── diagnose_draft.php
│
├── uploads/               # File storage
│   ├── drafts/           # Draft images
│   ├── compressed/       # Compressed images
│   └── uniform/          # Resized images
│
├── pdfs/                  # Generated PDFs
├── logs/                  # Error logs
├── tmp/                   # Temporary files
└── vendor/                # Composer dependencies
```

---

## Core Features

### 1. Multi-Step Form (23 Steps)
1. Booking Details
2. Expert Details (with geolocation)
3. Car Details
4. Car Documents
5. Body Frame Accidental Checklist (with images)
6. Exterior Body
7. Engine (Before Test Drive)
8. OBD Scan
9. Electrical and Interior
10. Warning Lights
11. Air Conditioning
12. Tyres
13. Transmission & Clutch Pedal
14. Axle
15. Engine (After Test Drive)
16. Brakes
17. Suspension
18. Brakes & Steering (Test Drive)
19. Underbody
20. Equipments
21. Final Car Result
22. Car Images From All Directions (5 images)
23. Payment Details (with 5 optional "Other Images")

### 2. Progressive Image Upload
- **Real-time Upload:** Images upload immediately when selected
- **Compression:** Automatic image compression (max 1200px, 70% quality)
- **Validation:** File type and size validation
- **Preview:** Instant image preview
- **Replace:** Easy image replacement with automatic old file deletion

### 3. Draft System
- **Auto-save:** Periodic automatic saving
- **Manual Save:** Save draft button
- **Resume:** Load draft on page reload
- **Discard:** Complete draft deletion with image cleanup
- **Persistence:** Survives page refreshes

### 4. PDF Generation
- **Professional Layout:** Red theme with company branding
- **Image Grid:** 3-column responsive grid (250x188px images)
- **Complete Data:** All 23 steps included
- **Header:** Logo, booking ID, expert ID, customer name
- **Optimized:** Fast generation with mPDF

### 5. Email Delivery
- **Async Sending:** Background email after user response
- **Attachments:** PDF report attached
- **HTML Email:** Professional email template
- **Error Handling:** Graceful failure handling

### 6. Geolocation
- **GPS Capture:** Automatic location detection
- **Reverse Geocoding:** Address lookup via OpenStreetMap
- **Accuracy Display:** Shows GPS accuracy
- **Permission Handling:** User-friendly permission prompts

---

## System Flow

### Inspection Flow
```
1. User opens index.php
2. Fills Step 1 (Booking Details)
3. Clicks "Next" → Validation → Step 2
4. Continues through 23 steps
5. Uploads images (progressive upload)
6. Can save draft at any time
7. On final step, clicks "Submit"
8. System processes:
   - Validates all data
   - Generates PDF (generate-pdf.php)
   - Sends immediate response to user
   - Sends email in background (send-email.php)
9. User receives success message
10. PDF and email delivered
```

### Draft Flow
```
1. User uploads images → upload-image.php
   - Image compressed
   - Saved to uploads/drafts/
   - Path stored in draft JSON
   - Draft ID created/updated

2. User clicks "Save Draft" → save-draft.php
   - Collects all form data
   - Merges with uploaded images
   - Saves to drafts/{draft_id}.json
   - Returns success

3. User refreshes page → loadDraft()
   - Checks localStorage for draft ID
   - Fetches draft from load-draft.php
   - Verifies images exist
   - Restores form fields
   - Displays image previews

4. User clicks "Discard Draft" → drafts/discard.php
   - Deletes all draft images
   - Deletes draft JSON
   - Clears localStorage
   - Reloads page
```

### Image Upload Flow
```
1. User selects image → uploadImageImmediately()
2. Shows "Uploading..." indicator
3. Sends to upload-image.php:
   - Validates file type/size
   - Compresses image (ImageOptimizer)
   - Generates unique filename
   - Saves to uploads/drafts/
   - Updates draft JSON
   - Returns web-accessible path
4. JavaScript receives response:
   - Stores path in uploadedFiles
   - Updates localStorage
   - Shows preview with "✅ Uploaded"
   - Marks field as complete
```

---

## Key Components

### Frontend (script.js)

**Main Functions:**
- `nextStep()` - Navigate to next step with validation
- `prevStep()` - Navigate to previous step
- `validateStep()` - Validate current step fields
- `saveDraft()` - Save current progress
- `loadDraft()` - Load saved draft
- `uploadImageImmediately()` - Progressive image upload
- `submitForm()` - Final form submission
- `fetchLocation()` - Get GPS coordinates

**Global Variables:**
- `currentStep` - Current form step (1-23)
- `uploadedFiles` - Object storing uploaded image paths
- `draftId` - Current draft identifier

### Backend (PHP)

**Core Files:**

1. **config.php**
   - Database configuration (if needed)
   - Email settings
   - Directory paths
   - Constants

2. **auto-config.php**
   - Automatic PHP configuration
   - Memory limits
   - Upload limits
   - Execution time

3. **init-directories.php**
   - DirectoryManager class
   - Path management
   - Directory creation
   - Cross-platform compatibility

4. **submit.php**
   - Form submission handler
   - Data validation
   - PDF generation trigger
   - Email sending trigger

5. **generate-pdf.php**
   - mPDF initialization
   - HTML generation
   - Image embedding
   - PDF styling
   - File saving

6. **send-email.php**
   - PHPMailer setup
   - Email composition
   - PDF attachment
   - SMTP configuration

7. **upload-image.php**
   - File validation
   - Image compression
   - Unique filename generation
   - Draft JSON update
   - Path return

8. **image-optimizer.php**
   - ImageOptimizer class
   - GD-based compression
   - Resize functionality
   - Quality optimization

---

## Database & Storage

### File-Based Storage
The system uses file-based storage (no database required):

**Draft Storage:**
```json
{
  "draft_id": "draft_673e8f9b2a1c8",
  "timestamp": 1732123456,
  "current_step": 5,
  "form_data": {
    "booking_id": "12345",
    "customer_name": "John Doe",
    ...
  },
  "uploaded_files": {
    "carPhoto": "uploads/drafts/1732123456_guest_abc123_car.jpg",
    "enginePhoto": "uploads/drafts/1732123457_guest_def456_engine.jpg",
    ...
  }
}
```

**Storage Locations:**
- **Drafts:** `uploads/drafts/{draft_id}.json`
- **Images:** `uploads/drafts/{timestamp}_{user}_{random}_{name}.jpg`
- **PDFs:** `pdfs/inspection_{booking_id}_{timestamp}.pdf`
- **Logs:** `logs/error.log`

---

## Configuration

### PHP Requirements
- PHP 7.4 or higher
- GD extension (image processing)
- mbstring extension
- fileinfo extension
- Composer (for dependencies)

### PHP Settings (auto-configured)
```php
memory_limit = 2048M
max_execution_time = 600
upload_max_filesize = 200M
post_max_size = 500M
max_file_uploads = 500
max_input_vars = 5000
```

### Email Configuration (config.php)
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Car Inspection System');
define('RECIPIENT_EMAIL', 'recipient@example.com');
```

### Directory Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/drafts/
chmod 755 pdfs/
chmod 755 logs/
chmod 755 tmp/
```

---

## Deployment

### Installation Steps

1. **Upload Files**
   ```bash
   # Upload all files to web server
   # Ensure vendor/ folder is included
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Email**
   - Edit `config.php`
   - Set SMTP credentials
   - Set recipient email

4. **Set Permissions**
   ```bash
   chmod 755 uploads/ pdfs/ logs/ tmp/
   ```

5. **Test System**
   - Open index.php in browser
   - Complete a test inspection
   - Verify PDF generation
   - Check email delivery

### Server Requirements
- Apache/Nginx web server
- PHP 7.4+ with required extensions
- 2GB+ RAM recommended
- 10GB+ disk space for images/PDFs

### .htaccess Configuration
```apache
# Increase upload limits
php_value upload_max_filesize 200M
php_value post_max_size 500M
php_value max_execution_time 600
php_value max_input_time 600
php_value memory_limit 2048M

# Security
Options -Indexes
```

---

## Maintenance

### Regular Tasks

**Daily:**
- Monitor error logs (`logs/error.log`)
- Check disk space usage

**Weekly:**
- Clean up old test PDFs
- Review draft storage

**Monthly:**
- Run orphaned image cleanup
- Archive old PDFs
- Update dependencies

### Cleanup Utilities

**1. Orphaned Image Cleanup**
```bash
# Dry run (preview only)
php cleanup-orphaned-images.php?dry_run=true

# Actual cleanup
php cleanup-orphaned-images.php
```

**2. Draft Cleanup**
```bash
# Diagnose drafts
php scripts/diagnose_draft.php

# Clean old drafts
php scripts/cleanup_drafts.php
```

### Monitoring

**Check System Health:**
- Error log size
- Disk space usage
- Draft count
- PDF count
- Image count

**Performance Metrics:**
- Average PDF generation time: 2-3 seconds
- Average image upload time: 1-2 seconds
- Average form submission time: 5-10 seconds

---

## Troubleshooting

### Common Issues

**1. Images Not Loading After Refresh**
- Check draft JSON has correct paths
- Verify images exist in uploads/drafts/
- Check file permissions
- Run debug-draft-paths.php

**2. PDF Generation Fails**
- Check memory_limit (needs 2048M)
- Verify mPDF is installed
- Check image paths are accessible
- Review error logs

**3. Email Not Sending**
- Verify SMTP credentials
- Check firewall/port 587
- Test with PHPMailer directly
- Review send-email.php logs

**4. Upload Fails**
- Check upload_max_filesize
- Verify GD extension installed
- Check directory permissions
- Review upload-image.php logs

---

## Security Considerations

### Implemented Security
- File type validation (images only)
- File size limits (20MB max)
- Path sanitization
- SQL injection prevention (no database)
- XSS prevention (htmlspecialchars)
- CSRF protection (session-based)

### Recommendations
- Use HTTPS in production
- Implement user authentication
- Add rate limiting
- Regular security updates
- Backup system regularly

---

## Future Enhancements

### Planned Features
- User authentication system
- Database integration
- Admin dashboard
- Report analytics
- Mobile app
- Cloud storage integration
- Multi-language support
- Digital signatures

---

## Support & Contact

For technical support or questions:
- Review this documentation
- Check error logs
- Use debug utilities
- Contact system administrator

---

## Version History

**v1.0 (November 2025)**
- Initial release
- 23-step inspection form
- Progressive image upload
- Draft system
- PDF generation
- Email delivery
- Geolocation support
- Orphaned image cleanup
- Complete documentation

---

## License

Proprietary - All rights reserved

---

**End of Documentation**
