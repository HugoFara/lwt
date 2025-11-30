<?php declare(strict_types=1);
/**
 * Statistics View
 *
 * Variables expected:
 * - $intensityStats: array with 'languages' and 'totals' keys
 * - $frequencyStats: array with 'languages' and 'totals' keys
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Admin;

// Prepare chart data as JSON for the TypeScript module
$intensityChartData = array_map(function ($lang) {
    return [
        'name' => $lang['name'] ?? '',
        's1' => (int)$lang['s1'],
        's2' => (int)$lang['s2'],
        's3' => (int)$lang['s3'],
        's4' => (int)$lang['s4'],
        's5' => (int)$lang['s5'],
        's99' => (int)$lang['s99'],
    ];
}, $intensityStats['languages']);

$frequencyChartTotals = [
    'ct' => (int)$frequencyStats['totals']['ct'],
    'at' => (int)$frequencyStats['totals']['at'],
    'kt' => (int)$frequencyStats['totals']['kt'],
    'cy' => (int)$frequencyStats['totals']['cy'],
    'ay' => (int)$frequencyStats['totals']['ay'],
    'ky' => (int)$frequencyStats['totals']['ky'],
    'cw' => (int)$frequencyStats['totals']['cw'],
    'aw' => (int)$frequencyStats['totals']['aw'],
    'kw' => (int)$frequencyStats['totals']['kw'],
    'cm' => (int)$frequencyStats['totals']['cm'],
    'am' => (int)$frequencyStats['totals']['am'],
    'km' => (int)$frequencyStats['totals']['km'],
    'ca' => (int)$frequencyStats['totals']['ca'],
    'aa' => (int)$frequencyStats['totals']['aa'],
    'ka' => (int)$frequencyStats['totals']['ka'],
];

?>
<!-- Hidden data elements for chart initialization -->
<div id="statistics-intensity-data"
     data-languages="<?php echo htmlspecialchars(json_encode($intensityChartData), ENT_QUOTES, 'UTF-8'); ?>"
     style="display: none;"></div>
<div id="statistics-frequency-data"
     data-totals="<?php echo htmlspecialchars(json_encode($frequencyChartTotals), ENT_QUOTES, 'UTF-8'); ?>"
     style="display: none;"></div>

<h2>Learning Intensity</h2>

<!-- Intensity Chart -->
<div class="stats-chart-container">
    <h3>Term Status Distribution by Language</h3>
    <canvas id="intensityChart" height="100"></canvas>
</div>

<p>Breakdown by Language and Term Status <wbr />(Click on numbers to see the list of terms)</p>
<table class="tab1 stats-table" cellspacing="0" cellpadding="5">
<tbody>
<tr>
    <th class="th1">Language</th>
    <th class="th1" title="All terms in this language">Total<br /></th>
    <th class="th1" title="Terms with status 1-5 (actively learning)">Active<br />(1..5)</th>
    <th class="th1" title="Terms with status 1-4 (not yet learned)">Learning<br />(1..4)</th>
    <th class="th1" title="Status 1: New/Unknown terms">Unknown<br />(1)</th>
    <th class="th1" title="Status 2: Recognized terms">Learning<br />(2)</th>
    <th class="th1" title="Status 3: Familiar terms">Learning<br />(3)</th>
    <th class="th1" title="Status 4: Almost learned terms">Learning<br />(4)</th>
    <th class="th1" title="Status 5: Fully learned terms">Learned<br />(5)</th>
    <th class="th1" title="Status 99: Terms marked as well-known">Well<br />Known<br />(99)</th>
    <th class="th1" title="Status 5 + 99: All known terms">Known<br />(5+99)</th>
    <th class="th1" title="Status 98: Ignored terms">Ign.<br />(98)</th>
</tr>
<?php foreach ($intensityStats['languages'] as $lang): ?>
<tr>
    <td class="td1"><?php echo htmlspecialchars($lang['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
    <td class="td1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $lang['all']; ?></b></a></td>
    <td class="td1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $lang['s15']; ?></b></a></td>
    <td class="td1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $lang['s14']; ?></b></a></td>
    <td class="td1 center"><span class="status1">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=1&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['s1']; ?></a>&nbsp;</span></td>
    <td class="td1 center"><span class="status2">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=2&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['s2']; ?></a>&nbsp;</span></td>
    <td class="td1 center"><span class="status3">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=3&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['s3']; ?></a>&nbsp;</span></td>
    <td class="td1 center"><span class="status4">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=4&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['s4']; ?></a>&nbsp;</span></td>
    <td class="td1 center"><span class="status5">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=5&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['s5']; ?></a>&nbsp;</span></td>
    <td class="td1 center"><span class="status99">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=99&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['s99']; ?></a>&nbsp;</span></td>
    <td class="td1 center"><span class="status5stat">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=599&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $lang['s599']; ?></b></a>&nbsp;</span></td>
    <td class="td1 center"><span class="status98">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=98&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $lang['s98']; ?></b></a>&nbsp;</span></td>
</tr>
<?php endforeach; ?>
<tr>
    <th class="th1"><b>TOTAL</b></th>
    <th class="th1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['all']; ?></b></a></th>
    <th class="th1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s15']; ?></b></a></th>
    <th class="th1 center"><a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s14']; ?></b></a></th>
    <th class="th1 center"><span class="status1">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=1&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s1']; ?></b></a>&nbsp;</span></th>
    <th class="th1 center"><span class="status2">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=2&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s2']; ?></b></a>&nbsp;</span></th>
    <th class="th1 center"><span class="status3">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=3&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s3']; ?></b></a>&nbsp;</span></th>
    <th class="th1 center"><span class="status4">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=4&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s4']; ?></b></a>&nbsp;</span></th>
    <th class="th1 center"><span class="status5">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=5&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s5']; ?></b></a>&nbsp;</span></th>
    <th class="th1 center"><span class="status99">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=99&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s99']; ?></b></a>&nbsp;</span></th>
    <th class="th1 center"><span class="status5stat">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=599&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s599']; ?></b></a>&nbsp;</span></th>
    <th class="th1 center"><span class="status98">&nbsp;<a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=98&amp;tag12=0&amp;tag2=&amp;tag1="><b><?php echo $intensityStats['totals']['s98']; ?></b></a>&nbsp;</span></th>
</tr>
</tbody>
</table>

<h2>Learning Frequency</h2>

<!-- Frequency Chart -->
<div class="stats-chart-container">
    <h3>Learning Activity Over Time (All Languages)</h3>
    <canvas id="frequencyChart" height="80"></canvas>
</div>

<p>Breakdown by Language and Time Range <wbr />(Terms created (C), Terms changed status = Activity (A), Terms set to "Known" (K))</p>
<table class="tab1 stats-table" cellspacing="0" cellpadding="5">
<tbody>
<tr>
    <th class="th1" rowspan="2">Language</th>
    <th class="th1 today-col" colspan="3">Today</th>
    <th class="th1" colspan="3">Yesterday</th>
    <th class="th1" colspan="3">Last 7 d</th>
    <th class="th1" colspan="3">Last 30 d</th>
    <th class="th1" colspan="3">Last 365 d</th>
    <th class="th1" colspan="3">All Time</th>
</tr>
<tr>
    <th class="th1 today-col" title="Created: New terms added today">C</th>
    <th class="th1 today-col" title="Activity: Terms with status changes today">A</th>
    <th class="th1 today-col" title="Known: Terms marked as known (status 5 or 99) today">K</th>
    <th class="th1" title="Created: New terms added yesterday">C</th>
    <th class="th1" title="Activity: Terms with status changes yesterday">A</th>
    <th class="th1" title="Known: Terms marked as known yesterday">K</th>
    <th class="th1" title="Created: New terms added in last 7 days">C</th>
    <th class="th1" title="Activity: Terms with status changes in last 7 days">A</th>
    <th class="th1" title="Known: Terms marked as known in last 7 days">K</th>
    <th class="th1" title="Created: New terms added in last 30 days">C</th>
    <th class="th1" title="Activity: Terms with status changes in last 30 days">A</th>
    <th class="th1" title="Known: Terms marked as known in last 30 days">K</th>
    <th class="th1" title="Created: New terms added in last 365 days">C</th>
    <th class="th1" title="Activity: Terms with status changes in last 365 days">A</th>
    <th class="th1" title="Known: Terms marked as known in last 365 days">K</th>
    <th class="th1" title="Created: Total terms ever created">C</th>
    <th class="th1" title="Activity: Total status changes ever">A</th>
    <th class="th1" title="Known: Total terms ever marked as known">K</th>
</tr>
<?php foreach ($frequencyStats['languages'] as $lang): ?>
<tr>
    <td class="td1"><?php echo htmlspecialchars($lang['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>

    <td class="td1 center today-col"><span class="status1">&nbsp;<?php echo $lang['ct']; ?>&nbsp;</span></td>
    <td class="td1 center today-col"><span class="status3">&nbsp;<?php echo $lang['at']; ?>&nbsp;</span></td>
    <td class="td1 center today-col"><span class="status5stat">&nbsp;<?php echo $lang['kt']; ?>&nbsp;</span></td>

    <td class="td1 center"><span class="status1">&nbsp;<?php echo $lang['cy']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status3">&nbsp;<?php echo $lang['ay']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status5stat">&nbsp;<?php echo $lang['ky']; ?>&nbsp;</span></td>

    <td class="td1 center"><span class="status1">&nbsp;<?php echo $lang['cw']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status3">&nbsp;<?php echo $lang['aw']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status5stat">&nbsp;<?php echo $lang['kw']; ?>&nbsp;</span></td>

    <td class="td1 center"><span class="status1">&nbsp;<?php echo $lang['cm']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status3">&nbsp;<?php echo $lang['am']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status5stat">&nbsp;<?php echo $lang['km']; ?>&nbsp;</span></td>

    <td class="td1 center"><span class="status1">&nbsp;<?php echo $lang['ca']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status3">&nbsp;<?php echo $lang['aa']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status5stat">&nbsp;<?php echo $lang['ka']; ?>&nbsp;</span></td>

    <td class="td1 center"><span class="status1">&nbsp;<?php echo $lang['call']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status3">&nbsp;<?php echo $lang['aall']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status5stat">&nbsp;<?php echo $lang['kall']; ?>&nbsp;</span></td>
</tr>
<?php endforeach; ?>
<tr>
    <th class="th1"><b>TOTAL</b></th>

    <th class="th1 center today-col"><span class="status1">&nbsp;<?php echo $frequencyStats['totals']['ct']; ?>&nbsp;</span></th>
    <th class="th1 center today-col"><span class="status3">&nbsp;<?php echo $frequencyStats['totals']['at']; ?>&nbsp;</span></th>
    <th class="th1 center today-col"><span class="status5stat">&nbsp;<?php echo $frequencyStats['totals']['kt']; ?>&nbsp;</span></th>

    <th class="th1 center"><span class="status1">&nbsp;<?php echo $frequencyStats['totals']['cy']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status3">&nbsp;<?php echo $frequencyStats['totals']['ay']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status5stat">&nbsp;<?php echo $frequencyStats['totals']['ky']; ?>&nbsp;</span></th>

    <th class="th1 center"><span class="status1">&nbsp;<?php echo $frequencyStats['totals']['cw']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status3">&nbsp;<?php echo $frequencyStats['totals']['aw']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status5stat">&nbsp;<?php echo $frequencyStats['totals']['kw']; ?>&nbsp;</span></th>

    <th class="th1 center"><span class="status1">&nbsp;<?php echo $frequencyStats['totals']['cm']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status3">&nbsp;<?php echo $frequencyStats['totals']['am']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status5stat">&nbsp;<?php echo $frequencyStats['totals']['km']; ?>&nbsp;</span></th>

    <th class="th1 center"><span class="status1">&nbsp;<?php echo $frequencyStats['totals']['ca']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status3">&nbsp;<?php echo $frequencyStats['totals']['aa']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status5stat">&nbsp;<?php echo $frequencyStats['totals']['ka']; ?>&nbsp;</span></th>

    <th class="th1 center"><span class="status1">&nbsp;<?php echo $frequencyStats['totals']['call']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status3">&nbsp;<?php echo $frequencyStats['totals']['aall']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status5stat">&nbsp;<?php echo $frequencyStats['totals']['kall']; ?>&nbsp;</span></th>
</tr>
</tbody>
</table>
<p>
    <input type="button" value="&lt;&lt; Back" data-action="navigate" data-url="/" />
</p>
