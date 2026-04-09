<?php

$hostName = $_SERVER['HTTP_HOST'] ?? '';
$isLocal =
    str_contains($hostName, 'localhost') ||
    str_contains($hostName, '127.0.0.1');

if ($isLocal) {
    /*
    |--------------------------------------------------------------------------
    | Local XAMPP path
    |--------------------------------------------------------------------------
    | Example local project:
    | C:\xampp\htdocs\titan-site\db.php
    | C:\xampp\htdocs\titan-site\private\db-config.php
    */
    $configPath = __DIR__ . '/../private/db-config.php';
} else {
    /*
    |--------------------------------------------------------------------------
    | cPanel production path
    |--------------------------------------------------------------------------
    | Example:
    | /home/titanva1/private/db-config.php
    */
    $configPath = '/home/titanva1/private/db-config.php';
}

if (!is_file($configPath)) {
    die('Database config not found: ' . htmlspecialchars($configPath, ENT_QUOTES, 'UTF-8'));
}

$config = require $configPath;

if (!is_array($config)) {
    die('Database config file is invalid.');
}

$selectedConfig = $isLocal
    ? ($config['local'] ?? null)
    : ($config['production'] ?? null);

if (
    !is_array($selectedConfig) ||
    empty($selectedConfig['host']) ||
    empty($selectedConfig['port']) ||
    empty($selectedConfig['dbname']) ||
    !array_key_exists('username', $selectedConfig) ||
    !array_key_exists('password', $selectedConfig)
) {
    die('Database configuration is missing required values.');
}

$host = $selectedConfig['host'];
$port = $selectedConfig['port'];
$dbname = $selectedConfig['dbname'];
$username = $selectedConfig['username'];
$password = $selectedConfig['password'];

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}