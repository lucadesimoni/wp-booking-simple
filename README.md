# WP booking Luca

A simple, modern booking system for WordPress — built for a single property (a
Swiss chalet), with an availability calendar, a live-priced booking form, Swiss
payment (TWINT / QR-bill), and email notifications. Guests manage their own
booking through a secure magic link — **no WordPress account required**.

Current version: **1.20.0** · Requires WordPress 5.0+ and PHP 7.4+.

## Features

- **Availability calendar** — interactive monthly calendar (FullCalendar) showing
  free/booked nights. Click a check-in then a check-out to select a stay; the
  dates flow straight into the booking form. Available as a block, a widget, or a
  shortcode.
- **Booking form** — clean, responsive form with live price and availability
  checks. Price is calculated per night from the adult/kid rates you configure.
- **Swiss payments** — offer **TWINT** and a **Swiss QR-bill** (QR-bill 2.0) at
  checkout and on the manage page. The QR code is generated locally (bundled MIT
  library) — no external service, no data leaves the site.
- **Magic-link booking management** — each confirmation email carries a unique,
  tokenised link. Guests view their booking, see what's outstanding, pay, or
  cancel — all without logging in.
- **Email notifications** — automatic confirmation, cancellation, and reminder
  emails with a visual template builder and merge tags (booking details, payment
  info, manage link). One click inserts ready-made **German** templates.
- **Full payment lifecycle** — unpaid / partially paid / paid / refunded, with the
  outstanding balance surfaced to both admin and guest.
- **Admin overview** — visual calendar of all bookings, a filterable bookings
  list, and settings for pricing, capacity, payment details, and email.
- **German localisation** — ships with `de_DE` / `de_CH` translations.
- **WordPress best practices** — nonces, capability checks, escaping, and a custom
  bookings table created on activation.

## Installation

1. Download `wp-booking-luca.zip` (or build it with `./build.sh`).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the zip, and
   activate.
3. Go to **WP booking Luca → Settings** to configure:
   - Price per adult and per kid (per night)
   - Currency (e.g. CHF, EUR)
   - Maximum capacity
   - Payment details (account name, IBAN, TWINT) for the QR-bill
   - Email from-address / from-name and the admin notification address

## Placing the calendar and form

The calendar is available three ways — pick whichever suits your theme:

**1. Gutenberg block (recommended)**
Edit a page, click **+**, search the **WP booking Luca** category for
**Booking Calendar** or **Booking Form**. Colours are configurable in the block
sidebar.

**2. Classic widget**
Go to **Appearance → Widgets** and add the **Booking Calendar** widget to any
sidebar. It renders the same interactive calendar as the block/shortcode, so
multiple calendars can coexist on one page.

**3. Shortcodes**

```
[wp_booking_form]                 Booking form
[wp_booking_form title="Book Your Stay"]

[wp_booking_calendar]             Availability calendar
[wp_booking_calendar title="Check Availability"]

[wp_booking_manage]              Guest booking-management page (see below)
```

## Guest booking management (magic link)

Create a page and add the `[wp_booking_manage]` shortcode. Guests reach it through
the tokenised link in their confirmation email:

```
yoursite.com/manage-booking/?token=BOOKING_TOKEN
```

There they see the booking status, reference, nights, amount outstanding, a TWINT /
QR payment option, and a cancel button — no login required.

## Email templates

Under **WP booking Luca → Settings → Email Templates** you can edit the
confirmation, cancellation, and reminder emails with a visual builder. Available
merge tags include `{booking_details}`, `{payment_info}`, `{manage_link}`,
`{site_name}`, and granular payment tags (`{payment_iban}`, `{payment_twint}`,
`{payment_twint_url}`, …). Use **Insert German templates** for ready-made
German copy.

## How it works

1. Guest picks dates on the calendar and submits the form.
2. Price is calculated from nights × (adults, kids); availability is verified.
3. A confirmation email goes out with the booking details, payment info, and a
   magic-link management URL.
4. Guest pays via TWINT / QR-bill and can view or cancel their booking through the
   link.
5. Admin tracks everything from the booking calendar and list, and updates payment
   status as money arrives.

## Development

- **Tests:** `php tests/standalone/run.php` (standalone, no WP install needed).
- **Build:** `./build.sh` → `dist/wp-booking-luca.zip`.
- **Translations:** edit `lang/*.po`, then compile with `php tools/i18n/po2mo.php`.
- Text domain: `wp-booking-system-luca`. Bookings are stored in a custom table.

## License

GPL-2.0-or-later.
