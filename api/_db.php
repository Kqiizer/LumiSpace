<?php
function db(): PDO {
  $host = getenv('DB_HOST') ?: '127.0.0.1';
  $db   = getenv('DB_NAME') ?: 'lumispace';
  $user = getenv('DB_USER') ?: 'root';
  $pass = getenv('DB_PASS') ?: '';
  $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
  return $pdo;
}
function json_input(){
  $raw = file_get_contents('php://input');
  return json_decode($raw, true) ?: [];
}
function json_out($arr, $code=200){
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
