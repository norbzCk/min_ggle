<?php
/**
 * PostgreSQL connection.
 * Priority:
 * 1) DATABASE_URL (recommended for Neon)
 * 2) DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS/DB_SSLMODE
 */

$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts === false) {
        die("Invalid DATABASE_URL format.");
    }

    $host = strtolower(trim((string)($parts['host'] ?? '')));
    $port = (int)($parts['port'] ?? 5432);
    $user = rawurldecode($parts['user'] ?? '');
    $password = rawurldecode($parts['pass'] ?? '');
    $dbname = rawurldecode(ltrim($parts['path'] ?? '', '/'));

    $sslmode = 'require';
    if (isset($parts['query'])) {
        parse_str($parts['query'], $queryParams);
        if (!empty($queryParams['sslmode'])) {
            $sslmode = $queryParams['sslmode'];
        }
    }

    if ($host === '' || $dbname === '' || $user === '' || $password === '') {
        die("DATABASE_URL is incomplete. It must include host, database name, username, and password.");
    }
} else {
    $host = trim((string)getenv('DB_HOST'));
    $dbname = trim((string)getenv('DB_NAME'));
    $user = trim((string)getenv('DB_USER'));
    $password = (string)getenv('DB_PASS');
    $port = (int)(getenv('DB_PORT') ?: 5432);
    $sslmode = getenv('DB_SSLMODE') ?: 'require';

    if ($host === '' || $dbname === '' || $user === '' || $password === '') {
        die("Database is not configured. Set DATABASE_URL (recommended) or all DB_HOST, DB_NAME, DB_USER, DB_PASS.");
    }

    $host = strtolower($host);
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // Throw exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Fetch assoc arrays
        PDO::ATTR_EMULATE_PREPARES => false,               // Use real prepared statements
    ];

    $pdo = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    die("Could not connect to database '$dbname': " . $e->getMessage());
}
