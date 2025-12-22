# Upgrading LWT

This guide covers how to upgrade Learning with Texts to a newer version safely.

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

### Configuration File Changes

- Versions before 3.0 used `connect.inc.php` instead of `.env`
- If upgrading from these versions, create a new `.env` file based on `.env.example`
- Copy your database credentials from `connect.inc.php` to `.env`

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
