<?php

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\Infrastructure\Http\FlashMessageService;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * Controller for feed index/browse operations.
 *
 * Handles the main feed list page with article browsing,
 * marked item processing, and text creation from feeds.
 *
 * @since 3.0.0
 */
class FeedIndexController
{
    use FeedFlashTrait;

    private string $viewPath;
    private FeedFacade $feedFacade;
    private LanguageFacade $languageFacade;
    private FlashMessageService $flashService;

    public function __construct(
        FeedFacade $feedFacade,
        LanguageFacade $languageFacade,
        ?FlashMessageService $flashService = null
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->feedFacade = $feedFacade;
        $this->languageFacade = $languageFacade;
        $this->flashService = $flashService ?? new FlashMessageService();
    }

    /**
     * Feeds index page.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );
        PageLayoutHelper::renderPageStart($this->languageFacade->getLanguageName($currentLang) . ' Feeds', true);

        $currentFeed = InputValidator::getString("selected_feed");

        $editText = 0;
        $message = '';

        // Handle marked items submission
        $markedItemsArray = InputValidator::getArray('marked_items');
        if (!empty($markedItemsArray)) {
            $result = $this->processMarkedItems();
            $editText = $result['editText'];
            $message = $result['message'];
        }

        // Display messages
        $this->displayFeedMessages($message);

        // Route based on action
        $markAction = InputValidator::getString('markaction');
        if (
            InputValidator::has('load_feed') || InputValidator::has('check_autoupdate')
            || ($markAction == 'update')
        ) {
            $this->feedFacade->renderFeedLoadInterfaceModern(
                (int)$currentFeed,
                InputValidator::has('check_autoupdate'),
                '/feeds'
            );
        } elseif (empty($editText)) {
            $this->renderFeedsIndex((int)$currentLang, (int)$currentFeed);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Process marked feed items and create texts from them.
     *
     * @return array{editText: int, message: string}
     */
    private function processMarkedItems(): array
    {
        $editText = 0;
        $message = '';

        $markedItemsArray = InputValidator::getArray('marked_items');
        if (empty($markedItemsArray)) {
            return ['editText' => $editText, 'message' => $message];
        }

        $markedItems = implode(',', array_filter($markedItemsArray, 'is_scalar'));
        $feedLinks = $this->feedFacade->getMarkedFeedLinks($markedItems);

        $stats = ['archived' => 0, 'sentences' => 0, 'textitems' => 0];
        $count = 0;
        $languages = null;

        foreach ($feedLinks as $row) {
            $requiresEdit = $this->feedFacade->getNfOption($row['NfOptions'], 'edit_text') == 1;

            if ($requiresEdit) {
                if ($editText == 1) {
                    $count++;
                } else {
                    echo '<form class="validate" action="/feeds" method="post">';
                    echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField();
                    $editText = 1;
                    $languages = $this->feedFacade->getLanguages();
                }
            }

            $doc = [[
                'link' => $row['FlLink'] === '' ? ('#' . ($row['FlID'] ?? 0)) : $row['FlLink'],
                'title' => $row['FlTitle'],
                'audio' => $row['FlAudio'],
                'text' => $row['FlText']
            ]];

            $nfName = $row['NfName'];
            $nfId = $row['NfID'];
            $nfOptions = $row['NfOptions'];

            $tagNameRaw = $this->feedFacade->getNfOption($nfOptions, 'tag');
            $tagName = is_string($tagNameRaw) && $tagNameRaw !== '' ? $tagNameRaw : mb_substr($nfName, 0, 20, "utf-8");

            $maxTextsRaw = $this->feedFacade->getNfOption($nfOptions, 'max_texts');
            $maxTexts = is_string($maxTextsRaw) ? (int)$maxTextsRaw : 0;
            if (!$maxTexts) {
                $maxTexts = (int)Settings::getWithDefault('set-max-texts-per-feed');
            }

            $charsetRaw = $this->feedFacade->getNfOption($nfOptions, 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $texts = $this->feedFacade->extractTextFromArticle(
                $doc,
                $row['NfArticleSectionTags'],
                $row['NfFilterTags'],
                $charset
            );

            if (isset($texts['error'])) {
                echo htmlspecialchars((string)$texts['error']['message'], ENT_QUOTES, 'UTF-8');
                /** @var array<string> $errLinks */
                $errLinks = $texts['error']['link'] ?? [];
                foreach ($errLinks as $errLink) {
                    $this->feedFacade->markLinkAsError($errLink);
                }
                unset($texts['error']);
            }

            if ($requiresEdit) {
                // Include edit form view
                $scrdir = $this->languageFacade->getScriptDirectionTag($row['NfLgID']);
                /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
                include $this->viewPath . 'edit_text_form.php';
            } elseif (is_array($texts)) {
                $result = $this->createTextsFromFeed($texts, $row, $tagName, $maxTexts);
                $stats['archived'] += $result['archived'] ?? 0;
                $stats['sentences'] += $result['sentences'] ?? 0;
                $stats['textitems'] += $result['textitems'] ?? 0;
            }
        }

        if ($stats['archived'] > 0) {
            $message = "Texts archived: {$stats['archived']} / Sentences deleted: {$stats['sentences']}" .
                       " / Text items deleted: {$stats['textitems']}";
        }

        if ($editText == 1) {
            /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
            include $this->viewPath . 'edit_text_footer.php';
        }

        return ['editText' => $editText, 'message' => $message];
    }

    /**
     * Create texts from feed data without edit form.
     *
     * @param array<int|string, array<string, mixed>> $texts    Parsed text data
     * @param array<string, mixed>                    $row      Feed data
     * @param string                                  $tagName  Tag name
     * @param int                                     $maxTexts Maximum texts to keep
     *
     * @return array{archived: int, sentences: int, textitems: int}
     */
    private function createTextsFromFeed(array $texts, array $row, string $tagName, int $maxTexts): array
    {
        foreach ($texts as $text) {
            echo '<div class="notification is-success" data-auto-hide="true">' .
                '<button class="delete" aria-label="close"></button>' .
                'Text "' . htmlspecialchars((string)($text['TxTitle'] ?? ''), ENT_QUOTES, 'UTF-8') . '" added!' .
                '</div>';

            $this->feedFacade->createTextFromFeed([
                'TxLgID' => $row['NfLgID'],
                'TxTitle' => $text['TxTitle'],
                'TxText' => $text['TxText'],
                'TxAudioURI' => $text['TxAudioURI'] ?? '',
                'TxSourceURI' => $text['TxSourceURI'] ?? ''
            ], $tagName);
        }

        TagsFacade::getAllTextTags(true);

        return $this->feedFacade->archiveOldTexts($tagName, $maxTexts);
    }

    /**
     * Display errors and messages for feed operations.
     *
     * @param string $message Message to display
     *
     * @return void
     */
    private function displayFeedMessages(string $message): void
    {
        if (InputValidator::has('checked_feeds_save')) {
            /** @var array<int, array{Nf_ID: int|string, TagList: array<string>, Nf_Max_Texts: int|null, TxLgID: int, TxTitle: string, TxText: string, TxAudioURI: string, TxSourceURI: string}> $feedData */
            $feedData = InputValidator::getArray('feed');
            $result = $this->feedFacade->saveTextsFromFeed($feedData);
            $message = "Texts archived: {$result['textsArchived']}, " .
                "Sentences deleted: {$result['sentencesDeleted']}, " .
                "Text items deleted: {$result['textItemsDeleted']}";
        }

        $this->renderFlashMessages($this->flashService);

        if ($message !== '') {
            PageLayoutHelper::renderMessage($message);
        }
    }

    /**
     * Render the main feeds index page.
     *
     * @param int $currentLang Current language filter
     * @param int $currentFeed Current feed filter
     *
     * @return void
     */
    private function renderFeedsIndex(int $currentLang, int $currentFeed): void
    {
        $currentQuery = InputValidator::getString("query");
        $currentQueryMode = InputValidator::getString("query_mode", 'title,desc,text');
        $currentRegexMode = Settings::getWithDefault("set-regex-mode");

        $filterData = $this->feedFacade->buildQueryFilter($currentQuery, $currentQueryMode, $currentRegexMode);
        $searchTerm = $filterData['search'];

        if (!empty($currentQuery) && !empty($currentRegexMode)) {
            if (!$this->feedFacade->validateRegexPattern($currentQuery)) {
                $currentQuery = '';
                $searchTerm = '';
                if (InputValidator::has('query')) {
                    echo '<div class="notification is-warning" data-auto-hide="true">' .
                        '<button class="delete" aria-label="close"></button>' .
                        'Warning: Invalid Search' .
                        '</div>';
                }
            }
        }

        $currentPage = InputValidator::getIntParam("page", 1, 1);
        $currentSort = InputValidator::getIntWithDb("sort", 'currentrsssort', 2);

        $feeds = $this->feedFacade->getFeeds($currentLang ?: null);

        // Determine current feed
        $feedTime = null;
        if ($currentFeed == 0 || empty($feeds)) {
            if (!empty($feeds)) {
                $currentFeed = (int)$feeds[0]['NfID'];
            }
        } else {
            // Get feed time for the selected feed
            foreach ($feeds as $f) {
                if ((int)$f['NfID'] === $currentFeed) {
                    $feedTime = $f['NfUpdate'];
                    break;
                }
            }
        }

        $feedIds = (string)$currentFeed;
        $recno = $currentFeed ? $this->feedFacade->countFeedLinks($feedIds, $searchTerm) : 0;

        // Pagination
        $maxPerPage = (int)Settings::getWithDefault('set-articles-per-page');
        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxPerPage) + 1);

        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if ($currentPage > $pages) {
            $currentPage = $pages;
        }

        $offset = ($currentPage - 1) * $maxPerPage;
        $sortColumn = $this->feedFacade->getSortColumn($currentSort);

        // Get articles if there are any
        $articles = [];
        if ($recno > 0) {
            $articles = $this->feedFacade->getFeedLinks($feedIds, $searchTerm, $sortColumn, $offset, $maxPerPage);
        }

        // Format last update for view
        $lastUpdateFormatted = null;
        /** @psalm-suppress RedundantCastGivenDocblockType */
        $feedTimeInt = is_numeric($feedTime) ? (int)$feedTime : 0;
        if ($feedTimeInt !== 0) {
            $diff = time() - $feedTimeInt;
            $lastUpdateFormatted = $this->feedFacade->formatLastUpdate($diff);
        }

        // Pass service to view for utility methods
        $feedService = $this->feedFacade;
        $languages = $this->languageFacade->getLanguagesForSelect();

        // Include browse view
        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'browse.php';
    }
}
