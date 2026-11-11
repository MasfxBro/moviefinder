#!/usr/bin/env bash
# deploy.sh — MovieFinder release procedure
# See docs/OPERATIONS.md section 5 for the full narrative.
#
# Usage (run from /var/www/moviefinder on the server):
#   bash scripts/deploy.sh
#
# Prerequisites:
#   - shared/.env and shared/storage already exist (created once on first deploy)
#   - The pre-built frontend assets (public/build/) are available locally
#     (CI builds them and ships them alongside this script, or you scp them here)
#   - The deploy user has sudo rights for `systemctl reload php8.5-fpm` only
#
# This script follows the atomic symlink-swap pattern described in OPERATIONS.md:
#   /var/www/moviefinder/releases/<id>/  — one folder per release
#   /var/www/moviefinder/shared/         — .env, storage (shared across releases)
#   /var/www/moviefinder/current         — symlink; web server document root = current/public
#
# Never roll back migrations; fix forward. See OPERATIONS.md section 5 for rollback steps.

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------

APP_DIR="/var/www/moviefinder"
RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_LINK="${APP_DIR}/current"
KEEP_RELEASES=5

RELEASE_ID=$(date +"%Y%m%d-%H%M")
RELEASE_DIR="${RELEASES_DIR}/${RELEASE_ID}"

# ---------------------------------------------------------------------------
# Step 1: Create the release directory and copy application code
# ---------------------------------------------------------------------------

echo "[deploy] Creating release ${RELEASE_ID} ..."
mkdir -p "${RELEASE_DIR}"

# Copy everything except releases/, shared/ and the current symlink itself
rsync -a --exclude=releases --exclude=shared --exclude=current \
    "${APP_DIR}/" "${RELEASE_DIR}/"

# ---------------------------------------------------------------------------
# Step 2: Install PHP dependencies (production, optimized)
# ---------------------------------------------------------------------------

echo "[deploy] Installing PHP dependencies ..."
cd "${RELEASE_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

# ---------------------------------------------------------------------------
# Step 3: Link shared resources and write BUILD_ID
# ---------------------------------------------------------------------------

echo "[deploy] Linking shared resources ..."
ln -nfs "${SHARED_DIR}/.env"     "${RELEASE_DIR}/.env"
ln -nfs "${SHARED_DIR}/storage"  "${RELEASE_DIR}/storage"

echo "[deploy] Writing BUILD_ID: ${RELEASE_ID}"
echo "${RELEASE_ID}" > "${RELEASE_DIR}/BUILD_ID"

# ---------------------------------------------------------------------------
# Step 4: Run database migrations (backward-compatible only)
# ---------------------------------------------------------------------------

echo "[deploy] Running migrations ..."
php artisan migrate --force

# ---------------------------------------------------------------------------
# Step 5: Warm caches for the new release
# ---------------------------------------------------------------------------

echo "[deploy] Warming caches ..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ---------------------------------------------------------------------------
# Step 6: Health check the new release BEFORE switching
# ---------------------------------------------------------------------------

echo "[deploy] Running health check ..."
# Point PHP-FPM temporarily by checking the new release directly.
# A lightweight check: artisan can bootstrap the app against the shared DB.
php artisan about --only=Environment > /dev/null

# ---------------------------------------------------------------------------
# Step 7: Switch the symlink (atomic)
# ---------------------------------------------------------------------------

echo "[deploy] Switching current -> releases/${RELEASE_ID} ..."
ln -nfs "${RELEASE_DIR}" "${CURRENT_LINK}"

# Graceful reload (in-flight requests finish; never restart which drops them)
sudo systemctl reload php8.5-fpm

# ---------------------------------------------------------------------------
# Step 8: Restart queue workers (they finish current job then restart)
# ---------------------------------------------------------------------------

echo "[deploy] Restarting queue workers ..."
php artisan queue:restart

# ---------------------------------------------------------------------------
# Step 9: Prune old releases (keep the last KEEP_RELEASES)
# ---------------------------------------------------------------------------

echo "[deploy] Pruning old releases (keep ${KEEP_RELEASES}) ..."
ls -1dt "${RELEASES_DIR}"/*/ | tail -n +$((KEEP_RELEASES + 1)) | xargs rm -rf --

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------

echo "[deploy] Release ${RELEASE_ID} is live. Watch logs for 15 minutes."
echo "[deploy] Smoke test checklist: docs/OPERATIONS.md section 12"
echo ""
echo "Rollback if needed:"
echo "  PREV=\$(ls -1dt ${RELEASES_DIR}/*/ | sed -n '2p' | xargs basename)"
echo "  ln -nfs ${RELEASES_DIR}/\${PREV} ${CURRENT_LINK}"
echo "  sudo systemctl reload php8.5-fpm"
echo "  php artisan queue:restart"
