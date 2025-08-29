#!/bin/bash

echo "🚂 Démarrage de MossAir sur Railway..."

# Vérifier les variables d'environnement
echo "🌍 Variables d'environnement:"
echo "PORT: $PORT"
echo "APP_ENV: $APP_ENV"
echo "DATABASE_URL: ${DATABASE_URL:0:50}..."

# Vider le cache Symfony
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-debug

# Vérifier que le dossier public existe
echo "📁 Vérification du dossier public..."
ls -la public/

# Vérifier que index.php existe
echo "📄 Vérification du fichier index.php..."
ls -la public/index.php

# Démarrer le serveur PHP
echo "🚀 Démarrage du serveur PHP sur le port $PORT..."
php -S 0.0.0.0:$PORT -t public
