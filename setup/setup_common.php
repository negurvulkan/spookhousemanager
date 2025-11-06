<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/config.php';

function getSetupConfigFilePath(): string
{
    return __DIR__ . '/../config/config.local.php';
}

function loadExistingSetupConfig(): array
{
    try {
        return loadAppConfig();
    } catch (RuntimeException $exception) {
        return [];
    }
}

function normaliseSetupInput(array $input): array
{
    return [
        'db_host' => trim($input['db_host'] ?? ''),
        'db_name' => trim($input['db_name'] ?? ''),
        'db_user' => trim($input['db_user'] ?? ''),
        'db_pass' => $input['db_pass'] ?? '',
        'db_charset' => trim($input['db_charset'] ?? '') ?: 'utf8mb4',
        'smarty_path' => trim($input['smarty_path'] ?? ''),
    ];
}

function validateSetupInput(array $input): array
{
    $errors = [];

    if ($input['db_host'] === '') {
        $errors['db_host'] = 'Bitte einen Datenbank-Host angeben.';
    }
    if ($input['db_name'] === '') {
        $errors['db_name'] = 'Bitte einen Datenbank-Namen angeben.';
    }
    if ($input['db_user'] === '') {
        $errors['db_user'] = 'Bitte einen Datenbank-Benutzernamen angeben.';
    }
    if ($input['smarty_path'] === '') {
        $errors['smarty_path'] = 'Bitte den Pfad zur Smarty.class.php angeben.';
    } elseif (!is_file($input['smarty_path'])) {
        $errors['smarty_path'] = 'Die angegebene Smarty.class.php wurde nicht gefunden.';
    }

    return $errors;
}

function testSetupDatabaseConnection(array $config): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db_host'],
        $config['db_name'],
        $config['db_charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->query('SELECT 1');

    return $pdo;
}

function writeSetupConfig(array $config): void
{
    $configFile = getSetupConfigFilePath();
    $configDir = dirname($configFile);

    if (!is_dir($configDir)) {
        if (!mkdir($configDir, 0775, true) && !is_dir($configDir)) {
            throw new RuntimeException('Das Konfigurationsverzeichnis konnte nicht erstellt werden.');
        }
    }

    if (!is_writable($configDir)) {
        throw new RuntimeException('Das Konfigurationsverzeichnis ist nicht beschreibbar.');
    }

    $content = "<?php\nreturn " . var_export($config, true) . ";\n";

    if (file_put_contents($configFile, $content) === false) {
        throw new RuntimeException('Die Konfigurationsdatei konnte nicht geschrieben werden.');
    }
}

function runSetupDatabaseUpdates(): string
{
    $updateScript = __DIR__ . '/../update.php';

    if (!file_exists($updateScript)) {
        throw new RuntimeException('Die Update-Routine wurde nicht gefunden.');
    }

    ob_start();
    require $updateScript;
    $output = ob_get_clean();

    return trim((string) $output);
}
