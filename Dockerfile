# Banco Quetzal - imagen para Render (PHP 8.2 + Apache + GoAccess)
FROM php:8.2-apache

# Extensión mysqli y GoAccess (análisis del log de Apache)
RUN apt-get update \
 && apt-get install -y --no-install-recommends goaccess \
 && docker-php-ext-install mysqli \
 && a2enmod rewrite headers remoteip \
 && rm -rf /var/lib/apt/lists/*

COPY docker/apache-banco.conf /etc/apache2/conf-available/banco.conf
COPY docker/php-banco.ini     /usr/local/etc/php/conf.d/banco.ini
COPY docker/entrypoint.sh     /usr/local/bin/entrypoint.sh
RUN a2enconf banco && chmod +x /usr/local/bin/entrypoint.sh

COPY . /var/www/html/
RUN mkdir -p /var/www/html/storage \
 && chown -R www-data:www-data /var/www/html/storage \
 && touch /var/log/apache2/banco_access.log \
 && chown www-data:www-data /var/log/apache2/banco_access.log \
 && chmod 644 /var/log/apache2/banco_access.log

ENV APACHE_ACCESS_LOG=/var/log/apache2/banco_access.log \
    PORT=10000

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
