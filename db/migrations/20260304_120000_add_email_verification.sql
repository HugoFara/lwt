-- Add email verification columns to users table
ALTER TABLE users
    ADD COLUMN UsEmailVerifiedAt DATETIME DEFAULT NULL AFTER UsEmail,
    ADD COLUMN UsEmailVerificationToken VARCHAR(255) DEFAULT NULL AFTER UsEmailVerifiedAt,
    ADD COLUMN UsEmailVerificationTokenExpires DATETIME DEFAULT NULL AFTER UsEmailVerificationToken;

-- Back-fill existing users as verified (they registered before verification was required)
UPDATE users SET UsEmailVerifiedAt = UsCreated WHERE UsEmailVerifiedAt IS NULL;
