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

    if ($columnExists('rooms', 'boundary_path')) {
        $db->exec('ALTER TABLE rooms DROP COLUMN boundary_path');
    }
};
