#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Returns success when the lock file changed since the last install recorded in the stamp file
lock_changed() {
    local lock_file="$1" stamp_file="$2"
    [ ! -f "$stamp_file" ] || [ "$(sha256sum "$lock_file" | cut -d' ' -f1)" != "$(cat "$stamp_file")" ]
}

# Install composer dependencies when vendor is missing or composer.lock changed (e.g. after git pull)
if [ ! -f "vendor/autoload.php" ] || lock_changed composer.lock vendor/.composer-lock.sha256; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    sha256sum composer.lock | cut -d' ' -f1 > vendor/.composer-lock.sha256
fi

# Create .env if it doesn't exist
if [ ! -f ".env" ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate
fi

# Wait for DB to be ready
echo "Waiting for database connection..."
until php -r "
\$host = getenv('DB_HOST') ?: 'db';
\$port = getenv('DB_PORT') ?: '5432';
\$db   = getenv('DB_DATABASE') ?: 'transacciones_facturacion';
\$user = getenv('DB_USERNAME') ?: 'postgres';
\$pass = getenv('DB_PASSWORD');
try {
    new PDO(\"pgsql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" > /dev/null 2>&1; do
    echo "Database is not ready yet. Retrying in 2 seconds..."
    sleep 2
done
echo "Database connection established successfully!"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Install npm dependencies when node_modules is missing or package-lock.json changed
if [ ! -d "node_modules" ] || lock_changed package-lock.json node_modules/.package-lock.sha256; then
    echo "Installing npm dependencies..."
    npm install
    sha256sum package-lock.json | cut -d' ' -f1 > node_modules/.package-lock.sha256
fi

# Always rebuild assets: public/build is not versioned, and Tailwind only includes the
# classes used in the current views, so an old build misses classes added since then
echo "Building assets with Vite..."
npm run build

# storage and bootstrap/cache belong to the php-fpm user (www-data): owner and group can
# write, everyone else can only read. Run artisan as www-data (docker exec -u www-data ...)
# so root does not leave files there that php-fpm cannot modify
echo "Setting folder permissions for storage and bootstrap/cache..."
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX,o-w storage bootstrap/cache || true
# Pest keeps its result cache inside vendor (only installed with dev dependencies)
chown -R www-data:www-data vendor/pestphp/pest/.temp 2>/dev/null || true

# Execute the main container command
exec "$@"