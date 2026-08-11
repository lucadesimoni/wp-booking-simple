#!/usr/bin/env bash
#
# Build a distributable WordPress plugin ZIP.
#
# Produces dist/wp-booking-luca.zip containing only the runtime files, laid out
# inside a `wp-booking-luca/` folder so it installs cleanly via
# Plugins → Add New → Upload Plugin.
#
# Usage: ./build.sh

set -euo pipefail

SLUG="wp-booking-luca"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST="${ROOT}/dist"
STAGE="${DIST}/${SLUG}"

# Files/folders that make up the shippable plugin.
INCLUDE=(
	"wp-booking-system.php"
	"uninstall.php"
	"index.php"
	"readme.txt"
	"changelog.txt"
	"LICENSE"
	"includes"
	"assets"
	"lang"
)

# Runtime files the plugin enqueues by URL. A missing one does not fail the
# build on its own, it just 404s in the browser and silently kills the date
# picker, calendar or payment QR code, so verify them up front instead of
# shipping a broken ZIP. See assets/vendor/README.md.
REQUIRED_ASSETS=(
	"assets/css/frontend.css"
	"assets/css/admin.css"
	"assets/js/frontend.js"
	"assets/js/admin.js"
	"assets/js/admin-template-builder.js"
	"assets/js/block.js"
	"assets/vendor/flatpickr/flatpickr.min.css"
	"assets/vendor/flatpickr/flatpickr.min.js"
	"assets/vendor/fullcalendar/index.global.min.js"
	"assets/vendor/qrcode/qrcode.js"
)

echo "Verifying plugin files..."
missing=()
for item in "${INCLUDE[@]}" "${REQUIRED_ASSETS[@]}"; do
	if [ ! -e "${ROOT}/${item}" ]; then
		missing+=("${item}")
	fi
done

if [ ${#missing[@]} -gt 0 ]; then
	echo "ERROR: cannot build, these required files are missing:" >&2
	for item in "${missing[@]}"; do
		echo "  - ${item}" >&2
	done
	echo >&2
	echo "If the bundled libraries under assets/vendor/ are missing, run" >&2
	echo "./tools/fetch-vendor.sh to restore them." >&2
	exit 1
fi

echo "Cleaning previous build..."
rm -rf "${STAGE}" "${DIST}/${SLUG}.zip"
mkdir -p "${STAGE}"

echo "Staging plugin files..."
for item in "${INCLUDE[@]}"; do
	cp -R "${ROOT}/${item}" "${STAGE}/"
done

# Strip any stray VCS/OS cruft from the staged copy.
find "${STAGE}" -name '.DS_Store' -delete 2>/dev/null || true
find "${STAGE}" -name '*.map' -delete 2>/dev/null || true

echo "Creating ZIP..."
( cd "${DIST}" && zip -rq "${SLUG}.zip" "${SLUG}" )

echo "Cleaning staging directory..."
rm -rf "${STAGE}"

echo "Done: dist/${SLUG}.zip"
