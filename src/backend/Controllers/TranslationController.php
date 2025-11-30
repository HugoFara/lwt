<?php

/**
 * \file
 * \brief Translation Controller - Handles translation API endpoints
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Controllers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Http\InputValidator;
use Lwt\Services\TranslationService;
use Lwt\Database\Settings;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../Services/DictionaryService.php';
require_once __DIR__ . '/../Core/Entity/GoogleTranslate.php';
require_once __DIR__ . '/../Services/TranslationService.php';

/**
 * Controller for translation API endpoints.
 *
 * Handles:
 * - Google Translate API (/api/google)
 * - Glosbe API (/api/glosbe)
 * - Generic translation (/api/translate)
 *
 * @category Lwt
 * @package  Lwt\Controllers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TranslationController extends BaseController
{
    /**
     * Translation service instance
     *
     * @var TranslationService
     */
    protected TranslationService $translationService;

    /**
     * Initialize controller with translation service.
     */
    public function __construct()
    {
        parent::__construct();
        $this->translationService = new TranslationService();
    }

    /**
     * Get the translation service.
     *
     * @return TranslationService
     */
    public function getTranslationService(): TranslationService
    {
        return $this->translationService;
    }

    /**
     * Google Translate endpoint.
     *
     * Translates text using Google Translate API.
     *
     * Request parameters:
     * - text: Text to translate
     * - sl: Source language code
     * - tl: Target language code
     * - sent: (optional) If set, use sentence mode
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function google(array $params): void
    {
        if (!InputValidator::hasFromGet('text')) {
            return;
        }

        $text = $this->get('text');

        header('Pragma: no-cache');
        header('Expires: 0');

        PageLayoutHelper::renderPageStartNobody('Google Translate');

        if ($text === '') {
            echo '<p class="msgblue">Term is not set!</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $srcLang = $this->get('sl');
        $tgtLang = $this->get('tl');
        $sentenceMode = InputValidator::hasFromGet('sent');

        $this->renderGoogleTranslation($text, $srcLang, $tgtLang, $sentenceMode);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Render Google translation results.
     *
     * @param string $text         Text to translate
     * @param string $srcLang      Source language code
     * @param string $tgtLang      Target language code
     * @param bool   $sentenceMode Whether in sentence translation mode
     *
     * @return void
     */
    protected function renderGoogleTranslation(
        string $text,
        string $srcLang,
        string $tgtLang,
        bool $sentenceMode
    ): void {
        // Get time token for Google API
        require_once __DIR__ . '/../Core/Integration/google_time_token.php';
        $timeToken = \Lwt\Includes\getGoogleTimeToken();

        $result = $this->translationService->translateViaGoogle(
            $text,
            $srcLang,
            $tgtLang,
            $timeToken
        );

        if (!$result['success']) {
            \my_die($result['error'] ?? 'Unable to get translation from Google!');
        }

        // Build Google Translate link
        $ggLink = \makeOpenDictStr(
            \createTheDictLink(
                $this->translationService->buildGoogleTranslateUrl($text, $srcLang, $tgtLang),
                $text
            ),
            "View on Google Translate"
        );

        if ($sentenceMode) {
            $this->renderSentenceTranslation($text, $result['translations'][0] ?? '');
        } else {
            $this->renderTermTranslation($text, $result['translations'], $srcLang, $tgtLang);
        }

        echo $ggLink;
    }

    /**
     * Render sentence translation result.
     *
     * @param string $text        Original text
     * @param string $translation Translated text
     *
     * @return void
     */
    protected function renderSentenceTranslation(string $text, string $translation): void
    {
        ?>
        <h2>Sentence Translation</h2>
        <span title="Translated via Google Translate">
            <?php echo \tohtml($translation); ?>
        </span>
        <p>Original sentence: </p>
        <blockquote><?php echo \tohtml($text); ?></blockquote>
        <?php
    }

    /**
     * Render term translation results.
     *
     * @param string   $text         Original text
     * @param string[] $translations Array of translations
     * @param string   $srcLang      Source language
     * @param string   $tgtLang      Target language
     *
     * @return void
     */
    protected function renderTermTranslation(
        string $text,
        array $translations,
        string $srcLang,
        string $tgtLang
    ): void {
        $lgId = Settings::get('currentlangage');
        $hasParentFrame = true; // Will be checked client-side
        ?>
        <h2 title="Translate with Google Translate">
            Word translation: <?php echo \tohtml($text) ?>
            <img id="textToSpeech" class="click" title="Click to read!"
            src="<?php \print_file_path('icn/speaker-volume.png'); ?>"></img>

            <img id="del_translation" class="click"
            title="Empty Translation Field" data-action="delete-translation"
            src="<?php \print_file_path('icn/broom.png'); ?>"></img>
        </h2>

        <script type="application/json" data-lwt-google-translate-config>
        <?php echo json_encode([
            'text' => $text,
            'langId' => $lgId,
            'hasParentFrame' => $hasParentFrame
        ]); ?>
        </script>
        <?php
        foreach ($translations as $word) {
            echo '<span class="click" data-action="add-translation" data-word="' .
                htmlspecialchars($word, ENT_QUOTES, 'UTF-8') . '">' .
                '<img src="/assets/icons/tick-button.png" title="Copy" alt="Copy" /> &nbsp; ' .
                \tohtml($word) . '</span><br />';
        }
        ?>
        <p>
            (Click on <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" />
            to copy word(s) into above term)<br />&nbsp;
        </p>
        <hr />
        <form action="ggl.php" method="get">
            Unhappy?<br/>Change term:
            <input type="text" name="text" maxlength="250" size="15"
            value="<?php echo \tohtml($text); ?>">
            <input type="hidden" name="sl" value="<?php echo \tohtml($srcLang); ?>">
            <input type="hidden" name="tl" value="<?php echo \tohtml($tgtLang); ?>">
            <input type="submit" value="Translate via Google Translate">
        </form>
        <?php
    }

    /**
     * Glosbe API endpoint.
     *
     * Displays the Glosbe dictionary interface for word translation.
     *
     * Request parameters:
     * - from: Source language code (Glosbe format)
     * - dest: Target language code (Glosbe format)
     * - phrase: Word or expression to translate
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function glosbe(array $params): void
    {
        $from = trim($this->param('from', ''));
        $dest = trim($this->param('dest', ''));
        $destOrig = $dest;
        $phrase = mb_strtolower(trim($this->param('phrase', '')), 'UTF-8');

        PageLayoutHelper::renderPageStartNobody('');

        $glosbeUrl = $this->translationService->buildGlosbeUrl($phrase, $from, $dest);
        $titleText = '<a href="' . $glosbeUrl . '">Glosbe Dictionary (' .
            \tohtml($from) . "-" . \tohtml($dest) . "):  &nbsp; " .
            '<span class="red2">' . \tohtml($phrase) . "</span></a>";

        echo '<h3>' . $titleText .
            ' <img id="del_translation" src="/assets/icons/broom.png" title="Empty Translation Field" ' .
            'class="click" data-action="delete-translation"></img></h3>';
        echo '<p>(Click on <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" /> ' .
            'to copy word(s) into above term)<br />&nbsp;</p>';

        $this->renderGlosbeScript($from, $dest, $phrase);

        echo '<p id="translations"></p>';

        echo '&nbsp;<form action="glosbe_api.php" method="get">Unhappy?<br/>Change term:
            <input type="text" name="phrase" maxlength="250" size="15" value="' . \tohtml($phrase) . '">
            <input type="hidden" name="from" value="' . \tohtml($from) . '">
            <input type="hidden" name="dest" value="' . \tohtml($destOrig) . '">
            <input type="submit" value="Translate via Glosbe">
            </form>';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Render the JavaScript for Glosbe translation.
     *
     * @param string $from   Source language code
     * @param string $dest   Target language code
     * @param string $phrase Phrase to translate
     *
     * @return void
     */
    protected function renderGlosbeScript(string $from, string $dest, string $phrase): void
    {
        $validation = $this->translationService->validateGlosbeParams($from, $dest, $phrase);

        $config = [
            'phrase' => urlencode($phrase),
            'from' => $from,
            'dest' => $dest,
            'hasParentFrame' => true // Will be checked client-side
        ];

        if (!$validation['valid']) {
            if ($phrase === '') {
                $config['error'] = 'empty_term';
            } else {
                $config['error'] = 'api_error';
            }
        }

        ?>
        <script type="application/json" data-lwt-glosbe-config>
        <?php echo json_encode($config); ?>
        </script>
        <?php
    }

    /**
     * Generic translation endpoint.
     *
     * Handles sentence translation and dictionary lookups.
     *
     * Request parameters:
     * - x: Operation type (1=sentence translation, 2=dictionary lookup)
     * - t: Text ID (for x=1) or text to translate (for x=2)
     * - i: Position (for x=1) or dictionary URI (for x=2)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function translate(array $params): void
    {
        $type = $this->paramInt('x');
        if ($type === null) {
            return;
        }

        $t = $this->paramInt('t', 0) ?? 0;
        $i = $this->paramInt('i', 0) ?? 0;

        $this->processTranslationRequest($type, $t, $i);
    }

    /**
     * Process a translation request.
     *
     * @param int $type Operation type
     * @param int $t    Text ID or text parameter
     * @param int $i    Position or dictionary URI parameter
     *
     * @return void
     */
    protected function processTranslationRequest(int $type, int $t, int $i): void
    {
        // Type 1: Translate sentence
        if ($type === 1) {
            $result = $this->translationService->getTranslatorUrl($i, $t);

            if (!empty($result['url'])) {
                $this->redirect($result['url']);
            }
            return;
        }

        // Type 2: Dictionary lookup
        if ($type === 2) {
            $url = $this->translationService->createDictLink((string)$t, (string)$i);
            $this->redirect($url);
        }
    }
}
