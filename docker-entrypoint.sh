#!/bin/sh
set -e

# Ensure working directory
cd /opt/www || exit 1

# If vendor autoload not present, install composer dependencies
if [ ! -f /opt/www/vendor/autoload.php ]; then
  echo "Vendor not found. Installing composer dependencies..."
  composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
fi

# Execute the container command (CMD)
exec "$@"
