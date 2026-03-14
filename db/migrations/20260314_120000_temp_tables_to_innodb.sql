-- Migration: Convert temp tables from MEMORY to InnoDB engine
-- The MEMORY engine has a hard size limit (max_heap_table_size, default 16MB)
-- which causes "table is full" errors when parsing large texts (e.g. full
-- books from Project Gutenberg). InnoDB has no practical size limit.

ALTER TABLE temp_word_occurrences ENGINE=InnoDB;
ALTER TABLE temp_words ENGINE=InnoDB;
