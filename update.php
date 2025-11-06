<?php

require_once __DIR__ . '/inc/db.php';

$db = getDb();

function ensureSchemaUpdatesTable(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS schema_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function hasUpdateRun(PDO $db, string $name): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM schema_updates WHERE name = :name');
    $stmt->execute(['name' => $name]);

    return (bool) $stmt->fetchColumn();
}

function markUpdateRun(PDO $db, string $name): void
{
    $stmt = $db->prepare('INSERT INTO schema_updates (name, executed_at) VALUES (:name, NOW())');
    $stmt->execute(['name' => $name]);
}

ensureSchemaUpdatesTable($db);

$updatesDir = __DIR__ . '/updates';
$updateFiles = glob($updatesDir . '/*.php');
sort($updateFiles);

foreach ($updateFiles as $file) {
    $name = basename($file, '.php');
    if (hasUpdateRun($db, $name)) {
        continue;
    }

    $callable = require $file;
    if (!is_callable($callable)) {
        throw new RuntimeException(sprintf('Update file %s did not return a callable.', $file));
    }

    try {
        if (!$db->inTransaction()) {
            $db->beginTransaction();
        }

        $callable($db);
        markUpdateRun($db, $name);

        if ($db->inTransaction()) {
            $db->commit();
        }

        echo "Executed update {$name}\n";
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $exception;
    }
}

echo "All updates executed.\n";
