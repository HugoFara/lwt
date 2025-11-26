<?php

/**
 * \file
 * \brief Define the Language class
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-classes-language.html
 * @since   2.7.0
 */

namespace Lwt\Classes;

/**
 * A language represented as an object.
 *
 * This structure is experimental and subject to change.
 *
 * @since 2.10.0-fork Get new ttsvoiceapi, showromanization properties
 */
class Language
{
    /**
     * @var int $id Language ID.
     */
    public $id;
    /**
     * @var string $name Language name.
     */
    public $name;
    /**
     * @var string $dict1uri URI for the first dictionary.
     */
    public $dict1uri;
    /**
     * @var string $dict2uri URI for the second dictionary.
     */
    public $dict2uri;
    /**
     * @var string $translator Translator URI.
     */
    public $translator;
    /**
     * @var string $exporttemplate Export template.
     */
    public $exporttemplate;
    /**
     * @var int $textsize Text size.
     */
    public $textsize;
    /**
     * @var string $charactersubst Character to substitue.
     */
    public $charactersubst;
    /**
     * @var string $regexpsplitsent Characters that should split a sentence in part.
     */
    public $regexpsplitsent;
    /**
     * @var string $exceptionsplitsent Characters taht should not split sentence.
     */
    public $exceptionsplitsent;

    /**
     * @var string $regexpwordchar Word characters.
     */
    public $regexpwordchar;

    /**
     * @var bool $removespaces If spaces should be removed.
     */
    public $removespaces;

    /**
     * @var bool $spliteachchar If each character should be separated.
     */
    public $spliteachchar;

    /**
     * @var bool $rightoleft If the language is right-to-left.
     */
    public $rightoleft;

    /**
     * @var string $ttsVoiceAPI An external API for voice
     */
    public $ttsvoiceapi;

    /**
     * @var bool $showromanization Setting to show or hide romanization
     */
    public $showromanization;

    /**
     * Export word data as a JSON dictionnary.
     *
     * @return string|false JSON disctionnary.
     */
    public function exportJsDict(): string|false
    {
        return json_encode(
            array(
                "lgid"               => $this->id,
                "dict1uri"           => $this->dict1uri,
                "dict2uri"           => $this->dict2uri,
                "translator"         => $this->translator,
                "exporttemplate"     => $this->exporttemplate,
                "textsize"           => $this->textsize,
                "charactersubst"     => $this->charactersubst,
                "regexpsplitsent"    => $this->regexpsplitsent,
                "exceptionsplitsent" => $this->exceptionsplitsent,
                "regexpwordchar"     => $this->regexpwordchar,
                "removespaces"       => $this->removespaces,
                "spliteachchar"      => $this->spliteachchar,
                "rightoleft"         => $this->rightoleft,
                "ttsvoiceapi"        => $this->ttsvoiceapi,
                "showromanization"   => $this->showromanization
            )
        );
    }
}
