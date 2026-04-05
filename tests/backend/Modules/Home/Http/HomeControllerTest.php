<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Home\Http;

use Lwt\Modules\Home\Http\HomeController;
use Lwt\Modules\Home\Application\HomeFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for HomeController.
 *
 * Tests constructor injection, getHomeFacade accessor,
 * index() facade interactions, and dashboard data processing.
 */
class HomeControllerTest extends TestCase
{
    /** @var HomeFacade&MockObject */
    private HomeFacade $homeFacade;

    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageFacade;

    private HomeController $controller;

    private TestableHomeController $testableController;

    protected function setUp(): void
    {
        $this->homeFacade = $this->createMock(HomeFacade::class);
        $this->languageFacade = $this->createMock(LanguageFacade::class);
        $this->controller = new HomeController($this->homeFacade, $this->languageFacade);
        $this->testableController = new TestableHomeController(
            $this->homeFacade,
            $this->languageFacade
        );
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(HomeController::class, $this->controller);
    }

    #[Test]
    public function constructorSetsHomeFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(HomeController::class, 'homeFacade');
        $reflection->setAccessible(true);

        $this->assertSame($this->homeFacade, $reflection->getValue($this->controller));
    }

    #[Test]
    public function constructorSetsLanguageFacadeProperty(): void
    {
        $reflection = new \ReflectionProperty(HomeController::class, 'languageFacade');
        $reflection->setAccessible(true);

        $this->assertSame($this->languageFacade, $reflection->getValue($this->controller));
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    #[Test]
    public function classExtendsBaseController(): void
    {
        $reflection = new \ReflectionClass(HomeController::class);
        $this->assertSame(
            'Lwt\Shared\Http\BaseController',
            $reflection->getParentClass()->getName()
        );
    }

    #[Test]
    public function classHasRequiredPublicMethods(): void
    {
        $reflection = new \ReflectionClass(HomeController::class);

        $expectedMethods = ['index', 'getHomeFacade'];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "HomeController should have method: $methodName"
            );
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method $methodName should be public"
            );
        }
    }

    #[Test]
    public function indexMethodAcceptsArrayParam(): void
    {
        $method = new \ReflectionMethod(HomeController::class, 'index');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
    }

    #[Test]
    public function indexMethodReturnsVoid(): void
    {
        $method = new \ReflectionMethod(HomeController::class, 'index');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    // =========================================================================
    // getHomeFacade tests
    // =========================================================================

    #[Test]
    public function getHomeFacadeReturnsInjectedFacade(): void
    {
        $this->assertSame($this->homeFacade, $this->controller->getHomeFacade());
    }

    #[Test]
    public function getHomeFacadeReturnType(): void
    {
        $method = new \ReflectionMethod(HomeController::class, 'getHomeFacade');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame(HomeFacade::class, $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    // =========================================================================
    // index() — facade interaction tests
    // =========================================================================

    #[Test]
    public function indexCallsGetDashboardData(): void
    {
        $dashboardData = [
            'language_count' => 0,
            'current_language_id' => null,
            'current_language_text_count' => 0,
            'current_text_id' => null,
            'current_text_info' => null,
            'is_wordpress' => false,
            'is_multi_user' => false,
        ];

        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn($dashboardData);

        $this->languageFacade->expects($this->once())
            ->method('getLanguagesForSelect')
            ->willReturn([]);

        $this->testableController->index([]);

        $this->assertSame($dashboardData, $this->testableController->capturedDashboardData);
    }

    #[Test]
    public function indexCallsGetLanguagesForSelect(): void
    {
        $languages = [
            ['id' => 1, 'name' => 'English'],
            ['id' => 2, 'name' => 'French'],
        ];

        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn([
                'language_count' => 2,
                'current_language_id' => 1,
                'current_language_text_count' => 5,
                'current_text_id' => null,
                'current_text_info' => null,
                'is_wordpress' => false,
                'is_multi_user' => false,
            ]);

        $this->languageFacade->expects($this->once())
            ->method('getLanguagesForSelect')
            ->willReturn($languages);

        $this->testableController->index([]);

        $this->assertSame($languages, $this->testableController->capturedLanguages);
    }

    #[Test]
    public function indexWithNoCurrentTextDoesNotEnterStatsBranch(): void
    {
        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn([
                'language_count' => 1,
                'current_language_id' => 1,
                'current_language_text_count' => 3,
                'current_text_id' => null,
                'current_text_info' => null,
                'is_wordpress' => false,
                'is_multi_user' => false,
            ]);

        $this->languageFacade->method('getLanguagesForSelect')->willReturn([]);

        $this->testableController->index([]);

        $this->assertFalse($this->testableController->textStatsBranchEntered);
        $this->assertNull($this->testableController->capturedLastTextInfo);
    }

    #[Test]
    public function indexWithCurrentTextIdButNullInfoSkipsStatsBranch(): void
    {
        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn([
                'language_count' => 1,
                'current_language_id' => 1,
                'current_language_text_count' => 3,
                'current_text_id' => 42,
                'current_text_info' => null,
                'is_wordpress' => false,
                'is_multi_user' => false,
            ]);

        $this->languageFacade->method('getLanguagesForSelect')->willReturn([]);

        $this->testableController->index([]);

        $this->assertFalse($this->testableController->textStatsBranchEntered);
        $this->assertNull($this->testableController->capturedLastTextInfo);
    }

    #[Test]
    public function indexWithNullTextIdButSetInfoSkipsStatsBranch(): void
    {
        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn([
                'language_count' => 1,
                'current_language_id' => 1,
                'current_language_text_count' => 3,
                'current_text_id' => null,
                'current_text_info' => [
                    'title' => 'Some text',
                    'language_id' => 1,
                    'language_name' => 'English',
                    'annotated' => false,
                ],
                'is_wordpress' => false,
                'is_multi_user' => false,
            ]);

        $this->languageFacade->method('getLanguagesForSelect')->willReturn([]);

        $this->testableController->index([]);

        $this->assertFalse($this->testableController->textStatsBranchEntered);
        $this->assertNull($this->testableController->capturedLastTextInfo);
    }

    #[Test]
    public function indexWithBothTextIdAndInfoEntersStatsBranch(): void
    {
        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn([
                'language_count' => 1,
                'current_language_id' => 1,
                'current_language_text_count' => 3,
                'current_text_id' => 42,
                'current_text_info' => [
                    'title' => 'Test text',
                    'language_id' => 1,
                    'language_name' => 'English',
                    'annotated' => true,
                ],
                'is_wordpress' => false,
                'is_multi_user' => false,
            ]);

        $this->languageFacade->method('getLanguagesForSelect')->willReturn([]);

        $this->testableController->index([]);

        $this->assertTrue($this->testableController->textStatsBranchEntered);
    }

    #[Test]
    public function indexWithEmptyDashboardDataUsesDefaults(): void
    {
        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn([]);

        $this->languageFacade->expects($this->once())
            ->method('getLanguagesForSelect')
            ->willReturn([]);

        $this->testableController->index([]);

        // With empty array, current_text_id and current_text_info are null
        $this->assertFalse($this->testableController->textStatsBranchEntered);
        $this->assertNull($this->testableController->capturedLastTextInfo);
        $this->assertSame([], $this->testableController->capturedDashboardData);
    }

    #[Test]
    public function indexWithMultipleLanguagesCallsFacadeOnce(): void
    {
        $languages = [
            ['id' => 1, 'name' => 'English'],
            ['id' => 2, 'name' => 'French'],
            ['id' => 3, 'name' => 'German'],
        ];

        $this->homeFacade->expects($this->once())
            ->method('getDashboardData')
            ->willReturn([
                'language_count' => 3,
                'current_language_id' => 2,
                'current_language_text_count' => 10,
                'current_text_id' => null,
                'current_text_info' => null,
                'is_wordpress' => false,
                'is_multi_user' => false,
            ]);

        $this->languageFacade->expects($this->once())
            ->method('getLanguagesForSelect')
            ->willReturn($languages);

        $this->testableController->index([]);

        $this->assertCount(3, $this->testableController->capturedLanguages);
    }
}
