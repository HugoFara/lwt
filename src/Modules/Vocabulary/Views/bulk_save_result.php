<?php

/**
 * Bulk Save Result View - confirmation shown after bulk-translated terms are saved.
 *
 * Variables expected:
 * - $newWords: array - The terms that were created
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

// Type assertions for variables passed from controller
assert(is_array($newWords));

?>
<p><?= __('vocabulary.result.bulk_saved', ['count' => count($newWords)]) ?></p>
