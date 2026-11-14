FROM php:8.2-apache

# System packages + PHP extensions Laravel and DomPDF need
RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip gd exif bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Apache serves Laravel's public/ folder
RUN printf '<VirtualHost *:80>\n    DocumentRoot /var/www/html/public\n    <Directory /var/www/html/public>\n        AllowOverride All\n        Require all granted\n    </Directory>\n    ErrorLog ${APACHE_LOG_DIR}/error.log\n    CustomLog ${APACHE_LOG_DIR}/access.log combined\n</VirtualHost>\n' \
    > /etc/apache2/sites-available/000-default.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP packages first (cached until composer files change)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copy the project
COPY . .

RUN mkdir -p storage/app/private storage/app/public storage/logs \
             storage/framework/cache/data storage/framework/sessions storage/framework/views \
             bootstrap/cache \
    && composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

# Wait for MySQL, run migrations, then start Apache
CMD ["sh", "-c", "until php artisan migrate --force; do echo 'Waiting for database...'; sleep 3; done; exec apache2-foreground"]