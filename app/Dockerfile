FROM php:8.1-fpm-alpine
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www/html
COPY index.php /var/www/html/
RUN chown -R www-data:www-data /var/www/html
USER www-data
