<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Intentamos crear el cliente de Google
    $client = new Google_Client();
    echo "<h2 style='color:green;'>✅ Google_Client cargado correctamente</h2>";
} catch (Error $e) {
    echo "<h2 style='color:red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
