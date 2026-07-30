#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Run composer install if vendor/autoload.php doesn't exist
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
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
\$pass = getenv('DB_PASSWORD') ?: '1234';
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

# Run npm install and build if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies and building assets..."
    npm install
    npm run build
fi

# Ensure storage and bootstrap cache permissions are correct
if [ ! -w "storage" ] || [ ! -w "bootstrap/cache" ]; then
    echo "Setting folder permissions (this may take a few seconds)..."
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
fi

# Execute the main container command
exec "$@"
