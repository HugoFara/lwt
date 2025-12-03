<?php declare(strict_types=1);
/**
 * Statistics View
 *
 * Modern Bulma + Alpine.js version of the statistics page.
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

use Lwt\View\Helper\IconHelper;

// Prepare chart data as JSON for the Alpine/Chart.js module
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
<div class="container" x-data="statisticsApp()">
    <!-- Hidden data elements for Chart.js initialization -->
    <script type="application/json" id="statistics-intensity-data">
    <?php echo json_encode(['languages' => $intensityChartData]); ?>
    </script>
    <script type="application/json" id="statistics-frequency-data">
    <?php echo json_encode(['totals' => $frequencyChartTotals]); ?>
    </script>

    <!-- Learning Intensity Section -->
    <section class="box mb-4" x-data="{ open: true }">
        <header class="collapsible-header" @click="open = !open">
            <h2 class="title is-4 mb-0">
                <span class="icon-text">
                    <span class="icon has-text-info">
                        <?php echo IconHelper::render('bar-chart-2', ['class' => 'icon']); ?>
                    </span>
                    <span>Learning Intensity</span>
                </span>
            </h2>
            <span class="icon collapse-icon" :class="{ 'is-rotated': open }">
                <?php echo IconHelper::render('chevron-down'); ?>
            </span>
        </header>

        <div class="collapsible-content" x-show="open" x-collapse>
        <!-- Intensity Chart -->
        <div class="box mb-4 mt-4">
            <h3 class="subtitle is-6">Term Status Distribution by Language</h3>
            <canvas id="intensityChart" height="100"></canvas>
        </div>

        <!-- Intensity Data Table -->
        <div class="box mt-4">
            <p class="mb-3">
                Breakdown by Language and Term Status
                <span class="has-text-grey is-size-7">(Click on numbers to see the list of terms)</span>
            </p>
            <div class="table-container">
                <table class="table is-striped is-hoverable is-narrow is-fullwidth">
                    <thead>
                        <tr>
                            <th>Language</th>
                            <th class="has-text-centered" title="All terms in this language">Total</th>
                            <th class="has-text-centered" title="Terms with status 1-5 (actively learning)">Active<br /><span class="is-size-7">(1..5)</span></th>
                            <th class="has-text-centered" title="Terms with status 1-4 (not yet learned)">Learning<br /><span class="is-size-7">(1..4)</span></th>
                            <th class="has-text-centered" title="Status 1: New/Unknown terms">Unknown<br /><span class="is-size-7">(1)</span></th>
                            <th class="has-text-centered" title="Status 2: Recognized terms">Learning<br /><span class="is-size-7">(2)</span></th>
                            <th class="has-text-centered" title="Status 3: Familiar terms">Learning<br /><span class="is-size-7">(3)</span></th>
                            <th class="has-text-centered" title="Status 4: Almost learned terms">Learning<br /><span class="is-size-7">(4)</span></th>
                            <th class="has-text-centered" title="Status 5: Fully learned terms">Learned<br /><span class="is-size-7">(5)</span></th>
                            <th class="has-text-centered" title="Status 99: Terms marked as well-known">Well Known<br /><span class="is-size-7">(99)</span></th>
                            <th class="has-text-centered" title="Status 5 + 99: All known terms">Known<br /><span class="is-size-7">(5+99)</span></th>
                            <th class="has-text-centered" title="Status 98: Ignored terms">Ignored<br /><span class="is-size-7">(98)</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($intensityStats['languages'] as $lang): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lang['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <strong><?php echo $lang['all']; ?></strong>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <strong><?php echo $lang['s15']; ?></strong>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <strong><?php echo $lang['s14']; ?></strong>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=1&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-1"><?php echo $lang['s1']; ?></span>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=2&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-2"><?php echo $lang['s2']; ?></span>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=3&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-3"><?php echo $lang['s3']; ?></span>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=4&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-4"><?php echo $lang['s4']; ?></span>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=5&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-5"><?php echo $lang['s5']; ?></span>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=99&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-99"><?php echo $lang['s99']; ?></span>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=599&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-known"><strong><?php echo $lang['s599']; ?></strong></span>
                                </a>
                            </td>
                            <td class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=98&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <span class="tag status-98"><strong><?php echo $lang['s98']; ?></strong></span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="has-background-light">
                            <th><strong>TOTAL</strong></th>
                            <th class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <strong><?php echo $intensityStats['totals']['all']; ?></strong>
                                </a>
                            </th>
                            <th class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <strong><?php echo $intensityStats['totals']['s15']; ?></strong>
                                </a>
                            </th>
                            <th class="has-text-centered">
                                <a href="/words/edit?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1=">
                                    <strong><?php echo $intensityStats['totals']['s14']; ?></strong>
                                </a>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-1"><strong><?php echo $intensityStats['totals']['s1']; ?></strong></span>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-2"><strong><?php echo $intensityStats['totals']['s2']; ?></strong></span>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-3"><strong><?php echo $intensityStats['totals']['s3']; ?></strong></span>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-4"><strong><?php echo $intensityStats['totals']['s4']; ?></strong></span>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-5"><strong><?php echo $intensityStats['totals']['s5']; ?></strong></span>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-99"><strong><?php echo $intensityStats['totals']['s99']; ?></strong></span>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-known"><strong><?php echo $intensityStats['totals']['s599']; ?></strong></span>
                            </th>
                            <th class="has-text-centered">
                                <span class="tag status-98"><strong><?php echo $intensityStats['totals']['s98']; ?></strong></span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        </div>
    </section>

    <!-- Learning Frequency Section -->
    <section class="box mb-4" x-data="{ open: false }">
        <header class="collapsible-header" @click="open = !open">
            <h2 class="title is-4 mb-0">
                <span class="icon-text">
                    <span class="icon has-text-success">
                        <?php echo IconHelper::render('trending-up', ['class' => 'icon']); ?>
                    </span>
                    <span>Learning Frequency</span>
                </span>
            </h2>
            <span class="icon collapse-icon" :class="{ 'is-rotated': open }">
                <?php echo IconHelper::render('chevron-down'); ?>
            </span>
        </header>

        <div class="collapsible-content" x-show="open" x-collapse>
        <!-- Frequency Chart -->
        <div class="box mb-4 mt-4">
            <h3 class="subtitle is-6">Learning Activity Over Time (All Languages)</h3>
            <canvas id="frequencyChart" height="80"></canvas>
        </div>

        <!-- Frequency Data Table -->
        <div class="box mt-4">
            <p class="mb-3">
                Breakdown by Language and Time Range
                <span class="has-text-grey is-size-7">
                    (C = Created, A = Activity/Status Change, K = Known)
                </span>
            </p>
            <div class="table-container">
                <table class="table is-striped is-hoverable is-narrow is-fullwidth">
                    <thead>
                        <tr>
                            <th rowspan="2">Language</th>
                            <th class="has-text-centered has-background-info-light" colspan="3">Today</th>
                            <th class="has-text-centered" colspan="3">Yesterday</th>
                            <th class="has-text-centered" colspan="3">Last 7 d</th>
                            <th class="has-text-centered" colspan="3">Last 30 d</th>
                            <th class="has-text-centered" colspan="3">Last 365 d</th>
                            <th class="has-text-centered" colspan="3">All Time</th>
                        </tr>
                        <tr>
                            <th class="has-text-centered has-background-info-light" title="Created: New terms added today">C</th>
                            <th class="has-text-centered has-background-info-light" title="Activity: Terms with status changes today">A</th>
                            <th class="has-text-centered has-background-info-light" title="Known: Terms marked as known today">K</th>
                            <th class="has-text-centered" title="Created: New terms added yesterday">C</th>
                            <th class="has-text-centered" title="Activity: Terms with status changes yesterday">A</th>
                            <th class="has-text-centered" title="Known: Terms marked as known yesterday">K</th>
                            <th class="has-text-centered" title="Created: New terms added in last 7 days">C</th>
                            <th class="has-text-centered" title="Activity: Terms with status changes in last 7 days">A</th>
                            <th class="has-text-centered" title="Known: Terms marked as known in last 7 days">K</th>
                            <th class="has-text-centered" title="Created: New terms added in last 30 days">C</th>
                            <th class="has-text-centered" title="Activity: Terms with status changes in last 30 days">A</th>
                            <th class="has-text-centered" title="Known: Terms marked as known in last 30 days">K</th>
                            <th class="has-text-centered" title="Created: New terms added in last 365 days">C</th>
                            <th class="has-text-centered" title="Activity: Terms with status changes in last 365 days">A</th>
                            <th class="has-text-centered" title="Known: Terms marked as known in last 365 days">K</th>
                            <th class="has-text-centered" title="Created: Total terms ever created">C</th>
                            <th class="has-text-centered" title="Activity: Total status changes ever">A</th>
                            <th class="has-text-centered" title="Known: Total terms ever marked as known">K</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($frequencyStats['languages'] as $lang): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lang['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <!-- Today -->
                            <td class="has-text-centered has-background-info-light"><span class="tag status-1"><?php echo $lang['ct']; ?></span></td>
                            <td class="has-text-centered has-background-info-light"><span class="tag status-3"><?php echo $lang['at']; ?></span></td>
                            <td class="has-text-centered has-background-info-light"><span class="tag status-known"><?php echo $lang['kt']; ?></span></td>
                            <!-- Yesterday -->
                            <td class="has-text-centered"><span class="tag status-1"><?php echo $lang['cy']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-3"><?php echo $lang['ay']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-known"><?php echo $lang['ky']; ?></span></td>
                            <!-- Last 7 days -->
                            <td class="has-text-centered"><span class="tag status-1"><?php echo $lang['cw']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-3"><?php echo $lang['aw']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-known"><?php echo $lang['kw']; ?></span></td>
                            <!-- Last 30 days -->
                            <td class="has-text-centered"><span class="tag status-1"><?php echo $lang['cm']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-3"><?php echo $lang['am']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-known"><?php echo $lang['km']; ?></span></td>
                            <!-- Last 365 days -->
                            <td class="has-text-centered"><span class="tag status-1"><?php echo $lang['ca']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-3"><?php echo $lang['aa']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-known"><?php echo $lang['ka']; ?></span></td>
                            <!-- All time -->
                            <td class="has-text-centered"><span class="tag status-1"><?php echo $lang['call']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-3"><?php echo $lang['aall']; ?></span></td>
                            <td class="has-text-centered"><span class="tag status-known"><?php echo $lang['kall']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="has-background-light">
                            <th><strong>TOTAL</strong></th>
                            <!-- Today -->
                            <th class="has-text-centered has-background-info-light"><span class="tag status-1"><?php echo $frequencyStats['totals']['ct']; ?></span></th>
                            <th class="has-text-centered has-background-info-light"><span class="tag status-3"><?php echo $frequencyStats['totals']['at']; ?></span></th>
                            <th class="has-text-centered has-background-info-light"><span class="tag status-known"><?php echo $frequencyStats['totals']['kt']; ?></span></th>
                            <!-- Yesterday -->
                            <th class="has-text-centered"><span class="tag status-1"><?php echo $frequencyStats['totals']['cy']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-3"><?php echo $frequencyStats['totals']['ay']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-known"><?php echo $frequencyStats['totals']['ky']; ?></span></th>
                            <!-- Last 7 days -->
                            <th class="has-text-centered"><span class="tag status-1"><?php echo $frequencyStats['totals']['cw']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-3"><?php echo $frequencyStats['totals']['aw']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-known"><?php echo $frequencyStats['totals']['kw']; ?></span></th>
                            <!-- Last 30 days -->
                            <th class="has-text-centered"><span class="tag status-1"><?php echo $frequencyStats['totals']['cm']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-3"><?php echo $frequencyStats['totals']['am']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-known"><?php echo $frequencyStats['totals']['km']; ?></span></th>
                            <!-- Last 365 days -->
                            <th class="has-text-centered"><span class="tag status-1"><?php echo $frequencyStats['totals']['ca']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-3"><?php echo $frequencyStats['totals']['aa']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-known"><?php echo $frequencyStats['totals']['ka']; ?></span></th>
                            <!-- All time -->
                            <th class="has-text-centered"><span class="tag status-1"><?php echo $frequencyStats['totals']['call']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-3"><?php echo $frequencyStats['totals']['aall']; ?></span></th>
                            <th class="has-text-centered"><span class="tag status-known"><?php echo $frequencyStats['totals']['kall']; ?></span></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        </div>
    </section>

    <!-- Back Button -->
    <div class="field">
        <div class="control">
            <a href="/" class="button is-light">
                <?php echo IconHelper::render('arrow-left'); ?>
                <span>Back to Main Menu</span>
            </a>
        </div>
    </div>
</div>

<style>
/* Collapsible section styles */
.collapsible-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.collapsible-header:hover {
    opacity: 0.8;
}

.collapse-icon {
    transition: transform 0.2s ease;
}

.collapse-icon.is-rotated {
    transform: rotate(180deg);
}

/* Statistics status tags - matching LWT's existing status colors */
.tag.status-1 { background-color: #F5B8A9; color: #000; }
.tag.status-2 { background-color: #F5CCA9; color: #000; }
.tag.status-3 { background-color: #F5E1A9; color: #000; }
.tag.status-4 { background-color: #F5F3A9; color: #000; }
.tag.status-5 { background-color: #CCFFCC; color: #000; }
.tag.status-98 { background-color: #E5E5E5; color: #000; }
.tag.status-99 { background-color: #99DDDF; color: #000; }
.tag.status-known { background-color: #AADDAA; color: #000; }
</style>
