# WP booking Luca

A simple, modern booking system for WordPress. Built for holiday chalets and
short-stay rentals, but works for any accommodation booking. Ships with an
admin calendar, a frontend form and availability calendar, an insights
dashboard, Swiss QR-bill / TWINT payments, and a guest self-service page
reached via a magic link — no external services required.

Current version: **1.20.0** — see [`changelog.txt`](changelog.txt) for the
full history.

## Highlights

- **Admin calendar overview** with colour-coded booking status and summary
  cards
- **Insights dashboard**: bookings per guest, owner usage, nights, revenue
  and payment breakdowns, with a date-range filter
- **Full booking editor** with per-booking payment tracking (status,
  method — Bank / TWINT / Cash / Refunded, amount paid)
- **TWINT / Swiss QR-bill payments** — free, no merchant account. Guests
  pay outstanding balances by scanning a Swiss QR-bill 2.0 code with any
  Swiss banking app. Generated locally; nothing is sent to a third party.
- **Change history** — every edit to a booking is recorded with who,
  what, when
- **CSV export** of bookings and payments (respects the dashboard date
  range)
- **Emails**: confirmation, cancellation and payment reminder templates
  with merge tags (including outstanding balance, IBAN, TWINT link).
  Drag-and-drop template builder. Built-in SMTP delivery with a
  test-email button. Confirmation emails include an `.ics` calendar
  invite.
- **Frontend booking form** with date-picker and configurable fields
  (owner dropdown, "visitors welcome?", required fields, defaults,
  min/max stay, advance notice, booking window, auto-confirm)
- **Availability calendar** with tooltips, past-date deactivation, and
  click-to-select date ranges
- **Native Elementor widgets** (`Booking Form`, `Booking Calendar`) under
  a dedicated "WP booking Luca" category, with Style tab controls
- **Gutenberg blocks** in a dedicated category (also work in Spectra),
  with colour and sizing controls
- **Shortcodes** for anywhere else
- **Magic-link guest page** (`Manage Booking`) with a colour-coded status
  banner, booking reference, nights, and payment status with the
  TWINT/QR panel when there is an outstanding balance
- **German translations** (`de_DE`, `de_CH`) included
- **Theme-compatible** — hardened against opinionated themes like Astra /
  Astra Pro
- **No external CDNs** — the date picker, calendar library, and QR-bill
  generator are bundled with the plugin

## Installation

Download the latest `wp-booking-luca.zip` from the
[**Releases page**](https://github.com/lucadesimoni/wp-booking-simple/releases/latest).

1. In WordPress, go to **Plugins → Add New → Upload Plugin** and choose
   the ZIP.
2. Activate the plugin. Activation creates a "Book Now" page and a
   "Manage Booking" page so the flow works immediately.
3. Go to **WP booking Luca → Settings** to configure pricing, email,
   SMTP and TWINT / QR-bill payment details.

Requirements: WordPress 5.0+ (tested up to 6.9) and PHP 7.4+.

## Placing the booking UI

Any of these produce the same booking form / calendar / manage view.

**Shortcodes**

```text
[wp_booking_form_luca]
[wp_booking_form_luca title="Book Your Stay"]

[wp_booking_calendar_luca]
[wp_booking_calendar_luca title="Check Availability"]

[wp_booking_manage_luca]
```

**Gutenberg blocks** — in the block inserter, category *WP booking Luca*:
`Booking Form`, `Booking Calendar`.

**Elementor widgets** — in the Elementor panel, category
*WP booking Luca*: `Booking Form`, `Booking Calendar`. Each widget has a
Style tab for accent, button and calendar colours, and sizing.

**Classic widget** — under **Appearance → Widgets**, drag the
*Booking Calendar* widget into a sidebar.

## Payments (TWINT / Swiss QR-bill)

Under **Settings → General → TWINT / QR-bill Payments**, add your
account holder, bank name, IBAN and (optionally) a TWINT pay-link URL.
Then:

- The post-booking confirmation shows the Swiss QR code + a *Pay with
  TWINT* button.
- The guest manage page shows the same panel while a balance is
  outstanding.
- The `{payment_info}` merge tag drops the same block into any email.
  Granular tags — `{payment_account}`, `{payment_bank}`,
  `{payment_iban}`, `{payment_twint}` — let you build a custom
  "Bankdaten" block.

The QR payload follows Swiss Payment Standards QR-bill 2.0 and is
generated locally with the bundled MIT-licensed QR generator by
Kazuhiko Arase.

## Guest flow

1. Guest picks dates and fills out the form.
2. Price is calculated automatically from adults × kids × nights.
3. Availability is checked against existing bookings.
4. Guest receives a confirmation email with an `.ics` invite and a
   unique magic link to the *Manage Booking* page.
5. On the manage page the guest can see status, nights, outstanding
   balance and scan the TWINT/QR code — or cancel.
6. Every admin edit is recorded in the change history.

## Development

**Source layout**

```text
wp-booking-system.php     Plugin bootstrap (defines constants, loads classes)
includes/                 All PHP classes (helpers, stats, admin, frontend,
                          ajax, email, widget, block, Elementor)
includes/elementor/       Elementor widget definitions
assets/                   CSS / JS / bundled libraries (date picker,
                          calendar, QR generator)
lang/                     German translations (de_DE, de_CH)
readme.txt                WordPress.org-style plugin readme
changelog.txt             Human-readable change history (source of truth)
build.sh                  Builds dist/wp-booking-luca.zip
```

**Database**

The plugin creates two tables on activation:

- `{prefix}wpbsl_bookings` — bookings, payments and metadata
- `{prefix}wpbsl_history` — per-booking change history

Both are removed on `uninstall.php`.

**Build the ZIP locally**

```sh
./build.sh
# → dist/wp-booking-luca.zip
```

The ZIP contains only the runtime files (`wp-booking-system.php`,
`includes/`, `assets/`, `lang/`, `readme.txt`, `changelog.txt`,
`LICENSE`, `index.php`, `uninstall.php`) inside a `wp-booking-luca/`
folder, so it installs cleanly via *Plugins → Add New → Upload Plugin*.

## Versioning & releases

The plugin follows [semantic versioning](https://semver.org/) roughly:
minor bumps for user-visible additions, patch bumps for fixes and small
tweaks. The version is the source-of-truth string in three places, and
they must stay in sync:

- `wp-booking-system.php` — `Version:` header and
  `WP_BOOKING_SYSTEM_LUCA_VERSION` constant
- `readme.txt` — `Stable tag:` line
- `changelog.txt` — leading entry

To cut a release:

1. Bump the version in the three files above and add a `changelog.txt`
   entry.
2. Merge to `main`.
3. Tag the commit: `git tag v1.X.Y && git push origin v1.X.Y`.
4. The release workflow builds `wp-booking-luca.zip` and publishes a
   GitHub Release with it attached.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).
