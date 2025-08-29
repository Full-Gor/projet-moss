#!/bin/bash

echo "🚂 Démarrage de MossAir sur Railway..."

# Vérifier les variables d'environnement
echo "🌍 Variables d'environnement:"
echo "PORT: $PORT"
echo "APP_ENV: $APP_ENV"
echo "DATABASE_URL: ${DATABASE_URL:0:50}..."

# Vérifier que le PORT est défini
if [ -z "$PORT" ]; then
    echo "❌ ERREUR: La variable PORT n'est pas définie"
    echo "Railway doit fournir cette variable automatiquement"
    exit 1
fi

# Installation des dépendances (au cas où)
echo "📦 Installation des dépendances..."
composer install --no-dev --optimize-autoloader

# Vider le cache Symfony
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# Exécuter les migrations
echo "🗃️ Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || echo "⚠️ Pas de migrations à exécuter"

# Vérifier que le dossier public existe
echo "📁 Vérification du dossier public..."
ls -la public/

# Vérifier que index.php existe
echo "📄 Vérification du fichier index.php..."
ls -la public/index.php

# Démarrer le serveur PHP sur toutes les interfaces et le port Railway
echo "🚀 Démarrage du serveur PHP sur 0.0.0.0:$PORT..."
exec php -S 0.0.0.0:$PORT -t public