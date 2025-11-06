<?php

require_once __DIR__ . '/db.php';

function find_user_by_username(string $username): ?array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_id(int $userId): ?array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function create_user(string $username, string $password): int
{
    $db = getDb();
    $stmt = $db->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, NOW())');
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) $db->lastInsertId();
}
