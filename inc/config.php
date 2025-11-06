<?php

declare(strict_types=1);

function loadAppConfig(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $defaultConfigFile = __DIR__ . '/../config/config.php';
    $localConfigFile = __DIR__ . '/../config/config.local.php';

    if (file_exists($localConfigFile)) {
        $config = require $localConfigFile;
    } elseif (file_exists($defaultConfigFile)) {
        $config = require $defaultConfigFile;
    } else {
        throw new RuntimeException('Configuration file not found.');
    }

    if (!is_array($config)) {
        throw new RuntimeException('Configuration file must return an array.');
    }

    return $config;
}
