FROM php:8.2-fpm

# System deps + PHP extensions Laravel/Sanctum/Spatie/Paystack/Firebase need
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (better layer caching)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the app
COPY . .

RUN composer dump-autoload --optimize \
    && php artisan config:clear

# Nginx + supervisord config
COPY nginx.conf /etc/nginx/sites-available/default
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Storage/cache dirs writable by www-data
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# Run migrations on boot, then start nginx + php-fpm under supervisord.
# On Render this container gets its own ephemeral filesystem each deploy,
# so migrate:persist happens here rather than as a separate release step.
CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan migrate --force \
    && supervisord -c /etc/supervisor/conf.d/supervisord.conf
