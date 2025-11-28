<?php

/**
 * Multi-Word Update Result View - JavaScript to update UI after multi-word edit
 *
 * Variables expected:
 * - $termJson: string - JSON encoded term data
 * - $oldStatusValue: int - Previous status value
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

namespace Lwt\Views\Word;

?>
<script type="text/javascript">
    function update_mword(mword, oldstatus) {
        const context = window.parent.document;
        let title = '';
        if (window.parent.LWT_DATA.settings.jQuery_tooltip)
            title = make_tooltip(
                mword.text, mword.translation, mword.romanization, mword.status
            );
        $('.word' + mword.woid, context)
        .attr('data_trans', mword.translation)
        .attr('data_rom', mword.romanization)
        .attr('title', title)
        .removeClass('status' + oldstatus)
        .addClass('status' + mword.status)
        .attr('data_status', mword.status);
    }

    update_mword(
        <?php echo $termJson; ?>,
        <?php echo $oldStatusValue; ?>
    );
</script>
