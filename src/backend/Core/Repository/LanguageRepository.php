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

use Lwt\Classes\Language;

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
        $language = new Language();
        $language->id = (int) $row['LgID'];
        $language->name = (string) $row['LgName'];
        $language->dict1uri = (string) $row['LgDict1URI'];
        $language->dict2uri = (string) $row['LgDict2URI'];
        $language->translator = (string) $row['LgGoogleTranslateURI'];
        $language->exporttemplate = (string) $row['LgExportTemplate'];
        $language->textsize = (int) $row['LgTextSize'];
        $language->charactersubst = (string) $row['LgCharacterSubstitutions'];
        $language->regexpsplitsent = (string) $row['LgRegexpSplitSentences'];
        $language->exceptionsplitsent = (string) $row['LgExceptionsSplitSentences'];
        $language->regexpwordchar = (string) $row['LgRegexpWordCharacters'];
        $language->removespaces = (bool) $row['LgRemoveSpaces'];
        $language->spliteachchar = (bool) $row['LgSplitEachChar'];
        $language->rightoleft = (bool) $row['LgRightToLeft'];
        $language->ttsvoiceapi = (string) $row['LgTTSVoiceAPI'];
        $language->showromanization = (bool) $row['LgShowRomanization'];

        return $language;
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
            'LgID' => $entity->id,
            'LgName' => $entity->name,
            'LgDict1URI' => $entity->dict1uri,
            'LgDict2URI' => $entity->dict2uri,
            'LgGoogleTranslateURI' => $entity->translator,
            'LgExportTemplate' => $entity->exporttemplate,
            'LgTextSize' => $entity->textsize,
            'LgCharacterSubstitutions' => $entity->charactersubst,
            'LgRegexpSplitSentences' => $entity->regexpsplitsent,
            'LgExceptionsSplitSentences' => $entity->exceptionsplitsent,
            'LgRegexpWordCharacters' => $entity->regexpwordchar,
            'LgRemoveSpaces' => (int) $entity->removespaces,
            'LgSplitEachChar' => (int) $entity->spliteachchar,
            'LgRightToLeft' => (int) $entity->rightoleft,
            'LgTTSVoiceAPI' => $entity->ttsvoiceapi,
            'LgShowRomanization' => (int) $entity->showromanization,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param Language $entity
     */
    protected function getEntityId(object $entity): int
    {
        return $entity->id ?? 0;
    }

    /**
     * {@inheritdoc}
     *
     * @param Language $entity
     */
    protected function setEntityId(object $entity, int $id): void
    {
        $entity->id = $id;
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
     * Create a new empty language entity.
     *
     * @return Language
     */
    public function createEmpty(): Language
    {
        $language = new Language();
        $language->id = 0;
        $language->name = '';
        $language->dict1uri = '';
        $language->dict2uri = '';
        $language->translator = '';
        $language->exporttemplate = '';
        $language->textsize = 100;
        $language->charactersubst = '';
        $language->regexpsplitsent = '';
        $language->exceptionsplitsent = '';
        $language->regexpwordchar = '';
        $language->removespaces = false;
        $language->spliteachchar = false;
        $language->rightoleft = false;
        $language->ttsvoiceapi = '';
        $language->showromanization = true;

        return $language;
    }
}
