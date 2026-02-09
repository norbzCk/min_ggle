<?php

$host = "localhost";
$dbname = "search_engine";
$user = "postgres";
$password = "postgres123";
$port = "5432";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, /* This is means for error handling use exception mode */
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, /* This means for fetching data use the as associative arrays format */
        PDO::ATTR_EMULATE_PREPARES => false, /* This lets PostgreSQL handle prepared statements for real., true would emulate(fake) them */
    ];
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}
?>