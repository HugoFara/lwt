-- Migration: Drop the persistent parse/import scratch tables.
--
-- temp_word_occurrences, temp_words and tempexprs used to be persistent,
-- globally-shared InnoDB tables that every parse/import TRUNCATEd and refilled.
-- That caused two problems:
--   * "Tablespace is missing for a table" (InnoDB error 194) crashes on
--     TRUNCATE when the table's .ibd file went missing (notably on Windows) —
--     every text import then failed. See issue #247.
--   * Concurrent parses/imports corrupted each other's rows (no isolation).
--
-- They are now created per database connection as CREATE TEMPORARY TABLE at
-- runtime (see src/Shared/Infrastructure/Database/ScratchTables.php), so the
-- persistent versions are no longer needed. Dropping them here also repairs
-- installs whose tablespace was already orphaned (the reported crash).
--
-- DROP TABLE IF EXISTS is idempotent and succeeds even when the tablespace is
-- missing, so it clears the orphaned dictionary entry as well.

DROP TABLE IF EXISTS temp_word_occurrences;
DROP TABLE IF EXISTS temp_words;
DROP TABLE IF EXISTS tempexprs;
