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

//This function answers a question “What part of the text should I show in search results?”

function buildSnippet($text, $keywords, $maxLen = 200) {

    // If $text is null, turn it into an empty string
    $text = $text ?? '';

    // If there is no text at all, return nothing
    if ($text === '') {
        return '';
    }

    // If there are NO keywords to search for,
    // just cut the text from the beginning to $maxLen characters
    if (!$keywords) {
        return mb_strimwidth($text, 0, $maxLen, '...');
    }

    // This will store the position of the FIRST keyword found in the text
    $firstPos = null;

    // Loop through each keyword
    foreach ($keywords as $word) {

        // Skip empty keywords (safety check)
        if ($word === '') {
            continue;
        }

        // Find the position of the keyword in the text (case-insensitive)
        $pos = mb_stripos($text, $word);

        // If the keyword exists in the text
        // AND this position is earlier than any previously found keyword
        if ($pos !== false && ($firstPos === null || $pos < $firstPos)) {
            $firstPos = $pos;
        }
    }

    // If NONE of the keywords were found in the text,
    // fall back to a normal beginning snippet
    if ($firstPos === null) {
        return mb_strimwidth($text, 0, $maxLen, '...');
    }

    // Calculate where the snippet should start
    // We move backwards a bit so the keyword is not at the edge, but we also ensure we don't start before the beginning of the text
    $windowStart = max(0, $firstPos - (int)floor($maxLen / 3));

    // Extract a piece of text starting from $windowStart
    // with a maximum length of $maxLen characters
    $snippet = mb_substr($text, $windowStart, $maxLen);

    // If we did not start from the beginning, add "..." in front
    $prefix = $windowStart > 0 ? '...' : '';

    // If there is still more text after the snippet, add "..." at the end
    $suffix = (mb_strlen($text) > ($windowStart + $maxLen)) ? '...' : '';

    // Return the final snippet with prefix and suffix
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
$pagesPerWindow = 10;

// Calculate window start and end
$windowStart = floor(($currentPage - 1) / $pagesPerWindow) * $pagesPerWindow + 1;
$windowEnd = min($windowStart + $pagesPerWindow - 1, $totalPages);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Search results</title>
    <link rel="stylesheet" href="style.css" type="text/css"/>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
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
                value="<?= htmlspecialchars($searchTerm) ?>" 
                placeholder="Search..."
                autocomplete="off"
                autofocus
            >
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</header>

<main>
    <div class="stats">
        <?php if ($searchTerm): ?>
            About <?= number_format($totalResults) ?> results for "<strong><?= htmlspecialchars($searchTerm) ?></strong>" 
            (<?= round($timeTaken, 2) ?> seconds)
        <?php else: ?>
            Enter a search term above
        <?php endif; ?>
    </div>

    <?php if ($results): ?>
        <?php foreach ($results as $row): ?>
            <div class="result-item">
                <div class="result-header">
                    <img src="https://www.google.com/s2/favicons?domain=<?= htmlspecialchars($row['page_domain']) ?>&sz=32" 
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
    <?php elseif ($searchTerm): ?>
        <p>No results found for "<?= htmlspecialchars($searchTerm) ?>"</p>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?q=<?= urlencode($searchTerm) ?>&page=<?= $currentPage - 1 ?>" class="page-link">‹ Prev</a>
        <?php endif; ?>

        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <span class="page-link current"><?= $i ?></span>
            <?php else: ?>
                <a href="?q=<?= urlencode($searchTerm) ?>&page=<?= $i ?>" class="page-link"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?q=<?= urlencode($searchTerm) ?>&page=<?= $currentPage + 1 ?>" class="page-link">Next ›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
