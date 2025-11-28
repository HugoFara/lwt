<?php

/**
 * Mobile Language Menu View - Language selection submenu
 *
 * Variables expected:
 * - $action: Action code (1)
 * - $langId: Language ID
 * - $langName: Language name
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Mobile;

/** @var int $action */
/** @var int $langId */
/** @var string $langName */

?>
<ul id="<?php echo $action . '-' . $langId; ?>" title="<?php echo tohtml($langName); ?>">
    <li class="group"><?php echo tohtml($langName); ?> Texts</li>
    <li><a href="/mobile?action=2&amp;lang=<?php echo $langId; ?>">All <?php echo tohtml($langName); ?> Texts</a></li>
    <li><a href="/mobile#notyetimpl">Text Tags</a></li>
    <li class="group"><?php echo tohtml($langName); ?> Terms</li>
    <li><a href="/mobile#notyetimpl">All <?php echo tohtml($langName); ?> Terms</a></li>
    <li><a href="/mobile#notyetimpl">Term Tags</a></li>
</ul>
