<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'sample-data.php';

$pdo->exec("TRUNCATE TABLE search_items");

$sql = "
INSERT INTO search_items (title, description, page_name, page_fav_icon_path, page_url, created_at)
VALUES (:title, :description, :page_name, :icon, :url, :created_at)
";

$stmt = $pdo->prepare($sql);

foreach ($sampleData as $row) {
    $stmt->execute([
        ':title' => $row['title'],
        ':description' => $row['description'],
        ':page_name' => $row['page_name'],
        ':icon' => $row['page_fav_icon_path'],
        ':url' => $row['page_url'],
        ':created_at' => $row['created_at'],
    ]);
}

echo "Data inserted successfully";

?>