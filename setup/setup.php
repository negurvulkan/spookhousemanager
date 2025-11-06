<?php

declare(strict_types=1);

require_once __DIR__ . '/setup_common.php';

function prompt(string $message, ?string $default = null, bool $allowEmpty = false): string
{
    $suffix = $default !== null ? sprintf(' [%s]', $default) : '';

    while (true) {
        $prompt = $message . $suffix . ': ';
        if (function_exists('readline')) {
            $input = readline($prompt);
            if ($input === false) {
                $input = '';
            }
            $input = trim($input);
        } else {
            fwrite(STDOUT, $prompt);
            $input = fgets(STDIN);
            if ($input === false) {
                $input = '';
            }
            $input = trim($input);
        }

        if ($input === '' && $default !== null) {
            return $default;
        }

        if ($input === '' && $allowEmpty) {
            return '';
        }

        if ($input !== '') {
            return $input;
        }

        fwrite(STDOUT, "Bitte einen Wert eingeben.\n");
    }
}

function confirm(string $message, bool $default = false): bool
{
    $defaultLabel = $default ? 'J/n' : 'j/N';
    $answer = strtolower(prompt($message . " ({$defaultLabel})", $default ? 'j' : 'n'));

    if ($answer === '') {
        return $default;
    }

    return in_array($answer[0], ['j', 'y'], true);
}

$existingConfig = loadExistingSetupConfig();
$configFile = getSetupConfigFilePath();

if (file_exists($configFile) && !confirm('Die Datei config.local.php existiert bereits. Überschreiben?', false)) {
    fwrite(STDOUT, "Setup abgebrochen.\n");
    exit(0);
}

$dbHost = prompt('Datenbank-Host', $existingConfig['db_host'] ?? '127.0.0.1');
$dbName = prompt('Datenbank-Name', $existingConfig['db_name'] ?? 'spookhouse');
$dbUser = prompt('Datenbank-Benutzer', $existingConfig['db_user'] ?? 'spookhouse');

$existingPassword = $existingConfig['db_pass'] ?? null;
if ($existingPassword !== null && $existingPassword !== '') {
    $passwordInput = prompt('Datenbank-Passwort (leer lassen, um den bestehenden Wert zu behalten)', null, true);
    $dbPass = $passwordInput === '' ? $existingPassword : $passwordInput;
} else {
    $dbPass = prompt('Datenbank-Passwort', '', true);
}
$dbCharset = prompt('Datenbank-Zeichensatz', $existingConfig['db_charset'] ?? 'utf8mb4');

$defaultSmarty = $existingConfig['smarty_path'] ?? (__DIR__ . '/../vendor/smarty/smarty/libs/Smarty.class.php');

while (true) {
    $smartyPath = prompt('Pfad zur Smarty.class.php', $defaultSmarty);

    if (!is_file($smartyPath)) {
        fwrite(STDOUT, "Die angegebene Datei wurde nicht gefunden. Bitte erneut versuchen.\n");
        continue;
    }

    $smartyPath = realpath($smartyPath) ?: $smartyPath;
    break;
}

$config = [
    'db_host' => $dbHost,
    'db_name' => $dbName,
    'db_user' => $dbUser,
    'db_pass' => $dbPass,
    'db_charset' => $dbCharset,
    'smarty_path' => $smartyPath,
];

fwrite(STDOUT, "\nTeste Datenbankverbindung...\n");

try {
    testSetupDatabaseConnection($config);
    fwrite(STDOUT, "Datenbankverbindung erfolgreich.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, 'Verbindung zur Datenbank fehlgeschlagen: ' . $exception->getMessage() . "\n");
    exit(1);
}

try {
    writeSetupConfig($config);
    fwrite(STDOUT, "Konfiguration gespeichert in config/config.local.php.\n");
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "\nFühre Datenbank-Updates aus...\n");

try {
    $output = runSetupDatabaseUpdates();
    if ($output !== '') {
        fwrite(STDOUT, $output . "\n");
    }
    fwrite(STDOUT, "Setup abgeschlossen.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Beim Ausführen der Updates ist ein Fehler aufgetreten: ' . $exception->getMessage() . "\n");
    exit(1);
}
