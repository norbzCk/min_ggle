<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'search_logic.php';

$query = $_GET['q'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20; // results per page

// Perform the search
$data = searchItems($pdo, $query, $page, $limit);

$results = $data['results'];
$totalResults = $data['total'];
$timeTaken = $data['time'];
$searchTerm = $query;

// Split query into keywords for highlighting
$keywords = array_values(array_filter(preg_split('/\s+/', trim($searchTerm))));

function highlightText($text, $keywords) {
    $safeText = htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    if (!$keywords) {
        return $safeText;
    }

    foreach ($keywords as $word) {
        if ($word === '') {
            continue;
        }
        $pattern = "/(" . preg_quote($word, '/') . ")/i";
        $safeText = preg_replace($pattern, '<span class="highlight">$1</span>', $safeText);
    }

    return $safeText;
}


function normalizeUrl($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    
    if (preg_match('~^(?:https?://|/|[a-zA-Z0-9_-]+\\.php\\b)~', $url)) {
        return $url;
    }
    $url = 'https://' . $url;
    return $url;
}

function buildSnippet($text, $keywords, $maxLen = 200) {
    $text = $text ?? '';
    if ($text === '') {
        return '';
    }
    if (!$keywords) {
        return mb_strimwidth($text, 0, $maxLen, '...');
    }

    $firstPos = null;
    foreach ($keywords as $word) {
        if ($word === '') continue;
        $pos = mb_stripos($text, $word);
        if ($pos !== false && ($firstPos === null || $pos < $firstPos)) {
            $firstPos = $pos;
        }
    }

    if ($firstPos === null) {
        return mb_strimwidth($text, 0, $maxLen, '...');
    }

    $windowStart = max(0, $firstPos - (int)floor($maxLen / 3));
    $snippet = mb_substr($text, $windowStart, $maxLen);
    $prefix = $windowStart > 0 ? '...' : '';
    $suffix = (mb_strlen($text) > ($windowStart + $maxLen)) ? '...' : '';

    return $prefix . $snippet . $suffix;
}

// Truncate description and highlight keywords in both title and description
$results = array_map(function($row) use ($keywords) {
    $urlFull = normalizeUrl($row['page_url'] ?? '');
    $row['page_url_full'] = $urlFull;
    $row['page_url_display'] = preg_replace('~^https?://~i', '', $urlFull);
    $row['page_domain'] = parse_url($urlFull, PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $description = buildSnippet($row['description'] ?? '', $keywords, 200);
    $row['description'] = highlightText($description, $keywords);
    $row['title'] = highlightText($row['title'] ?? '', $keywords);

    return $row;
}, $results);

// Pagination logic 
$totalPages = ceil($totalResults / $limit);
$currentPage = max(1, min($page, $totalPages));
$pagesPerWindow = 5;

// Calculate window start and end
$windowStart = max(1, $currentPage - floor($pagesPerWindow / 2));
$windowEnd = min($totalPages, $windowStart + $pagesPerWindow - 1);
if ($windowEnd - $windowStart + 1 < $pagesPerWindow) {
    $windowStart = max(1, $windowEnd - $pagesPerWindow + 1);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($searchTerm) ?> - Search Results</title>
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
                    value="<?= htmlspecialchars($searchTerm) ?>" 
                    placeholder="Search..."
                    autocomplete="off"
                    autofocus
                    required
                >
            </div>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</header>

<main>
    <?php if ($searchTerm): ?>
        <div class="stats">
            About <?= number_format($totalResults) ?> results (<?= round($timeTaken, 3) ?> seconds)
        </div>

        <?php if ($results): ?>
            <?php foreach ($results as $row): ?>
                <div class="result-item">
                    <div class="result-header">
                        <img src="https://www.google.com/s2/favicons?domain=<?= htmlspecialchars($row['page_domain']) ?>&sz=64" 
                             class="favicon" alt="" loading="lazy">
                        <a href="<?= htmlspecialchars($row['page_url_full']) ?>" class="result-url" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($row['page_url_display']) ?></a>
                    </div>
                    <h3 class="result-title">
                        <a href="<?= htmlspecialchars($row['page_url_full']) ?>" target="_blank" rel="noopener noreferrer"><?= $row['title'] ?></a>
                    </h3>
                    <div class="result-snippet">
                        <?= $row['description'] ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?q=<?= urlencode($searchTerm) ?>&page=<?= $currentPage - 1 ?>" class="page-link" aria-label="Previous page">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </a>
                <?php endif; ?>

                <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="page-link current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?q=<?= urlencode($searchTerm) ?>&page=<?= $i ?>" class="page-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?q=<?= urlencode($searchTerm) ?>&page=<?= $currentPage + 1 ?>" class="page-link" aria-label="Next page">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <h2 style="margin-bottom: 10px;">No results found</h2>
                <p style="color: var(--text-muted);">Try different keywords or check for spelling errors.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px;">
             <p style="color: var(--text-muted);">Enter a search term to get started.</p>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2026 — ITU/CSU07315. All rights reserved.</p>
</footer>

</body>
</html>
