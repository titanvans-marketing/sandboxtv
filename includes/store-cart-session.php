<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid JSON payload.',
    ]);
    exit;
}

$cart = isset($payload['cart']) && is_array($payload['cart'])
    ? array_values($payload['cart'])
    : [];

$selectedModel = isset($payload['selectedModel'])
    ? (string) $payload['selectedModel']
    : '';

$syncedAt = isset($payload['syncedAt'])
    ? (string) $payload['syncedAt']
    : gmdate('c');

$_SESSION['titan_service_cart'] = $cart;
$_SESSION['titan_selected_model'] = $selectedModel;
$_SESSION['titan_service_cart_synced_at'] = $syncedAt;

echo json_encode([
    'ok' => true,
    'count' => count($cart),
]);