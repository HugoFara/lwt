<?php

namespace Lwt\Interface\Feed_Wizard;

require_once 'Core/database_connect.php';

use Lwt\Database\Connection;
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Feed/feeds.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Language/language_utilities.php';

function feed_wizard_insert_uri(): void
{
    session_start();

    if (isset($_REQUEST['select_mode'])) {
        $_SESSION['wizard']['select_mode'] = $_REQUEST['select_mode'];
    }
    if (isset($_REQUEST['hide_images'])) {
        $_SESSION['wizard']['hide_images'] = $_REQUEST['hide_images'];
    }
    pagestart('Feed Wizard', false);
    if (isset($_REQUEST['err'])) {
        echo '<div class="red">
        <p>+++ ERROR: PLEASE CHECK YOUR NEWSFEED URI!!! +++</p>
        </div>';
    }
    ?>
<form class="validate" action="/feeds/wizard" method="post">
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1">Feed URI: </td>
            <td class="td1">
                <input class="notempty" style="width:90%" type="text" name="rss_url" <?php
                if (isset($_SESSION['wizard']['rss_url'])) {
                    echo 'value="' . $_SESSION['wizard']['rss_url'] . '" ';
                }?>
                />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
    </table>
    <input type="hidden" name="step" value="2" />
    <input type="hidden" name="selected_feed" value="0" />
    <input type="hidden" name="article_tags" value="1" />
    <input type="button" value="Cancel" onclick="location.href='/feeds/edit?del_wiz=1';return false;" />
    <button>Next</button>
</form>
<script type="text/javascript">
    $('h1')
    .eq(-1)
    .html(
        'Feed Wizard | Step 1 - Insert Newsfeed URI ' +
        '<a href="docs/info.html#feed_wizard" target="_blank">' +
        '<img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img></a>'
    )
    .css('text-align','center');
</script>
    <?php
}


function feed_wizard_select_text(): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    session_start();
    if (isset($_REQUEST['edit_feed']) && !isset($_SESSION['wizard'])) {
        $_SESSION['wizard']['edit_feed'] = $_REQUEST['edit_feed'];
        $result = Connection::query(
            "SELECT *
            FROM " . $tbpref . "newsfeeds
            WHERE NfID=" . $_REQUEST['edit_feed']
        );
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        $_SESSION['wizard']['rss_url'] = $row['NfSourceURI'];
        $article_tags = explode('|', str_replace('!?!', '|', $row['NfArticleSectionTags']));
        $_SESSION['wizard']['article_tags'] = '';
        foreach ($article_tags as $tag) {
            if (substr_compare(trim($tag), "redirect", 0, 8) == 0) {
                $_SESSION['wizard']['redirect'] = trim($tag) . ' | ';
            } else {
                $_SESSION['wizard']['article_tags'] .= '<li style="text-align: left">
                <img class="delete_selection" src="/assets/icons/cross.png" title="Delete Selection" alt="-" />'
                . $tag .
                '</li>';
            }
        }
        $filter_tags = explode('|', str_replace('!?!', '|', $row['NfFilterTags']));
        $_SESSION['wizard']['filter_tags'] = '';
        foreach ($filter_tags as $tag) {
            if (trim($tag) != '') {
                $_SESSION['wizard']['filter_tags'] .= '<li style="text-align: left">
                <img class="delete_selection" src="/assets/icons/cross.png" title="Delete Selection" alt="-" />'
                . $tag .
                '</li>';
            }
        }
        $_SESSION['wizard']['feed'] = get_links_from_new_feed($row['NfSourceURI']);
        if (empty($_SESSION['wizard']['feed'])) {
            unset($_SESSION['wizard']['feed']);
            header("Location: /feeds/wizard?step=1&err=1");
            exit();
        }
        $_SESSION['wizard']['feed']['feed_title'] = $row['NfName'];
        $_SESSION['wizard']['options'] = $row['NfOptions'];
        if (empty($_SESSION['wizard']['feed']['feed_text'])) {
            $_SESSION['wizard']['feed']['feed_text'] = '';
            $_SESSION['wizard']['detected_feed'] = 'Detected: «Webpage Link»';
        }
        $_SESSION['wizard']['lang'] = $row['NfLgID'];
        if ($_SESSION['wizard']['feed']['feed_text'] != '') {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «' . $_SESSION['wizard']['feed']['feed_text'] . '»';
        } else {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «Webpage Link»';
        }
        if (
            $_SESSION['wizard']['feed']['feed_text'] != get_nf_option($_SESSION['wizard']['options'], 'article_source')
        ) {
            $source = get_nf_option($_SESSION['wizard']['options'], 'article_source');
            $_SESSION['wizard']['feed']['feed_text'] = $source;
            $feed_len = count(array_filter(array_keys($_SESSION['wizard']['feed']), 'is_numeric'));
            for ($i = 0; $i < $feed_len; $i++) {
                $_SESSION['wizard']['feed'][$i]['text'] = $_SESSION['wizard']['feed'][$i][$source];
            }
        }
    } elseif (isset($_REQUEST['rss_url'])) {
        if (
            isset($_SESSION['wizard']) && !empty($_SESSION['wizard']['feed']) &&
            $_REQUEST['rss_url'] === $_SESSION['wizard']['rss_url']
        ) {
            session_destroy();
            my_die("Your session seems to have an issue, please reload the page.");
        }
        $_SESSION['wizard']['feed'] = get_links_from_new_feed($_REQUEST['rss_url']);
        $_SESSION['wizard']['rss_url'] = $_REQUEST['rss_url'];
        if (empty($_SESSION['wizard']['feed'])) {
            unset($_SESSION['wizard']['feed']);
            header("Location: /feeds/wizard?step=1&err=1");
            exit();
        }
        if (!isset($_SESSION['wizard']['article_tags'])) {
            $_SESSION['wizard']['article_tags'] = '';
        }
        if (!isset($_SESSION['wizard']['filter_tags'])) {
            $_SESSION['wizard']['filter_tags'] = '';
        }
        if (!isset($_SESSION['wizard']['options'])) {
            $_SESSION['wizard']['options'] = 'edit_text=1';
        }
        if (!isset($_SESSION['wizard']['lang'])) {
            $_SESSION['wizard']['lang'] = '';
        }
        if ($_SESSION['wizard']['feed']['feed_text'] != '') {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «' .
            $_SESSION['wizard']['feed']['feed_text'] . '»';
        } else {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «Webpage Link»';
        }
    }
    if (isset($_REQUEST['filter_tags'])) {
        $_SESSION['wizard']['filter_tags'] = $_REQUEST['filter_tags'];
    }
    if (isset($_REQUEST['selected_feed'])) {
        $_SESSION['wizard']['selected_feed'] = $_REQUEST['selected_feed'];
    }
    if (isset($_REQUEST['maxim'])) {
        $_SESSION['wizard']['maxim'] = $_REQUEST['maxim'];
    }
    if (!isset($_SESSION['wizard']['maxim'])) {
        $_SESSION['wizard']['maxim'] = 1;
    }
    if (isset($_REQUEST['select_mode'])) {
        $_SESSION['wizard']['select_mode'] = $_REQUEST['select_mode'];
    }
    if (!isset($_SESSION['wizard']['select_mode'])) {
        $_SESSION['wizard']['select_mode'] = '0';
    }
    if (isset($_REQUEST['hide_images'])) {
        $_SESSION['wizard']['hide_images'] = $_REQUEST['hide_images'];
    }
    if (!isset($_SESSION['wizard']['hide_images'])) {
        $_SESSION['wizard']['hide_images'] = 'yes';
    }
    if (!isset($_SESSION['wizard']['redirect'])) {
        $_SESSION['wizard']['redirect'] = '';
    }
    if (!isset($_SESSION['wizard']['selected_feed'])) {
        $_SESSION['wizard']['selected_feed'] = 0;
    }
    if (!isset($_SESSION['wizard']['host'])) {
        $_SESSION['wizard']['host'] = array();
    }
    if (isset($_REQUEST['host_status']) && isset($_REQUEST['host_name'])) {
        $host_name = $_REQUEST['host_name'];
        $_SESSION['wizard']['host'][$host_name] = $_REQUEST['host_status'];
    }

    if (isset($_REQUEST['NfName'])) {
        $_SESSION['wizard']['feed']['feed_title'] = $_REQUEST['NfName'];
    }

    $feed_len = count(array_filter(array_keys($_SESSION['wizard']['feed']), 'is_numeric'));


    if (
        isset($_REQUEST['NfArticleSection']) &&
        ($_REQUEST['NfArticleSection'] != $_SESSION['wizard']['feed']['feed_text'])
    ) {
        $_SESSION['wizard']['feed']['feed_text'] = $_REQUEST['NfArticleSection'];
        $source = $_SESSION['wizard']['feed']['feed_text'];

        for ($i = 0; $i < $feed_len; $i++) {
            if ($_SESSION['wizard']['feed']['feed_text'] != '') {
                $_SESSION['wizard']['feed'][$i]['text'] = $_SESSION['wizard']['feed'][$i][$source];
            } else {
                unset($_SESSION['wizard']['feed'][$i]['text']);
            }
            unset($_SESSION['wizard']['feed'][$i]['html']);
        }
        $_SESSION['wizard']['host'] = array();
    }
    pagestart_nobody('Feed Wizard');
    ?>
<script type="text/javascript" src="/assets/js/jquery.xpath.min.js" charset="utf-8"></script>
<script type="text/javascript">
    // Extend jQuery
    $(function() {
        jQuery.fn.get_adv_xpath = extend_adv_xpath
    });

    filter_Array = [];
    // Prepare the page
    $(lwt_feed_wizard.prepareInteractions);

    if (<?php echo json_encode($_SESSION['wizard']['hide_images'] == 'yes'); ?>) {
        $(function () {
            $("img").not($("#lwt_header").find("*")).css("display","none");
        });
    }
    const lwt_wiz_select_test = {
        clickCancel: function() {
            $('#adv').hide();
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            return false;
        },

        changeSelectMode: function() {
            $('*').removeClass('lwt_marked_text');
            $('*[class=\'\']').removeAttr('class');
            $('#get_button').prop('disabled', true);
            $('#mark_action').empty();
            $('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
            return false;
        },

        changeHideImage: function() {
            if ($(this).val() == 'no')
                $('img').not($('#lwt_header').find('*')).css('display', '');
            else
                $('img').not($('#lwt_header').find('*')).css('display', 'none');
            return false;
        },

        clickBack: function() {
            location.href = '/feeds/wizard?step=1&amp;select_mode=' +
                encodeURIComponent($('select[name=\'select_mode\']').val()) +
                '&amp;hide_images=' +
                encodeURIComponent($('select[name=\'hide_images\']').val());
            return false;
        },

        clickMinMax: function() {
            $('#lwt_container').toggle();
            if ($('#lwt_container').css('display') == 'none') {
                $('input[name=\'maxim\']').val(0);
            } else {
                $('input[name=\'maxim\']').val(1);
            }
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            return false;
        },

        changeSelectedFeed: function() {
            var html = $('#lwt_sel').html();
            $('input[name=\'html\']').val(html);
            document.lwt_form1.submit();
        },

        changeArticleSection: function() {
            var html = $('#lwt_sel').html();
            $('input[name=\'html\']').val(html);
            document.lwt_form1.submit();
        },

        setMaxim: function() {
            $('#lwt_container').hide();
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            if ($('#lwt_container').css('display') == 'none') {
                $('input[name=\'maxim\']').val(0);
            } else {
                $('input[name=\'maxim\']').val(1);
            }
        }
    }
</script>
<div id="lwt_header">
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
        <div id="adv" style="display: none;">
        <button onclick="lwt_wiz_select_test.clickCancel()">Cancel</button>
        <button id="adv_get_button">Get</button>
    </div>
    <div id="settings" style="display: none;">
        <p><b>Feed Wizard | Settings</b></p>
        <div style="margin-left:150px;text-align:left">
            Selection Mode:
            <select name="select_mode" onchange="lwt_wiz_select_test.changeSelectMode()">
                <option value="0"<?php if ($_SESSION['wizard']['select_mode'] == '0') {
                    echo ' selected';
                                 }?>>Smart Selection</option>
                <option value="all"<?php if ($_SESSION['wizard']['select_mode'] == 'all') {
                    echo ' selected';
                                   }?>>Get All Attributes</option>
                <option value="adv"<?php if ($_SESSION['wizard']['select_mode'] == 'adv') {
                    echo ' selected';
                                   }?>>Advanced Selection</option>
                </select><br />
                Hide Images: <select name="hide_images" onchange="lwt_wiz_select_test.changeHideImage()">
                <option value="yes"<?php if ($_SESSION['wizard']['hide_images'] == 'yes') {
                    echo ' selected';
                                   }?>>Yes</option>
                <option value="no"<?php if ($_SESSION['wizard']['hide_images'] == 'no') {
                    echo ' selected';
                                  }?>>No</option>
            </select>
        </div>
        <button style="position:relative;left:150px;" onclick="$('#settings').hide();return false;">
            OK
        </button>
    </div>
    <div id="lwt_container">
        <?php echo_lwt_logo();?>
        <h1>Feed Wizard | Step 2 - Select Article Text
        <a href="docs/info.html#feed_wizard" target="_blank">
            <img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img>
        </a>
        </h1>
        <ol id="lwt_sel" style="margin-left:77px">
            <?php
            if (isset($_REQUEST['html'])) {
                echo $_REQUEST['html'];
            }
            if (isset($_REQUEST['article_tags']) || isset($_REQUEST['edit_feed'])) {
                echo $_SESSION['wizard']['article_tags'];
            } ?>
        </ol>
        <table class="tab2" style="margin-left:77px" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1" style="text-align:left">Name: </td>
                <td class="td1">
                    <input class="notempty" size="50" type="text" name="NfName"
                    value="<?php echo htmlspecialchars($_SESSION['wizard']['feed']['feed_title'], ENT_COMPAT); ?>" />
                    <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                    alt="Field must not be empty" />
                </td>
            </tr>
            <tr>
                <td class="td1" style="text-align:left">Newsfeed url: </td>
                <td class="td1" style="text-align:left">
                    <?php echo $_SESSION['wizard']['rss_url']; ?>
                </td>
            </tr>
            <tr>
                <td class="td1" style="text-align:left">Article Source: </td>
                <td class="td1" style="text-align:left">
                    <select name="NfArticleSection"
                    onchange="lwt_wiz_select_test.changeArticleSection()">
                        <option value="" <?php
                        if (
                            !array_key_exists('feed_text', $_SESSION['wizard']['feed']) ||
                            $_SESSION['wizard']['feed']['feed_text'] == ''
                        ) {
                            echo ' selected="selected"';
                        }
                        ?>>
                            Webpage Link
                        </option>
                        <?php
                        $sources = array('description','encoded','content');
                        foreach ($sources as $source) {
                            if (isset($_SESSION['wizard']['feed'][0][$source])) {
                                echo '<option value="' . $source . '"';
                                if (
                                    array_key_exists('feed_text', $_SESSION['wizard']['feed']) &&
                                    $_SESSION['wizard']['feed']['feed_text'] == $source
                                ) {
                                    echo ' selected="selected"';
                                }
                                echo '>' . $source . '</option>';
                            }
                        }
                        ?>
                            </select>
                            <?php echo '(' . $_SESSION['wizard']['detected_feed'] . ')'; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <table style="width:100%;">
                <tr>
                    <td>
                        <input type="hidden" name="rss_url"
                        value="<?php echo $_SESSION['wizard']['rss_url']; ?>" />
                        <input type="button" value="Cancel"
                        onclick="location.href='/feeds/edit?del_wiz=1';return false;" />
                    </td>
                    <td>
                        <span>
                            <select name="selected_feed"
                            style="width:250px;max-width:200px;"
                            onchange="lwt_wiz_select_test.changeSelectedFeed()">
                                <?php
                                $current_host = '';
                                $current_status = '';
                                for ($i = 0; $i < $feed_len; $i++) {
                                    $feed_host = parse_url(
                                        $_SESSION['wizard']['feed'][$i]['link'],
                                        PHP_URL_HOST
                                    );
                                    if (gettype($feed_host) != 'string') {
                                        my_die('$feed_host is of type ' . gettype($feed_host));
                                    }
                                    if (!isset($_SESSION['wizard']['host'][$feed_host])) {
                                        $_SESSION['wizard']['host'][$feed_host] = '-';
                                    }
                                    echo '<option value="' . $i . '" title="' . $_SESSION['wizard']['feed'][$i]['title'] . '"';
                                    if ($i == $_SESSION['wizard']['selected_feed']) {
                                        echo ' selected="selected"';
                                        $current_host = $feed_host;
                                        $current_status = $_SESSION['wizard']['host'][$feed_host];
                                    }
                                    echo '>' .
                                    (
                                        (
                                            isset($_SESSION['wizard']['feed'][$i]['html']) ||
                                            $i == $_SESSION['wizard']['selected_feed']
                                        ) ? '▸ ' : '- '
                                    ) .
                                    ($i + 1)  . ' ' . $_SESSION['wizard']['host'][$feed_host] . '&nbsp;host: ' .
                                    $feed_host . '</option>';
                                }
                                ?>
                            </select>
                            <input type="hidden" name="host_name" value="<?php echo $current_host ?>" />
                            <?php if (count($_SESSION['wizard']['host']) > 1) { ?>
                            <select id="host_status" name="host_status">
                                <option value="&nbsp;-&nbsp;" <?php
                                if ($current_status == '&nbsp;-&nbsp;') {
                                    echo 'selected="selected"';
                                }
                                ?>>
                                &nbsp;-&nbsp;
                            </option>
                            <option value="☆" <?php
                            if ($current_status == '☆') {
                                echo 'selected="selected"';
                            }
                            ?>>☆</option>
                            <option value="★" <?php
                            if ($current_status == '★') {
                                echo 'selected="selected"';
                            }
                            ?>>★</option>
                        </select>
                                <?php
                            }
                            ?>
                    </span>
                </td>
                <td style="width:270px;text-align: right;">
                    <select name="mark_action" id="mark_action">
                        <option value="">[Click On Text]</option>
                    </select>
                    <button id="get_button" name="button" disabled>Get</button>
                    <img src="/assets/icons/wrench-screwdriver.png" title="Settings" alt="-"
                    onclick="$('#settings').show();return false;" />
                </td>
                <td>
                    <span>
                        <input type="button" value="Back"
                        onclick="lwt_wiz_select_test.clickBack()" />
                        <button id="next">Next</button>
                    </span>
                </td>
                <td style="width:63px"></td>
            </tr>
        </table>
        <button style="position:absolute;right:10px;top:10px"
        onclick="lwt_wiz_select_test.clickMinMax()">
            min/max
        </button>
        <input type="hidden" name="step" value="2" />
        <input type="hidden" name="html" />
        <input type="hidden" id="article_tags" name="article_tags" disabled />
        <input type="hidden" name="maxim" value="1" />
    </form>
</div>
    <?php
    echo '<br /><p id="lwt_last"></p>';
    $i = $_SESSION['wizard']['selected_feed'];
    $a_feed = null;
    if (!isset($_SESSION['wizard']['feed'][$i]['html'])) {
        $a_feed[0] = $_SESSION['wizard']['feed'][$i];
        $_SESSION['wizard']['feed'][$i]['html'] = get_text_from_rsslink(
            $a_feed,
            $_SESSION['wizard']['redirect'] . 'new',
            'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
            get_nf_option($_SESSION['wizard']['options'], 'charset')
        );
    }
    echo $_SESSION['wizard']['feed'][$i]['html'];
    ?>
<script type="text/javascript">
    if (<?php echo json_encode($_SESSION['wizard']['maxim'] == 0); ?>) {
        $(lwt_wiz_select_test.setMaxim);
    }
</script>
    <?php
}


function feed_wizard_filter_text(): void
{
    session_start();

    if (isset($_REQUEST['NfName'])) {
        $_SESSION['wizard']['feed']['feed_title'] = $_REQUEST['NfName'];
    }
    if (isset($_REQUEST['NfArticleSection'])) {
        $_SESSION['wizard']['article_section'] = $_REQUEST['NfArticleSection'];
    }
    if (isset($_REQUEST['article_selector'])) {
        $_SESSION['wizard']['article_selector'] = $_REQUEST['article_selector'];
    }
    if (isset($_REQUEST['selected_feed'])) {
        $_SESSION['wizard']['selected_feed'] = $_REQUEST['selected_feed'];
    }
    if (isset($_REQUEST['article_tags'])) {
        $_SESSION['wizard']['article_tags'] = $_REQUEST['article_tags'];
    }
    if (isset($_REQUEST['html'])) {
        $_SESSION['wizard']['filter_tags'] = $_REQUEST['html'];
    }
    if (isset($_REQUEST['NfOptions'])) {
        $_SESSION['wizard']['options'] = $_REQUEST['NfOptions'];
    }
    if (isset($_REQUEST['NfLgID'])) {
        $_SESSION['wizard']['lang'] = $_REQUEST['NfLgID'];
    }
    if (isset($_REQUEST['NfName'])) {
        $_SESSION['wizard']['feed']['feed_title'] = $_REQUEST['NfName'];
    }
    if (!isset($_SESSION['wizard']['article_tags'])) {
        $_SESSION['wizard']['article_tags'] = '';
    }
    if (isset($_REQUEST['maxim'])) {
        $_SESSION['wizard']['maxim'] = $_REQUEST['maxim'];
    }
    if (isset($_REQUEST['select_mode'])) {
        $_SESSION['wizard']['select_mode'] = $_REQUEST['select_mode'];
    }
    if (isset($_REQUEST['hide_images'])) {
        $_SESSION['wizard']['hide_images'] = $_REQUEST['hide_images'];
    }
    if (!isset($_SESSION['wizard']['select_mode'])) {
        $_SESSION['wizard']['select_mode'] = '';
    }
    if (!isset($_SESSION['wizard']['maxim'])) {
        $_SESSION['wizard']['maxim'] = 1;
    }
    if (!isset($_SESSION['wizard']['selected_feed'])) {
        $_SESSION['wizard']['selected_feed'] = 0;
    }
    if (!isset($_SESSION['wizard']['host2'])) {
        $_SESSION['wizard']['host2'] = array();
    }
    if (isset($_REQUEST['host_status']) and isset($_REQUEST['host_name'])) {
        $host_name = $_REQUEST['host_name'];
        $_SESSION['wizard']['host'][$host_name] = $_REQUEST['host_status'];
    }
    if (isset($_REQUEST['host_status2']) and isset($_REQUEST['host_name'])) {
        $host_name = $_REQUEST['host_name'];
        $_SESSION['wizard']['host2'][$host_name] = $_REQUEST['host_status2'];
    }
    $feed_len = count(array_filter(array_keys($_SESSION['wizard']['feed']), 'is_numeric'));
    pagestart_nobody("Feed Wizard");
    ?>
<script type="text/javascript" src="/assets/js/jquery.xpath.min.js" charset="utf-8"></script>
<script type="text/javascript">
    filter_Array = [];
    const lwt_wizard_filter = {
        updateFilterArray: function() {
            articleSection = <?php echo json_encode((string) $_SESSION['wizard']['article_selector']); ?>;
            articleSection.trim();
            if (articleSection == '') {
                alert("Article section is empty!")
            }
            $('#lwt_header')
                .nextAll()
                .find('*')
                .addBack()
                .not($(document).xpath(articleSection).find('*').addBack())
                .not($('#lwt_header').find('*').addBack())
                .each(function() {
                    $(this).addClass('lwt_filtered_text');
                    filter_Array.push(this);
                });
        },

        hideImages: function() {
            $("img").not($("#lwt_header").find("*")).css("display", "none");
        },

        clickCancel: function() {
            $('#adv').hide();
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            return false;
        },

        changeSelectMode: function() {
            $('*').removeClass('lwt_marked_text');
            $('*[class=\'\']').removeAttr('class');
            $('#get_button').prop('disabled', true);
            $('#mark_action').empty();
            $('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
            return false;
        },

        changeHideImages: function() {
            if ($(this).val() == 'no')
                $('img').not($('#lwt_header').find('*')).css('display', '');
            else
                $('img').not($('#lwt_header').find('*')).css('display', 'none');
            return false;
        },

        changeSelectedFeed: function() {
            const html = $('#lwt_sel').html();
            $('input[name=\'html\']').val(html);
            document.lwt_form1.submit();
        },

        clickBack: function() {
            location.href = '/feeds/wizard?step=2&amp;article_tags=1&amp;maxim=' +
                $('#maxim').val() + '&amp;filter_tags=' +
                encodeURIComponent($('#lwt_sel').html()) + '&amp;select_mode=' +
                encodeURIComponent($('select[name=\'select_mode\']').val()) +
                '&amp;hide_images=' +
                encodeURIComponent($('select[name=\'hide_images\']').val());
            return false;
        },

        clickMinMax: function() {
            $('#lwt_container').toggle();
            if ($('#lwt_container').css('display') == 'none') {
                $('input[name=\'maxim\']').val(0);
            } else {
                $('input[name=\'maxim\']').val(1);
            }
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            return false;
        },

        setMaxim: function() {
            $('#lwt_container').hide();
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            if ($('#lwt_container').css('display') == 'none') {
                $('input[name=\'maxim\']').val(0);
            } else {
                $('input[name=\'maxim\']').val(1);
            }
        }
    }

    // Extend jQuery
    $(function() {
        jQuery.fn.get_adv_xpath = extend_adv_xpath
    });

    // Prepare the page
    $(lwt_feed_wizard.prepareInteractions);

    if (<?php echo json_encode($_SESSION['wizard']['hide_images'] == 'yes'); ?>) {
        $(lwt_wizard_filter.hideImages);
    }

    $(lwt_wizard_filter.updateFilterArray);
</script>
<div id="lwt_header">
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
    <div id="adv" style="display: none;">
    <button onclick="lwt_wizard_filter.clickCancel()">Cancel</button>
    <button id="adv_get_button">Get</button>
</div>
<div id="settings" style="display: none;">
    <p><b>Feed Wizard | Settings</b></p>
    <div style="margin-left:150px;text-align:left">
        Selection Mode:
        <select name="select_mode" onchange="lwt_wizard_filter.changeSelectMode()">
            <option value="0"<?php if ($_SESSION['wizard']['select_mode'] == '0') {
                echo ' selected';
                             }?>>Smart Selection</option>
            <option value="all"<?php if ($_SESSION['wizard']['select_mode'] == 'all') {
                echo ' selected';
                               }?>>Get All Attributes</option>
            <option value="adv"<?php if ($_SESSION['wizard']['select_mode'] == 'adv') {
                echo ' selected';
                               }?>>Advanced Selection</option>
        </select><br />
        Hide Images:
        <select name="hide_images" onchange="lwt_wizard_filter.changeHideImage()">
            <option value="yes"<?php if ($_SESSION['wizard']['hide_images'] == 'yes') {
                echo ' selected';
                               }?>>Yes</option>
            <option value="no"<?php if ($_SESSION['wizard']['hide_images'] == 'no') {
                echo ' selected';
                              }?>>No</option>
        </select>
    </div>
    <button style="position:relative;left:150px;" onclick="$('#settings').hide();return false;">OK</button>
    </div>
    <div id="lwt_container">
        <?php echo_lwt_logo();?>
        <h1>Feed Wizard | Step 3 - Filter Text
        <a href="docs/info.html#feed_wizard" target="_blank">
            <img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img>
        </a>
        </h1>
        <ol id="lwt_sel" style="margin-left:77px">
            <?php echo $_SESSION['wizard']['filter_tags']; ?>
        </ol>
        <table class="tab2" style="margin-left:77px" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1" style="text-align:left">Name: </td>
                <td class="td1" style="text-align:left">
                    <?php echo htmlspecialchars($_SESSION['wizard']['feed']['feed_title'], ENT_COMPAT); ?></td></tr>
                <tr>
                    <td class="td1" style="text-align:left">Newsfeed url: </td>
                    <td class="td1" style="text-align:left">
                        <?php echo $_SESSION['wizard']['rss_url']; ?>
                    </td>
                </tr>
                <tr>
                    <td class="td1" style="text-align:left">Article Section: </td>
                    <td class="td1" style="text-align:left">
                        <?php echo $_SESSION['wizard']['article_section']; ?>
                    </td>
                </tr>
                <tr>
                    <td class="td1" style="text-align:left">Article Source: </td>
                    <td class="td1" style="text-align:left">
                        <?php
                        if (array_key_exists('feed_text', $_SESSION['wizard']['feed'])) {
                            echo $_SESSION['wizard']['feed']['feed_text'];
                        } else {
                            echo 'Webpage Link';
                            $_SESSION['wizard']['feed']['feed_text'] = '';
                        } ?>
                    </td>
                </tr>
            </table>
        </div>
        <table style="width:100%;">
            <tr>
                <td>
                    <input type="button" value="Cancel" onclick="location.href='/feeds/edit?del_wiz=1';return false;" />
                </td>
                <td>
                    <span>
                        <select name="selected_feed" style="width:250px;max-width:200px;"
                        onchange="lwt_wizard_filter.changeSelectedFeed()">
                            <?php
                            $current_host = '';
                            $current_status = '';

                            for ($i = 0; $i < $feed_len; $i++) {
                                $feed_host = parse_url($_SESSION['wizard']['feed'][$i]['link']);
                                $feed_host = $feed_host['host'];
                                if (!isset($_SESSION['wizard']['host2'][$feed_host])) {
                                    $_SESSION['wizard']['host2'][$feed_host] = '-';
                                }
                                echo "<option value=" . $i . " title=" . $_SESSION['wizard']['feed'][$i]['title'];
                                if ($i == $_SESSION['wizard']['selected_feed']) {
                                    echo ' selected="selected"';
                                    $current_host = $feed_host;
                                    $current_status = $_SESSION['wizard']['host2'][$feed_host];
                                }
                                echo '>' . (
                                    (
                                        isset($_SESSION['wizard']['feed'][$i]['html']) ||
                                        $i == $_SESSION['wizard']['selected_feed']
                                    ) ?
                                        ('▸ ') : ('- ')
                                ) .
                                    ($i + 1)  . ' ' . $_SESSION['wizard']['host2'][$feed_host] .
                                    '&nbsp;host: ' . $feed_host . '</option>';
                            }
                            ?>
                    </select>
                    <input type="hidden" name="host_name" value="<?php echo $current_host ?>" />
                        <?php if (count($_SESSION['wizard']['host']) > 1) { ?>
                    <select id="host_status" name="host_status2">
                        <option value="&nbsp;-&nbsp;" <?php if ($current_status == '&nbsp;-&nbsp;') {
                            echo 'selected="selected"';
                                                      } ?>>
                                &nbsp;-&nbsp;
                            </option>
                            <option value="☆" <?php if ($current_status == '☆') {
                                echo 'selected="selected"';
                                              } ?>>☆</option>
                            <option value="★" <?php if ($current_status == '★') {
                                echo 'selected="selected"';
                                              } ?>>★</option>
                        </select>
                            <?php
                        } ?>
                    </span>
                </td>
                <td style="width:280px;text-align: right;">
                    <select name="mark_action" id="mark_action" >
                        <option value="">[Click On Text]</option>
                    </select>
                    <button id="filter_button" name="button" disabled>Filter</button>
                    <img src="/assets/icons/wrench-screwdriver.png" title="Settings" alt="-"
                    onclick="$('#settings').show();return false;" />
                </td>
                <td>
                    <span>
                        <input type="button" value="Back"
                        onclick="lwt_wizard_filter.clickBack()" />
                        <button id="next">Next</button>
                    </span>
                </td>
                <td style="width:63px"></td>
            </tr>
        </table>
        <button style="position:absolute;right:10px;top:10px"
        onclick="lwt_wizard_filter.clickMinMax()">
            min/max
        </button>
        <input type="hidden" id="filter_tags" name="filter_tags" disabled />
        <input type="hidden" name="html" />
        <input type="hidden" name="step" value="3" />
        <input type="hidden" id="maxim" name="maxim" value="1" />
    </form>
</div>
    <?php
    echo '<br /><p id="lwt_last"></p>';
    $i = $_SESSION['wizard']['selected_feed'];
    $a_feed = null;
    if (!isset($_SESSION['wizard']['feed'][$i]['html'])) {
        $a_feed[0] = $_SESSION['wizard']['feed'][$i];
        $_SESSION['wizard']['feed'][$i]['html'] = get_text_from_rsslink(
            $a_feed,
            $_SESSION['wizard']['redirect'] . 'new',
            'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
            get_nf_option($_SESSION['wizard']['options'], 'charset')
        );
    }
    echo $_SESSION['wizard']['feed'][$i]['html'];
    ?>
    <script type="text/javascript">
        if (<?php echo json_encode($_SESSION['wizard']['maxim'] == 0); ?>) {
            $(lwt_wizard_filter.setMaxim);
        }
    </script>
    <?php
}


function feed_wizard_edit_options(): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    session_start();
    pagestart('Feed Wizard', false);
    if (isset($_REQUEST['filter_tags'])) {
        $_SESSION['wizard']['filter_tags'] = $_REQUEST['filter_tags'];
    }
    ?>
<form class="validate" action="edit_feeds.php" method="post">
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1">Language: </td>
            <td class="td1">
                <select name="NfLgID" class="notempty">
                    <option value="">[Select...]</option>
                <?php

                $result = Connection::query(
                    "SELECT LgName, LgID
        FROM " . $tbpref . "languages
        WHERE LgName<>''
        ORDER BY LgName"
                );
                while ($row_l = mysqli_fetch_assoc($result)) {
                    echo '<option value="' . $row_l['LgID'] . '"';
                    if ($_SESSION['wizard']['lang'] === $row_l['LgID']) {
                        echo ' selected="selected"';
                    }
                    echo '>' . $row_l['LgName'] . '</option>';
                }
                mysqli_free_result($result);
                $auto_upd_i = get_nf_option($_SESSION['wizard']['options'], 'autoupdate');
                if ($auto_upd_i == null) {
                    $auto_upd_v = null;
                } else {
                    $auto_upd_v = substr($auto_upd_i, -1);
                    $auto_upd_i = substr($auto_upd_i, 0, -1);
                }

                ?>
                </select>
                <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1">Name: </td>
            <td class="td1">
                <input class="notempty" style="width:95%" type="text" name="NfName"
                value="<?php echo htmlspecialchars($_SESSION['wizard']['feed']['feed_title'], ENT_COMPAT); ?>" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1">Newsfeed url: </td>
            <td class="td1">
                <input class="notempty" style="width:95%" type="text"
                name="NfSourceURI"
                value="<?php echo htmlspecialchars($_SESSION['wizard']['rss_url']); ?>" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                alt="Field must not be empty" />
            </td>
        </tr>
            <tr>
                <td class="td1">Article Section: </td>
                <td class="td1">
                    <input class="notempty" style="width:95%" type="text"
                    name="NfArticleSectionTags"
                    value="<?php echo htmlspecialchars(preg_replace('/[ ]+/', ' ', trim($_SESSION['wizard']['redirect'] . $_SESSION['wizard']['article_section']))); ?>" />
                    <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                    alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1">Filter Tags: </td>
            <td class="td1">
                <input type="text" style="width:95%" name="NfFilterTags"
                value="<?php echo htmlspecialchars(preg_replace('/[ ]+/', ' ', trim($_REQUEST['html']))); ?>" />
            </td>
        </tr>
        <tr>
            <td class="td1">Options: </td>
            <td class="td1">
                <table style="width:100%">
                    <tr>
                        <td style="width:35%">
                            <input type="checkbox" name="edit_text"<?php
                            if (get_nf_option($_SESSION['wizard']['options'], 'edit_text') !== null) {
                                echo ' checked="checked"';
                            } ?>
                            /> Edit Text
                        </td>
                        <td>
                            <input type="checkbox" name="c_autoupdate"<?php
                            if ($auto_upd_i !== null) {
                                echo ' checked="checked"';
                            } ?>
                            /> Auto Update Interval:
                            <input class="posintnumber<?php
                            if (get_nf_option($_SESSION['wizard']['options'], 'autoupdate') !== null) {
                                echo ' notempty';
                            }
                            ?>" data_info="Auto Update Interval" type="number"
                                                    min="0" size="4" name="autoupdate" value="<?php
                                                    echo $auto_upd_i; ?>" <?php
if ($auto_upd_i == null) {
    echo ' disabled';
} ?>
                                                    />
                                                    <select name="autoupdate" value="<?php
                                                    echo $auto_upd_v; ?>" <?php if ($auto_upd_v == null) {
                                echo ' disabled';
                                                    } ?>>
                                <option value="h"<?php if ($auto_upd_v == 'h') {
                                    echo ' selected="selected"';
                                                 }?>>Hour(s)</option>
                                <option value="d"<?php if ($auto_upd_v == 'd') {
                                    echo ' selected="selected"';
                                                 }?>>Day(s)</option>
                                <option value="w"<?php if ($auto_upd_v == 'w') {
                                    echo ' selected="selected"';
                                                 }?>>Week(s)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" name="c_max_links"<?php if (get_nf_option($_SESSION['wizard']['options'], 'max_links') !== null) {
                                echo ' checked="checked"';
                                                                     } ?> /> Max. Links:
                            <input class="<?php if (get_nf_option($_SESSION['wizard']['options'], 'max_links') !== null) {
                                echo 'notempty ';
                                          } ?>posintnumber maxint_300" data_info="Max. Links"
                            type="number" min="0" max="300" size="4" name="max_links"
                            value="<?php
                                echo get_nf_option($_SESSION['wizard']['options'], 'max_links');
                            ?>" <?php
if (get_nf_option($_SESSION['wizard']['options'], 'max_links') == null) {
    echo ' disabled';
} ?> />
                            </td>
                            <td>
                                <input type="checkbox" name="c_charset"<?php if (get_nf_option($_SESSION['wizard']['options'], 'charset') !== null) {
                                    echo ' checked="checked"';
                                                                       } ?> /> Charset: <input <?php if (get_nf_option($_SESSION['wizard']['options'], 'charset') !== null) {
    echo 'class="notempty" ';
                                                                       } ?>type="text" data_info="Charset" size="20" name="charset" value="<?php
                                    echo get_nf_option($_SESSION['wizard']['options'], 'charset');
?>" <?php
if (get_nf_option($_SESSION['wizard']['options'], 'charset') == null) {
    echo ' disabled';
} ?> />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox"
                            name="c_max_texts"<?php if (get_nf_option($_SESSION['wizard']['options'], 'max_texts') !== null) {
                                echo ' checked="checked"';
                                              } ?> />
                            Max. Texts:
                            <input class="<?php if (get_nf_option($_SESSION['wizard']['options'], 'max_texts') !== null) {
                                echo 'notempty ';
                                          } ?>posintnumber maxint_30" data_info="Max. Texts"
                            type="number" min="0" max="30" size="4" name="max_texts"
                            value="<?php
                            echo get_nf_option($_SESSION['wizard']['options'], 'max_texts');
                            ?>" <?php
if (get_nf_option($_SESSION['wizard']['options'], 'max_texts') == null) {
    echo ' disabled';
} ?> />
                        </td>
                        <td>
                            <input type="checkbox"
                            name="c_tag"<?php if (get_nf_option($_SESSION['wizard']['options'], 'tag') !== null) {
                                echo ' checked="checked"';
                                        } ?> />
                            Tag:
                            <input <?php if (get_nf_option($_SESSION['wizard']['options'], 'tag') !== null) {
                                echo 'class="notempty" ';
                                   } ?>type="text" data_info="Tag" size="20" name="tag"
                            value="<?php echo get_nf_option($_SESSION['wizard']['options'], 'tag'); ?>"
                            <?php if (get_nf_option($_SESSION['wizard']['options'], 'tag') == null) {
                                echo ' disabled';
                            } ?> />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php if (isset($_SESSION['wizard']['edit_feed'])) {
        echo '<input type="hidden" name="NfID" value="' . $_SESSION['wizard']['edit_feed'] . '" />';
    }?>
    <input type="button" value="Cancel" onclick="location.href='/feeds/edit?del_wiz=1';" />
    <input type="hidden" name="NfOptions" value="" />
    <input type="hidden" name="article_source"
    value="<?php echo htmlspecialchars($_SESSION['wizard']['feed']['feed_text']); ?>" />
    <input type="hidden" name="save_feed" value="1" />
    <input type="button" value="Back" onclick="str=$('[name=\'edit_text\']:checked').length > 0?'edit_text=1,':'';$('[name^=\'c_\']').each(function(){str+=this.checked ? $(this).parent().children('input[type=\'text\']').attr('name') + '='+ $(this).parent().children('input[type=\'text\']').val() + ($(this).attr('name')=='c_autoupdate' ? $(this).parent().find('select').val() + ',' : ','): '';});location.href='/feeds/wizard?step=3&amp;NfOptions='+str+'&amp;NfLgID='+$('select[name=\'NfLgID\']').val()+'&amp;NfName='+$('input[name=\'NfName\']').val();return false;" />
    <input type="submit" value="Save" />
</form>
<script type="text/javascript">
    if(<?php if (isset($_SESSION['wizard']['edit_feed'])) {
        echo $_SESSION['wizard']['edit_feed'];
       } else {
           echo '0';
       } ?>) {
        $('input[name="save_feed"]').attr('name','update_feed');
        $('input[type="submit"]').val('Update');
    }
    $('h1')
    .eq(-1)
    .html(
        'Feed Wizard | Step 4 - Edit Options ' +
        '<a href="docs/info.html#feed_wizard" target="_blank">' +
        '<img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img></a>'
    )
    .css('text-align','center');
    $('[name^="c_"]').change(function(){
        if(this.checked){
            $(this).parent().children('input[type="text"]')
            .removeAttr('disabled').addClass("notempty");
            $(this).parent().find('select').removeAttr('disabled');
        } else{
            $(this).parent().children('input[type="text"]')
            .attr('disabled','disabled').removeClass("notempty");
            $(this).parent().find('select').attr('disabled','disabled');
        }
    });
    $('[type="submit"]').on('click', function(){
        var str;
        str=$('[name="edit_text"]:checked').length > 0?"edit_text=1,":"";
        $('[name^="c_"]').each(function(){
            str+=this.checked ? $(this).parent()
            .children('input[type="text"]').attr('name') + '='
            + $(this).parent().children('input[type="text"]').val()
            + (
                $(this).attr('name')=='c_autoupdate' ?
                $(this).parent().find('select').val() + ',' :
                ','
            ) : '';
        });
        if($('input[name="article_source"]').val()!='')
            str=str+'article_source='+ $('input[name="article_source"]').val();
        $('input[name="NfOptions"]').val(str);
    });
</script>
    <?php
}



switch ((int)$_REQUEST['step']) {
    case 2:
        feed_wizard_select_text();
        break;
    case 3:
        feed_wizard_filter_text();
        break;
    case 4:
        feed_wizard_edit_options();
        unset($_SESSION['wizard']);
        break;
    case 1:
    default:
        feed_wizard_insert_uri();
        break;
}

pageend();
?>
