# Translations (i18n)

The plugin's translatable strings use the text domain `wp-booking-simple`
and are loaded from `lang/` (see the `Domain Path` header and
`load_plugin_textdomain()` call).

## Files in `lang/`

- `wp-booking-simple.pot` — template with every source string.
- `wp-booking-simple-de_DE.po` / `.mo` — German (Germany).
- `wp-booking-simple-de_CH.po` / `.mo` — German (Switzerland), same content.

WordPress loads the `.mo` matching the site language (Settings → General →
Site Language). Set it to *Deutsch* (`de_DE`) or *Deutsch (Schweiz)* (`de_CH`)
to see the German UI and emails.

## Editing a translation

The `.po` files are the source of truth. Edit the `msgstr` lines, then
recompile the `.mo` (no gettext tooling required):

```
php tools/i18n/po2mo.php lang/wp-booking-simple-de_DE.po
```

## Adding a new language

Copy `wp-booking-simple.pot` to
`lang/wp-booking-simple-<locale>.po`, translate the `msgstr` entries,
then run `po2mo.php` on it.

## Regenerating the template after code changes

`extract-strings.php` lists every translatable string found in the PHP source
(used to build the `.pot`). After adding or changing strings, re-extract and
update the `.po` files with the new/changed entries before recompiling.
