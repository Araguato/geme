FROM php:8.2-apache

# Systempakete & PHP-Erweiterungen für Laravel
RUN apt-get update && apt-get install -y git unzip default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# mod_rewrite aktivieren
RUN a2enmod rewrite

WORKDIR /var/www/html

# Zuerst die Composer-Datei kopieren (für besseren Cache)
COPY composer.json ./

# Composer aus offiziellem Image holen
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Abhängigkeiten installieren (ohne dev, optimiert)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Jetzt den Rest der Anwendung kopieren. Der Pfad ist bewusst mit explizitem ./ geschrieben,
# damit Docker diesen Layer neu baut, falls sich der Build-Kontext ändert.
COPY ./ /var/www/html

# Dateirechte anpassen
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \;

# DocumentRoot auf public/ umstellen
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#<Directory /var/www/html/>#<Directory /var/www/html/public/>#' /etc/apache2/apache2.conf

EXPOSE 80

# Al iniciar el contenedor: ejecutar migraciones y asegurar el storage symlink,
# luego arrancar Apache en primer plano.
CMD ["bash", "-lc", "php artisan migrate --force || true && php artisan storage:link || true && apache2-foreground"]