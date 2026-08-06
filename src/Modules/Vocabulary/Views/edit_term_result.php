<?php

/**
 * Edit Term Result View - confirmation shown after a term is updated during review.
 *
 * Variables expected:
 * - $message: string - Result message
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

namespace Lwt\Views\Word;

// Type assertions for variables passed from controller
assert(is_string($message));

?>
<p><?= __('vocabulary.result.ok_prefix') ?> <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
