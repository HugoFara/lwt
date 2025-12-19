-- Fix TxAudioURI to allow NULL values
-- The migration 20240103_120316_2.10.0-fork.sql incorrectly set NOT NULL
-- This broke the ability to create texts without audio

ALTER TABLE `texts`
    MODIFY COLUMN `TxAudioURI` VARCHAR(2048) DEFAULT NULL;
