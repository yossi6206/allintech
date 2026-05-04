FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY HTML/ /var/www/html/

RUN cp /var/www/html/index3.html /var/www/html/index.html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
