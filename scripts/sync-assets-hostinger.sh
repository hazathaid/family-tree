#!/usr/bin/env bash
#
# Sinkronkan public/build (hasil npm run build) ke document root Hostinger.
#
# Pemakaian:
#   ./scripts/sync-assets-hostinger.sh
#   PUBLIC_HTML=/home/u407782993/domains/domain.com/public_html ./scripts/sync-assets-hostinger.sh
#
# APP_DIR otomatis = folder induk script ini (root proyek, tempat artisan berada).
# Kalau public_html tidak diberikan, script mencari otomatis:
#   /home/<user>/**/public_html (maksimal 5 level)

set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
PUBLIC_HTML="${PUBLIC_HTML:-}"

if [ ! -d "$APP_DIR/public/build/assets" ]; then
    printf 'ERROR: %s/public/build/assets tidak ada. Jalankan npm run build dulu.\n' "$APP_DIR" >&2
    exit 1
fi

printf '\n==> Asset sumber (%s/public/build/assets):\n' "$APP_DIR"
ls -la "$APP_DIR/public/build/assets"

if [ -z "$PUBLIC_HTML" ]; then
    CANDIDATES="$(find "$HOME" -maxdepth 5 -type d -name public_html 2>/dev/null || true)"
    COUNT="$(printf '%s\n' "$CANDIDATES" | sed '/^$/d' | wc -l | tr -d ' ')"
    if [ "$COUNT" = "1" ]; then
        PUBLIC_HTML="$CANDIDATES"
    else
        printf '\nDitemukan %s folder public_html. Set PUBLIC_HTML secara eksplisit:\n' "$COUNT"
        printf '%s\n' "$CANDIDATES"
        printf '\nContoh:\n  PUBLIC_HTML=/path/ke/public_html %s\n' "$0"
        exit 1
    fi
fi

if [ ! -d "$PUBLIC_HTML" ]; then
    printf 'ERROR: PUBLIC_HTML tidak ditemukan: %s\n' "$PUBLIC_HTML" >&2
    exit 1
fi

printf '\n==> Menyalin asset ke %s/build\n' "$PUBLIC_HTML"
mkdir -p "$PUBLIC_HTML/build/assets"
cp -Rf "$APP_DIR/public/build/." "$PUBLIC_HTML/build/"
find "$PUBLIC_HTML/build" -type f -exec chmod 644 {} +
find "$PUBLIC_HTML/build" -type d -exec chmod 755 {} +

printf '\n==> Asset tujuan (%s/build/assets):\n' "$PUBLIC_HTML"
ls -la "$PUBLIC_HTML/build/assets"

printf '\n==> Membersihkan cache Laravel\n'
"$PHP_BIN" artisan optimize:clear || true

cat <<'EOF'

==> Selesai.
1) Purge cache di hPanel Hostinger (Cache Manager), lalu hard refresh Ctrl+Shift+R.
2) Tes dari server (ganti DOMAIN):
   curl -I https://DOMAIN/build/assets/app-B_t82eN4.js
   Harus HTTP 200 dengan content-type application/javascript.
EOF
