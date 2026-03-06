<?php

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'search_engine';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') ?: 'postgres123';
$port = getenv('DB_PORT') ?: '5432';
$sslmode = getenv('DB_SSLMODE') ?: '';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    if ($sslmode !== '') {
        $dsn .= "sslmode=$sslmode;";
    }
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die("Could not connect to database '$dbname': " . $e->getMessage());
}
