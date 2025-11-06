<?php

require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/house.php';

require_login();

header('Content-Type: application/json');

$roomId = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
if ($roomId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid room_id parameter.']);
    exit;
}

$currentUser = get_authenticated_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$room = get_room_with_house_by_id($roomId);
if (!$room) {
    http_response_code(404);
    echo json_encode(['error' => 'Room not found.']);
    exit;
}

if ((int) $room['user_id'] !== (int) $currentUser['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

try {
    $walls = getWallsByRoomId($roomId);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load walls data.']);
    exit;
}

$formatSide = static function (array $wall, string $prefix): ?array {
    $sprite = $wall[$prefix . '_sprite'] ?? null;
    $isOutside = $wall[$prefix . '_is_outside'] ?? null;
    $material = $wall[$prefix . '_material'] ?? null;
    $tint = $wall[$prefix . '_tint'] ?? null;

    if ($sprite === null && $material === null && $tint === null && $isOutside === null) {
        return null;
    }

    $side = [
        'sprite' => $sprite,
        'is_outside' => $isOutside,
    ];

    if ($material !== null) {
        $side['material'] = $material;
    }

    if ($tint !== null) {
        $side['tint'] = $tint;
    }

    return $side;
};

$payloadWalls = array_map(static function (array $wall) use ($formatSide): array {
    return [
        'id' => $wall['id'],
        'room_id' => $wall['room_id'],
        'start_x' => $wall['start_x'],
        'start_y' => $wall['start_y'],
        'end_x' => $wall['end_x'],
        'end_y' => $wall['end_y'],
        'status' => $wall['status'],
        'sides' => [
            'A' => $formatSide($wall, 'side_a'),
            'B' => $formatSide($wall, 'side_b'),
        ],
    ];
}, $walls);

echo json_encode(['walls' => $payloadWalls], JSON_UNESCAPED_SLASHES);
