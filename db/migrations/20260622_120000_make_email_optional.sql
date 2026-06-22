-- Make user email optional.
--
-- The username (UsUsername) is already the unique identity for an account, so
-- email is no longer required at registration. It is kept only as an optional
-- recovery/verification channel. Allow NULL so email-less accounts can exist;
-- the existing UNIQUE KEY on UsEmail still prevents duplicate *real* emails
-- (MySQL/MariaDB permit multiple NULLs under a UNIQUE index, so email-less
-- accounts do not collide).
ALTER TABLE users MODIFY COLUMN UsEmail varchar(255) DEFAULT NULL;
