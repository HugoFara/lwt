<?php declare(strict_types=1);
/**
 * Test Term Area View - Container for AJAX-based word tests
 *
 * Variables expected:
 * - $langSettings: array - Language settings with rtl, removeSpaces, textSize
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

namespace Lwt\Views\Review;

?>
<div id="body">
    <p id="term-test"
       dir="<?php echo $langSettings['rtl'] ? 'rtl' : 'ltr'; ?>"
       style="<?php echo $langSettings['removeSpaces'] ? 'word-break:break-all;' : ''; ?>
              font-size: <?php echo $langSettings['textSize']; ?>%;
              line-height: 1.4;
              text-align: center;
              margin-bottom: 300px;">
    </p>
