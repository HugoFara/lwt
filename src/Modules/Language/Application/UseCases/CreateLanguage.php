<?php declare(strict_types=1);
/**
 * Create Language Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Language\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Language\Application\UseCases;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Modules\Language\Domain\LanguageRepositoryInterface;
use Lwt\Modules\Language\Infrastructure\MySqlLanguageRepository;

/**
 * Use case for creating a new language.
 *
 * @since 3.0.0
 */
class CreateLanguage
{
    private LanguageRepositoryInterface $repository;

    /**
     * @param LanguageRepositoryInterface|null $repository Repository instance
     */
    public function __construct(?LanguageRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new MySqlLanguageRepository();
    }

    /**
     * Create a new language from request data.
     *
     * @return string Result message
     */
    public function execute(): string
    {
        $data = $this->getLanguageDataFromRequest();

        // Check if there's an empty language record to reuse
        $row = QueryBuilder::table('languages')
            ->selectRaw('MIN(LgID) AS min_id')
            ->where('LgName', '=', '')
            ->firstPrepared();
        $val = $row['min_id'] ?? null;

        $this->buildLanguageSql($data, $val !== null ? (int)$val : null);

        return "Saved: 1";
    }

    /**
     * Create a new language from data array (API-friendly version).
     *
     * @param array $data Language data (camelCase keys)
     *
     * @return int Created language ID, or 0 on failure
     */
    public function createFromData(array $data): int
    {
        $normalizedData = $this->normalizeLanguageData($data);

        // Check if there's an empty language record to reuse
        $row = QueryBuilder::table('languages')
            ->selectRaw('MIN(LgID) AS min_id')
            ->where('LgName', '=', '')
            ->firstPrepared();
        $val = $row['min_id'] ?? null;

        $this->buildLanguageSql($normalizedData, $val !== null ? (int)$val : null);

        if ($val !== null) {
            return (int)$val;
        }

        $row = QueryBuilder::table('languages')
            ->selectRaw('MAX(LgID) AS max_id')
            ->firstPrepared();
        return (int)($row['max_id'] ?? 0);
    }

    /**
     * Get language data from request using InputValidator.
     *
     * @return array<string, string|int|bool|null>
     */
    public function getLanguageDataFromRequest(): array
    {
        return [
            'LgName' => InputValidator::getString('LgName'),
            'LgDict1URI' => InputValidator::getString('LgDict1URI'),
            'LgDict2URI' => InputValidator::getString('LgDict2URI'),
            'LgGoogleTranslateURI' => InputValidator::getString('LgGoogleTranslateURI'),
            'LgDict1PopUp' => InputValidator::has('LgDict1PopUp'),
            'LgDict2PopUp' => InputValidator::has('LgDict2PopUp'),
            'LgGoogleTranslatePopUp' => InputValidator::has('LgGoogleTranslatePopUp'),
            'LgSourceLang' => InputValidator::getString('LgSourceLang') ?: null,
            'LgTargetLang' => InputValidator::getString('LgTargetLang') ?: null,
            'LgExportTemplate' => InputValidator::getString('LgExportTemplate'),
            'LgTextSize' => InputValidator::getString('LgTextSize', '100'),
            'LgCharacterSubstitutions' => InputValidator::getString('LgCharacterSubstitutions', '', false),
            'LgRegexpSplitSentences' => InputValidator::getString('LgRegexpSplitSentences'),
            'LgExceptionsSplitSentences' => InputValidator::getString('LgExceptionsSplitSentences', '', false),
            'LgRegexpWordCharacters' => InputValidator::getString('LgRegexpWordCharacters'),
            'LgParserType' => InputValidator::getString('LgParserType') ?: null,
            'LgRemoveSpaces' => InputValidator::has('LgRemoveSpaces'),
            'LgSplitEachChar' => InputValidator::has('LgSplitEachChar'),
            'LgRightToLeft' => InputValidator::has('LgRightToLeft'),
            'LgTTSVoiceAPI' => InputValidator::getString('LgTTSVoiceAPI'),
            'LgShowRomanization' => InputValidator::has('LgShowRomanization'),
            'LgLocalDictMode' => (int) InputValidator::getString('LgLocalDictMode', '0'),
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
            'LgDict1PopUp' => !empty($data['dict1PopUp']),
            'LgDict2PopUp' => !empty($data['dict2PopUp']),
            'LgGoogleTranslatePopUp' => !empty($data['translatorPopUp']),
            'LgSourceLang' => $data['sourceLang'] ?? null,
            'LgTargetLang' => $data['targetLang'] ?? null,
            'LgExportTemplate' => $data['exportTemplate'] ?? '',
            'LgTextSize' => (string)($data['textSize'] ?? '100'),
            'LgCharacterSubstitutions' => $data['characterSubstitutions'] ?? '',
            'LgRegexpSplitSentences' => $data['regexpSplitSentences'] ?? '.!?',
            'LgExceptionsSplitSentences' => $data['exceptionsSplitSentences'] ?? '',
            'LgRegexpWordCharacters' => $data['regexpWordCharacters'] ?? 'a-zA-Z',
            'LgParserType' => $data['parserType'] ?? null,
            'LgRemoveSpaces' => !empty($data['removeSpaces']),
            'LgSplitEachChar' => !empty($data['splitEachChar']),
            'LgRightToLeft' => !empty($data['rightToLeft']),
            'LgTTSVoiceAPI' => $data['ttsVoiceApi'] ?? '',
            'LgShowRomanization' => $data['showRomanization'] ?? false,
            'LgLocalDictMode' => (int)($data['localDictMode'] ?? 0),
        ];
    }

    /**
     * Convert empty strings to null.
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
     * Build SQL and execute insert or update for a language.
     *
     * @param array    $data Language data
     * @param int|null $id   Language ID for update, null for insert
     *
     * @return void
     */
    private function buildLanguageSql(array $data, ?int $id = null): void
    {
        $columns = [
            'LgName', 'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
            'LgDict1PopUp', 'LgDict2PopUp', 'LgGoogleTranslatePopUp',
            'LgSourceLang', 'LgTargetLang',
            'LgExportTemplate', 'LgTextSize', 'LgCharacterSubstitutions',
            'LgRegexpSplitSentences', 'LgExceptionsSplitSentences',
            'LgRegexpWordCharacters', 'LgParserType', 'LgRemoveSpaces', 'LgSplitEachChar',
            'LgRightToLeft', 'LgTTSVoiceAPI', 'LgShowRomanization', 'LgLocalDictMode'
        ];

        $params = [
            $this->emptyToNull($data["LgName"]),
            $this->emptyToNull($data["LgDict1URI"]),
            $this->emptyToNull($data["LgDict2URI"]),
            $this->emptyToNull($data["LgGoogleTranslateURI"]),
            (int)($data["LgDict1PopUp"] ?? false),
            (int)($data["LgDict2PopUp"] ?? false),
            (int)($data["LgGoogleTranslatePopUp"] ?? false),
            $this->emptyToNull($data["LgSourceLang"] ?? null),
            $this->emptyToNull($data["LgTargetLang"] ?? null),
            $this->emptyToNull($data["LgExportTemplate"]),
            $this->emptyToNull($data["LgTextSize"]),
            $data["LgCharacterSubstitutions"],
            $this->emptyToNull($data["LgRegexpSplitSentences"]),
            $data["LgExceptionsSplitSentences"],
            $this->emptyToNull($data["LgRegexpWordCharacters"]),
            $data["LgParserType"] ?? null,
            (int)$data["LgRemoveSpaces"],
            (int)$data["LgSplitEachChar"],
            (int)$data["LgRightToLeft"],
            $data["LgTTSVoiceAPI"] ?? '',
            (int)$data["LgShowRomanization"],
            (int)($data["LgLocalDictMode"] ?? 0),
        ];

        $insertData = array_combine($columns, $params);

        if ($id === null) {
            QueryBuilder::table('languages')->insertPrepared($insertData);
        } else {
            QueryBuilder::table('languages')
                ->where('LgID', '=', $id)
                ->updatePrepared($insertData);
        }
    }
}
