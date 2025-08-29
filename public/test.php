<?php
echo "Hello from Railway! PHP is working.";
echo "<br>Port: " . ($_ENV['PORT'] ?? 'not set');
echo "<br>Environment: " . ($_ENV['APP_ENV'] ?? 'not set');
echo "<br>Time: " . date('Y-m-d H:i:s');
?>
