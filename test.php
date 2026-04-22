<?php
require 'db.php';

try {
    $stmt = $pdo->query("SELECT 1 AS test");
    $result = $stmt->fetch();
    echo "Connection test passed: " . $result['test'];
} catch (PDOException $e) {
    die("Test query failed: " . $e->getMessage());
}