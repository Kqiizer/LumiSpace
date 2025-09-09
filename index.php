<?php
$host = 'db';
$db   = getenv('MYSQL_DATABASE') ?: 'posdb';
$user = getenv('MYSQL_USER') ?: 'posuser';
$pass = getenv('MYSQL_PASSWORD') ?: 'pospass';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
  $pdo->query("SELECT 1");
  echo "<h1>PHP + Apache OK</h1><p>Conexión a MySQL: ✔</p>";
  echo "<p>Sirve aquí tus HTML/CSS/JS en la carpeta <code>app/</code>.</p>";
} catch (Throwable $e) {
  echo "<h1>PHP + Apache</h1><p>Error DB: " . htmlspecialchars($e->getMessage()) . "</p>";
}
