<?php

require_once __DIR__ . '/env.php';

function get_database_connection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', '127.0.0.1') ?: '127.0.0.1';
    $port = env('DB_PORT', '3306') ?: '3306';
    $database = env('DB_NAME', 'zypp_camera_house') ?: 'zypp_camera_house';
    $username = env('DB_USER', 'root') ?: 'root';
    $password = env('DB_PASS', '') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}
