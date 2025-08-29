<?php
echo "<h1>Diagnostic PHP - Serveur Web</h1>";

echo "<h2>Configuration PHP</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>PHP INI:</strong> " . php_ini_loaded_file() . "</p>";

echo "<h2>Extensions PostgreSQL</h2>";
$extensions = get_loaded_extensions();
$pgsql_found = false;
foreach ($extensions as $ext) {
    if (strpos($ext, 'pgsql') !== false) {
        echo "<p style='color: green;'>✅ $ext (ACTIVE)</p>";
        $pgsql_found = true;
    }
}
if (!$pgsql_found) {
    echo "<p style='color: red;'>❌ Aucune extension PostgreSQL trouvée</p>";
}

echo "<h2>Test de connexion PDO</h2>";
try {
    $dsn = "pgsql:host=db.bdeewxslzhwmjiglpavv.supabase.co;port=5432;dbname=postgres;user=postgres;password=Laataghdab!";
    $pdo = new PDO($dsn);
    echo "<p style='color: green;'>✅ Connexion PDO réussie !</p>";

    $stmt = $pdo->query("SELECT current_database(), current_user, version()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Base de données:</strong> " . $result['current_database'] . "</p>";
    echo "<p><strong>Utilisateur:</strong> " . $result['current_user'] . "</p>";
    echo "<p><strong>Version:</strong> " . $result['version'] . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur PDO : " . $e->getMessage() . "</p>";
}

echo "<h2>Variables d'environnement</h2>";
if (isset($_ENV['DATABASE_URL'])) {
    echo "<p><strong>DATABASE_URL:</strong> " . $_ENV['DATABASE_URL'] . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ DATABASE_URL non définie</p>";
}

echo "<h2>Test Doctrine</h2>";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/bootstrap.php';

    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();

    $container = $kernel->getContainer();
    $em = $container->get('doctrine')->getManager();

    $connection = $em->getConnection();
    $stmt = $connection->executeQuery("SELECT 1 as test");
    $result = $stmt->fetchAssociative();

    echo "<p style='color: green;'>✅ Doctrine fonctionne ! Test: " . $result['test'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur Doctrine : " . $e->getMessage() . "</p>";
}
