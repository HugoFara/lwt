<?php

/**
 * Table Test Header Row View - Column headers for test table
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Review;

?>
<tr>
    <th class=""><?php echo \htmlspecialchars(__('review.table.col_ed_short'), ENT_QUOTES, 'UTF-8'); ?></th>
    <th class="clickable">
        <?php echo \htmlspecialchars(__('review.table.col_status'), ENT_QUOTES, 'UTF-8'); ?>
    </th>
    <th class="clickable">
        <?php echo \htmlspecialchars(__('review.table.col_term'), ENT_QUOTES, 'UTF-8'); ?>
    </th>
    <th class="clickable">
        <?php echo \htmlspecialchars(__('review.table.col_translation'), ENT_QUOTES, 'UTF-8'); ?>
    </th>
    <th class="clickable">
        <?php echo \htmlspecialchars(__('review.table.col_romanization'), ENT_QUOTES, 'UTF-8'); ?>
    </th>
    <th class="clickable">
        <?php echo \htmlspecialchars(__('review.table.col_sentence'), ENT_QUOTES, 'UTF-8'); ?>
    </th>
</tr>
