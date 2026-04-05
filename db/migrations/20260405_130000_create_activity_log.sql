-- Migration: Create activity_log table for tracking daily learning activity.
-- Each row represents one user's activity for one calendar date.
-- Counters are incremented atomically via INSERT ... ON DUPLICATE KEY UPDATE.

CREATE TABLE IF NOT EXISTS activity_log (
    AlID             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    AlUsID           INT UNSIGNED DEFAULT NULL COMMENT 'User ID (NULL in single-user mode)',
    AlDate           DATE NOT NULL COMMENT 'Activity date',
    AlTermsCreated   INT UNSIGNED NOT NULL DEFAULT 0,
    AlTermsReviewed  INT UNSIGNED NOT NULL DEFAULT 0,
    AlTextsRead      INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (AlID),
    UNIQUE KEY uq_activity_user_date (AlUsID, AlDate),
    KEY idx_activity_date (AlDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
