<?php

require_once __DIR__ . '/database_connect.php';

// -------------------------------------------------------------

function load_feeds($currentfeed): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $cnt = 0;
    $ajax = $feeds = array();
    echo '<script type="text/javascript">';
    if (isset($_REQUEST['check_autoupdate'])) {
        $result = do_mysqli_query(
            "SELECT * FROM " . $tbpref . "newsfeeds
            where `NfOptions` like '%autoupdate=%'"
        );
        while ($row = mysqli_fetch_assoc($result)) {
            if ($autoupdate = get_nf_option($row['NfOptions'], 'autoupdate')) {
                if (strpos($autoupdate, 'h') !== false) {
                    $autoupdate = str_replace('h', '', $autoupdate);
                    $autoupdate = 60 * 60 * (int)$autoupdate;
                } elseif (strpos($autoupdate, 'd') !== false) {
                    $autoupdate = str_replace('d', '', $autoupdate);
                    $autoupdate = 60 * 60 * 24 * (int)$autoupdate;
                } elseif (strpos($autoupdate, 'w') !== false) {
                    $autoupdate = str_replace('w', '', $autoupdate);
                    $autoupdate = 60 * 60 * 24 * 7 * (int)$autoupdate;
                } else {
                    continue;
                }
                if (time() > ($autoupdate + (int) $row['NfUpdate'])) {
                    $ajax[$cnt] =  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" .
                        ($row['NfID'] ?? '') . "').replaceWith( '<div id=\"feed_" . ($row['NfID'] ?? '') . "\" class=\"msgblue\"><p>" .
                        addslashes((string) $row['NfName']) . ": loading</p></div>' );},url:'api.php/v1/feeds/" .
                            ($row['NfID'] ?? '') . "/load', data: { name: '" . addslashes((string) $row['NfName']) .
                            "', source_uri: '" . ($row['NfSourceURI'] ?? '') .
                            "', options: '" . ($row['NfOptions'] ?? '') .
                            "' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);var msg = data.error ? '<div class=\"red\"><p>' + data.error + '</p></div>' : '<div class=\"msgblue\"><p>' + data.message + '</p></div>';$('#feed_" .
                                ($row['NfID'] ?? '') . "').replaceWith( msg );}})";
                    $cnt += 1;
                    $feeds[$row['NfID']] = $row['NfName'];
                }
            }
        }
        mysqli_free_result($result);
    } else {
        $sql = "SELECT * FROM " . $tbpref . "newsfeeds WHERE NfID in ($currentfeed)";
        $result = do_mysqli_query($sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $ajax[$cnt] =  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" .
                ($row['NfID'] ?? '') . "').replaceWith( '<div id=\"feed_" . ($row['NfID'] ?? '') . "\" class=\"msgblue\"><p>" .
                addslashes((string) $row['NfName']) . ": loading</p></div>' );},url:'api.php/v1/feeds/" .
                    ($row['NfID'] ?? '') . "/load', data: { name: '" .
                    addslashes((string) $row['NfName']) . "', source_uri: '" . ($row['NfSourceURI'] ?? '') .
                    "', options: '" . ($row['NfOptions'] ?? '') .
                    "' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);var msg = data.error ? '<div class=\"red\"><p>' + data.error + '</p></div>' : '<div class=\"msgblue\"><p>' + data.message + '</p></div>';$('#feed_" .
                        ($row['NfID'] ?? '') . "').replaceWith( msg );}})";
            $cnt += 1;
            $feeds[$row['NfID']] = $row['NfName'];
        }
        mysqli_free_result($result);
    }
    if (!empty($ajax)) {
        $z = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $z[] = 'a' . $i;
        }
        echo "feedcnt=0;\n";
        echo '$(document).ready(function(){ $.when(',implode(',', $ajax),").then(function(",implode(',', $z),"){window.location.replace(\"",($_SERVER['PHP_SELF'] ?? '/'),"\");});});";
    } else {
        echo "window.location.replace(\"",($_SERVER['PHP_SELF'] ?? '/'),"\");";
    }
    echo "\n</script>\n";
    if ($cnt != 1) {
        echo "<div class=\"msgblue\"><p>UPDATING <span id=\"feedcount\">0</span>/",$cnt," FEEDS</p></div>";
    }
    foreach ($feeds as $k => $v) {
        echo "<div id='feed_$k' class=\"msgblue\"><p>" . ($v ?? 'Feed') . ": waiting</p></div>";
    }
    echo "<div class=\"center\"><button onclick='window.location.replace(\"",($_SERVER['PHP_SELF'] ?? '/'),"\");'>Continue</button></div>";
}

// -------------------------------------------------------------


function write_rss_to_db($texts): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $texts = array_reverse($texts);
    $message1 = $message2 = $message3 = $message4 = 0;
    $Nf_ID = null;
    foreach ($texts as $text) {
        $Nf_ID[] = $text['Nf_ID'];
    }
    $Nf_ID = array_unique($Nf_ID);
    $Nf_tag = '';
    $text_item = null;
    $nf_max_texts = null;
    foreach ($Nf_ID as $feed_ID) {
        foreach ($texts as $text) {
            if ($feed_ID == $text['Nf_ID']) {
                if ($Nf_tag != '"' . implode('","', $text['TagList']) . '"') {
                    $Nf_tag = '"' . implode('","', $text['TagList']) . '"';
                    foreach ($text['TagList'] as $tag) {
                        if (! in_array($tag, $_SESSION['TEXTTAGS'])) {
                            do_mysqli_query(
                                'insert into ' . $tbpref . 'tags2 (T2Text)
                                values (' . convert_string_to_sqlsyntax($tag) . ')'
                            );
                        }
                    }
                    $nf_max_texts = $text['Nf_Max_Texts'];
                }
                echo '<div class="msgblue"><p class="hide_message">+++ "' .
                $text['TxTitle'] . '" added! +++</p></div>';
                do_mysqli_query(
                    'INSERT INTO ' . $tbpref . 'texts (
                        TxLgID,TxTitle,TxText,TxAudioURI,TxSourceURI
                    ) VALUES (
                        ' . $text['TxLgID'] . ',' .
                        convert_string_to_sqlsyntax($text['TxTitle']) . ',' .
                        convert_string_to_sqlsyntax($text['TxText']) . ',' .
                        convert_string_to_sqlsyntax($text['TxAudioURI']) . ',' .
                        convert_string_to_sqlsyntax($text['TxSourceURI']) . ')'
                );
                $id = get_last_key();
                splitCheckText(
                    get_first_value(
                        'select TxText as value from ' . $tbpref . 'texts
                        where TxID = ' . $id
                    ),
                    get_first_value(
                        'select TxLgID as value from ' . $tbpref . 'texts
                        where TxID = ' . $id
                    ),
                    $id
                );
                do_mysqli_query(
                    'insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID)
                    select ' . $id . ', T2ID from ' . $tbpref . 'tags2
                    where T2Text in (' . $Nf_tag . ')'
                );
            }
        }
        get_texttags(1);
        $result = do_mysqli_query(
            "SELECT TtTxID FROM " . $tbpref . "texttags
            join " . $tbpref . "tags2 on TtT2ID=T2ID
            WHERE T2Text in (" . $Nf_tag . ")"
        );
        $text_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $text_item[$text_count++] = $row['TtTxID'];
        }
        mysqli_free_result($result);
        if ($text_count > $nf_max_texts) {
            sort($text_item, SORT_NUMERIC);
            $text_item = array_slice($text_item, 0, $text_count - $nf_max_texts);
            foreach ($text_item as $text_ID) {
                $message3 += (int) runsql(
                    'delete from ' . $tbpref . 'textitems2
                    where Ti2TxID = ' . $text_ID,
                    ""
                );
                $message2 += (int) runsql(
                    'delete from ' . $tbpref . 'sentences
                    where SeTxID = ' . $text_ID,
                    ""
                );
                $message4 += (int) runsql(
                    'insert into ' . $tbpref . 'archivedtexts (
                        AtLgID, AtTitle, AtText, AtAnnotatedText,
                        AtAudioURI, AtSourceURI
                    ) select TxLgID, TxTitle, TxText, TxAnnotatedText,
                    TxAudioURI, TxSourceURI
                    from ' . $tbpref . 'texts
                    where TxID = ' . $text_ID,
                    ""
                );
                $id = get_last_key();
                runsql(
                    'insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID)
                    select ' . $id . ', TtT2ID from ' . $tbpref . 'texttags
                    where TtTxID = ' . $text_ID,
                    ""
                );
                $message1 += (int) runsql(
                    'delete from ' . $tbpref . 'texts
                    where TxID = ' . $text_ID,
                    ""
                );
                // $message .= $message4 . " / " . $message1 . " / " . $message2 . " / " . $message3;
                adjust_autoincr('texts', 'TxID');
                adjust_autoincr('sentences', 'SeID');
                runsql(
                    "DELETE " . $tbpref . "texttags
                    FROM ("
                        . $tbpref . "texttags
                        LEFT JOIN " . $tbpref . "texts on TtTxID = TxID
                    )
                    WHERE TxID IS NULL",
                    ''
                );
            }
        }
    }
    if ($message4 > 0 || $message1 > 0) {
        return "Texts archived: " . $message1 .
        " / Sentences deleted: " . $message2 . " / Text items deleted: " . $message3;
    } else {
        return '';
    }
}

// -------------------------------------------------------------

function print_last_feed_update($diff): void
{
    $periods = array(
    array(60 * 60 * 24 * 365 , 'year'),
    array(60 * 60 * 24 * 30 , 'month'),
    array(60 * 60 * 24 * 7, 'week'),
    array(60 * 60 * 24 , 'day'),
    array(60 * 60 , 'hour'),
    array(60 , 'minute'),
    array(1 , 'second'),
    );
    if ($diff >= 1) {
        for ($key = 0; $key < 7; $key++) {
            $x = intval($diff / $periods[$key][0]);
            if ($x >= 1) {
                echo " last update: $x ";
                print_r($periods[$key][1]);
                if ($x > 1) {
                    echo 's';
                }echo ' ago';
                break;
            }
        }
    } else {
        echo ' up to date';
    }
}

/**
 * @return null|string|string[]
 *
 * @psalm-return array<string, string>|null|string
 */
function get_nf_option($str, $option)
{
    $arr = explode(',', $str);
    $all = null;
    if ($option == 'all') {
        $all = array();
    }
    foreach ($arr as $value) {
        $res = explode('=', $value);
        if (trim($res[0]) == $option) {
            return $res[1] ?? null;
        }
        if ($option == 'all') {
            $all[$res[0]] = $res[1] ?? '';
        }
    }
    if ($option == 'all') {
        return $all;
    }
    return null;
}


/**
 * @return ((false|null|string)[]|null|string)[]|false
 *
 * @psalm-return array{feed_title: null|string,...}|false
 */
function get_links_from_new_feed($NfSourceURI): array|false
{
    $rss = new DOMDocument('1.0', 'utf-8');
    if (!$rss->load($NfSourceURI, LIBXML_NOCDATA | ENT_NOQUOTES)) {
        return false;
    }
    $rss_data = array();
    $desc_count = 0;
    $desc_nocount = 0;
    $enc_count = 0;
    $enc_nocount = 0;
    if ($rss->getElementsByTagName('rss')->length !== 0) {
        $feed_tags = array(
            'item' => 'item',
            'title' => 'title',
            'description' => 'description',
            'link' => 'link'
        );
    } elseif ($rss->getElementsByTagName('feed')->length !== 0) {
        $feed_tags = array(
            'item' => 'entry',
            'title' => 'title',
            'description' => 'summary',
            'link' => 'link'
        );
    } else {
        return false;
    }
    foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
        $item = array (
            'title' => preg_replace(
                array('/\s\s+/','/\ \&\ /','/\"/'),
                array(' ',' &amp; ','\"'),
                trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)
            ),
            'desc' => preg_replace(
                array('/\s\s+/','/\ \&\ /','/\<[^\>]*\>/','/\"/'),
                array(' ',' &amp; ','','\"'),
                trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)
            ),
            'link' => trim(
                ($feed_tags['item'] == 'entry') ?
                ($node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')) :
                ($node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)
            ),
        );
        if ($feed_tags['item'] == 'item') {
            foreach ($node->getElementsByTagName('encoded') as $txt_node) {
                if ($txt_node->parentNode === $node) {
                    $item['encoded'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['encoded'] = mb_convert_encoding(
                        html_entity_decode($item['encoded'], ENT_NOQUOTES, "UTF-8"),
                        "HTML-ENTITIES",
                        "UTF-8"
                    );
                }
            }
            foreach ($node->getElementsByTagName('description') as $txt_node) {
                if ($txt_node->parentNode === $node) {
                    $item['description'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['description'] = mb_convert_encoding(
                        html_entity_decode($item['description'], ENT_NOQUOTES, "UTF-8"),
                        "HTML-ENTITIES",
                        "UTF-8"
                    );
                }
            }
            if (isset($item['desc'])) {
                if (mb_strlen($item['desc'], "UTF-8") > 900) {
                    $desc_count++;
                } else {
                    $desc_nocount++;
                }
            }
            if (isset($item['encoded'])) {
                if (mb_strlen($item['encoded'], "UTF-8") > 900) {
                    $enc_count++;
                } else {
                    $enc_nocount++;
                }
            }
        }
        if ($feed_tags['item'] == 'entry') {
            foreach ($node->getElementsByTagName('content') as $txt_node) {
                if ($txt_node->parentNode === $node) {
                    $item['content'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['content'] = mb_convert_encoding(
                        html_entity_decode($item['content'], ENT_NOQUOTES, "UTF-8"),
                        "HTML-ENTITIES",
                        "UTF-8"
                    );
                }
            }
            if (isset($item['content'])) {
                if (mb_strlen($item['content'], "UTF-8") > 900) {
                    $desc_count++;
                } else {
                    $desc_nocount++;
                }
            }
        }
        if ($item['title'] != "" && $item['link'] != "") {
            array_push($rss_data, $item);
        }
    }
    if ($desc_count > $desc_nocount) {
        $source = ($feed_tags['item'] == 'entry') ? 'content' : 'description';
        $rss_data['feed_text'] = $source;
        foreach ($rss_data as $i => $val) {
            if (is_array($val)) {
                $rss_data[$i]['text'] = $val[$source] ?? '';
            }
        }
    } elseif ($enc_count > $enc_nocount) {
        $rss_data['feed_text'] = 'encoded';
        foreach ($rss_data as $i => $val) {
            if (is_array($val)) {
                $rss_data[$i]['text'] = $val['encoded'] ?? '';
            }
        }
    } else {
        $rss_data['feed_text'] = '';
    }
    $rss_data['feed_title'] = $rss->getElementsByTagName('title')->item(0)->nodeValue;
    return $rss_data;
}

// -------------------------------------------------------------

/**
 * @return (false|null|string)[][]|false
 *
 * @psalm-return false|list<array{audio: string, date: string, desc: null|string, link: string, text?: false|string, title: non-empty-string|null}>
 */
function get_links_from_rss($NfSourceURI, $NfArticleSection): array|false
{
    $rss = new DOMDocument('1.0', 'utf-8');
    if (!$rss->load($NfSourceURI, LIBXML_NOCDATA | ENT_NOQUOTES)) {
        return false;
    }
    $rss_data = array();
    if ($rss->getElementsByTagName('rss')->length !== 0) {
        $feed_tags = array(
            'item' => 'item','title' => 'title','description' => 'description',
            'link' => 'link','pubDate' => 'pubDate','enclosure' => 'enclosure',
            'url' => 'url'
        );
    } elseif ($rss->getElementsByTagName('feed')->length !== 0) {
        $feed_tags = array(
            'item' => 'entry','title' => 'title','description' => 'summary',
            'link' => 'link','pubDate' => 'published','enclosure' => 'link',
            'url' => 'href'
        );
    } else {
        return false;
    }
    foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
        $item = array (
        'title' => preg_replace(
            array('/\s\s+/','/\ \&\ /'),
            array(' ',' &amp; '),
            trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)
        ),
        'desc' => isset($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue) ?
        preg_replace(
            array('/\ \&\ /','/<br(\s+)?\/?>/i','/<br [^>]*?>/i','/\<[^\>]*\>/','/(\n)[\s^\n]*\n[\s]*/'),
            array(' &amp; ',"\n","\n",'','$1$1'),
            trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)
        ) : '',
        'link' => trim(
            ($feed_tags['item'] == 'entry') ? (
            $node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')
            ) : (
                $node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)
        ),
        'date' => isset($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue) ?
        trim($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue)
        : null,
        );
        $pubDate = date_parse_from_format('D, d M Y H:i:s T', $item['date']);
        if ($pubDate['error_count'] > 0) {
            $item['date'] = date("Y-m-d H:i:s", time() - count($rss_data));
        } else {
            $item['date'] = date(
                "Y-m-d H:i:s",
                mktime(
                    $pubDate['hour'],
                    $pubDate['minute'],
                    $pubDate['second'],
                    $pubDate['month'],
                    $pubDate['day'],
                    $pubDate['year']
                )
            );
        }
        if (strlen($item['desc']) > 1000) {
            $item['desc'] = mb_substr($item['desc'], 0, 995, "utf-8") . '...';
        }
        if ($NfArticleSection) {
            foreach ($node->getElementsByTagName($NfArticleSection) as $txt_node) {
                if ($txt_node->parentNode === $node) {
                    $item['text'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['text'] = mb_convert_encoding(html_entity_decode($item['text'], ENT_NOQUOTES, "UTF-8"), "HTML-ENTITIES", "UTF-8");
                    //$item['text']=str_replace ('"','\"',$item['text']);///////////////
                }
            }
        }
        $item['audio'] = "";
        foreach ($node->getElementsByTagName($feed_tags['enclosure']) as $enc) {
            $type = $enc->getAttribute('type');
            if ($type == "audio/mpeg") {
                $item['audio'] = $enc->getAttribute($feed_tags['url']);
            }
        }
        if ($item['title'] != "" && ($item['link'] != "" || ($NfArticleSection != "" && !empty($item['text'])))) {
            array_push($rss_data, $item);
        }
    }
    return $rss_data;
}


/**
 * @return (array|mixed|null|string)[][]|null|string
 *
 * @psalm-return array<array{TxTitle: mixed, TxAudioURI: mixed|null, TxText: string, TxSourceURI: mixed|string, message?: string, link?: non-empty-list<mixed>}>|null|string
 */
function get_text_from_rsslink($feed_data, $NfArticleSection, $NfFilterTags, $NfCharset = null): array|string|null
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $data = null;
    foreach ($feed_data as $key => $val) {
        if (strncmp($NfArticleSection, 'redirect:', 9) == 0) {
            $dom = new DOMDocument();
            $HTMLString = file_get_contents(trim($feed_data[$key]['link']));
            $dom->loadHTML($HTMLString);
            $xPath = new DOMXPath($dom);
            $redirect = explode(" | ", $NfArticleSection, 2);
            $NfArticleSection = $redirect[1] ?? '';
            $redirect = substr($redirect[0], 9);
            $feed_host = parse_url(trim($feed_data[$key]['link']));
            foreach ($xPath->query($redirect) as $node) {
                if (
                    empty(trim($node->localName))
                    || $node->nodeType == XML_TEXT_NODE
                    || !$node->hasAttributes()
                ) {
                    continue;
                }
                /*
                May be better but yet untested*/
                /**
                 * @psalm-suppress NullIterator
                 */
                foreach ($node->attributes as $attr) {
                    if ($attr->name == 'href') {
                        $feed_data[$key]['link'] = $attr->value;
                        if (strncmp($feed_data[$key]['link'], '..', 2) == 0) {
                            $feed_data[$key]['link'] = 'http://' . ($feed_host['host'] ?? 'localhost') .
                            substr($feed_data[$key]['link'], 2);
                        }
                    }
                }
                /*
                $len = $node->attributes->length;
                for ($i=0; $i<$len; $i++){
                    if ($node->attributes->item($i)->name=='href') {
                        $feed_data[$key]['link'] = $node->attributes->item($i)->value;
                        if (strncmp($feed_data[$key]['link'], '..', 2)==0) {
                            $feed_data[$key]['link'] = 'http://'.$feed_host['host'] . substr($feed_data[$key]['link'], 2);
                        }
                    }
                } */
            }
            unset($dom);
            unset($HTMLString);
            unset($xPath);
        }
        $data[$key]['TxTitle'] = $feed_data[$key]['title'];
        $data[$key]['TxAudioURI'] = isset($feed_data[$key]['audio']) ? $feed_data[$key]['audio'] : null;
        $data[$key]['TxText'] = "";
        if (isset($feed_data[$key]['text'])) {
            if ($feed_data[$key]['text'] == "") {
                unset($feed_data[$key]['text']);
            }
        }
        if (isset($feed_data[$key]['text'])) {
            $link = trim($feed_data[$key]['link']);
            if (substr($link, 0, 1) == '#') {
                runsql(
                    'UPDATE ' . $tbpref . 'feedlinks
                    SET FlLink=' . convert_string_to_sqlsyntax($link) . '
                    where FlID = ' . substr($link, 1),
                    ""
                );
            }
            $data[$key]['TxSourceURI'] = $link;
            $HTMLString = str_replace(
                array('>','<'),
                array('> ',' <'),
                $feed_data[$key]['text']
            );//$HTMLString=str_replace (array('>','<'),array('> ',' <'),$HTMLString);
        } else {
            $data[$key]['TxSourceURI'] = $feed_data[$key]['link'];
            $context = stream_context_create(array('http' => array('follow_location' => true )));
            $HTMLString = @file_get_contents(trim($data[$key]['TxSourceURI']), false, $context);
            if (!empty($HTMLString)) {
                $encod  = '';
                if (empty($NfCharset)) {
                    $header = get_headers(trim($data[$key]['TxSourceURI']), true);
                    foreach ($header as $k => $v) {
                        if (strtolower($k) == 'content-type') {
                            if (is_array($v)) {
                                $encod = $v[count($v) - 1];
                            } else {
                                $encod = $v;
                            }
                            $pos = strpos($encod, 'charset=');
                            if (($pos !== false) && (strpos($encod, 'text/html;') !== false)) {
                                $encod = substr($encod, $pos + 8);
                                break;
                            } else {
                                $encod = '';
                            }
                        }
                    }
                } else {
                    if ($NfCharset != 'meta') {
                        $encod  = $NfCharset;
                    }
                }

                if (empty($encod)) {
                    $doc = new DOMDocument();
                    $previous_value = libxml_use_internal_errors(true);
                    $doc->loadHTML($HTMLString);
                    /*
                    if (!$doc->loadHTML($HTMLString)) {
                    foreach (libxml_get_errors() as $error) {
                    // handle errors here
                    }*/
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous_value);
                    $nodes = $doc->getElementsByTagName('meta');
                    foreach ($nodes as $node) {
                        $len = $node->attributes->length;
                        for ($i = 0; $i < $len; $i++) {
                            if ($node->attributes->item($i)->name == 'content') {
                                $pos = strpos($node->attributes->item($i)->value, 'charset=');
                                if ($pos) {
                                    $encod = substr($node->attributes->item($i)->value, $pos + 8);
                                    unset($doc);
                                    unset($nodes);
                                    break 2;
                                }
                            }
                        }
                    }
                    if (empty($encod)) {
                        foreach ($nodes as $node) {
                            $len = $node->attributes->length;
                            if ($len == '1') {
                                if ($node->attributes->item(0)->name == 'charset') {
                                    $encod = $node->attributes->item(0)->value;
                                    break;
                                }
                            }
                        }
                    }
                }
                unset($doc);
                unset($nodes);
                if (empty($encod)) {
                    mb_detect_order("ASCII,UTF-8,ISO-8859-1,windows-1252,iso-8859-15");
                    $encod  = mb_detect_encoding($HTMLString);
                }
                $chset = $encod;
                switch ($encod) {
                    case 'windows-1253':
                        $chset = 'el_GR.utf8';
                        break;
                    case 'windows-1254':
                        $chset = 'tr_TR.utf8';
                        break;
                    case 'windows-1255':
                        $chset = 'he.utf8';
                        break;
                    case 'windows-1256':
                        $chset = 'ar_AE.utf8';
                        break;
                    case 'windows-1258':
                        $chset = 'vi_VI.utf8';
                        break;
                    case 'windows-874':
                        $chset = 'th_TH.utf8';
                        break;
                }
                $HTMLString = '<meta http-equiv="Content-Type" content="text/html; charset=' . $chset . '">' . $HTMLString;
                if ($encod != $chset) {
                    $HTMLString = iconv($encod, 'utf-8', $HTMLString);
                } else {
                    $HTMLString = mb_convert_encoding($HTMLString, 'HTML-ENTITIES', $encod);
                }
            }
        }
        $HTMLString = str_replace(array('<br />','<br>','</br>','</h','</p'), array("\n","\n","","\n</h","\n</p"), $HTMLString);
        $dom = new DOMDocument();
        $previous_value = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8">' . $HTMLString);
        foreach ($dom->childNodes as $item) {/////////////////////////////////
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item); // remove hack
            }
        }
        $dom->encoding = 'UTF-8'; // insert proper    //////////////////////////////

        /*
        if (!$dom->loadHTML($HTMLString)) {
        foreach (libxml_get_errors() as $error) {
        // handle errors here
        }*/
        libxml_clear_errors();
        libxml_use_internal_errors($previous_value);
        $filter_tags = explode("!?!", rtrim("//img | //script | //meta | //noscript | //link | //iframe!?!" . $NfFilterTags, "!?!"));
        foreach (explode("!?!", $NfArticleSection) as $article_tag) {
            if ($article_tag == 'new') {
                foreach ($filter_tags as $filter_tag) {
                    $nodes = $dom->getElementsByTagName($filter_tag);
                    $domElemsToRemove = array();
                    foreach ($nodes as $domElement) {
                        $domElemsToRemove[] = $domElement;
                    }
                    foreach ($domElemsToRemove as $node) {
                        $node->parentNode->removeChild($node);
                    }
                }
                $nodes = $dom->getElementsByTagName('*');
                foreach ($nodes as $node) {
                    $node->removeAttribute('onclick');
                }
                $str = $dom->saveHTML($dom);
                //$str=mb_convert_encoding(html_entity_decode($str, ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
                return preg_replace(
                    array('/\<html[^\>]*\>/','/\<body\>/'),
                    array('',''),
                    $str
                );
            }
        }
        $selector = new DOMXPath($dom);
        foreach ($filter_tags as $filter_tag) {
            foreach ($selector->query($filter_tag) as $node) {
                $node->parentNode->removeChild($node);
            }
        }
        if (isset($feed_data[$key]['text'])) {
            foreach ($selector->query($NfArticleSection) as $text_temp) {
                if ($text_temp->nodeValue != '') {
                    $data[$key]['TxText'] .= mb_convert_encoding(
                        $text_temp->nodeValue,
                        "HTML-ENTITIES",
                        "UTF-8"
                    );
                }
            }
            $data[$key]['TxText'] = html_entity_decode($data[$key]['TxText'], ENT_NOQUOTES, "UTF-8");
        } else {
            $article_tags = explode("!?!", $NfArticleSection);
            if (strncmp($NfArticleSection, 'redirect:', 9) == 0) {
                unset($article_tags[0]);
            }
            foreach ($article_tags as $article_tag) {
                $queryResult = @$selector->query($article_tag);
                if ($queryResult !== false) {
                    foreach ($queryResult as $text_temp) {
                        if ($text_temp->nodeValue != '') {
                            $data[$key]['TxText'] .= $text_temp->nodeValue;
                        }
                    }
                }
            }
        }

        if ($data[$key]['TxText'] == "") {
            unset($data[$key]);
            if (!isset($data['error']['message'])) {
                $data['error']['message'] = '';
            }
            $data['error']['message'] .= '"<a href=' . $feed_data[$key]['link'] . ' onclick="window.open(this.href, \'child\'); return false">'  . $feed_data[$key]['title'] . '</a>" has no text section!<br />';
            $data['error']['link'][] = $feed_data[$key]['link'];
        } else {
            $data[$key]['TxText'] = trim(preg_replace(array('/[\r\t]+/','/(\n)[\s^\n]*\n[\s]*/','/\ \ +/'), array(' ','$1$1',' '), $data[$key]['TxText']));
            //$data[$key]['TxText']=trim(preg_replace(array('/[\s^\n]+/','/(\n)[\s^\n]*\n[\s]*/','/\ +/','/[ ]*(\n)/'), array(' ','$1$1',' ','$1'), $data[$key]['TxText']));
        }
    }
    return $data;
}
