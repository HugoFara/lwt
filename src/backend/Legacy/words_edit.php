<?php

/**
 * Manage terms
 *
 * Call: /words/edit?....
 *  ... markaction=[opcode] ... do actions on marked terms
 *  ... allaction=[opcode] ... do actions on all terms
 *  ... del=[wordid] ... do delete
 *  ... op=Save ... do insert new
 *  ... op=Change ... do update
 *  ... new=1&lang=[langid] ... display new term screen
 *  ... chg=[wordid] ... display edit screen
 *  ... filterlang=[langid] ... language filter
 *  ... sort=[sortcode] ... sort
 *  ... page=[pageno] ... page
 *  ... query=[termtextfilter] ... term text filter
 *  ... status=[statuscode] ... status filter
 *  ... text=[textid] ... text filter
 *  ... tag1=[tagid] ... tag filter 1
 *  ... tag2=[tagid] ... tag filter 2
 *  ... tag12=0/1 ... tag1-tag2 OR=0, AND=1
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 */

require_once 'Core/session_utility.php';
require_once 'Core/simterms.php';
require_once 'Core/start_session.php';

$currentlang = validateLang((string) processDBParam("filterlang", 'currentlanguage', '', false));
$currentsort = (int) processDBParam("sort", 'currentwordsort', '1', true);

$currentpage = (int) processSessParam("page", "currentwordpage", '1', true);
$currentquery = (string) processSessParam("query", "currentwordquery", '', false);
$currentquerymode = (string) processSessParam(
    "query_mode",
    "currentwordquerymode",
    'term,rom,transl',
    false
);
$currentregexmode = getSettingWithDefault("set-regex-mode");
$currentstatus = (string) processSessParam("status", "currentwordstatus", '', false);
$currenttext = validateText((string) processSessParam("text", "currentwordtext", '', false));
$currenttexttag = (string) processSessParam("texttag", "currentwordtexttag", '', false);
$currenttextmode = (string) processSessParam("text_mode", "currentwordtextmode", 0, false);
$currenttag1 = validateTag(
    (string) processSessParam("tag1", "currentwordtag1", '', false),
    $currentlang
);
$currenttag2 = validateTag(
    (string) processSessParam("tag2", "currentwordtag2", '', false),
    $currentlang
);
$currenttag12 = (string) processSessParam("tag12", "currentwordtag12", '', false);

$wh_lang = ($currentlang != '') ? (' and WoLgID=' . $currentlang ) : '';
$wh_stat = '';
if ($currentstatus != '') {
    $wh_stat = ' and ' . makeStatusCondition('WoStatus', (int)$currentstatus);
}
$wh_query = $currentregexmode . 'like ' .
convert_string_to_sqlsyntax(
    ($currentregexmode == '') ?
    (str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))) :
    $currentquery
);
switch ($currentquerymode) {
    case 'term,rom,transl':
        $wh_query = ' and (WoText ' . $wh_query . ' or IFNULL(WoRomanization,\'*\') ' .
        $wh_query . ' or WoTranslation ' . $wh_query . ')';
        break;
    case 'term,rom':
        $wh_query = ' and (WoText ' . $wh_query . ' or IFNULL(WoRomanization,\'*\') ' .
        $wh_query . ')';
        break;
    case 'rom,transl':
        $wh_query = ' and (IFNULL(WoRomanization,\'*\') ' . $wh_query .
        ' or WoTranslation ' . $wh_query . ')';
        break;
    case 'term,transl':
        $wh_query = ' and (WoText ' . $wh_query . ' or WoTranslation ' . $wh_query . ')';
        break;
    case 'term':
        $wh_query = ' and (WoText ' . $wh_query . ')';
        break;
    case 'rom':
        $wh_query = ' and (IFNULL(WoRomanization,\'*\') ' . $wh_query . ')';
        break;
    case 'transl':
        $wh_query = ' and (WoTranslation ' . $wh_query . ')';
        break;
}
if ($currentquery !== '') {
    if ($currentregexmode !== '') {
        if (
            @mysqli_query(
                $GLOBALS["DBCONNECTION"],
                'select "test" rlike ' . convert_string_to_sqlsyntax($currentquery)
            ) === false
        ) {
            $currentquery = '';
            $wh_query = '';
            unset($_SESSION['currentwordquery']);
            if (isset($_REQUEST['query'])) {
                echo '<p id="hide3" style="color:red;text-align:center;">' .
                '+++ Warning: Invalid Search +++</p>';
            }
        }
    }
} else {
    $wh_query = '';
}

$wh_tag1 = null;
$wh_tag2 = null;
if ($currenttag1 == '' && $currenttag2 == '') {
    $wh_tag = '';
} else {
    if ($currenttag1 != '') {
        if ($currenttag1 == -1) {
            $wh_tag1 = "group_concat(WtTgID) IS NULL";
        } else {
            $wh_tag1 = "concat('/',group_concat(WtTgID separator '/'),'/') like '%/" .
            $currenttag1 . "/%'";
        }
    }
    if ($currenttag2 != '') {
        if ($currenttag2 == -1) {
            $wh_tag2 = "group_concat(WtTgID) IS NULL";
        } else {
            $wh_tag2 = "concat('/',group_concat(WtTgID separator '/'),'/') like '%/" .
            $currenttag2 . "/%'";
        }
    }
    if ($currenttag1 != '' && $currenttag2 == '') {
        $wh_tag = " having (" . $wh_tag1 . ') ';
    } elseif ($currenttag2 != '' && $currenttag1 == '') {
        $wh_tag = " having (" . $wh_tag2 . ') ';
    } else {
        $wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') .
        $wh_tag2 . ')) ';
    }
}

$no_pagestart =
    (getreq('markaction') == 'exp') ||
    (getreq('markaction') == 'exp2') ||
    (getreq('markaction') == 'exp3') ||
    (getreq('markaction') == 'test') ||
    (getreq('markaction') == 'deltag') ||
    (getreq('allaction') == 'expall') ||
    (getreq('allaction') == 'expall2') ||
    (getreq('allaction') == 'expall3') ||
    (getreq('allaction') == 'testall') ||
    (getreq('allaction') == 'deltagall');

if (!$no_pagestart) {
    pagestart(
        'My ' . getLanguage($currentlang) . ' Terms (Words and Expressions)',
        true
    );
}

$message = '';


if (isset($_REQUEST['markaction'])) {
    // MARK ACTIONS
    $markaction = $_REQUEST['markaction'];
    $actiondata = getreq('data');
    $message = "Multiple Actions: 0";
    if (isset($_REQUEST['marked'])) {
        if (is_array($_REQUEST['marked'])) {
            $l = count($_REQUEST['marked']);
            if ($l > 0) {
                $id_list = array();
                for ($i = 0; $i < $l; $i++) {
                    $id_list[] = (string) $_REQUEST['marked'][$i];
                }
                $list = "(" . implode(",", $id_list) . ")";
                if ($markaction == 'del') {
                    $message = runsql(
                        'delete from ' . $tbpref . 'words where WoID in ' . $list,
                        "Deleted"
                    );
                    do_mysqli_query(
                        'update ' . $tbpref . 'textitems2
                        set Ti2WoID = 0
                        where Ti2WordCount = 1 and Ti2WoID in ' . $list
                    );
                    do_mysqli_query(
                        'delete from ' . $tbpref . 'textitems2
                        where Ti2WoID in ' . $list
                    );
                    adjust_autoincr('words', 'WoID');
                    runsql(
                        "DELETE " . $tbpref . "wordtags
                        FROM (
                            " . $tbpref . "wordtags
                            LEFT JOIN " . $tbpref . "words
                            on WtWoID = WoID
                        ) WHERE WoID IS NULL",
                        ''
                    );
                } elseif ($markaction == 'addtag') {
                    $message = addtaglist($actiondata, $list);
                } elseif ($markaction == 'deltag') {
                    removetaglist($actiondata, $list);
                    header("Location: /words/edit");
                    exit();
                } elseif ($markaction == 'spl1') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoStatus=WoStatus+1, WoStatusChanged = NOW(),' .
                        make_score_random_insert_update('u') . '
                        where WoStatus in (1,2,3,4) and WoID in ' . $list,
                        "Updated Status (+1)"
                    );
                } elseif ($markaction == 'smi1') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoStatus=WoStatus-1, WoStatusChanged = NOW(),' .
                        make_score_random_insert_update('u') . '
                        where WoStatus in (2,3,4,5) and WoID in ' . $list,
                        "Updated Status (-1)"
                    );
                } elseif ($markaction == 's5') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoStatus=5, WoStatusChanged = NOW(),' .
                        make_score_random_insert_update('u') . '
                        where WoID in ' . $list,
                        "Updated Status (=5)"
                    );
                } elseif ($markaction == 's1') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoStatus=1, WoStatusChanged = NOW(),' .
                        make_score_random_insert_update('u') . '
                        where WoID in ' . $list,
                        "Updated Status (=1)"
                    );
                } elseif ($markaction == 's99') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoStatus=99, WoStatusChanged = NOW(),' .
                        make_score_random_insert_update('u') . '
                        where WoID in ' . $list,
                        "Updated Status (=99)"
                    );
                } elseif ($markaction == 's98') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoStatus=98, WoStatusChanged = NOW(),' .
                        make_score_random_insert_update('u') . '
                        where WoID in ' . $list,
                        "Updated Status (=98)"
                    );
                } elseif ($markaction == 'today') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoStatusChanged = NOW(),' .
                        make_score_random_insert_update('u') . '
                        where WoID in ' . $list,
                        "Updated Status Date (= Now)"
                    );
                } elseif ($markaction == 'delsent') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoSentence = NULL
                        where WoID in ' . $list,
                        "Term Sentence(s) deleted"
                    );
                } elseif ($markaction == 'lower') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoText = WoTextLC
                        where WoID in ' . $list,
                        "Term(s) set to lowercase"
                    );
                } elseif ($markaction == 'cap') {
                    $message = runsql(
                        'update ' . $tbpref . 'words
                        set WoText = CONCAT(
                            UPPER(LEFT(WoTextLC,1)),SUBSTRING(WoTextLC,2)
                        )
                        where WoID in ' . $list,
                        "Term(s) capitalized"
                    );
                } elseif ($markaction == 'exp') {
                    anki_export(
                        'select distinct WoID, LgRightToLeft,
                        LgRegexpWordCharacters, LgName, WoText, WoTranslation,
                        WoRomanization, WoSentence,
                        ifnull(
                            group_concat(
                                distinct TgText
                                order by TgText separator \' \'
                            ),
                            \'\'
                        ) as taglist
                        from (
                            (
                                ' . $tbpref . 'words
                                left JOIN ' . $tbpref . 'wordtags
                                ON WoID = WtWoID
                            )
                            left join ' . $tbpref . 'tags
                            on TgID = WtTgID
                        ),
                        ' . $tbpref . 'languages
                        where WoLgID = LgID AND WoTranslation != \'\' AND
                        WoTranslation != \'*\' and
                        WoSentence like concat(\'%{\',WoText,\'}%\') and
                        WoID in ' . $list . '
                        group by WoID'
                    );
                } elseif ($markaction == 'exp2') {
                    tsv_export(
                        'select distinct WoID, LgName, WoText, WoTranslation,
                        WoRomanization, WoSentence, WoStatus,
                        ifnull(
                            group_concat(
                                distinct TgText order by TgText separator \' \'
                            ), \'\'
                        ) as taglist
                        from (
                            (
                                ' . $tbpref . 'words
                                left JOIN ' . $tbpref . 'wordtags
                                ON WoID = WtWoID
                            )
                            left join ' . $tbpref . 'tags on TgID = WtTgID
                        ), ' . $tbpref . 'languages
                        where WoLgID = LgID and WoID in ' . $list . '
                        group by WoID'
                    );
                } elseif ($markaction == 'exp3') {
                    flexible_export(
                        'select distinct WoID, LgName, LgExportTemplate,
                        LgRightToLeft, WoText, WoTextLC, WoTranslation,
                        WoRomanization, WoSentence, WoStatus,
                        ifnull(
                            group_concat(
                                distinct TgText order by TgText separator \' \'),\'\'
                            ) as taglist
                            from (
                                (
                                    ' . $tbpref . 'words
                                    left JOIN ' . $tbpref . 'wordtags
                                    ON WoID = WtWoID
                                )
                                left join ' . $tbpref . 'tags on TgID = WtTgID
                            ), ' . $tbpref . 'languages
                            where WoLgID = LgID and WoID in ' . $list . '
                            group by WoID'
                    );
                } elseif ($markaction == 'test') {
                    $_SESSION['testsql'] = $list;
                    header("Location: /test?selection=2");
                    exit();
                }
            }
        }
    }
}


if (isset($_REQUEST['allaction'])) {
    // ALL ACTIONS
    $allaction = (string) $_REQUEST['allaction'];
    $actiondata = getreq('data');
    if (
        $allaction == 'delall' || $allaction == 'spl1all' ||
        $allaction == 'smi1all' || $allaction == 's5all' || $allaction == 's1all' ||
        $allaction == 's99all' || $allaction == 's98all' ||
        $allaction == 'todayall' || $allaction == 'addtagall' ||
        $allaction == 'deltagall' || $allaction == 'delsentall' ||
        $allaction == 'lowerall' || $allaction == 'capall'
    ) {
        if ($currenttext == '') {
            $sql = 'select distinct WoID from (
                ' . $tbpref . 'words
                left JOIN ' . $tbpref . 'wordtags
                ON WoID = WtWoID
            ) where (1=1) ' . $wh_lang . $wh_stat .  $wh_query . '
            group by WoID ' . $wh_tag;
        } else {
            $sql = 'select distinct WoID
            from (
                ' . $tbpref . 'words
                left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID
            ), ' . $tbpref . 'textitems2
            where Ti2LgID = WoLgID and Ti2WoID = WoID and
            Ti2TxID in (' . $currenttext . ')' . $wh_lang . $wh_stat . $wh_query .
            ' group by WoID ' . $wh_tag;
        }
        $cnt = 0;
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $id = $record['WoID'];
            if ($allaction == 'delall') {
                $message = runsql(
                    'delete from ' . $tbpref . 'words
                    where WoID = ' . $id,
                    ""
                );
                do_mysqli_query(
                    'update ' . $tbpref . 'textitems2
                    set Ti2WoID = 0
                    where Ti2WordCount = 1 and Ti2WoID = ' . $id
                );
                do_mysqli_query(
                    'delete from ' . $tbpref . 'textitems2
                    where Ti2WoID  = ' . $id
                );
            } elseif ($allaction == 'addtagall') {
                addtaglist($actiondata, '(' . $id . ')');
                $message = 1;
            } elseif ($allaction == 'deltagall') {
                removetaglist($actiondata, '(' . $id . ')');
                $message = 1;
            } elseif ($allaction == 'spl1all') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoStatus=WoStatus+1, WoStatusChanged = NOW(),' .
                    make_score_random_insert_update('u') . '
                    where WoStatus in (1,2,3,4) and WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 'smi1all') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoStatus=WoStatus-1, WoStatusChanged = NOW(),' .
                    make_score_random_insert_update('u') . '
                    where WoStatus in (2,3,4,5) and WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 's5all') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoStatus=5, WoStatusChanged = NOW(),' .
                    make_score_random_insert_update('u') . ' where WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 's1all') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoStatus=1, WoStatusChanged = NOW(),' .
                    make_score_random_insert_update('u') . ' where WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 's99all') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoStatus=99, WoStatusChanged = NOW(),' .
                    make_score_random_insert_update('u') . ' where WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 's98all') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoStatus=98, WoStatusChanged = NOW(),' .
                    make_score_random_insert_update('u') . ' where WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 'todayall') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoStatusChanged = NOW(),' .
                    make_score_random_insert_update('u') . ' where WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 'delsentall') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoSentence = NULL where WoID = ' . $id,
                    ""
                );
            } elseif ($allaction == 'lowerall') {
                $message = runsql(
                    'update ' . $tbpref . 'words
                set WoText = WoTextLC where WoID = ' . $id,
                    ""
                );
            } else {
                // $allaction == 'capall' by default
                $message = runsql(
                    'update ' . $tbpref . 'words
                    set WoText = CONCAT(UPPER(LEFT(WoTextLC,1)),SUBSTRING(WoTextLC,2))
                    where WoID = ' . $id,
                    ""
                );
            }
            $cnt += (int)$message;
        }
        mysqli_free_result($res);
        if ($allaction == 'deltagall') {
            header("Location: /words/edit");
            exit();
        }
        if ($allaction == 'addtagall') {
            $message = "Tag added in $cnt Terms";
        } elseif ($allaction == 'delall') {
            $message = "Deleted: $cnt Terms";
            adjust_autoincr('words', 'WoID');
            runsql(
                "DELETE " . $tbpref . "wordtags FROM (
                    " . $tbpref . "wordtags
                    LEFT JOIN " . $tbpref . "words
                    on WtWoID = WoID
                ) WHERE WoID IS NULL",
                ''
            );
        } else {
            $message = "$cnt Terms changed";
        }
    } elseif ($allaction == 'expall') {
        if ($currenttext == '') {
            anki_export(
                'select distinct WoID, LgRightToLeft, LgRegexpWordCharacters,
                LgName, WoText, WoTranslation, WoRomanization, WoSentence,
                ifnull(
                    group_concat(distinct TgText order by TgText separator \' \'),
                    \'\') as taglist
                from (
                    (
                        ' . $tbpref . 'words
                        left JOIN ' . $tbpref . 'wordtags
                        ON WoID = WtWoID
                    )
                    left join ' . $tbpref . 'tags
                    on TgID = WtTgID
                ), ' . $tbpref . 'languages
                where WoLgID = LgID AND WoTranslation != \'*\' and
                WoSentence like concat(\'%{\',WoText,\'}%\') ' . $wh_lang .
                $wh_stat . $wh_query . '
                group by WoID ' . $wh_tag
            );
        } else {
            anki_export(
                'select distinct WoID, LgRightToLeft, LgRegexpWordCharacters,
                LgName, WoText, WoTranslation, WoRomanization, WoSentence,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist
                from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID) left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages, ' . $tbpref . 'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' . $currenttext . ')' . ' and WoLgID = LgID AND WoTranslation != \'*\' and WoSentence like concat(\'%{\',WoText,\'}%\') ' . $wh_lang . $wh_stat . $wh_query . ' group by WoID ' . $wh_tag
            );
        }
    } elseif ($allaction == 'expall2') {
        if ($currenttext == '') {
            tsv_export(
                'select distinct WoID, LgName, WoText, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID) left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages where WoLgID = LgID ' . $wh_lang . $wh_stat .  $wh_query . ' group by WoID ' . $wh_tag
            );
        } else {
            tsv_export(
                'select distinct WoID, LgName, WoText, WoTranslation,
                WoRomanization, WoSentence, WoStatus,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID) left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages, ' . $tbpref . 'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' . $currenttext . ')' . ' and WoLgID = LgID ' . $wh_lang . $wh_stat . $wh_query . ' group by WoID ' . $wh_tag
            );
        }
    } elseif ($allaction == 'expall3') {
        if ($currenttext == '') {
            flexible_export(
                'select distinct WoID, LgName, LgExportTemplate, LgRightToLeft,
                 WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence,
                 WoStatus,
                 ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID) left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages where WoLgID = LgID ' . $wh_lang . $wh_stat .  $wh_query . ' group by WoID ' . $wh_tag
            );
        } else {
            flexible_export(
                'select distinct WoID, LgName, LgExportTemplate, LgRightToLeft,
                WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence,
                WoStatus,
                ifnull(group_concat(distinct TgText order by TgText separator \' \'),\'\') as taglist from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID) left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages, ' . $tbpref . 'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' . $currenttext . ')' . ' and WoLgID = LgID ' . $wh_lang . $wh_stat . $wh_query . ' group by WoID ' . $wh_tag
            );
        }
    } elseif ($allaction == 'testall') {
        if ($currenttext == '') {
            $sql = 'select distinct WoID
            from (' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID)
            where (1=1) ' . $wh_lang . $wh_stat .  $wh_query . ' group by WoID ' . $wh_tag;
        } else {
            $sql = 'select distinct WoID
            from (' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID), ' . $tbpref . 'textitems2
            where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' . $currenttext . ')' . $wh_lang . $wh_stat . $wh_query . '
            group by WoID ' . $wh_tag;
        }
        $id_list = array();
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $id_list[] = $record['WoID'];
        }
        mysqli_free_result($res);
        $_SESSION['testsql'] = "(" . implode(",", $id_list) . ")";
        header("Location: /test?selection=2");
        exit();
    }
} elseif (isset($_REQUEST['del'])) {
    // DEL
    $message = runsql('delete from ' . $tbpref . 'words where WoID = ' . $_REQUEST['del'], "Deleted");
    adjust_autoincr('words', 'WoID');
    do_mysqli_query('update ' . $tbpref . 'textitems2 set Ti2WoID = 0 where Ti2WordCount = 1 and Ti2WoID = ' . $_REQUEST['del']);
    do_mysqli_query('delete from ' . $tbpref . 'textitems2 where Ti2WoID  = ' . $_REQUEST['del']);
    runsql("DELETE " . $tbpref . "wordtags FROM (" . $tbpref . "wordtags LEFT JOIN " . $tbpref . "words on WtWoID = WoID) WHERE WoID IS NULL", '');
} elseif (isset($_REQUEST['op'])) {
    // INS/UPD
    $translation_raw = repl_tab_nl(getreq("WoTranslation"));
    if ($translation_raw == '') {
        $translation = '*';
    } else {
        $translation = $translation_raw;
    }

    if ($_REQUEST['op'] == 'Save') {
        // INSERT
        $message = runsql(
            'insert into ' . $tbpref . 'words (WoLgID, WoTextLC, WoText, ' .
            'WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged,' .
            make_score_random_insert_update('iv') . ') values( ' .
            $_REQUEST["WoLgID"] . ', ' .
            convert_string_to_sqlsyntax(mb_strtolower($_REQUEST["WoText"], 'UTF-8')) . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoText"]) . ', ' .
            $_REQUEST["WoStatus"] . ', ' .
            convert_string_to_sqlsyntax($translation) . ', ' .
            convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', ' .
            convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . ', NOW(), ' .
            make_score_random_insert_update('id') . ')',
            "Saved",
            $sqlerrdie = false
        );

        $wid = get_last_key();
        init_word_count();
        $len = get_first_value(
            'select WoWordCount as value
            from ' . $tbpref . 'words where WoID = ' . $wid
        );
        $textlc = mb_strtolower($_REQUEST["WoText"], 'UTF-8');
        if ($len > 1) {
            insertExpressions($textlc, $_REQUEST["WoLgID"], $wid, $len, 1);
        } else {
            do_mysqli_query(
                'UPDATE ' . $tbpref . 'textitems2
                SET Ti2WoID = ' . $wid . '
                WHERE Ti2LgID = ' . $_REQUEST["WoLgID"] . ' AND LOWER(Ti2Text) = ' .
                convert_string_to_sqlsyntax_notrim_nonull($textlc)
            );
        }
    } else {
        // UPDATE
        $oldstatus = $_REQUEST["WoOldStatus"];
        $newstatus = $_REQUEST["WoStatus"];
        $xx = '';
        if ($oldstatus != $newstatus) {
            $xx = ', WoStatus = ' .    $newstatus . ', WoStatusChanged = NOW()';
        }
        $wid = (int)$_REQUEST["WoID"];
        $message = runsql(
            'update ' . $tbpref . 'words set WoText = ' .
            convert_string_to_sqlsyntax($_REQUEST["WoText"]) . ', WoTextLC = ' .
            convert_string_to_sqlsyntax(mb_strtolower($_REQUEST["WoText"], 'UTF-8')) .
            ', WoTranslation = ' .
            convert_string_to_sqlsyntax($translation) . ', WoSentence = ' .
            convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) .
            ', WoRomanization = ' .
            convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . $xx . ',' .
            make_score_random_insert_update('u') .
            ' where WoID = ' . $_REQUEST["WoID"],
            "Updated",
            $sqlerrdie = false
        );
    }

    saveWordTags($wid);
}


if (isset($_REQUEST['new']) && isset($_REQUEST['lang'])) {
    // NEW
    $lgid = (int) $_REQUEST['lang'];
    $scrdir = getScriptDirectionTag($lgid);
    $showRoman = (bool) get_first_value(
        "SELECT LgShowRomanization AS value
        FROM {$tbpref}languages
        WHERE LgID = $lgid"
    );

    ?>

    <h2>New Term</h2>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(lwtFormCheck.askBeforeExit);
    </script>
    <form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $_REQUEST['lang']; ?>" />
    <table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
    <td class="td1 right">Language:</td>
    <td class="td1"><?php echo tohtml(getLanguage($_REQUEST['lang'])); ?></td>
    </tr>
    <tr>
    <td class="td1 right">Term:</td>
    <td class="td1">
        <input <?php echo $scrdir; ?> class="notempty setfocus checkoutsidebmp"
        data_info="Term" type="text" name="WoText" id="wordfield" value="" maxlength="250" />
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
    </tr>
    <?php print_similar_terms_tabrow(); ?>
    <tr>
    <td class="td1 right">Translation:</td>
    <td class="td1">
        <textarea class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" name="WoTranslation" cols="40" rows="3"></textarea></td>
    </tr>
    <tr>
    <td class="td1 right">Tags:</td>
    <td class="td1">
    <?php echo getWordTags(0); ?>
    </td>
    </tr>
    <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
    <td class="td1 right">Romaniz.:</td>
    <td class="td1"><input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="" maxlength="100" size="40" /></td>
    </tr>
    <tr>
    <td class="td1 right">Sentence<br />Term in {...}:</td>
    <td class="td1"><textarea <?php echo $scrdir; ?> name="WoSentence" cols="40" rows="3" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence"></textarea></td>
    </tr>
    <tr>
    <td class="td1 right">Status:</td>
    <td class="td1">
    <?php echo get_wordstatus_radiooptions(1); ?>
    </td>
    </tr>
    <tr>
    <td class="td1 right" colspan="2">  &nbsp;
    <?php echo createDictLinksInEditWin2(
        (int) $_REQUEST['lang'],
        'document.forms[\'newword\'].WoSentence',
        'document.forms[\'newword\'].WoText'
    ); ?>
        &nbsp; &nbsp;
    <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/words/edit';}" />
    <input type="submit" name="op" value="Save" /></td>
    </tr>
    </table>
    </form>

    <?php
} elseif (isset($_REQUEST['chg'])) {
    // CHG
    $sql = 'select * from ' . $tbpref . 'words, ' . $tbpref . 'languages
    where LgID = WoLgID and WoID = ' . $_REQUEST['chg'];
    $res = do_mysqli_query($sql);
    if ($record = mysqli_fetch_assoc($res)) {
        $wordlc = $record['WoTextLC'];
        $transl = repl_tab_nl($record['WoTranslation']);
        if ($transl == '*') {
            $transl = '';
        }
        $scrdir = ($record['LgRightToLeft'] ? ' dir="rtl" ' : '');
        $showRoman = $record['LgShowRomanization'];

        ?>

     <h2>Edit Term</h2>
     <script type="text/javascript" charset="utf-8">
         $(document).ready(lwtFormCheck.askBeforeExit);
     </script>
     <form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $_REQUEST['chg']; ?>" method="post">
     <input type="hidden" name="WoID" value="<?php echo $record['WoID']; ?>" />
     <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $record['WoLgID']; ?>" />
     <input type="hidden" name="WoOldStatus" value="<?php echo $record['WoStatus']; ?>" />
     <table class="tab1" cellspacing="0" cellpadding="5">
     <tr>
        <td class="td1 right">Language:</td>
        <td class="td1"><?php echo tohtml($record['LgName']); ?></td>
     </tr>
     <tr title="Normally only change uppercase/lowercase here!">
        <td class="td1 right">Term:</td>
        <td class="td1"><input <?php echo $scrdir; ?> class="notempty setfocus checkoutsidebmp" data_info="Term" type="text" name="WoText" id="wordfield" value="<?php echo tohtml($record['WoText']); ?>" maxlength="250" size="40" /> <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
        <?php print_similar_terms_tabrow(); ?>
     <tr>
        <td class="td1 right">Translation:</td>
        <td class="td1">
            <textarea class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" name="WoTranslation" cols="40" rows="3"><?php echo tohtml($transl); ?></textarea>
        </td>
     </tr>
     <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <?php echo getWordTags($record['WoID']); ?>
        </td>
     </tr>
     <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
        <td class="td1 right">Romaniz.:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" maxlength="100" size="40"
            value="<?php echo tohtml($record['WoRomanization']); ?>" />
        </td>
     </tr>
     <tr>
        <td class="td1 right">Sentence<br />Term in {...}:</td>
        <td class="td1"><textarea <?php echo $scrdir; ?> class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" name="WoSentence" cols="40" rows="3"><?php echo tohtml(repl_tab_nl($record['WoSentence'])); ?></textarea></td>
     </tr>
     <tr>
        <td class="td1 right">Status:</td>
        <td class="td1">
            <?php echo get_wordstatus_radiooptions($record['WoStatus']); ?>
        </td>
     </tr>
     <tr>
        <td class="td1 right" colspan="2">  &nbsp;
            <?php echo createDictLinksInEditWin2($record['WoLgID'], 'document.forms[\'editword\'].WoSentence', 'document.forms[\'editword\'].WoText'); ?>
            &nbsp; &nbsp;
            <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/words/edit#rec<?php echo $_REQUEST['chg']; ?>';}" />
            <input type="submit" name="op" value="Change" />
        </td>
     </tr>
     </table>
     </form>
        <?php
        // Display example sentence button
        example_sentences_area($record['LgID'], $wordlc, "document.forms['editword'].WoSentence", $_REQUEST['chg']);
    }
    mysqli_free_result($res);
} else {
    // DISPLAY
    if (
        substr($message, 0, 24) == "Error: Duplicate entry '"
        && substr($message, -24) == "' for key 'WoLgIDTextLC'"
    ) {
        $lgID = $_REQUEST["WoLgID"] . "-";
        $message = substr($message, 24 + strlen($lgID));
        $message = substr($message, 0, strlen($message) - 24);
        $message = "Error: Term '" . $message . "' already exists. Please go back and correct this!";
    }
    echo error_message_with_hide($message, false);

    if ($currenttext == '') {
        $sql = 'select count(*) as value from (select WoID from (' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID) where (1=1) ' . $wh_lang . $wh_stat .  $wh_query . ' group by WoID ' . $wh_tag . ') as dummy';
    } else {
        $sql = 'select count(*) as value from (select WoID from (' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID), ' . $tbpref . 'textitems2 where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' . $currenttext . ')' . $wh_lang . $wh_stat . $wh_query . ' group by WoID ' . $wh_tag . ') as dummy';
    }
    $recno = (int) get_first_value($sql);
    if ($debug) {
        echo $sql . ' ===&gt; ' . $recno;
    }

    $maxperpage = (int) getSettingWithDefault('set-terms-per-page');

    $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

    if ($currentpage < 1) {
        $currentpage = 1;
    }
    if ($currentpage > $pages) {
        $currentpage = $pages;
    }
    $limit = 'LIMIT ' . (($currentpage - 1) * $maxperpage) . ',' . $maxperpage;

    $sorts = array('WoTextLC','lower(WoTranslation)','WoID desc','WoID asc','WoStatus, WoTextLC','WoTodayScore','textswordcount desc, WoTextLC asc');
    $lsorts = count($sorts);
    if ($currentsort < 1) {
        $currentsort = 1;
    }
    if ($currentsort > $lsorts) {
        $currentsort = $lsorts;
    }


    if ($currentlang != '') {
        ?>
   <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1&amp;lang=<?php echo $currentlang; ?>"><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New <?php echo tohtml(getLanguage($currentlang)); ?> Term ...</a></p>
        <?php
    } else {
        ?>
   <p><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New Term? - Set Language Filter first ...</p>
        <?php
    }
    ?>

<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="resetAll('/words/edit');" /></th>
</tr>
<tr>
<td class="td1 center" colspan="2">
Language:
<select name="filterlang" onchange="{setLang(document.form1.filterlang,'/words/edit');}"><?php	echo get_languages_selectoptions($currentlang, '[Filter off]'); ?></select>
</td>
<td class="td1 center" colspan="2">
<select name="text_mode" onchange="{val=document.form1.text_mode.value; location.href='/words/edit?page=1&amp;texttag=&amp;text=&amp;text_mode=' + val;}">
<option value="0"<?php if ($currenttextmode == "0") {
    echo ' selected="selected"';
                 } ?>>Text:</option>
<option value="1"<?php if ($currenttextmode == "1") {
    echo ' selected="selected"';
                 } ?>>Text Tag:</option>
</select>
<select name="text" onchange="{val=document.form1.text.options[document.form1.text.selectedIndex].value; location.href='/words/edit?page=1&amp;text=' + val;}"><?php echo ($currenttextmode != 1) ? (get_texts_selectoptions($currentlang, $currenttext)) : (get_txtag_selectoptions($currentlang, $currenttexttag)); ?></select>
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Status:
<select name="status" onchange="{val=document.form1.status.options[document.form1.status.selectedIndex].value; location.href='/words/edit?page=1&amp;status=' + val;}"><?php echo get_wordstatus_selectoptions($currentstatus, true, false); ?></select>
</td>
<td class="td1 center" colspan="2" nowrap="nowrap"><select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='/words/edit?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}">
<option value="term,rom,transl"<?php if ($currentquerymode == "term,rom,transl") {
    echo ' selected="selected"';
                               } ?>>Term, Rom., Transl.</option>
<option disabled="disabled">------------</option>
<option value="term"<?php if ($currentquerymode == "term") {
    echo ' selected="selected"';
                    } ?>>Term</option>
<option value="rom"<?php if ($currentquerymode == "rom") {
    echo ' selected="selected"';
                   } ?>>Romanization</option>
<option value="transl"<?php if ($currentquerymode == "transl") {
    echo ' selected="selected"';
                      } ?>>Translation</option>
<option disabled="disabled">------------</option>
<option value="term,rom"<?php if ($currentquerymode == "term,rom") {
    echo ' selected="selected"';
                        } ?>>Term, Rom.</option>
<option value="term,transl"<?php if ($currentquerymode == "term,transl") {
    echo ' selected="selected"';
                           } ?>>Term, Transl.</option>
<option value="rom,transl"<?php if ($currentquerymode == "rom,transl") {
    echo ' selected="selected"';
                          } ?>>Rom., Transl.</option>
</select><?php
if ($currentregexmode == '') {
    echo '<span style="vertical-align: middle"> (Wildc.=*): </span>';
} elseif ($currentregexmode == 'r') {
    echo '<span style="vertical-align: middle"> RegEx Mode: </span>';
} else {
    echo '<span style="vertical-align: middle"> RegEx(CS) Mode: </span>';
}?>
<input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val);mode=document.form1.query_mode.value; location.href='/words/edit?page=1&amp;query=' + val + '&amp;query_mode=' + mode;}" />&nbsp;
<input type="button" value="Clear" onclick="{location.href='/words/edit?page=1&amp;query=';}" />
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Tag #1:
<select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='/words/edit?page=1&amp;tag1=' + val;}"><?php echo get_tag_selectoptions($currenttag1, $currentlang); ?></select>
</td>
<td class="td1 center" nowrap="nowrap">
Tag #1 .. <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='/words/edit?page=1&amp;tag12=' + val;}"><?php echo get_andor_selectoptions($currenttag12); ?></select> .. Tag #2
</td>
<td class="td1 center" nowrap="nowrap">
Tag #2:
<select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='/words/edit?page=1&amp;tag2=' + val;}"><?php echo get_tag_selectoptions($currenttag2, $currentlang); ?></select>
</td>
</tr>
    <?php if ($recno > 0) { ?>
<tr>
<th class="th1" colspan="2" nowrap="nowrap">
        <?php echo $recno; ?> Term<?php echo ($recno == 1 ? '' : 's'); ?>
</th><th class="th1" colspan="1" nowrap="nowrap">
        <?php makePager($currentpage, $pages, '/words/edit', 'form1'); ?>
</th><th class="th1" nowrap="nowrap">
Sort Order:
<select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/words/edit?page=1&amp;sort=' + val;}"><?php echo get_wordssort_selectoptions($currentsort); ?></select>
</th></tr>
        <?php
    } ?>
</table>
</form>

    <?php
    if ($recno == 0) {
        ?>
<p>No terms found.</p>
        <?php
    } else {
        ?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><th class="th1 center" colspan="2">
Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
</th></tr>
<tr><td class="td1 center" colspan="2">
<b>ALL</b> <?php echo ($recno == 1 ? '1 Term' : $recno . ' Terms'); ?>:&nbsp;
<select name="allaction" onchange="allActionGo(document.form2, document.form2.allaction,<?php echo $recno; ?>);"><?php echo get_allwordsactions_selectoptions(); ?></select>
</td></tr>
<tr><td class="td1 center">
<input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
<input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
</td>
<td class="td1 center">Marked Terms:&nbsp;
<select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo get_multiplewordsactions_selectoptions(); ?></select>
</td></tr></table>

<table class="sortable tab2"  cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 sorttable_nosort">Act.</th>
        <?php if ($currentlang == '') {
            echo '<th class="th1 clickable">Lang.</th>';
        } ?>
<th class="th1 clickable">Term /<br />Romanization</th>
<th class="th1 clickable">Translation [Tags]<br /><span id="waitinfo">Please <img src="<?php print_file_path('icn/waiting2.gif'); ?>" /> wait ...</span></th>
<th class="th1 sorttable_nosort">Se.<br />?</th>
<th class="th1 sorttable_numeric clickable">Stat./<br />Days</th>
<th class="th1 sorttable_numeric clickable">Score<br />%</th>

        <?php
        if ($currentsort == 7) {
            ?>
<th class="th1 sorttable_numeric clickable" title="Word Count in Active Texts">WCnt<br />Txts</th>
            <?php
        }
        ?>
</tr>

        <?php

        if ($currentsort == 7) {
            if ($currenttext != '') {
                $sql = '';
            } else {
                $sql = 'select WoID, 0 AS textswordcount, WoText, WoTranslation,
        WoRomanization, WoSentence,
        ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
        WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
        DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
        WoTomorrowScore AS Score2,
        ifnull(concat(\'[\',group_concat(distinct TgText order by TgText separator \', \'),\']\'),\'\') as taglist,
        WoTextLC, WoTodayScore
        from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID)
        left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages
        where WoLgID = LgID and WoID NOT IN (SELECT DISTINCT Ti2WoID
        from ' . $tbpref . 'textitems2 where Ti2LgID = LgID) ' . $wh_lang . $wh_stat .  $wh_query . '
        group by WoID ' . $wh_tag . ' UNION ';
            }
            $sql .= 'select WoID, count(WoID) AS textswordcount, WoText, WoTranslation,
    WoRomanization, WoSentence,
    ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
    WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
    DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
    WoTomorrowScore AS Score2,
    ifnull(concat(\'[\',group_concat(distinct TgText order by TgText separator \', \'),\']\'),\'\') as taglist,
    WoTextLC, WoTodayScore
    from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID)
    left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages, ' . $tbpref . 'textitems2
    where Ti2LgID = WoLgID and Ti2WoID = WoID and WoLgID = LgID ';
            if ($currenttext != '') {
                $sql .= 'and Ti2TxID in (' . $currenttext . ')' . ' ';
            }
            $sql .= $wh_lang . $wh_stat .  $wh_query . ' group by WoID ' . $wh_tag . ' order by ' . $sorts[$currentsort - 1] . ' ' . $limit;
        } else {
            if ($currenttext == '') {
                if ($wh_tag == '') {
                    $sql = 'select WoID, WoText, WoTranslation, WoRomanization, WoSentence,
            SentOK, WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,  Days,
            WoTodayScore AS Score, WoTomorrowScore AS Score2,
            ifnull(concat(\'[\',group_concat(distinct TgText order by TgText separator \', \'),\']\'),\'\') as taglist
            from (select WoID, WoTextLC, WoText, WoTranslation, WoRomanization,
            WoSentence,
            ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
            WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
            DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore,
            WoTomorrowScore
            from ' . $tbpref . 'words, ' . $tbpref . 'languages
            where WoLgID = LgID ' . $wh_lang . $wh_stat .  $wh_query . '
            group by WoID
            order by ' . $sorts[$currentsort - 1] . ' ' . $limit . ') AS AA
            left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID
            left join ' . $tbpref . 'tags on TgID = WtTgID
            group by WoID
            order by ' . $sorts[$currentsort - 1];
                } else {
                    $sql = 'select WoID, WoText, WoTranslation, WoRomanization, WoSentence,
            ifnull(WoSentence,\'\') like concat(\'%{\',WoText,\'}%\') as SentOK,
            WoStatus, LgName, LgRightToLeft, LgGoogleTranslateURI,
            DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
            WoTomorrowScore AS Score2,
            ifnull(concat(\'[\',group_concat(distinct TgText order by TgText separator \', \'),\']\'),\'\') as taglist from ((' . $tbpref . 'words left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID) left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages where WoLgID = LgID ' . $wh_lang . $wh_stat .  $wh_query . ' group by WoID ' . $wh_tag . ' order by ' . $sorts[$currentsort - 1] . ' ' . $limit;
                }
            } else {
                $sql = 'select distinct WoID, WoText, WoTranslation, WoRomanization,
        WoSentence, ifnull(WoSentence,\'\') like \'%{%}%\' as SentOK, WoStatus,
        LgName, LgRightToLeft, LgGoogleTranslateURI,
        DATEDIFF( NOW( ) , WoStatusChanged ) AS Days, WoTodayScore AS Score,
        WoTomorrowScore AS Score2,
        ifnull(concat(\'[\',group_concat(distinct TgText order by TgText separator \', \'),\']\'),\'\') as taglist
        from ((' . $tbpref . 'words
        left JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID)
        left join ' . $tbpref . 'tags on TgID = WtTgID), ' . $tbpref . 'languages, ' . $tbpref . 'textitems2
        where Ti2LgID = WoLgID and Ti2WoID = WoID and Ti2TxID in (' . $currenttext . ') and WoLgID = LgID ' . $wh_lang . $wh_stat . $wh_query . '
        group by WoID ' . $wh_tag . '
        order by ' . $sorts[$currentsort - 1] . ' ' . $limit;
            }
        }

        if ($debug) {
            echo $sql;
        }
        flush();
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $days = $record['Days'];
            if ($record['WoStatus'] > 5) {
                $days = "-";
            }
            $score = $record['Score'];
            if ($score < 0) {
                $score = '<span class="scorered">0 <img src="/assets/icons/status-busy.png" title="Test today!" alt="Test today!" /></span>';
            } else {
                $score = '<span class="scoregreen">' . floor((int)$score) . ($record['Score2'] < 0 ? ' <img src="/assets/icons/status-away.png" title="Test tomorrow!" alt="Test tomorrow!" />' : ' <img src="/assets/icons/status.png" title="-" alt="-" />') . '</span>';
            }
            echo '<tr>';
            echo '<td class="td1 center"><a name="rec' . $record['WoID'] . '"><input name="marked[]" type="checkbox" class="markcheck" value="' . $record['WoID'] . '" ' . checkTest($record['WoID'], 'marked') . ' /></a></td>';
            echo '<td class="td1 center" nowrap="nowrap">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['WoID'] . '"><img src="/assets/icons/sticky-note--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <a class="confirmdelete" href="' . $_SERVER['PHP_SELF'] . '?del=' . $record['WoID'] . '"><img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></a>&nbsp;</td>';
            if ($currentlang == '') {
                echo '<td class="td1 center">' . tohtml($record['LgName']) . '</td>';
            }
            echo '<td class="td1"><span';
            if (
                !empty($record['LgGoogleTranslateURI']) && strpos((string) $record['LgGoogleTranslateURI'], '&sl=') !== false
            ) {
                echo ' class="tts_' . preg_replace('/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/', '$1', $record['LgGoogleTranslateURI']) .
                '"';
            }
            echo ($record['LgRightToLeft'] ? ' dir="rtl" ' : '') . '>' . tohtml($record['WoText']) . '</span>' . ($record['WoRomanization'] != '' ? (' / <span id="roman' . $record['WoID'] . '" class="edit_area clickedit">' . tohtml(repl_tab_nl($record['WoRomanization'])) . '</span>') : (' / <span id="roman' . $record['WoID'] . '" class="edit_area clickedit">*</span>')) . '</td>';
            echo '<td class="td1"><span id="trans' . $record['WoID'] . '" class="edit_area clickedit">' . tohtml(repl_tab_nl($record['WoTranslation'])) . '</span> <span class="smallgray2">' . tohtml($record['taglist']) . '</span></td>';
            echo '<td class="td1 center"><b>' . ($record['SentOK'] != 0 ? '<img src="/assets/icons/status.png" title="' . tohtml($record['WoSentence']) . '" alt="Yes" />' : '<img src="/assets/icons/status-busy.png" title="(No valid sentence)" alt="No" />') . '</b></td>';
            echo '<td class="td1 center" title="' . tohtml(get_status_name($record['WoStatus'])) . '">' . tohtml(get_status_abbr($record['WoStatus'])) . ($record['WoStatus'] < 98 ? '/' . $days : '') . '</td>';
            echo '<td class="td1 center" nowrap="nowrap">' . $score . '</td>';
            if ($currentsort == 7) {
                echo '<td class="td1 center" nowrap="nowrap">' . $record['textswordcount'] . '</td>';
            }
            echo "</tr>\n";
        }
        mysqli_free_result($res);

        ?>
</table>


<script type="text/javascript">
//<![CDATA[
    $('#waitinfo').addClass('hide');
//]]>
</script>

        <?php if ($pages > 1) { ?>
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1" nowrap="nowrap">
            <?php echo $recno; ?> Term<?php echo ($recno == 1 ? '' : 's'); ?>
        </th>
        <th class="th1" nowrap="nowrap">
            <?php makePager($currentpage, $pages, '/words/edit', 'form2'); ?>
        </th>
    </tr>
</table>
</form>

            <?php
        } ?>

        <?php
    }
}

pageend();

?>
