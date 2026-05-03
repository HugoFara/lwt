-- Migration: Make per-user-scoped name uniques actually per-user
--
-- Before this migration, languages.LgName, tags.TgText, and text_tags.T2Text
-- each carried a *global* UNIQUE constraint. With multi-user mode enabled
-- this meant two users could not coexist if either had already picked the
-- same name (e.g. both wanting a language called "Spanish", a term tag
-- called "easy", or a text tag called "fiction"). The second user's
-- attempt died with `Duplicate entry '...' for key '...'` and a 500.
--
-- Replace each with a composite UNIQUE on the (user_id, name) pair so
-- per-user namespacing actually works. NULL user_ids are treated as
-- distinct by MariaDB, so legacy NULL-owner rows continue to coexist.
--
-- Idempotent via IF EXISTS / IF NOT EXISTS (MariaDB extensions).

-- languages: LgName -> (LgUsID, LgName)
ALTER TABLE languages DROP INDEX IF EXISTS LgName;
ALTER TABLE languages ADD UNIQUE KEY IF NOT EXISTS LgName_per_user (LgUsID, LgName);

-- tags (term tags): TgText -> (TgUsID, TgText)
ALTER TABLE tags DROP INDEX IF EXISTS TgText;
ALTER TABLE tags ADD UNIQUE KEY IF NOT EXISTS TgText_per_user (TgUsID, TgText);

-- text_tags (text tags): T2Text -> (T2UsID, T2Text)
ALTER TABLE text_tags DROP INDEX IF EXISTS T2Text;
ALTER TABLE text_tags ADD UNIQUE KEY IF NOT EXISTS T2Text_per_user (T2UsID, T2Text);
