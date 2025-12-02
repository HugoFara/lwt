<?php declare(strict_types=1);
/**
 * Desktop Test Layout View (Bulma + Alpine.js)
 *
 * Minimal container for client-side rendered test interface.
 * All UI is rendered by JavaScript/Alpine.js.
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

?>
<!-- Test application root - all UI rendered by Alpine.js -->
<div id="test-app"></div>

<!-- Audio elements for feedback -->
<audio id="success_sound" preload="auto">
  <source src="<?php \print_file_path("sounds/success.mp3"); ?>" type="audio/mpeg" />
</audio>
<audio id="failure_sound" preload="auto">
  <source src="<?php \print_file_path("sounds/failure.mp3"); ?>" type="audio/mpeg" />
</audio>

<!-- Test configuration -->
<script type="application/json" id="test-config"><?php echo json_encode($config); ?></script>
