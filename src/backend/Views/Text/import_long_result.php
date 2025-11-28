<?php

/**
 * Long Text Import Result View
 *
 * Variables expected:
 * - $message: string - Result message to display
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

?>
<?php echo error_message_with_hide($message, false); ?>
<p>&nbsp;<br /><input type="button" value="Show Texts" onclick="location.href='/texts';" /></p>
