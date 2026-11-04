FROM php:8.2-apache

# Installation des dépendances, SQLite, Python 3 et Pip
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    python3 \
    python3-pip \
    && docker-php-ext-install pdo_sqlite pdo

# Installation de la bibliothèque Python pour MQTT (Crucial pour ton script)
RUN pip3 install --no-cache-dir --break-system-packages paho-mqtt

# Activation du module de réécriture Apache
RUN a2enmod rewrite

# Copie des fichiers dans le dossier web d'Apache
COPY . /var/www/html/

# Correction des permissions des fichiers et dossiers
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80