<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/functions.php';

$term = trim((string)($_GET['q'] ?? ''));

if ($term === '') {
    echo json_encode(['ok' => true, 'suggestions' => []]);
    exit();
}

try {
    $suggestions = getSearchSuggestions($term, (int)($_GET['limit'] ?? 8));
    echo json_encode(['ok' => true, 'suggestions' => $suggestions], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'No se pudieron obtener sugerencias',
        'detail'=> $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

