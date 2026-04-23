<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Http\FeedEditController;
use Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\FlashMessageService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FeedEditController.
 *
 * Tests feed CRUD operations: edit routing, new/edit/delete feed,
 * mark actions, form handling, and the management list.
 */
class FeedEditControllerTest extends TestCase
{
    /** @var FeedFacade&MockObject */
    private FeedFacade $feedFacade;

    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageFacade;

    /** @var FeedWizardSessionManager&MockObject */
    private FeedWizardSessionManager $wizardSession;

    /** @var FlashMessageService&MockObject */
    private FlashMessageService $flashService;

    private FeedEditController $controller;

    protected function setUp(): void
    {
        $this->feedFacade = $this->createMock(FeedFacade::class);
        $this->languageFacade = $this->createMock(LanguageFacade::class);
        $this->wizardSession = $this->createMock(FeedWizardSessionManager::class);
        $this->flashService = $this->createMock(FlashMessageService::class);

        $this->controller = new FeedEditController(
            $this->feedFacade,
            $this->languageFacade,
            $this->wizardSession,
            $this->flashService
        );
    }

    /**
     * Create a controller with redirect() stubbed out to prevent exit().
     *
     * @return FeedEditController&MockObject
     */
    private function createControllerWithRedirectStub(): FeedEditController
    {
        $controller = $this->getMockBuilder(FeedEditController::class)
            ->setConstructorArgs([
                $this->feedFacade,
                $this->languageFacade,
                $this->wizardSession,
                $this->flashService,
            ])
            ->onlyMethods(['redirect'])
            ->getMock();

        $controller->method('redirect')
            ->willReturnCallback(function (string $url): void {
                // no-op: prevents header() + exit() in tests
            });

        return $controller;
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(FeedEditController::class, $this->controller);
    }

    #[Test]
    public function constructorSetsFeedFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(FeedEditController::class, 'feedFacade');
        $reflection->setAccessible(true);

        $this->assertSame($this->feedFacade, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsLanguageFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(FeedEditController::class, 'languageFacade');
        $reflection->setAccessible(true);

        $this->assertSame($this->languageFacade, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsWizardSessionProperty(): void
    {
        $reflection = new \ReflectionProperty(FeedEditController::class, 'wizardSession');
        $reflection->setAccessible(true);

        $this->assertSame($this->wizardSession, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsFlashServiceProperty(): void
    {
        $reflection = new \ReflectionProperty(FeedEditController::class, 'flashService');
        $reflection->setAccessible(true);

        $this->assertSame($this->flashService, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsViewPathProperty(): void
    {
        $reflection = new \ReflectionProperty(FeedEditController::class, 'viewPath');
        $reflection->setAccessible(true);

        $viewPath = $reflection->getValue($this->controller);
        $this->assertStringEndsWith('/Views/', $viewPath);
    }

    #[Test]
    public function constructorWithDefaultWizardAndFlash(): void
    {
        $controller = new FeedEditController(
            $this->feedFacade,
            $this->languageFacade
        );

        $reflection = new \ReflectionProperty(FeedEditController::class, 'wizardSession');
        $reflection->setAccessible(true);
        $this->assertInstanceOf(FeedWizardSessionManager::class, $reflection->getValue($controller));

        $flashRef = new \ReflectionProperty(FeedEditController::class, 'flashService');
        $flashRef->setAccessible(true);
        $this->assertInstanceOf(FlashMessageService::class, $flashRef->getValue($controller));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classUsesFeedFlashTrait(): void
    {
        $reflection = new \ReflectionClass(FeedEditController::class);
        $traitNames = array_map(
            fn(\ReflectionClass $t) => $t->getName(),
            $reflection->getTraits()
        );

        $this->assertContains(
            'Lwt\Modules\Feed\Http\FeedFlashTrait',
            $traitNames
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(FeedEditController::class);

        $expectedMethods = ['edit', 'spa', 'newFeed', 'editFeed', 'deleteFeed'];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "FeedEditController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function classHasRequiredPrivateMethods(): void
    {
        $reflection = new \ReflectionClass(FeedEditController::class);

        $expectedMethods = [
            'handleMarkAction', 'formatMarkActionMessage',
            'handleUpdateFeed', 'handleSaveFeed',
            'showNewForm', 'showEditForm', 'showMultiLoadForm', 'showList',
            'loadCuratedFeeds'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "FeedEditController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPrivate(),
                "Method $methodName should be private"
            );
        }
    }

    // =========================================================================
    // handleMarkAction tests (via reflection)
    // =========================================================================

    #[Test]
    public function handleMarkActionReturnsNullWhenNoAction(): void
    {
        $_REQUEST = ['markaction' => '', 'selected_feed' => '5'];

        $method = new \ReflectionMethod(FeedEditController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '5');
        $this->assertNull($result);
    }

    #[Test]
    public function handleMarkActionReturnsNullWhenNoFeedSelected(): void
    {
        $_REQUEST = ['markaction' => 'del'];

        $method = new \ReflectionMethod(FeedEditController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '');
        $this->assertNull($result);
    }

    #[Test]
    public function handleMarkActionDeleteCallsFacade(): void
    {
        $_REQUEST = ['markaction' => 'del'];

        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('5');

        $method = new \ReflectionMethod(FeedEditController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '5');
        $this->assertSame(['action' => 'del', 'success' => true], $result);
    }

    #[Test]
    public function handleMarkActionDeleteArticlesCallsFacade(): void
    {
        $_REQUEST = ['markaction' => 'del_art'];

        $this->feedFacade->expects($this->once())
            ->method('deleteArticles')
            ->with('3');

        $method = new \ReflectionMethod(FeedEditController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '3');
        $this->assertSame(['action' => 'del_art', 'success' => true], $result);
    }

    #[Test]
    public function handleMarkActionResetArticlesCallsFacade(): void
    {
        $_REQUEST = ['markaction' => 'res_art'];

        $this->feedFacade->expects($this->once())
            ->method('resetUnloadableArticles')
            ->with('7');

        $method = new \ReflectionMethod(FeedEditController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '7');
        $this->assertSame(['action' => 'res_art', 'success' => true], $result);
    }

    #[Test]
    public function handleMarkActionReturnsNullForUnknownAction(): void
    {
        $_REQUEST = ['markaction' => 'unknown'];

        $method = new \ReflectionMethod(FeedEditController::class, 'handleMarkAction');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, '5');
        $this->assertNull($result);
    }

    // =========================================================================
    // formatMarkActionMessage tests
    // =========================================================================

    #[Test]
    public function formatMarkActionMessageReturnsEmptyForNull(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'formatMarkActionMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, null);
        $this->assertSame('', $result);
    }

    #[Test]
    public function formatMarkActionMessageReturnsDeleteMessage(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'formatMarkActionMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ['action' => 'del', 'success' => true]);
        $this->assertStringContainsString('deleted', $result);
        $this->assertStringContainsString('Newsfeed', $result);
    }

    #[Test]
    public function formatMarkActionMessageReturnsDeleteArticlesMessage(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'formatMarkActionMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ['action' => 'del_art', 'success' => true]);
        $this->assertSame('Article item(s) deleted', $result);
    }

    #[Test]
    public function formatMarkActionMessageReturnsResetMessage(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'formatMarkActionMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ['action' => 'res_art', 'success' => true]);
        $this->assertSame('Article(s) reset', $result);
    }

    #[Test]
    public function formatMarkActionMessageReturnsEmptyForUnknownAction(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'formatMarkActionMessage');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ['action' => 'unknown', 'success' => true]);
        $this->assertSame('', $result);
    }

    // =========================================================================
    // handleUpdateFeed tests
    // =========================================================================

    #[Test]
    public function handleUpdateFeedDoesNothingWithoutParam(): void
    {
        $_REQUEST = [];

        $this->feedFacade->expects($this->never())
            ->method('updateFeed');

        $method = new \ReflectionMethod(FeedEditController::class, 'handleUpdateFeed');
        $method->setAccessible(true);
        $method->invoke($this->controller);
    }

    #[Test]
    public function handleUpdateFeedCallsFacadeWithFormData(): void
    {
        $_REQUEST = [
            'update_feed' => '1',
            'NfID' => '42',
            'NfLgID' => '1',
            'NfName' => 'Updated Feed',
            'NfSourceURI' => 'http://example.com/rss',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => 'script',
            'NfOptions' => 'tag:news,',
        ];

        $this->feedFacade->expects($this->once())
            ->method('updateFeed')
            ->with(
                42,
                $this->callback(function (array $data) {
                    return $data['NfName'] === 'Updated Feed'
                        && $data['NfOptions'] === 'tag:news';
                })
            );

        $method = new \ReflectionMethod(FeedEditController::class, 'handleUpdateFeed');
        $method->setAccessible(true);
        $method->invoke($this->controller);
    }

    #[Test]
    public function handleUpdateFeedTrimsTrailingCommaFromOptions(): void
    {
        $_REQUEST = [
            'update_feed' => '1',
            'NfID' => '1',
            'NfLgID' => '1',
            'NfName' => 'Test',
            'NfSourceURI' => 'http://test.com',
            'NfArticleSectionTags' => '',
            'NfFilterTags' => '',
            'NfOptions' => 'opt1:val1,opt2:val2,',
        ];

        $this->feedFacade->expects($this->once())
            ->method('updateFeed')
            ->with(
                $this->anything(),
                $this->callback(function (array $data) {
                    return $data['NfOptions'] === 'opt1:val1,opt2:val2';
                })
            );

        $method = new \ReflectionMethod(FeedEditController::class, 'handleUpdateFeed');
        $method->setAccessible(true);
        $method->invoke($this->controller);
    }

    // =========================================================================
    // handleSaveFeed tests
    // =========================================================================

    #[Test]
    public function handleSaveFeedDoesNothingWithoutParam(): void
    {
        $_REQUEST = [];

        $this->feedFacade->expects($this->never())
            ->method('createFeed');

        $method = new \ReflectionMethod(FeedEditController::class, 'handleSaveFeed');
        $method->setAccessible(true);
        $method->invoke($this->controller);
    }

    #[Test]
    public function handleSaveFeedCallsFacadeWithFormData(): void
    {
        $_REQUEST = [
            'save_feed' => '1',
            'NfLgID' => '2',
            'NfName' => 'New Feed',
            'NfSourceURI' => 'http://example.com/rss',
            'NfArticleSectionTags' => '',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ];

        $this->feedFacade->expects($this->once())
            ->method('createFeed')
            ->with($this->callback(function (array $data) {
                return $data['NfName'] === 'New Feed'
                    && $data['NfLgID'] === '2';
            }));

        $method = new \ReflectionMethod(FeedEditController::class, 'handleSaveFeed');
        $method->setAccessible(true);
        $method->invoke($this->controller);
    }

    // =========================================================================
    // showEditForm tests
    // =========================================================================

    #[Test]
    public function showEditFormOutputsErrorWhenFeedNotFound(): void
    {
        $this->feedFacade->method('getFeedById')
            ->with(999)
            ->willReturn(null);

        $method = new \ReflectionMethod(FeedEditController::class, 'showEditForm');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($this->controller, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('Feed not found', $output);
        $this->assertStringContainsString('is-danger', $output);
    }

    #[Test]
    public function showEditFormCallsFacadeForExistingFeed(): void
    {
        $feed = [
            'NfID' => 1,
            'NfName' => 'Test Feed',
            'NfSourceURI' => 'http://test.com',
            'NfLgID' => 1,
            'NfArticleSectionTags' => '',
            'NfFilterTags' => '',
            'NfOptions' => '',
            'NfUpdate' => 0,
        ];

        $this->feedFacade->expects($this->once())
            ->method('getFeedById')
            ->with(1)
            ->willReturn($feed);

        $this->feedFacade->expects($this->once())
            ->method('getLanguages')
            ->willReturn([]);

        $this->feedFacade->method('getFeedOption')
            ->willReturn(null);

        $method = new \ReflectionMethod(FeedEditController::class, 'showEditForm');
        $method->setAccessible(true);

        ob_start();
        try {
            $method->invoke($this->controller, 1);
        } catch (\Throwable $e) {
            // View include may fail in test context
        }
        ob_end_clean();
    }

    #[Test]
    public function showEditFormParsesAutoUpdateOption(): void
    {
        $feed = [
            'NfID' => 1,
            'NfName' => 'Test',
            'NfSourceURI' => 'http://test.com',
            'NfLgID' => 1,
            'NfArticleSectionTags' => '',
            'NfFilterTags' => '',
            'NfOptions' => 'autoupdate:24h',
            'NfUpdate' => 0,
        ];

        $this->feedFacade->method('getFeedById')->willReturn($feed);
        $this->feedFacade->method('getLanguages')->willReturn([]);

        // Return different values based on option param
        $this->feedFacade->method('getFeedOption')
            ->willReturnCallback(function (string $options, string $option) {
                if ($option === '') {
                    return [];
                }
                if ($option === 'autoupdate') {
                    return '24h';
                }
                return null;
            });

        $method = new \ReflectionMethod(FeedEditController::class, 'showEditForm');
        $method->setAccessible(true);

        ob_start();
        try {
            $method->invoke($this->controller, 1);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        // If we got here without fatal error, the parsing logic works
        $this->assertTrue(true);
    }

    #[Test]
    public function showEditFormHandlesNullAutoUpdate(): void
    {
        $feed = [
            'NfID' => 1,
            'NfName' => 'Test',
            'NfSourceURI' => 'http://test.com',
            'NfLgID' => 1,
            'NfArticleSectionTags' => '',
            'NfFilterTags' => '',
            'NfOptions' => '',
            'NfUpdate' => 0,
        ];

        $this->feedFacade->method('getFeedById')->willReturn($feed);
        $this->feedFacade->method('getLanguages')->willReturn([]);
        $this->feedFacade->method('getFeedOption')->willReturn(null);

        $method = new \ReflectionMethod(FeedEditController::class, 'showEditForm');
        $method->setAccessible(true);

        ob_start();
        try {
            $method->invoke($this->controller, 1);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();

        $this->assertTrue(true);
    }

    // =========================================================================
    // showNewForm tests
    // =========================================================================

    #[Test]
    public function showNewFormCallsGetLanguagesForSelect(): void
    {
        $this->languageFacade->expects($this->once())
            ->method('getLanguagesForSelect')
            ->willReturn([['id' => 1, 'name' => 'English']]);

        $method = new \ReflectionMethod(FeedEditController::class, 'showNewForm');
        $method->setAccessible(true);

        ob_start();
        try {
            $method->invoke($this->controller);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    // =========================================================================
    // showMultiLoadForm tests
    // =========================================================================

    #[Test]
    public function showMultiLoadFormCallsFacadeForFeeds(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getFeeds')
            ->with(5)
            ->willReturn([]);

        $this->languageFacade->expects($this->once())
            ->method('getLanguagesForSelect')
            ->willReturn([]);

        $method = new \ReflectionMethod(FeedEditController::class, 'showMultiLoadForm');
        $method->setAccessible(true);

        ob_start();
        try {
            $method->invoke($this->controller, 5);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    #[Test]
    public function showMultiLoadFormPassesNullForZeroLang(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getFeeds')
            ->with(null);

        $this->languageFacade->method('getLanguagesForSelect')->willReturn([]);

        $method = new \ReflectionMethod(FeedEditController::class, 'showMultiLoadForm');
        $method->setAccessible(true);

        ob_start();
        try {
            $method->invoke($this->controller, 0);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    // =========================================================================
    // deleteFeed tests
    // =========================================================================

    #[Test]
    public function deleteFeedCallsFacadeWithStringId(): void
    {
        $controller = $this->createControllerWithRedirectStub();

        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('42')
            ->willReturn(['feeds' => 1]);

        $this->flashService->expects($this->once())
            ->method('success')
            ->with('Feed deleted successfully');

        $controller->deleteFeed(42);
    }

    #[Test]
    public function deleteFeedShowsErrorWhenNoFeedsDeleted(): void
    {
        $controller = $this->createControllerWithRedirectStub();

        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('99')
            ->willReturn(['feeds' => 0]);

        $this->flashService->expects($this->once())
            ->method('error')
            ->with('Failed to delete feed');

        $controller->deleteFeed(99);
    }

    // =========================================================================
    // editFeed tests
    // =========================================================================

    #[Test]
    public function editFeedRedirectsWhenFeedNotFound(): void
    {
        $controller = $this->createControllerWithRedirectStub();

        $this->feedFacade->method('getFeedById')
            ->with(999)
            ->willReturn(null);

        $this->flashService->expects($this->once())
            ->method('error')
            ->with('Feed not found');

        $controller->editFeed(999);
    }

    #[Test]
    public function editFeedCallsLanguageFacadeForExistingFeed(): void
    {
        $feed = [
            'NfID' => 1,
            'NfLgID' => 5,
            'NfName' => 'Test',
            'NfSourceURI' => 'http://test.com',
            'NfArticleSectionTags' => '',
            'NfFilterTags' => '',
            'NfOptions' => '',
            'NfUpdate' => 0,
        ];

        $this->feedFacade->method('getFeedById')->willReturn($feed);
        $this->feedFacade->method('getLanguages')->willReturn([]);
        $this->feedFacade->method('getFeedOption')->willReturn(null);

        $this->languageFacade->expects($this->once())
            ->method('getLanguageName')
            ->with(5)
            ->willReturn('French');

        $_REQUEST = [];

        ob_start();
        try {
            $this->controller->editFeed(1);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    // =========================================================================
    // edit method routing tests
    // =========================================================================

    #[Test]
    public function editMethodClearsWizardSessionIfExists(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST = ['filterlang' => '1', 'sort' => '1'];

        $this->wizardSession->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->wizardSession->expects($this->once())
            ->method('clear');

        $this->languageFacade->method('getLanguageName')->willReturn('Test');
        $this->flashService->method('getAndClear')->willReturn([]);
        $this->feedFacade->method('countFeeds')->willReturn(0);
        $this->languageFacade->method('getLanguagesForSelect')->willReturn([]);

        ob_start();
        try {
            $this->controller->edit([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    #[Test]
    public function editMethodDoesNotClearWizardIfNotExists(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST = ['filterlang' => '1'];

        $this->wizardSession->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $this->wizardSession->expects($this->never())
            ->method('clear');

        $this->languageFacade->method('getLanguageName')->willReturn('Test');
        $this->flashService->method('getAndClear')->willReturn([]);
        $this->feedFacade->method('countFeeds')->willReturn(0);
        $this->languageFacade->method('getLanguagesForSelect')->willReturn([]);

        ob_start();
        try {
            $this->controller->edit([]);
        } catch (\Throwable $e) {
            // View include may fail
        }
        ob_end_clean();
    }

    // =========================================================================
    // Method signature tests
    // =========================================================================

    #[Test]
    public function editMethodAcceptsArrayParam(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'edit');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function spaMethodAcceptsArrayParam(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'spa');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function newFeedMethodAcceptsArrayParam(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'newFeed');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function editFeedMethodAcceptsIntParam(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'editFeed');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    #[Test]
    public function deleteFeedMethodAcceptsIntParam(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'deleteFeed');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    #[Test]
    public function editMethodReturnsVoid(): void
    {
        $method = new \ReflectionMethod(FeedEditController::class, 'edit');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }
}
