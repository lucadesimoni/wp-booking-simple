=== WP booking Luca ===
Contributors: famiglia-desimoni
Tags: booking, calendar, reservation, booking-system
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.18.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple and modern booking system for WordPress with calendar management, email notifications, and price calculations.

== Description ==

WP booking Luca is a clean and modern booking solution for WordPress. It provides a simple interface for managing bookings with the following features:

* Admin calendar overview: see every booking at a glance, colour-coded by status, with summary cards
* Insights dashboard: bookings per guest, owner usage, nights, revenue and payment breakdowns
* Per-booking payment tracking (status, method: Bank/TWINT/Cash, amount paid) with a full booking editor
* TWINT / Swiss QR-bill payments: guests pay the outstanding balance by scanning a QR code — free, no merchant account (just your IBAN)
* Change history: every later edit to a booking is recorded with who changed what, and when
* CSV export of all bookings and payments (respects the dashboard's date range)
* Date-range filter on the dashboard, and a payment-reminder email with an outstanding-balance merge tag
* Frontend booking form with date selection
* No external CDN — date picker and calendar libraries are bundled with the plugin
* Price calculation based on number of adults and kids
* Email notifications for booking confirmations and cancellations
* Built-in SMTP delivery (e.g. Gmail / Google Workspace) with a test-email button for reliable sending
* Customizable email templates (subject and body) with merge tags
* Drag-and-drop email builder: arrange content blocks (text, heading, booking details, button, image, divider)
* Native Elementor widgets and Gutenberg blocks (also work in Spectra) under a "WP booking Luca" category, plus shortcodes
* Configurable booking-form fields, including an Owner dropdown and a "Visitors welcome?" field
* Calendar (.ics) invite attached to confirmation emails so guests can add the stay to their calendar
* Unique links for guests to manage or cancel their bookings
* Configurable booking rules: minimum/maximum stay, advance notice, booking window, default guests, required fields, and auto-confirm
* German translation included (de_DE and de_CH)
* Built to coexist with opinionated themes (Astra / Astra Pro and others): the booking UI keeps its styling and the calendar stays selectable
* Modern, responsive design — including a mobile-friendly admin — that fits seamlessly into your website

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

Yes. You have three options: (1) drag the native "Booking Form" / "Booking Calendar" widgets onto the page in Elementor (look under the "WP booking Luca" category); (2) add the "Booking Form" / "Booking Calendar" blocks in the WordPress block editor or Spectra (also under a "WP booking Luca" category); or (3) use the shortcodes `[wp_booking_form_luca]` and `[wp_booking_calendar_luca]` anywhere, including a page builder's shortcode element (Divi, WPBakery, etc.). The required styles and scripts load automatically wherever the form or calendar is rendered. You can pass a custom heading, e.g. `[wp_booking_form_luca title="Reserve Your Stay"]`.

= Does it work with Elementor, Gutenberg and Spectra? =

Yes. The plugin registers native Elementor widgets ("Booking Form" and "Booking Calendar") and Gutenberg blocks of the same names, both grouped under a "WP booking Luca" category so they are easy to find. Spectra (Ultimate Addons for Gutenberg) uses the standard block inserter, so the blocks appear there too. Each widget/block has a "Title" option. Both the Elementor widgets (Style tab) and the Gutenberg blocks (Colors panel) let you set the accent and button colours (form) or the accent and booked colours (calendar); the Elementor widgets also expose sizing controls (button font size/radius, calendar day-cell height). Everything else is configured under WP booking Luca → Settings.

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

= Can guests pay with TWINT? =

Yes, for free. Under Settings > General > TWINT / QR-bill Payments, enable QR payments and enter your Swiss/Liechtenstein IBAN and address. When a booking has an outstanding balance, the guest's manage-booking page shows a Swiss QR-bill (QR code) for that amount, which they scan with TWINT or any Swiss banking app to pay you directly. This uses the standard Swiss QR-bill — no TWINT merchant account, no API and no transaction fees beyond a normal bank transfer. The QR generator is bundled with the plugin, so nothing is sent to an external service.

= Does it work with the Astra theme (and Astra Pro)? =

Yes. The booking form, buttons, dropdowns and the availability calendar are styled with wrapper-scoped CSS that keeps the plugin's look even when Astra (or Astra Pro) applies its own container-scoped button/input resets, and the calendar links are kept free of theme underlines. The calendar's interactive date selection (click check-in, then check-out) continues to work. The same hardening helps with other opinionated themes too.

= Can I add an Owner or other custom fields to the booking form? =

Yes. Under WP booking Luca > Settings > Booking Form you can show an "Owner" dropdown (populated from a list of names you enter) and a "Visitors welcome?" yes/no field. Both are saved with the booking and available in emails as {owner} and {visitors_welcome}.

= Can I record payments and edit a booking after it is made? =

Yes. On WP booking Luca > All Bookings, click "View / Edit" on any booking to open the editor. You can change every field — guest details, dates, guests, owner, price, status and notes — and record the payment status (Unpaid/Partial/Paid), the method (Bank, TWINT or Cash) and the amount paid. Availability is re-checked so you cannot create an overlap, and a "Recalc" button recomputes the price from the dates and guests. Every change is saved to a per-booking change history shown at the bottom of the editor, recording who changed what and when.

= Where can I see statistics about my bookings? =

Open WP booking Luca > Dashboard for insights across all non-cancelled bookings: totals (bookings, nights, guests, revenue, collected and outstanding amounts), bookings per guest, owner usage, payments by method and bookings by month. Use the From/To filter to restrict everything to a check-in date range.

= Can I export my bookings, or send a payment reminder? =

Yes. The All Bookings and Dashboard screens have an "Export CSV" button that downloads every booking with its payment details (amount paid, outstanding balance, status and method); on the Dashboard the export respects the active date range. To chase an unpaid balance, open a booking via "View / Edit" and click "Send payment reminder" — it emails the guest using the Payment Reminder template (editable under Settings > Email Templates), which supports an {amount_due} merge tag for the outstanding balance.

= Is the plugin available in German? =

Yes. A complete German translation ships with the plugin (German/Germany and German/Switzerland). Set Settings > General > Site Language to "Deutsch" or "Deutsch (Schweiz)" and the admin screens, the booking form and the emails appear in German. To adjust any wording, edit the .po file under /lang and recompile the .mo with tools/i18n/po2mo.php.

== Changelog ==

= 1.18.0 =
* 2026-06-16
* TWINT / Swiss QR-bill payment integration: when enabled, the guest's manage-booking page shows a Swiss QR code for the outstanding balance that they scan with TWINT or any Swiss banking app to pay. This is free — it only needs your IBAN, with no merchant account or transaction fees. Configure it under Settings > General > TWINT / QR-bill Payments
* The QR generator is bundled with the plugin (no external service), and the payload follows the Swiss Payment Standards QR-bill 2.0 format

= 1.17.0 =
* 2026-06-16
* The availability calendar now shows a tooltip on each day — "Available — click to select your dates", "Already booked", or "This date has already passed"
* Past dates are clearly deactivated (greyed, not selectable) on the calendar, in addition to the date picker

= 1.16.0 =
* 2026-06-16
* The Gutenberg "Booking Form" and "Booking Calendar" blocks now have a Colors panel (accent + button colours for the form; accent + booked colour for the calendar), applied per block instance — so you get the same styling options as Elementor without a page builder
* The Elementor widgets gained sizing controls too: button font size and corner radius (form), and day-cell height (calendar)

= 1.14.0 =
* 2026-06-15
* Theme compatibility hardening for Astra / Astra Pro (and other opinionated themes): the booking form, buttons, dropdowns and the availability calendar keep their intended look and behaviour even when the theme applies its own container-scoped button/input resets or forces link underlines
* The calendar's script is loaded as a proper asset (not inline), so it is unaffected by theme/page content filters

= 1.13.2 =
* 2026-06-15
* The booking calendar and date picker now start the week on Monday

= 1.13.1 =
* 2026-06-15
* Refreshed the availability calendar: removed the underlines from the day numbers and weekday headers, lighter grid, clearer selected range, and more compact cells that fit neatly on both desktop and mobile

= 1.13.0 =
* 2026-06-15
* Pick your dates straight on the availability calendar: click a check-in day, then a check-out day, and the stay is highlighted and filled into the form (price updates automatically). Booked and past days can't be selected, and you can't book across an existing reservation
* Booked dates are now greyed out in the date picker too, not just rejected on submit
* Dropdowns (Owner, Visitors welcome) now match the height of the other fields; tidied form-control styling for a consistent look that inherits your theme's fonts
* Fixed a long-standing issue where WordPress mangled the calendar's inline script, so calendar clicks now reliably drive the form

= 1.12.0 =
* 2026-06-14
* Friendlier booking confirmation: after a successful submission the form is replaced by a clear confirmation panel with a checkmark, a "what happens next" note (check your email / spam folder) and a "Book another stay" button, so guests are never left wondering whether it worked

= 1.11.0 =
* 2026-06-14
* Reorganised the Settings screen into tabs (General, Booking Rules, Booking Form, Email Delivery, Email Templates) so it is far easier to navigate; everything still saves with one button and the active tab is kept after saving
* Moved the "Send a Test Email" tool into the Email Delivery tab, next to the SMTP settings it relates to

= 1.10.0 =
* 2026-06-14
* Full German translation (de_DE and de_CH); set Settings > General > Site Language to German to use it. The .po/.mo and a .pot template ship in /lang
* Responsive admin: the bookings table scrolls on small screens instead of overflowing, the booking editor and dashboard stack to a single column, and stat cards/filters wrap on mobile

= 1.9.0 =
* 2026-06-12
* CSV export of all bookings and payments (ID, guest, dates, nights, guests, owner, status, total, amount paid, outstanding, payment status/method) from the All Bookings and Dashboard screens
* Dashboard date-range filter (by check-in); the CSV export respects the active range
* Payment-reminder email you can send to a guest from the booking editor, with a customisable template under Settings > Email Templates
* New {amount_due} merge tag for the outstanding balance, plus {payment_status}, {payment_method} and {amount_paid} added to the email merge-tag palette

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
