<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\StarterVocabController;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Vocabulary\Application\Services\FrequencyImportService;
use Lwt\Modules\Vocabulary\Application\Services\WiktionaryEnrichmentService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class StarterVocabControllerTest extends TestCase
{
    /** @var LanguageFacade&MockObject */
    private LanguageFacade $languageFacade;

    /** @var FrequencyImportService&MockObject */
    private FrequencyImportService $frequencyImportService;

    /** @var WiktionaryEnrichmentService&MockObject */
    private WiktionaryEnrichmentService $enrichmentService;

    private StarterVocabController $controller;

    protected function setUp(): void
    {
        $this->languageFacade = $this->createMock(LanguageFacade::class);
        $this->frequencyImportService = $this->createMock(FrequencyImportService::class);
        $this->enrichmentService = $this->createMock(WiktionaryEnrichmentService::class);
        $this->controller = new StarterVocabController(
            $this->languageFacade,
            $this->frequencyImportService,
            $this->enrichmentService
        );
    }

    // =========================================================================
    // Constructor
    // =========================================================================

    #[Test]
    public function constructorCreatesValidController(): void
    {
        $this->assertInstanceOf(StarterVocabController::class, $this->controller);
    }

    #[Test]
    public function constructorStoresLanguageFacade(): void
    {
        $ref = new \ReflectionProperty(StarterVocabController::class, 'languageFacade');
        $ref->setAccessible(true);
        $this->assertSame($this->languageFacade, $ref->getValue($this->controller));
    }

    #[Test]
    public function constructorStoresFrequencyImportService(): void
    {
        $ref = new \ReflectionProperty(StarterVocabController::class, 'frequencyImportService');
        $ref->setAccessible(true);
        $this->assertSame($this->frequencyImportService, $ref->getValue($this->controller));
    }

    #[Test]
    public function constructorStoresEnrichmentService(): void
    {
        $ref = new \ReflectionProperty(StarterVocabController::class, 'enrichmentService');
        $ref->setAccessible(true);
        $this->assertSame($this->enrichmentService, $ref->getValue($this->controller));
    }

    // =========================================================================
    // import()
    // =========================================================================

    #[Test]
    public function importMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'import'));
    }

    #[Test]
    public function importMethodHasCorrectSignature(): void
    {
        $method = new \ReflectionMethod(StarterVocabController::class, 'import');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    #[Test]
    public function importReturnTypeIsJsonResponse(): void
    {
        $method = new \ReflectionMethod(StarterVocabController::class, 'import');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('Lwt\Shared\Infrastructure\Http\JsonResponse', $returnType->getName());
    }

    // =========================================================================
    // enrich()
    // =========================================================================

    #[Test]
    public function enrichMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'enrich'));
    }

    #[Test]
    public function enrichReturnTypeIsJsonResponse(): void
    {
        $method = new \ReflectionMethod(StarterVocabController::class, 'enrich');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('Lwt\Shared\Infrastructure\Http\JsonResponse', $returnType->getName());
    }

    #[Test]
    public function enrichMethodAcceptsIntId(): void
    {
        $method = new \ReflectionMethod(StarterVocabController::class, 'enrich');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    // =========================================================================
    // show()
    // =========================================================================

    #[Test]
    public function showMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'show'));
    }

    #[Test]
    public function showMethodAcceptsIntId(): void
    {
        $method = new \ReflectionMethod(StarterVocabController::class, 'show');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    // =========================================================================
    // skip()
    // =========================================================================

    #[Test]
    public function skipMethodExists(): void
    {
        $this->assertTrue(method_exists($this->controller, 'skip'));
    }

    // =========================================================================
    // Constants
    // =========================================================================

    #[Test]
    public function allowedCountsContainsExpectedValues(): void
    {
        $ref = new \ReflectionClassConstant(StarterVocabController::class, 'ALLOWED_COUNTS');
        $this->assertSame([500, 1000, 2000, 5000], $ref->getValue());
    }

    #[Test]
    public function allowedModesContainsExpectedValues(): void
    {
        $ref = new \ReflectionClassConstant(StarterVocabController::class, 'ALLOWED_MODES');
        $this->assertSame(['translation', 'definition'], $ref->getValue());
    }
}
