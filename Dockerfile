# Gunakan PHP 8.2 dengan Apache
FROM php:8.2-apache

# Install ekstensi MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Aktifkan mod_rewrite jika pakai .htaccess
RUN a2enmod rewrite

# Copy semua file ke folder web Apache
COPY . /var/www/html

# Ubah permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Port default Apache adalah 80
EXPOSE 80
