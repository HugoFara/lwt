<?php

/**
 * Letter Pair Profile - the letter-pair fingerprint of a term
 *
 * PHP version 8.2
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <git@hugofara.net>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.5.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services;

/**
 * The letter pairs of a term, in both its raw and its phonetic spelling.
 *
 * Built by {@see SimilarityCalculator::profile()}. Holding both sets together
 * lets a term be profiled once and then compared many times, and lets a
 * comparison subtract the parts of a term that have already been explained.
 *
 * @since 3.5.0
 */
class LetterPairProfile
{
    /**
     * Letter pairs of the term as written.
     *
     * @var list<string>
     */
    private array $chars;

    /**
     * Letter pairs of the phonetically normalized term.
     *
     * @var list<string>
     */
    private array $phonetics;

    /**
     * Constructor.
     *
     * @param list<string> $chars     Letter pairs as written
     * @param list<string> $phonetics Letter pairs after phonetic normalization
     */
    public function __construct(array $chars, array $phonetics)
    {
        $this->chars = $chars;
        $this->phonetics = $phonetics;
    }

    /**
     * Letter pairs of the term as written.
     *
     * @return list<string>
     */
    public function chars(): array
    {
        return $this->chars;
    }

    /**
     * Letter pairs of the phonetically normalized term.
     *
     * @return list<string>
     */
    public function phonetics(): array
    {
        return $this->phonetics;
    }

    /**
     * Whether the profile carries no pairs at all.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->chars === [] && $this->phonetics === [];
    }

    /**
     * The pairs of this profile that the other profile does not account for.
     *
     * Used to shrink a term to the part no suggestion has explained yet.
     *
     * @param self $other Profile to subtract
     *
     * @return self
     */
    public function minus(self $other): self
    {
        return new self(
            array_values(array_diff($this->chars, $other->chars)),
            array_values(array_diff($this->phonetics, $other->phonetics))
        );
    }
}
