# Bundled third-party libraries

The plugin deliberately ships these libraries instead of loading them from a
CDN, so the date picker, availability calendar and payment QR code keep working
behind a strict Content-Security-Policy, on intranet sites, and when the
visitor's network blocks third-party hosts.

**These files must stay committed.** They are enqueued by
`includes/class-wp-booking-system-luca-frontend.php` and
`includes/class-wp-booking-system-luca-admin.php`; if they are missing the
browser gets a 404 and the date picker, calendar and QR code silently do
nothing. `build.sh` fails the build rather than publishing a ZIP without them.

| Path | Package | Version | License |
| --- | --- | --- | --- |
| `flatpickr/flatpickr.min.js`, `flatpickr/flatpickr.min.css` | [flatpickr](https://www.npmjs.com/package/flatpickr) | 4.6.13 | MIT |
| `fullcalendar/index.global.min.js` | [fullcalendar](https://www.npmjs.com/package/fullcalendar) | 6.1.10 | MIT |
| `qrcode/qrcode.js` | [qrcode-generator](https://www.npmjs.com/package/qrcode-generator) | 1.4.4 | MIT |

The version numbers registered with `wp_register_script()` / `wp_enqueue_script()`
must match the table above, so that cache busting follows a library upgrade.

## Updating

Run `tools/fetch-vendor.sh` to re-download every library at the pinned version:

```bash
./tools/fetch-vendor.sh
```

To move to a newer release, bump the version in `tools/fetch-vendor.sh`, re-run
it, then update both this table and the version strings in the two PHP classes
listed above.

## Local modification

`qrcode/qrcode.js` is upstream `qrcode-generator` with one appended block,
marked `WP booking Luca adapter`, which:

* exposes the library as the prefixed global `WPBSLQRCode`, so it cannot
  collide with another plugin or theme that ships its own `qrcode` global, and
* accepts the numeric error-correction level from the QR specification
  (`0` = M, as the Swiss QR-bill requires) in addition to upstream's letter
  form.

`tools/fetch-vendor.sh` re-applies this adapter automatically, so re-running it
never loses the modification. No other vendored file is patched.
