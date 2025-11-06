<?php

require_once __DIR__ . '/config.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

$config = [];
try {
    $config = loadAppConfig();
} catch (RuntimeException $exception) {
    // Configuration is optional for locating Smarty; fall back to defaults.
}

$potentialPaths = [];
if (isset($config['smarty_path']) && is_string($config['smarty_path'])) {
    $potentialPaths[] = $config['smarty_path'];
}

$potentialPaths[] = __DIR__ . '/../vendor/smarty/smarty/libs/Smarty.class.php';
$potentialPaths[] = __DIR__ . '/../vendor/smarty/libs/Smarty.class.php';

foreach ($potentialPaths as $path) {
    if ($path && file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!class_exists('Smarty')) {
    throw new RuntimeException('Smarty library is not available. Please install Smarty via Composer or configure the Smarty path.');
}

$smarty = new Smarty();
$smarty->setTemplateDir(__DIR__ . '/../templates/');
$smarty->setCompileDir(__DIR__ . '/../templates_c/');
$smarty->setCacheDir(__DIR__ . '/../cache/');
$smarty->setConfigDir(__DIR__ . '/../config/');
$smarty->escape_html = true;
