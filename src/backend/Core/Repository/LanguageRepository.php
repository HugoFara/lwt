<?php declare(strict_types=1);
/**
 * Language Repository
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Repository
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Repository;

use Lwt\Core\Entity\Language;
use Lwt\Core\Entity\ValueObject\LanguageId;

/**
 * Repository for Language entities.
 *
 * Provides database access for language management operations.
 *
 * @extends AbstractRepository<Language>
 *
 * @since 3.0.0
 */
class LanguageRepository extends AbstractRepository
{
    /**
     * @var string Table name without prefix
     */
    protected string $tableName = 'languages';

    /**
     * @var string Primary key column
     */
    protected string $primaryKey = 'LgID';

    /**
     * @var array<string, string> Property to column mapping
     */
    protected array $columnMap = [
        'id' => 'LgID',
        'name' => 'LgName',
        'dict1uri' => 'LgDict1URI',
        'dict2uri' => 'LgDict2URI',
        'translator' => 'LgGoogleTranslateURI',
        'exporttemplate' => 'LgExportTemplate',
        'textsize' => 'LgTextSize',
        'charactersubst' => 'LgCharacterSubstitutions',
        'regexpsplitsent' => 'LgRegexpSplitSentences',
        'exceptionsplitsent' => 'LgExceptionsSplitSentences',
        'regexpwordchar' => 'LgRegexpWordCharacters',
        'removespaces' => 'LgRemoveSpaces',
        'spliteachchar' => 'LgSplitEachChar',
        'rightoleft' => 'LgRightToLeft',
        'ttsvoiceapi' => 'LgTTSVoiceAPI',
        'showromanization' => 'LgShowRomanization',
    ];

    /**
     * {@inheritdoc}
     */
    protected function mapToEntity(array $row): Language
    {
        return Language::reconstitute(
            (int) $row['LgID'],
            (string) $row['LgName'],
            (string) $row['LgDict1URI'],
            (string) ($row['LgDict2URI'] ?? ''),
            (string) ($row['LgGoogleTranslateURI'] ?? ''),
            (string) ($row['LgExportTemplate'] ?? ''),
            (int) ($row['LgTextSize'] ?? 100),
            (string) ($row['LgCharacterSubstitutions'] ?? ''),
            (string) $row['LgRegexpSplitSentences'],
            (string) ($row['LgExceptionsSplitSentences'] ?? ''),
            (string) $row['LgRegexpWordCharacters'],
            (bool) ($row['LgRemoveSpaces'] ?? false),
            (bool) ($row['LgSplitEachChar'] ?? false),
            (bool) ($row['LgRightToLeft'] ?? false),
            (string) ($row['LgTTSVoiceAPI'] ?? ''),
            (bool) ($row['LgShowRomanization'] ?? false)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param Language $entity
     *
     * @return array<string, mixed>
     */
    protected function mapToRow(object $entity): array
    {
        return [
            'LgID' => $entity->id()->toInt(),
            'LgName' => $entity->name(),
            'LgDict1URI' => $entity->dict1Uri(),
            'LgDict2URI' => $entity->dict2Uri(),
            'LgGoogleTranslateURI' => $entity->translatorUri(),
            'LgExportTemplate' => $entity->exportTemplate(),
            'LgTextSize' => $entity->textSize(),
            'LgCharacterSubstitutions' => $entity->characterSubstitutions(),
            'LgRegexpSplitSentences' => $entity->regexpSplitSentences(),
            'LgExceptionsSplitSentences' => $entity->exceptionsSplitSentences(),
            'LgRegexpWordCharacters' => $entity->regexpWordCharacters(),
            'LgRemoveSpaces' => (int) $entity->removeSpaces(),
            'LgSplitEachChar' => (int) $entity->splitEachChar(),
            'LgRightToLeft' => (int) $entity->rightToLeft(),
            'LgTTSVoiceAPI' => $entity->ttsVoiceApi(),
            'LgShowRomanization' => (int) $entity->showRomanization(),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param Language $entity
     */
    protected function getEntityId(object $entity): int
    {
        return $entity->id()->toInt();
    }

    /**
     * {@inheritdoc}
     *
     * @param Language $entity
     */
    protected function setEntityId(object $entity, int $id): void
    {
        $entity->setId(LanguageId::fromInt($id));
    }

    /**
     * Find all non-empty languages (those with a name).
     *
     * @param string $orderBy Column to order by (default: 'LgName')
     * @param string $direction Sort direction (default: 'ASC')
     *
     * @return Language[]
     */
    public function findAllActive(string $orderBy = 'LgName', string $direction = 'ASC'): array
    {
        $rows = $this->query()
            ->where('LgName', '!=', '')
            ->orderBy($orderBy, $direction)
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find a language by name.
     *
     * @param string $name Language name
     *
     * @return Language|null
     */
    public function findByName(string $name): ?Language
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Check if a language name exists.
     *
     * @param string   $name      Language name
     * @param int|null $excludeId Language ID to exclude (for updates)
     *
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = $this->query()->where('LgName', '=', $name);

        if ($excludeId !== null) {
            $query->where('LgID', '!=', $excludeId);
        }

        return $query->existsPrepared();
    }

    /**
     * Get languages as name => id dictionary.
     *
     * @return array<string, int>
     */
    public function getAllAsDict(): array
    {
        $languages = [];
        $rows = $this->query()
            ->select(['LgID', 'LgName'])
            ->where('LgName', '!=', '')
            ->getPrepared();

        foreach ($rows as $row) {
            $languages[(string) $row['LgName']] = (int) $row['LgID'];
        }

        return $languages;
    }

    /**
     * Get languages formatted for select dropdown options.
     *
     * @param int $maxNameLength Maximum name length before truncation
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getForSelect(int $maxNameLength = 30): array
    {
        $result = [];
        $rows = $this->query()
            ->select(['LgID', 'LgName'])
            ->where('LgName', '!=', '')
            ->orderBy('LgName')
            ->getPrepared();

        foreach ($rows as $row) {
            $name = (string) $row['LgName'];
            if (strlen($name) > $maxNameLength) {
                $name = substr($name, 0, $maxNameLength) . '...';
            }
            $result[] = [
                'id' => (int) $row['LgID'],
                'name' => $name,
            ];
        }

        return $result;
    }

    /**
     * Find the first empty language record (for reuse on insert).
     *
     * @return int|null The empty language ID or null
     */
    public function findEmptyLanguageId(): ?int
    {
        $row = $this->query()
            ->select('LgID')
            ->where('LgName', '=', '')
            ->orderBy('LgID')
            ->limit(1)
            ->firstPrepared();

        return $row !== null ? (int) $row['LgID'] : null;
    }

    /**
     * Check if a language is RTL (right-to-left).
     *
     * @param int $id Language ID
     *
     * @return bool
     */
    public function isRightToLeft(int $id): bool
    {
        $row = $this->query()
            ->select('LgRightToLeft')
            ->where('LgID', '=', $id)
            ->firstPrepared();

        return $row !== null && (bool) $row['LgRightToLeft'];
    }

    /**
     * Get the word character regex for a language.
     *
     * @param int $id Language ID
     *
     * @return string|null The regex or null if not found
     */
    public function getWordCharacters(int $id): ?string
    {
        $row = $this->query()
            ->select('LgRegexpWordCharacters')
            ->where('LgID', '=', $id)
            ->firstPrepared();

        return $row !== null ? (string) $row['LgRegexpWordCharacters'] : null;
    }

    /**
     * Create a new empty language entity with default values.
     *
     * @return Language
     */
    public function createEmpty(): Language
    {
        return Language::create(
            'New Language',
            '',
            '.!?',
            'a-zA-Z'
        );
    }

    /**
     * Get the name of a language by ID.
     *
     * @param int $id Language ID
     *
     * @return string|null The language name or null if not found
     */
    public function getName(int $id): ?string
    {
        $row = $this->query()
            ->select('LgName')
            ->where('LgID', '=', $id)
            ->firstPrepared();

        return $row !== null ? (string) $row['LgName'] : null;
    }

    /**
     * Get the translator URI for a language.
     *
     * @param int $id Language ID
     *
     * @return string|null The translator URI or null if not found
     */
    public function getTranslatorUri(int $id): ?string
    {
        $row = $this->query()
            ->select('LgGoogleTranslateURI')
            ->where('LgID', '=', $id)
            ->firstPrepared();

        return $row !== null ? (string) $row['LgGoogleTranslateURI'] : null;
    }
}
