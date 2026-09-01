FROM php:8.2-apache

# ── System dependencies + PHP extensions ──────────────────────────────────────
RUN apt-get update && apt-get install -y \
        git curl libpng-dev libonig-dev libxml2-dev libzip-dev \
        libicu-dev libpq-dev zip unzip \
    && docker-php-ext-install \
        pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Node.js 20 LTS (for Vite) ────────────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── PHP dependencies (--no-scripts: artisan is not available yet) ─────────────
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev --no-scripts \
        --no-interaction --prefer-dist \
        --optimize-autoloader

# ── Node dependencies ────────────────────────────────────────────────────────
COPY package*.json ./
RUN npm install

# ── Application source ───────────────────────────────────────────────────────
COPY . .

# ── Finish Composer (run deferred scripts) + build Vite assets ───────────────
RUN php artisan package:discover --ansi \
    && npm run build

# ── Apache: enable rewrite for Laravel routes ────────────────────────────────
RUN a2enmod rewrite

# ── Permissions ──────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Entrypoint ───────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
