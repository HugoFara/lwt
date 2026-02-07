<?php

/**
 * Delete Result View - Shows result after deleting a word
 *
 * Variables expected:
 * - $wid: int - Word ID that was deleted
 * - $textId: int - Text ID
 * - $deleted: bool - Whether deletion was successful
 * - $term: string - The deleted term text
 * - $termLc: string - The deleted term text (lowercase)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Views;

use Lwt\Shared\Infrastructure\Utilities\StringUtils;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;

// Type assertions for variables passed from controller
assert(is_int($wid));
assert(is_int($textId));
assert(is_bool($deleted));
assert(is_string($term));
assert(is_string($termLc));

$hex = StringUtils::toClassName($termLc);
?>
<?php if ($deleted) : ?>
<p>OK, term deleted.</p>
<?php else : ?>
<p>Term not found or already deleted.</p>
<?php endif; ?>

<script type="application/json" data-lwt-delete-result-config>
<?php echo json_encode([
    'wid' => $wid,
    'term' => $term,
    'hex' => $hex,
    'deleted' => $deleted,
    'textId' => $textId,
    'todoContent' => $textId > 0 ? (new TextStatisticsService())->getTodoWordsContent($textId) : ''
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
