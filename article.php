<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require 'db.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    $title = 'Article not found';
    $content = 'Missing article slug.';
} else {
    $stmt = $pdo->prepare(
        "SELECT title, description, page_url, created_at
         FROM search_items
         WHERE page_url ILIKE :slug
         ORDER BY created_at DESC
         LIMIT 1"
    );
    $stmt->execute([':slug' => '%' . $slug . '%']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $title = $row['title'] ?? 'Untitled';
        $content = $row['description'] ?? '';
    } else {
        $title = 'Article not found';
        $content = 'No content matched this link.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
</head>
<body>
<header>
    <div class="search-bar-container">
        <a href="index.html" class="logo">Search</a>
        <form class="search-form" action="results.php" method="get">
            <span class="search-icon">🔍</span>
            <input 
                type="search" 
                name="q" 
                class="search-input" 
                placeholder="Search..."
                autocomplete="off"
            >
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</header>

<main>
    <h1 class="result-title" style="margin-top: 10px;">
        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <div class="result-snippet" style="font-size: 16px;">
        <?= nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) ?>
    </div>
</main>
</body>
</html>
