# Search Engine Project

A simple PHP + PostgreSQL search engine demo. It includes a home page, search results with basic ranking, and a small article view. Sample data can be inserted into the database for local testing.

## Features
- Keyword search with basic relevance ranking (title weighted higher than description).
- Highlighted search terms and short result snippets.
- Pagination for results.
- Simple article view by URL slug.

## Requirements
- PHP 8+ with PDO and `pdo_pgsql` enabled.
- PostgreSQL 12+.
- A local PHP server (e.g. XAMPP/LAMPP).

## Setup
1. Create a PostgreSQL database (default expected name is `search_engine`).
2. Create the `search_items` table:

```sql
CREATE TABLE search_items (
  id SERIAL PRIMARY KEY,
  title TEXT NOT NULL,
  description TEXT,
  page_name TEXT,
  page_fav_icon_path TEXT,
  page_url TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_search_title_desc
ON search_items USING GIN (
    to_tsvector('english', title || ''|| description)
);
```

3. Update database credentials in `db.php` if needed.
4. Seed sample data by visiting `insert_data.php` in your browser (it truncates and inserts sample rows).
5. Open `index.html` to use the search UI.

## Usage
- Search: `index.html` -> results are shown in `results.php`.
- Article view: `article.php?slug=example.com` (matches a URL fragment in `page_url`).

## Notes
- Search is case-insensitive and uses `ILIKE` for matching.
- Ranking boosts title matches over description-only matches.
- For larger datasets, consider enabling `pg_trgm` and adding GIN indexes on `title` and `description`.

## File Overview
- `index.html` - Home page with search form.
- `results.php` - Search results page and pagination.
- `search_logic.php` - Query building and ranking.
- `db.php` - Database connection configuration.
- `insert_data.php` - Seed script for sample data.
- `sample-data.php` - Sample dataset.
- `article.php` - Simple article view.
- `style.css` - Stylesheet.

