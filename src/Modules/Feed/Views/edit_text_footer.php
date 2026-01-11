<?php

/**
 * Feed Edit Text Form Footer View
 *
 * Renders the submit button and JavaScript for the edit text form.
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

declare(strict_types=1);

namespace Lwt\Views\Feed;

?>
   <input id="markaction" type="submit" value="Save" />
   <input type="button" value="Cancel" data-action="navigate" data-url="/feeds" />
   <input type="hidden" name="checked_feeds_save" value="1" />
   </form>
