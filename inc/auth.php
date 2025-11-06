<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/user.php';

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function is_logged_in(): bool
{
    ensure_session_started();
    return isset($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function login_user(string $username, string $password): bool
{
    $user = find_user_by_username($username);
    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    ensure_session_started();
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];

    return true;
}

function logout_user(): void
{
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function get_authenticated_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return find_user_by_id((int) $_SESSION['user_id']);
}
