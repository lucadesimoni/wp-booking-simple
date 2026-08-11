# Naming Convention Explanation

## Current Naming Status

### ✅ Changed (User-Facing Display Names)
- Plugin header name: **"WP Booking Simple"** ✅
- Admin menu title: **"WP Booking Simple"** ✅  
- Documentation: All references updated ✅

### ⚠️ Still Using "WP Booking Simple" (Internal Technical Identifiers)

These are the **internal technical identifiers** that still use the old name:

#### 1. **PHP Class Names**
- `WP_Booking_System`
- `WP_Booking_System_Admin`
- `WP_Booking_System_Ajax`
- `WP_Booking_System_Database`
- `WP_Booking_System_Email`
- `WP_Booking_System_Frontend`
- `WP_Booking_System_Widget`
- `WP_Booking_System_Block`

#### 2. **Function Names**
- `wp_booking_system()` - Main plugin function

#### 3. **Constants**
- `WP_BOOKING_SYSTEM_VERSION`
- `WP_BOOKING_SYSTEM_PLUGIN_DIR`
- `WP_BOOKING_SYSTEM_PLUGIN_URL`

#### 4. **Text Domain** (for translations)
- `wp-booking-system`

#### 5. **File Names**
- `wp-booking-simple.php`
- `class-wp-booking-system-*.php`

#### 6. **Database Table Names**
- `wp_wpbs_bookings` (prefix + wpbs = wp booking system)

#### 7. **Option Names** (in wp_options table)
- `wpbs_price_adult`
- `wpbs_price_kid`
- `wpbs_currency`
- `wpbs_email_from`
- `wpbs_email_from_name`
- `wpbs_admin_notification_email`
- `wpbs_chalet_capacity`

#### 8. **AJAX Action Names**
- `wpbs_get_bookings`
- `wpbs_get_booking`
- `wpbs_delete_booking`
- `wpbs_check_availability`
- `wpbs_calculate_price`
- `wpbs_submit_booking`
- `wpbs_cancel_booking`
- `wpbs_get_calendar_availability`

#### 9. **Nonce Names**
- `wp-booking-system-admin`
- `wp-booking-system-frontend`

#### 10. **JavaScript Variable Names**
- `wpbsAdmin`
- `wpbsFrontend`

## Why Are These Still Using Old Names?

### Standard WordPress Practice
In WordPress development, there's a clear distinction:

1. **Display Names** = What users see (menus, titles, descriptions)
   - ✅ Changed to "WP Booking Simple"

2. **Technical Identifiers** = Internal code references (classes, functions, constants)
   - ⚠️ Typically kept unchanged for compatibility

### Reasons to Keep Technical Names Unchanged

1. **Backward Compatibility**
   - Prevents breaking existing installations
   - No need to migrate database or options
   - No code changes required for updates

2. **WordPress Conventions**
   - Text domains are permanent (used for translations)
   - Constants and class names are registered in WordPress
   - Changing them requires full plugin rewrite

3. **Database Persistence**
   - Option names stored in database
   - Table names in database
   - Changing requires migration scripts

4. **Hooks & Filters**
   - Other plugins/themes might use these hooks
   - Changing breaks external integrations

## Should You Change Them?

### Option 1: Keep As-Is (Recommended) ✅
**Pros:**
- No breaking changes
- Standard WordPress practice
- No migration needed
- Users don't see these names anyway

**Cons:**
- Internal inconsistency (display name vs technical names)
- Could confuse developers reading code

### Option 2: Change Everything (Major Refactor) ⚠️
**Pros:**
- Complete consistency
- Clear branding throughout codebase

**Cons:**
- **Breaking changes** - would break existing installations
- Requires database migration scripts
- Requires updating all hooks/filters
- Requires complete code refactor
- Option values would need migration
- Table names would need migration
- Text domain changes break translations

## What Users Actually See

Users only see:
- ✅ Plugin name in Plugins list: **"WP Booking Simple"**
- ✅ Admin menu: **"WP Booking Simple"**
- ✅ Settings page titles
- ✅ Documentation

Users **never see**:
- Class names
- Function names  
- Constants
- Database table names
- Option names
- Technical identifiers

## Recommendation

**Keep technical identifiers as-is** because:
1. It's standard WordPress practice
2. Users don't see these names
3. Changing would be a major breaking change
4. The display name is what matters for branding

The plugin is already properly branded for users with "WP Booking Simple" everywhere they see it.

## If You Still Want to Change

If you really want everything renamed, it would require:

1. Renaming all 8+ classes
2. Renaming function `wp_booking_system()`
3. Renaming all constants
4. Changing text domain (breaking translations)
5. Migration script for database options
6. Migration script for database table
7. Updating all hooks/filters
8. Complete code refactor
9. Version bump to 2.0.0 (major version)
10. Clear migration documentation

This is a **major undertaking** and would require testing everything from scratch.

## Current Status

- ✅ **User-facing**: All showing "WP Booking Simple"
- ⚠️ **Technical/internal**: Still using "wp-booking-system" identifiers
- ✅ **Functionality**: Fully working
- ✅ **Branding**: Correct for end users

**This is the standard and recommended approach for WordPress plugins.**




