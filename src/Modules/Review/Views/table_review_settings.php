<?php

declare(strict_types=1);

/**
 * Table Test Settings View - Checkboxes for column visibility
 *
 * Variables expected:
 * - $settings: array - Settings array with keys: edit, status, term, trans, rom, sentence
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 *
 * @var array{edit: int, status: int, term: int, trans: int, rom: int, sentence: int} $settings
 */

namespace Lwt\Views\Review;

use Lwt\Shared\UI\Helpers\FormHelper;

/** @var array{edit: int, status: int, term: int, trans: int, rom: int, sentence: int} $settings */

?>
<p>
    <input type="checkbox" id="cbEdit" <?php echo FormHelper::getChecked($settings['edit']); ?> />
    <?php echo \htmlspecialchars(__('review.table.col_edit'), ENT_QUOTES, 'UTF-8'); ?>
    <input type="checkbox" id="cbStatus" <?php echo FormHelper::getChecked($settings['status']); ?> />
    <?php echo \htmlspecialchars(__('review.table.col_status'), ENT_QUOTES, 'UTF-8'); ?>
    <input type="checkbox" id="cbTerm" <?php echo FormHelper::getChecked($settings['term']); ?> />
    <?php echo \htmlspecialchars(__('review.table.col_term'), ENT_QUOTES, 'UTF-8'); ?>
    <input type="checkbox" id="cbTrans" <?php echo FormHelper::getChecked($settings['trans']); ?> />
    <?php echo \htmlspecialchars(__('review.table.col_translation'), ENT_QUOTES, 'UTF-8'); ?>
    <input type="checkbox" id="cbRom" <?php echo FormHelper::getChecked($settings['rom']); ?> />
    <?php echo \htmlspecialchars(__('review.table.col_romanization'), ENT_QUOTES, 'UTF-8'); ?>
    <input type="checkbox" id="cbSentence" <?php echo FormHelper::getChecked($settings['sentence']); ?> />
    <?php echo \htmlspecialchars(__('review.table.col_sentence'), ENT_QUOTES, 'UTF-8'); ?>
</p>
