<?php

require_once __DIR__ . '/../inc/user.php';

$argv = $_SERVER['argv'] ?? [];
array_shift($argv);

if (count($argv) < 2) {
    fwrite(STDERR, "Usage: php cli/create_admin.php <username> <password>\n");
    exit(1);
}

[$username, $password] = $argv;

if (find_user_by_username($username)) {
    fwrite(STDERR, "User {$username} already exists.\n");
    exit(1);
}

$userId = create_user($username, $password);
fwrite(STDOUT, "Created user with ID {$userId}.\n");
