#!/bin/sh
set -e

# Ensure working directory
cd /opt/www || exit 1

echo "=== Docker Entrypoint ==="
echo "Checking composer.lock..."

# Generate composer.lock if it doesn't exist
if [ ! -f /opt/www/composer.lock ]; then
  echo "composer.lock not found. Generating..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

echo "Checking vendor directory..."

# Se vendor não existir no volume montado ou estiver incompleto, instalar
if [ ! -f /opt/www/vendor/autoload.php ] || [ ! -d /opt/www/vendor/hyperf/devtool ]; then
  echo "Vendor not found or incomplete in mounted volume. Installing composer dependencies..."
  rm -rf /opt/www/vendor/*
  composer install --no-interaction --prefer-dist --optimize-autoloader

  echo "Installation complete. Verifying devtool..."
  if [ -d /opt/www/vendor/hyperf/devtool ]; then
    echo "✓ hyperf/devtool found"
  else
    echo "✗ hyperf/devtool NOT found"
  fi
fi

# Verify installation succeeded
if [ ! -f /opt/www/vendor/autoload.php ]; then
  echo "ERROR: Composer installation failed!"
  exit 1
fi

echo "=== Starting application ==="

# Execute the container command (CMD)
exec "$@"
