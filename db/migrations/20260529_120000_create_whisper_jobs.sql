-- Migration: per-user Whisper job ownership map
--
-- WhisperApiHandler proxies status/result/cancel calls to the NLP
-- microservice using a client-supplied job_id. Before this table,
-- nothing tied the job_id to the user who started it, so a leaked or
-- guessed UUID let any logged-in user read or cancel another user's
-- transcription. Persist {job_id -> user_id} on /transcribe and gate
-- the read/cancel endpoints on the binding.
--
-- The session-token approach wouldn't work: ApiV1::handle calls
-- session_write_close early in the request, so handlers cannot persist
-- session changes for later calls. A small InnoDB table is simplest.
--
-- Idempotent via IF NOT EXISTS (MariaDB extension).

CREATE TABLE IF NOT EXISTS whisper_jobs (
    WjJobID varchar(64) NOT NULL,
    WjUsID int(10) unsigned DEFAULT NULL,
    WjCreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (WjJobID),
    KEY idx_whisper_jobs_user (WjUsID),
    CONSTRAINT fk_whisper_jobs_user FOREIGN KEY (WjUsID)
        REFERENCES users(UsID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
