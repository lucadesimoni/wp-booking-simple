# Refactoring Plan: Rename to wp_booking_simple

## Naming Convention Changes

### Classes
- `WP_Booking_System` → `WP_Booking_Simple`
- `WP_Booking_System_Database` → `WP_Booking_Simple_Database`
- `WP_Booking_System_Admin` → `WP_Booking_Simple_Admin`
- `WP_Booking_System_Frontend` → `WP_Booking_Simple_Frontend`
- `WP_Booking_System_Ajax` → `WP_Booking_Simple_Ajax`
- `WP_Booking_System_Email` → `WP_Booking_Simple_Email`
- `WP_Booking_System_Widget` → `WP_Booking_Simple_Widget`
- `WP_Booking_System_Block` → `WP_Booking_Simple_Block`

### Functions
- `wp_booking_system()` → `wp_booking_simple()`

### Constants
- `WP_BOOKING_SYSTEM_VERSION` → `WP_BOOKING_SIMPLE_VERSION`
- `WP_BOOKING_SYSTEM_PLUGIN_DIR` → `WP_BOOKING_SIMPLE_PLUGIN_DIR`
- `WP_BOOKING_SYSTEM_PLUGIN_URL` → `WP_BOOKING_SIMPLE_PLUGIN_URL`

### Text Domain
- `wp-booking-system` → `wp-booking-simple`

### Options (wp_options table)
- `wpbs_price_adult` → `wpbsl_price_adult`
- `wpbs_price_kid` → `wpbsl_price_kid`
- `wpbs_currency` → `wpbsl_currency`
- `wpbs_email_from` → `wpbsl_email_from`
- `wpbs_email_from_name` → `wpbsl_email_from_name`
- `wpbs_admin_notification_email` → `wpbsl_admin_notification_email`
- `wpbs_chalet_capacity` → `wpbsl_chalet_capacity`

### AJAX Actions
- `wpbs_get_bookings` → `wpbsl_get_bookings`
- `wpbs_get_booking` → `wpbsl_get_booking`
- `wpbs_delete_booking` → `wpbsl_delete_booking`
- `wpbs_check_availability` → `wpbsl_check_availability`
- `wpbs_calculate_price` → `wpbsl_calculate_price`
- `wpbs_submit_booking` → `wpbsl_submit_booking`
- `wpbs_cancel_booking` → `wpbsl_cancel_booking`
- `wpbs_get_calendar_availability` → `wpbsl_get_calendar_availability`

### Nonces
- `wp-booking-system-admin` → `wp-booking-simple-admin`
- `wp-booking-system-frontend` → `wp-booking-simple-frontend`

### JavaScript Variables
- `wpbsAdmin` → `wpbsAdmin`
- `wpbsFrontend` → `wpbsFrontend`

### Database Table
- `wp_wpbs_bookings` → `wp_wpbsl_bookings`

### Files to Create/Update
1. Rename class files (or create new ones)
2. Update all references in existing files
3. Update wp-booking-simple.php
4. Update all includes/class-*.php files
5. Update assets/js/*.js files
6. Update assets/css/*.css (comments only)

## Migration Considerations

### Database Migration Needed
- Old options will need to be migrated on plugin update
- Database table name change requires migration script

### Breaking Changes
- This is a major version change (1.0.0 → 2.0.0)
- Existing installations will need migration
- Any external code using hooks/filters will break




