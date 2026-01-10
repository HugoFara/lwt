<?php declare(strict_types=1);
/**
 * Feed Wizard Session Manager
 *
 * Infrastructure adapter for PHP session state management in the Feed wizard.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Infrastructure
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Feed\Infrastructure;

/**
 * Adapter for PHP session state management in the Feed wizard.
 *
 * Abstracts $_SESSION['wizard'] access for the Feed module,
 * enabling testability and future session backend changes.
 *
 * @since 3.0.0
 */
class FeedWizardSessionManager
{
    /**
     * Session key for wizard data.
     */
    private const KEY_WIZARD = 'wizard';

    /**
     * Ensure session is started.
     *
     * @return void
     */
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get the wizard session data as typed array.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $this->ensureSession();
        if (!isset($_SESSION[self::KEY_WIZARD]) || !is_array($_SESSION[self::KEY_WIZARD])) {
            $_SESSION[self::KEY_WIZARD] = [];
        }
        /** @var array<string, mixed> */
        return $_SESSION[self::KEY_WIZARD];
    }

    /**
     * Check if wizard session exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        $this->ensureSession();
        return isset($_SESSION[self::KEY_WIZARD]) && is_array($_SESSION[self::KEY_WIZARD]);
    }

    /**
     * Clear the wizard session.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->ensureSession();
        unset($_SESSION[self::KEY_WIZARD]);
    }

    /**
     * Get a string value from wizard session.
     *
     * @param string $key     Key to retrieve
     * @param string $default Default value
     *
     * @return string
     */
    public function getString(string $key, string $default = ''): string
    {
        $wizard = $this->getAll();
        if (!isset($wizard[$key])) {
            return $default;
        }
        return is_string($wizard[$key]) ? $wizard[$key] : $default;
    }

    /**
     * Get an integer value from wizard session.
     *
     * @param string $key     Key to retrieve
     * @param int    $default Default value
     *
     * @return int
     */
    public function getInt(string $key, int $default = 0): int
    {
        $wizard = $this->getAll();
        if (!isset($wizard[$key])) {
            return $default;
        }
        return is_numeric($wizard[$key]) ? (int)$wizard[$key] : $default;
    }

    /**
     * Get an array value from wizard session.
     *
     * @param string $key Key to retrieve
     *
     * @return array<int|string, mixed>
     */
    public function getArray(string $key): array
    {
        $wizard = $this->getAll();
        if (!isset($wizard[$key]) || !is_array($wizard[$key])) {
            return [];
        }
        return $wizard[$key];
    }

    /**
     * Set a value in wizard session.
     *
     * @param string $key   Key to set
     * @param mixed  $value Value to set
     *
     * @return void
     *
     * @psalm-suppress MixedAssignment
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureSession();
        if (!isset($_SESSION[self::KEY_WIZARD]) || !is_array($_SESSION[self::KEY_WIZARD])) {
            $_SESSION[self::KEY_WIZARD] = [];
        }
        $_SESSION[self::KEY_WIZARD][$key] = $value;
    }

    /**
     * Check if a key exists in wizard session.
     *
     * @param string $key Key to check
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        $wizard = $this->getAll();
        return isset($wizard[$key]);
    }

    /**
     * Remove a key from wizard session.
     *
     * @param string $key Key to remove
     *
     * @return void
     *
     * @psalm-suppress MixedArrayAccess
     */
    public function remove(string $key): void
    {
        $this->ensureSession();
        if (isset($_SESSION[self::KEY_WIZARD][$key])) {
            unset($_SESSION[self::KEY_WIZARD][$key]);
        }
    }

    // =========================================================================
    // Typed Accessors for Feed Data
    // =========================================================================

    /**
     * Get feed data.
     *
     * @return array<int|string, mixed>
     */
    public function getFeed(): array
    {
        return $this->getArray('feed');
    }

    /**
     * Set feed data.
     *
     * @param array<int|string, mixed> $feed Feed data
     *
     * @return void
     */
    public function setFeed(array $feed): void
    {
        $this->set('feed', $feed);
    }

    /**
     * Get RSS URL.
     *
     * @return string
     */
    public function getRssUrl(): string
    {
        return $this->getString('rss_url');
    }

    /**
     * Set RSS URL.
     *
     * @param string $url URL
     *
     * @return void
     */
    public function setRssUrl(string $url): void
    {
        $this->set('rss_url', $url);
    }

    /**
     * Get article tags HTML.
     *
     * @return string
     */
    public function getArticleTags(): string
    {
        return $this->getString('article_tags');
    }

    /**
     * Set article tags HTML.
     *
     * @param string $tags Tags HTML
     *
     * @return void
     */
    public function setArticleTags(string $tags): void
    {
        $this->set('article_tags', $tags);
    }

    /**
     * Get filter tags HTML.
     *
     * @return string
     */
    public function getFilterTags(): string
    {
        return $this->getString('filter_tags');
    }

    /**
     * Set filter tags HTML.
     *
     * @param string $tags Tags HTML
     *
     * @return void
     */
    public function setFilterTags(string $tags): void
    {
        $this->set('filter_tags', $tags);
    }

    /**
     * Get feed options string.
     *
     * @return string
     */
    public function getOptions(): string
    {
        return $this->getString('options');
    }

    /**
     * Set feed options string.
     *
     * @param string $options Options
     *
     * @return void
     */
    public function setOptions(string $options): void
    {
        $this->set('options', $options);
    }

    /**
     * Get language ID.
     *
     * @return string
     */
    public function getLang(): string
    {
        return $this->getString('lang');
    }

    /**
     * Set language ID.
     *
     * @param string $langId Language ID
     *
     * @return void
     */
    public function setLang(string $langId): void
    {
        $this->set('lang', $langId);
    }

    /**
     * Get edit feed ID.
     *
     * @return int|null
     */
    public function getEditFeedId(): ?int
    {
        if (!$this->has('edit_feed')) {
            return null;
        }
        return $this->getInt('edit_feed');
    }

    /**
     * Set edit feed ID.
     *
     * @param int $feedId Feed ID
     *
     * @return void
     */
    public function setEditFeedId(int $feedId): void
    {
        $this->set('edit_feed', $feedId);
    }

    /**
     * Get detected feed type string.
     *
     * @return string
     */
    public function getDetectedFeed(): string
    {
        return $this->getString('detected_feed');
    }

    /**
     * Set detected feed type string.
     *
     * @param string $detected Detected type
     *
     * @return void
     */
    public function setDetectedFeed(string $detected): void
    {
        $this->set('detected_feed', $detected);
    }

    /**
     * Get redirect option.
     *
     * @return string
     */
    public function getRedirect(): string
    {
        return $this->getString('redirect');
    }

    /**
     * Set redirect option.
     *
     * @param string $redirect Redirect string
     *
     * @return void
     */
    public function setRedirect(string $redirect): void
    {
        $this->set('redirect', $redirect);
    }

    /**
     * Get selected feed index.
     *
     * @return int
     */
    public function getSelectedFeed(): int
    {
        return $this->getInt('selected_feed');
    }

    /**
     * Set selected feed index.
     *
     * @param int $index Index
     *
     * @return void
     */
    public function setSelectedFeed(int $index): void
    {
        $this->set('selected_feed', $index);
    }

    /**
     * Get max items.
     *
     * @return int
     */
    public function getMaxim(): int
    {
        return $this->getInt('maxim', 1);
    }

    /**
     * Set max items.
     *
     * @param int $maxim Max items
     *
     * @return void
     */
    public function setMaxim(int $maxim): void
    {
        $this->set('maxim', $maxim);
    }

    /**
     * Get select mode.
     *
     * @return string
     */
    public function getSelectMode(): string
    {
        return $this->getString('select_mode', '0');
    }

    /**
     * Set select mode.
     *
     * @param string $mode Mode
     *
     * @return void
     */
    public function setSelectMode(string $mode): void
    {
        $this->set('select_mode', $mode);
    }

    /**
     * Get hide images flag.
     *
     * @return string
     */
    public function getHideImages(): string
    {
        return $this->getString('hide_images', 'yes');
    }

    /**
     * Set hide images flag.
     *
     * @param string $hide Hide flag
     *
     * @return void
     */
    public function setHideImages(string $hide): void
    {
        $this->set('hide_images', $hide);
    }

    /**
     * Get host array.
     *
     * @return array<string, string>
     */
    public function getHost(): array
    {
        /** @var array<string, string> */
        return $this->getArray('host');
    }

    /**
     * Set host status.
     *
     * @param string $hostName Host name
     * @param string $status   Status
     *
     * @return void
     */
    public function setHostStatus(string $hostName, string $status): void
    {
        $host = $this->getHost();
        $host[$hostName] = $status;
        $this->set('host', $host);
    }

    /**
     * Clear host array.
     *
     * @return void
     */
    public function clearHost(): void
    {
        $this->set('host', []);
    }

    /**
     * Get host2 array.
     *
     * @return array<string, string>
     */
    public function getHost2(): array
    {
        /** @var array<string, string> */
        return $this->getArray('host2');
    }

    /**
     * Set host2 status.
     *
     * @param string $hostName Host name
     * @param string $status   Status
     *
     * @return void
     */
    public function setHost2Status(string $hostName, string $status): void
    {
        $host2 = $this->getHost2();
        $host2[$hostName] = $status;
        $this->set('host2', $host2);
    }

    /**
     * Get article section.
     *
     * @return string
     */
    public function getArticleSection(): string
    {
        return $this->getString('article_section');
    }

    /**
     * Set article section.
     *
     * @param string $section Section
     *
     * @return void
     */
    public function setArticleSection(string $section): void
    {
        $this->set('article_section', $section);
    }

    /**
     * Get article selector.
     *
     * @return string
     */
    public function getArticleSelector(): string
    {
        return $this->getString('article_selector');
    }

    /**
     * Set article selector.
     *
     * @param string $selector Selector
     *
     * @return void
     */
    public function setArticleSelector(string $selector): void
    {
        $this->set('article_selector', $selector);
    }

    /**
     * Get feed title from feed data.
     *
     * @return string
     */
    public function getFeedTitle(): string
    {
        $feed = $this->getFeed();
        if (isset($feed['feed_title']) && is_string($feed['feed_title'])) {
            return $feed['feed_title'];
        }
        return '';
    }

    /**
     * Set feed title in feed data.
     *
     * @param string $title Title
     *
     * @return void
     */
    public function setFeedTitle(string $title): void
    {
        $feed = $this->getFeed();
        $feed['feed_title'] = $title;
        $this->setFeed($feed);
    }

    /**
     * Get feed text type from feed data.
     *
     * @return string
     */
    public function getFeedText(): string
    {
        $feed = $this->getFeed();
        if (isset($feed['feed_text']) && is_string($feed['feed_text'])) {
            return $feed['feed_text'];
        }
        return '';
    }

    /**
     * Set feed text type in feed data.
     *
     * @param string $text Text type
     *
     * @return void
     */
    public function setFeedText(string $text): void
    {
        $feed = $this->getFeed();
        $feed['feed_text'] = $text;
        $this->setFeed($feed);
    }

    /**
     * Get feed item by index.
     *
     * @param int $index Item index
     *
     * @return array<string, mixed>|null
     */
    public function getFeedItem(int $index): ?array
    {
        $feed = $this->getFeed();
        if (!isset($feed[$index]) || !is_array($feed[$index])) {
            return null;
        }
        return $feed[$index];
    }

    /**
     * Set feed item by index.
     *
     * @param int                   $index Item index
     * @param array<string, mixed> $item  Item data
     *
     * @return void
     */
    public function setFeedItem(int $index, array $item): void
    {
        $feed = $this->getFeed();
        $feed[$index] = $item;
        $this->setFeed($feed);
    }

    /**
     * Get feed item HTML.
     *
     * @param int $index Item index
     *
     * @return string|null
     */
    public function getFeedItemHtml(int $index): ?string
    {
        $item = $this->getFeedItem($index);
        if ($item === null || !isset($item['html'])) {
            return null;
        }
        return is_string($item['html']) ? $item['html'] : null;
    }

    /**
     * Set feed item HTML.
     *
     * @param int   $index Item index
     * @param mixed $html  HTML content
     *
     * @return void
     *
     * @psalm-suppress MixedAssignment
     */
    public function setFeedItemHtml(int $index, mixed $html): void
    {
        $item = $this->getFeedItem($index);
        if ($item === null) {
            $item = [];
        }
        $item['html'] = $html;
        $this->setFeedItem($index, $item);
    }

    /**
     * Count numeric feed items.
     *
     * @return int
     */
    public function countFeedItems(): int
    {
        $feed = $this->getFeed();
        return count(array_filter(array_keys($feed), 'is_numeric'));
    }
}
