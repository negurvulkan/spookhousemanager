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

    $getColumnDefinition = static function (string $table, string $column) use ($db): ?array {
        $stmt = $db->prepare(
            'SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        $definition = $stmt->fetch(PDO::FETCH_ASSOC);

        return $definition ?: null;
    };

    $foreignKeyExists = static function (string $table, string $constraint) use ($db): bool {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint'
        );
        $stmt->execute([
            'table' => $table,
            'constraint' => $constraint,
        ]);

        return (bool) $stmt->fetchColumn();
    };

    if (!$tableExists('wall_side_types')) {
        $db->exec('CREATE TABLE IF NOT EXISTS wall_side_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  sprite_path VARCHAR(255) NOT NULL,
  material VARCHAR(50) DEFAULT NULL,
  is_outside TINYINT(1) NOT NULL DEFAULT 0,
  tint VARCHAR(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    $db->exec("INSERT IGNORE INTO wall_side_types (code, sprite_path, material, is_outside) VALUES
('outer_stone', 'assets/walls/stone.png', 'stone', 1),
('inner_wood', 'assets/walls/wood.png', 'wood', 0)");

    $ensureUnsignedIntColumn = static function (string $column) use ($db, $columnExists, $getColumnDefinition): void {
        if (!$columnExists('walls', $column)) {
            $db->exec(sprintf('ALTER TABLE walls ADD COLUMN %s INT UNSIGNED NULL', $column));

            return;
        }

        $definition = $getColumnDefinition('walls', $column);
        if (!$definition) {
            return;
        }

        $dataType = strtolower((string) $definition['DATA_TYPE']);
        $columnType = strtolower((string) $definition['COLUMN_TYPE']);

        if ($dataType != 'int') {
            $db->exec(sprintf(
                'UPDATE walls SET %1$s = NULL WHERE %1$s IS NOT NULL AND %1$s NOT REGEXP \'^[0-9]+$\'',
                $column
            ));
        }

        if ($dataType != 'int' || strpos($columnType, 'unsigned') === false) {
            $db->exec(sprintf('ALTER TABLE walls MODIFY COLUMN %s INT UNSIGNED NULL', $column));
        }
    };

    $ensureUnsignedIntColumn('side_a_type');
    $ensureUnsignedIntColumn('side_b_type');

    $db->exec('UPDATE walls SET side_a_type = NULL WHERE side_a_type = 0');
    $db->exec('UPDATE walls SET side_b_type = NULL WHERE side_b_type = 0');

    if (!$foreignKeyExists('walls', 'fk_walls_side_a')) {
        $db->exec('ALTER TABLE walls ADD CONSTRAINT fk_walls_side_a FOREIGN KEY (side_a_type) REFERENCES wall_side_types(id) ON DELETE SET NULL');
    }

    if (!$foreignKeyExists('walls', 'fk_walls_side_b')) {
        $db->exec('ALTER TABLE walls ADD CONSTRAINT fk_walls_side_b FOREIGN KEY (side_b_type) REFERENCES wall_side_types(id) ON DELETE SET NULL');
    }

    if ($tableExists('wall_sides') && !$tableExists('wall_sides_legacy')) {
        $db->exec('RENAME TABLE wall_sides TO wall_sides_legacy');
    }
};
