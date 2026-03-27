FROM php:8.2-apache

# ── Extensions PHP nécessaires ────────────────────────────────────────────
RUN docker-php-ext-install pdo pdo_mysql mysqli

# ── Activer mod_rewrite Apache ────────────────────────────────────────────
RUN a2enmod rewrite

# ── Configuration Apache : pointer vers /public ───────────────────────────
RUN echo '<VirtualHost *:80>\n\
    ServerName lopango\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# ── Copier les fichiers de l'app ──────────────────────────────────────────
COPY . /var/www/html/

# ── Permissions sur le dossier data (lecture/écriture JSON) ───────────────
RUN chown -R www-data:www-data /var/www/html/data \
    && chmod -R 755 /var/www/html/data

# ── Port exposé ───────────────────────────────────────────────────────────
EXPOSE 80
