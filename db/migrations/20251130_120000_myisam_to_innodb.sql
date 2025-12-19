-- Migration: Convert all MyISAM tables to InnoDB
--
-- This migration converts all permanent tables from MyISAM to InnoDB engine.
-- Benefits of InnoDB:
-- - ACID transactions support
-- - Row-level locking (better concurrency)
-- - Foreign key constraints (to be added in future migration)
-- - Crash recovery
--
-- Note: MEMORY engine tables (temptextitems, tempwords) are intentionally
-- left unchanged as they are temporary tables that benefit from MEMORY engine.

-- Convert each table to InnoDB
ALTER TABLE archivedtexts ENGINE=InnoDB;
ALTER TABLE archtexttags ENGINE=InnoDB;
ALTER TABLE feedlinks ENGINE=InnoDB;
ALTER TABLE languages ENGINE=InnoDB;
ALTER TABLE newsfeeds ENGINE=InnoDB;
ALTER TABLE sentences ENGINE=InnoDB;
ALTER TABLE settings ENGINE=InnoDB;
ALTER TABLE tags ENGINE=InnoDB;
ALTER TABLE tags2 ENGINE=InnoDB;
ALTER TABLE textitems2 ENGINE=InnoDB;
ALTER TABLE texts ENGINE=InnoDB;
ALTER TABLE texttags ENGINE=InnoDB;
ALTER TABLE words ENGINE=InnoDB;
ALTER TABLE wordtags ENGINE=InnoDB;
