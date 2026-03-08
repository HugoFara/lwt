<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\WiktionaryEnrichmentService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WiktionaryEnrichmentServiceTest extends TestCase
{
    private WiktionaryEnrichmentService $service;

    protected function setUp(): void
    {
        $this->service = new WiktionaryEnrichmentService();
    }

    // =========================================================================
    // buildKaikkiUrl
    // =========================================================================

    #[Test]
    public function buildKaikkiUrlForLatinWord(): void
    {
        $url = $this->service->buildKaikkiUrl('casa', 'Spanish');
        $this->assertSame(
            'https://kaikki.org/dictionary/Spanish/meaning/c/ca/casa.jsonl',
            $url
        );
    }

    #[Test]
    public function buildKaikkiUrlForSingleCharWord(): void
    {
        $url = $this->service->buildKaikkiUrl('a', 'Spanish');
        $this->assertSame(
            'https://kaikki.org/dictionary/Spanish/meaning/a/a/a.jsonl',
            $url
        );
    }

    #[Test]
    public function buildKaikkiUrlForCyrillicWord(): void
    {
        $url = $this->service->buildKaikkiUrl('дом', 'Russian');
        $this->assertStringContainsString('/dictionary/Russian/meaning/', $url);
        $this->assertStringEndsWith('.jsonl', $url);
    }

    #[Test]
    public function buildKaikkiUrlForWordWithSpaces(): void
    {
        $url = $this->service->buildKaikkiUrl('ice cream', 'English');
        $this->assertStringContainsString('/dictionary/English/meaning/', $url);
        $this->assertStringContainsString('ice%20cream', $url);
    }

    #[Test]
    public function buildKaikkiUrlForAccentedWord(): void
    {
        $url = $this->service->buildKaikkiUrl('café', 'French');
        $this->assertStringContainsString('/dictionary/French/meaning/', $url);
    }

    #[Test]
    public function buildKaikkiUrlEncodesLanguageName(): void
    {
        $url = $this->service->buildKaikkiUrl('hus', 'Norwegian Bokmål');
        $this->assertStringContainsString('Norwegian%20Bokm%C3%A5l', $url);
    }

    // =========================================================================
    // parseKaikkiResponse
    // =========================================================================

    #[Test]
    public function parseKaikkiResponseExtractsFirstGloss(): void
    {
        $jsonl = '{"word":"casa","pos":"noun","lang":"Spanish","senses":[{"glosses":["house"],"tags":["feminine"]}]}';

        $result = $this->service->parseKaikkiResponse($jsonl);
        $this->assertSame('house', $result);
    }

    #[Test]
    public function parseKaikkiResponsePrefersNonFormOf(): void
    {
        $verbLine = '{"word":"casa","pos":"verb","lang":"Spanish","senses":[{"glosses":["inflection of casar:"],"form_of":[{"word":"casar"}],"tags":["form-of"]}]}';
        $nounLine = '{"word":"casa","pos":"noun","lang":"Spanish","senses":[{"glosses":["house"]}]}';
        $jsonl = $verbLine . "\n" . $nounLine;

        $result = $this->service->parseKaikkiResponse($jsonl);
        $this->assertSame('house', $result);
    }

    #[Test]
    public function parseKaikkiResponseFallsBackToFormOf(): void
    {
        $jsonl = '{"word":"casas","pos":"noun","lang":"Spanish","senses":[{"glosses":["plural of casa"],"form_of":[{"word":"casa"}],"tags":["form-of"]}]}';

        $result = $this->service->parseKaikkiResponse($jsonl);
        $this->assertSame('plural of casa', $result);
    }

    #[Test]
    public function parseKaikkiResponseHandlesMultiplePosEntries(): void
    {
        $line1 = '{"word":"test","pos":"noun","lang":"English","senses":[{"glosses":["a procedure"]}]}';
        $line2 = '{"word":"test","pos":"verb","lang":"English","senses":[{"glosses":["to try"]}]}';
        $jsonl = $line1 . "\n" . $line2;

        $result = $this->service->parseKaikkiResponse($jsonl);
        $this->assertSame('a procedure', $result);
    }

    #[Test]
    public function parseKaikkiResponseReturnsNullForEmptyInput(): void
    {
        $this->assertNull($this->service->parseKaikkiResponse(''));
    }

    #[Test]
    public function parseKaikkiResponseReturnsNullForInvalidJson(): void
    {
        $this->assertNull($this->service->parseKaikkiResponse('not json at all'));
    }

    #[Test]
    public function parseKaikkiResponseReturnsNullForNoSenses(): void
    {
        $jsonl = '{"word":"test","pos":"noun","lang":"English"}';
        $this->assertNull($this->service->parseKaikkiResponse($jsonl));
    }

    #[Test]
    public function parseKaikkiResponseReturnsNullForEmptySenses(): void
    {
        $jsonl = '{"word":"test","pos":"noun","lang":"English","senses":[]}';
        $this->assertNull($this->service->parseKaikkiResponse($jsonl));
    }

    #[Test]
    public function parseKaikkiResponseReturnsNullForEmptyGlosses(): void
    {
        $jsonl = '{"word":"test","pos":"noun","lang":"English","senses":[{"glosses":[]}]}';
        $this->assertNull($this->service->parseKaikkiResponse($jsonl));
    }

    #[Test]
    public function parseKaikkiResponseSkipsBlankLines(): void
    {
        $jsonl = "\n\n" . '{"word":"casa","pos":"noun","lang":"Spanish","senses":[{"glosses":["house"]}]}' . "\n\n";

        $result = $this->service->parseKaikkiResponse($jsonl);
        $this->assertSame('house', $result);
    }

    #[Test]
    public function parseKaikkiResponseHandlesMultipleGlosses(): void
    {
        $jsonl = '{"word":"banco","pos":"noun","lang":"Spanish","senses":[{"glosses":["bank","bench"]}]}';

        $result = $this->service->parseKaikkiResponse($jsonl);
        $this->assertSame('bank', $result);
    }

    // =========================================================================
    // parseWikitext
    // =========================================================================

    #[Test]
    public function parseWikitextExtractsSimpleDefinition(): void
    {
        $wikitext = "==Noun==\n# [[house]]\n# [[home]]";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('house', $result);
    }

    #[Test]
    public function parseWikitextStripsLinkMarkup(): void
    {
        $wikitext = "# [[large]] [[building]]";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('large building', $result);
    }

    #[Test]
    public function parseWikitextStripsPipedLinks(): void
    {
        $wikitext = "# A [[dwelling|place of dwelling]]";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('A place of dwelling', $result);
    }

    #[Test]
    public function parseWikitextStripsLabelTemplates(): void
    {
        $wikitext = "# {{lb|es|architecture}} A [[building]]";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('A building', $result);
    }

    #[Test]
    public function parseWikitextStripsLinkTemplates(): void
    {
        $wikitext = "# A type of {{l|en|building}}";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('A type of building', $result);
    }

    #[Test]
    public function parseWikitextStripsGlossTemplates(): void
    {
        $wikitext = "# {{gloss|a type of building}}";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('a type of building', $result);
    }

    #[Test]
    public function parseWikitextStripsBoldAndItalic(): void
    {
        $wikitext = "# '''strong''' and ''emphasis''";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('strong and emphasis', $result);
    }

    #[Test]
    public function parseWikitextReturnsNullForEmpty(): void
    {
        $this->assertNull($this->service->parseWikitext(''));
    }

    #[Test]
    public function parseWikitextReturnsNullForNoDefinitions(): void
    {
        $wikitext = "==Noun==\n{{es-noun|f}}\n\n===Synonyms===\n* [[vivienda]]";
        $this->assertNull($this->service->parseWikitext($wikitext));
    }

    #[Test]
    public function parseWikitextSkipsInflectionForms(): void
    {
        $wikitext = "# {{inflection of|es|casar||3|s|pres|ind}}\n# [[house]]";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('house', $result);
    }

    #[Test]
    public function parseWikitextSkipsSubDefinitions(): void
    {
        // "## " lines are sub-definitions, "# " lines are main
        $wikitext = "## A sub-meaning\n# The main meaning";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('The main meaning', $result);
    }

    // =========================================================================
    // fetchKaikkiTranslation (error case - no network)
    // =========================================================================

    #[Test]
    public function fetchKaikkiTranslationReturnsNullOnNetworkFailure(): void
    {
        // Use a non-routable IP to guarantee timeout/failure
        $result = $this->service->fetchKaikkiTranslation(
            'nonexistentword999xyz',
            'NonexistentLanguage999'
        );
        $this->assertNull($result);
    }

    // =========================================================================
    // fetchWiktionaryDefinition structure
    // =========================================================================

    #[Test]
    public function fetchWiktionaryDefinitionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->service, 'fetchWiktionaryDefinition'));
    }

    #[Test]
    public function fetchWiktionaryDefinitionAcceptsThreeParams(): void
    {
        $method = new \ReflectionMethod(WiktionaryEnrichmentService::class, 'fetchWiktionaryDefinition');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('word', $params[0]->getName());
        $this->assertSame('wiktCode', $params[1]->getName());
        $this->assertSame('kaikkiLangName', $params[2]->getName());
    }

    // =========================================================================
    // enrichBatchTranslation / enrichBatchDefinition structure
    // =========================================================================

    #[Test]
    public function enrichBatchTranslationReturnsCorrectKeysForUnsupported(): void
    {
        $result = $this->service->enrichBatchTranslation(999, 'Klingon');
        $this->assertArrayHasKey('enriched', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertSame(0, $result['enriched']);
        $this->assertNotEmpty($result['warning']);
    }

    #[Test]
    public function enrichBatchDefinitionReturnsCorrectKeysForUnsupported(): void
    {
        $result = $this->service->enrichBatchDefinition(999, 'Klingon');
        $this->assertArrayHasKey('enriched', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertSame(0, $result['enriched']);
        $this->assertNotEmpty($result['warning']);
    }

    // =========================================================================
    // cleanWikitext (via parseWikitext integration)
    // =========================================================================

    #[Test]
    public function parseWikitextHandlesComplexTemplate(): void
    {
        $wikitext = "# {{lb|en|colloquial}} A {{l|en|person}}'s [[home]]";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame("A person's home", $result);
    }

    #[Test]
    public function parseWikitextHandlesRemainingTemplates(): void
    {
        $wikitext = "# {{something|param1|param2}} text after";
        $result = $this->service->parseWikitext($wikitext);
        // The first param should be kept, rest stripped
        $this->assertStringContainsString('text after', $result ?? '');
    }

    #[Test]
    public function parseWikitextHandlesMentionTemplate(): void
    {
        $wikitext = "# Derived from {{m|la|casa}}";
        $result = $this->service->parseWikitext($wikitext);
        $this->assertSame('Derived from casa', $result);
    }
}
