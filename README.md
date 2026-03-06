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

## Hosting
1. Copy project files to your web root (for Apache this is commonly `public_html` or `/var/www/html`).
2. Ensure PHP 8+ has `pdo` and `pdo_pgsql` enabled.
3. Set database environment variables in your web server/app config:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
4. Point your domain/subdomain document root to this project folder.
5. Create the `search_items` table (SQL above), then run `insert_data.php` once to seed data.
6. Verify:
   - Home: `/index.html`
   - Results: `/results.php?q=test`
   - Article: `/article.php?slug=example.com`

### Apache virtual host example
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/srch_eng_proj

    <Directory /var/www/html/srch_eng_proj>
        AllowOverride All
        Require all granted
    </Directory>

    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_PORT 5432
    SetEnv DB_NAME search_engine
    SetEnv DB_USER postgres
    SetEnv DB_PASS your-strong-password
</VirtualHost>
```

## Free Hosting (Render + Neon)
This is a no-cost deployment path:
- Render free web service for the PHP app.
- Neon free PostgreSQL for the database.

### 1. Push this repo to GitHub
Render deploys easiest from a GitHub repo.

### 2. Create a free Neon database
In Neon dashboard, create a project and copy:
- Host
- Database name
- User
- Password
- Port (usually `5432`)

Run the schema SQL in Neon SQL editor:
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
```

### 3. Create a Render web service
1. New Web Service.
2. Connect your GitHub repo.
3. Render detects `render.yaml` + `Dockerfile`.
4. Use Free plan.

### 4. Set Render environment variables
Set these in Render service settings:
- `DB_HOST` = Neon host
- `DB_PORT` = `5432`
- `DB_NAME` = Neon database name
- `DB_USER` = Neon user
- `DB_PASS` = Neon password
- `DB_SSLMODE` = `require`

### 5. Seed sample data once
After deploy succeeds, open:
- `https://your-render-url/insert_data.php`

Then use:
- `https://your-render-url/index.html`
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
