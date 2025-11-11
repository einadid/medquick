#!/bin/bash

# Deployment script for QuickMed

echo "🚀 Starting deployment..."

# Set maintenance mode
echo "⏳ Enabling maintenance mode..."
touch maintenance.on

# Pull latest changes
echo "🔄 Pulling latest changes..."
git pull origin main

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Optimize application
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "💾 Running database migrations..."
php artisan migrate --force

# Clear cache
echo "🧹 Clearing cache..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

# Disable maintenance mode
echo "✅ Disabling maintenance mode..."
rm -f maintenance.on

echo "🎉 Deployment completed successfully!"