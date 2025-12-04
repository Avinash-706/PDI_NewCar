# Draft Cleanup System - Quick Start Guide

## 🚀 Ready to Use - No Additional Setup Required!

The cleanup system is already integrated and working automatically.

---

## What's Already Working

### ✅ Automatic Cleanup (Background)
- Runs on 5% of page loads (1 in 20 visits)
- Deletes drafts older than 3 days
- Removes all associated images
- Completely automatic - no action needed

### ✅ Manual Discard Button
- User clicks "Discard Draft"
- Deletes draft + all images
- Resets form to blank
- Already working in your form

---

## Quick Test

### Test the System (Safe - Dry Run)
```bash
php test-cleanup-system.php
```

This will:
1. Show all existing drafts
2. Show which ones are expired
3. Ask if you want to delete them
4. Safe to run anytime

---

## Manual Cleanup (If Needed)

### Via Web Browser
```
https://yoursite.com/drafts/auto-cleanup.php?run=cleanup&dry_run=1
```
(Remove `&dry_run=1` to actually delete)

### Via Command Line
```bash
# See what would be deleted
php drafts/auto-cleanup.php --dry-run

# Actually delete
php drafts/auto-cleanup.php
```

---

## Set Up Cron Job (Recommended for Production)

Add this to your crontab:
```cron
# Run cleanup daily at 3 AM
0 3 * * * php /path/to/drafts/auto-cleanup.php >> /path/to/logs/cleanup.log 2>&1
```

**Why use cron?**
- More reliable than page load
- Runs at predictable times
- Better for production
- Easier to monitor

---

## Monitor Cleanup Activity

### View Logs
```bash
# Real-time monitoring
tail -f /path/to/php_error.log | grep "Auto-cleanup"

# Recent cleanup activity
grep "Auto-cleanup" /path/to/php_error.log | tail -20
```

### What You'll See
```
Auto-cleanup: Found expired draft: draft_123 (age: 5.2 days)
Deleted draft image: /path/to/image.jpg
Auto-cleanup [EXECUTED]: Found 12 expired drafts out of 150 total
Auto-cleanup: Deleted 12 drafts, 540 images, freed 120.05 MB
```

---

## Configuration (Optional)

### Change Max Age (Default: 3 days)

Edit `index.php`:
```php
// Change to 7 days
define('AUTO_CLEANUP_MAX_AGE', 604800); // 7 days in seconds
```

### Change Cleanup Frequency (Default: 5%)

Edit `drafts/auto-cleanup.php` line ~250:
```php
// Change from 5% to 10%
if (rand(1, 10) !== 1) { // was: rand(1, 20)
    return;
}
```

### Disable Auto-Cleanup

Edit `index.php`:
```php
// Set to false
define('AUTO_CLEANUP_ENABLED', false);
```

---

## Troubleshooting

### "Images not deleting"
- Check file permissions: `chmod 755 uploads/drafts/`
- Check error logs for details
- Run test script to diagnose

### "Cleanup not running"
- Verify `AUTO_CLEANUP_ENABLED` is `true` in index.php
- Check error logs
- Try manual cleanup to test

### "Performance issues"
- Reduce frequency to 1% (change `rand(1, 100)`)
- Use cron job instead of page load
- Increase max age to reduce deletions

---

## Files Reference

| File | Purpose |
|------|---------|
| `drafts/discard.php` | Manual discard handler |
| `drafts/auto-cleanup.php` | Auto-cleanup system |
| `test-cleanup-system.php` | Testing script |
| `DRAFT-CLEANUP-SYSTEM.md` | Full documentation |
| `CLEANUP-IMPLEMENTATION-SUMMARY.md` | Implementation details |
| `CLEANUP-QUICK-START.md` | This file |

---

## Common Commands

```bash
# Test the system (safe)
php test-cleanup-system.php

# Dry run (see what would be deleted)
php drafts/auto-cleanup.php --dry-run

# Execute cleanup
php drafts/auto-cleanup.php

# View recent logs
grep "Auto-cleanup" /path/to/php_error.log | tail -20

# Check disk space saved
du -sh uploads/drafts/
```

---

## That's It! 🎉

The system is already working. You don't need to do anything else unless you want to:
- Set up a cron job (recommended)
- Adjust settings (optional)
- Monitor logs (optional)

For detailed information, see `DRAFT-CLEANUP-SYSTEM.md`
