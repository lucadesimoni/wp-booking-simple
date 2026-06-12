=== WP booking Luca ===
Contributors: famiglia-desimoni
Tags: booking, calendar, reservation, booking-system
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple and modern booking system for WordPress with calendar management, email notifications, and price calculations.

== Description ==

WP booking Luca is a clean and modern booking solution for WordPress. It provides a simple interface for managing bookings with the following features:

* Admin calendar overview: see every booking at a glance, colour-coded by status, with summary cards
* Insights dashboard: bookings per guest, owner usage, nights, revenue and payment breakdowns
* Per-booking payment tracking (status, method: Bank/TWINT/Cash, amount paid) with a full booking editor
* Change history: every later edit to a booking is recorded with who changed what, and when
* Frontend booking form with date selection
* No external CDN — date picker and calendar libraries are bundled with the plugin
* Price calculation based on number of adults and kids
* Email notifications for booking confirmations and cancellations
* Built-in SMTP delivery (e.g. Gmail / Google Workspace) with a test-email button for reliable sending
* Customizable email templates (subject and body) with merge tags
* Drag-and-drop email builder: arrange content blocks (text, heading, booking details, button, image, divider)
* Configurable booking-form fields, including an Owner dropdown and a "Visitors welcome?" field
* Calendar (.ics) invite attached to confirmation emails so guests can add the stay to their calendar
* Unique links for guests to manage or cancel their bookings
* Configurable booking rules: minimum/maximum stay, advance notice, booking window, default guests, required fields, and auto-confirm
* Modern, responsive design that fits seamlessly into your website

Perfect for vacation rentals, hotels, or any accommodation booking needs.

== Installation ==

1. Upload the plugin ZIP through Plugins > Add New > Upload Plugin, or extract it to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress. On activation it automatically creates a "Book Now" page and a "Manage Booking" page, so everything works immediately.
3. Go to WP booking Luca > Settings to configure pricing, email settings, and chalet capacity
4. To embed elsewhere, use the shortcode `[wp_booking_form_luca]` (form), `[wp_booking_calendar_luca]` (availability calendar) or `[wp_booking_manage_luca]` (management page), or add the "Booking Form" / "Booking Calendar" blocks when editing a page
5. Guests receive a confirmation email containing a unique magic link to the "Manage Booking" page where they can view or cancel their reservation

== Screenshots ==

1. Admin calendar view for managing bookings
2. Frontend booking form with modern design
3. Booking management page for guests

== Frequently Asked Questions ==

= How do I display the booking form? =

Activation creates a "Book Now" page for you. To place the form elsewhere, use the shortcode `[wp_booking_form_luca]` or the "Booking Form" block on any page or post.

= Can I embed the form on an existing page (e.g. my Chalet page)? =

Yes. Add `[wp_booking_form_luca]` (booking form) and/or `[wp_booking_calendar_luca]` (availability calendar) anywhere on the page — in the block/classic editor, or in a page builder's shortcode element (Elementor, Divi, WPBakery, etc.). The required styles and scripts load automatically wherever the form or calendar is rendered, so it works even when the page content is managed by a page builder. You can pass a custom heading, e.g. `[wp_booking_form_luca title="Reserve Your Stay"]`.

= How do guests manage their bookings? =

Guests receive an email with a unique magic link to the auto-created "Manage Booking" page (which uses the `[wp_booking_manage_luca]` shortcode), where they can view or cancel their booking.

= How do I set pricing? =

Go to Bookings > Settings in your WordPress admin and configure the price per adult and price per kid. Prices are calculated per night.

= Can I set booking rules like a minimum stay? =

Yes. Under WP booking Luca > Settings you can configure the minimum and maximum stay (nights), the minimum advance notice and how far ahead guests may book, the default number of adults/kids on the form, whether the phone number is required, whether the notes field is shown, and whether new bookings are confirmed automatically. These rules are applied in the date picker and enforced again on the server.

= Do booking emails send automatically, and can I use Gmail? =

Yes. Confirmation and admin-notification emails are sent automatically when a booking is made (and a cancellation email when a booking is cancelled). By default they go through WordPress's standard mailer, which uses your server's mail and can be unreliable. Under WP booking Luca > Settings you can enable SMTP and enter your mail server details to send through a real mailbox such as Gmail / Google Workspace. For Gmail use host smtp.gmail.com, port 587 (TLS), your full address as the username, and a Google "App Password" as the password. Use the "Send Test Email" button to confirm delivery.

= Does the confirmation email include a calendar invite? =

Yes. The booking confirmation (and the admin notification) include an .ics calendar attachment named booking-{id}.ics. Opening it adds an all-day event from check-in to check-out to Apple Calendar, Google Calendar, Outlook, etc.

= Can I customize the email templates? =

Yes. Under WP booking Luca > Settings > Email Templates you can either edit the plain-text subject and body, or use the drag-and-drop builder to arrange content blocks (Text, Heading, Booking details, Button, Image, Divider). Drag a block by its handle to reorder it. Use merge tags such as {guest_name}, {check_in}, {check_out}, {guests}, {total_price}, {owner}, {visitors_welcome}, {booking_details} and {manage_link}, which are replaced with each booking's details. When a template has blocks, they are used; otherwise the plain-text body applies (clear it to restore the default).

= Can I add an Owner or other custom fields to the booking form? =

Yes. Under WP booking Luca > Settings > Booking Form you can show an "Owner" dropdown (populated from a list of names you enter) and a "Visitors welcome?" yes/no field. Both are saved with the booking and available in emails as {owner} and {visitors_welcome}.

= Can I record payments and edit a booking after it is made? =

Yes. On WP booking Luca > All Bookings, click "View / Edit" on any booking to open the editor. You can change every field — guest details, dates, guests, owner, price, status and notes — and record the payment status (Unpaid/Partial/Paid), the method (Bank, TWINT or Cash) and the amount paid. Availability is re-checked so you cannot create an overlap, and a "Recalc" button recomputes the price from the dates and guests. Every change is saved to a per-booking change history shown at the bottom of the editor, recording who changed what and when.

= Where can I see statistics about my bookings? =

Open WP booking Luca > Dashboard for insights across all non-cancelled bookings: totals (bookings, nights, guests, revenue, collected and outstanding amounts), bookings per guest, owner usage, payments by method and bookings by month.

== Changelog ==

= 1.8.0 =
* 2026-06-12
* Per-booking payment tracking: payment status (Unpaid/Partial/Paid), method (Bank/TWINT/Cash) and amount paid, shown as a Payment column on the bookings list
* Full booking editor: edit any field (guest, dates, guests, owner, price, payment, notes) from a modal, with availability re-checked and price recalculation
* Change history: every later edit is recorded with who changed what and when, shown as a timeline in the editor
* New Insights dashboard: KPIs (bookings, nights, guests, revenue, collected, outstanding), bookings per guest, owner usage, payments by method and bookings by month
* Added {payment_status}, {payment_method} and {amount_paid} email merge tags

= 1.7.0 =
* 2026-06-12
* Admin calendar overview: month/list views with bookings colour-coded by status, summary cards (upcoming/pending/confirmed/total), a legend, and it opens on the next upcoming booking
* Added an Owner column to the All Bookings table, plus Owner and "Visitors welcome?" in the booking details view
* Bundled Flatpickr and FullCalendar locally — the date picker and calendar no longer depend on an external CDN (works behind strict CSP / offline)

= 1.6.0 =
* 2026-06-12
* Drag-and-drop email template builder: arrange Text, Heading, Booking details, Button, Image and Divider blocks per email
* Configurable booking-form fields: new Owner dropdown (from a list you define) and a "Visitors welcome?" yes/no field, with {owner} and {visitors_welcome} merge tags
* Booking table gains owner and visitors_welcome columns (added automatically on upgrade)

= 1.5.0 =
* 2026-06-12
* Booking confirmation and admin emails now include an .ics calendar invite (booking-{id}.ics) so the stay can be added to any calendar
* Improved email styling for dark-mode clients (Outlook/Gmail): forced light color scheme and explicit inline colors

= 1.4.0 =
* 2026-06-12
* Customizable email templates: edit the subject and body of the confirmation, cancellation, and admin emails using merge tags
* Clear a template field and save to restore its default; guest-supplied values are escaped in emails

= 1.3.0 =
* 2026-06-12
* Built-in SMTP delivery so booking emails can be sent through Gmail / Google Workspace or any SMTP server
* Added a "Send Test Email" button in Settings with success/error feedback
* The configured From address/name now applies to all site mail; contextual reminder to enable SMTP

= 1.2.0 =
* 2026-06-11
* Configurable booking entry options: min/max stay, advance-notice and booking window, default guests, required phone, notes field toggle, and auto-confirm
* Stay-length and booking-window rules enforced in the date picker and server-side

= 1.1.1 =
* 2026-06-11
* Booking form/calendar can now be embedded on any existing page or page builder; assets load automatically wherever they render

= 1.1.0 =
* 2026-06-11
* Fixed activation fatal errors and a BOM issue that broke page headers
* Magic-link booking management now works out of the box (auto-created pages)
* Added a Booking Form block and admin Confirm/Cancel actions
* Booking assets now load only where needed
* Added automated tests and a build script

= 1.0.0 =
* 2025-11-28
* Initial release
* Admin calendar interface
* Frontend booking form
* Price calculation based on adults and kids
* Email notifications
* Booking management and cancellation system
