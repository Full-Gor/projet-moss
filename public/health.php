<?php
header('Content-Type: application/json');

$response = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [
        'php_version' => PHP_VERSION,
        'port' => $_ENV['PORT'] ?? 'not set',
        'app_env' => $_ENV['APP_ENV'] ?? 'not set',
        'database_url' => isset($_ENV['DATABASE_URL']) ? 'set' : 'not set'
    ],
    'symfony' => [
        'kernel_exists' => class_exists('App\Kernel'),
        'public_dir' => __DIR__,
        'index_exists' => file_exists(__DIR__ . '/index.php')
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
