<?php

require_once __DIR__ . '/db.php';

function create_house(int $userId, string $name): int
{
    $db = getDb();
    $stmt = $db->prepare('INSERT INTO houses (user_id, name, created_at) VALUES (:user_id, :name, NOW())');
    $stmt->execute([
        'user_id' => $userId,
        'name' => $name,
    ]);

    return (int) $db->lastInsertId();
}

function get_house_by_id(int $houseId, int $userId): ?array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM houses WHERE id = :id AND user_id = :user_id');
    $stmt->execute([
        'id' => $houseId,
        'user_id' => $userId,
    ]);
    $house = $stmt->fetch();

    return $house ?: null;
}

function get_houses_by_user_id(int $userId): array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM houses WHERE user_id = :user_id ORDER BY created_at DESC');
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
}

function get_floors_by_house_id(int $houseId): array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM floors WHERE house_id = :house_id ORDER BY level ASC');
    $stmt->execute(['house_id' => $houseId]);

    return $stmt->fetchAll();
}

function get_walls_by_floor_id(int $floorId): array
{
    $db = getDb();
    $sql = 'SELECT w.*, r.name AS room_name
            FROM walls w
            INNER JOIN rooms r ON w.room_id = r.id
            WHERE r.floor_id = :floor_id';
    $stmt = $db->prepare($sql);
    $stmt->execute(['floor_id' => $floorId]);
    $walls = $stmt->fetchAll();

    return array_map(static function (array $wall) {
        $wall['orientation'] = ($wall['start_y'] === $wall['end_y']) ? 'horizontal' : 'vertical';
        $wall['length'] = ($wall['orientation'] === 'horizontal')
            ? abs($wall['end_x'] - $wall['start_x'])
            : abs($wall['end_y'] - $wall['start_y']);
        $wall['left'] = min($wall['start_x'], $wall['end_x']);
        $wall['top'] = min($wall['start_y'], $wall['end_y']);

        return $wall;
    }, $walls);
}

function generateInitialHouse(int $houseId): void
{
    $db = getDb();

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO floors (house_id, level, created_at) VALUES (:house_id, :level, NOW())');
        $stmt->execute([
            'house_id' => $houseId,
            'level' => 0,
        ]);
        $floorId = (int) $db->lastInsertId();

        $stmt = $db->prepare('INSERT INTO rooms (floor_id, name, style_id, created_at) VALUES (:floor_id, :name, NULL, NOW())');
        $stmt->execute([
            'floor_id' => $floorId,
            'name' => 'Foyer',
        ]);
        $roomId = (int) $db->lastInsertId();

        $walls = [
            ['start_x' => 0, 'start_y' => 0, 'end_x' => 400, 'end_y' => 0],
            ['start_x' => 400, 'start_y' => 0, 'end_x' => 400, 'end_y' => 300],
            ['start_x' => 400, 'start_y' => 300, 'end_x' => 0, 'end_y' => 300],
            ['start_x' => 0, 'start_y' => 300, 'end_x' => 0, 'end_y' => 0],
        ];

        $wallIds = [];
        $insertWallStmt = $db->prepare('INSERT INTO walls (room_id, start_x, start_y, end_x, end_y, side_a_type, side_b_type, status) VALUES (:room_id, :start_x, :start_y, :end_x, :end_y, :side_a_type, :side_b_type, :status)');
        foreach ($walls as $index => $wallData) {
            $insertWallStmt->execute([
                'room_id' => $roomId,
                'start_x' => $wallData['start_x'],
                'start_y' => $wallData['start_y'],
                'end_x' => $wallData['end_x'],
                'end_y' => $wallData['end_y'],
                'side_a_type' => 'default',
                'side_b_type' => 'default',
                'status' => 'normal',
            ]);
            $wallIds[$index] = (int) $db->lastInsertId();
        }

        $doorWallIndex = 0;
        $doorStmt = $db->prepare('INSERT INTO doors (wall_id, pos, door_type) VALUES (:wall_id, :pos, :door_type)');
        $doorStmt->execute([
            'wall_id' => $wallIds[$doorWallIndex],
            'pos' => 200,
            'door_type' => 'normal',
        ]);

        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}
