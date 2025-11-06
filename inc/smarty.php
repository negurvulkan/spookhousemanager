<?php

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if (!class_exists('Smarty')) {
    $smartyLib = __DIR__ . '/../vendor/smarty/smarty/libs/Smarty.class.php';
    if (file_exists($smartyLib)) {
        require_once $smartyLib;
    }
}

if (!class_exists('Smarty')) {
    throw new RuntimeException('Smarty library is not available. Please install Smarty via Composer.');
}

$smarty = new Smarty();
$smarty->setTemplateDir(__DIR__ . '/../templates/');
$smarty->setCompileDir(__DIR__ . '/../templates_c/');
$smarty->setCacheDir(__DIR__ . '/../cache/');
$smarty->setConfigDir(__DIR__ . '/../config/');
$smarty->escape_html = true;
