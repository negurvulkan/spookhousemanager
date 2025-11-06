<?php

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/house.php';

require_login();

$currentUser = get_authenticated_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    $name = 'Neues Spukhaus';
}

$houseId = create_house((int) $currentUser['id'], $name);
$house = get_house_by_id($houseId, (int) $currentUser['id']);
if ($house) {
    generateInitialHouse($house);
}

header('Location: /house_view.php?id=' . $houseId);
exit;
