# Installation Instructions

## Quick Installation

### Method 1: WordPress Admin Upload (Recommended)

1. **Download the Plugin**
   - File: `wp-booking-simple.zip`
   - This is the ready-to-upload package

2. **Upload to WordPress**
   - Log in to your WordPress admin panel
   - Navigate to **Plugins → Add New**
   - Click **Upload Plugin** button at the top
   - Click **Choose File** and select `wp-booking-simple.zip`
   - Click **Install Now**

3. **Activate the Plugin**
   - After installation, click **Activate Plugin**
   - You should see a new **Bookings** menu item in the admin sidebar

4. **Configure Settings**
   - Go to **Bookings → Settings**
   - Set your pricing (Price per Adult, Price per Kid)
   - Set currency (e.g., CHF, EUR, USD)
   - Configure email settings
   - Click **Save Settings**

5. **Create Booking Page**
   - Go to **Pages → Add New**
   - Add the shortcode: `[wp_booking_form_simple]`
   - Publish the page

6. **Add Calendar Widget (Optional)**
   - Go to **Appearance → Widgets**
   - Find **Booking Calendar** widget
   - Drag it to your sidebar
   - Save

### Method 2: FTP Upload

1. **Extract the ZIP file**
   - Extract `wp-booking-simple.zip` to a folder
   - You should see a `wp-booking-system` folder

2. **Upload via FTP**
   - Connect to your server via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `wp-booking-system` folder

3. **Activate**
   - Go to WordPress Admin → Plugins
   - Find **WP Booking Simple**
   - Click **Activate**

## System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher (or MariaDB equivalent)
- **Memory**: 128MB PHP memory limit (256MB recommended)

## File Structure

The plugin includes:

```
wp-booking-system/
├── wp-booking-simple.php    (Main plugin file)
├── index.php                 (Security file)
├── uninstall.php            (Cleanup on uninstall)
├── LICENSE                  (GPL-2.0 license)
├── readme.txt               (Plugin readme)
├── includes/                (Core classes)
│   ├── class-wp-booking-simple.php
│   ├── class-wp-booking-system-admin.php
│   ├── class-wp-booking-system-ajax.php
│   ├── class-wp-booking-system-database.php
│   ├── class-wp-booking-system-email.php
│   ├── class-wp-booking-system-frontend.php
│   ├── class-wp-booking-system-widget.php
│   └── class-wp-booking-system-block.php
└── assets/                  (CSS and JavaScript)
    ├── css/
    │   ├── admin.css
    │   └── frontend.css
    └── js/
        ├── admin.js
        ├── frontend.js
        └── block.js
```

## Post-Installation Checklist

After installation, verify:

- [ ] Plugin appears in Plugins list
- [ ] "WP Booking Simple" menu appears in admin sidebar
- [ ] Settings page is accessible (WP Booking Simple → Settings)
- [ ] Calendar page loads (WP Booking Simple → Booking Calendar)
- [ ] Booking form shortcode works on frontend
- [ ] Calendar widget appears in Widgets list
- [ ] No PHP errors in error log

## Troubleshooting

### Plugin Won't Activate

- Check PHP version (must be 7.4+)
- Check WordPress version (must be 5.0+)
- Check error log: `wp-content/debug.log`
- Enable WordPress debug mode in `wp-config.php`:
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  ```

### Database Table Not Created

- Check database user permissions
- Verify `dbDelta()` function is working
- Check for PHP errors during activation

### Styles/JavaScript Not Loading

- Clear browser cache
- Check if CDN resources are accessible (FullCalendar, Flatpickr)
- Verify file permissions (should be 644 for files, 755 for directories)

### Email Not Sending

- Check WordPress email configuration
- Install a mail plugin like "WP Mail SMTP"
- Check spam folder
- Verify email settings in plugin settings

## Uninstallation

To completely remove the plugin:

1. **Deactivate** the plugin (Plugins → Installed Plugins)
2. **Delete** the plugin (click "Delete" link)
3. The `uninstall.php` file will automatically:
   - Remove the database table `wp_wpbs_bookings`
   - Delete all plugin options
   - Clear cached data

**Note**: All booking data will be permanently deleted when you delete the plugin.

## Support

For issues or questions:
1. Check the `TESTING_GUIDE.md` for testing procedures
2. Review `BEST_PRACTICES.md` for technical details
3. Check WordPress error logs
4. Verify all requirements are met

## Next Steps

After successful installation:

1. **Configure Settings** - Set your pricing and email preferences
2. **Create Booking Page** - Add the booking form to a page
3. **Add Calendar Widget** - Display availability in sidebar
4. **Test Booking Flow** - Make a test booking to verify everything works
5. **Customize Styling** - Adjust CSS to match your theme if needed

Enjoy your new booking system! 🎉

