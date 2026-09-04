FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y --no-install-recommends \
    sqlite3 \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    tar \
    unzip \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_sqlite \
        gd \
        mbstring \
        zip \
        xml \
        intl \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activation des modules Apache requis
RUN a2enmod rewrite headers alias deflate

# Configuration de PHP pour la production
RUN { \
        echo 'upload_max_filesize = 16M'; \
        echo 'post_max_size = 20M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
        echo 'date.timezone = Africa/Casablanca'; \
    } > /usr/local/etc/php/conf.d/syndic-custom.ini

# Configuration du VirtualHost Apache
RUN { \
        echo '<VirtualHost *:80>'; \
        echo '    DocumentRoot /var/www/html'; \
        echo '    Alias /Syndic /var/www/html/Syndic'; \
        echo '    <Directory /var/www/html/Syndic>'; \
        echo '        Options -Indexes +FollowSymLinks'; \
        echo '        AllowOverride All'; \
        echo '        Require all granted'; \
        echo '    </Directory>'; \
        echo '    <Directory /var/www/html>'; \
        echo '        Options -Indexes +FollowSymLinks'; \
        echo '        AllowOverride All'; \
        echo '        Require all granted'; \
        echo '    </Directory>'; \
        echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/000-default.conf

# Répertoire de travail
WORKDIR /var/www/html/Syndic

# Copie du code source
COPY . /var/www/html/Syndic/

# Création des dossiers et permissions
RUN mkdir -p /var/www/html/Syndic/data/tenants \
    && mkdir -p /var/www/html/Syndic/uploads/logos/presets \
    && mkdir -p /var/www/html/Syndic/uploads/reclamations \
    && chown -R www-data:www-data /var/www/html/Syndic \
    && chmod -R 775 /var/www/html/Syndic/data \
    && chmod -R 775 /var/www/html/Syndic/uploads

EXPOSE 80

CMD ["apache2-foreground"]
