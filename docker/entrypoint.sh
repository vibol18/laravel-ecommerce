#!/bin/bash
set -e

PORT="${PORT:-8080}"

# ── Tell Apache which port to listen on ──────────────────────────────────────
cat > /etc/apache2/ports.conf <<EOF
Listen ${PORT}
EOF

# ── VirtualHost pointing at Laravel's public/ dir ─────────────────────────────
cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# ── Ensure writable directories ──────────────────────────────────────────────
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ── Database migrations ──────────────────────────────────────────────────────
php artisan migrate --force

# ── Storage symlink (for public file uploads) ────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── Production caches ────────────────────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Start Apache in the foreground ───────────────────────────────────────────
exec apache2-foreground
