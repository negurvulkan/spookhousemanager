<?php

return static function (PDO $db): void {
    $tableExists = static function (string $table) use ($db): bool {
        $stmt = $db->prepare("SHOW TABLES LIKE :table");
        $stmt->execute(['table' => $table]);
        return (bool) $stmt->fetchColumn();
    };

    if (!$tableExists('users')) {
        $db->exec('CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    if (!$tableExists('houses')) {
        $db->exec('CREATE TABLE houses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_houses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            INDEX idx_houses_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    if (!$tableExists('floors')) {
        $db->exec('CREATE TABLE floors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            house_id INT NOT NULL,
            level INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_floors_house FOREIGN KEY (house_id) REFERENCES houses (id) ON DELETE CASCADE,
            INDEX idx_floors_house (house_id),
            INDEX idx_floors_level (level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    if (!$tableExists('rooms')) {
        $db->exec('CREATE TABLE rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            floor_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            style_id INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_rooms_floor FOREIGN KEY (floor_id) REFERENCES floors (id) ON DELETE CASCADE,
            INDEX idx_rooms_floor (floor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    if (!$tableExists('walls')) {
        $db->exec('CREATE TABLE walls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            start_x INT NOT NULL,
            start_y INT NOT NULL,
            end_x INT NOT NULL,
            end_y INT NOT NULL,
            side_a_type VARCHAR(50) NOT NULL,
            side_b_type VARCHAR(50) NOT NULL,
            status ENUM("normal","damaged","ripped_open","sealed") NOT NULL DEFAULT "normal",
            CONSTRAINT fk_walls_room FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE,
            INDEX idx_walls_room (room_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    if (!$tableExists('doors')) {
        $db->exec('CREATE TABLE doors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            wall_id INT NOT NULL,
            pos INT NOT NULL,
            door_type VARCHAR(50) NOT NULL,
            CONSTRAINT fk_doors_wall FOREIGN KEY (wall_id) REFERENCES walls (id) ON DELETE CASCADE,
            INDEX idx_doors_wall (wall_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    if (!$tableExists('windows')) {
        $db->exec('CREATE TABLE windows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            wall_id INT NOT NULL,
            window_type VARCHAR(50) NOT NULL,
            CONSTRAINT fk_windows_wall FOREIGN KEY (wall_id) REFERENCES walls (id) ON DELETE CASCADE,
            INDEX idx_windows_wall (wall_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
};
