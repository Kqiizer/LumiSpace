<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/functions.php';

try {
    $options = [
        'q'           => $_GET['q'] ?? '',
        'category'    => $_GET['category'] ?? '',
        'brand'       => $_GET['brand'] ?? '',
        'color'       => $_GET['color'] ?? '',
        'size'        => $_GET['size'] ?? '',
        'availability'=> $_GET['availability'] ?? '',
        'min_price'   => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
        'max_price'   => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
        'sort'        => $_GET['sort'] ?? 'relevance',
        'page'        => isset($_GET['page']) ? (int)$_GET['page'] : 1,
        'per_page'    => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12,
    ];

    $results = searchProductos($options);

    logSearchQuery(
        $_SESSION['usuario_id'] ?? null,
        $options['q'] ?? '',
        [
            'category'     => $options['category'],
            'brand'        => $options['brand'],
            'color'        => $options['color'],
            'size'         => $options['size'],
            'availability' => $options['availability'],
            'price'        => ['min' => $options['min_price'], 'max' => $options['max_price']],
        ],
        $results['total'] ?? 0
    );

    echo json_encode([
        'ok'      => true,
        'results' => $results['results'],
        'meta'    => [
            'total'       => $results['total'],
            'page'        => $results['page'],
            'per_page'    => $results['per_page'],
            'total_pages' => $results['total_pages'],
        ],
        'facets'  => $results['facets'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error al realizar la búsqueda',
        'detail'=> $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

