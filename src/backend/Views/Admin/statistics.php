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
<?php foreach ($intensityStats['languages'] as $lang): ?>
<tr>
    <td class="td1"><?php echo tohtml($lang['name']); ?></td>
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
</table>

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
<?php foreach ($frequencyStats['languages'] as $lang): ?>
<tr>
    <td class="td1"><?php echo tohtml($lang['name']); ?></td>

    <td class="td1 center"><span class="status1">&nbsp;<?php echo $lang['ct']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status3">&nbsp;<?php echo $lang['at']; ?>&nbsp;</span></td>
    <td class="td1 center"><span class="status5stat">&nbsp;<?php echo $lang['kt']; ?>&nbsp;</span></td>

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

    <th class="th1 center"><span class="status1">&nbsp;<?php echo $frequencyStats['totals']['ct']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status3">&nbsp;<?php echo $frequencyStats['totals']['at']; ?>&nbsp;</span></th>
    <th class="th1 center"><span class="status5stat">&nbsp;<?php echo $frequencyStats['totals']['kt']; ?>&nbsp;</span></th>

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
</table>
<p>
    <input type="button" value="&lt;&lt; Back" data-action="navigate" data-url="/" />
</p>
