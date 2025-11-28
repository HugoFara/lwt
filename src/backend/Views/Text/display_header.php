<?php

/**
 * Text Display Header View
 *
 * Variables expected:
 * - $title: string - Text title
 * - $textId: int - Text ID
 * - $audio: string - Audio URI
 * - $sourceUri: string|null - Source URI
 * - $textLinks: string - Previous/next text navigation links
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedGlobalVariable Variables are injected by including file
 */

// Variables injected from text_display_header.php:
// $title, $audio, $sourceUri, $textLinks

?>
<script type="text/javascript">
    //<![CDATA[

    /** Hide translations. */
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
        if ($sourceUri !== null && $sourceUri !== '') {
            echo ' <a href="' . $sourceUri . '" target="_blank">
                <img src="' . get_file_path('assets/icons/chain.png') . '" title="Text Source" alt="Text Source" />
            </a>';
        }
        echo $textLinks;
        ?>
    </div>
    <div>
        <img class="click" src="/assets/icons/cross.png" title="Close Window" alt="Close Window" onclick="top.close();" />
    </div>
</div>
<?php
\makeMediaPlayer($audio);
?>
