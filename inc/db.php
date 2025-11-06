<?php

require_once __DIR__ . '/config.php';

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = loadDbConfig();

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db_host'],
        $config['db_name'],
        $config['db_charset'] ?? 'utf8mb4'
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

    return $pdo;
}

function loadDbConfig(): array
{
    $config = loadAppConfig();

    $requiredKeys = ['db_host', 'db_name', 'db_user', 'db_pass'];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $config)) {
            throw new InvalidArgumentException("Missing database configuration key: {$key}");
        }
    }

    if (!isset($config['db_charset'])) {
        $config['db_charset'] = 'utf8mb4';
    }

    return $config;
}
