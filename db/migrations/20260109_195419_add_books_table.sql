-- Migration: Add books table and book-related columns to texts table
-- This enables EPUB import and multi-chapter book support

-- Create books table
CREATE TABLE IF NOT EXISTS books (
    BkID SMALLINT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    BkUsID INT(10) UNSIGNED NULL,
    BkLgID TINYINT(3) UNSIGNED NOT NULL,
    BkTitle VARCHAR(200) NOT NULL,
    BkAuthor VARCHAR(200) NULL,
    BkDescription TEXT NULL,
    BkCoverPath VARCHAR(500) NULL COMMENT 'Path to cover image file',
    BkSourceType ENUM('text', 'epub', 'pdf') NOT NULL DEFAULT 'text',
    BkSourceHash VARCHAR(64) NULL COMMENT 'SHA-256 hash for duplicate detection',
    BkTotalChapters SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    BkCurrentChapter SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    BkCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    BkUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_books_language (BkLgID),
    INDEX idx_books_user (BkUsID),
    INDEX idx_books_source_hash (BkSourceHash),

    CONSTRAINT fk_books_language FOREIGN KEY (BkLgID)
        REFERENCES languages(LgID) ON DELETE RESTRICT,
    CONSTRAINT fk_books_user FOREIGN KEY (BkUsID)
        REFERENCES users(UsID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add book-related columns to texts table
ALTER TABLE texts
    ADD COLUMN IF NOT EXISTS TxBkID SMALLINT(5) UNSIGNED NULL AFTER TxUsID,
    ADD COLUMN IF NOT EXISTS TxChapterNum SMALLINT(5) UNSIGNED NULL AFTER TxBkID,
    ADD COLUMN IF NOT EXISTS TxChapterTitle VARCHAR(200) NULL AFTER TxChapterNum;

-- Add index for book-chapter queries (if not exists)
-- MariaDB 10.5+ supports IF NOT EXISTS for indexes
ALTER TABLE texts
    ADD INDEX IF NOT EXISTS idx_texts_book (TxBkID, TxChapterNum);

-- Add foreign key constraint for book reference
-- First check if constraint exists to make migration idempotent
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'texts'
    AND CONSTRAINT_NAME = 'fk_texts_book'
);

-- Only add FK if it doesn't exist (using prepared statement)
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE texts ADD CONSTRAINT fk_texts_book FOREIGN KEY (TxBkID) REFERENCES books(BkID) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
