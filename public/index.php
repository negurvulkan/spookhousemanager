<?php

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/house.php';
require_once __DIR__ . '/../inc/smarty.php';

require_login();

$currentUser = get_authenticated_user();
$houses = get_houses_by_user_id((int) $currentUser['id']);

$smarty->assign('title', 'Meine Spukhäuser');
$smarty->assign('currentUser', $currentUser);
$smarty->assign('houses', $houses);
$smarty->display('house/list.tpl');
