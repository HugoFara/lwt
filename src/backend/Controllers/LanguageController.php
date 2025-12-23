<?php declare(strict_types=1);
/**
 * \file
 * \brief Language Controller - Language configuration
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-languagecontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\FormHelper;
use Lwt\View\Helper\IconHelper;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/FormHelper.php';
require_once __DIR__ . '/../Core/Http/UrlUtilities.php';
require_once __DIR__ . '/../Core/Entity/Language.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';

use Lwt\Database\Settings;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\LanguageService;
use Lwt\Core\Http\UrlUtilities;
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
    private LanguageService $languageService;

    /**
     * Create a new LanguageController.
     *
     * @param LanguageService $languageService Language service for language operations
     */
    public function __construct(LanguageService $languageService)
    {
        parent::__construct();
        $this->languageService = $languageService;
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
        PageLayoutHelper::renderPageStart('Languages', true);

        $message = '';

        // Handle actions
        $refreshId = $this->paramInt('refresh');
        if ($refreshId !== null) {
            $message = $this->languageService->refresh($refreshId);
        }

        $delId = $this->paramInt('del');
        $op = $this->param('op');
        if ($delId !== null) {
            $message = $this->languageService->delete($delId);
        } elseif ($op !== '') {
            if ($op === 'Save') {
                $message = $this->languageService->create();
            } elseif ($op === 'Change') {
                $lgId = $this->paramInt('LgID', 0) ?? 0;
                $message = $this->languageService->update($lgId);
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
        $languages = $this->languageService->getLanguagesWithStats();

        include __DIR__ . '/../Views/Language/index.php';
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
        $languageDefsJson = json_encode(LanguageDefinitions::getAll());

        ?>
        <h2>
            New Language
            <a target="_blank" href="docs/info.html#howtolang">
                <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
            </a>
        </h2>
        <?php

        include __DIR__ . '/../Views/Language/wizard.php';

        $languageEntity = $this->languageService->createEmptyLanguage();
        $language = $this->languageService->toViewObject($languageEntity);
        $sourceLg = '';
        $targetLg = '';
        $isNew = true;

        $this->prepareLanguageCodes($languageEntity, $currentNativeLanguage, $sourceLg, $targetLg);

        $allLanguages = $this->languageService->getAllLanguages();
        $parserInfo = (new ParserRegistry())->getParserInfo();

        include __DIR__ . '/../Views/Language/form.php';
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
        $languageEntity = $this->languageService->getById($lid);

        if ($languageEntity === null) {
            echo '<p class="red">Language not found.</p>';
            return;
        }

        $language = $this->languageService->toViewObject($languageEntity);
        $currentNativeLanguage = Settings::get('currentnativelanguage');
        $sourceLg = '';
        $targetLg = '';
        $isNew = false;

        $this->prepareLanguageCodes($languageEntity, $currentNativeLanguage, $sourceLg, $targetLg);

        $allLanguages = $this->languageService->getAllLanguages();
        $parserInfo = (new ParserRegistry())->getParserInfo();

        ?>
    <h2>Edit Language
        <a target="_blank" href="docs/info.html#howtolang">
            <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
        </a>
    </h2>
        <?php

        include __DIR__ . '/../Views/Language/form.php';
    }

    /**
     * Prepare source and target language codes for the form.
     *
     * @param \Lwt\Core\Entity\Language $language              Language object
     * @param string                    $currentNativeLanguage Current native language
     * @param string                    &$sourceLg             Output source language code
     * @param string                    &$targetLg             Output target language code
     *
     * @return void
     */
    private function prepareLanguageCodes(
        \Lwt\Core\Entity\Language $language,
        string $currentNativeLanguage,
        string &$sourceLg,
        string &$targetLg
    ): void {
        if (array_key_exists($currentNativeLanguage, LanguageDefinitions::getAll())) {
            $targetLg = LanguageDefinitions::getAll()[$currentNativeLanguage][1];
        }

        $langName = $language->name();
        if ($langName) {
            if (array_key_exists($langName, LanguageDefinitions::getAll())) {
                $sourceLg = LanguageDefinitions::getAll()[$langName][1];
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
        $keys = array_keys(LanguageDefinitions::getAll());
        foreach ($keys as $item) {
            $r .= '<option value="' . $item . '"' .
                FormHelper::getSelected($selected, $item) . '>' . $item . '</option>';
        }
        return $r;
    }

}
