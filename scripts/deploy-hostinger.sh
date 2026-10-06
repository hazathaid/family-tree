#!/usr/bin/env bash
#
# Deploy/update script for Family Tree Platform on Hostinger shared hosting
# (Web Premium / Web Business). Run it after `git pull` — either from the
# hPanel Git "deployment command" hook or manually over SSH.
#
# Usage:
#   APP_DIR=/home/u123456789/domains/demo.example.com/app \
#   PUBLIC_HTML=/home/u123456789/domains/demo.example.com/public_html \
#   ./scripts/deploy-hostinger.sh
#
# All variables are optional except APP_DIR when the default path does not match.
#
#   APP_DIR      Absolute path to the Laravel app directory (contains artisan).
#   PUBLIC_HTML  Absolute path to public_html; when set, built assets are copied there.
#   PHP_BIN      PHP 8.3 CLI binary (default: /opt/alt/php83/usr/bin/php).
#   COMPOSER_BIN Composer binary (default: composer2).
#   BRANCH       Git branch to deploy (default: main).
#   SKIP_MIGRATE Set to 1 to skip `migrate --force`.
#   SKIP_NPM     Set to 1 to skip asset build even when npm is available.
#
# This script does NOT touch public/index.php (shared hosting uses a custom path).

set -euo pipefail

APP_DIR="${APP_DIR:-$PWD}"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer2}"
BRANCH="${BRANCH:-main}"
SKIP_MIGRATE="${SKIP_MIGRATE:-0}"
SKIP_NPM="${SKIP_NPM:-0}"

log() { printf '\n==> %s\n' "$1"; }

if [ ! -f "$APP_DIR/artisan" ]; then
    printf 'ERROR: artisan not found in APP_DIR=%s\n' "$APP_DIR" >&2
    exit 1
fi

cd "$APP_DIR"

# Resolve the Composer binary to an absolute path so it can be executed by
# PHP_BIN explicitly. The default PHP CLI on shared hosting may be older than
# the version required by composer.lock (platform check).
COMPOSER_CMD="$(command -v "$COMPOSER_BIN" 2>/dev/null || printf '%s' "$COMPOSER_BIN")"

log "Putting the application into maintenance mode"
"$PHP_BIN" artisan down --retry=60 || true

log "Pulling latest $BRANCH"
git pull --ff-only origin "$BRANCH"

log "Installing production dependencies"
"$PHP_BIN" "$COMPOSER_CMD" install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --ignore-platform-req=ext-pcntl

if [ "$SKIP_NPM" != "1" ] && command -v npm >/dev/null 2>&1; then
    log "Building frontend assets"
    npm ci --no-audit --no-fund || npm install --no-audit --no-fund
    npm run build
elif [ "$SKIP_NPM" != "1" ]; then
    log "npm not available: build public/build locally or in CI and upload it"
fi

if [ -n "${PUBLIC_HTML:-}" ] && [ -d "$APP_DIR/public/build" ]; then
    log "Publishing built assets to public_html/build"
    mkdir -p "$PUBLIC_HTML/build"
    cp -R "$APP_DIR/public/build/." "$PUBLIC_HTML/build/"
fi

if [ "$SKIP_MIGRATE" != "1" ]; then
    log "Running database migrations"
    "$PHP_BIN" artisan migrate --force
fi

log "Rebuilding caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

log "Draining queued jobs (database driver)"
"$PHP_BIN" artisan queue:work database --stop-when-empty --tries=3 || true

log "Bringing the application back online"
"$PHP_BIN" artisan up

log "Done. Verify /up and review storage/logs/laravel.log for errors"
