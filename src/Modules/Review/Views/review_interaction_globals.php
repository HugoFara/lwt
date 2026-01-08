<?php declare(strict_types=1);
/**
 * Test Interaction Globals View - JavaScript config for dictionaries
 *
 * Variables expected:
 * - $dict1Uri: string - Dictionary 1 URI
 * - $dict2Uri: string - Dictionary 2 URI
 * - $translateUri: string - Translator URI
 * - $langId: int - Language ID
 * - $langCode: string - Language code
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
<script type="application/json" data-lwt-test-interaction-globals-config>
<?php echo json_encode([
    'langId' => $langId,
    'dict1Uri' => $dict1Uri,
    'dict2Uri' => $dict2Uri,
    'translateUri' => $translateUri,
    'langCode' => $langCode
]); ?>
</script>
