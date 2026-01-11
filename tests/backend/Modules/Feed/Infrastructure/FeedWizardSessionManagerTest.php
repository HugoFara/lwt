<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Infrastructure;

use Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FeedWizardSessionManager.
 *
 * Tests session state management for the Feed wizard workflow.
 *
 * @covers \Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager
 */
class FeedWizardSessionManagerTest extends TestCase
{
    private FeedWizardSessionManager $manager;

    protected function setUp(): void
    {
        // Clear any existing session data
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        $this->manager = new FeedWizardSessionManager();
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // ===================================
    // BASIC SESSION OPERATIONS
    // ===================================

    public function testExistsReturnsFalseWhenNoSession(): void
    {
        $this->assertFalse($this->manager->exists());
    }

    public function testExistsReturnsTrueAfterSettingValue(): void
    {
        $this->manager->set('test_key', 'test_value');
        $this->assertTrue($this->manager->exists());
    }

    public function testClearRemovesWizardSession(): void
    {
        $this->manager->set('test_key', 'test_value');
        $this->assertTrue($this->manager->exists());

        $this->manager->clear();
        $this->assertFalse($this->manager->exists());
    }

    public function testGetAllReturnsEmptyArrayWhenNoSession(): void
    {
        $result = $this->manager->getAll();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAllReturnsAllSetValues(): void
    {
        $this->manager->set('key1', 'value1');
        $this->manager->set('key2', 'value2');

        $result = $this->manager->getAll();

        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key2', $result);
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
    }

    // ===================================
    // STRING VALUE OPERATIONS
    // ===================================

    public function testGetStringReturnsDefaultWhenKeyNotSet(): void
    {
        $result = $this->manager->getString('nonexistent', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testGetStringReturnsSetValue(): void
    {
        $this->manager->set('my_string', 'hello world');
        $result = $this->manager->getString('my_string');
        $this->assertEquals('hello world', $result);
    }

    public function testGetStringReturnsDefaultForNonStringValue(): void
    {
        $this->manager->set('not_a_string', ['array', 'value']);
        $result = $this->manager->getString('not_a_string', 'default');
        $this->assertEquals('default', $result);
    }

    // ===================================
    // INTEGER VALUE OPERATIONS
    // ===================================

    public function testGetIntReturnsDefaultWhenKeyNotSet(): void
    {
        $result = $this->manager->getInt('nonexistent', 42);
        $this->assertEquals(42, $result);
    }

    public function testGetIntReturnsSetValue(): void
    {
        $this->manager->set('my_int', 123);
        $result = $this->manager->getInt('my_int');
        $this->assertEquals(123, $result);
    }

    public function testGetIntParsesNumericString(): void
    {
        $this->manager->set('numeric_string', '456');
        $result = $this->manager->getInt('numeric_string');
        $this->assertEquals(456, $result);
    }

    public function testGetIntReturnsDefaultForNonNumericValue(): void
    {
        $this->manager->set('not_numeric', 'abc');
        $result = $this->manager->getInt('not_numeric', 99);
        $this->assertEquals(99, $result);
    }

    // ===================================
    // ARRAY VALUE OPERATIONS
    // ===================================

    public function testGetArrayReturnsEmptyArrayWhenKeyNotSet(): void
    {
        $result = $this->manager->getArray('nonexistent');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetArrayReturnsSetValue(): void
    {
        $testArray = ['a' => 1, 'b' => 2];
        $this->manager->set('my_array', $testArray);
        $result = $this->manager->getArray('my_array');
        $this->assertEquals($testArray, $result);
    }

    public function testGetArrayReturnsEmptyArrayForNonArrayValue(): void
    {
        $this->manager->set('not_array', 'string value');
        $result = $this->manager->getArray('not_array');
        $this->assertEmpty($result);
    }

    // ===================================
    // HAS AND REMOVE OPERATIONS
    // ===================================

    public function testHasReturnsFalseWhenKeyNotSet(): void
    {
        $this->assertFalse($this->manager->has('nonexistent'));
    }

    public function testHasReturnsTrueWhenKeySet(): void
    {
        $this->manager->set('existing_key', 'value');
        $this->assertTrue($this->manager->has('existing_key'));
    }

    public function testRemoveDeletesKey(): void
    {
        $this->manager->set('to_remove', 'value');
        $this->assertTrue($this->manager->has('to_remove'));

        $this->manager->remove('to_remove');
        $this->assertFalse($this->manager->has('to_remove'));
    }

    public function testRemoveNonexistentKeyDoesNotError(): void
    {
        // Should not throw an exception
        $this->manager->remove('nonexistent_key');
        $this->assertFalse($this->manager->has('nonexistent_key'));
    }

    // ===================================
    // FEED DATA OPERATIONS
    // ===================================

    public function testGetFeedReturnsEmptyArrayWhenNotSet(): void
    {
        $result = $this->manager->getFeed();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSetFeedAndGetFeed(): void
    {
        $feedData = [
            'feed_title' => 'Test Feed',
            'feed_text' => 'description',
            0 => ['link' => 'http://example.com', 'title' => 'Article 1'],
            1 => ['link' => 'http://example.com/2', 'title' => 'Article 2'],
        ];

        $this->manager->setFeed($feedData);
        $result = $this->manager->getFeed();

        $this->assertEquals($feedData, $result);
    }

    public function testGetFeedTitleReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getFeedTitle();
        $this->assertEquals('', $result);
    }

    public function testSetFeedTitleAndGetFeedTitle(): void
    {
        $this->manager->setFeedTitle('My Test Feed');
        $result = $this->manager->getFeedTitle();
        $this->assertEquals('My Test Feed', $result);
    }

    public function testGetFeedTextReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getFeedText();
        $this->assertEquals('', $result);
    }

    public function testSetFeedTextAndGetFeedText(): void
    {
        $this->manager->setFeedText('description');
        $result = $this->manager->getFeedText();
        $this->assertEquals('description', $result);
    }

    // ===================================
    // FEED ITEM OPERATIONS
    // ===================================

    public function testGetFeedItemReturnsNullWhenNotSet(): void
    {
        $result = $this->manager->getFeedItem(0);
        $this->assertNull($result);
    }

    public function testSetFeedItemAndGetFeedItem(): void
    {
        $item = [
            'link' => 'http://example.com/article',
            'title' => 'Test Article',
            'text' => 'Article content...',
        ];

        $this->manager->setFeedItem(0, $item);
        $result = $this->manager->getFeedItem(0);

        $this->assertEquals($item, $result);
    }

    public function testGetFeedItemHtmlReturnsNullWhenNotSet(): void
    {
        $this->manager->setFeedItem(0, ['link' => 'http://example.com']);
        $result = $this->manager->getFeedItemHtml(0);
        $this->assertNull($result);
    }

    public function testSetFeedItemHtmlAndGetFeedItemHtml(): void
    {
        $this->manager->setFeedItem(0, ['link' => 'http://example.com']);
        $this->manager->setFeedItemHtml(0, '<p>HTML content</p>');

        $result = $this->manager->getFeedItemHtml(0);
        $this->assertEquals('<p>HTML content</p>', $result);
    }

    public function testCountFeedItemsReturnsZeroWhenEmpty(): void
    {
        $result = $this->manager->countFeedItems();
        $this->assertEquals(0, $result);
    }

    public function testCountFeedItemsCountsOnlyNumericKeys(): void
    {
        $feedData = [
            'feed_title' => 'Test Feed',  // Non-numeric key
            'feed_text' => 'description', // Non-numeric key
            0 => ['link' => 'http://example.com/1'],
            1 => ['link' => 'http://example.com/2'],
            2 => ['link' => 'http://example.com/3'],
        ];

        $this->manager->setFeed($feedData);
        $result = $this->manager->countFeedItems();

        $this->assertEquals(3, $result);
    }

    // ===================================
    // RSS URL OPERATIONS
    // ===================================

    public function testGetRssUrlReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getRssUrl();
        $this->assertEquals('', $result);
    }

    public function testSetRssUrlAndGetRssUrl(): void
    {
        $url = 'https://example.com/feed.xml';
        $this->manager->setRssUrl($url);
        $result = $this->manager->getRssUrl();
        $this->assertEquals($url, $result);
    }

    // ===================================
    // ARTICLE TAGS OPERATIONS
    // ===================================

    public function testGetArticleTagsReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getArticleTags();
        $this->assertEquals('', $result);
    }

    public function testSetArticleTagsAndGetArticleTags(): void
    {
        $tags = '<li>tag1</li><li>tag2</li>';
        $this->manager->setArticleTags($tags);
        $result = $this->manager->getArticleTags();
        $this->assertEquals($tags, $result);
    }

    // ===================================
    // FILTER TAGS OPERATIONS
    // ===================================

    public function testGetFilterTagsReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getFilterTags();
        $this->assertEquals('', $result);
    }

    public function testSetFilterTagsAndGetFilterTags(): void
    {
        $tags = '<li>filter1</li>';
        $this->manager->setFilterTags($tags);
        $result = $this->manager->getFilterTags();
        $this->assertEquals($tags, $result);
    }

    // ===================================
    // OPTIONS OPERATIONS
    // ===================================

    public function testGetOptionsReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getOptions();
        $this->assertEquals('', $result);
    }

    public function testSetOptionsAndGetOptions(): void
    {
        $options = 'edit_text=1,max_texts=10';
        $this->manager->setOptions($options);
        $result = $this->manager->getOptions();
        $this->assertEquals($options, $result);
    }

    // ===================================
    // LANGUAGE OPERATIONS
    // ===================================

    public function testGetLangReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getLang();
        $this->assertEquals('', $result);
    }

    public function testSetLangAndGetLang(): void
    {
        $this->manager->setLang('5');
        $result = $this->manager->getLang();
        $this->assertEquals('5', $result);
    }

    // ===================================
    // EDIT FEED ID OPERATIONS
    // ===================================

    public function testGetEditFeedIdReturnsNullWhenNotSet(): void
    {
        $result = $this->manager->getEditFeedId();
        $this->assertNull($result);
    }

    public function testSetEditFeedIdAndGetEditFeedId(): void
    {
        $this->manager->setEditFeedId(42);
        $result = $this->manager->getEditFeedId();
        $this->assertEquals(42, $result);
    }

    // ===================================
    // DETECTED FEED OPERATIONS
    // ===================================

    public function testGetDetectedFeedReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getDetectedFeed();
        $this->assertEquals('', $result);
    }

    public function testSetDetectedFeedAndGetDetectedFeed(): void
    {
        $detected = 'Detected: «RSS 2.0»';
        $this->manager->setDetectedFeed($detected);
        $result = $this->manager->getDetectedFeed();
        $this->assertEquals($detected, $result);
    }

    // ===================================
    // REDIRECT OPERATIONS
    // ===================================

    public function testGetRedirectReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getRedirect();
        $this->assertEquals('', $result);
    }

    public function testSetRedirectAndGetRedirect(): void
    {
        $redirect = 'redirect http://example.com | ';
        $this->manager->setRedirect($redirect);
        $result = $this->manager->getRedirect();
        $this->assertEquals($redirect, $result);
    }

    // ===================================
    // SELECTED FEED OPERATIONS
    // ===================================

    public function testGetSelectedFeedReturnsZeroWhenNotSet(): void
    {
        $result = $this->manager->getSelectedFeed();
        $this->assertEquals(0, $result);
    }

    public function testSetSelectedFeedAndGetSelectedFeed(): void
    {
        $this->manager->setSelectedFeed(3);
        $result = $this->manager->getSelectedFeed();
        $this->assertEquals(3, $result);
    }

    // ===================================
    // MAXIM OPERATIONS
    // ===================================

    public function testGetMaximReturnsOneWhenNotSet(): void
    {
        $result = $this->manager->getMaxim();
        $this->assertEquals(1, $result);
    }

    public function testSetMaximAndGetMaxim(): void
    {
        $this->manager->setMaxim(5);
        $result = $this->manager->getMaxim();
        $this->assertEquals(5, $result);
    }

    // ===================================
    // SELECT MODE OPERATIONS
    // ===================================

    public function testGetSelectModeReturnsZeroWhenNotSet(): void
    {
        $result = $this->manager->getSelectMode();
        $this->assertEquals('0', $result);
    }

    public function testSetSelectModeAndGetSelectMode(): void
    {
        $this->manager->setSelectMode('smart');
        $result = $this->manager->getSelectMode();
        $this->assertEquals('smart', $result);
    }

    // ===================================
    // HIDE IMAGES OPERATIONS
    // ===================================

    public function testGetHideImagesReturnsYesWhenNotSet(): void
    {
        $result = $this->manager->getHideImages();
        $this->assertEquals('yes', $result);
    }

    public function testSetHideImagesAndGetHideImages(): void
    {
        $this->manager->setHideImages('no');
        $result = $this->manager->getHideImages();
        $this->assertEquals('no', $result);
    }

    // ===================================
    // HOST OPERATIONS
    // ===================================

    public function testGetHostReturnsEmptyArrayWhenNotSet(): void
    {
        $result = $this->manager->getHost();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSetHostStatusAndGetHost(): void
    {
        $this->manager->setHostStatus('example.com', 'allowed');
        $this->manager->setHostStatus('test.com', 'blocked');

        $result = $this->manager->getHost();

        $this->assertArrayHasKey('example.com', $result);
        $this->assertArrayHasKey('test.com', $result);
        $this->assertEquals('allowed', $result['example.com']);
        $this->assertEquals('blocked', $result['test.com']);
    }

    public function testClearHostRemovesAllHosts(): void
    {
        $this->manager->setHostStatus('example.com', 'allowed');
        $this->manager->clearHost();

        $result = $this->manager->getHost();
        $this->assertEmpty($result);
    }

    // ===================================
    // HOST2 OPERATIONS
    // ===================================

    public function testGetHost2ReturnsEmptyArrayWhenNotSet(): void
    {
        $result = $this->manager->getHost2();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSetHost2StatusAndGetHost2(): void
    {
        $this->manager->setHost2Status('example.com', 'status1');
        $result = $this->manager->getHost2();

        $this->assertArrayHasKey('example.com', $result);
        $this->assertEquals('status1', $result['example.com']);
    }

    // ===================================
    // ARTICLE SECTION OPERATIONS
    // ===================================

    public function testGetArticleSectionReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getArticleSection();
        $this->assertEquals('', $result);
    }

    public function testSetArticleSectionAndGetArticleSection(): void
    {
        $section = 'article.content';
        $this->manager->setArticleSection($section);
        $result = $this->manager->getArticleSection();
        $this->assertEquals($section, $result);
    }

    // ===================================
    // ARTICLE SELECTOR OPERATIONS
    // ===================================

    public function testGetArticleSelectorReturnsEmptyStringWhenNotSet(): void
    {
        $result = $this->manager->getArticleSelector();
        $this->assertEquals('', $result);
    }

    public function testSetArticleSelectorAndGetArticleSelector(): void
    {
        $selector = 'div.main-content';
        $this->manager->setArticleSelector($selector);
        $result = $this->manager->getArticleSelector();
        $this->assertEquals($selector, $result);
    }

    // ===================================
    // INTEGRATION TESTS
    // ===================================

    public function testFullWizardWorkflow(): void
    {
        // Step 1: Set RSS URL
        $this->manager->setRssUrl('https://example.com/feed.xml');

        // Step 2: Set detected feed data
        $feedData = [
            'feed_title' => 'Example Feed',
            'feed_text' => 'description',
            0 => ['link' => 'http://example.com/1', 'title' => 'Article 1'],
            1 => ['link' => 'http://example.com/2', 'title' => 'Article 2'],
        ];
        $this->manager->setFeed($feedData);
        $this->manager->setDetectedFeed('Detected: «RSS 2.0»');

        // Step 3: Set options
        $this->manager->setOptions('edit_text=1');
        $this->manager->setLang('1');
        $this->manager->setArticleTags('<li>div.content</li>');

        // Step 4: Verify all values
        $this->assertEquals('https://example.com/feed.xml', $this->manager->getRssUrl());
        $this->assertEquals('Example Feed', $this->manager->getFeedTitle());
        $this->assertEquals(2, $this->manager->countFeedItems());
        $this->assertEquals('Detected: «RSS 2.0»', $this->manager->getDetectedFeed());
        $this->assertEquals('edit_text=1', $this->manager->getOptions());
        $this->assertEquals('1', $this->manager->getLang());
        $this->assertEquals('<li>div.content</li>', $this->manager->getArticleTags());

        // Step 5: Clear session
        $this->manager->clear();
        $this->assertFalse($this->manager->exists());
    }

    public function testSessionPersistenceAcrossManagerInstances(): void
    {
        // Set data with first manager
        $this->manager->setRssUrl('https://example.com/feed.xml');
        $this->manager->setFeedTitle('Test Feed');

        // Create new manager instance (simulates new request)
        $newManager = new FeedWizardSessionManager();

        // Data should persist
        $this->assertEquals('https://example.com/feed.xml', $newManager->getRssUrl());
        $this->assertEquals('Test Feed', $newManager->getFeedTitle());
    }
}
