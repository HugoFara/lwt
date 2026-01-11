<?php

/**
 * Feed Wizard Controller
 *
 * Handles the multi-step feed creation wizard.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for feed wizard operations.
 *
 * Handles the multi-step feed creation wizard:
 * - Step 1: Insert Feed URI
 * - Step 2: Select Article Text
 * - Step 3: Filter Text
 * - Step 4: Edit Options
 *
 * @since 3.0.0
 */
class FeedWizardController
{
    /**
     * View base path.
     */
    private string $viewPath;

    /**
     * Feed facade.
     */
    private FeedFacade $feedFacade;

    /**
     * Language facade.
     */
    private LanguageFacade $languageFacade;

    /**
     * Wizard session manager.
     */
    private FeedWizardSessionManager $wizardSession;

    /**
     * Constructor.
     *
     * @param FeedFacade               $feedFacade     Feed facade
     * @param LanguageFacade           $languageFacade Language facade
     * @param FeedWizardSessionManager $wizardSession  Wizard session manager
     */
    public function __construct(
        FeedFacade $feedFacade,
        LanguageFacade $languageFacade,
        ?FeedWizardSessionManager $wizardSession = null
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->feedFacade = $feedFacade;
        $this->languageFacade = $languageFacade;
        $this->wizardSession = $wizardSession ?? new FeedWizardSessionManager();
    }

    /**
     * Set custom view path.
     *
     * @param string $path View path
     *
     * @return void
     */
    public function setViewPath(string $path): void
    {
        $this->viewPath = rtrim($path, '/') . '/';
    }

    /**
     * Get the wizard feed data as typed array.
     *
     * @return array<int|string, mixed>
     */
    private function getWizardFeed(): array
    {
        return $this->wizardSession->getFeed();
    }

    /**
     * Feed wizard page.
     *
     * Routes based on step parameter:
     * - step=1: Insert Feed URI
     * - step=2: Select Article Text
     * - step=3: Filter Text
     * - step=4: Edit Options
     *
     * @param array<string, string> $params Route parameters
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     */
    public function wizard(array $params): ?RedirectResponse
    {
        $step = InputValidator::getInt('step', 1) ?? 1;

        switch ($step) {
            case 2:
                return $this->wizardStep2();
            case 3:
                $this->wizardStep3();
                break;
            case 4:
                $this->wizardStep4();
                break;
            case 1:
            default:
                $this->wizardStep1();
                break;
        }

        return null;
    }

    /**
     * Wizard Step 1: Insert Feed URI.
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude View path is constructed at runtime
     */
    private function wizardStep1(): void
    {
        $this->initWizardSession();

        PageLayoutHelper::renderPageStart('Feed Wizard', false);

        $errorMessage = InputValidator::has('err') ? true : null;
        $rssUrl = $this->wizardSession->getRssUrl() ?: null;

        include $this->viewPath . 'wizard_step1.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Wizard Step 2: Select Article Text.
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     */
    private function wizardStep2(): ?RedirectResponse
    {
        // Handle edit mode - load existing feed
        $editFeedId = InputValidator::getInt('edit_feed');
        $rssUrl = InputValidator::getString('rss_url');
        if ($editFeedId !== null && !$this->wizardSession->exists()) {
            $redirect = $this->loadExistingFeedForEdit($editFeedId);
            if ($redirect !== null) {
                return $redirect;
            }
        } elseif ($rssUrl !== '') {
            $redirect = $this->loadNewFeedFromUrl($rssUrl);
            if ($redirect !== null) {
                return $redirect;
            }
        }

        // Process session parameters
        $this->processStep2SessionParams();

        $feedData = $this->wizardSession->getFeed();
        $feedLen = count(array_filter(array_keys($feedData), 'is_numeric'));

        // Handle article section change
        $nfArticleSection = InputValidator::getString('NfArticleSection');
        if (
            $nfArticleSection !== '' &&
            ($nfArticleSection != ($feedData['feed_text'] ?? ''))
        ) {
            $this->updateFeedArticleSource($nfArticleSection, $feedLen);
        }

        PageLayoutHelper::renderPageStartNobody('Feed Wizard');

        $wizardData = $this->wizardSession->getAll();
        $feedHtml = $this->getStep2FeedHtml();

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'wizard_step2.php';

        PageLayoutHelper::renderPageEnd();

        return null;
    }

    /**
     * Wizard Step 3: Filter Text.
     *
     * @return void
     */
    private function wizardStep3(): void
    {
        $this->processStep3SessionParams();

        $feedData = $this->getWizardFeed();
        $feedLen = count(array_filter(array_keys($feedData), 'is_numeric'));

        PageLayoutHelper::renderPageStartNobody("Feed Wizard");

        $wizardData = $this->wizardSession->getAll();
        $feedHtml = $this->getStep3FeedHtml();

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'wizard_step3.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Wizard Step 4: Edit Options.
     *
     * @return void
     */
    private function wizardStep4(): void
    {
        PageLayoutHelper::renderPageStart('Feed Wizard', false);

        $filterTags = InputValidator::getString('filter_tags');
        if ($filterTags !== '') {
            $this->wizardSession->setFilterTags($filterTags);
        }

        $options = $this->wizardSession->getOptions();
        $autoUpdI = $this->feedFacade->getNfOption($options, 'autoupdate');
        if ($autoUpdI === null || !is_string($autoUpdI)) {
            $autoUpdV = null;
            $autoUpdI = null;
        } else {
            $autoUpdV = substr($autoUpdI, -1);
            $autoUpdI = substr($autoUpdI, 0, -1);
        }

        $wizardData = $this->wizardSession->getAll();
        $languages = $this->feedFacade->getLanguages();
        $service = $this->feedFacade;

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'wizard_step4.php';

        // Clear wizard session after step 4
        $this->wizardSession->clear();

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Initialize wizard session data.
     *
     * @return void
     */
    private function initWizardSession(): void
    {
        $selectMode = InputValidator::getString('select_mode');
        if ($selectMode !== '') {
            $this->wizardSession->setSelectMode($selectMode);
        }
        $hideImages = InputValidator::getString('hide_images');
        if ($hideImages !== '') {
            $this->wizardSession->setHideImages($hideImages);
        }
    }

    /**
     * Load existing feed data for editing.
     *
     * @param int $feedId Feed ID
     *
     * @return RedirectResponse|null Redirect on error, null on success
     */
    private function loadExistingFeedForEdit(int $feedId): ?RedirectResponse
    {
        $row = $this->feedFacade->getFeedById($feedId);

        if ($row === null) {
            return new RedirectResponse('/feeds/wizard?step=1&err=1');
        }

        $this->wizardSession->setEditFeedId($feedId);
        $this->wizardSession->setRssUrl($row['NfSourceURI']);

        // Parse article tags
        $articleTags = explode('|', str_replace('!?!', '|', $row['NfArticleSectionTags']));
        $articleTagsHtml = '';
        foreach ($articleTags as $tag) {
            if (substr_compare(trim($tag), "redirect", 0, 8) == 0) {
                $this->wizardSession->setRedirect(trim($tag) . ' | ');
            } else {
                $articleTagsHtml .= '<li class="has-text-left">'
                . IconHelper::render('x', ['class' => 'delete_selection', 'title' => 'Delete Selection', 'alt' => '-'])
                . $tag .
                '</li>';
            }
        }
        $this->wizardSession->setArticleTags($articleTagsHtml);

        // Parse filter tags
        $filterTags = explode('|', str_replace('!?!', '|', $row['NfFilterTags']));
        $filterTagsHtml = '';
        foreach ($filterTags as $tag) {
            if (trim($tag) != '') {
                $filterTagsHtml .= '<li class="has-text-left">'
                . IconHelper::render('x', ['class' => 'delete_selection', 'title' => 'Delete Selection', 'alt' => '-'])
                . $tag .
                '</li>';
            }
        }
        $this->wizardSession->setFilterTags($filterTagsHtml);

        $feedData = $this->feedFacade->detectAndParseFeed($row['NfSourceURI']);
        if (!is_array($feedData) || empty($feedData)) {
            $this->wizardSession->remove('feed');
            return new RedirectResponse('/feeds/wizard?step=1&err=1');
        }
        // Update feed data with title
        $feedData['feed_title'] = $row['NfName'];
        $this->wizardSession->setFeed($feedData);
        $this->wizardSession->setOptions($row['NfOptions']);

        $feedText = isset($feedData['feed_text']) && is_string($feedData['feed_text'])
            ? $feedData['feed_text']
            : '';
        if ($feedText === '') {
            $feedData['feed_text'] = '';
            $this->wizardSession->setFeed($feedData);
            $this->wizardSession->setDetectedFeed('Detected: «Webpage Link»');
        } else {
            $this->wizardSession->setDetectedFeed('Detected: «' . $feedText . '»');
        }

        $this->wizardSession->setLang((string)$row['NfLgID']);

        // Handle custom article source
        $articleSource = $this->feedFacade->getNfOption($row['NfOptions'], 'article_source');
        $articleSourceStr = is_string($articleSource) ? $articleSource : '';
        $currentFeedText = $feedText;
        if ($currentFeedText !== $articleSourceStr && $articleSourceStr !== '') {
            $feedData['feed_text'] = $articleSourceStr;
            $feedLen = count(array_filter(array_keys($feedData), 'is_numeric'));
            for ($i = 0; $i < $feedLen; $i++) {
                $item = $feedData[$i] ?? null;
                if (is_array($item) && isset($item[$articleSourceStr])) {
                    $item['text'] = $item[$articleSourceStr];
                    $feedData[$i] = $item;
                }
            }
            $this->wizardSession->setFeed($feedData);
        }

        return null;
    }

    /**
     * Load new feed from URL.
     *
     * @param string $rssUrl Feed URL
     *
     * @return RedirectResponse|null Redirect on error, null on success
     */
    private function loadNewFeedFromUrl(string $rssUrl): ?RedirectResponse
    {
        $existingFeed = $this->wizardSession->getFeed();
        $existingUrl = $this->wizardSession->getRssUrl();
        if (
            $this->wizardSession->exists() && !empty($existingFeed) &&
            $rssUrl === $existingUrl
        ) {
            session_destroy();
            throw new \RuntimeException(
                "Session state conflict detected. Please reload the page and try again."
            );
        }

        $feedData = $this->feedFacade->detectAndParseFeed($rssUrl);
        if ($feedData !== false) {
            $this->wizardSession->setFeed($feedData);
        }
        $this->wizardSession->setRssUrl($rssUrl);

        $currentFeed = $this->wizardSession->getFeed();
        if (empty($currentFeed)) {
            $this->wizardSession->remove('feed');
            return new RedirectResponse('/feeds/wizard?step=1&err=1');
        }

        if (!$this->wizardSession->has('article_tags')) {
            $this->wizardSession->setArticleTags('');
        }
        if (!$this->wizardSession->has('filter_tags')) {
            $this->wizardSession->setFilterTags('');
        }
        if (!$this->wizardSession->has('options')) {
            $this->wizardSession->setOptions('edit_text=1');
        }
        if (!$this->wizardSession->has('lang')) {
            $this->wizardSession->setLang('');
        }

        $feedText = $this->wizardSession->getFeedText();
        if ($feedText !== '') {
            $this->wizardSession->setDetectedFeed('Detected: «' . $feedText . '»');
        } else {
            $this->wizardSession->setDetectedFeed('Detected: «Webpage Link»');
        }

        return null;
    }

    /**
     * Process step 2 session parameters.
     *
     * @return void
     */
    private function processStep2SessionParams(): void
    {
        $filterTags = InputValidator::getString('filter_tags');
        if ($filterTags !== '') {
            $this->wizardSession->setFilterTags($filterTags);
        }
        $selectedFeed = InputValidator::getString('selected_feed');
        if ($selectedFeed !== '') {
            $this->wizardSession->setSelectedFeed((int)$selectedFeed);
        }
        $maxim = InputValidator::getString('maxim');
        if ($maxim !== '') {
            $this->wizardSession->setMaxim((int)$maxim);
        }
        if (!$this->wizardSession->has('maxim')) {
            $this->wizardSession->setMaxim(1);
        }
        $selectMode = InputValidator::getString('select_mode');
        if ($selectMode !== '') {
            $this->wizardSession->setSelectMode($selectMode);
        }
        if (!$this->wizardSession->has('select_mode')) {
            $this->wizardSession->setSelectMode('0');
        }
        $hideImages = InputValidator::getString('hide_images');
        if ($hideImages !== '') {
            $this->wizardSession->setHideImages($hideImages);
        }
        if (!$this->wizardSession->has('hide_images')) {
            $this->wizardSession->setHideImages('yes');
        }
        if (!$this->wizardSession->has('redirect')) {
            $this->wizardSession->setRedirect('');
        }
        if (!$this->wizardSession->has('selected_feed')) {
            $this->wizardSession->setSelectedFeed(0);
        }
        if (!$this->wizardSession->has('host')) {
            $this->wizardSession->set('host', []);
        }
        $hostName = InputValidator::getString('host_name');
        $hostStatus = InputValidator::getString('host_status');
        if ($hostStatus !== '' && $hostName !== '') {
            $this->wizardSession->setHostStatus($hostName, $hostStatus);
        }
        $nfName = InputValidator::getString('NfName');
        if ($nfName !== '') {
            $this->wizardSession->setFeedTitle($nfName);
        }
    }

    /**
     * Process step 3 session parameters.
     *
     * @return void
     */
    private function processStep3SessionParams(): void
    {
        $nfName = InputValidator::getString('NfName');
        if ($nfName !== '') {
            $this->wizardSession->setFeedTitle($nfName);
        }
        $nfArticleSection = InputValidator::getString('NfArticleSection');
        if ($nfArticleSection !== '') {
            $this->wizardSession->setArticleSection($nfArticleSection);
        }
        $articleSelector = InputValidator::getString('article_selector');
        if ($articleSelector !== '') {
            $this->wizardSession->setArticleSelector($articleSelector);
        }
        $selectedFeed = InputValidator::getString('selected_feed');
        if ($selectedFeed !== '') {
            $this->wizardSession->setSelectedFeed((int)$selectedFeed);
        }
        $articleTags = InputValidator::getString('article_tags');
        if ($articleTags !== '') {
            $this->wizardSession->setArticleTags($articleTags);
        }
        $html = InputValidator::getString('html');
        if ($html !== '') {
            $this->wizardSession->setFilterTags($html);
        }
        $nfOptions = InputValidator::getString('NfOptions');
        if ($nfOptions !== '') {
            $this->wizardSession->setOptions($nfOptions);
        }
        $nfLgId = InputValidator::getString('NfLgID');
        if ($nfLgId !== '') {
            $this->wizardSession->setLang($nfLgId);
        }
        if (!$this->wizardSession->has('article_tags')) {
            $this->wizardSession->setArticleTags('');
        }
        $maxim = InputValidator::getString('maxim');
        if ($maxim !== '') {
            $this->wizardSession->setMaxim((int)$maxim);
        }
        $selectMode = InputValidator::getString('select_mode');
        if ($selectMode !== '') {
            $this->wizardSession->setSelectMode($selectMode);
        }
        $hideImages = InputValidator::getString('hide_images');
        if ($hideImages !== '') {
            $this->wizardSession->setHideImages($hideImages);
        }
        if (!$this->wizardSession->has('select_mode')) {
            $this->wizardSession->setSelectMode('');
        }
        if (!$this->wizardSession->has('maxim')) {
            $this->wizardSession->setMaxim(1);
        }
        if (!$this->wizardSession->has('selected_feed')) {
            $this->wizardSession->setSelectedFeed(0);
        }
        if (!$this->wizardSession->has('host2')) {
            $this->wizardSession->set('host2', []);
        }
        $hostName = InputValidator::getString('host_name');
        $hostStatus = InputValidator::getString('host_status');
        $hostStatus2 = InputValidator::getString('host_status2');
        if ($hostStatus !== '' && $hostName !== '') {
            $this->wizardSession->setHostStatus($hostName, $hostStatus);
        }
        if ($hostStatus2 !== '' && $hostName !== '') {
            $this->wizardSession->setHost2Status($hostName, $hostStatus2);
        }
    }

    /**
     * Update feed article source.
     *
     * @param string $articleSection New article section
     * @param int    $feedLen        Number of feed items
     *
     * @return void
     */
    private function updateFeedArticleSource(string $articleSection, int $feedLen): void
    {
        $this->wizardSession->setFeedText($articleSection);
        $source = $articleSection;

        for ($i = 0; $i < $feedLen; $i++) {
            $item = $this->wizardSession->getFeedItem($i);
            if ($item === null) {
                continue;
            }
            if ($source !== '') {
                /** @var mixed $sourceValue */
                $sourceValue = $item[$source] ?? '';
                $item['text'] = is_string($sourceValue) ? $sourceValue : '';
            } else {
                unset($item['text']);
            }
            unset($item['html']);
            $this->wizardSession->setFeedItem($i, $item);
        }
        $this->wizardSession->clearHost();
    }

    /**
     * Get HTML content for step 2 feed preview.
     *
     * @return string|array HTML content
     */
    private function getStep2FeedHtml(): string|array
    {
        $i = $this->wizardSession->getSelectedFeed();
        $existingHtml = $this->wizardSession->getFeedItemHtml($i);

        if ($existingHtml === null) {
            $feedItem = $this->wizardSession->getFeedItem($i);
            if ($feedItem === null) {
                return '';
            }
            // Build feed item array with required fields
            /** @var array{link: string, title: string, audio?: string, text?: string} $typedItem */
            $typedItem = [
                'link' => isset($feedItem['link']) && is_string($feedItem['link']) ? $feedItem['link'] : '',
                'title' => isset($feedItem['title']) && is_string($feedItem['title']) ? $feedItem['title'] : '',
            ];
            if (isset($feedItem['audio']) && is_string($feedItem['audio'])) {
                $typedItem['audio'] = $feedItem['audio'];
            }
            if (isset($feedItem['text']) && is_string($feedItem['text'])) {
                $typedItem['text'] = $feedItem['text'];
            }
            $aFeed = [$typedItem];
            $charsetRaw = $this->feedFacade->getNfOption($this->wizardSession->getOptions(), 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $html = $this->feedFacade->extractTextFromArticle(
                $aFeed,
                $this->wizardSession->getRedirect() . 'new',
                'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
                $charset
            );
            $this->wizardSession->setFeedItemHtml($i, $html);
            return is_string($html) ? $html : (is_array($html) ? $html : '');
        }

        return $existingHtml;
    }

    /**
     * Get HTML content for step 3 feed preview.
     *
     * @return string|array HTML content
     */
    private function getStep3FeedHtml(): string|array
    {
        $i = $this->wizardSession->getSelectedFeed();
        $existingHtml = $this->wizardSession->getFeedItemHtml($i);

        if ($existingHtml === null) {
            $feedItem = $this->wizardSession->getFeedItem($i);
            if ($feedItem === null) {
                return '';
            }
            // Build feed item array with required fields
            /** @var array{link: string, title: string, audio?: string, text?: string} $typedItem */
            $typedItem = [
                'link' => isset($feedItem['link']) && is_string($feedItem['link']) ? $feedItem['link'] : '',
                'title' => isset($feedItem['title']) && is_string($feedItem['title']) ? $feedItem['title'] : '',
            ];
            if (isset($feedItem['audio']) && is_string($feedItem['audio'])) {
                $typedItem['audio'] = $feedItem['audio'];
            }
            if (isset($feedItem['text']) && is_string($feedItem['text'])) {
                $typedItem['text'] = $feedItem['text'];
            }
            $aFeed = [$typedItem];
            $charsetRaw = $this->feedFacade->getNfOption($this->wizardSession->getOptions(), 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $html = $this->feedFacade->extractTextFromArticle(
                $aFeed,
                $this->wizardSession->getRedirect() . 'new',
                'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
                $charset
            );
            $this->wizardSession->setFeedItemHtml($i, $html);
            return is_string($html) ? $html : (is_array($html) ? $html : '');
        }

        return $existingHtml;
    }
}
