<?php

header('Content-Type: text/plain; charset=UTF-8');

$databaseUrl = getenv('DATABASE_URL');
$source = 'env vars';
$host = '';
$port = '';
$dbname = '';
$user = '';
$sslmode = '';
$notes = [];

if ($databaseUrl) {
    $source = 'DATABASE_URL';
    $parts = parse_url($databaseUrl);

    if ($parts === false) {
        $notes[] = 'DATABASE_URL could not be parsed.';
    } else {
        $host = strtolower(trim((string)($parts['host'] ?? '')));
        $port = (string)($parts['port'] ?? 5432);
        $user = rawurldecode($parts['user'] ?? '');
        $dbname = rawurldecode(ltrim($parts['path'] ?? '', '/'));
        $sslmode = 'require';

        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            if (!empty($queryParams['sslmode'])) {
                $sslmode = (string)$queryParams['sslmode'];
            }
        }

        if ($host === '') {
            $notes[] = 'Host is empty inside DATABASE_URL.';
        }
        if ($dbname === '') {
            $notes[] = 'Database name is empty inside DATABASE_URL.';
        }
        if ($user === '') {
            $notes[] = 'Username is empty inside DATABASE_URL.';
        }
    }
} else {
    $host = trim((string)getenv('DB_HOST'));
    $port = (string)(getenv('DB_PORT') ?: 5432);
    $dbname = trim((string)getenv('DB_NAME'));
    $user = trim((string)getenv('DB_USER'));
    $sslmode = (string)(getenv('DB_SSLMODE') ?: 'require');

    if ($host === '') {
        $notes[] = 'DB_HOST is empty.';
    }
    if ($dbname === '') {
        $notes[] = 'DB_NAME is empty.';
    }
    if ($user === '') {
        $notes[] = 'DB_USER is empty.';
    }
    if ((string)getenv('DB_PASS') === '') {
        $notes[] = 'DB_PASS is empty.';
    }
}

echo "DB config debug\n";
echo "source: {$source}\n";
echo "host: " . ($host !== '' ? $host : '[empty]') . "\n";
echo "port: " . ($port !== '' ? $port : '[empty]') . "\n";
echo "dbname: " . ($dbname !== '' ? $dbname : '[empty]') . "\n";
echo "user: " . ($user !== '' ? $user : '[empty]') . "\n";
echo "sslmode: " . ($sslmode !== '' ? $sslmode : '[empty]') . "\n";
echo "password_set: " . (($databaseUrl || (string)getenv('DB_PASS') !== '') ? 'yes' : 'no') . "\n";

if ($notes) {
    echo "notes:\n";
    foreach ($notes as $note) {
        echo "- {$note}\n";
    }
} else {
    echo "notes:\n";
    echo "- configuration looks structurally complete\n";
}
