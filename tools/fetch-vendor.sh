#!/usr/bin/env bash
#
# Re-download the third-party front-end libraries bundled under assets/vendor/.
#
# The plugin ships these files instead of loading them from a CDN, so they are
# committed to the repository. Run this script to restore them after a clean
# checkout that lost them, or to upgrade to a newer release (bump the versions
# below, run the script, then update assets/vendor/README.md and the version
# strings passed to wp_register_script()/wp_enqueue_script()).
#
# Usage: ./tools/fetch-vendor.sh

set -euo pipefail

FLATPICKR_VERSION="4.6.13"
FULLCALENDAR_VERSION="6.1.10"
QRCODE_GENERATOR_VERSION="1.4.4"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VENDOR="${ROOT}/assets/vendor"
WORK="$(mktemp -d)"
trap 'rm -rf "${WORK}"' EXIT

# Download an npm package and unpack it into $WORK/<name>.
fetch_package() {
	local name="$1" version="$2"
	local url="https://registry.npmjs.org/${name}/-/${name}-${version}.tgz"

	echo "Fetching ${name}@${version}..."
	mkdir -p "${WORK}/${name}"
	curl -fsSL "${url}" | tar xz -C "${WORK}/${name}" --strip-components=1
}

fetch_package "flatpickr" "${FLATPICKR_VERSION}"
fetch_package "fullcalendar" "${FULLCALENDAR_VERSION}"
fetch_package "qrcode-generator" "${QRCODE_GENERATOR_VERSION}"

echo "Installing into assets/vendor/..."
mkdir -p "${VENDOR}/flatpickr" "${VENDOR}/fullcalendar" "${VENDOR}/qrcode"

cp "${WORK}/flatpickr/dist/flatpickr.min.js"  "${VENDOR}/flatpickr/"
cp "${WORK}/flatpickr/dist/flatpickr.min.css" "${VENDOR}/flatpickr/"
cp "${WORK}/flatpickr/LICENSE.md"             "${VENDOR}/flatpickr/LICENSE.md"

cp "${WORK}/fullcalendar/index.global.min.js" "${VENDOR}/fullcalendar/"
cp "${WORK}/fullcalendar/LICENSE.md"          "${VENDOR}/fullcalendar/LICENSE.md"

# qrcode-generator exposes a `qrcode` global and takes the error-correction
# level as a letter. Append the plugin's adapter so the bundled file also
# exposes the prefixed `WPBSLQRCode` global used by assets/js/frontend.js.
# See assets/vendor/README.md.
cat "${WORK}/qrcode-generator/qrcode.js" "${ROOT}/tools/qrcode-adapter.js" > "${VENDOR}/qrcode/qrcode.js"

echo "Done. Bundled libraries are up to date:"
echo "  flatpickr        ${FLATPICKR_VERSION}"
echo "  fullcalendar     ${FULLCALENDAR_VERSION}"
echo "  qrcode-generator ${QRCODE_GENERATOR_VERSION}"
