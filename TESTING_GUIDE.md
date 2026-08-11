# WP Booking Simple - Testing Guide

This guide will help you test all features of the WP Booking Simple plugin.

## Prerequisites

1. **WordPress Installation**
   - WordPress 5.0 or higher
   - PHP 7.4 or higher
   - MySQL/MariaDB database

2. **Local Development Options**
   - **Local by Flywheel** (Recommended for Windows)
   - **XAMPP** (Windows/Mac/Linux)
   - **MAMP** (Mac/Windows)
   - **Docker** with WordPress
   - **Live WordPress site** (staging environment recommended)

## Installation Steps

### 1. Install the Plugin

**Option A: Manual Installation**
1. Copy the entire plugin folder to `/wp-content/plugins/wp-booking-system/`
2. Go to WordPress Admin → Plugins
3. Find "WP Booking Simple" and click "Activate"

**Option B: ZIP Installation**
1. Create a ZIP file of the plugin folder
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate

### 2. Verify Installation

After activation, you should see:
- ✅ A new "Bookings" menu item in the WordPress admin sidebar
- ✅ No PHP errors in the error log
- ✅ Database table `wp_wpbs_bookings` created (check via phpMyAdmin or database tool)

## Testing Checklist

### Phase 1: Admin Configuration

#### Test 1: Settings Page
1. Go to **WP Booking Simple → Settings**
2. Configure the following:
   - Price per Adult: `50` (or your preferred amount)
   - Price per Kid: `25` (or your preferred amount)
   - Currency: `CHF` (or your preferred currency)
   - Email From Address: Your email address
   - Email From Name: Your site name
   - Admin Notification Email: Email to receive new booking notifications
   - Chalet Maximum Capacity: `10` (maximum number of guests)
3. Click "Save Settings"
4. ✅ **Expected**: Success message appears, settings are saved

#### Test 2: Admin Calendar View
1. Go to **WP Booking Simple → Booking Calendar**
2. ✅ **Expected**: FullCalendar loads with navigation buttons and calendar is visible
3. Try navigating between months
4. ✅ **Expected**: Calendar displays correctly (should be empty initially)

#### Test 3: All Bookings List
1. Go to **WP Booking Simple → All Bookings**
2. ✅ **Expected**: Empty table with message "No bookings found"

### Phase 2: Frontend Booking Form

#### Test 4: Booking Form Shortcode
1. Create a new page (Pages → Add New)
2. Add the shortcode: `[wp_booking_form_simple]`
3. Publish the page
4. View the page on the frontend
5. ✅ **Expected**: 
   - Booking form displays with all fields
   - Date pickers are functional (Flatpickr)
   - Form styling matches the design

#### Test 5: Date Selection
1. Click on "Check-in" date field
2. Select a date (e.g., tomorrow)
3. Click on "Check-out" date field
4. Select a date after check-in (e.g., 3 days later)
5. ✅ **Expected**: 
   - Dates are selected correctly
   - Check-out date must be after check-in
   - Price calculation appears below the form

#### Test 6: Price Calculation
1. Select check-in and check-out dates
2. Set Adults: `2`
3. Set Kids: `1`
4. ✅ **Expected**: 
   - Price updates automatically
   - Formula: (2 adults × price + 1 kid × price) × number of nights
   - Price displays in configured currency

#### Test 7: Form Validation
1. Try submitting the form with empty fields
2. ✅ **Expected**: Error message appears
3. Try submitting with invalid email
4. ✅ **Expected**: Email validation error
5. Try submitting with check-out before check-in
6. ✅ **Expected**: Date validation error

#### Test 8: Submit Booking
1. Fill in all required fields:
   - First Name: `John`
   - Last Name: `Doe`
   - Email: `john.doe@example.com`
   - Phone: `+41 123 456 789`
   - Check-in: Select a future date
   - Check-out: Select a date after check-in
   - Adults: `2`
   - Kids: `1`
   - Notes: `Test booking`
2. Click "Book Now"
3. ✅ **Expected**: 
   - Success message appears
   - Form resets
   - Confirmation email sent to guest (check email inbox)
   - Admin notification email sent (if configured)
   - Booking appears in admin (WP Booking Simple → All Bookings)

### Phase 3: Calendar Widget

#### Test 9: Widget Installation
1. Go to **Appearance → Widgets**
2. Find "Booking Calendar" widget
3. Drag it to a sidebar (e.g., "Sidebar" or "Footer")
4. Configure widget title (optional): `Check Availability`
5. Click "Save"
6. ✅ **Expected**: Widget appears in the sidebar

#### Test 10: Widget Calendar Display
1. View the frontend page with the sidebar
2. ✅ **Expected**: 
   - Monthly calendar displays
   - Navigation arrows work (prev/next month)
   - Legend shows "Available" and "Booked"
   - Booked dates are highlighted (if any bookings exist)

#### Test 11: Calendar Date Selection
1. Click on an available date in the calendar widget
2. ✅ **Expected**: 
   - If booking form is on the same page, check-in date is filled
   - Click another date to set check-out
   - Dates sync with the booking form

#### Test 12: Calendar Shortcode
1. Create a new page
2. Add shortcode: `[wp_booking_calendar_simple title="Check Availability"]`
3. Publish and view the page
4. ✅ **Expected**: Calendar displays with custom title

#### Test 12b: Gutenberg Block Calendar
1. Create or edit a page using Gutenberg editor
2. Click the "+" button to add a block
3. Search for "Booking Calendar" block
4. Add the block to the page
5. Configure the block title (optional)
6. Publish and view the page
7. ✅ **Expected**: Calendar displays correctly with the configured title

### Phase 4: Booking Management

#### Test 13: Booking Confirmation Email
1. Check the email inbox for the email used in Test 8
2. ✅ **Expected**: 
   - Email received with booking details
   - Contains: guest name, dates, guests, price, status
   - Contains "Manage Booking" link with token

#### Test 14: Booking Management Page
1. Create a new page: "Manage Booking"
2. Add shortcode: `[wp_booking_manage_simple]`
3. Publish the page
4. Copy the management link from the confirmation email
5. Open the link in a browser
6. ✅ **Expected**: 
   - Booking details displayed
   - Status shown (should be "Pending")
   - "Cancel Booking" button visible

#### Test 15: Cancel Booking
1. On the booking management page, click "Cancel Booking"
2. Confirm the cancellation
3. ✅ **Expected**: 
   - Success message appears
   - Status changes to "Cancelled"
   - Cancellation email sent
   - Booking no longer appears in calendar widget (dates become available)

### Phase 5: Admin Management

#### Test 16: View Booking in Admin
1. Go to **Bookings → All Bookings**
2. Find the test booking
3. Click "View"
4. ✅ **Expected**: Booking details displayed in alert/popup

#### Test 17: Calendar with Bookings
1. Go to **Bookings → Booking Calendar**
2. ✅ **Expected**: 
   - Bookings appear as colored blocks on the calendar
   - Different colors for different statuses:
     - Orange: Pending
     - Green: Confirmed
     - Red: Cancelled
3. Click on a booking block
4. ✅ **Expected**: Booking details displayed

#### Test 18: Delete Booking
1. Go to **Bookings → All Bookings**
2. Click "Delete" on a test booking
3. Confirm deletion
4. ✅ **Expected**: 
   - Booking removed from list
   - Booking removed from database
   - Dates become available again

### Phase 6: Availability Testing

#### Test 19: Double Booking Prevention
1. Create a booking for dates: Jan 15-20
2. Try to create another booking for the same dates
3. ✅ **Expected**: 
   - Error message: "Selected dates are not available"
   - Booking is not created

#### Test 20: Overlapping Dates
1. Create a booking for: Jan 15-20
2. Try to create booking for: Jan 18-25 (overlapping)
3. ✅ **Expected**: Error message, booking prevented

#### Test 21: Available Dates After Cancellation
1. Cancel a booking (Test 15)
2. Try to book the same dates again
3. ✅ **Expected**: Booking succeeds (dates are now available)

### Phase 7: Edge Cases

#### Test 22: Past Dates
1. Try to select a past date for check-in
2. ✅ **Expected**: Past dates are disabled in date picker

#### Test 23: Same Day Check-in/Check-out
1. Try to select the same date for check-in and check-out
2. ✅ **Expected**: Validation error (check-out must be after check-in)

#### Test 24: Zero Guests
1. Try to submit with 0 adults
2. ✅ **Expected**: Validation error (at least 1 adult required)

#### Test 25: Capacity Validation
1. Set Adults to a number that exceeds the configured capacity (e.g., if capacity is 10, try 12 adults)
2. Try to submit the booking
3. ✅ **Expected**: Validation error showing maximum capacity limit
4. Reduce guests to within capacity
5. ✅ **Expected**: Booking succeeds

#### Test 26: Very Long Stay
1. Create a booking for 30+ days
2. ✅ **Expected**: Price calculates correctly for all nights

#### Test 27: Admin Notification Email
1. Configure Admin Notification Email in settings
2. Create a new booking
3. ✅ **Expected**: Admin receives email notification with booking details

## Quick Test Script

For rapid testing, use this sequence:

1. **Setup** (2 min)
   - Activate plugin
   - Configure settings (Bookings → Settings)
   - Create a page with `[wp_booking_form_simple]`

2. **Create Booking** (1 min)
   - Fill form with test data
   - Submit booking
   - Check email

3. **View in Admin** (1 min)
   - Check Bookings → All Bookings
   - Check Bookings → Booking Calendar

4. **Test Widget** (1 min)
   - Add widget to sidebar
   - View frontend
   - Click dates

5. **Test Management** (1 min)
   - Open management link from email
   - Cancel booking
   - Verify cancellation

## Troubleshooting

### Plugin Not Appearing
- Check if plugin folder is in `/wp-content/plugins/`
- Verify main plugin file name matches folder name
- Check WordPress error log

### Database Table Not Created
- Check database permissions
- Verify `dbDelta()` function is working
- Check for PHP errors during activation

### Calendar Not Loading
- Check browser console for JavaScript errors
- Verify FullCalendar CDN is accessible
- Check if jQuery is loaded

### Emails Not Sending
- Check WordPress email configuration
- Test with a plugin like "WP Mail SMTP"
- Check spam folder
- Verify email settings in plugin settings

### Widget Not Appearing
- Check if theme supports widgets
- Verify widget is saved in Appearance → Widgets
- Check if sidebar is displayed on the page

### AJAX Errors
- Check browser console (F12)
- Verify nonce is being sent
- Check WordPress AJAX URL is correct
- Verify user permissions

## Testing Tools

### Browser Developer Tools
- **F12** - Open developer console
- Check **Console** tab for JavaScript errors
- Check **Network** tab for AJAX requests
- Check **Application** tab for cookies/localStorage

### WordPress Debug Mode
Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Database Inspection
- Use phpMyAdmin or similar tool
- Check table: `wp_wpbs_bookings`
- Verify data structure matches expected schema

### Email Testing
- Use a service like **Mailtrap** for testing
- Or use **WP Mail SMTP** plugin with Gmail/SMTP
- Check email logs if available

## Expected Results Summary

After complete testing, you should have:

✅ Plugin installed and activated  
✅ Settings configured and saved  
✅ Admin calendar displaying correctly  
✅ Booking form working on frontend  
✅ Price calculation accurate  
✅ Bookings created successfully  
✅ Confirmation emails sent  
✅ Calendar widget displaying availability  
✅ Booking management working  
✅ Cancellation working  
✅ Double booking prevented  
✅ All validations working  
✅ Admin can view/delete bookings  

## Next Steps After Testing

1. **Customize Styling**: Adjust CSS to match your theme
2. **Configure Email Templates**: Customize email content if needed
3. **Set Up Real Pricing**: Update prices in settings
4. **Test with Real Users**: Have friends/family test the booking flow
5. **Monitor Performance**: Check database queries and page load times
6. **Backup Strategy**: Ensure regular backups of booking data

## Support

If you encounter issues during testing:
1. Check the error logs (WordPress debug log, PHP error log)
2. Review browser console for JavaScript errors
3. Verify all requirements are met
4. Check that all files are properly uploaded

Happy testing! 🎉

