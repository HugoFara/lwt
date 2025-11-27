<?php

/**
 * Display statistics
 *
 * Call: /admin/statistics
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

namespace Lwt\Interface\Statistics;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';

use Lwt\Services\StatisticsService;

require_once __DIR__ . '/../Services/StatisticsService.php';

// Initialize service and get data (used by included view)
$statisticsService = new StatisticsService();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$intensityStats = $statisticsService->getIntensityStatistics();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$frequencyStats = $statisticsService->getFrequencyStatistics();

// Render page
pagestart('My Statistics', true);

// Include the view
include __DIR__ . '/../Views/Admin/statistics.php';

pageend();
