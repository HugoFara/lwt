<?php

require_once __DIR__ . '/database_connect.php';

/**
 * Return the list of all tags.
 *
 * @param int $refresh If true, refresh all tags for session
 *
 *
 * @return array<string> All tags
 */
function get_tags($refresh = 0)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (isset($_SESSION['TAGS'])
        && is_array($_SESSION['TAGS'])
        && isset($_SESSION['TBPREF_TAGS'])
        && $_SESSION['TBPREF_TAGS'] == $tbpref . url_base()
        && $refresh == 0
    ) {
            return $_SESSION['TAGS'];
    }
    $tags = array();
    $sql = 'SELECT TgText FROM ' . $tbpref . 'tags ORDER BY TgText';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $tags[] = (string)$record["TgText"];
    }
    mysqli_free_result($res);
    $_SESSION['TAGS'] = $tags;
    $_SESSION['TBPREF_TAGS'] = $tbpref . url_base();
    return $_SESSION['TAGS'];
}

/**
 * Return the list of all text tags.
 *
 * @param int $refresh If true, refresh all text tags for session
 *
 *
 * @return array<string> All text tags
 */
function get_texttags($refresh = 0)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (isset($_SESSION['TEXTTAGS'])
        && is_array($_SESSION['TEXTTAGS'])
        && isset($_SESSION['TBPREF_TEXTTAGS'])
        && $refresh == 0
        && $_SESSION['TBPREF_TEXTTAGS'] == $tbpref . url_base()
    ) {
            return $_SESSION['TEXTTAGS'];
    }
    $tags = array();
    $sql = 'SELECT T2Text FROM ' . $tbpref . 'tags2 ORDER BY T2Text';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $tags[] = (string)$record["T2Text"];
    }
    mysqli_free_result($res);
    $_SESSION['TEXTTAGS'] = $tags;
    $_SESSION['TBPREF_TEXTTAGS'] = $tbpref . url_base();
    return $_SESSION['TEXTTAGS'];
}

// -------------------------------------------------------------

function getTextTitle($textid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Validate that textid is numeric
    if (!is_numeric($textid)) {
        return "?";
    }
    $text = get_first_value(
        "SELECT TxTitle AS value
        FROM " . $tbpref . "texts
        WHERE TxID=" . (int)$textid
    );
    if (!isset($text)) {
        $text = "?";
    }
    return (string)$text;
}

// -------------------------------------------------------------

function get_tag_selectoptions($v,$l): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (!isset($v)) {
        $v = '';
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select TgID, TgText
        from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags
        where TgID = WtTgID and WtWoID = WoID
        group by TgID
        order by UPPER(TgText)";
    } else {
        $sql = "select TgID, TgText
        from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags
        where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $l . "
        group by TgID
        order by UPPER(TgText)";
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["TgText"];
        $cnt++;
        $r .= "<option value=\"" . $record["TgID"] . "\"" .
         get_selected($v, $record["TgID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_texttag_selectoptions($v,$l): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (!isset($v) ) {
        $v = '';
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select T2ID, T2Text
        from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags
        where T2ID = TtT2ID and TtTxID = TxID
        group by T2ID
        order by UPPER(T2Text)";
    } else {
        $sql = "select T2ID, T2Text
        from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags
        where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $l . "
        group by T2ID
        order by UPPER(T2Text)";
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["T2Text"];
        $cnt++;
        $r .= "<option value=\"" . $record["T2ID"] . "\"" .
        get_selected($v, $record["T2ID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_txtag_selectoptions($l,$v): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (!isset($v)) {
        $v = '';
    }
    $u ='';
    $r = "<option value=\"&amp;texttag\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    $sql = 'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID, GROUP_CONCAT(TxID
    ORDER BY TxID) AS TextID
    FROM ' . $tbpref . 'texts
    LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
    LEFT JOIN ' . $tbpref . 'tags2 ON TtT2ID = T2ID';
    if ($l) {
        $sql .= ' WHERE TxLgID=' . $l;
    }
    $sql .= ' GROUP BY UPPER(TagName)';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        if ($record['TagName']==1) {
            $u ="<option disabled=\"disabled\">--------</option><option value=\"" .
            $record['TextID'] . "&amp;texttag=-1\"" . get_selected($v, "-1") .
            ">UNTAGGED</option>";
        } else {
            $r .= "<option value=\"" .$record['TextID']."&amp;texttag=".
            $record['TagID'] . "\"" . get_selected($v, $record['TagID']) . ">" .
            $record['TagName'] . "</option>";
        }
    }
    mysqli_free_result($res);
    return $r.$u;
}

// -------------------------------------------------------------

function get_archivedtexttag_selectoptions($v,$l): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (!isset($v)) {
        $v = '';
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select T2ID, T2Text
        from " . $tbpref . "archivedtexts, " .
        $tbpref . "tags2, " . $tbpref . "archtexttags
        where T2ID = AgT2ID and AgAtID = AtID
        group by T2ID
        order by UPPER(T2Text)";
    } else {
        $sql = "select T2ID, T2Text
        from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " .
        $tbpref . "archtexttags
        where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $l . "
        group by T2ID
        order by UPPER(T2Text)";
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["T2Text"];
        $cnt++;
        $r .= "<option value=\"" . $record["T2ID"] . "\"" .
        get_selected($v, $record["T2ID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}


/**
 * Save the tags for words.
 *
 * @return void
 */
function saveWordTags($wid)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Validate that wid is numeric
    if (!is_numeric($wid)) {
        return;
    }
    runsql("DELETE from " . $tbpref . "wordtags WHERE WtWoID =" . (int)$wid, '');
    if (!isset($_REQUEST['TermTags'])
        || !is_array($_REQUEST['TermTags'])
        || !isset($_REQUEST['TermTags']['TagList'])
        || !is_array($_REQUEST['TermTags']['TagList'])
    ) {
         return;
    }
    $cnt = count($_REQUEST['TermTags']['TagList']);
    getWordTags(1);

    for ($i = 0; $i < $cnt; $i++) {
        $tag = $_REQUEST['TermTags']['TagList'][$i];
        if(!in_array($tag, $_SESSION['TAGS'])) {
            runsql(
                "INSERT INTO {$tbpref}tags (TgText)
                VALUES(" . convert_string_to_sqlsyntax($tag) . ")",
                ""
            );
        }
        runsql(
            "INSERT INTO {$tbpref}wordtags (WtWoID, WtTgID)
            SELECT $wid, TgID
            FROM {$tbpref}tags
            WHERE TgText = " . convert_string_to_sqlsyntax($tag),
            ""
        );
    }
    // refresh tags cache
    get_tags(1);
}

/**
 * Save the tags for texts.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 */
function saveTextTags($tid): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Validate that tid is numeric
    if (!is_numeric($tid)) {
        return;
    }
    runsql(
        "DELETE FROM " . $tbpref . "texttags WHERE TtTxID =" . (int)$tid,
        ''
    );
    if (!isset($_REQUEST['TextTags'])
        || !is_array($_REQUEST['TextTags'])
        || !isset($_REQUEST['TextTags']['TagList'])
        || !is_array($_REQUEST['TextTags']['TagList'])
    ) {
        return;
    }
    $cnt = count($_REQUEST['TextTags']['TagList']);
    get_texttags(1);

    for ($i = 0; $i < $cnt; $i++) {
        $tag = $_REQUEST['TextTags']['TagList'][$i];
        if (!in_array($tag, $_SESSION['TEXTTAGS'])) {
            runsql(
                "INSERT INTO {$tbpref}tags2 (T2Text)
                VALUES(" . convert_string_to_sqlsyntax($tag) . ")",
                ""
            );
        }
        runsql(
            "INSERT INTO {$tbpref}texttags (TtTxID, TtT2ID)
            SELECT $tid, T2ID
            FROM {$tbpref}tags2
            WHERE T2Text = " . convert_string_to_sqlsyntax($tag),
            ""
        );
    }
    // refresh tags cache
    get_texttags(1);
}


/**
 * Save the tags for archived texts.
 *
 * @return void
 *
 * @global string $tbpref Databse table prefix.
 */
function saveArchivedTextTags($tid): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Validate that tid is numeric
    if (!is_numeric($tid)) {
        return;
    }
    runsql("DELETE from " . $tbpref . "archtexttags WHERE AgAtID =" . (int)$tid, '');
    if (!isset($_REQUEST['TextTags'])
        || !is_array($_REQUEST['TextTags'])
        || !isset($_REQUEST['TextTags']['TagList'])
        || !is_array($_REQUEST['TextTags']['TagList'])
    ) {
        return;
    }
    $cnt = count($_REQUEST['TextTags']['TagList']);
    get_texttags(1);
    for ($i = 0; $i < $cnt; $i++) {
        $tag = $_REQUEST['TextTags']['TagList'][$i];
        if (!in_array($tag, $_SESSION['TEXTTAGS'])) {
            runsql(
                'INSERT INTO {$tbpref}tags2 (T2Text)
                VALUES(' . convert_string_to_sqlsyntax($tag) . ')',
                ""
            );
        }
        runsql(
            "INSERT INTO {$tbpref}archtexttags (AgAtID, AgT2ID)
            SELECT $tid, T2ID
            FROM {$tbpref}tags2
            WHERE T2Text = " . convert_string_to_sqlsyntax($tag),
            ""
        );
        // refresh tags cache
        get_texttags(1);
    }
}

// -------------------------------------------------------------

function getWordTags($wid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $r = '<ul id="termtags">';
    if ($wid > 0) {
        $sql = 'select TgText
        from ' . $tbpref . 'wordtags, ' . $tbpref . 'tags
        where TgID = WtTgID and WtWoID = ' . $wid . '
        order by TgText';
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["TgText"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}

/**
 * Return a HTML-formatted list of the text tags.
 *
 * @param int $tid Text ID. Can be below 1 to create an empty list.
 *
 * @return string UL list of text tags
 *
 * @global string $tbpref Database table prefix
 */
function getTextTags($tid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $r = '<ul id="texttags" class="respinput">';
    if ($tid > 0) {
        $sql = "SELECT T2Text
        FROM {$tbpref}texttags, {$tbpref}tags2
        WHERE T2ID = TtT2ID AND TtTxID = $tid
        ORDER BY T2Text";
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}


/**
 * Return a HTML-formatted list of the text tags for an archived text.
 *
 * @param int $tid Text ID. Can be below 1 to create an empty list.
 *
 * @return string UL list of text tags
 *
 * @global string $tbpref Database table prefix
 */
function getArchivedTextTags($tid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $r = '<ul id="texttags">';
    if ($tid > 0) {
        $sql = 'SELECT T2Text
        FROM ' . $tbpref . 'archtexttags, ' . $tbpref . 'tags2
        WHERE T2ID = AgT2ID AND AgAtID = ' . $tid . '
        ORDER BY T2Text';
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}

// -------------------------------------------------------------

function addtaglist($item, $list): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Handle empty list
    if ($list === '()') {
        return "Tag added in 0 Terms";
    }
    $tagid = get_first_value(
        'select TgID as value
        from ' . $tbpref . 'tags
        where TgText = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        runsql(
            'insert into ' . $tbpref . 'tags (TgText)
            values(' . convert_string_to_sqlsyntax($item) . ')',
            ""
        );
        $tagid = get_first_value(
            'select TgID as value
            from ' . $tbpref . 'tags
            where TgText = ' . convert_string_to_sqlsyntax($item)
        );
        // If still not set, tag creation failed
        if (!isset($tagid)) {
            return "Failed to create tag";
        }
    }
    $sql = 'select WoID
    from ' . $tbpref . 'words
    LEFT JOIN ' . $tbpref . 'wordtags
    ON WoID = WtWoID AND WtTgID = ' . $tagid . '
    WHERE WtTgID IS NULL AND WoID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += (int) runsql(
            'insert ignore into ' . $tbpref . 'wordtags (WtWoID, WtTgID)
            values(' . $record['WoID'] . ', ' . $tagid . ')',
            ""
        );
    }
    mysqli_free_result($res);
    get_tags($refresh = 1);
    return "Tag added in $cnt Terms";
}

// -------------------------------------------------------------

function addarchtexttaglist($item, $list): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Handle empty list
    if ($list === '()') {
        return "Tag added in 0 Texts";
    }
    $tagid = get_first_value(
        'select T2ID as value from ' . $tbpref . 'tags2
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        runsql(
            'insert into ' . $tbpref . 'tags2 (T2Text)
            values(' . convert_string_to_sqlsyntax($item) . ')',
            ""
        );
        $tagid = get_first_value(
            'select T2ID as value
            from ' . $tbpref . 'tags2
            where T2Text = ' . convert_string_to_sqlsyntax($item)
        );
        // If still not set, tag creation failed
        if (!isset($tagid)) {
            return "Failed to create tag";
        }
    }
    $sql = 'select AtID from ' . $tbpref . 'archivedtexts
    LEFT JOIN ' . $tbpref . 'archtexttags
    ON AtID = AgAtID AND AgT2ID = ' . $tagid . '
    WHERE AgT2ID IS NULL AND AtID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += (int) runsql(
            'insert ignore into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID)
            values(' . $record['AtID'] . ', ' . $tagid . ')',
            ""
        );
    }
    mysqli_free_result($res);
    get_texttags($refresh = 1);
    return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function addtexttaglist($item, $list): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Handle empty list
    if ($list === '()') {
        return "Tag added in 0 Texts";
    }
    $tagid = get_first_value(
        'select T2ID as value
        from ' . $tbpref . 'tags2
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        runsql(
            'insert into ' . $tbpref . 'tags2 (T2Text)
            values(' . convert_string_to_sqlsyntax($item) . ')',
            ""
        );
        $tagid = get_first_value(
            'select T2ID as value
            from ' . $tbpref . 'tags2
            where T2Text = ' . convert_string_to_sqlsyntax($item)
        );
        // If still not set, tag creation failed
        if (!isset($tagid)) {
            return "Failed to create tag";
        }
    }
    $sql = 'select TxID from ' . $tbpref . 'texts
     LEFT JOIN ' . $tbpref . 'texttags
     ON TxID = TtTxID AND TtT2ID = ' . $tagid . '
     WHERE TtT2ID IS NULL AND TxID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += (int) runsql(
            'insert ignore into ' . $tbpref . 'texttags (TtTxID, TtT2ID)
            values(' . $record['TxID'] . ', ' . $tagid . ')',
            ""
        );
    }
    mysqli_free_result($res);
    get_texttags($refresh = 1);
    return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function removetaglist($item, $list): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Handle empty list
    if ($list === '()') {
        return "Tag removed in 0 Terms";
    }
    $tagid = get_first_value(
        'SELECT TgID AS value
        FROM ' . $tbpref . 'tags
        WHERE TgText = ' . convert_string_to_sqlsyntax($item)
    );
    if (! isset($tagid)) {
        return "Tag " . $item . " not found";
    }
    $sql = 'select WoID from ' . $tbpref . 'words where WoID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql(
            'DELETE FROM ' . $tbpref . 'wordtags
            WHERE WtWoID = ' . $record['WoID'] . ' AND WtTgID = ' . $tagid,
            ""
        );
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Terms";
}

// -------------------------------------------------------------

function removearchtexttaglist($item, $list): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Handle empty list
    if ($list === '()') {
        return "Tag removed in 0 Texts";
    }
    $tagid = get_first_value(
        'select T2ID as value
        from ' . $tbpref . 'tags2
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        return "Tag " . $item . " not found";
    }
    $sql = 'select AtID from ' . $tbpref . 'archivedtexts where AtID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql(
            'delete from ' . $tbpref . 'archtexttags
            where AgAtID = ' . $record['AtID'] . ' and AgT2ID = ' . $tagid,
            ""
        );
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function removetexttaglist($item, $list): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Handle empty list
    if ($list === '()') {
        return "Tag removed in 0 Texts";
    }
    $tagid = get_first_value(
        'select T2ID as value from ' . $tbpref . 'tags2
        where T2Text = ' . convert_string_to_sqlsyntax($item)
    );
    if (!isset($tagid)) {
        return "Tag " . $item . " not found";
    }
    $sql = 'select TxID from ' . $tbpref . 'texts where TxID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql(
            'delete from ' . $tbpref . 'texttags
            where TtTxID = ' . $record['TxID'] . ' and TtT2ID = ' . $tagid,
            ""
        );
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Texts";
}

?>
