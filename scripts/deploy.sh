#!/bin/bash
set -euo pipefail

echo "🚀 Beza Platform Deployment — $(date)"

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "❌ .env file missing. Copy .env.production.example to .env and fill in secrets."
    exit 1
fi

echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🔑 Generating app key..."
php artisan key:generate --force

echo "🔄 Caching configuration..."
php artisan config:cache

echo "🔄 Caching routes..."
php artisan route:cache

echo "🔄 Caching views..."
php artisan view:cache

echo "🗄️ Running migrations..."
php artisan migrate --force

echo "🧹 Clearing old cache..."
php artisan cache:clear

echo "✅ Deployment complete — $(date)"
