<?php

declare(strict_types=1);

require_once __DIR__ . '/setup_common.php';

$existingConfig = loadExistingSetupConfig();
$existingPassword = $existingConfig['db_pass'] ?? '';
$formInput = normaliseSetupInput($_POST ?? []);
$errors = [];
$success = false;
$updateLog = '';
$message = '';
$configData = $formInput;
$configFile = getSetupConfigFilePath();
$configExists = file_exists($configFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateSetupInput($formInput);
    $configData = $formInput;

    if ($configData['db_pass'] === '' && $existingPassword !== '') {
        $configData['db_pass'] = $existingPassword;
    }

    if (empty($errors)) {
        try {
            $configData['smarty_path'] = realpath($configData['smarty_path']) ?: $configData['smarty_path'];
            $formInput['smarty_path'] = $configData['smarty_path'];
            testSetupDatabaseConnection($configData);
        } catch (Throwable $exception) {
            $errors['connection'] = 'Die Verbindung zur Datenbank ist fehlgeschlagen: ' . $exception->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            writeSetupConfig($configData);
            $updateLog = runSetupDatabaseUpdates();
            $message = 'Setup erfolgreich abgeschlossen. Die Konfiguration wurde gespeichert.';
            $success = true;
        } catch (Throwable $exception) {
            $errors['setup'] = 'Beim Ausführen des Setups ist ein Fehler aufgetreten: ' . $exception->getMessage();
        }
    }

    if (!$success) {
        $formInput['db_pass'] = '';
    }
} else {
    if (!empty($existingConfig)) {
        $formInput = normaliseSetupInput($existingConfig);
        $formInput['db_pass'] = '';
        $configData = $formInput;
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spookhouse Manager - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background: radial-gradient(circle at top, #272742, #0f0f1a);
            color: #f8f9fa;
            min-height: 100vh;
        }
        .setup-card {
            background-color: rgba(15, 15, 26, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.45);
        }
        .form-label {
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="setup-card rounded-4 p-4 p-md-5">
                <div class="mb-4 text-center">
                    <h1 class="h3 fw-bold">Spookhouse Manager Setup</h1>
                    <p class="text-secondary">Konfiguriere die Datenbankverbindung und den Pfad zu Smarty.</p>
                </div>

                <?php if ($configExists && !$success): ?>
                    <div class="alert alert-warning">
                        Die Datei <code>config/config.local.php</code> existiert bereits. Beim Speichern wird sie überschrieben.
                    </div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success"><?= h($message) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="db_host" class="form-label">Datenbank-Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="<?= h($formInput['db_host'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="db_name" class="form-label">Datenbank-Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="<?= h($formInput['db_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="db_user" class="form-label">Datenbank-Benutzer</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" value="<?= h($formInput['db_user'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="db_pass" class="form-label">Datenbank-Passwort</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?= h($formInput['db_pass'] ?? '') ?>" autocomplete="new-password">
                            <?php if ($existingPassword !== ''): ?>
                                <div class="form-text text-secondary">Leer lassen, um das bestehende Passwort zu behalten.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="db_charset" class="form-label">Zeichensatz</label>
                            <input type="text" class="form-control" id="db_charset" name="db_charset" value="<?= h($formInput['db_charset'] ?? 'utf8mb4') ?>">
                        </div>
                        <div class="col-12">
                            <label for="smarty_path" class="form-label">Pfad zur <code>Smarty.class.php</code></label>
                            <input type="text" class="form-control" id="smarty_path" name="smarty_path" value="<?= h($formInput['smarty_path'] ?? '') ?>" required>
                            <div class="form-text">Bitte absoluten Pfad angeben, z.&nbsp;B. <code>/var/www/vendor/smarty/smarty/libs/Smarty.class.php</code>.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a class="btn btn-outline-light" href="/">Zurück zur Startseite</a>
                        <button type="submit" class="btn btn-primary btn-lg">Setup ausführen</button>
                    </div>
                </form>

                <?php if ($success): ?>
                    <div class="mt-4">
                        <h2 class="h5">Ergebnis</h2>
                        <p><?= h($message) ?></p>
                        <?php if ($updateLog !== ''): ?>
                            <pre class="bg-dark text-light p-3 rounded-3 small"><?= h($updateLog) ?></pre>
                        <?php endif; ?>
                        <a class="btn btn-success" href="/login.php">Zum Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
