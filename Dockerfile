FROM php:8.3-apache
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY . /var/www/html/
EXPOSE 80
