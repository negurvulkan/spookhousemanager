<?php

require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/house.php';

require_login();

header('Content-Type: application/json');

$floorId = isset($_GET['floor_id']) ? (int) $_GET['floor_id'] : 0;
if ($floorId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid floor_id parameter.']);
    exit;
}

$currentUser = get_authenticated_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$floor = get_floor_with_house_by_id($floorId);
if (!$floor) {
    http_response_code(404);
    echo json_encode(['error' => 'Floor not found.']);
    exit;
}

if ((int) $floor['user_id'] !== (int) $currentUser['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

try {
    $walls = get_walls_with_sides_by_floor_id($floorId);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load walls data.']);
    exit;
}

echo json_encode(['walls' => $walls], JSON_UNESCAPED_SLASHES);
