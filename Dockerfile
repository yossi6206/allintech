FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/sites-available/000-default.conf

COPY HTML/ /var/www/html/

RUN cp /var/www/html/index3.html /var/www/html/index.html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
