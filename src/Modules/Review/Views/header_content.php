<?php declare(strict_types=1);
/**
 * Review Header Content View - Review buttons and word count
 *
 * Variables expected:
 * - $title: string - Page title
 * - $property: string - URL property string
 * - $totalDue: int - Words due today
 * - $totalCount: int - Total words
 * - $languageName: string - L2 language name
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

?>
<h1>REVIEW - <?php echo \htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
<div class="test-word-count">
    Word<?php echo $totalCount > 1 ? 's' : ''; ?> due today:
    <?php echo $totalCount; ?>,
    <span class="todosty" id="not-tested-header"><?php echo $totalDue; ?></span> remaining.
</div>
<div class="flex-spaced">
    <div>
        <input type="button" value="..[<?php echo \htmlspecialchars($languageName, ENT_QUOTES, 'UTF-8'); ?>].."
            data-action="start-word-test" data-test-type="1"
            data-property="<?php echo \htmlspecialchars($property, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="button" value="..[L1].."
            data-action="start-word-test" data-test-type="2"
            data-property="<?php echo \htmlspecialchars($property, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="button" value="..[-].."
            data-action="start-word-test" data-test-type="3"
            data-property="<?php echo \htmlspecialchars($property, ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div>
        <input type="button" value="[<?php echo \htmlspecialchars($languageName, ENT_QUOTES, 'UTF-8'); ?>]"
            data-action="start-word-test" data-test-type="4"
            data-property="<?php echo \htmlspecialchars($property, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="button" value="[L1]"
            data-action="start-word-test" data-test-type="5"
            data-property="<?php echo \htmlspecialchars($property, ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div>
        <input type="button" value="Table"
            data-action="start-test-table"
            data-property="<?php echo \htmlspecialchars($property, ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div>
        <input type="checkbox" id="utterance-allowed" />
        <label for="utterance-allowed">Read words aloud</label>
    </div>
</div>
