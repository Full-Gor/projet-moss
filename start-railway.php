<?php

// Script de démarrage pour Railway
$port = $_ENV['PORT'] ?? 8000;
$host = '0.0.0.0';

echo "Starting Symfony application on $host:$port\n";
echo "Document root: " . __DIR__ . "/public\n";

// Démarrer le serveur PHP intégré
$command = "php -S $host:$port -t public";
echo "Command: $command\n";

system($command);
