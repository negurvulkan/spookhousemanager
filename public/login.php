<?php

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/smarty.php';

ensure_session_started();

if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Bitte Benutzernamen und Passwort eingeben.';
    } elseif (login_user($username, $password)) {
        header('Location: /index.php');
        exit;
    } else {
        $error = 'Ungültige Anmeldedaten.';
    }
}

$smarty->assign('currentUser', null);
$smarty->assign('error', $error);
$smarty->display('auth/login.tpl');
