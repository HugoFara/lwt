<?php

/**
 * \file
 * \brief From to import a file from YouTube.
 *
 * You need a personal YouTube API key.
 *
 * PHP version 8.1
 *
 * @package Lwt
 */

namespace Lwt\Text_From_Youtube;

require_once __DIR__ . '/settings.php';

/**
 * @var string|null YT_API_KEY Text from YouTube API key
 *
 * You can change the key here.
 */
define('YT_API_KEY', null);

function do_form_fragment(): void
{
    ?>
<tr>
  <td class="td1 right">YouTube Video Id:</td>
  <td class="td1">
    <input type='text' id='ytVideoId'>
    <input type='button' value='Fetch Text from Youtube' onclick='getYtTextData()'>
    <input type='text' id='ytApiKey' value='<?php echo YT_API_KEY ?>' style='visibility:collapse' aria='hidden'>
    <p id='ytDataStatus'></p>
  </td>
</tr>
    <?php
}

function do_js(): void
{
    ?>
<script>
function setYtDataStatus(msg) {
  $('#ytDataStatus').text(msg);
}

const fetching_success = function (data) {
  if (data.items.length == 0) {
      setYtDataStatus('No videos found.');
  } else {
    setYtDataStatus('Success!');
    const snippet = data.items[0].snippet;
    $('[name=TxTitle]').attr('value', snippet.title);
    $('[name=TxText]').attr('value', snippet.description);
    $('[name=TxSourceURI]').attr('value', `https://youtube.com/watch?v=${ytVideoId}`);
  }
}

function getYtTextData() {
  setYtDataStatus('Fetching YouTube data...');
  const ytVideoId = document.getElementById('ytVideoId').value;
  const API_KEY = document.getElementById('ytApiKey').value;
  const url = `https://www.googleapis.com/youtube/v3/videos?part=snippet&id=${ytVideoId}&key=${API_KEY}`;
  $.get(url, fetching_success);
}
</script>
    <?php
}
