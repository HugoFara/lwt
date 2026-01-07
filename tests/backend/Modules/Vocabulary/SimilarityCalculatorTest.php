<?php declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Vocabulary\Application\Services\SimilarityCalculator;

/**
 * Comprehensive tests for SimilarityCalculator.
 *
 * Tests all similarity algorithms including phonetic normalization,
 * letter pairs, Sørensen–Dice coefficient, and combined similarity ranking.
 */
class SimilarityCalculatorTest extends TestCase
{
    private SimilarityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SimilarityCalculator();
    }

    // =========================================================================
    // Constants Tests
    // =========================================================================

    public function testStatusWeightLearnedConstant(): void
    {
        $this->assertSame(1.3, SimilarityCalculator::STATUS_WEIGHT_LEARNED);
    }

    public function testStatusWeightInProgressConstant(): void
    {
        $this->assertSame(1.15, SimilarityCalculator::STATUS_WEIGHT_IN_PROGRESS);
    }

    public function testStatusWeightNewConstant(): void
    {
        $this->assertSame(1.0, SimilarityCalculator::STATUS_WEIGHT_NEW);
    }

    public function testStatusWeightWellKnownConstant(): void
    {
        $this->assertSame(1.25, SimilarityCalculator::STATUS_WEIGHT_WELL_KNOWN);
    }

    public function testStatusWeightIgnoredConstant(): void
    {
        $this->assertSame(0.5, SimilarityCalculator::STATUS_WEIGHT_IGNORED);
    }

    // =========================================================================
    // getStatusWeight Tests
    // =========================================================================

    public function testGetStatusWeightForNewWord(): void
    {
        $this->assertSame(1.0, $this->calculator->getStatusWeight(1));
    }

    public function testGetStatusWeightForLearningStage2(): void
    {
        $this->assertSame(1.15, $this->calculator->getStatusWeight(2));
    }

    public function testGetStatusWeightForLearningStage3(): void
    {
        $this->assertSame(1.15, $this->calculator->getStatusWeight(3));
    }

    public function testGetStatusWeightForLearningStage4(): void
    {
        $this->assertSame(1.15, $this->calculator->getStatusWeight(4));
    }

    public function testGetStatusWeightForLearnedWord(): void
    {
        $this->assertSame(1.3, $this->calculator->getStatusWeight(5));
    }

    public function testGetStatusWeightForIgnoredWord(): void
    {
        $this->assertSame(0.5, $this->calculator->getStatusWeight(98));
    }

    public function testGetStatusWeightForWellKnownWord(): void
    {
        $this->assertSame(1.25, $this->calculator->getStatusWeight(99));
    }

    public function testGetStatusWeightForUnknownStatusReturnsNew(): void
    {
        $this->assertSame(1.0, $this->calculator->getStatusWeight(0));
        $this->assertSame(1.0, $this->calculator->getStatusWeight(6));
        $this->assertSame(1.0, $this->calculator->getStatusWeight(100));
        $this->assertSame(1.0, $this->calculator->getStatusWeight(-1));
    }

    // =========================================================================
    // phoneticNormalize Tests - Basic functionality
    // =========================================================================

    public function testPhoneticNormalizeEmptyString(): void
    {
        $this->assertSame('', $this->calculator->phoneticNormalize(''));
    }

    public function testPhoneticNormalizeSingleCharacter(): void
    {
        $this->assertSame('a', $this->calculator->phoneticNormalize('a'));
    }

    public function testPhoneticNormalizeBasicWord(): void
    {
        $result = $this->calculator->phoneticNormalize('hello');
        $this->assertSame('helo', $result); // duplicate 'l' removed
    }

    public function testPhoneticNormalizeRemovesConsecutiveDuplicates(): void
    {
        // Only consecutive duplicates of mapped chars are removed
        $this->assertSame('helo', $this->calculator->phoneticNormalize('helloo'));
        // 'banana' - alternating chars, no consecutive duplicates
        $this->assertSame('banana', $this->calculator->phoneticNormalize('banana'));
        // 'baaana' - consecutive 'a's get deduplicated
        $this->assertSame('bana', $this->calculator->phoneticNormalize('baaana'));
    }

    // =========================================================================
    // phoneticNormalize Tests - Vowel mappings
    // =========================================================================

    public function testPhoneticNormalizeAccentedA(): void
    {
        $this->assertSame('a', $this->calculator->phoneticNormalize('à'));
        $this->assertSame('a', $this->calculator->phoneticNormalize('á'));
        $this->assertSame('a', $this->calculator->phoneticNormalize('â'));
        $this->assertSame('a', $this->calculator->phoneticNormalize('ã'));
        $this->assertSame('a', $this->calculator->phoneticNormalize('ä'));
        $this->assertSame('a', $this->calculator->phoneticNormalize('å'));
    }

    public function testPhoneticNormalizeAccentedE(): void
    {
        $this->assertSame('e', $this->calculator->phoneticNormalize('è'));
        $this->assertSame('e', $this->calculator->phoneticNormalize('é'));
        $this->assertSame('e', $this->calculator->phoneticNormalize('ê'));
        $this->assertSame('e', $this->calculator->phoneticNormalize('ë'));
    }

    public function testPhoneticNormalizeAccentedI(): void
    {
        $this->assertSame('i', $this->calculator->phoneticNormalize('ì'));
        $this->assertSame('i', $this->calculator->phoneticNormalize('í'));
        $this->assertSame('i', $this->calculator->phoneticNormalize('î'));
        $this->assertSame('i', $this->calculator->phoneticNormalize('ï'));
    }

    public function testPhoneticNormalizeAccentedO(): void
    {
        $this->assertSame('o', $this->calculator->phoneticNormalize('ò'));
        $this->assertSame('o', $this->calculator->phoneticNormalize('ó'));
        $this->assertSame('o', $this->calculator->phoneticNormalize('ô'));
        $this->assertSame('o', $this->calculator->phoneticNormalize('õ'));
        $this->assertSame('o', $this->calculator->phoneticNormalize('ö'));
    }

    public function testPhoneticNormalizeAccentedU(): void
    {
        $this->assertSame('u', $this->calculator->phoneticNormalize('ù'));
        $this->assertSame('u', $this->calculator->phoneticNormalize('ú'));
        $this->assertSame('u', $this->calculator->phoneticNormalize('û'));
        $this->assertSame('u', $this->calculator->phoneticNormalize('ü'));
    }

    public function testPhoneticNormalizeDiphthongs(): void
    {
        $this->assertSame('ae', $this->calculator->phoneticNormalize('æ'));
        $this->assertSame('oe', $this->calculator->phoneticNormalize('œ'));
    }

    public function testPhoneticNormalizeYToI(): void
    {
        $this->assertSame('i', $this->calculator->phoneticNormalize('y'));
    }

    // =========================================================================
    // phoneticNormalize Tests - Consonant mappings
    // =========================================================================

    public function testPhoneticNormalizeCToK(): void
    {
        $this->assertSame('k', $this->calculator->phoneticNormalize('c'));
        $this->assertSame('k', $this->calculator->phoneticNormalize('k'));
        $this->assertSame('k', $this->calculator->phoneticNormalize('q'));
    }

    public function testPhoneticNormalizeCedilla(): void
    {
        $this->assertSame('s', $this->calculator->phoneticNormalize('ç'));
    }

    public function testPhoneticNormalizeSAndZ(): void
    {
        $this->assertSame('s', $this->calculator->phoneticNormalize('s'));
        $this->assertSame('s', $this->calculator->phoneticNormalize('z'));
        $this->assertSame('s', $this->calculator->phoneticNormalize('ś'));
        $this->assertSame('s', $this->calculator->phoneticNormalize('š'));
        $this->assertSame('s', $this->calculator->phoneticNormalize('ž'));
    }

    public function testPhoneticNormalizeGermanEszett(): void
    {
        $this->assertSame('ss', $this->calculator->phoneticNormalize('ß'));
    }

    public function testPhoneticNormalizeXToKs(): void
    {
        $this->assertSame('ks', $this->calculator->phoneticNormalize('x'));
    }

    public function testPhoneticNormalizePolishL(): void
    {
        $this->assertSame('l', $this->calculator->phoneticNormalize('ł'));
    }

    public function testPhoneticNormalizeSpanishN(): void
    {
        $this->assertSame('n', $this->calculator->phoneticNormalize('ñ'));
    }

    // =========================================================================
    // phoneticNormalize Tests - Multi-character mappings
    // =========================================================================

    public function testPhoneticNormalizePhToF(): void
    {
        $this->assertSame('fone', $this->calculator->phoneticNormalize('phone'));
        $this->assertSame('filosofi', $this->calculator->phoneticNormalize('philosophy'));
    }

    // =========================================================================
    // phoneticNormalize Tests - Real words
    // =========================================================================

    public function testPhoneticNormalizeGermanWords(): void
    {
        // "schön" -> s + c(k) + h + ö(o) + n = 'skhon'
        $result = $this->calculator->phoneticNormalize('schön');
        $this->assertSame('skhon', $result);

        // "größe" (size) - ö->o, ß->ss, e->e = 'grosse'
        $result = $this->calculator->phoneticNormalize('größe');
        $this->assertSame('grosse', $result);
    }

    public function testPhoneticNormalizeFrenchWords(): void
    {
        // "français" -> francais equivalent
        $result = $this->calculator->phoneticNormalize('français');
        $this->assertSame('fransais', $result);

        // "café"
        $result = $this->calculator->phoneticNormalize('café');
        $this->assertSame('kafe', $result);
    }

    public function testPhoneticNormalizeSpanishWords(): void
    {
        // "niño" -> nino
        $result = $this->calculator->phoneticNormalize('niño');
        $this->assertSame('nino', $result);

        // "año" -> ano
        $result = $this->calculator->phoneticNormalize('año');
        $this->assertSame('ano', $result);
    }

    public function testPhoneticNormalizePolishWords(): void
    {
        // "złoty" -> sloti (z->s, ł->l, y->i)
        $result = $this->calculator->phoneticNormalize('złoty');
        $this->assertSame('sloti', $result);
    }

    public function testPhoneticNormalizeSimilarWordsMatch(): void
    {
        // Words that should normalize to similar forms
        $cafe1 = $this->calculator->phoneticNormalize('cafe');
        $cafe2 = $this->calculator->phoneticNormalize('café');
        $this->assertSame($cafe1, $cafe2);

        $naive1 = $this->calculator->phoneticNormalize('naive');
        $naive2 = $this->calculator->phoneticNormalize('naïve');
        $this->assertSame($naive1, $naive2);
    }

    // =========================================================================
    // letterPairs Tests
    // =========================================================================

    public function testLetterPairsEmptyString(): void
    {
        $this->assertSame([], $this->calculator->letterPairs(''));
    }

    public function testLetterPairsSingleCharacter(): void
    {
        $this->assertSame([], $this->calculator->letterPairs('a'));
    }

    public function testLetterPairsTwoCharacters(): void
    {
        $this->assertSame(['ab'], $this->calculator->letterPairs('ab'));
    }

    public function testLetterPairsThreeCharacters(): void
    {
        $this->assertSame(['ab', 'bc'], $this->calculator->letterPairs('abc'));
    }

    public function testLetterPairsWord(): void
    {
        $result = $this->calculator->letterPairs('hello');
        $this->assertSame(['he', 'el', 'll', 'lo'], $result);
    }

    public function testLetterPairsWithSpaces(): void
    {
        // Includes space as character
        $result = $this->calculator->letterPairs('a b');
        $this->assertSame(['a ', ' b'], $result);
    }

    public function testLetterPairsUtf8(): void
    {
        // UTF-8 characters should work
        $result = $this->calculator->letterPairs('café');
        $this->assertSame(['ca', 'af', 'fé'], $result);
    }

    public function testLetterPairsReturnsCorrectCount(): void
    {
        $str = 'testing';
        $result = $this->calculator->letterPairs($str);
        $this->assertCount(strlen($str) - 1, $result);
    }

    // =========================================================================
    // wordLetterPairs Tests
    // =========================================================================

    public function testWordLetterPairsEmptyString(): void
    {
        $this->assertSame([], $this->calculator->wordLetterPairs(''));
    }

    public function testWordLetterPairsSingleWord(): void
    {
        $result = $this->calculator->wordLetterPairs('hello');
        $this->assertSame(['he', 'el', 'll', 'lo'], $result);
    }

    public function testWordLetterPairsTwoWords(): void
    {
        $result = $this->calculator->wordLetterPairs('hi there');
        // 'hi' -> ['hi'], 'there' -> ['th', 'he', 'er', 're']
        // Unique pairs
        $this->assertContains('hi', $result);
        $this->assertContains('th', $result);
        $this->assertContains('he', $result);
        $this->assertContains('er', $result);
        $this->assertContains('re', $result);
    }

    public function testWordLetterPairsDeduplicates(): void
    {
        // 'aa aa' would have duplicate 'aa' pairs from each word
        $result = $this->calculator->wordLetterPairs('aa aa');
        // Should only have 'aa' once
        $this->assertSame(['aa'], $result);
    }

    public function testWordLetterPairsSentence(): void
    {
        $result = $this->calculator->wordLetterPairs('the quick fox');
        $this->assertContains('th', $result);
        $this->assertContains('he', $result);
        $this->assertContains('qu', $result);
        $this->assertContains('ui', $result);
        $this->assertContains('ic', $result);
        $this->assertContains('ck', $result);
        $this->assertContains('fo', $result);
        $this->assertContains('ox', $result);
    }

    public function testWordLetterPairsUtf8(): void
    {
        $result = $this->calculator->wordLetterPairs('café latte');
        $this->assertContains('ca', $result);
        $this->assertContains('af', $result);
        $this->assertContains('fé', $result);
        $this->assertContains('la', $result);
        $this->assertContains('at', $result);
        $this->assertContains('tt', $result);
        $this->assertContains('te', $result);
    }

    // =========================================================================
    // getSimilarityRanking Tests - Basic cases
    // =========================================================================

    public function testGetSimilarityRankingIdenticalStrings(): void
    {
        $this->assertSame(1.0, $this->calculator->getSimilarityRanking('hello', 'hello'));
    }

    public function testGetSimilarityRankingCompletelyDifferent(): void
    {
        $result = $this->calculator->getSimilarityRanking('abc', 'xyz');
        $this->assertSame(0.0, $result);
    }

    public function testGetSimilarityRankingEmptyStrings(): void
    {
        $this->assertSame(0.0, $this->calculator->getSimilarityRanking('', ''));
    }

    public function testGetSimilarityRankingOneEmpty(): void
    {
        $this->assertSame(0.0, $this->calculator->getSimilarityRanking('hello', ''));
        $this->assertSame(0.0, $this->calculator->getSimilarityRanking('', 'hello'));
    }

    public function testGetSimilarityRankingSingleCharacters(): void
    {
        // Single characters have no pairs
        $this->assertSame(0.0, $this->calculator->getSimilarityRanking('a', 'a'));
        $this->assertSame(0.0, $this->calculator->getSimilarityRanking('a', 'b'));
    }

    public function testGetSimilarityRankingTwoCharacters(): void
    {
        // Same pair
        $this->assertSame(1.0, $this->calculator->getSimilarityRanking('ab', 'ab'));
        // Different pairs
        $this->assertSame(0.0, $this->calculator->getSimilarityRanking('ab', 'cd'));
    }

    // =========================================================================
    // getSimilarityRanking Tests - Partial matches
    // =========================================================================

    public function testGetSimilarityRankingPartialOverlap(): void
    {
        // 'abc' has pairs ['ab', 'bc']
        // 'abd' has pairs ['ab', 'bd']
        // intersection: ['ab'] = 1
        // union: 4
        // score = 2 * 1 / 4 = 0.5
        $result = $this->calculator->getSimilarityRanking('abc', 'abd');
        $this->assertSame(0.5, $result);
    }

    public function testGetSimilarityRankingHighSimilarity(): void
    {
        $result = $this->calculator->getSimilarityRanking('hello', 'hallo');
        // 'hello' pairs: he, el, ll, lo (4)
        // 'hallo' pairs: ha, al, ll, lo (4)
        // intersection: ll, lo (2)
        // Score = 2 * 2 / 8 = 0.5
        $this->assertSame(0.5, $result);
    }

    public function testGetSimilarityRankingLowSimilarity(): void
    {
        $result = $this->calculator->getSimilarityRanking('cat', 'dog');
        // No common pairs
        $this->assertSame(0.0, $result);
    }

    public function testGetSimilarityRankingSymmetric(): void
    {
        $score1 = $this->calculator->getSimilarityRanking('hello', 'hallo');
        $score2 = $this->calculator->getSimilarityRanking('hallo', 'hello');
        $this->assertSame($score1, $score2);
    }

    // =========================================================================
    // getSimilarityRanking Tests - Real-world examples
    // =========================================================================

    public function testGetSimilarityRankingTypos(): void
    {
        // Common typo - transposed letters
        // 'receive' pairs: re, ec, ce, ei, iv, ve (6)
        // 'recieve' pairs: re, ec, ci, ie, ev, ve (6)
        // intersection: re, ec, ve (3)
        // Score = 2 * 3 / 12 = 0.5
        $result = $this->calculator->getSimilarityRanking('receive', 'recieve');
        $this->assertSame(0.5, $result);

        // Missing letter
        // 'hello' pairs: he, el, ll, lo (4)
        // 'helo' pairs: he, el, lo (3)
        // intersection: he, el, lo (3)
        // Score = 2 * 3 / 7 = 0.857...
        $result = $this->calculator->getSimilarityRanking('hello', 'helo');
        $this->assertGreaterThan(0.8, $result);
    }

    public function testGetSimilarityRankingRelatedWords(): void
    {
        // Related words with common stems
        $result = $this->calculator->getSimilarityRanking('running', 'runner');
        $this->assertGreaterThan(0.5, $result);

        $result = $this->calculator->getSimilarityRanking('testing', 'tested');
        $this->assertGreaterThan(0.4, $result);
    }

    public function testGetSimilarityRankingMultipleWords(): void
    {
        $result = $this->calculator->getSimilarityRanking('hello world', 'hello there');
        // 'hello' is shared
        $this->assertGreaterThan(0.3, $result);
    }

    // =========================================================================
    // getSimilarityRanking Tests - UTF-8 support
    // =========================================================================

    public function testGetSimilarityRankingUtf8Identical(): void
    {
        $this->assertSame(1.0, $this->calculator->getSimilarityRanking('café', 'café'));
    }

    public function testGetSimilarityRankingUtf8Similar(): void
    {
        $result = $this->calculator->getSimilarityRanking('café', 'cafe');
        // Should be high but not perfect due to é vs e
        $this->assertGreaterThan(0.5, $result);
    }

    public function testGetSimilarityRankingUtf8Different(): void
    {
        $result = $this->calculator->getSimilarityRanking('日本', '中国');
        // Completely different characters
        $this->assertSame(0.0, $result);
    }

    // =========================================================================
    // getCombinedSimilarityRanking Tests
    // =========================================================================

    public function testGetCombinedSimilarityRankingIdentical(): void
    {
        $result = $this->calculator->getCombinedSimilarityRanking('hello', 'hello');
        $this->assertSame(1.0, $result);
    }

    public function testGetCombinedSimilarityRankingEmpty(): void
    {
        $result = $this->calculator->getCombinedSimilarityRanking('', '');
        $this->assertSame(0.0, $result);
    }

    public function testGetCombinedSimilarityRankingDefaultWeight(): void
    {
        // Default phonetic weight is 0.3
        $result = $this->calculator->getCombinedSimilarityRanking('hello', 'hallo');
        $this->assertGreaterThan(0.0, $result);
        $this->assertLessThan(1.0, $result);
    }

    public function testGetCombinedSimilarityRankingNoPhoneticWeight(): void
    {
        // With 0 phonetic weight, should equal character similarity
        $charSimilarity = $this->calculator->getSimilarityRanking('hello', 'hallo');
        $combined = $this->calculator->getCombinedSimilarityRanking('hello', 'hallo', 0.0);
        $this->assertSame($charSimilarity, $combined);
    }

    public function testGetCombinedSimilarityRankingFullPhoneticWeight(): void
    {
        // With 1.0 phonetic weight, should equal phonetic similarity
        $phonetic1 = $this->calculator->phoneticNormalize('hello');
        $phonetic2 = $this->calculator->phoneticNormalize('hallo');
        $phoneticSimilarity = $this->calculator->getSimilarityRanking($phonetic1, $phonetic2);
        $combined = $this->calculator->getCombinedSimilarityRanking('hello', 'hallo', 1.0);
        $this->assertSame($phoneticSimilarity, $combined);
    }

    public function testGetCombinedSimilarityRankingBetweenPureScores(): void
    {
        $str1 = 'cafe';
        $str2 = 'café';

        $charSimilarity = $this->calculator->getSimilarityRanking($str1, $str2);
        $phonetic1 = $this->calculator->phoneticNormalize($str1);
        $phonetic2 = $this->calculator->phoneticNormalize($str2);
        $phoneticSimilarity = $this->calculator->getSimilarityRanking($phonetic1, $phonetic2);
        $combined = $this->calculator->getCombinedSimilarityRanking($str1, $str2, 0.5);

        // Combined should be average of both when weight is 0.5
        $expected = 0.5 * $charSimilarity + 0.5 * $phoneticSimilarity;
        $this->assertEqualsWithDelta($expected, $combined, 0.0001);
    }

    public function testGetCombinedSimilarityRankingSymmetric(): void
    {
        $score1 = $this->calculator->getCombinedSimilarityRanking('hello', 'hallo');
        $score2 = $this->calculator->getCombinedSimilarityRanking('hallo', 'hello');
        $this->assertEqualsWithDelta($score1, $score2, 0.0001);
    }

    // =========================================================================
    // getCombinedSimilarityRanking Tests - Phonetic benefits
    // =========================================================================

    public function testGetCombinedSimilarityRankingPhoneticHelpsAccentedChars(): void
    {
        // cafe vs café - phonetic normalization should make them identical
        $charOnly = $this->calculator->getSimilarityRanking('cafe', 'café');
        $combined = $this->calculator->getCombinedSimilarityRanking('cafe', 'café', 0.5);

        // Combined should be higher due to phonetic matching
        $this->assertGreaterThanOrEqual($charOnly, $combined);
    }

    public function testGetCombinedSimilarityRankingPhoneticHelpsSimilarSounds(): void
    {
        // 'phone' vs 'fone' - 'ph' maps to 'f'
        $charOnly = $this->calculator->getSimilarityRanking('phone', 'fone');
        $combined = $this->calculator->getCombinedSimilarityRanking('phone', 'fone', 0.5);

        // Combined should be higher due to phonetic matching
        $this->assertGreaterThan($charOnly, $combined);
    }

    public function testGetCombinedSimilarityRankingPhoneticHelpsGermanWords(): void
    {
        // 'grosse' vs 'größe' - ß maps to ss
        $charOnly = $this->calculator->getSimilarityRanking('grosse', 'größe');
        $combined = $this->calculator->getCombinedSimilarityRanking('grosse', 'größe', 0.5);

        // Combined should be higher
        $this->assertGreaterThan($charOnly, $combined);
    }

    // =========================================================================
    // Edge Cases and Regression Tests
    // =========================================================================

    public function testPhoneticNormalizeConsecutiveMappedChars(): void
    {
        // Multiple consecutive chars that map to same thing
        // 'cc' -> both map to 'k', but duplicate removal should give just 'k'
        $result = $this->calculator->phoneticNormalize('cc');
        $this->assertSame('k', $result);
    }

    public function testPhoneticNormalizeMixedCase(): void
    {
        // Note: The method expects lowercase input based on docblock
        // Testing with lowercase as specified
        $result = $this->calculator->phoneticNormalize('hello');
        $this->assertSame('helo', $result);
    }

    public function testLetterPairsLongString(): void
    {
        $str = 'abcdefghij';
        $result = $this->calculator->letterPairs($str);
        $this->assertCount(9, $result);
        $this->assertSame('ab', $result[0]);
        $this->assertSame('ij', $result[8]);
    }

    public function testGetSimilarityRankingVeryLongStrings(): void
    {
        $str1 = str_repeat('hello ', 100);
        $str2 = str_repeat('hello ', 100);
        $result = $this->calculator->getSimilarityRanking($str1, $str2);
        $this->assertSame(1.0, $result);
    }

    public function testGetSimilarityRankingSpecialCharacters(): void
    {
        // 'hello!' pairs: he, el, ll, lo, o! (5)
        // 'hello?' pairs: he, el, ll, lo, o? (5)
        // intersection: he, el, ll, lo (4)
        // Score = 2 * 4 / 10 = 0.8
        $result = $this->calculator->getSimilarityRanking('hello!', 'hello?');
        $this->assertSame(0.8, $result);
    }

    public function testGetSimilarityRankingNumbers(): void
    {
        $result = $this->calculator->getSimilarityRanking('abc123', 'abc123');
        $this->assertSame(1.0, $result);

        $result = $this->calculator->getSimilarityRanking('abc123', 'abc456');
        $this->assertGreaterThan(0.0, $result); // 'abc' portion matches
    }

    public function testCombinedSimilarityWithVariousWeights(): void
    {
        $str1 = 'phone';
        $str2 = 'fone';

        $weight0 = $this->calculator->getCombinedSimilarityRanking($str1, $str2, 0.0);
        $weight03 = $this->calculator->getCombinedSimilarityRanking($str1, $str2, 0.3);
        $weight05 = $this->calculator->getCombinedSimilarityRanking($str1, $str2, 0.5);
        $weight1 = $this->calculator->getCombinedSimilarityRanking($str1, $str2, 1.0);

        // All scores should be in valid range
        $this->assertGreaterThanOrEqual(0.0, $weight0);
        $this->assertLessThanOrEqual(1.0, $weight0);
        $this->assertGreaterThanOrEqual(0.0, $weight1);
        $this->assertLessThanOrEqual(1.0, $weight1);

        // Since 'ph' -> 'f' phonetically, phonetic similarity should be higher
        // than character similarity for this pair
        $charSimilarity = $this->calculator->getSimilarityRanking($str1, $str2);
        $phonetic1 = $this->calculator->phoneticNormalize($str1);
        $phonetic2 = $this->calculator->phoneticNormalize($str2);
        $phoneticSimilarity = $this->calculator->getSimilarityRanking($phonetic1, $phonetic2);
        $this->assertGreaterThan($charSimilarity, $phoneticSimilarity);
    }

    // =========================================================================
    // Algorithm Correctness Tests (Sørensen–Dice coefficient)
    // =========================================================================

    public function testSorensenDiceCoefficientCalculation(): void
    {
        // 'night' -> pairs: ni, ig, gh, ht (4 pairs)
        // 'nacht' -> pairs: na, ac, ch, ht (4 pairs)
        // intersection: ht (1 pair)
        // union: 8 pairs
        // Dice = 2 * 1 / 8 = 0.25
        $result = $this->calculator->getSimilarityRanking('night', 'nacht');
        $this->assertEqualsWithDelta(0.25, $result, 0.0001);
    }

    public function testSorensenDiceCoefficientPerfectMatch(): void
    {
        // 'abc' -> pairs: ab, bc (2 pairs)
        // 'abc' -> pairs: ab, bc (2 pairs)
        // intersection: 2
        // union: 4
        // Dice = 2 * 2 / 4 = 1.0
        $result = $this->calculator->getSimilarityRanking('abc', 'abc');
        $this->assertSame(1.0, $result);
    }

    public function testSorensenDiceCoefficientNoMatch(): void
    {
        // 'abc' -> pairs: ab, bc (2 pairs)
        // 'xyz' -> pairs: xy, yz (2 pairs)
        // intersection: 0
        // union: 4
        // Dice = 2 * 0 / 4 = 0.0
        $result = $this->calculator->getSimilarityRanking('abc', 'xyz');
        $this->assertSame(0.0, $result);
    }
}
