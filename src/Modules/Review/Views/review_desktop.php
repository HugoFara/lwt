<?php

/**
 * Desktop Review Layout View
 *
 * Minimal container for client-side rendered review interface.
 * All UI is rendered by Alpine.js.
 *
 * Variables expected:
 * - $config: array - Review configuration (from ReviewController)
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Review;

use Lwt\Core\StringUtils;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

?>
<!-- Main navigation -->
<?php echo PageLayoutHelper::buildNavbar(); ?>

<!-- Review application root - all UI rendered by Alpine.js -->
<div id="review-app"></div>

<!-- Audio elements for feedback -->
<audio id="success_sound" preload="auto">
  <source src="<?php StringUtils::printFilePath("sounds/success.mp3"); ?>" type="audio/mpeg" />
</audio>
<audio id="failure_sound" preload="auto">
  <source src="<?php StringUtils::printFilePath("sounds/failure.mp3"); ?>" type="audio/mpeg" />
</audio>

<!-- Review configuration -->
<script type="application/json" id="review-config"><?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
