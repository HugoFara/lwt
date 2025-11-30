<?php

/**
 * \file
 * \brief Form to import a file from YouTube.
 *
 * You need a personal YouTube API key.
 *
 * JavaScript functionality moved to src/frontend/js/texts/youtube_import.ts
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 */

namespace Lwt\Text_From_Youtube;

/**
 * @var string|null YT_API_KEY Text from YouTube API key
 *
 * You can change the key here.
 */
define('YT_API_KEY', null);

/**
 * Output the YouTube import form fragment.
 *
 * @return void
 */
function do_form_fragment(): void
{
    ?>
<tr>
  <td class="td1 right">YouTube Video Id:</td>
  <td class="td1">
    <input type="text" id="ytVideoId" />
    <input type="button" value="Fetch Text from Youtube" data-action="fetch-youtube" />
    <input type="hidden" id="ytApiKey" value="<?php echo YT_API_KEY ?>" />
    <p id="ytDataStatus"></p>
  </td>
</tr>
    <?php
}

/**
 * Output the YouTube import JavaScript.
 *
 * @deprecated 3.0.0 JavaScript moved to youtube_import.ts, this function is now a no-op.
 *
 * @return void
 */
function do_js(): void
{
    // JavaScript functionality moved to src/frontend/js/texts/youtube_import.ts
    // This function is kept for backwards compatibility but does nothing.
}
