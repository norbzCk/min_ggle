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
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> - Search</title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
</head>
<body>
<header>
    <div class="search-bar-container">
        <a href="index.html" class="logo">Search</a>
        <form class="search-form" action="results.php" method="get">
            <div class="search-input-wrap">
                <span class="search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input 
                    type="search" 
                    name="q" 
                    class="search-input" 
                    placeholder="Search..."
                    autocomplete="off"
                    required
                >
            </div>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</header>

<main>
    <article class="result-item" style="background: transparent; padding: 0;">
        <h1 class="result-title" style="font-size: 2.5rem; margin-bottom: 24px; line-height: 1.2;">
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <div class="result-snippet" style="font-size: 1.1rem; line-height: 1.8; color: var(--text);">
            <?= nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) ?>
        </div>
        
        <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--border);">
             <a href="index.html" class="btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Home
             </a>
        </div>
    </article>
</main>

<footer>
    <p>&copy; 2026 — ITU/CSU07315. All rights reserved.</p>
</footer>

</body>
</html>
