<?php

require_once __DIR__ . '/db.php';

if (!defined('HOUSE_GRID_WALKABLE_UNIT')) {
    define('HOUSE_GRID_WALKABLE_UNIT', 100);
}

function grid_label_from_index(int $index): string
{
    $base = intdiv($index, 2);
    $alphabetIndex = $base % 26;
    $repeat = intdiv($base, 26);

    $char = chr(ord('A') + $alphabetIndex);
    $label = str_repeat($char, $repeat + 1);

    return $index % 2 === 0 ? $label : strtolower($label);
}

function grid_index_from_label(string $label): int
{
    if ($label === '') {
        throw new InvalidArgumentException('Grid label darf nicht leer sein.');
    }

    $isLower = $label === strtolower($label);
    $upperLabel = strtoupper($label);

    $length = strlen($upperLabel);
    $base = 0;
    for ($i = 0; $i < $length; $i++) {
        $base *= 26;
        $base += (ord($upperLabel[$i]) - ord('A') + 1);
    }
    $base -= 1; // nullbasiert

    $index = $base * 2;

    if ($isLower) {
        $index += 1;
    }

    return $index;
}

function format_grid_coordinate(int $rowIndex, int $colIndex): string
{
    return grid_label_from_index($rowIndex) . ':' . grid_label_from_index($colIndex);
}

function parse_grid_coordinate(string $coordinate): array
{
    $parts = explode(':', $coordinate);
    if (count($parts) !== 2) {
        throw new InvalidArgumentException('Ungültiges Koordinatenformat: ' . $coordinate);
    }

    return [
        'row' => grid_index_from_label($parts[0]),
        'col' => grid_index_from_label($parts[1]),
    ];
}

function convert_grid_to_pixels(int $index): int
{
    return (int) floor($index / 2) * HOUSE_GRID_WALKABLE_UNIT;
}

function is_point_on_segment(float $px, float $py, float $ax, float $ay, float $bx, float $by): bool
{
    $cross = ($py - $ay) * ($bx - $ax) - ($px - $ax) * ($by - $ay);
    if (abs($cross) > 1e-9) {
        return false;
    }

    $dot = ($px - $ax) * ($px - $bx) + ($py - $ay) * ($py - $by);

    return $dot <= 0;
}

function is_point_in_polygon(float $x, float $y, array $polygon): bool
{
    $numPoints = count($polygon);
    if ($numPoints < 3) {
        return false;
    }

    $inside = false;
    $j = $numPoints - 1;
    for ($i = 0; $i < $numPoints; $i++) {
        $xi = $polygon[$i]['x'];
        $yi = $polygon[$i]['y'];
        $xj = $polygon[$j]['x'];
        $yj = $polygon[$j]['y'];

        if (is_point_on_segment($x, $y, $xi, $yi, $xj, $yj)) {
            return true;
        }

        $denominator = $yj - $yi;
        if ((($yi > $y) !== ($yj > $y)) &&
            ($x < ($xj - $xi) * ($y - $yi) / ($denominator !== 0.0 ? $denominator : 1e-9) + $xi)) {
            $inside = !$inside;
        }

        $j = $i;
    }

    return $inside;
}

function build_rectangular_room_boundary(int $gridWidth, int $gridHeight): array
{
    $maxRow = $gridHeight * 2;
    $maxCol = $gridWidth * 2;

    return [
        format_grid_coordinate(0, 0),
        format_grid_coordinate(0, $maxCol),
        format_grid_coordinate($maxRow, $maxCol),
        format_grid_coordinate($maxRow, 0),
        format_grid_coordinate(0, 0),
    ];
}

function get_rooms_with_boundaries_by_floor_id(int $floorId): array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT id, floor_id, name, grid_width, grid_height, boundary_path FROM rooms WHERE floor_id = :floor_id');
    $stmt->execute(['floor_id' => $floorId]);

    $rooms = [];
    while ($row = $stmt->fetch()) {
        $boundary = [];
        if (!empty($row['boundary_path'])) {
            $decoded = json_decode($row['boundary_path'], true);
            if (is_array($decoded)) {
                $boundary = array_values(array_filter($decoded, static function ($item) {
                    return is_string($item) && strpos($item, ':') !== false;
                }));
            }
        }

        $row['boundary'] = $boundary;
        $rooms[] = $row;
    }

    return $rooms;
}

function build_floor_layout(int $floorId): ?array
{
    $rooms = get_rooms_with_boundaries_by_floor_id($floorId);
    if (empty($rooms)) {
        return null;
    }

    $maxWidth = 0;
    $maxHeight = 0;
    foreach ($rooms as $room) {
        $maxWidth = max($maxWidth, (int) $room['grid_width']);
        $maxHeight = max($maxHeight, (int) $room['grid_height']);
    }

    $maxWidth = max(1, $maxWidth);
    $maxHeight = max(1, $maxHeight);

    $totalColumns = $maxWidth * 2 + 1;
    $totalRows = $maxHeight * 2 + 1;
    $unit = 40; // visuelle Skalierung

    $columnLabels = [];
    for ($c = 0; $c < $totalColumns; $c++) {
        $columnLabels[] = grid_label_from_index($c);
    }

    $rowLabels = [];
    for ($r = 0; $r < $totalRows; $r++) {
        $rowLabels[] = grid_label_from_index($r);
    }

    $svgWidth = ($totalColumns - 1) * $unit;
    $svgHeight = ($totalRows - 1) * $unit;

    $gridLines = [
        'vertical' => [],
        'horizontal' => [],
    ];
    for ($c = 0; $c < $totalColumns; $c++) {
        $gridLines['vertical'][] = [
            'position' => $c * $unit,
            'structural' => $c % 2 === 0,
        ];
    }
    for ($r = 0; $r < $totalRows; $r++) {
        $gridLines['horizontal'][] = [
            'position' => $r * $unit,
            'structural' => $r % 2 === 0,
        ];
    }

    $roomsLayout = [];
    $roomPolygons = [];
    foreach ($rooms as $room) {
        $boundary = $room['boundary'];
        $points = [];
        $labels = [];
        $gridPoints = [];
        foreach ($boundary as $coordinate) {
            try {
                $parsed = parse_grid_coordinate($coordinate);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            $x = ($parsed['col']) * $unit;
            $y = ($parsed['row']) * $unit;
            $points[] = $x . ',' . $y;
            $labels[] = $coordinate;
            $gridPoints[] = ['x' => (float) $parsed['col'], 'y' => (float) $parsed['row']];
        }

        if (count($gridPoints) > 1) {
            $firstPoint = $gridPoints[0];
            $lastPoint = $gridPoints[count($gridPoints) - 1];
            if ($firstPoint['x'] === $lastPoint['x'] && $firstPoint['y'] === $lastPoint['y']) {
                array_pop($gridPoints);
            }
        }

        if (!empty($points) && $points[0] !== end($points)) {
            $points[] = $points[0];
        }

        if (!empty($gridPoints)) {
            $roomPolygons[(int) $room['id']] = $gridPoints;
        }

        $roomsLayout[] = [
            'id' => (int) $room['id'],
            'name' => $room['name'],
            'polygonPoints' => implode(' ', $points),
            'pathLabels' => $labels,
            'gridWidth' => (int) $room['grid_width'],
            'gridHeight' => (int) $room['grid_height'],
            'cells' => [],
        ];
    }

    $walkableCells = [];
    $roomCells = [];
    foreach ($roomPolygons as $roomId => $_) {
        $roomCells[$roomId] = [];
    }

    for ($r = 1; $r < $totalRows; $r += 2) {
        for ($c = 1; $c < $totalColumns; $c += 2) {
            $label = grid_label_from_index($r) . ':' . grid_label_from_index($c);
            $cellBelongs = false;
            $cellRooms = [];

            foreach ($roomPolygons as $roomId => $polygon) {
                if (is_point_in_polygon((float) $c, (float) $r, $polygon)) {
                    $roomCells[$roomId][] = $label;
                    $cellBelongs = true;
                    $cellRooms[] = $roomId;
                }
            }

            if ($cellBelongs) {
                $walkableCells[] = [
                    'x' => ($c - 1) * $unit,
                    'y' => ($r - 1) * $unit,
                    'width' => 2 * $unit,
                    'height' => 2 * $unit,
                    'label' => $label,
                    'roomIds' => $cellRooms,
                ];
            }
        }
    }

    foreach ($roomsLayout as &$roomLayout) {
        $roomId = $roomLayout['id'];
        if (isset($roomCells[$roomId])) {
            $roomLayout['cells'] = $roomCells[$roomId];
        }
    }
    unset($roomLayout);

    return [
        'svgWidth' => $svgWidth,
        'svgHeight' => $svgHeight,
        'unit' => $unit,
        'columnLabels' => $columnLabels,
        'rowLabels' => $rowLabels,
        'gridLines' => $gridLines,
        'walkableCells' => $walkableCells,
        'rooms' => $roomsLayout,
    ];
}

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

        $gridWidth = 4;
        $gridHeight = 3;
        $boundary = build_rectangular_room_boundary($gridWidth, $gridHeight);

        $stmt = $db->prepare('INSERT INTO rooms (floor_id, name, style_id, grid_width, grid_height, boundary_path, created_at) VALUES (:floor_id, :name, NULL, :grid_width, :grid_height, :boundary_path, NOW())');
        $stmt->execute([
            'floor_id' => $floorId,
            'name' => 'Foyer',
            'grid_width' => $gridWidth,
            'grid_height' => $gridHeight,
            'boundary_path' => json_encode($boundary, JSON_THROW_ON_ERROR),
        ]);
        $roomId = (int) $db->lastInsertId();

        $walls = [];
        for ($i = 0, $len = count($boundary) - 1; $i < $len; $i++) {
            $start = parse_grid_coordinate($boundary[$i]);
            $end = parse_grid_coordinate($boundary[$i + 1]);

            $walls[] = [
                'start_x' => convert_grid_to_pixels($start['col']),
                'start_y' => convert_grid_to_pixels($start['row']),
                'end_x' => convert_grid_to_pixels($end['col']),
                'end_y' => convert_grid_to_pixels($end['row']),
            ];
        }

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
            'pos' => ($gridWidth * HOUSE_GRID_WALKABLE_UNIT) / 2,
            'door_type' => 'normal',
        ]);

        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}
