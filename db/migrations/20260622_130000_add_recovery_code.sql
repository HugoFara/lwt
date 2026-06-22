-- One-time recovery code for accounts registered without an email.
--
-- Email is optional at registration, so email-less accounts have no email-based
-- password reset. They instead get a one-time recovery code at sign-up (shown
-- once, stored hashed here) that lets them reset their password later. The code
-- is rotated on each successful use.
ALTER TABLE users
    ADD COLUMN UsRecoveryCodeHash VARCHAR(255) DEFAULT NULL AFTER UsPasswordResetTokenExpires;
