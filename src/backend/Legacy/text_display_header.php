<?php

/**
 * \file
 * \brief Display an improved annotated text (top frame)
 *
 * Call: display_impr_text_header.php?text=[textid]
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/display-impr-text-header.html
 * @since    1.5.0
 */

require_once 'Core/session_utility.php';

/**
 * Return the useful data for the header part of a printed text.
 *
 * @param int $textid Text ID
 *
 * @return array{0: string, 1: string, 2: string} Text title,
 * text audio and source URI
 *
 * @global string $tbpref Database table prefix.
 */
function do_diplay_impr_text_header_data($textid)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();

    $sql =
    'SELECT TxLgID, TxTitle, TxAudioURI, TxSourceURI
    FROM ' . $tbpref . 'texts
    WHERE TxID = ' . $textid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);


    if (isset($record['TxAudioURI'])) {
        $audio = trim((string) $record['TxAudioURI']);
    } else {
        $audio = '';
    }

    $title = $record['TxTitle'];
    $sourceURI = $record['TxSourceURI'];
    mysqli_free_result($res);

    saveSetting('currenttext', $textid);
    return array($title, $audio, $sourceURI);
}

/**
 * Echo JavaScript area containing behaviors to show/hide
 * translations and annotations.
 *
 * @return void
 */
function do_diplay_impr_text_header_js()
{

    ?>

<script type="text/javascript">
    //<![CDATA[

    /** Hide traslations. */
    function do_hide_t() {
        $('#showt').show();
        $('#hidet').hide();
        $('.anntermruby')
        .css('color','#E5E4E2').css('background-color', '#E5E4E2');
    }

    /** Show translations. */
    function do_show_t() {
        $('#showt').hide();
        $('#hidet').show();
        $('.anntermruby')
        .css('color','inherit').css('background-color', '');
    }

    /** Hide annotations. */
    function do_hide_a() {
        $('#show').show();
        $('#hide').hide();
        $('.anntransruby2')
        .css('color','#C8DCF0').css('background-color', '#C8DCF0');
    }

    /** Show annotations. */
    function do_show_a() {
        $('#show').hide();
        $('#hide').show();
        $('.anntransruby2')
        .css('color','').css('background-color', '');
    }
    //]]>
</script>

    <?php
}

/**
 * Make the header content to display a printed text.
 *
 * @param string $title     Text title
 * @param int    $textid    Text ID
 * @param string $audio     Audio URI
 * @param string $sourceURI Text source link
 *
 * @return void
 */
function do_diplay_impr_text_header_content($title, $textid, $audio, $sourceURI)
{
    $text_links = getPreviousAndNextTextLinks(
        $textid,
        'display_impr_text.php?text=',
        true,
        ' &nbsp; &nbsp; '
    );
    ?>
    <h1><?php echo tohtml($title); ?></h1>
<div class="flex-spaced">
    <div>
        <img id="hidet" class="click" src="/assets/icons/light-bulb-T.png"
        title="Toggle Text Display (Now ON)" alt="Toggle Text Display (Now ON)" onclick="do_hide_t();" />
        <img id="showt" style="display:none;" class="click" src="/assets/icons/light-bulb-off-T.png"
        title="Toggle Text Display (Now OFF)" alt="Toggle Text Display (Now OFF)" onclick="do_show_t();" />
        <img id="hide" class="click" src="/assets/icons/light-bulb-A.png"
        title="Toggle Annotation Display (Now ON)" alt="Toggle Annotation Display (Now ON)" onclick="do_hide_a();" />
        <img id="show" style="display:none;" class="click" src="/assets/icons/light-bulb-off-A.png"
        title="Toggle Annotation Display (Now OFF)" alt="Toggle Annotation Display (Now OFF)" onclick="do_show_a();" />
    </div>
    <div>
        <?php
        if (isset($sourceURI)) {
            echo ' <a href="' . $sourceURI . '" target="_blank">
                <img src="' . get_file_path('assets/icons/chain.png') . '" title="Text Source" alt="Text Source" />
            </a>';
        }
        echo $text_links;
        ?>
    </div>
    <div>
        <img class="click" src="/assets/icons/cross.png" title="Close Window" alt="Close Window" onclick="top.close();" />
    </div>
</div>
    <?php
    makeMediaPlayer($audio);
}

/**
 * Main function to generate a complete header for a specific text.
 *
 * @param int $textid Text ID.
 *
 * @return void
 */
function do_diplay_impr_text_header_main($textid)
{
    do_diplay_impr_text_header_js();
    list($title, $audio, $sourceURI) = do_diplay_impr_text_header_data($textid);
    do_diplay_impr_text_header_content($title, $textid, $audio, $sourceURI);
}

?>
