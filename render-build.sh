#!/bin/bash

# Script de build pour Render.com
echo "🚀 Démarrage du build pour MossAir..."

# Installer les dépendances
echo "📦 Installation des dépendances..."
composer install --no-dev --optimize-autoloader --no-interaction

# Vider le cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-debug

# Exécuter les migrations
echo "🗄️ Exécution des migrations..."
php bin/console doctrine:migrations:migrate --env=prod --no-interaction

# Installer les assets
echo "🎨 Installation des assets..."
php bin/console assets:install public --env=prod

echo "✅ Build terminé avec succès !"
