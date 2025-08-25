FROM php:8.2-apache

# Enable extensions
RUN docker-php-ext-install pdo pdo_mysql

# Apache config: enable mod_rewrite
RUN a2enmod rewrite

# Copy app
COPY src/ /var/www/html/

# Set DocumentRoot to public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Set recommended PHP.ini settings
RUN {         echo "display_errors=On";         echo "error_reporting=E_ALL";     } > /usr/local/etc/php/conf.d/php-dev.ini

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80