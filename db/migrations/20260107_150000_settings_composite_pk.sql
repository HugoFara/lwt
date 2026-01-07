-- Migration: Add composite primary key to settings table for multi-user support
-- Issue #29: Settings table should use (StKey, StUsID) as PK to prevent conflicts

-- First, ensure StUsID has a default value (0 for system settings)
UPDATE settings SET StUsID = 0 WHERE StUsID IS NULL;

-- Modify StUsID to be NOT NULL with default 0
ALTER TABLE settings MODIFY StUsID int(10) unsigned NOT NULL DEFAULT 0;

-- Drop the existing single-column primary key
ALTER TABLE settings DROP PRIMARY KEY;

-- Add composite primary key on (StKey, StUsID)
ALTER TABLE settings ADD PRIMARY KEY (StKey, StUsID);

-- Drop the now-redundant StUsID index (covered by composite PK suffix)
ALTER TABLE settings DROP KEY StUsID;
