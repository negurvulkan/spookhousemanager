<?php

return static function (PDO $db): void {
    $columnExists = static function (string $table, string $column) use ($db): bool {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        return (bool) $stmt->fetchColumn();
    };

    if (!$columnExists('rooms', 'grid_width')) {
        $db->exec('ALTER TABLE rooms ADD grid_width INT NOT NULL DEFAULT 0 AFTER style_id');
    }

    if (!$columnExists('rooms', 'grid_height')) {
        $db->exec('ALTER TABLE rooms ADD grid_height INT NOT NULL DEFAULT 0 AFTER grid_width');
    }

    if (!$columnExists('rooms', 'boundary_path')) {
        $db->exec('ALTER TABLE rooms ADD boundary_path TEXT NULL AFTER grid_height');
    }

    $roomNeedsUpdateStmt = $db->query('SELECT id FROM rooms WHERE boundary_path IS NULL OR boundary_path = ""');
    $roomIds = $roomNeedsUpdateStmt ? $roomNeedsUpdateStmt->fetchAll(PDO::FETCH_COLUMN) : [];

    if (empty($roomIds)) {
        return;
    }

    $gridLabelFromIndex = static function (int $index): string {
        $base = intdiv($index, 2);
        $alphabetIndex = $base % 26;
        $repeat = intdiv($base, 26);

        $char = chr(ord('A') + $alphabetIndex);
        $label = str_repeat($char, $repeat + 1);

        return $index % 2 === 0 ? $label : strtolower($label);
    };

    $formatCoordinate = static function (int $rowIndex, int $colIndex) use ($gridLabelFromIndex): string {
        return $gridLabelFromIndex($rowIndex) . ':' . $gridLabelFromIndex($colIndex);
    };

    $buildBoundary = static function (int $gridWidth, int $gridHeight) use ($formatCoordinate): array {
        $maxRow = $gridHeight * 2;
        $maxCol = $gridWidth * 2;

        return [
            $formatCoordinate(0, 0),
            $formatCoordinate(0, $maxCol),
            $formatCoordinate($maxRow, $maxCol),
            $formatCoordinate($maxRow, 0),
            $formatCoordinate(0, 0),
        ];
    };

    $wallBoundsStmt = $db->prepare(
        'SELECT 
            MIN(LEAST(start_x, end_x)) AS min_x,
            MAX(GREATEST(start_x, end_x)) AS max_x,
            MIN(LEAST(start_y, end_y)) AS min_y,
            MAX(GREATEST(start_y, end_y)) AS max_y
        FROM walls
        WHERE room_id = :room_id'
    );

    $updateRoomStmt = $db->prepare('UPDATE rooms SET grid_width = :grid_width, grid_height = :grid_height, boundary_path = :boundary_path WHERE id = :id');

    foreach ($roomIds as $roomId) {
        $wallBoundsStmt->execute(['room_id' => $roomId]);
        $bounds = $wallBoundsStmt->fetch(PDO::FETCH_ASSOC);

        $width = isset($bounds['max_x'], $bounds['min_x']) ? ((int) $bounds['max_x'] - (int) $bounds['min_x']) : 0;
        $height = isset($bounds['max_y'], $bounds['min_y']) ? ((int) $bounds['max_y'] - (int) $bounds['min_y']) : 0;

        $cellSize = 100;
        $gridWidth = $width > 0 ? max(1, (int) round($width / $cellSize)) : 1;
        $gridHeight = $height > 0 ? max(1, (int) round($height / $cellSize)) : 1;

        $boundary = $buildBoundary($gridWidth, $gridHeight);

        $updateRoomStmt->execute([
            'grid_width' => $gridWidth,
            'grid_height' => $gridHeight,
            'boundary_path' => json_encode($boundary),
            'id' => $roomId,
        ]);
    }
};
