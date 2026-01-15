<?php

/**
 * \file
 * \brief Language Controller - Language configuration
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Language\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-languagecontroller.html
 * @since   3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Language\Http;

use Lwt\Controllers\BaseController;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\FormHelper;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Domain\Language;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Core\Parser\ParserRegistry;

/**
 * Controller for language configuration.
 *
 * Handles:
 * - Language listing and management
 * - Language creation and editing
 * - Language deletion
 * - Text reparsing
 * - Language pair selection
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class LanguageController extends BaseController
{
    private LanguageFacade $languageFacade;

    /**
     * Create a new LanguageController.
     *
     * @param LanguageFacade $languageFacade Language facade for language operations
     */
    public function __construct(LanguageFacade $languageFacade)
    {
        parent::__construct();
        $this->languageFacade = $languageFacade;
    }

    /**
     * Languages index page - handles all language operations.
     *
     * Routes based on request parameters:
     * - new=1: Show new language form
     * - chg=[id]: Show edit form for language
     * - del=[id]: Delete language
     * - refresh=[id]: Reparse texts for language
     * - op=Save: Create new language
     * - op=Change: Update existing language
     * - (default): Show language list
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        // Handle new language creation with redirect (before any output)
        if ($this->param('op') === 'Save') {
            $result = $this->languageFacade->create();
            if ($result['success']) {
                // Redirect to text creation page after successful language creation
                header('Location: ' . url('/texts?new=1'));
                exit;
            }
            // On error, fall through to show the form with error message
        }

        PageLayoutHelper::renderPageStart('Languages', true);

        $message = '';

        // Handle actions
        $refreshId = $this->paramInt('refresh');
        if ($refreshId !== null) {
            $result = $this->languageFacade->refresh($refreshId);
            $message = "Sentences deleted: {$result['sentencesDeleted']} / " .
                "Text items deleted: {$result['textItemsDeleted']} / " .
                "Sentences added: {$result['sentencesAdded']} / " .
                "Text items added: {$result['textItemsAdded']}";
        }

        $delId = $this->paramInt('del');
        $op = $this->param('op');
        if ($delId !== null) {
            $result = $this->languageFacade->delete($delId);
            $message = $result['error'] ?? "Deleted: {$result['count']}";
        } elseif ($op !== '') {
            if ($op === 'Save') {
                // Save was already handled above (with redirect on success)
                // If we get here, it means there was an error
                $message = "Error creating language";
            } elseif ($op === 'Change') {
                $lgId = $this->paramInt('LgID', 0) ?? 0;
                $result = $this->languageFacade->update($lgId);
                if ($result['error'] !== null) {
                    $message = $result['error'];
                } elseif ($result['reparsed'] !== null) {
                    $message = "Updated: 1 / Reparsed texts: {$result['reparsed']}";
                } else {
                    $message = "Updated: 1 / Reparsing not needed";
                }
            }
        }

        // Display appropriate view
        if ($this->hasParam('new')) {
            $this->showNewForm();
        } elseif ($this->hasParam('chg')) {
            $this->showEditForm($this->paramInt('chg', 0) ?? 0);
        } else {
            $this->showList($message);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Show the list of languages.
     *
     * @param string $message Optional message to display
     *
     * @return void
     */
    private function showList(string $message): void
    {
        $this->message($message, false);

        $currentLanguageId = (int) Settings::get('currentlanguage');
        $languages = $this->languageFacade->getLanguagesWithStats();

        include __DIR__ . '/../Views/index.php';
    }

    /**
     * Show the new language form.
     *
     * @return void
     */
    private function showNewForm(): void
    {
        $currentNativeLanguage = Settings::get('currentnativelanguage');
        $languageOptions = $this->getWizardSelectOptions($currentNativeLanguage);
        $languageOptionsEmpty = $this->getWizardSelectOptions('');
        $languageDefsJson = json_encode(LanguagePresets::getAll());
        $languagePresetsArray = $this->getLanguagePresetsArray();

        ?>
        <h2>
            New Language
            <a target="_blank" href="docs/info.html#howtolang">
                <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
            </a>
        </h2>
        <?php

        include __DIR__ . '/../Views/wizard.php';

        $languageEntity = $this->languageFacade->createEmptyLanguage();
        $language = $this->languageFacade->toViewObject($languageEntity);
        $sourceLg = '';
        $targetLg = '';
        $isNew = true;

        $this->prepareLanguageCodes($languageEntity, $currentNativeLanguage, $sourceLg, $targetLg);

        $allLanguages = $this->languageFacade->getAllLanguages();
        $parserInfo = (new ParserRegistry())->getParserInfo();

        include __DIR__ . '/../Views/form.php';
    }

    /**
     * Show the edit language form.
     *
     * @param int $lid Language ID
     *
     * @return void
     */
    private function showEditForm(int $lid): void
    {
        $languageEntity = $this->languageFacade->getById($lid);

        if ($languageEntity === null) {
            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                'Language not found.</div>';
            return;
        }

        $language = $this->languageFacade->toViewObject($languageEntity);
        $currentNativeLanguage = Settings::get('currentnativelanguage');
        $sourceLg = '';
        $targetLg = '';
        $isNew = false;

        $this->prepareLanguageCodes($languageEntity, $currentNativeLanguage, $sourceLg, $targetLg);

        $allLanguages = $this->languageFacade->getAllLanguages();
        $parserInfo = (new ParserRegistry())->getParserInfo();

        ?>
    <h2>Edit Language
        <a target="_blank" href="docs/info.html#howtolang">
            <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
        </a>
    </h2>
        <?php

        include __DIR__ . '/../Views/form.php';
    }

    /**
     * Prepare source and target language codes for the form.
     *
     * @param Language $language              Language object
     * @param string   $currentNativeLanguage Current native language
     * @param string   &$sourceLg             Output source language code
     * @param string   &$targetLg             Output target language code
     *
     * @return void
     */
    private function prepareLanguageCodes(
        Language $language,
        string $currentNativeLanguage,
        string &$sourceLg,
        string &$targetLg
    ): void {
        if (array_key_exists($currentNativeLanguage, LanguagePresets::getAll())) {
            $targetLg = LanguagePresets::getAll()[$currentNativeLanguage][1];
        }

        $langName = $language->name();
        if ($langName) {
            if (array_key_exists($langName, LanguagePresets::getAll())) {
                $sourceLg = LanguagePresets::getAll()[$langName][1];
            }
            $lgFromDict = UrlUtilities::langFromDict($language->translatorUri());
            if ($lgFromDict != '' && $lgFromDict != $sourceLg) {
                $sourceLg = $lgFromDict;
            }

            $targetFromDict = UrlUtilities::targetLangFromDict($language->translatorUri());
            if ($targetFromDict != '' && $targetFromDict != $targetLg) {
                $targetLg = $targetFromDict;
            }
        }
    }

    /**
     * Generate wizard select options HTML.
     *
     * @param string $selected Currently selected value
     *
     * @return string HTML options
     */
    private function getWizardSelectOptions(string $selected): string
    {
        $r = '<option value=""' . FormHelper::getSelected($selected, '') . '>[Choose...]</option>';
        $keys = array_keys(LanguagePresets::getAll());
        foreach ($keys as $item) {
            $r .= '<option value="' . $item . '"' .
                FormHelper::getSelected($selected, $item) . '>' . $item . '</option>';
        }
        return $r;
    }

    /**
     * Get language presets as array for searchable select.
     *
     * @return array<int, array{id: int|string, name: string}>
     */
    private function getLanguagePresetsArray(): array
    {
        $presets = [];
        $keys = array_keys(LanguagePresets::getAll());
        foreach ($keys as $item) {
            $presets[] = ['id' => $item, 'name' => $item];
        }
        return $presets;
    }
}
