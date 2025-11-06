<?php

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/house.php';
require_once __DIR__ . '/../inc/smarty.php';

require_login();

$currentUser = get_authenticated_user();
$houseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($houseId <= 0) {
    header('Location: /index.php');
    exit;
}

$house = get_house_by_id($houseId, (int) $currentUser['id']);
if (!$house) {
    header('Location: /index.php');
    exit;
}

$floors = get_floors_by_house_id($houseId);
$selectedFloorId = $floors[0]['id'] ?? null;
if (isset($_GET['floor'])) {
    $requestedFloor = (int) $_GET['floor'];
    foreach ($floors as $floor) {
        if ((int) $floor['id'] === $requestedFloor) {
            $selectedFloorId = $requestedFloor;
            break;
        }
    }
}

$walls = $selectedFloorId ? get_walls_by_floor_id($selectedFloorId) : [];

$smarty->assign('title', $house['name']);
$smarty->assign('currentUser', $currentUser);
$smarty->assign('house', $house);
$smarty->assign('floors', $floors);
$smarty->assign('selectedFloorId', $selectedFloorId);
$smarty->assign('walls', $walls);
$smarty->display('house/view.tpl');
