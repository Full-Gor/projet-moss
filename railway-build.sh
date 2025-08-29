#!/bin/bash

echo "🚂 Démarrage du build Railway pour MossAir..."

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

# Vérifier la configuration
echo "🔧 Vérification de la configuration..."
php bin/console debug:config --env=prod

echo "✅ Build Railway terminé avec succès !"
