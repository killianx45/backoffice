#!/bin/bash

# Script de démarrage pour le container Symfony
set -e

echo "🚀 Démarrage du container Symfony..."

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données..."
until nc -z db 3306; do
  echo "Base de données non accessible, attente..."
  sleep 3
done
echo "✅ Base de données accessible"

# Permissions basiques uniquement sur les dossiers nécessaires
echo "🔐 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/var /var/www/html/public || true
chmod -R 755 /var/www/html/var /var/www/html/public || true

echo "✅ Symfony prêt !"

# Démarrage d'Apache
apache2-foreground