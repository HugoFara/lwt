<?php

/**
 * \file
 * \brief Session-scoped scratch tables for text parsing and vocabulary import.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.2.2
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Database;

/**
 * Creates the per-connection scratch tables used while parsing texts and
 * importing vocabulary.
 *
 * These tables (`temp_word_occurrences`, `temp_words`, `tempexprs`) used to be
 * persistent, globally-shared InnoDB tables that every parse/import `TRUNCATE`d
 * and refilled. That design caused two problems:
 *
 *  - **Tablespace crashes.** On file-per-table InnoDB, `TRUNCATE` physically
 *    drops and recreates the `.ibd`. When that file went missing (notably on
 *    Windows) the table was left with a missing tablespace and every subsequent
 *    import failed with InnoDB error 194 "Tablespace is missing for a table"
 *    (see issue #247).
 *  - **Concurrency corruption.** Because the tables were shared, two parses or
 *    imports running at once (multiple tabs, a feed refresh overlapping a manual
 *    import, or any two users in multi-user mode) read and truncated each
 *    other's rows, silently producing scrambled sentences and word occurrences.
 *
 * They are now created per database connection with `CREATE TEMPORARY TABLE`, so
 * each request gets its own private copy. Temporary InnoDB tables live in the
 * shared session temporary tablespace (there is no per-table `.ibd` to orphan),
 * and they are dropped automatically when the connection closes.
 *
 * Callers must `ensure*()` the table exists before use, then clear rows with
 * `DELETE FROM` — never `TRUNCATE`, which is DDL that recreates the tablespace
 * and implicitly commits any open transaction.
 *
 * @since 3.2.2
 */
final class ScratchTables
{
    /**
     * Ensure the word-occurrence staging table exists for this connection.
     *
     * Holds the tokenised word occurrences of a single text while it is being
     * parsed into `sentences` / `word_occurrences`.
     *
     * @return void
     */
    public static function ensureWordOccurrences(): void
    {
        Connection::query(
            <<<'SQL'
            CREATE TEMPORARY TABLE IF NOT EXISTS temp_word_occurrences (
                TiCount smallint(5) unsigned NOT NULL,
                TiSeID mediumint(8) unsigned NOT NULL,
                TiOrder smallint(5) unsigned NOT NULL,
                TiWordCount tinyint(3) unsigned NOT NULL,
                TiText varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    /**
     * Ensure the word-import staging table exists for this connection.
     *
     * Holds the rows of a vocabulary import file before they are merged into
     * the `words` table.
     *
     * @return void
     */
    public static function ensureWords(): void
    {
        Connection::query(
            <<<'SQL'
            CREATE TEMPORARY TABLE IF NOT EXISTS temp_words (
                WoText varchar(250) DEFAULT NULL,
                WoTextLC varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
                WoTranslation varchar(500) NOT NULL DEFAULT '*',
                WoRomanization varchar(100) DEFAULT NULL,
                WoSentence varchar(1000) DEFAULT NULL,
                WoTaglist varchar(255) DEFAULT NULL,
                PRIMARY KEY(WoTextLC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    /**
     * Ensure the multi-word-expression staging table exists for this connection.
     *
     * Populated by the multi-word detection pass while parsing a text.
     *
     * @return void
     */
    public static function ensureExpressions(): void
    {
        Connection::query(
            <<<'SQL'
            CREATE TEMPORARY TABLE IF NOT EXISTS tempexprs (
                sent mediumint unsigned,
                word varchar(250),
                lword varchar(250),
                TiOrder smallint unsigned,
                n tinyint(3) unsigned NOT NULL
            )
            SQL
        );
    }
}
