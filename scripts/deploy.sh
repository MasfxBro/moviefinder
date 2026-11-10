#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Print each command to the terminal before executing it.
set -x

# Get the current date and time
CURRENT_DATE=$(date +%Y%m%d-%H%M)

# Write the release id into the release's BUILD_ID file
BUILD_ID_FILE="releases/$CURRENT_DATE/BUILD_ID"

# Create the release directory
mkdir -p "releases/$CURRENT_DATE"

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy the pre-built frontend assets
cp -r public/build "releases/$CURRENT_DATE/public/build"

# Link shared files
ln -nfs ../../shared/.env "releases/$CURRENT_DATE/.env"
ln -nfs ../../shared/storage "releases/$CURRENT_DATE/storage"

# Write the release id into the release's BUILD_ID file
echo "$CURRENT_DATE" > "$BUILD_ID_FILE"

# Run migrations
php artisan migrate --force

# Warm caches for the new release
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Health check the new release
if ! curl -sSf http://localhost/up > /dev/null; then
  echo "Health check failed"
  exit 1
fi

# Switch current to the new release
ln -nfs "releases/$CURRENT_DATE" "current"

# Graceful reload of PHP-FPM
sudo systemctl reload php8.5-fpm

# Restart the queue workers
php artisan queue:restart

# Post-deploy smoke test
php artisan test --filter=SmokeTest

# Update CHANGELOG.md and tag the release
echo "## [$CURRENT_DATE]" >> CHANGELOG.md
echo "- Release $CURRENT_DATE" >> CHANGELOG.md
git add CHANGELOG.md
git commit -m "chore(release): $CURRENT_DATE"
git tag "$CURRENT_DATE"

# Rollback procedure
rollback() {
  PREVIOUS_RELEASE=$(ls -t releases | grep -v "$CURRENT_DATE" | head -n 1)
  ln -nfs "releases/$PREVIOUS_RELEASE" "current"
  sudo systemctl reload php8.5-fpm
  php artisan queue:restart
  echo "Rolled back to release $PREVIOUS_RELEASE"
}

# Trap errors and rollback if necessary
trap 'rollback' ERR

# Run the deploy script
deploy

# End of script