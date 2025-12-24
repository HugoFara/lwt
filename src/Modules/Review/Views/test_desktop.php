<?php declare(strict_types=1);
/**
 * Desktop Test Layout View
 *
 * Minimal container for client-side rendered test interface.
 * All UI is rendered by Alpine.js.
 *
 * Variables expected:
 * - $config: array - Test configuration (from TestController)
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Test;

use Lwt\Core\StringUtils;

use Lwt\View\Helper\PageLayoutHelper;

?>
<!-- Main navigation -->
<?php echo PageLayoutHelper::buildNavbar(); ?>

<!-- Test application root - all UI rendered by Alpine.js -->
<div id="test-app"></div>

<!-- Audio elements for feedback -->
<audio id="success_sound" preload="auto">
  <source src="<?php StringUtils::printFilePath("sounds/success.mp3"); ?>" type="audio/mpeg" />
</audio>
<audio id="failure_sound" preload="auto">
  <source src="<?php StringUtils::printFilePath("sounds/failure.mp3"); ?>" type="audio/mpeg" />
</audio>

<!-- Test configuration -->
<script type="application/json" id="test-config"><?php echo json_encode($config); ?></script>
