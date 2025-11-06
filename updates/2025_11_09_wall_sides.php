<?php

return static function (PDO $db): void {
    $tableExists = static function (string $table) use ($db): bool {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
        );
        $stmt->execute(['table' => $table]);

        return (bool) $stmt->fetchColumn();
    };

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

    $indexExists = static function (string $table, string $index) use ($db): bool {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name'
        );
        $stmt->execute([
            'table' => $table,
            'index_name' => $index,
        ]);

        return (bool) $stmt->fetchColumn();
    };

    if (!$tableExists('wall_sides')) {
        $db->exec('CREATE TABLE wall_sides (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wall_id INT UNSIGNED NOT NULL,
            side ENUM(\'A\',\'B\') NOT NULL,
            sprite_path VARCHAR(255) DEFAULT NULL,
            material VARCHAR(50) DEFAULT NULL,
            is_outside TINYINT(1) NOT NULL DEFAULT 0,
            tint VARCHAR(7) DEFAULT NULL,
            CONSTRAINT fk_wall_sides_wall FOREIGN KEY (wall_id) REFERENCES walls(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    if (!$indexExists('wall_sides', 'wall_side_unique')) {
        $db->exec('ALTER TABLE wall_sides ADD UNIQUE KEY wall_side_unique (wall_id, side)');
    }

    if (!$indexExists('wall_sides', 'idx_wall_sides_wall')) {
        $db->exec('ALTER TABLE wall_sides ADD INDEX idx_wall_sides_wall (wall_id)');
    }

    $roomIdStmt = $db->query('SELECT id FROM rooms ORDER BY id ASC LIMIT 1');
    $roomId = $roomIdStmt ? $roomIdStmt->fetchColumn() : false;

    if (!$roomId) {
        return;
    }

    $gridIndexFromColumnLabel = static function (string $label): int {
        $label = strtoupper($label);
        $length = strlen($label);
        if ($length === 0) {
            throw new \InvalidArgumentException('Column label must not be empty.');
        }

        $index = 0;
        for ($i = 0; $i < $length; $i++) {
            $index *= 26;
            $index += (ord($label[$i]) - ord('A') + 1);
        }

        return ($index - 1) * 2;
    };

    $gridIndexFromRowNumber = static function (int $rowNumber): int {
        if ($rowNumber < 1) {
            throw new \InvalidArgumentException('Row number must be >= 1.');
        }

        return ($rowNumber - 1) * 2;
    };

    $convertGridIndexToPixels = static function (int $index): int {
        $unit = 100;

        return intdiv($index, 2) * $unit;
    };

    $startColIndex = $gridIndexFromColumnLabel('B');
    $endColIndex = $gridIndexFromColumnLabel('E');
    $rowIndex = $gridIndexFromRowNumber(1);

    $startX = $convertGridIndexToPixels($startColIndex);
    $endX = $convertGridIndexToPixels($endColIndex);
    $rowY = $convertGridIndexToPixels($rowIndex);

    $wallLookupStmt = $db->prepare('SELECT id FROM walls WHERE room_id = :room_id AND start_x = :start_x AND start_y = :start_y AND end_x = :end_x AND end_y = :end_y LIMIT 1');
    $wallLookupStmt->execute([
        'room_id' => $roomId,
        'start_x' => $startX,
        'start_y' => $rowY,
        'end_x' => $endX,
        'end_y' => $rowY,
    ]);

    $wallId = $wallLookupStmt->fetchColumn();

    if (!$wallId) {
        if (!$columnExists('walls', 'side_a_type')) {
            $db->exec('ALTER TABLE walls ADD side_a_type VARCHAR(50) NOT NULL DEFAULT \"default\"');
        }
        if (!$columnExists('walls', 'side_b_type')) {
            $db->exec('ALTER TABLE walls ADD side_b_type VARCHAR(50) NOT NULL DEFAULT \"default\"');
        }

        $insertWallStmt = $db->prepare('INSERT INTO walls (room_id, start_x, start_y, end_x, end_y, side_a_type, side_b_type, status) VALUES (:room_id, :start_x, :start_y, :end_x, :end_y, :side_a_type, :side_b_type, :status)');
        $insertWallStmt->execute([
            'room_id' => $roomId,
            'start_x' => $startX,
            'start_y' => $rowY,
            'end_x' => $endX,
            'end_y' => $rowY,
            'side_a_type' => 'demo_outer',
            'side_b_type' => 'demo_inner',
            'status' => 'normal',
        ]);

        $wallId = (int) $db->lastInsertId();
    }

    if (!$wallId) {
        return;
    }

    $sideExistsStmt = $db->prepare('SELECT COUNT(*) FROM wall_sides WHERE wall_id = :wall_id AND side = :side');
    $insertSideStmt = $db->prepare('INSERT INTO wall_sides (wall_id, side, sprite_path, material, is_outside, tint) VALUES (:wall_id, :side, :sprite_path, :material, :is_outside, :tint)');

    foreach ([
        'A' => ['sprite' => 'assets/walls/outer_stone.png', 'is_outside' => 1, 'material' => 'stone'],
        'B' => ['sprite' => 'assets/walls/inner_wallpaper.png', 'is_outside' => 0, 'material' => 'wallpaper'],
    ] as $side => $data) {
        $sideExistsStmt->execute([
            'wall_id' => $wallId,
            'side' => $side,
        ]);

        if ($sideExistsStmt->fetchColumn()) {
            continue;
        }

        $insertSideStmt->execute([
            'wall_id' => $wallId,
            'side' => $side,
            'sprite_path' => $data['sprite'],
            'material' => $data['material'],
            'is_outside' => $data['is_outside'],
            'tint' => null,
        ]);
    }
};
