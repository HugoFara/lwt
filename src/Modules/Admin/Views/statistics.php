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

use Lwt\Shared\UI\Helpers\IconHelper;

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
        </div>
    </section>

    <!-- Learning Frequency Section -->
    <section class="box mb-4" x-data="{ open: true }">
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
