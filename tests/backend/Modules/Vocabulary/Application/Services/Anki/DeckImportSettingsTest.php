<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services\Anki;

use InvalidArgumentException;
use Lwt\Modules\Vocabulary\Application\Services\Anki\DeckImportSettings;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ForeignNote;
use PHPUnit\Framework\TestCase;

/**
 * The Anki-maturity to LWT-status mapping.
 *
 * This is the part that makes importing a deck worthwhile rather than
 * reclassifying by hand, so it is worth pinning down precisely.
 */
final class DeckImportSettingsTest extends TestCase
{
    private function settings(bool $derive = true, int $fixed = 1): DeckImportSettings
    {
        return new DeckImportSettings(
            notetypeId: 1,
            termField: 'Front',
            translationField: 'Back',
            languageId: 3,
            deriveStatus: $derive,
            fixedStatus: $fixed,
        );
    }

    private function note(int $interval, bool $suspended = false, bool $isNew = false): ForeignNote
    {
        return new ForeignNote(
            fields: ['Front' => 'w', 'Back' => 't'],
            tags: [],
            interval: $interval,
            suspended: $suspended,
            isNew: $isNew,
        );
    }

    public function testMatureCardsBecomeWellKnown(): void
    {
        $settings = $this->settings();

        $this->assertSame(99, $settings->statusFor($this->note(21)), 'exactly at the threshold');
        $this->assertSame(99, $settings->statusFor($this->note(365)));
    }

    public function testYoungCardsScaleWithInterval(): void
    {
        $settings = $this->settings();

        $this->assertSame(2, $settings->statusFor($this->note(1)));
        $this->assertSame(2, $settings->statusFor($this->note(6)));
        $this->assertSame(3, $settings->statusFor($this->note(7)));
        $this->assertSame(3, $settings->statusFor($this->note(13)));
        $this->assertSame(4, $settings->statusFor($this->note(14)));
        $this->assertSame(4, $settings->statusFor($this->note(20)));
    }

    public function testUnstudiedCardsStartAtTheLowestStatus(): void
    {
        $settings = $this->settings();

        $this->assertSame(1, $settings->statusFor($this->note(0, isNew: true)));
        $this->assertSame(1, $settings->statusFor($this->note(0)));
    }

    public function testSuspendedCardsBecomeIgnored(): void
    {
        $settings = $this->settings();

        // Suspension wins over a long interval: the user parked it deliberately.
        $this->assertSame(98, $settings->statusFor($this->note(200, suspended: true)));
    }

    public function testFixedStatusOverridesTheDerivation(): void
    {
        $settings = $this->settings(derive: false, fixed: 99);

        $this->assertSame(99, $settings->statusFor($this->note(0, isNew: true)));
        $this->assertSame(99, $settings->statusFor($this->note(200, suspended: true)));
    }

    public function testDerivedStatusIsAlwaysAValidTermStatus(): void
    {
        $settings = $this->settings();
        $valid = [1, 2, 3, 4, 5, 98, 99];

        foreach ([0, 1, 5, 7, 13, 14, 20, 21, 100, 10000] as $interval) {
            foreach ([true, false] as $suspended) {
                foreach ([true, false] as $isNew) {
                    $this->assertContains(
                        $settings->statusFor($this->note($interval, $suspended, $isNew)),
                        $valid,
                        "interval={$interval}"
                    );
                }
            }
        }
    }

    public function testRejectsMissingTermField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DeckImportSettings(1, '', 'Back', 3);
    }

    public function testRejectsMissingLanguage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DeckImportSettings(1, 'Front', 'Back', 0);
    }

    public function testRejectsInvalidFixedStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DeckImportSettings(1, 'Front', 'Back', 3, false, 42);
    }
}
