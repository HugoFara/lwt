<?php declare(strict_types=1);
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
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Test;

use Lwt\Shared\UI\Helpers\FormHelper;

// Type assertions for view variables
/** @var array{edit: bool, status: bool, term: bool, trans: bool, rom: bool, sentence: bool} $settings */
$settings = $settings ?? ['edit' => false, 'status' => false, 'term' => false, 'trans' => false, 'rom' => false, 'sentence' => false];

?>
<p>
    <input type="checkbox" id="cbEdit" <?php echo FormHelper::getChecked($settings['edit']); ?> />
    Edit
    <input type="checkbox" id="cbStatus" <?php echo FormHelper::getChecked($settings['status']); ?> />
    Status
    <input type="checkbox" id="cbTerm" <?php echo FormHelper::getChecked($settings['term']); ?> />
    Term
    <input type="checkbox" id="cbTrans" <?php echo FormHelper::getChecked($settings['trans']); ?> />
    Translation
    <input type="checkbox" id="cbRom" <?php echo FormHelper::getChecked($settings['rom']); ?> />
    Romanization
    <input type="checkbox" id="cbSentence" <?php echo FormHelper::getChecked($settings['sentence']); ?> />
    Sentence
</p>
