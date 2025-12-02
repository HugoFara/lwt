<?php declare(strict_types=1);
/**
 * Language Service - Business logic for language management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Core\Entity\Language;
use Lwt\Core\Globals;
use Lwt\Core\Http\InputValidator;
use Lwt\Core\Http\UrlUtilities;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Maintenance;
use Lwt\Database\QueryBuilder;
use Lwt\Database\TextParsing;

require_once __DIR__ . '/../Core/Http/InputValidator.php';
require_once __DIR__ . '/../Core/Http/url_utilities.php';

/**
 * Service class for managing languages.
 *
 * Handles CRUD operations for languages, text reparsing,
 * and language validation.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class LanguageService
{
    /**
     * Get all languages as a name => id dictionary.
     *
     * @return array<string, int>
     */
    public function getAllLanguages(): array
    {
        $langs = [];
        $sql = "SELECT LgID, LgName FROM " . Globals::table('languages') . " WHERE LgName<>''";
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $langs[(string)$record['LgName']] = (int)$record['LgID'];
        }
        mysqli_free_result($res);
        return $langs;
    }

    /**
     * Get a language by ID.
     *
     * @param int $lid Language ID
     *
     * @return Language|null Language object or null if not found
     */
    public function getById(int $lid): ?Language
    {
        if ($lid <= 0) {
            return $this->createEmptyLanguage();
        }

        $tbpref = Globals::getTablePrefix();
        $sql = "SELECT * FROM {$tbpref}languages WHERE LgID = $lid";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
            return null;
        }

        return $this->mapRecordToLanguage($record);
    }

    /**
     * Create an empty language object with default values.
     *
     * @return Language
     */
    public function createEmptyLanguage(): Language
    {
        return Language::create(
            'New Language',
            '',
            '.!?',
            'a-zA-Z'
        );
    }

    /**
     * Map a database record to a Language object.
     *
     * @param array $record Database record
     *
     * @return Language
     */
    private function mapRecordToLanguage(array $record): Language
    {
        return Language::reconstitute(
            (int) $record["LgID"],
            (string) $record["LgName"],
            (string) $record["LgDict1URI"],
            (string) ($record["LgDict2URI"] ?? ''),
            (string) ($record["LgGoogleTranslateURI"] ?? ''),
            (string) ($record["LgExportTemplate"] ?? ''),
            (int) ($record["LgTextSize"] ?? 100),
            (string) ($record["LgCharacterSubstitutions"] ?? ''),
            (string) $record["LgRegexpSplitSentences"],
            (string) ($record["LgExceptionsSplitSentences"] ?? ''),
            (string) $record["LgRegexpWordCharacters"],
            (bool) ($record["LgRemoveSpaces"] ?? false),
            (bool) ($record["LgSplitEachChar"] ?? false),
            (bool) ($record["LgRightToLeft"] ?? false),
            (string) ($record["LgTTSVoiceAPI"] ?? ''),
            (bool) ($record["LgShowRomanization"] ?? true)
        );
    }

    /**
     * Check if a language exists by ID.
     *
     * @param int $lid Language ID
     *
     * @return bool
     */
    public function exists(int $lid): bool
    {
        $tbpref = Globals::getTablePrefix();
        $count = Connection::fetchValue(
            "SELECT COUNT(*) as value FROM {$tbpref}languages WHERE LgID = $lid"
        );
        return $count > 0;
    }

    /**
     * Save a new language to the database.
     *
     * @return string Result message
     */
    public function create(): string
    {
        $tbpref = Globals::getTablePrefix();
        $data = $this->getLanguageDataFromRequest();

        // Check if there's an empty language record to reuse
        $val = Connection::fetchValue(
            "SELECT MIN(LgID) AS value FROM {$tbpref}languages WHERE LgName=''"
        );

        $sql = $this->buildLanguageSql($data, $val !== null ? (int)$val : null);

        $affected = Connection::execute($sql);
        return "Saved: " . $affected;
    }

    /**
     * Get language data from request using InputValidator.
     *
     * @return array<string, string|int|bool>
     */
    private function getLanguageDataFromRequest(): array
    {
        return [
            'LgName' => InputValidator::getString('LgName'),
            'LgDict1URI' => InputValidator::getString('LgDict1URI'),
            'LgDict2URI' => InputValidator::getString('LgDict2URI'),
            'LgGoogleTranslateURI' => InputValidator::getString('LgGoogleTranslateURI'),
            'LgExportTemplate' => InputValidator::getString('LgExportTemplate'),
            'LgTextSize' => InputValidator::getString('LgTextSize', '100'),
            'LgCharacterSubstitutions' => InputValidator::getString('LgCharacterSubstitutions', '', false),
            'LgRegexpSplitSentences' => InputValidator::getString('LgRegexpSplitSentences'),
            'LgExceptionsSplitSentences' => InputValidator::getString('LgExceptionsSplitSentences', '', false),
            'LgRegexpWordCharacters' => InputValidator::getString('LgRegexpWordCharacters'),
            'LgRemoveSpaces' => InputValidator::has('LgRemoveSpaces'),
            'LgSplitEachChar' => InputValidator::has('LgSplitEachChar'),
            'LgRightToLeft' => InputValidator::has('LgRightToLeft'),
            'LgTTSVoiceAPI' => InputValidator::getString('LgTTSVoiceAPI'),
            'LgShowRomanization' => InputValidator::has('LgShowRomanization'),
        ];
    }

    /**
     * Build SQL for inserting or updating a language.
     *
     * @param array    $data Language data
     * @param int|null $id   Language ID for update, null for insert
     *
     * @return string SQL query
     */
    private function buildLanguageSql(array $data, ?int $id = null): string
    {
        $tbpref = Globals::getTablePrefix();

        $fields = [
            'LgName' => Escaping::toSqlSyntax($data["LgName"]),
            'LgDict1URI' => Escaping::toSqlSyntax($data["LgDict1URI"]),
            'LgDict2URI' => Escaping::toSqlSyntax($data["LgDict2URI"]),
            'LgGoogleTranslateURI' => Escaping::toSqlSyntax($data["LgGoogleTranslateURI"]),
            'LgExportTemplate' => Escaping::toSqlSyntax($data["LgExportTemplate"]),
            'LgTextSize' => Escaping::toSqlSyntax($data["LgTextSize"]),
            'LgCharacterSubstitutions' => Escaping::toSqlSyntaxNoTrimNoNull($data["LgCharacterSubstitutions"]),
            'LgRegexpSplitSentences' => Escaping::toSqlSyntax($data["LgRegexpSplitSentences"]),
            'LgExceptionsSplitSentences' => Escaping::toSqlSyntaxNoTrimNoNull($data["LgExceptionsSplitSentences"]),
            'LgRegexpWordCharacters' => Escaping::toSqlSyntax($data["LgRegexpWordCharacters"]),
            'LgRemoveSpaces' => (int)isset($data["LgRemoveSpaces"]),
            'LgSplitEachChar' => (int)isset($data["LgSplitEachChar"]),
            'LgRightToLeft' => (int)isset($data["LgRightToLeft"]),
            'LgTTSVoiceAPI' => Escaping::toSqlSyntaxNoNull($data["LgTTSVoiceAPI"]),
            'LgShowRomanization' => (int)isset($data["LgShowRomanization"]),
        ];

        if ($id === null) {
            // INSERT
            $columns = implode(', ', array_keys($fields));
            $values = implode(', ', array_values($fields));
            return "INSERT INTO {$tbpref}languages ($columns) VALUES($values)";
        }

        // UPDATE
        $setParts = [];
        foreach ($fields as $key => $value) {
            $setParts[] = "$key = $value";
        }
        return "UPDATE {$tbpref}languages SET " . implode(', ', $setParts) . " WHERE LgID = $id";
    }

    /**
     * Update an existing language.
     *
     * @param int $lid Language ID
     *
     * @return string Result message
     */
    public function update(int $lid): string
    {
        $tbpref = Globals::getTablePrefix();
        $data = $this->getLanguageDataFromRequest();

        // Get old values for comparison
        $sql = "SELECT * FROM {$tbpref}languages where LgID = $lid";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        if ($record === false || $record === null) {
            return "Cannot access language data";
        }

        // Check if reparsing is needed
        $needReParse = $this->needsReparsing($data, $record);

        mysqli_free_result($res);

        // Update language
        $updateSql = $this->buildLanguageSql($data, $lid);
        $affected = Connection::execute($updateSql);
        $message = "Updated: " . $affected;

        if ($needReParse) {
            $reparseCount = $this->reparseTexts($lid);
            $message .= " / Reparsed texts: " . $reparseCount;
        } else {
            $message .= " / Reparsing not needed";
        }

        return $message;
    }

    /**
     * Check if language changes require reparsing texts.
     *
     * @param array $newData   New language data
     * @param array $oldRecord Old language data
     *
     * @return bool
     */
    private function needsReparsing(array $newData, array $oldRecord): bool
    {
        return (
            Escaping::toSqlSyntaxNoTrimNoNull($newData["LgCharacterSubstitutions"])
            != Escaping::toSqlSyntaxNoTrimNoNull($oldRecord['LgCharacterSubstitutions'])
        ) || (
            Escaping::toSqlSyntax($newData["LgRegexpSplitSentences"]) !=
            Escaping::toSqlSyntax($oldRecord['LgRegexpSplitSentences'])
        ) || (
            Escaping::toSqlSyntaxNoTrimNoNull($newData["LgExceptionsSplitSentences"])
            != Escaping::toSqlSyntaxNoTrimNoNull($oldRecord['LgExceptionsSplitSentences'])
        ) || (
            Escaping::toSqlSyntax($newData["LgRegexpWordCharacters"]) !=
            Escaping::toSqlSyntax($oldRecord['LgRegexpWordCharacters'])
        ) || ((isset($newData["LgRemoveSpaces"]) ? 1 : 0) != $oldRecord['LgRemoveSpaces']) ||
        ((isset($newData["LgSplitEachChar"]) ? 1 : 0) != $oldRecord['LgSplitEachChar']);
    }

    /**
     * Reparse all texts for a language.
     *
     * @param int $lid Language ID
     *
     * @return int Number of reparsed texts
     */
    private function reparseTexts(int $lid): int
    {
        QueryBuilder::table('sentences')
            ->where('SeLgID', '=', $lid)
            ->delete();
        QueryBuilder::table('textitems2')
            ->where('Ti2LgID', '=', $lid)
            ->delete();
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $tbpref = Globals::getTablePrefix();
        Connection::execute("UPDATE {$tbpref}words SET WoWordCount = 0 WHERE WoLgID = $lid");
        Maintenance::initWordCount();

        $sql = "SELECT TxID, TxText FROM {$tbpref}texts
        WHERE TxLgID = $lid ORDER BY TxID";
        $res = Connection::query($sql);
        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $txtid = (int)$record["TxID"];
            $txttxt = (string)$record["TxText"];
            TextParsing::splitCheck($txttxt, $lid, $txtid);
            $count++;
        }
        mysqli_free_result($res);

        return $count;
    }

    /**
     * Delete a language.
     *
     * @param int $lid Language ID
     *
     * @return string Result message
     */
    public function delete(int $lid): string
    {
        // Check for related data
        $stats = $this->getRelatedDataCounts($lid);

        if ($stats['texts'] > 0 || $stats['archivedTexts'] > 0 ||
            $stats['words'] > 0 || $stats['feeds'] > 0) {
            return 'You must first delete texts, archived texts, newsfeeds and words with this language!';
        }

        $affected = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->delete();
        return "Deleted: " . $affected;
    }

    /**
     * Get counts of related data for a language.
     *
     * @param int $lid Language ID
     *
     * @return array{texts: int, archivedTexts: int, words: int, feeds: int}
     */
    public function getRelatedDataCounts(int $lid): array
    {
        $tbpref = Globals::getTablePrefix();

        return [
            'texts' => (int) Connection::fetchValue(
                "SELECT count(TxID) as value FROM {$tbpref}texts where TxLgID = $lid"
            ),
            'archivedTexts' => (int) Connection::fetchValue(
                "SELECT count(AtID) as value FROM {$tbpref}archivedtexts where AtLgID = $lid"
            ),
            'words' => (int) Connection::fetchValue(
                "SELECT count(WoID) as value FROM {$tbpref}words where WoLgID = $lid"
            ),
            'feeds' => (int) Connection::fetchValue(
                "SELECT count(NfID) as value FROM {$tbpref}newsfeeds where NfLgID = $lid"
            ),
        ];
    }

    /**
     * Refresh (reparse) all texts for a language.
     *
     * @param int $lid Language ID
     *
     * @return string Result message
     */
    public function refresh(int $lid): string
    {
        $sentencesDeleted = QueryBuilder::table('sentences')
            ->where('SeLgID', '=', $lid)
            ->delete();
        $textItemsDeleted = QueryBuilder::table('textitems2')
            ->where('Ti2LgID', '=', $lid)
            ->delete();
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        $tbpref = Globals::getTablePrefix();
        $sql = "select TxID, TxText from {$tbpref}texts
        where TxLgID = $lid
        order by TxID";
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $txtid = (int)$record["TxID"];
            $txttxt = (string)$record["TxText"];
            TextParsing::splitCheck($txttxt, $lid, $txtid);
        }
        mysqli_free_result($res);

        $sentencesAdded = Connection::fetchValue(
            "SELECT count(*) as value FROM {$tbpref}sentences where SeLgID = $lid"
        );
        $textItemsAdded = Connection::fetchValue(
            "SELECT count(*) as value FROM {$tbpref}textitems2 where Ti2LgID = $lid"
        );

        return "Sentences deleted: " . $sentencesDeleted .
            " / Text items deleted: " . $textItemsDeleted .
            " / Sentences added: " . $sentencesAdded .
            " / Text items added: " . $textItemsAdded;
    }

    /**
     * Check if a language name is duplicate.
     *
     * @param string $name         Language name
     * @param int    $excludeLgId  Language ID to exclude from check (for updates)
     *
     * @return bool
     */
    public function isDuplicateName(string $name, int $excludeLgId = 0): bool
    {
        $tbpref = Globals::getTablePrefix();
        $escapedName = Escaping::toSqlSyntax($name);

        $sql = "SELECT LgID as value FROM {$tbpref}languages WHERE LgName = $escapedName";
        if ($excludeLgId > 0) {
            $sql .= " AND LgID != $excludeLgId";
        }

        $result = Connection::fetchValue($sql . " LIMIT 1");
        return $result !== null;
    }

    /**
     * Get languages with statistics for display.
     *
     * @return array Language data with counts
     */
    public function getLanguagesWithStats(): array
    {
        $tbpref = Globals::getTablePrefix();

        // Get base language data
        $sql = "SELECT LgID, LgName, LgExportTemplate
        FROM {$tbpref}languages
        WHERE LgName<>'' ORDER BY LgName";
        $res = Connection::query($sql);

        // Get feed counts
        $feedCounts = $this->getFeedCounts();
        $articleCounts = $this->getArticleCounts();

        $languages = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $lid = (int)$record['LgID'];
            $stats = $this->getRelatedDataCounts($lid);

            $languages[] = [
                'id' => $lid,
                'name' => $record['LgName'],
                'hasExportTemplate' => !empty($record['LgExportTemplate']),
                'textCount' => $stats['texts'],
                'archivedTextCount' => $stats['archivedTexts'],
                'wordCount' => $stats['words'],
                'feedCount' => $feedCounts[$lid] ?? 0,
                'articleCount' => $articleCounts[$lid] ?? 0,
            ];
        }
        mysqli_free_result($res);

        return $languages;
    }

    /**
     * Get feed counts per language.
     *
     * @return array<int, int> Language ID => feed count
     */
    private function getFeedCounts(): array
    {
        $tbpref = Globals::getTablePrefix();
        $res = Connection::query(
            "SELECT NfLgID, count(*) as value FROM {$tbpref}newsfeeds group by NfLgID"
        );
        $counts = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $counts[(int)$record['NfLgID']] = (int)$record['value'];
        }
        mysqli_free_result($res);
        return $counts;
    }

    /**
     * Get article counts per language.
     *
     * @return array<int, int> Language ID => article count
     */
    private function getArticleCounts(): array
    {
        $tbpref = Globals::getTablePrefix();
        $res = Connection::query(
            "SELECT NfLgID, count(*) AS value
            FROM {$tbpref}newsfeeds, {$tbpref}feedlinks
            WHERE NfID=FlNfID
            GROUP BY NfLgID"
        );
        $counts = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $counts[(int)$record['NfLgID']] = (int)$record['value'];
        }
        mysqli_free_result($res);
        return $counts;
    }

    /**
     * Check if a language can be deleted (no related data).
     *
     * @param int $lid Language ID
     *
     * @return bool
     */
    public function canDelete(int $lid): bool
    {
        $stats = $this->getRelatedDataCounts($lid);
        return $stats['texts'] === 0 &&
               $stats['archivedTexts'] === 0 &&
               $stats['words'] === 0 &&
               $stats['feeds'] === 0;
    }

    // =========================================================================
    // Methods migrated from Core/Language/language_utilities.php
    // =========================================================================

    /**
     * Get language name from its ID.
     *
     * @param string|int $lid Language ID
     *
     * @return string Language name, empty string if not found
     */
    public function getLanguageName($lid): string
    {
        $tbpref = Globals::getTablePrefix();
        if (is_int($lid)) {
            $lg_id = $lid;
        } elseif (isset($lid) && trim($lid) != '' && ctype_digit($lid)) {
            $lg_id = (int) $lid;
        } else {
            return '';
        }
        $r = Connection::fetchValue(
            "SELECT LgName AS value
            FROM {$tbpref}languages
            WHERE LgID = $lg_id"
        );
        if (isset($r)) {
            return (string)$r;
        }
        return '';
    }

    /**
     * Try to get language code from its ID.
     *
     * @param int   $lgId           Language ID
     * @param array $languagesTable Table of languages, usually from LanguageDefinitions::getAll()
     *
     * @return string Two-letter code (e.g., BCP 47) or empty string
     */
    public function getLanguageCode(int $lgId, array $languagesTable): string
    {
        $tbpref = Globals::getTablePrefix();
        $query = "SELECT LgName, LgGoogleTranslateURI
        FROM {$tbpref}languages
        WHERE LgID = $lgId";

        $res = Connection::query($query);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if ($record === null) {
            return '';
        }

        $lgName = (string) $record["LgName"];
        $translatorUri = (string) $record["LgGoogleTranslateURI"];

        // If we are using a standard language name, use it
        if (array_key_exists($lgName, $languagesTable)) {
            return $languagesTable[$lgName][1];
        }

        // Otherwise, use the translator URL
        $lgFromDict = UrlUtilities::langFromDict($translatorUri);
        if ($lgFromDict != '') {
            return $lgFromDict;
        }
        return '';
    }

    /**
     * Return a right-to-left direction indication in HTML if language is RTL.
     *
     * @param string|int|null $lid Language ID
     *
     * @return string ' dir="rtl" ' or empty string
     *
     * @psalm-return ' dir="rtl" '|''
     */
    public function getScriptDirectionTag($lid): string
    {
        $tbpref = Globals::getTablePrefix();
        if (!isset($lid)) {
            return '';
        }
        if (is_string($lid)) {
            if (trim($lid) == '' || !is_numeric($lid)) {
                return '';
            }
            $lg_id = (int) $lid;
        } else {
            $lg_id = $lid;
        }
        $r = Connection::fetchValue(
            "SELECT LgRightToLeft as value
            from {$tbpref}languages
            where LgID = $lg_id"
        );
        if (isset($r) && $r) {
            return ' dir="rtl" ';
        }
        return '';
    }

    // =========================================================================
    // Methods migrated from Core/Language/phonetic_reading.php
    // =========================================================================

    /**
     * Convert text to phonetic representation using MeCab (for Japanese).
     *
     * @param string $text  Text to be converted
     * @param int    $lgId  Language ID
     *
     * @return string Parsed text in phonetic format
     */
    public function getPhoneticReadingById(string $text, int $lgId): string
    {
        $tbpref = Globals::getTablePrefix();
        $sentenceSplit = Connection::fetchValue(
            "SELECT LgRegexpWordCharacters AS value FROM {$tbpref}languages
            WHERE LgID = $lgId"
        );

        // For now we only support phonetic text with MeCab
        if ($sentenceSplit != "mecab") {
            return $text;
        }

        return $this->processMecabPhonetic($text, $tbpref);
    }

    /**
     * Convert text to phonetic representation by language code.
     *
     * @param string $text Text to be converted
     * @param string $lang Language code (usually BCP 47 or ISO 639-1)
     *
     * @return string Parsed text in phonetic format
     */
    public function getPhoneticReadingByCode(string $text, string $lang): string
    {
        $tbpref = Globals::getTablePrefix();
        // Many languages are already phonetic
        if (!str_starts_with($lang, "ja") && !str_starts_with($lang, "jp")) {
            return $text;
        }

        return $this->processMecabPhonetic($text, $tbpref);
    }

    /**
     * Process text through MeCab for phonetic reading.
     *
     * @param string $text   Text to process
     * @param string $tbpref Table prefix
     *
     * @return string Phonetic reading from MeCab
     */
    private function processMecabPhonetic(string $text, string $tbpref): string
    {
        $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
        $mecab_args = ' -O yomi ';
        if (file_exists($mecab_file)) {
            unlink($mecab_file);
        }
        $fp = fopen($mecab_file, 'w');
        fwrite($fp, $text . "\n");
        fclose($fp);
        $mecab = \get_mecab_path($mecab_args);
        $handle = popen($mecab . $mecab_file, "r");
        $mecab_str = '';
        while (($line = fgets($handle, 4096)) !== false) {
            $mecab_str .= $line;
        }
        if (!feof($handle)) {
            echo "Error: unexpected fgets() fail\n";
        }
        pclose($handle);
        unlink($mecab_file);
        return $mecab_str;
    }

    // =========================================================================
    // Methods migrated from Core/UI/ui_helpers.php
    // =========================================================================

    /**
     * Get languages formatted for select dropdown options.
     *
     * @return array<int, array{id: int, name: string}> Array of language data
     */
    public function getLanguagesForSelect(): array
    {
        $result = [];
        $sql = "SELECT LgID, LgName FROM " . Globals::table('languages')
             . " WHERE LgName<>'' ORDER BY LgName";
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $name = (string)$record['LgName'];
            if (strlen($name) > 30) {
                $name = substr($name, 0, 30) . '...';
            }
            $result[] = [
                'id' => (int)$record['LgID'],
                'name' => $name
            ];
        }
        mysqli_free_result($res);
        return $result;
    }
}
