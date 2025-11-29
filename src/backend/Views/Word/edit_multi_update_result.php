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
    (function() {
        const mword = <?php echo $termJson; ?>;
        updateMultiWordInDOM(
            mword.woid,
            mword.text,
            mword.translation,
            mword.romanization,
            mword.status,
            <?php echo $oldStatusValue; ?>
        );
    })();
</script>
