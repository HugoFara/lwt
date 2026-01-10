-- Test script for inter-table foreign key constraints
-- Run this after applying the migration to verify FK behavior
-- Usage: mysql -u root -p learning-with-texts < tests/sql/test_foreign_keys.sql

-- ============================================================================
-- Setup: Create test data
-- ============================================================================

-- Ensure we're using the correct database
-- (Adjust database name if using a different one)

SET FOREIGN_KEY_CHECKS = 1;

-- Clean up any previous test data
DELETE FROM word_occurrences WHERE Ti2TxID IN (SELECT TxID FROM texts WHERE TxTitle LIKE 'FK_TEST_%');
DELETE FROM sentences WHERE SeTxID IN (SELECT TxID FROM texts WHERE TxTitle LIKE 'FK_TEST_%');
DELETE FROM text_tag_map WHERE TtTxID IN (SELECT TxID FROM texts WHERE TxTitle LIKE 'FK_TEST_%');
DELETE FROM texts WHERE TxTitle LIKE 'FK_TEST_%';
DELETE FROM word_tag_map WHERE WtWoID IN (SELECT WoID FROM words WHERE WoText LIKE 'fktest_%');
DELETE FROM words WHERE WoText LIKE 'fktest_%';
-- Note: archivedtexts merged into texts table, cleanup already handled by texts DELETE above
DELETE FROM feed_links WHERE FlTitle LIKE 'FK_TEST_%';
DELETE FROM news_feeds WHERE NfName LIKE 'FK_TEST_%';
DELETE FROM tags WHERE TgText LIKE 'fktest_%';
DELETE FROM text_tags WHERE T2Text LIKE 'fktest_%';
DELETE FROM languages WHERE LgName LIKE 'FK_TEST_%';

SELECT '=== Starting Foreign Key Tests ===' AS status;

-- ============================================================================
-- Test 1: Verify Ti2WoID is nullable (can store NULL for unknown words)
-- ============================================================================

SELECT '--- Test 1: Ti2WoID nullable check ---' AS test;

-- Create test language
INSERT INTO languages (LgName, LgDict1URI, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters)
VALUES ('FK_TEST_Lang', 'https://test.com/###', '', '.!?', '', 'a-zA-Z');

SET @test_lang_id = LAST_INSERT_ID();

-- Create test text
INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
VALUES (@test_lang_id, 'FK_TEST_Text1', 'Test text content', '');

SET @test_text_id = LAST_INSERT_ID();

-- Create test sentence
INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
VALUES (@test_lang_id, @test_text_id, 1, 'Test sentence', 1);

SET @test_sentence_id = LAST_INSERT_ID();

-- Insert text item with NULL Ti2WoID (unknown word)
INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text, Ti2Translation)
VALUES (NULL, @test_lang_id, @test_text_id, @test_sentence_id, 1, 1, 'unknownword', '');

SELECT IF(COUNT(*) = 1, 'PASS: Ti2WoID can be NULL', 'FAIL: Ti2WoID NULL insert failed') AS result
FROM word_occurrences WHERE Ti2WoID IS NULL AND Ti2Text = 'unknownword';

-- ============================================================================
-- Test 2: FK texts -> languages (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 2: texts -> languages CASCADE ---' AS test;

-- Create another language for cascade test
INSERT INTO languages (LgName, LgDict1URI, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters)
VALUES ('FK_TEST_Lang_Cascade', 'https://test.com/###', '', '.!?', '', 'a-zA-Z');

SET @cascade_lang_id = LAST_INSERT_ID();

INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
VALUES (@cascade_lang_id, 'FK_TEST_Cascade_Text', 'Cascade test', '');

SET @cascade_text_id = LAST_INSERT_ID();

-- Verify text exists
SELECT IF(COUNT(*) = 1, 'SETUP: Text created', 'SETUP FAIL: Text not created') AS result
FROM texts WHERE TxID = @cascade_text_id;

-- Delete language - should cascade to texts
DELETE FROM languages WHERE LgID = @cascade_lang_id;

-- Verify text was deleted
SELECT IF(COUNT(*) = 0, 'PASS: Text deleted via CASCADE', 'FAIL: Text not deleted') AS result
FROM texts WHERE TxID = @cascade_text_id;

-- ============================================================================
-- Test 3: FK sentences -> texts (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 3: sentences -> texts CASCADE ---' AS test;

-- Create test text
INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
VALUES (@test_lang_id, 'FK_TEST_Sentence_Cascade', 'Test', '');

SET @sent_test_text_id = LAST_INSERT_ID();

INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
VALUES (@test_lang_id, @sent_test_text_id, 1, 'Sentence to cascade', 1);

SET @sent_test_sentence_id = LAST_INSERT_ID();

-- Verify sentence exists
SELECT IF(COUNT(*) = 1, 'SETUP: Sentence created', 'SETUP FAIL') AS result
FROM sentences WHERE SeID = @sent_test_sentence_id;

-- Delete text - should cascade to sentences
DELETE FROM texts WHERE TxID = @sent_test_text_id;

-- Verify sentence was deleted
SELECT IF(COUNT(*) = 0, 'PASS: Sentence deleted via CASCADE', 'FAIL: Sentence not deleted') AS result
FROM sentences WHERE SeID = @sent_test_sentence_id;

-- ============================================================================
-- Test 4: FK word_occurrences -> texts (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 4: word_occurrences -> texts CASCADE ---' AS test;

INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
VALUES (@test_lang_id, 'FK_TEST_TextItems_Cascade', 'Test', '');

SET @ti_test_text_id = LAST_INSERT_ID();

INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
VALUES (@test_lang_id, @ti_test_text_id, 1, 'Sentence', 1);

SET @ti_test_sentence_id = LAST_INSERT_ID();

INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text, Ti2Translation)
VALUES (NULL, @test_lang_id, @ti_test_text_id, @ti_test_sentence_id, 1, 1, 'testword', '');

-- Verify text item exists
SELECT IF(COUNT(*) = 1, 'SETUP: TextItem created', 'SETUP FAIL') AS result
FROM word_occurrences WHERE Ti2TxID = @ti_test_text_id;

-- Delete text - should cascade to word_occurrences (via sentences cascade)
DELETE FROM texts WHERE TxID = @ti_test_text_id;

-- Verify text item was deleted
SELECT IF(COUNT(*) = 0, 'PASS: TextItem deleted via CASCADE', 'FAIL: TextItem not deleted') AS result
FROM word_occurrences WHERE Ti2TxID = @ti_test_text_id;

-- ============================================================================
-- Test 5: FK word_occurrences -> words (ON DELETE SET NULL)
-- ============================================================================

SELECT '--- Test 5: word_occurrences -> words SET NULL ---' AS test;

INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
VALUES (@test_lang_id, 'FK_TEST_SetNull', 'Test', '');

SET @sn_test_text_id = LAST_INSERT_ID();

INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
VALUES (@test_lang_id, @sn_test_text_id, 1, 'Sentence', 1);

SET @sn_test_sentence_id = LAST_INSERT_ID();

-- Create a word
INSERT INTO words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoWordCount)
VALUES (@test_lang_id, 'fktest_word', 'fktest_word', 1, 'test translation', 1);

SET @sn_test_word_id = LAST_INSERT_ID();

-- Create text item linked to word
INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text, Ti2Translation)
VALUES (@sn_test_word_id, @test_lang_id, @sn_test_text_id, @sn_test_sentence_id, 1, 1, 'fktest_word', '');

-- Verify link exists
SELECT IF(Ti2WoID = @sn_test_word_id, 'SETUP: TextItem linked to word', 'SETUP FAIL') AS result
FROM word_occurrences WHERE Ti2TxID = @sn_test_text_id AND Ti2Order = 1;

-- Delete word - Ti2WoID should become NULL
DELETE FROM words WHERE WoID = @sn_test_word_id;

-- Verify Ti2WoID is now NULL (not deleted, just unlinked)
SELECT IF(Ti2WoID IS NULL, 'PASS: Ti2WoID set to NULL', 'FAIL: Ti2WoID not NULL') AS result
FROM word_occurrences WHERE Ti2TxID = @sn_test_text_id AND Ti2Order = 1;

-- Verify text item still exists
SELECT IF(COUNT(*) = 1, 'PASS: TextItem preserved after word deletion', 'FAIL: TextItem deleted') AS result
FROM word_occurrences WHERE Ti2TxID = @sn_test_text_id AND Ti2Order = 1;

-- ============================================================================
-- Test 6: FK word_tag_map -> words (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 6: word_tag_map -> words CASCADE ---' AS test;

-- Create word and tag
INSERT INTO words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoWordCount)
VALUES (@test_lang_id, 'fktest_tagged', 'fktest_tagged', 1, 'test', 1);

SET @wt_test_word_id = LAST_INSERT_ID();

INSERT INTO tags (TgText, TgComment) VALUES ('fktest_tag', 'Test tag');
SET @wt_test_tag_id = LAST_INSERT_ID();

INSERT INTO word_tag_map (WtWoID, WtTgID) VALUES (@wt_test_word_id, @wt_test_tag_id);

-- Verify wordtag exists
SELECT IF(COUNT(*) = 1, 'SETUP: Wordtag created', 'SETUP FAIL') AS result
FROM word_tag_map WHERE WtWoID = @wt_test_word_id;

-- Delete word - should cascade to word_tag_map
DELETE FROM words WHERE WoID = @wt_test_word_id;

-- Verify wordtag was deleted
SELECT IF(COUNT(*) = 0, 'PASS: Wordtag deleted via CASCADE', 'FAIL: Wordtag not deleted') AS result
FROM word_tag_map WHERE WtWoID = @wt_test_word_id;

-- ============================================================================
-- Test 7: FK word_tag_map -> tags (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 7: word_tag_map -> tags CASCADE ---' AS test;

-- Create word and tag
INSERT INTO words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoWordCount)
VALUES (@test_lang_id, 'fktest_tagged2', 'fktest_tagged2', 1, 'test', 1);

SET @wt2_test_word_id = LAST_INSERT_ID();

INSERT INTO tags (TgText, TgComment) VALUES ('fktest_tag2', 'Test tag 2');
SET @wt2_test_tag_id = LAST_INSERT_ID();

INSERT INTO word_tag_map (WtWoID, WtTgID) VALUES (@wt2_test_word_id, @wt2_test_tag_id);

-- Verify wordtag exists
SELECT IF(COUNT(*) = 1, 'SETUP: Wordtag created', 'SETUP FAIL') AS result
FROM word_tag_map WHERE WtTgID = @wt2_test_tag_id;

-- Delete tag - should cascade to word_tag_map
DELETE FROM tags WHERE TgID = @wt2_test_tag_id;

-- Verify wordtag was deleted
SELECT IF(COUNT(*) = 0, 'PASS: Wordtag deleted via tag CASCADE', 'FAIL: Wordtag not deleted') AS result
FROM word_tag_map WHERE WtTgID = @wt2_test_tag_id;

-- ============================================================================
-- Test 8: FK text_tag_map -> texts (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 8: text_tag_map -> texts CASCADE ---' AS test;

INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
VALUES (@test_lang_id, 'FK_TEST_TextTag', 'Test', '');

SET @tt_test_text_id = LAST_INSERT_ID();

INSERT INTO text_tags (T2Text, T2Comment) VALUES ('fktest_texttag', 'Test text tag');
SET @tt_test_tag_id = LAST_INSERT_ID();

INSERT INTO text_tag_map (TtTxID, TtT2ID) VALUES (@tt_test_text_id, @tt_test_tag_id);

-- Verify texttag exists
SELECT IF(COUNT(*) = 1, 'SETUP: Texttag created', 'SETUP FAIL') AS result
FROM text_tag_map WHERE TtTxID = @tt_test_text_id;

-- Delete text - should cascade to text_tag_map
DELETE FROM texts WHERE TxID = @tt_test_text_id;

-- Verify texttag was deleted
SELECT IF(COUNT(*) = 0, 'PASS: Texttag deleted via CASCADE', 'FAIL: Texttag not deleted') AS result
FROM text_tag_map WHERE TtTxID = @tt_test_text_id;

-- ============================================================================
-- Test 9: FK archived texts (in texts table) -> languages (ON DELETE CASCADE)
-- Note: Archived texts are now stored in the texts table with TxArchivedAt set
-- ============================================================================

SELECT '--- Test 9: archived texts -> languages CASCADE ---' AS test;

INSERT INTO languages (LgName, LgDict1URI, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters)
VALUES ('FK_TEST_Archive_Lang', 'https://test.com/###', '', '.!?', '', 'a-zA-Z');

SET @arch_lang_id = LAST_INSERT_ID();

-- Insert archived text (texts with TxArchivedAt set)
INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxArchivedAt)
VALUES (@arch_lang_id, 'FK_TEST_Archived', 'Archived content', '', NOW());

SET @arch_text_id = LAST_INSERT_ID();

-- Verify archived text exists
SELECT IF(COUNT(*) = 1, 'SETUP: ArchivedText created', 'SETUP FAIL') AS result
FROM texts WHERE TxID = @arch_text_id AND TxArchivedAt IS NOT NULL;

-- Delete language - should cascade to archived texts
DELETE FROM languages WHERE LgID = @arch_lang_id;

-- Verify archived text was deleted
SELECT IF(COUNT(*) = 0, 'PASS: ArchivedText deleted via CASCADE', 'FAIL: ArchivedText not deleted') AS result
FROM texts WHERE TxID = @arch_text_id;

-- ============================================================================
-- Test 10: FK text_tag_map -> texts (archived texts) (ON DELETE CASCADE)
-- Note: Archived texts now use the same text_tag_map table as active texts
-- ============================================================================

SELECT '--- Test 10: text_tag_map -> archived texts CASCADE ---' AS test;

-- Insert archived text
INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxArchivedAt)
VALUES (@test_lang_id, 'FK_TEST_ArchTag', 'Archived', '', NOW());

SET @att_arch_id = LAST_INSERT_ID();

INSERT INTO text_tags (T2Text, T2Comment) VALUES ('fktest_archtag', 'Arch tag');
SET @att_tag_id = LAST_INSERT_ID();

INSERT INTO text_tag_map (TtTxID, TtT2ID) VALUES (@att_arch_id, @att_tag_id);

-- Verify text_tag_map exists for archived text
SELECT IF(COUNT(*) = 1, 'SETUP: ArchTextTag created', 'SETUP FAIL') AS result
FROM text_tag_map WHERE TtTxID = @att_arch_id;

-- Delete archived text - should cascade to text_tag_map
DELETE FROM texts WHERE TxID = @att_arch_id;

-- Verify text_tag_map entry was deleted
SELECT IF(COUNT(*) = 0, 'PASS: ArchTextTag deleted via CASCADE', 'FAIL: ArchTextTag not deleted') AS result
FROM text_tag_map WHERE TtTxID = @att_arch_id;

-- ============================================================================
-- Test 11: FK news_feeds -> languages (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 11: news_feeds -> languages CASCADE ---' AS test;

INSERT INTO languages (LgName, LgDict1URI, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters)
VALUES ('FK_TEST_Feed_Lang', 'https://test.com/###', '', '.!?', '', 'a-zA-Z');

SET @feed_lang_id = LAST_INSERT_ID();

INSERT INTO news_feeds (NfLgID, NfName, NfSourceURI, NfArticleSectionTags, NfFilterTags, NfUpdate, NfOptions)
VALUES (@feed_lang_id, 'FK_TEST_Feed', 'https://test.com/feed', '', '', 0, '');

SET @feed_id = LAST_INSERT_ID();

-- Verify newsfeed exists
SELECT IF(COUNT(*) = 1, 'SETUP: Newsfeed created', 'SETUP FAIL') AS result
FROM news_feeds WHERE NfID = @feed_id;

-- Delete language - should cascade to news_feeds
DELETE FROM languages WHERE LgID = @feed_lang_id;

-- Verify newsfeed was deleted
SELECT IF(COUNT(*) = 0, 'PASS: Newsfeed deleted via CASCADE', 'FAIL: Newsfeed not deleted') AS result
FROM news_feeds WHERE NfID = @feed_id;

-- ============================================================================
-- Test 12: FK feed_links -> news_feeds (ON DELETE CASCADE)
-- ============================================================================

SELECT '--- Test 12: feed_links -> news_feeds CASCADE ---' AS test;

INSERT INTO news_feeds (NfLgID, NfName, NfSourceURI, NfArticleSectionTags, NfFilterTags, NfUpdate, NfOptions)
VALUES (@test_lang_id, 'FK_TEST_FeedLink', 'https://test.com/feed2', '', '', 0, '');

SET @fl_feed_id = LAST_INSERT_ID();

INSERT INTO feed_links (FlNfID, FlTitle, FlLink, FlDescription, FlDate, FlAudio, FlText)
VALUES (@fl_feed_id, 'FK_TEST_Link', 'https://test.com/article', 'Test', NOW(), '', '');

SET @feedlink_id = LAST_INSERT_ID();

-- Verify feedlink exists
SELECT IF(COUNT(*) = 1, 'SETUP: Feedlink created', 'SETUP FAIL') AS result
FROM feed_links WHERE FlID = @feedlink_id;

-- Delete newsfeed - should cascade to feed_links
DELETE FROM news_feeds WHERE NfID = @fl_feed_id;

-- Verify feedlink was deleted
SELECT IF(COUNT(*) = 0, 'PASS: Feedlink deleted via CASCADE', 'FAIL: Feedlink not deleted') AS result
FROM feed_links WHERE FlID = @feedlink_id;

-- ============================================================================
-- Test 13: Verify FK constraint prevents invalid references
-- ============================================================================

SELECT '--- Test 13: FK constraint enforcement ---' AS test;

-- Try to insert text with non-existent language (should fail)
SET @error_occurred = 0;

-- This should fail due to FK constraint
-- We use a handler to catch the error
DELIMITER //
CREATE PROCEDURE test_fk_constraint()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SELECT 'PASS: FK constraint prevented invalid insert' AS result;
    END;

    INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
    VALUES (255, 'FK_TEST_Invalid', 'Test', '');

    SELECT 'FAIL: Invalid insert was allowed' AS result;
END//
DELIMITER ;

CALL test_fk_constraint();
DROP PROCEDURE IF EXISTS test_fk_constraint;

-- ============================================================================
-- Test 14: Verify language deletion cascades through entire chain
-- ============================================================================

SELECT '--- Test 14: Full cascade chain (language -> text -> sentence -> textitem) ---' AS test;

INSERT INTO languages (LgName, LgDict1URI, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters)
VALUES ('FK_TEST_FullCascade', 'https://test.com/###', '', '.!?', '', 'a-zA-Z');

SET @fc_lang_id = LAST_INSERT_ID();

INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
VALUES (@fc_lang_id, 'FK_TEST_FullCascade_Text', 'Test', '');

SET @fc_text_id = LAST_INSERT_ID();

INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
VALUES (@fc_lang_id, @fc_text_id, 1, 'Full cascade sentence', 1);

SET @fc_sentence_id = LAST_INSERT_ID();

INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text, Ti2Translation)
VALUES (NULL, @fc_lang_id, @fc_text_id, @fc_sentence_id, 1, 1, 'cascadeword', '');

-- Verify all exist
SELECT IF(
    (SELECT COUNT(*) FROM texts WHERE TxID = @fc_text_id) = 1 AND
    (SELECT COUNT(*) FROM sentences WHERE SeID = @fc_sentence_id) = 1 AND
    (SELECT COUNT(*) FROM word_occurrences WHERE Ti2TxID = @fc_text_id) = 1,
    'SETUP: Full chain created', 'SETUP FAIL'
) AS result;

-- Delete language - should cascade through entire chain
DELETE FROM languages WHERE LgID = @fc_lang_id;

-- Verify all were deleted
SELECT IF(
    (SELECT COUNT(*) FROM texts WHERE TxID = @fc_text_id) = 0 AND
    (SELECT COUNT(*) FROM sentences WHERE SeID = @fc_sentence_id) = 0 AND
    (SELECT COUNT(*) FROM word_occurrences WHERE Ti2TxID = @fc_text_id) = 0,
    'PASS: Full chain deleted via CASCADE', 'FAIL: Some records remain'
) AS result;

-- ============================================================================
-- Cleanup
-- ============================================================================

SELECT '--- Cleanup ---' AS status;

-- Clean up remaining test data
DELETE FROM word_occurrences WHERE Ti2TxID IN (SELECT TxID FROM texts WHERE TxTitle LIKE 'FK_TEST_%');
DELETE FROM sentences WHERE SeTxID IN (SELECT TxID FROM texts WHERE TxTitle LIKE 'FK_TEST_%');
DELETE FROM text_tag_map WHERE TtTxID IN (SELECT TxID FROM texts WHERE TxTitle LIKE 'FK_TEST_%');
DELETE FROM texts WHERE TxTitle LIKE 'FK_TEST_%';
DELETE FROM word_tag_map WHERE WtWoID IN (SELECT WoID FROM words WHERE WoText LIKE 'fktest_%');
DELETE FROM words WHERE WoText LIKE 'fktest_%';
-- Note: archivedtexts merged into texts table, cleanup already handled by texts DELETE above
DELETE FROM feed_links WHERE FlTitle LIKE 'FK_TEST_%';
DELETE FROM news_feeds WHERE NfName LIKE 'FK_TEST_%';
DELETE FROM tags WHERE TgText LIKE 'fktest_%';
DELETE FROM text_tags WHERE T2Text LIKE 'fktest_%';
DELETE FROM languages WHERE LgName LIKE 'FK_TEST_%';

SELECT '=== All Foreign Key Tests Complete ===' AS status;
