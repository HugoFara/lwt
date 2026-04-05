-- Migration: Backfill activity_log from existing words table.
--
-- Terms created: grouped by WoCreated date and user.
-- Terms reviewed: grouped by WoStatusChanged date (approximation — only the
--   last status change per term is recorded, so historical review counts are
--   underestimates).
-- Texts read: cannot be backfilled (no historical data available).

-- Step 1: Backfill terms created
INSERT INTO activity_log (AlUsID, AlDate, AlTermsCreated, AlTermsReviewed, AlTextsRead)
SELECT
    WoUsID,
    CAST(WoCreated AS DATE),
    COUNT(*),
    0,
    0
FROM words
WHERE WoCreated IS NOT NULL
GROUP BY WoUsID, CAST(WoCreated AS DATE)
ON DUPLICATE KEY UPDATE AlTermsCreated = VALUES(AlTermsCreated);

-- Step 2: Backfill reviewed terms (status changed on a different date than creation)
INSERT INTO activity_log (AlUsID, AlDate, AlTermsCreated, AlTermsReviewed, AlTextsRead)
SELECT
    WoUsID,
    CAST(WoStatusChanged AS DATE),
    0,
    COUNT(*),
    0
FROM words
WHERE WoStatusChanged IS NOT NULL
  AND CAST(WoStatusChanged AS DATE) <> CAST(WoCreated AS DATE)
GROUP BY WoUsID, CAST(WoStatusChanged AS DATE)
ON DUPLICATE KEY UPDATE AlTermsReviewed = VALUES(AlTermsReviewed);
