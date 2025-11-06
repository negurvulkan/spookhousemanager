<?php

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/house.php';
require_once __DIR__ . '/../inc/smarty.php';

require_login();

$currentUser = get_authenticated_user();
$floorId = isset($_GET['floor_id']) ? (int) $_GET['floor_id'] : 0;
if ($floorId <= 0) {
    header('Location: /index.php');
    exit;
}

$floor = get_floor_with_house_by_id($floorId);
if (!$floor || (int) $floor['user_id'] !== (int) $currentUser['id']) {
    header('Location: /index.php');
    exit;
}

$smarty->assign('title', 'Wand-Canvas');
$smarty->assign('currentUser', $currentUser);
$smarty->assign('floor', $floor);
$smarty->assign('floorId', $floorId);
$smarty->display('house/canvas.tpl');
