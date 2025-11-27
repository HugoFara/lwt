<?php

/**
 * Manage text tags
 *
 * Call: /tags/text?....
 *  ... markaction=[opcode] ... do actions on marked text tags
 *  ... allaction=[opcode] ... do actions on all text tags
 *  ... del=[wordid] ... do delete
 *  ... op=Save ... do insert new
 *  ... op=Change ... do update
 *  ... new=1 ... display new text tag screen
 *  ... chg=[wordid] ... display edit screen
 *  ... sort=[sortcode] ... sort
 *  ... page=[pageno] ... page
 *  ... query=[tagtextfilter] ... tag text filter
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 */

namespace Lwt\Interface\Edit_Text_Tags;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Http/param_helpers.php';
require_once __DIR__ . '/../Services/TagService.php';

use Lwt\Services\TagService;

// Initialize service for text tags
$tagService = new TagService('text');

$currentsort = (int) processDBParam("sort", 'currenttexttagsort', '1', true);
$currentpage = (int) processSessParam("page", "currenttexttagpage", '1', true);
$currentquery = (string) processSessParam("query", "currenttexttagquery", '', false);

$wh_query = $tagService->buildWhereClause($currentquery);

pagestart('My Text Tags', true);

$message = '';

// MARK ACTIONS
if (isset($_REQUEST['markaction'])) {
    $markaction = $_REQUEST['markaction'];
    $message = "Multiple Actions: 0";
    if (isset($_REQUEST['marked']) && is_array($_REQUEST['marked']) && count($_REQUEST['marked']) > 0) {
        if ($markaction == 'del') {
            $message = $tagService->deleteMultiple($_REQUEST['marked']);
        }
    }
}

// ALL ACTIONS
if (isset($_REQUEST['allaction'])) {
    $allaction = $_REQUEST['allaction'];
    if ($allaction == 'delall') {
        $message = $tagService->deleteAll($wh_query);
    }
} elseif (isset($_REQUEST['del'])) {
    // DEL
    $message = $tagService->delete((int) $_REQUEST['del']);
} elseif (isset($_REQUEST['op'])) {
    // INS/UPD
    if ($_REQUEST['op'] == 'Save') {
        $message = $tagService->create($_REQUEST["T2Text"], $_REQUEST["T2Comment"]);
    } elseif ($_REQUEST['op'] == 'Change') {
        $message = $tagService->update(
            (int) $_REQUEST["T2ID"],
            $_REQUEST["T2Text"],
            $_REQUEST["T2Comment"]
        );
    }
}

// NEW
if (isset($_REQUEST['new'])) {
    /** @psalm-suppress UnusedVariable - Variables used by included view */
    $mode = 'new';
    /** @psalm-suppress UnusedVariable */
    $tag = null;
    /** @psalm-suppress UnusedVariable */
    $service = $tagService;
    /** @psalm-suppress UnusedVariable */
    $formFieldPrefix = 'T2';

    include __DIR__ . '/../Views/Tags/tag_form.php';
} elseif (isset($_REQUEST['chg'])) {
    // CHG
    $tagData = $tagService->getById((int) $_REQUEST['chg']);
    if ($tagData) {
        /** @psalm-suppress UnusedVariable - Variables used by included view */
        $mode = 'edit';
        /** @psalm-suppress UnusedVariable */
        $tag = [
            'id' => $tagData['T2ID'],
            'text' => $tagData['T2Text'],
            'comment' => $tagData['T2Comment']
        ];
        /** @psalm-suppress UnusedVariable */
        $service = $tagService;
        /** @psalm-suppress UnusedVariable */
        $formFieldPrefix = 'T2';

        include __DIR__ . '/../Views/Tags/tag_form.php';
    }
} else {
    // DISPLAY
    $message = $tagService->formatDuplicateError($message);

    get_texttags($refresh = 1);   // refresh tags cache

    /** @psalm-suppress UnusedVariable - Variables used by included view */
    $totalCount = $tagService->getCount($wh_query);
    /** @psalm-suppress UnusedVariable */
    $pagination = $tagService->getPagination($totalCount, $currentpage);

    $sortColumn = $tagService->getSortColumn($currentsort);
    /** @psalm-suppress UnusedVariable */
    $tags = $tagService->getList(
        $wh_query,
        $sortColumn,
        $pagination['currentPage'],
        $pagination['perPage']
    );

    /** @psalm-suppress UnusedVariable */
    $currentQuery = $currentquery;
    /** @psalm-suppress UnusedVariable */
    $currentSort = $currentsort;
    /** @psalm-suppress UnusedVariable */
    $service = $tagService;
    /** @psalm-suppress UnusedVariable */
    $isTextTag = true;

    include __DIR__ . '/../Views/Tags/tag_list.php';
}

pageend();
