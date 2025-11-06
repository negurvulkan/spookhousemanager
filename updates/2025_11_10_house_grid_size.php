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

    $defaultWidth = 4;
    $defaultHeight = 3;

    if (!$columnExists('houses', 'grid_width')) {
        $db->exec('ALTER TABLE houses ADD grid_width INT NOT NULL DEFAULT 4 AFTER name');
    }

    if (!$columnExists('houses', 'grid_height')) {
        $db->exec('ALTER TABLE houses ADD grid_height INT NOT NULL DEFAULT 3 AFTER grid_width');
    }

    $roomsHaveGridWidth = $columnExists('rooms', 'grid_width');
    $roomsHaveGridHeight = $columnExists('rooms', 'grid_height');

    if ($roomsHaveGridWidth && $roomsHaveGridHeight) {
        $stmt = $db->query(
            'SELECT f.house_id, MAX(r.grid_width) AS max_width, MAX(r.grid_height) AS max_height
            FROM rooms r
            INNER JOIN floors f ON r.floor_id = f.id
            GROUP BY f.house_id'
        );

        if ($stmt) {
            $updateStmt = $db->prepare('UPDATE houses SET grid_width = :width, grid_height = :height WHERE id = :house_id');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $width = isset($row['max_width']) ? (int) $row['max_width'] : 0;
                $height = isset($row['max_height']) ? (int) $row['max_height'] : 0;

                $width = $width > 0 ? $width : $defaultWidth;
                $height = $height > 0 ? $height : $defaultHeight;

                $updateStmt->execute([
                    'width' => $width,
                    'height' => $height,
                    'house_id' => (int) $row['house_id'],
                ]);
            }
        }
    }

    $normalizeStmt = $db->prepare('UPDATE houses SET grid_width = :width, grid_height = :height WHERE id = :house_id');
    $housesStmt = $db->query('SELECT id, grid_width, grid_height FROM houses');
    if ($housesStmt) {
        while ($house = $housesStmt->fetch(PDO::FETCH_ASSOC)) {
            $width = isset($house['grid_width']) ? (int) $house['grid_width'] : 0;
            $height = isset($house['grid_height']) ? (int) $house['grid_height'] : 0;

            $newWidth = $width > 0 ? $width : $defaultWidth;
            $newHeight = $height > 0 ? $height : $defaultHeight;

            if ($newWidth !== $width || $newHeight !== $height) {
                $normalizeStmt->execute([
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'house_id' => (int) $house['id'],
                ]);
            }
        }
    }

    if ($roomsHaveGridWidth) {
        $db->exec('ALTER TABLE rooms DROP COLUMN grid_width');
    }

    if ($roomsHaveGridHeight) {
        $db->exec('ALTER TABLE rooms DROP COLUMN grid_height');
    }
};
