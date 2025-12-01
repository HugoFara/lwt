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
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Http/url_utilities.php';
require_once __DIR__ . '/../Core/Entity/Language.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';

use Lwt\Database\Settings;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\LanguageService;
use Lwt\Core\Http\UrlUtilities;

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

    public function __construct()
    {
        parent::__construct();
        $this->languageService = new LanguageService();
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
        PageLayoutHelper::renderPageStart('My Languages', true);

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

        $language = $this->languageService->createEmptyLanguage();
        $sourceLg = '';
        $targetLg = '';
        $isNew = true;

        $this->prepareLanguageCodes($language, $currentNativeLanguage, $sourceLg, $targetLg);

        $allLanguages = $this->languageService->getAllLanguages();

        include __DIR__ . '/../Views/Language/form.php';

        ?>
        <p class="smallgray">
            <b>Important:</b>
            <br />
            The placeholders "••" for the from/sl and dest/tl language codes in the
            URIs must be <b>replaced</b> by the actual source and target language
            codes!<br />
            <a href="docs/info.html#howtolang" target="_blank">Please read the documentation</a>.
            Languages with a <b>non-Latin alphabet need special attention</b>,
            <a href="docs/info.html#langsetup" target="_blank">see also here</a>.
        </p>
        <?php

        include __DIR__ . '/../Views/Language/voice_api_help.php';
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
        $language = $this->languageService->getById($lid);

        if ($language === null) {
            echo '<p class="red">Language not found.</p>';
            return;
        }

        $currentNativeLanguage = Settings::get('currentnativelanguage');
        $sourceLg = '';
        $targetLg = '';
        $isNew = false;

        $this->prepareLanguageCodes($language, $currentNativeLanguage, $sourceLg, $targetLg);

        $allLanguages = $this->languageService->getAllLanguages();

        ?>
    <h2>Edit Language
        <a target="_blank" href="docs/info.html#howtolang">
            <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
        </a>
    </h2>
        <?php

        include __DIR__ . '/../Views/Language/form.php';

        ?>
    <p class="smallgray">
        <b>Warning:</b> Changing certain language settings
        (e.g. RegExp Word Characters, etc.)<wbr />
        may cause partial or complete loss of improved annotated texts!
    </p>
        <?php

        include __DIR__ . '/../Views/Language/voice_api_help.php';
    }

    /**
     * Prepare source and target language codes for the form.
     *
     * @param \Lwt\Classes\Language $language              Language object
     * @param string                $currentNativeLanguage Current native language
     * @param string                &$sourceLg             Output source language code
     * @param string                &$targetLg             Output target language code
     *
     * @return void
     */
    private function prepareLanguageCodes(
        \Lwt\Classes\Language $language,
        string $currentNativeLanguage,
        string &$sourceLg,
        string &$targetLg
    ): void {
        if (array_key_exists($currentNativeLanguage, LanguageDefinitions::getAll())) {
            $targetLg = LanguageDefinitions::getAll()[$currentNativeLanguage][1];
        }

        if ($language->name) {
            if (array_key_exists($language->name, LanguageDefinitions::getAll())) {
                $sourceLg = LanguageDefinitions::getAll()[$language->name][1];
            }
            $lgFromDict = UrlUtilities::langFromDict($language->translator ?? '');
            if ($lgFromDict != '' && $lgFromDict != $sourceLg) {
                $sourceLg = $lgFromDict;
            }

            $targetFromDict = UrlUtilities::targetLangFromDict($language->translator ?? '');
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

    /**
     * Select language pair page (replaces language_select_pair.php)
     *
     * This is a popup wizard for selecting L1 (native) and L2 (study) languages.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function selectPair(array $params): void
    {
        PageLayoutHelper::renderPageStartNobody(
            'Language Settings Wizard',
            'html{background-color: rgba(0, 0, 0, 0);}'
        );

        $currentnativelanguage = Settings::get('currentnativelanguage');
        $languageOptions = $this->getWizardSelectOptions($currentnativelanguage);
        $languageOptionsEmpty = $this->getWizardSelectOptions('');
        $languagesJson = json_encode(LanguageDefinitions::getAll());

        include __DIR__ . '/../Views/Language/select_pair.php';

        PageLayoutHelper::renderPageEnd();
    }
}
