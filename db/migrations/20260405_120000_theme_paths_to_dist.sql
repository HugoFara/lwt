-- Migrate theme directory paths from assets/themes/ to dist/themes/
-- This accompanies the build output move from assets/ to dist/

UPDATE settings
SET StValue = REPLACE(StValue, 'assets/themes/', 'dist/themes/')
WHERE StKey = 'set-theme-dir'
  AND StValue LIKE 'assets/themes/%';
