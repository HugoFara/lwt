-- Add FSRS scheduling state and review history (issue #238, phase 2a).
--
-- Phase 2a is deliberately ADDITIVE: nothing here replaces the legacy Leitner
-- scoring. `words.WoTodayScore` / `WoTomorrowScore` / `WoRandom` and the
-- SCORE_FORMULA_* SQL keep being written exactly as before, and `WoStatus`
-- remains the manual, authoritative source for reading-view colours. These two
-- tables accumulate alongside them so the two schedulers can be compared on
-- real data before anything is retired (that is phase 2b).
--
-- Neither table carries a user column: both are keyed by WoID, and `words`
-- already carries WoUsID with an FK to users. Ownership is therefore reached by
-- joining `words`, which is what the repository does — see
-- src/Shared/Infrastructure/Database/UserScopedQuery.php for the tables that do
-- get an automatic scope column.

-- Per-term FSRS memory state. One row per reviewed term; rows are created
-- lazily on a term's first graded review (seeded from WoStatus/WoStatusChanged)
-- rather than backfilled, so installs with large vocabularies pay nothing here.
-- NB: the WoID-referencing columns below are `int(10) unsigned`, NOT the
-- `mediumint(8) unsigned` that db/schema/baseline.sql still declares. The
-- inter-table FK migration (20251221_120000) widens words.WoID to INT UNSIGNED,
-- and MySQL rejects a foreign key whose column type differs from its parent
-- with errno 150 ("Foreign key constraint is incorrectly formed"). Match the
-- post-migration type, not the baseline's.
CREATE TABLE IF NOT EXISTS term_schedule (
    TsWoID int(10) unsigned NOT NULL,
    -- Stability: days for retrievability to decay to 0.9. FSRS clamps to >= 0.001.
    TsStability double NOT NULL,
    -- Difficulty: FSRS clamps to [1, 10].
    TsDifficulty double NOT NULL,
    TsDue datetime NOT NULL,
    TsLastReview datetime DEFAULT NULL,
    TsReps int(10) unsigned NOT NULL DEFAULT 0,
    TsLapses int(10) unsigned NOT NULL DEFAULT 0,
    -- 0 = new, 1 = learning, 2 = review, 3 = relearning (matches Anki's card states,
    -- so the .apkg exporter can populate cards.type/queue directly later).
    TsState tinyint(3) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (TsWoID),
    KEY TsDue (TsDue),
    KEY TsState (TsState),
    CONSTRAINT fk_term_schedule_word FOREIGN KEY (TsWoID)
        REFERENCES words (WoID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Append-only review history. FSRS can schedule from current state alone, so
-- this is not read by the scheduler; it exists because per-user parameter
-- optimisation (Anki's "FSRS optimizer") needs history, and because it maps
-- onto Anki's `revlog` table for .apkg round-trip (issue #228).
CREATE TABLE IF NOT EXISTS review_log (
    RlID int(10) unsigned NOT NULL AUTO_INCREMENT,
    RlWoID int(10) unsigned NOT NULL,
    -- 1 = Again, 2 = Hard, 3 = Good, 4 = Easy.
    RlGrade tinyint(3) unsigned NOT NULL,
    -- Scheduling state BEFORE this review, so a re-optimiser can replay history.
    RlState tinyint(3) unsigned NOT NULL,
    -- Memory state AFTER this review.
    RlStability double NOT NULL,
    RlDifficulty double NOT NULL,
    -- Whole days since the previous review (0 for a first or same-day review).
    RlElapsedDays int(11) NOT NULL,
    -- Interval in days the scheduler assigned as a result of this review.
    RlScheduledDays int(11) NOT NULL,
    RlReviewedAt datetime NOT NULL,
    PRIMARY KEY (RlID),
    KEY RlWoID (RlWoID, RlReviewedAt),
    KEY RlReviewedAt (RlReviewedAt),
    CONSTRAINT fk_review_log_word FOREIGN KEY (RlWoID)
        REFERENCES words (WoID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
