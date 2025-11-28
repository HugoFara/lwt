<?php

/**
 * Mobile Table Set Selection View - Select database table prefix
 *
 * Variables expected:
 * - $currentPrefix: Current table prefix
 * - $prefixes: Array of available prefixes
 * - $isFixed: Whether the prefix is fixed
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

/** @var string $currentPrefix */
/** @var array $prefixes */
/** @var bool $isFixed */

?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1">
            <form name="f1" class="inline" action="/mobile/start" method="post">
            <p>
                Select:
                <select name="prefix" <?php if ($isFixed): ?>disabled title="Database prefix is fixed and cannot be changed!"<?php endif; ?>>
                    <option value="" <?php echo ($currentPrefix === '' ? 'selected="selected"' : ''); ?>>
                        Default Table Set
                    </option>
                    <?php foreach ($prefixes as $prefix): ?>
                    <option value="<?php echo tohtml($prefix); ?>" <?php echo (substr($currentPrefix, 0, -1) === $prefix ? 'selected="selected"' : ''); ?>>
                        <?php echo tohtml($prefix); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="center">
                <input type="submit" name="op" value="Start LWT" />
            </p>
            </form>
        </th>
    </tr>
</table>
