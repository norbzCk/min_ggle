<?php

function searchItems(PDO $pdo, string $query, int $page = 1, int $limit = 20): array{
    $startTime = microtime(true);

    $query = trim($query);
    if ($query === '') {
        return [
            'results' => [],
            'total' => 0,
            'time' => 0
        ];
    }

    $keywords = array_filter(explode(' ', $query));

    $conditions = [];
    $params = [];
    $scoreParts = [];

    foreach ($keywords as $i => $word) {
        $conditions[] = "(title ILIKE :w$i OR description ILIKE :w$i)";

        $params[":w$i"] = "%$word%";


        // Heavier boost for title matches so they rank higher than description-only matches
        $scoreParts[] = "
            (CASE WHEN title ILIKE :w$i THEN 5 ELSE 0 END +
             CASE WHEN description ILIKE :w$i THEN 1 ELSE 0 END)
        ";
    }

    $whereSQL = implode(" AND ", $conditions);
    $scoreSQL = implode(" + ", $scoreParts);
    $allTitleSQL = implode(" AND ", array_map(function($i) {
        return "title ILIKE :w$i";
    }, array_keys($keywords)));

    $offset = ($page - 1) * $limit;

    /* MAIN QUERY */
    $sql = "
        SELECT title, description, page_url, page_fav_icon_path,
               ($scoreSQL) AS relevance,
               (CASE WHEN ($allTitleSQL) THEN 1 ELSE 0 END) AS all_title_match
        FROM search_items
        WHERE $whereSQL
        ORDER BY all_title_match DESC, relevance DESC, created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* COUNT QUERY */
    $countSql = "SELECT COUNT(*) FROM search_items WHERE $whereSQL";
    $countStmt = $pdo->prepare($countSql);

    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }

    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $timeTaken = round(microtime(true) - $startTime, 4);

    return [
        'results' => $results,
        'total' => $total,
        'time' => $timeTaken
    ];
}
