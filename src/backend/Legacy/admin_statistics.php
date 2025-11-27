<?php

/**
 * Display statistics
 *
 * Call: statistics.php
 *
 * PHP version 8.1
 *
 * @category User_Interface
 */

namespace Lwt\Interface\Statistics;

require_once 'Core/database_connect.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Word/word_status.php';

pagestart('My Statistics', true);

?>
<h2>Learning Intensity</h2>
<p>Breakdown by Language and Term Status <wbr />(Click on numbers to see the list of terms)</p>
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
    <th class="th1">Language</th>
    <th class="th1">Total<br /></th>
    <th class="th1">Active<br />(1..5)</th>
    <th class="th1">Learning<br />(1..4)</th>
    <th class="th1">Unknown<br />(1)</th>
    <th class="th1">Learning<br />(2)</th>
    <th class="th1">Learning<br />(3)</th>
    <th class="th1">Learning<br />(4)</th>
    <th class="th1">Learned<br />(5)</th>
    <th class="th1">Well<br />Known<br />(99)</th>
    <th class="th1">Known<br />(5+99)</th>
    <th class="th1">Ign.<br />(98)</th>
</tr>
<?php

$sum1 = 0;
$sum2 = 0;
$sum3 = 0;
$sum4 = 0;
$sum5 = 0;
$sum98 = 0;
$sum99 = 0;
$sum14 = 0;
$sum15 = 0;
$sum599 = 0;
$sumall = 0;

$sql = 'SELECT WoLgID,WoStatus,count(*) AS value FROM ' . $tbpref . 'words GROUP BY WoLgID,WoStatus';
$res = do_mysqli_query($sql);
/** @var array<int|string, array<int|string, int>> $term_stat */
$term_stat = [];
if ($res instanceof \mysqli_result) {
    while ($record = mysqli_fetch_assoc($res)) {
        $lgId = (string)$record['WoLgID'];
        $status = (string)$record['WoStatus'];
        $term_stat[$lgId][$status] = (int)$record['value'];
    }
    mysqli_free_result($res);
}
$sql = 'SELECT LgID, LgName FROM ' . $tbpref . 'languages where LgName<>"" ORDER BY LgName';
$res = do_mysqli_query($sql);
if ($res instanceof \mysqli_result) {
    while ($record = mysqli_fetch_assoc($res)) {
        $lgId = (string)$record['LgID'];
        $lang = $lgId;

        $s1 = $term_stat[$lgId][1] ?? 0;
        $s2 = $term_stat[$lgId][2] ?? 0;
        $s3 = $term_stat[$lgId][3] ?? 0;
        $s4 = $term_stat[$lgId][4] ?? 0;
        $s5 = $term_stat[$lgId][5] ?? 0;
        $s98 = $term_stat[$lgId][98] ?? 0;
        $s99 = $term_stat[$lgId][99] ?? 0;
        $s14 = $s1 + $s2 + $s3 + $s4;
        $s15 = $s14 + $s5;
        $s599 = $s5 + $s99;
        $all = $s15 + $s98 + $s99;

        $sum1 += $s1;
        $sum2 += $s2;
        $sum3 += $s3;
        $sum4 += $s4;
        $sum5 += $s5;
        $sum98 += $s98;
        $sum99 += $s99;
        $sum14 += $s14;
        $sum15 += $s15;
        $sum599 += $s599;
        $sumall += $all;

        echo '<tr>';
        echo '<td class="td1">' . tohtml($record['LgName']) . '</td>';
        echo '<td class="td1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $all . '</b></a></td>';
        echo '<td class="td1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s15 . '</b></a></td>';
        echo '<td class="td1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s14 . '</b></a></td>';
        echo '<td class="td1 center"><span class="status1">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=1&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s1 . '</a>&nbsp;</span></td>';
        echo '<td class="td1 center"><span class="status2">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=2&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s2 . '</a>&nbsp;</span></td>';
        echo '<td class="td1 center"><span class="status3">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=3&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s3 . '</a>&nbsp;</span></td>';
        echo '<td class="td1 center"><span class="status4">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=4&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s4 . '</a>&nbsp;</span></td>';
        echo '<td class="td1 center"><span class="status5">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=5&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s5 . '</a>&nbsp;</span></td>';
        echo '<td class="td1 center"><span class="status99">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=99&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s99 . '</a>&nbsp;</span></td>';
        echo '<td class="td1 center"><span class="status5stat">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=599&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s599 . '</b></a>&nbsp;</span></td>';
        echo '<td class="td1 center"><span class="status98">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=98&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s98 . '</b></a>&nbsp;</span></td>';
        echo '</tr>';
    }
    mysqli_free_result($res);
}
echo '<tr>';
echo '<th class="th1"><b>TOTAL</b></th>';
echo '<th class="th1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sumall . '</b></a></th>';
echo '<th class="th1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum15 . '</b></a></th>';
echo '<th class="th1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum14 . '</b></a></th>';
echo '<th class="th1 center"><span class="status1">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=1&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum1 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status2">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=2&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum2 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=3&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum3 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status4">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=4&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum4 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=5&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum5 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status99">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=99&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum99 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=599&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum599 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status98">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=98&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum98 . '</b></a>&nbsp;</span></th>';
echo '</tr>';
?>
</table>

<?php

$sumct = 0;
$sumat = 0;
$sumkt = 0;
$sumcy = 0;
$sumay = 0;
$sumky = 0;
$sumcw = 0;
$sumaw = 0;
$sumkw = 0;
$sumcm = 0;
$sumam = 0;
$sumkm = 0;
$sumca = 0;
$sumaa = 0;
$sumka = 0;
$sumcall = 0;
$sumaall = 0;
$sumkall = 0;

?>

<h2>Learning Frequency</h2>
<p>Breakdown by Language and Time Range <wbr />(Terms created (C), Terms changed status = Activity (A), Terms set to "Known" (K))</p>
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
    <th class="th1" rowspan="2">Language</th>
    <th class="th1" colspan="3">Today</th>
    <th class="th1" colspan="3">Yesterday</th>
    <th class="th1" colspan="3">Last 7 d</th>
    <th class="th1" colspan="3">Last 30 d</th>
    <th class="th1" colspan="3">Last 365 d</th>
    <th class="th1" colspan="3">All Time</th>
</tr>
<tr>
    <th class="th1">C</th>
    <th class="th1">A</th>
    <th class="th1">K</th>
    <th class="th1">C</th>
    <th class="th1">A</th>
    <th class="th1">K</th>
    <th class="th1">C</th>
    <th class="th1">A</th>
    <th class="th1">K</th>
    <th class="th1">C</th>
    <th class="th1">A</th>
    <th class="th1">K</th>
    <th class="th1">C</th>
    <th class="th1">A</th>
    <th class="th1">K</th>
    <th class="th1">C</th>
    <th class="th1">A</th>
    <th class="th1">K</th>
</tr>
<?php

$sql = 'select WoLgID,TO_DAYS(curdate())-TO_DAYS(cast(WoCreated as date)) Created,count(WoID) as value from ' . $tbpref . 'words where WoStatus in (1,2,3,4,5,99) GROUP BY WoLgID,Created';
$res = do_mysqli_query($sql);
$term_created = null;
while ($record = mysqli_fetch_assoc($res)) {
    $term_created[$record['WoLgID']][$record['Created']] = $record['value'];
}

$sql = 'select WoLgID,WoStatus,TO_DAYS(curdate())-TO_DAYS(cast(WoStatusChanged as date)) Changed,count(WoID) as value from ' . $tbpref . 'words GROUP BY WoLgID,WoStatus,WoStatusChanged';
$res = do_mysqli_query($sql);
$term_active = null;
$term_known = null;
while ($record = mysqli_fetch_assoc($res)) {
    if (!empty($record['WoStatus'])) {
        switch ($record['WoStatus']) {
            case ($record['WoStatus'] == 5 || $record['WoStatus'] == 99):
                if (!isset($term_known[$record['WoLgID']][$record['Changed']])) {
                    $term_known[$record['WoLgID']][$record['Changed']] = 0;
                }
                $term_known[$record['WoLgID']][$record['Changed']] += $record['value'];
                if (!isset($term_active[$record['WoLgID']][$record['Changed']])) {
                    $term_active[$record['WoLgID']][$record['Changed']] = 0;
                }
                $term_active[$record['WoLgID']][$record['Changed']] += $record['value'];
                break;
            case ($record['WoStatus'] > 0 and $record['WoStatus'] < 5):
                if (!isset($term_active[$record['WoLgID']][$record['Changed']])) {
                    $term_active[$record['WoLgID']][$record['Changed']] = 0;
                }
                $term_active[$record['WoLgID']][$record['Changed']] += $record['value'];
                break;
            default:
                break;
        }
    }
}

$sql = 'SELECT LgID, LgName FROM ' . $tbpref . 'languages where LgName<>"" ORDER BY LgName';
$res = do_mysqli_query($sql);
while ($record = mysqli_fetch_assoc($res)) {
    $cw = 0;
    $cm = 0;
    $ca = 0;
    $call = 0;
    $aw = 0;
    $am = 0;
    $aa = 0;
    $aall = 0;
    $kw = 0;
    $km = 0;
    $ka = 0;
    $kall = 0;

    if (isset($term_created[$record['LgID']])) {
        foreach ($term_created[$record['LgID']] as $created => $val) {
            switch ($created) {
                case ($created === '0'):
                    $cw += $val;
                    break;
                case ($created > 364):
                    $call += $val;
                    break;
                case ($created > 29):
                    $ca += $val;
                    break;
                case ($created > 6):
                    $cm += $val;
                    break;
                default:
                    $cw += $val;
                    break;
            }
        }
    }

    $ct = isset($term_created[$record['LgID']][0]) ? $term_created[$record['LgID']][0] : 0;
    $cy = isset($term_created[$record['LgID']][1]) ? $term_created[$record['LgID']][1] : 0;
    $cm += $cw;
    $ca += $cm;
    $call += $ca;
    if (isset($term_active[$record['LgID']])) {
        foreach ($term_active[$record['LgID']] as $active => $val) {
            switch ($active) {
                case ($active === '0'):
                    $aw += $val;
                    break;
                case ($active > 364):
                    $aall += $val;
                    break;
                case ($active > 29):
                    $aa += $val;
                    break;
                case ($active > 6):
                    $am += $val;
                    break;
                default:
                    $aw += $val;
                    break;
            }
        }
    }

    $at = isset($term_active[$record['LgID']][0]) ? $term_active[$record['LgID']][0] : 0;
    $ay = isset($term_active[$record['LgID']][1]) ? $term_active[$record['LgID']][1] : 0;
    $am += $aw;
    $aa += $am;
    $aall += $aa;

    if (isset($term_known[$record['LgID']])) {
        foreach ($term_known[$record['LgID']] as $known => $val) {
            switch ($known) {
                case ($known === '0'):
                    $kw += $val;
                    break;
                case ($known > 364):
                    $kall += $val;
                    break;
                case ($known > 29):
                    $ka += $val;
                    break;
                case ($known > 6):
                    $km += $val;
                    break;
                default:
                    $kw += $val;
                    break;
            }
        }
    }

    $kt = isset($term_known[$record['LgID']][0]) ? $term_known[$record['LgID']][0] : 0;
    $ky = isset($term_known[$record['LgID']][1]) ? $term_known[$record['LgID']][1] : 0;
    $km += $kw;
    $ka += $km;
    $kall += $ka;

    $sumct += $ct;
    $sumat += $at;
    $sumkt += $kt;
    $sumcy += $cy;
    $sumay += $ay;
    $sumky += $ky;
    $sumcw += $cw;
    $sumaw += $aw;
    $sumkw += $kw;
    $sumcm += $cm;
    $sumam += $am;
    $sumkm += $km;
    $sumca += $ca;
    $sumaa += $aa;
    $sumka += $ka;
    $sumcall += $call;
    $sumaall += $aall;
    $sumkall += $kall;

    echo '<tr>';
    echo '<td class="td1">' . tohtml($record['LgName']) . '</td>';

    echo '<td class="td1 center"><span class="status1">&nbsp;' . $ct . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status3">&nbsp;' . $at . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $kt . '&nbsp;</span></td>';

    echo '<td class="td1 center"><span class="status1">&nbsp;' . $cy . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status3">&nbsp;' . $ay . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $ky . '&nbsp;</span></td>';

    echo '<td class="td1 center"><span class="status1">&nbsp;' . $cw . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status3">&nbsp;' . $aw . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $kw . '&nbsp;</span></td>';

    echo '<td class="td1 center"><span class="status1">&nbsp;' . $cm . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status3">&nbsp;' . $am . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $km . '&nbsp;</span></td>';

    echo '<td class="td1 center"><span class="status1">&nbsp;' . $ca . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status3">&nbsp;' . $aa . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $ka . '&nbsp;</span></td>';

    echo '<td class="td1 center"><span class="status1">&nbsp;' . $call . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status3">&nbsp;' . $aall . '&nbsp;</span></td>';
    echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $kall . '&nbsp;</span></td>';

    echo '</tr>';
}
mysqli_free_result($res);
echo '<tr>';
echo '<th class="th1"><b>TOTAL</b></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumct . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumat . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkt . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcy . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumay . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumky . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcw . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumaw . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkw . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcm . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumam . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkm . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumca . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumaa . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumka . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcall . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumaall . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkall . '&nbsp;</span></th>';

echo '</tr>';
?>
</table>
<p>
    <input type="button" value="&lt;&lt; Back" onclick="location.href='/';" />
</p>
<?php

pageend();

?>
