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

```
python3 tools/i18n/build-catalogs.py
php tools/i18n/po2mo.php lang/wp-booking-simple-de_DE.po
php tools/i18n/po2mo.php lang/wp-booking-simple-de_CH.po
```

`build-catalogs.py` re-reads every translatable string from the source (via
`extract-strings.php`), rewrites the `.pot`, and rewrites both German `.po`
files — carrying existing translations over untouched and filling gaps from
its `NEW_TRANSLATIONS` table. It **exits non-zero and names any string that
still has no translation**, so a new string cannot quietly ship in English.

Add the German text for new strings to `NEW_TRANSLATIONS` in that script (or
edit the `.po` directly and re-run it; existing translations always win).

`de_CH` is generated from `de_DE` with `ß` replaced by `ss`, so only `de_DE`
needs translating.

The template used to be maintained by hand, which is how 33 strings ended up
untranslated at once — `extract-strings.php` listed them but nothing checked
that the catalogs kept up. Run the script instead of editing the `.pot`.
