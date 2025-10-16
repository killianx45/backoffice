# Utilisation d'une image PHP 8.3 avec Apache
FROM php:8.3-apache

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libicu-dev \
    libzip-dev \
    curl \
    netcat-openbsd \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        intl \
        opcache \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configuration d'Apache pour Symfony
RUN a2enmod rewrite
COPY <<EOF /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>
    <Directory /var/www/html>
        Options FollowSymlinks
    </Directory>
    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
EOF

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copie du code source d'abord
COPY . .

# Installation des dépendances PHP (avec les dépendances de dev pour le développement)
RUN composer install --optimize-autoloader --no-interaction --no-scripts

# Copie et permissions pour le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposition du port 80
EXPOSE 80

# Point d'entrée personnalisé
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]