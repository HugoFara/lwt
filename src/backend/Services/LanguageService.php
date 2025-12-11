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

        $record = Connection::preparedFetchOne(
            "SELECT * FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lid]
        );

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
     * Convert a Language entity to a view object (stdClass) for templates.
     *
     * Templates expect public properties rather than method accessors.
     *
     * @param Language $language The Language entity
     *
     * @return \stdClass View object with public properties
     */
    public function toViewObject(Language $language): \stdClass
    {
        $view = new \stdClass();
        $view->id = $language->id()->toInt();
        $view->name = $language->name();
        $view->dict1uri = $language->dict1Uri();
        $view->dict2uri = $language->dict2Uri();
        $view->translator = $language->translatorUri();
        $view->exporttemplate = $language->exportTemplate();
        $view->textsize = $language->textSize();
        $view->charactersubst = $language->characterSubstitutions();
        $view->regexpsplitsent = $language->regexpSplitSentences();
        $view->exceptionsplitsent = $language->exceptionsSplitSentences();
        $view->regexpwordchar = $language->regexpWordCharacters();
        $view->removespaces = $language->removeSpaces();
        $view->spliteachchar = $language->splitEachChar();
        $view->rightoleft = $language->rightToLeft();
        $view->ttsvoiceapi = $language->ttsVoiceApi();
        $view->showromanization = $language->showRomanization();
        return $view;
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
        $count = Connection::preparedFetchValue(
            "SELECT COUNT(*) as value FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lid]
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
        $data = $this->getLanguageDataFromRequest();

        // Check if there's an empty language record to reuse
        $val = Connection::fetchValue(
            "SELECT MIN(LgID) AS value FROM " . Globals::getTablePrefix() . "languages WHERE LgName=''"
        );

        $sqlData = $this->buildLanguageSql($data, $val !== null ? (int)$val : null);

        $affected = Connection::preparedExecute($sqlData['sql'], $sqlData['params']);
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
     * Convert empty strings to null (matches old Escaping::toSqlSyntax behavior).
     *
     * @param string|null $value Value to convert
     *
     * @return string|null Trimmed value or null if empty
     */
    private function emptyToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Build SQL and parameters for inserting or updating a language.
     *
     * @param array    $data Language data
     * @param int|null $id   Language ID for update, null for insert
     *
     * @return array{sql: string, params: array} SQL query and parameters
     */
    private function buildLanguageSql(array $data, ?int $id = null): array
    {
        $columns = [
            'LgName', 'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
            'LgExportTemplate', 'LgTextSize', 'LgCharacterSubstitutions',
            'LgRegexpSplitSentences', 'LgExceptionsSplitSentences',
            'LgRegexpWordCharacters', 'LgRemoveSpaces', 'LgSplitEachChar',
            'LgRightToLeft', 'LgTTSVoiceAPI', 'LgShowRomanization'
        ];

        $params = [
            $this->emptyToNull($data["LgName"]),
            $this->emptyToNull($data["LgDict1URI"]),
            $this->emptyToNull($data["LgDict2URI"]),
            $this->emptyToNull($data["LgGoogleTranslateURI"]),
            $this->emptyToNull($data["LgExportTemplate"]),
            $this->emptyToNull($data["LgTextSize"]),
            $data["LgCharacterSubstitutions"],  // No trim, keeps empty strings
            $this->emptyToNull($data["LgRegexpSplitSentences"]),
            $data["LgExceptionsSplitSentences"],  // No trim, keeps empty strings
            $this->emptyToNull($data["LgRegexpWordCharacters"]),
            (int)isset($data["LgRemoveSpaces"]),
            (int)isset($data["LgSplitEachChar"]),
            (int)isset($data["LgRightToLeft"]),
            $data["LgTTSVoiceAPI"] ?? '',  // This one uses empty string, not null
            (int)isset($data["LgShowRomanization"]),
        ];

        if ($id === null) {
            // INSERT
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO " . Globals::getTablePrefix() . "languages (" . implode(', ', $columns) . ") VALUES($placeholders)";
        } else {
            // UPDATE
            $setParts = array_map(fn($col) => "$col = ?", $columns);
            $sql = "UPDATE " . Globals::getTablePrefix() . "languages SET " . implode(', ', $setParts) . " WHERE LgID = ?";
            $params[] = $id;
        }

        return ['sql' => $sql, 'params' => $params];
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
        $data = $this->getLanguageDataFromRequest();

        // Get old values for comparison
        $record = Connection::preparedFetchOne(
            "SELECT * FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lid]
        );
        if ($record === false || $record === null) {
            return "Cannot access language data";
        }

        // Check if reparsing is needed
        $needReParse = $this->needsReparsing($data, $record);

        // Update language
        $sqlData = $this->buildLanguageSql($data, $lid);
        $affected = Connection::preparedExecute($sqlData['sql'], $sqlData['params']);
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
        // Compare values directly (no trim for character substitutions and exceptions)
        return (
            ($newData["LgCharacterSubstitutions"] ?? '')
            != ($oldRecord['LgCharacterSubstitutions'] ?? '')
        ) || (
            trim($newData["LgRegexpSplitSentences"] ?? '') !=
            trim($oldRecord['LgRegexpSplitSentences'] ?? '')
        ) || (
            ($newData["LgExceptionsSplitSentences"] ?? '')
            != ($oldRecord['LgExceptionsSplitSentences'] ?? '')
        ) || (
            trim($newData["LgRegexpWordCharacters"] ?? '') !=
            trim($oldRecord['LgRegexpWordCharacters'] ?? '')
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
        Connection::preparedExecute(
            "UPDATE " . Globals::getTablePrefix() . "words SET WoWordCount = 0 WHERE WoLgID = ?",
            [$lid]
        );
        Maintenance::initWordCount();

        $rows = Connection::preparedFetchAll(
            "SELECT TxID, TxText FROM " . Globals::getTablePrefix() . "texts WHERE TxLgID = ? ORDER BY TxID",
            [$lid]
        );
        $count = 0;
        foreach ($rows as $record) {
            $txtid = (int)$record["TxID"];
            $txttxt = (string)$record["TxText"];
            TextParsing::splitCheck($txttxt, $lid, $txtid);
            $count++;
        }

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
        return [
            'texts' => (int) Connection::preparedFetchValue(
                "SELECT count(TxID) as value FROM " . Globals::getTablePrefix() . "texts where TxLgID = ?",
                [$lid]
            ),
            'archivedTexts' => (int) Connection::preparedFetchValue(
                "SELECT count(AtID) as value FROM " . Globals::getTablePrefix() . "archivedtexts where AtLgID = ?",
                [$lid]
            ),
            'words' => (int) Connection::preparedFetchValue(
                "SELECT count(WoID) as value FROM " . Globals::getTablePrefix() . "words where WoLgID = ?",
                [$lid]
            ),
            'feeds' => (int) Connection::preparedFetchValue(
                "SELECT count(NfID) as value FROM " . Globals::getTablePrefix() . "newsfeeds where NfLgID = ?",
                [$lid]
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

        $rows = Connection::preparedFetchAll(
            "SELECT TxID, TxText FROM " . Globals::getTablePrefix() . "texts WHERE TxLgID = ? ORDER BY TxID",
            [$lid]
        );
        foreach ($rows as $record) {
            $txtid = (int)$record["TxID"];
            $txttxt = (string)$record["TxText"];
            TextParsing::splitCheck($txttxt, $lid, $txtid);
        }

        $sentencesAdded = Connection::preparedFetchValue(
            "SELECT count(*) as value FROM " . Globals::getTablePrefix() . "sentences where SeLgID = ?",
            [$lid]
        );
        $textItemsAdded = Connection::preparedFetchValue(
            "SELECT count(*) as value FROM " . Globals::getTablePrefix() . "textitems2 where Ti2LgID = ?",
            [$lid]
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
        $params = [trim($name)];

        $sql = "SELECT LgID as value FROM " . Globals::getTablePrefix() . "languages WHERE LgName = ?";
        if ($excludeLgId > 0) {
            $sql .= " AND LgID != ?";
            $params[] = $excludeLgId;
        }

        $result = Connection::preparedFetchValue($sql . " LIMIT 1", $params);
        return $result !== null;
    }

    /**
     * Get languages with statistics for display.
     *
     * @return array Language data with counts
     */
    public function getLanguagesWithStats(): array
    {
        // Get base language data
        $sql = "SELECT LgID, LgName, LgExportTemplate
        FROM " . Globals::getTablePrefix() . "languages
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
        $res = Connection::query(
            "SELECT NfLgID, count(*) as value FROM " . Globals::getTablePrefix() . "newsfeeds group by NfLgID"
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
        $res = Connection::query(
            "SELECT NfLgID, count(*) AS value
            FROM " . Globals::getTablePrefix() . "newsfeeds, " . Globals::getTablePrefix() . "feedlinks
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
        if (is_int($lid)) {
            $lg_id = $lid;
        } elseif (isset($lid) && trim($lid) != '' && ctype_digit($lid)) {
            $lg_id = (int) $lid;
        } else {
            return '';
        }
        $r = Connection::preparedFetchValue(
            "SELECT LgName AS value FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lg_id]
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
        $record = Connection::preparedFetchOne(
            "SELECT LgName, LgGoogleTranslateURI FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lgId]
        );

        if ($record === null || $record === false) {
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
        $r = Connection::preparedFetchValue(
            "SELECT LgRightToLeft as value FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lg_id]
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
        $sentenceSplit = Connection::preparedFetchValue(
            "SELECT LgRegexpWordCharacters AS value FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lgId]
        );

        // For now we only support phonetic text with MeCab
        if ($sentenceSplit != "mecab") {
            return $text;
        }

        return $this->processMecabPhonetic($text);
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
        // Many languages are already phonetic
        if (!str_starts_with($lang, "ja") && !str_starts_with($lang, "jp")) {
            return $text;
        }

        return $this->processMecabPhonetic($text);
    }

    /**
     * Process text through MeCab for phonetic reading.
     *
     * @param string $text Text to process
     *
     * @return string Phonetic reading from MeCab
     */
    private function processMecabPhonetic(string $text): string
    {
        $mecab_file = sys_get_temp_dir() . "/" . Globals::getTablePrefix() . "mecab_to_db.txt";
        $mecab_args = ' -O yomi ';
        if (file_exists($mecab_file)) {
            unlink($mecab_file);
        }
        $fp = fopen($mecab_file, 'w');
        fwrite($fp, $text . "\n");
        fclose($fp);
        $mecab = (new TextParsingService())->getMecabPath($mecab_args);
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

    /**
     * Get languages that have at least one text, with text counts.
     *
     * Returns only languages with texts (for display in grouped text list).
     *
     * @return array<int, array{id: int, name: string, text_count: int}>
     */
    public function getLanguagesWithTextCounts(): array
    {
        $sql = "SELECT l.LgID, l.LgName, COUNT(t.TxID) AS text_count
                FROM " . Globals::getTablePrefix() . "languages l
                INNER JOIN " . Globals::getTablePrefix() . "texts t ON t.TxLgID = l.LgID
                WHERE l.LgName <> ''
                GROUP BY l.LgID, l.LgName
                HAVING COUNT(t.TxID) > 0
                ORDER BY l.LgName";

        $res = Connection::query($sql);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $result[] = [
                'id' => (int)$record['LgID'],
                'name' => (string)$record['LgName'],
                'text_count' => (int)$record['text_count']
            ];
        }
        mysqli_free_result($res);
        return $result;
    }

    /**
     * Get languages that have at least one archived text, with archived text counts.
     *
     * Returns only languages with archived texts (for display in grouped archived text list).
     *
     * @return array<int, array{id: int, name: string, text_count: int}>
     */
    public function getLanguagesWithArchivedTextCounts(): array
    {
        $sql = "SELECT l.LgID, l.LgName, COUNT(a.AtID) AS text_count
                FROM " . Globals::getTablePrefix() . "languages l
                INNER JOIN " . Globals::getTablePrefix() . "archivedtexts a ON a.AtLgID = l.LgID
                WHERE l.LgName <> ''
                GROUP BY l.LgID, l.LgName
                HAVING COUNT(a.AtID) > 0
                ORDER BY l.LgName";

        $res = Connection::query($sql);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $result[] = [
                'id' => (int)$record['LgID'],
                'name' => (string)$record['LgName'],
                'text_count' => (int)$record['text_count']
            ];
        }
        mysqli_free_result($res);
        return $result;
    }

    // =========================================================================
    // API-friendly CRUD methods (accept data as parameter, not from request)
    // =========================================================================

    /**
     * Create a new language from data array.
     *
     * @param array $data Language data
     *
     * @return int Created language ID, or 0 on failure
     */
    public function createFromData(array $data): int
    {
        $normalizedData = $this->normalizeLanguageData($data);

        // Check if there's an empty language record to reuse
        $val = Connection::fetchValue(
            "SELECT MIN(LgID) AS value FROM " . Globals::getTablePrefix() . "languages WHERE LgName=''"
        );

        $sqlData = $this->buildLanguageSqlFromData($normalizedData, $val !== null ? (int)$val : null);
        Connection::preparedExecute($sqlData['sql'], $sqlData['params']);

        if ($val !== null) {
            return (int)$val;
        }

        return (int)Connection::fetchValue(
            "SELECT MAX(LgID) AS value FROM " . Globals::getTablePrefix() . "languages"
        );
    }

    /**
     * Update an existing language from data array.
     *
     * @param int   $lid  Language ID
     * @param array $data Language data
     *
     * @return array{success: bool, reparsed: int, message: string}
     */
    public function updateFromData(int $lid, array $data): array
    {
        $normalizedData = $this->normalizeLanguageData($data);

        // Get old values for comparison
        $record = Connection::preparedFetchOne(
            "SELECT * FROM " . Globals::getTablePrefix() . "languages WHERE LgID = ?",
            [$lid]
        );

        if ($record === false || $record === null) {
            return ['success' => false, 'reparsed' => 0, 'message' => 'Language not found'];
        }

        // Check if reparsing is needed
        $needReParse = $this->needsReparsingFromData($normalizedData, $record);

        // Update language
        $sqlData = $this->buildLanguageSqlFromData($normalizedData, $lid);
        Connection::preparedExecute($sqlData['sql'], $sqlData['params']);

        $reparsedCount = 0;
        if ($needReParse) {
            $reparsedCount = $this->reparseTexts($lid);
        }

        return [
            'success' => true,
            'reparsed' => $reparsedCount,
            'message' => $needReParse ? 'Updated and reparsed' : 'Updated'
        ];
    }

    /**
     * Delete a language by ID.
     *
     * @param int $lid Language ID
     *
     * @return bool True if deleted
     */
    public function deleteById(int $lid): bool
    {
        $affected = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->delete();
        return $affected > 0;
    }

    /**
     * Refresh (reparse) all texts for a language and return stats.
     *
     * @param int $lid Language ID
     *
     * @return array{sentencesDeleted: int, textItemsDeleted: int, sentencesAdded: int, textItemsAdded: int}
     */
    public function refreshTexts(int $lid): array
    {
        $sentencesDeleted = QueryBuilder::table('sentences')
            ->where('SeLgID', '=', $lid)
            ->delete();
        $textItemsDeleted = QueryBuilder::table('textitems2')
            ->where('Ti2LgID', '=', $lid)
            ->delete();
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        $rows = Connection::preparedFetchAll(
            "SELECT TxID, TxText FROM " . Globals::getTablePrefix() . "texts WHERE TxLgID = ? ORDER BY TxID",
            [$lid]
        );
        foreach ($rows as $record) {
            $txtid = (int)$record["TxID"];
            $txttxt = (string)$record["TxText"];
            TextParsing::splitCheck($txttxt, $lid, $txtid);
        }

        $sentencesAdded = (int)Connection::preparedFetchValue(
            "SELECT count(*) as value FROM " . Globals::getTablePrefix() . "sentences where SeLgID = ?",
            [$lid]
        );
        $textItemsAdded = (int)Connection::preparedFetchValue(
            "SELECT count(*) as value FROM " . Globals::getTablePrefix() . "textitems2 where Ti2LgID = ?",
            [$lid]
        );

        return [
            'sentencesDeleted' => $sentencesDeleted,
            'textItemsDeleted' => $textItemsDeleted,
            'sentencesAdded' => $sentencesAdded,
            'textItemsAdded' => $textItemsAdded
        ];
    }

    /**
     * Normalize language data from API request to database fields.
     *
     * @param array $data API request data (camelCase keys)
     *
     * @return array Normalized data (LgXxx keys)
     */
    private function normalizeLanguageData(array $data): array
    {
        return [
            'LgName' => $data['name'] ?? '',
            'LgDict1URI' => $data['dict1Uri'] ?? '',
            'LgDict2URI' => $data['dict2Uri'] ?? '',
            'LgGoogleTranslateURI' => $data['translatorUri'] ?? '',
            'LgExportTemplate' => $data['exportTemplate'] ?? '',
            'LgTextSize' => (string)($data['textSize'] ?? '100'),
            'LgCharacterSubstitutions' => $data['characterSubstitutions'] ?? '',
            'LgRegexpSplitSentences' => $data['regexpSplitSentences'] ?? '.!?',
            'LgExceptionsSplitSentences' => $data['exceptionsSplitSentences'] ?? '',
            'LgRegexpWordCharacters' => $data['regexpWordCharacters'] ?? 'a-zA-Z',
            'LgRemoveSpaces' => !empty($data['removeSpaces']),
            'LgSplitEachChar' => !empty($data['splitEachChar']),
            'LgRightToLeft' => !empty($data['rightToLeft']),
            'LgTTSVoiceAPI' => $data['ttsVoiceApi'] ?? '',
            'LgShowRomanization' => $data['showRomanization'] ?? true,
        ];
    }

    /**
     * Build SQL and parameters for inserting or updating a language from normalized data.
     *
     * @param array    $data Normalized language data
     * @param int|null $id   Language ID for update, null for insert
     *
     * @return array{sql: string, params: array} SQL query and parameters
     */
    private function buildLanguageSqlFromData(array $data, ?int $id = null): array
    {
        $columns = [
            'LgName', 'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
            'LgExportTemplate', 'LgTextSize', 'LgCharacterSubstitutions',
            'LgRegexpSplitSentences', 'LgExceptionsSplitSentences',
            'LgRegexpWordCharacters', 'LgRemoveSpaces', 'LgSplitEachChar',
            'LgRightToLeft', 'LgTTSVoiceAPI', 'LgShowRomanization'
        ];

        $params = [
            $this->emptyToNull($data["LgName"]),
            $this->emptyToNull($data["LgDict1URI"]),
            $this->emptyToNull($data["LgDict2URI"]),
            $this->emptyToNull($data["LgGoogleTranslateURI"]),
            $this->emptyToNull($data["LgExportTemplate"]),
            $this->emptyToNull($data["LgTextSize"]),
            $data["LgCharacterSubstitutions"],  // No trim, keeps empty strings
            $this->emptyToNull($data["LgRegexpSplitSentences"]),
            $data["LgExceptionsSplitSentences"],  // No trim, keeps empty strings
            $this->emptyToNull($data["LgRegexpWordCharacters"]),
            (int)$data["LgRemoveSpaces"],
            (int)$data["LgSplitEachChar"],
            (int)$data["LgRightToLeft"],
            $data["LgTTSVoiceAPI"] ?? '',  // This one uses empty string, not null
            (int)$data["LgShowRomanization"],
        ];

        if ($id === null) {
            // INSERT
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO " . Globals::getTablePrefix() . "languages (" . implode(', ', $columns) . ") VALUES($placeholders)";
        } else {
            // UPDATE
            $setParts = array_map(fn($col) => "$col = ?", $columns);
            $sql = "UPDATE " . Globals::getTablePrefix() . "languages SET " . implode(', ', $setParts) . " WHERE LgID = ?";
            $params[] = $id;
        }

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Check if language changes require reparsing texts (from normalized data).
     *
     * @param array $newData   New normalized language data
     * @param array $oldRecord Old language data from database
     *
     * @return bool
     */
    private function needsReparsingFromData(array $newData, array $oldRecord): bool
    {
        // Compare values directly (no trim for character substitutions and exceptions)
        return (
            ($newData["LgCharacterSubstitutions"] ?? '')
            != ($oldRecord['LgCharacterSubstitutions'] ?? '')
        ) || (
            trim($newData["LgRegexpSplitSentences"] ?? '') !=
            trim($oldRecord['LgRegexpSplitSentences'] ?? '')
        ) || (
            ($newData["LgExceptionsSplitSentences"] ?? '')
            != ($oldRecord['LgExceptionsSplitSentences'] ?? '')
        ) || (
            trim($newData["LgRegexpWordCharacters"] ?? '') !=
            trim($oldRecord['LgRegexpWordCharacters'] ?? '')
        ) || (
            (int)$newData["LgRemoveSpaces"] != (int)$oldRecord['LgRemoveSpaces']
        ) || (
            (int)$newData["LgSplitEachChar"] != (int)$oldRecord['LgSplitEachChar']
        );
    }
}
