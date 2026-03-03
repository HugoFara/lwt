# Upgrading LWT

This guide covers how to upgrade Learning with Texts to a newer version safely.

## Upgrading to v3.0

LWT v3.0 is a major architectural rebuild. While the automatic migration system handles most database changes, there are important breaking changes you should be aware of.

### What's New in v3.0

- **Front controller architecture** — all requests route through `index.php` instead of individual PHP files
- **InnoDB database engine** — all tables converted from MyISAM for better reliability and foreign key support
- **Modular codebase** — reorganized into `src/Modules/` with proper separation of concerns
- **Modern frontend** — jQuery replaced with Alpine.js; Bulma CSS replaces custom styles; Vite build system
- **Multi-user support** — optional user isolation via `MULTI_USER_ENABLED` env var
- **OAuth login** — Google, Microsoft, and WordPress authentication options
- **Full UTF-8mb4 support** — proper emoji and extended Unicode handling
- **Foreign key constraints** — referential integrity enforced at the database level

### Before You Upgrade

::: warning Backup First
v3.0 includes irreversible database migrations. Always create a full backup before upgrading.
:::

**Checklist:**

1. **Verify PHP version** — v3.0 requires **PHP 8.1 or higher**
2. **Back up your database** — use LWT's built-in backup (Settings → Backup/Restore) or `mysqldump`
3. **Back up your files** — especially `connect.inc.php` (v2.x) or `.env`, and your `media/` folder
4. **Note your current URLs** — bookmarks to specific pages will change (see [URL Changes](#url-changes) below)

### Configuration Migration

v3.0 replaces `connect.inc.php` with a `.env` file.

**If upgrading from v2.x:**

1. Copy `.env.example` to `.env`
2. Transfer your database credentials from `connect.inc.php`:

| `connect.inc.php` | `.env` |
|---|---|
| `$server = "localhost"` | `DB_HOST=localhost` |
| `$userid = "root"` | `DB_USER=root` |
| `$passwd = ""` | `DB_PASSWORD=` |
| `$dbname = "learning-with-texts"` | `DB_NAME=learning-with-texts` |
| `$socket = ""` | `DB_SOCKET=` |

3. Delete `connect.inc.php` — it is no longer used

**New configuration options** (all optional):

- `APP_BASE_PATH` — set if LWT is in a subdirectory (e.g., `/lwt`)
- `MULTI_USER_ENABLED` — enable multi-user data isolation
- `YT_API_KEY` — YouTube import support
- `MAIL_*` — email settings for password reset
- `GOOGLE_CLIENT_ID` / `MICROSOFT_CLIENT_ID` — OAuth login
- `CSP_MEDIA_SOURCES` — control external audio/video sources

See `.env.example` for full documentation of all options.

### Breaking Changes

#### URL Changes

All URLs have changed due to the front controller architecture. The 60+ individual PHP files in the root directory (e.g., `edit_texts.php`, `do_test.php`) have been removed.

| Old URL (v2.x) | New URL (v3.0) |
|---|---|
| `edit_texts.php` | `/text/edit` |
| `do_text.php?text=5` | `/text/5/read` |
| `edit_words.php?wid=10` | `/words/10/edit` |
| `do_test.php?text=5` | `/review/text/5` |
| `set_text_mode.php` | `/settings` |

Legacy query-parameter URLs (e.g., `/text/read?text=5`) are still supported for backward compatibility alongside the new RESTful format.

**Bookmarks:** Your old bookmarks will need updating. If you access LWT through a web server that handles URL rewriting, some old URLs may redirect automatically.

#### Database Engine Change (MyISAM → InnoDB)

All permanent tables are automatically converted from MyISAM to InnoDB. This is handled by the migration system and requires no manual action. Benefits include:

- ACID transactions and crash recovery
- Row-level locking (better concurrent access)
- Foreign key constraint enforcement

::: tip
The conversion is automatic but may take a few minutes on large databases. Do not interrupt LWT during the first startup after upgrading.
:::

#### Table Renames

Several tables have been renamed for clarity. The migration handles this automatically, but if you have **custom SQL scripts or external tools** that query the database directly, update them:

| Old Name | New Name |
|---|---|
| `textitems2` | `word_occurrences` |
| `tags2` | `text_tags` |
| `texttags` | `text_tag_map` |
| `archtexttags` | `archived_text_tag_map` |
| `wordtags` | `word_tag_map` |
| `archivedtexts` | `archived_texts` |
| `newsfeeds` | `news_feeds` |
| `feedlinks` | `feed_links` |

Additionally, `archived_texts` is merged into the `texts` table using a `TxArchivedAt` column to distinguish archived texts.

#### Unknown Words: NULL instead of 0

The `Ti2WoID` column (now in the `word_occurrences` table) uses `NULL` instead of `0` for unknown/new words. This enables proper foreign key constraints.

If you have custom SQL queries:

```sql
-- Old (v2.x)
SELECT * FROM textitems2 WHERE Ti2WoID = 0;

-- New (v3.0)
SELECT * FROM word_occurrences WHERE Ti2WoID IS NULL;
```

#### jQuery Removed

jQuery has been replaced with Alpine.js. If you have custom JavaScript that depends on jQuery (e.g., `$()` selectors, `$.ajax()` calls), it will no longer work. The global `$` and `jQuery` objects are no longer available.

#### Frame-Based Settings Removed

The old frame-based layout for settings pages has been removed in favor of standard page navigation.

### What Happens Automatically

When you start LWT v3.0 for the first time, the migration system automatically:

1. Converts all tables from MyISAM to InnoDB
2. Adds foreign key constraints between related tables
3. Converts `Ti2WoID = 0` values to `NULL`
4. Renames tables to their new names
5. Merges archived texts into the texts table
6. Converts all text columns from `utf8` to `utf8mb4`
7. Adds multi-user columns (inactive unless `MULTI_USER_ENABLED=true`)
8. Adds authentication tables (users, password reset tokens, OAuth links)

All 40+ migrations run in sequence. On a typical database this completes in under a minute.

### Docker Upgrade Path

```bash
# 1. Back up your data
docker compose exec db mysqldump -u root -p learning-with-texts > backup.sql

# 2. Stop the current containers
docker compose down

# 3. Pull the new version
docker compose pull
# Or rebuild from source:
docker compose build --no-cache

# 4. Start the updated containers
docker compose up -d

# 5. Migrations run automatically on first request
# Visit http://localhost:8010/lwt/ and wait for migrations to complete
```

Your data persists in Docker volumes across upgrades.

### Troubleshooting v3 Upgrade Issues

#### Migration takes too long or times out

Large databases (10,000+ terms) may take several minutes for the InnoDB conversion and foreign key additions. Increase your PHP `max_execution_time` or run migrations from the command line:

```bash
php index.php
```

#### "Table doesn't exist" errors

If you see errors referencing old table names (e.g., `textitems2`), your migrations may not have completed. Check the `_migrations` table in your database to see which migrations have been applied, then restart LWT to retry.

#### Custom SQL scripts break

Update any external scripts to use the new table names (see [Table Renames](#table-renames) above) and `NULL` checks instead of `= 0` for unknown words.

#### JavaScript errors in browser console

Clear your browser cache completely (`Ctrl+Shift+R` or `Cmd+Shift+R`). The old jQuery-based scripts are gone and the new Alpine.js scripts must be loaded fresh.

If running from source, rebuild the frontend assets:

```bash
npm install
npm run build:all
```

#### OAuth login buttons not appearing

OAuth providers only appear on the login page when configured:

- **Google**: Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`
- **Microsoft**: Set `MICROSOFT_CLIENT_ID` and `MICROSOFT_CLIENT_SECRET` in `.env`
- **WordPress**: Set `WORDPRESS_ENABLED=true` in `.env` (for LWT installed under a WordPress directory)

---

## How Upgrades Work

LWT uses an **automatic database migration system**. When you start LWT after updating the files, it automatically:

1. Detects your current database version
2. Runs any pending migrations to update the database schema
3. Updates the `dbversion` setting to match the current version

This means most upgrades are seamless - just replace the files and open LWT.

## Standard Upgrade Process

### Step 1: Backup Your Data

Before any upgrade, create backups:

**Database backup (recommended):**

1. Open LWT in your browser
2. Go to **Settings** (gear icon)
3. Click **Backup/Restore/Empty Database**
4. Click **Backup ENTIRE Database** to download a backup file

**File backup:**

Copy your entire LWT directory to a safe location. Important files include:

- `.env` - Your database configuration
- `media/` - Your audio files (if you created this folder)

### Step 2: Download the New Version

Get the latest release:

- **Stable releases**: [GitHub Releases](https://github.com/HugoFara/lwt/releases)
- **Latest development**: [Download main branch](https://github.com/HugoFara/lwt/archive/refs/heads/main.zip)

### Step 3: Replace Files

1. Extract the new version
2. Copy your `.env` file from the backup into the new LWT directory
3. Copy your `media/` folder (if it exists) into the new directory
4. Replace the old LWT directory with the new one

### Step 4: Clear Browser Cache

Clear your browser cache to ensure you're loading the new JavaScript and CSS files. You can also try:

- Hard refresh: `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)
- Or open LWT in a private/incognito window

### Step 5: Open LWT

Open LWT in your browser as usual. The database will be automatically updated if needed.

## Docker Upgrades

If you're using Docker:

```bash
# Stop the current container
docker compose down

# Pull the latest image (if using pre-built images)
docker compose pull

# Or rebuild from source
docker compose build --no-cache

# Start the updated container
docker compose up -d
```

Your data persists in Docker volumes, so it will be preserved across upgrades.

## Upgrading from Very Old Versions

If you're upgrading from a version older than 2.7.0 (released before 2022), special considerations apply:

### Database Schema Changes

Older versions used a different database structure. The migration system will attempt to convert your data, but for very old versions:

1. **Export your data first**: Use LWT's export features to save your terms and texts
2. **Consider a fresh install**: Create a new database, then import your exported data
3. **Test thoroughly**: After upgrading, verify your terms and texts are intact

### PHP Version Requirements

Modern LWT requires **PHP 8.1 or higher**. If your server runs an older PHP version, you'll need to upgrade PHP first.

| LWT Version | Minimum PHP |
|-------------|-------------|
| 3.0+        | PHP 8.1     |
| 2.9.x       | PHP 8.0     |
| 2.0-2.8     | PHP 7.4     |
| < 2.0       | PHP 5.6     |

## Troubleshooting

### "Database needs to be reinstalled" Error

This usually means the migration couldn't complete. Try:

1. Restore your database backup
2. Check that your `.env` credentials are correct
3. Ensure your MySQL/MariaDB user has ALTER TABLE permissions

### Features Not Working After Upgrade

1. **Clear browser cache** completely
2. **Check browser console** (F12) for JavaScript errors
3. **Rebuild assets** if you're running from source:

   ```bash
   npm install
   npm run build:all
   ```

### Tests Not Auto-Advancing

If tests don't advance to the next word after upgrading:

1. Clear your browser cache
2. Check if JavaScript is loading correctly (F12 > Console)
3. Try a different browser to isolate the issue

### Database Connection Errors

Verify your `.env` file has the correct settings:

```bash
DB_HOST=localhost      # Or your database host
DB_USER=root           # Your database username
DB_PASSWORD=           # Your database password
DB_NAME=learning-with-texts
```

## Checking Your Version

To see your current LWT version:

1. Look at the footer of any LWT page
2. Or check the `dbversion` value in your database's `settings` table

## Downgrading

Downgrading is **not officially supported** because newer database migrations cannot be reversed automatically. If you need to downgrade:

1. Restore your database backup from before the upgrade
2. Use the older LWT files

This is why backups before upgrading are essential.

## Getting Help

If you encounter issues:

- [GitHub Issues](https://github.com/HugoFara/lwt/issues) - Report bugs or search existing issues
