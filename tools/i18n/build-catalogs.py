#!/usr/bin/env python3
"""Rebuild lang/*.pot and the German .po catalogs from the PHP source.

The template used to be maintained by hand, which is how 33 strings ended up
untranslated: extract-strings.php only listed them, nothing checked that the
catalogs kept up. This script closes that loop.

  1. reads every translatable string via tools/i18n/extract-strings.php,
  2. writes a fresh .pot,
  3. rewrites de_DE.po and de_CH.po, carrying over every existing translation
     verbatim and filling the gaps from NEW_TRANSLATIONS below,
  4. reports anything still untranslated (non-zero exit), so a future string
     cannot silently ship in English.

de_CH is de_DE in Swiss orthography: Switzerland does not use "ß".

Usage: python3 tools/i18n/build-catalogs.py
Then:  php tools/i18n/po2mo.php lang/wp-booking-simple-de_DE.po
"""

import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
LANG = ROOT / "lang"
DOMAIN = "wp-booking-simple"

# Translations for strings that had none. Existing ones are reused from the
# current .po files and never overwritten here.
NEW_TRANSLATIONS = {
    "%d adult": ("%d Erwachsener", "%d Erwachsene"),
    "%d kid": ("%d Kind", "%d Kinder"),
    "Accent color": "Akzentfarbe",
    "Booked color": "Farbe für belegte Tage",
    "Button background": "Hintergrund der Schaltfläche",
    "Button corner radius": "Eckenradius der Schaltfläche",
    "Button font size": "Schriftgröße der Schaltfläche",
    "Button hover background": "Hintergrund der Schaltfläche (Mauszeiger darüber)",
    "Button text color": "Textfarbe der Schaltfläche",
    "Day cell height": "Höhe der Tageszelle",
    "IBAN": "IBAN",
    "Kids Field": "Feld „Kinder“",
    "Last Name Field": "Feld „Nachname“",
    "Requiring a phone number only applies while the field is shown.":
        "Die Telefonpflicht gilt nur, solange das Feld angezeigt wird.",
    "Select a date": "Datum wählen",
    "Show a monthly availability calendar.":
        "Zeigt einen Monatskalender mit der Verfügbarkeit.",
    "Show the booking form with live price and availability.":
        "Zeigt das Buchungsformular mit Live-Preis und Verfügbarkeit.",
    "Show the last name field on the booking form.":
        "Das Feld „Nachname“ im Buchungsformular anzeigen.",
    "Show the number of kids on the booking form.":
        "Die Anzahl der Kinder im Buchungsformular anzeigen.",
    "Show the phone field on the booking form.":
        "Das Feld „Telefon“ im Buchungsformular anzeigen.",
    "Style": "Stil",
    "Title": "Titel",
    "Turn this off to ask for a first name only. Existing bookings keep the last name they were made with.":
        "Ausschalten, um nur nach dem Vornamen zu fragen. Bestehende Buchungen "
        "behalten den Nachnamen, mit dem sie erstellt wurden.",
    "With this off, every booking counts as adults only and the kid price is never applied.":
        "Ist dies ausgeschaltet, zählt jede Buchung nur Erwachsene und der "
        "Kinderpreis wird nie angewendet.",
    "Pricing, fields and booking rules are configured under WP Booking Simple → Settings.":
        "Preise, Felder und Buchungsregeln werden unter WP Booking Simple → "
        "Einstellungen konfiguriert.",
    "Please enter a valid Swiss/Liechtenstein IBAN (CH… or LI…) to enable TWINT / QR-bill payments.":
        "Bitte geben Sie eine gültige Schweizer oder Liechtensteiner IBAN "
        "(CH… oder LI…) ein, um Zahlungen per TWINT / QR-Rechnung zu "
        "aktivieren.",
    "Booking #%1$d %2$s %3$s": "Buchung Nr. %1$d %2$s %3$s",
    "Dear {guest_name},\n\nThank you for your booking! We are pleased to confirm your reservation.\n\n"
    "{booking_details}\n\n{payment_info}\n\nYou can manage or cancel your booking using the link below:\n\n"
    "{manage_link}\n\nWe look forward to welcoming you!\n\nBest regards,\n{site_name}":
        "Liebe/r {guest_name},\n\nvielen Dank für Ihre Buchung! Wir freuen uns, "
        "Ihre Reservierung zu bestätigen.\n\n{booking_details}\n\n{payment_info}\n\n"
        "Sie können Ihre Buchung über den folgenden Link verwalten oder "
        "stornieren:\n\n{manage_link}\n\nWir freuen uns darauf, Sie begrüßen zu "
        "dürfen!\n\nMit freundlichen Grüßen,\n{site_name}",
    "Button typography": "Typografie der Schaltfläche",
    "Colors": "Farben",
    "Form background": "Hintergrund des Formulars",
    "Form padding": "Innenabstand des Formulars",
    "Form shadow": "Schatten des Formulars",
    "Plugin colors (dark red)": "Plugin-Farben (Dunkelrot)",
    "Theme colors": "Theme-Farben",
    "Theme colors use your theme's brand color, buttons and heading font: the Astra global palette and Customizer buttons, Elementor's global colors, or a block theme's palette. Colors set on a block or Elementor widget still take precedence.":
        "Theme-Farben übernehmen die Markenfarbe, die Schaltflächen und die "
        "Überschriftenschrift Ihres Themes: die globale Astra-Palette und die "
        "Customizer-Schaltflächen, die globalen Farben von Elementor oder die "
        "Palette eines Block-Themes. Farben, die an einem Block oder "
        "Elementor-Widget gesetzt sind, haben weiterhin Vorrang.",
    "Title color": "Farbe des Titels",
    "Title typography": "Typografie des Titels",
}


def po_escape(text):
    return (
        text.replace("\\", "\\\\")
        .replace('"', '\\"')
        .replace("\n", "\\n")
        .replace("\t", "\\t")
    )


def po_unescape(text):
    out, i = [], 0
    while i < len(text):
        ch = text[i]
        if ch == "\\" and i + 1 < len(text):
            nxt = text[i + 1]
            out.append({"n": "\n", "t": "\t", '"': '"', "\\": "\\"}.get(nxt, nxt))
            i += 2
        else:
            out.append(ch)
            i += 1
    return "".join(out)


def parse_po(path):
    """Return {msgid: msgstr_or_tuple_of_plural_forms}."""
    if not path.exists():
        return {}
    entries, cur, key = {}, {}, None
    for raw in path.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#"):
            if not line:
                _flush(entries, cur)
                cur, key = {}, None
            continue
        m = re.match(r'^(msgid|msgid_plural|msgctxt|msgstr(?:\[\d+\])?)\s+"(.*)"$', line)
        if m:
            key = m.group(1)
            cur[key] = cur.get(key, "") + po_unescape(m.group(2))
            continue
        m = re.match(r'^"(.*)"$', line)
        if m and key:
            cur[key] += po_unescape(m.group(1))
    _flush(entries, cur)
    entries.pop("", None)
    return entries


def _flush(entries, cur):
    mid = cur.get("msgid")
    if not mid:
        return
    if "msgid_plural" in cur:
        forms = [cur.get(f"msgstr[{i}]", "") for i in (0, 1)]
        if any(forms):
            entries[mid] = tuple(forms)
    elif cur.get("msgstr"):
        entries[mid] = cur["msgstr"]


HEADER = '''msgid ""
msgstr ""
"Project-Id-Version: WP Booking Simple\\n"
"Report-Msgid-Bugs-To: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Language: {lang}\\n"
"Language-Team: {team}\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"
"X-Domain: {domain}\\n"
'''


def render(entries, translations, lang, team):
    out = [HEADER.format(lang=lang, team=team, domain=DOMAIN)]
    for e in entries:
        mid, plural = e["msgid"], e.get("plural")
        tr = translations.get(mid)
        out.append("")
        if e.get("context"):
            out.append(f'msgctxt "{po_escape(e["context"])}"')
        out.append(f'msgid "{po_escape(mid)}"')
        if plural:
            out.append(f'msgid_plural "{po_escape(plural)}"')
            forms = tr if isinstance(tr, tuple) else ("", "")
            out.append(f'msgstr[0] "{po_escape(forms[0])}"')
            out.append(f'msgstr[1] "{po_escape(forms[1])}"')
        else:
            text = tr if isinstance(tr, str) else ""
            out.append(f'msgstr "{po_escape(text)}"')
    return "\n".join(out) + "\n"


def swiss(value):
    if isinstance(value, tuple):
        return tuple(v.replace("ß", "ss") for v in value)
    return value.replace("ß", "ss")


def main():
    raw = subprocess.run(
        ["php", "tools/i18n/extract-strings.php"],
        cwd=ROOT, capture_output=True, text=True, check=True,
    ).stdout
    entries = json.loads(raw)

    existing = parse_po(LANG / f"{DOMAIN}-de_DE.po")
    german = dict(existing)
    for mid, tr in NEW_TRANSLATIONS.items():
        german.setdefault(mid, tr)

    (LANG / f"{DOMAIN}.pot").write_text(
        render(entries, {}, "", "LANGUAGE <LL@li.org>"), encoding="utf-8"
    )
    (LANG / f"{DOMAIN}-de_DE.po").write_text(
        render(entries, german, "de_DE", "German"), encoding="utf-8"
    )
    (LANG / f"{DOMAIN}-de_CH.po").write_text(
        render(entries, {k: swiss(v) for k, v in german.items()}, "de_CH", "German (Switzerland)"),
        encoding="utf-8",
    )

    missing = [e["msgid"] for e in entries if not german.get(e["msgid"])]
    print(f"Strings: {len(entries)}   übersetzt: {len(entries) - len(missing)}   fehlend: {len(missing)}")
    for m in missing:
        print("  FEHLT:", m[:100])
    return 1 if missing else 0


if __name__ == "__main__":
    sys.exit(main())
