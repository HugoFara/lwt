<?php

/**
 * \file
 * \brief Define the Term class
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-classes-term.html
 * @since   2.7.0
 */

namespace Lwt\Classes;

/**
 * A term (word or mutli-word) represented as an object.
 */
class Term
{
    /**
     * @var int $id Term ID.
     */
    public $id;
    /**
     * @var int $lgid Language ID.
     */
    public $lgid;
    /**
     * @var string $text Associated text.
     */
    public $text;
    /**
     * @var string $textlc Associated text in lower case.
     */
    public $textlc;
    /**
     * @var int $status Term status.
     */
    public $status;
    /**
     * @var string $translation Term translation.
     */
    public $translation;
    /**
     * @var string $sentence Sentence containing the term.
     */
    public $sentence;
    /**
     * @var string $roman Romanization.
     */
    public $roman;
    /**
     * @var int $wordcount Number of words in the term.
     */
    public $wordcount;
    /**
     * @var int $statuschanged Last status change date.
     */
    public $statuschanged;
}
