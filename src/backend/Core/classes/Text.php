<?php

/**
 * \file
 * \brief Define the Text class
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-classes-text.html
 * @since   2.9.0
 */

namespace Lwt\Classes;

/**
 * A text represented as an object.
 */
class Text
{
    /**
     * @var int $id Text ID.
     */
    public $id;
    /**
     * @var int $lgid Language id.
     */
    public $lgid;
    /**
     * @var string $title Text title.
     */
    public $title;
    /**
     * @var string $text Associated text.
     */
    public $text;
    /**
     * @var string $annotated Annotated version of the text.
     */
    public $annotated;
    /**
     * @var string $media_uri Media address (local path or URL).
     */
    public $media_uri;
    /**
     * @var string $source Source of text (usually URL).
     */
    public $source;
    /**
     * @var string $position Position in text.
     */
    public $position;
    /**
     * @var float $audio_pos Position of the associated media.
     */
    public $audio_pos;

    public function load_from_db_record($record)
    {
        $this->id = $record['TxID'];
        $this->lgid = $record['TxLgID'];
        $this->title = $record['TxTitle'];
        $this->text = $record['TxText'];
        $this->annotated = $record['TxAnnotatedText'];
        $this->media_uri = $record['TxAudioURI'];
        $this->source = $record['TxSourceURI'];
        $this->position = $record['TxPosition'];
        $this->audio_pos = $record['TxAudioPosition'];
    }
}
