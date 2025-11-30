<?php declare(strict_types=1);
/**
 * Statistics Service - Business logic for learning statistics
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Database\Connection;

/**
 * Service class for computing and retrieving learning statistics.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class StatisticsService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Get term statistics grouped by language and status.
     *
     * @return array{languages: array, totals: array} Statistics data
     */
    public function getIntensityStatistics(): array
    {
        $termStat = $this->getTermCountsByLanguageAndStatus();
        $languages = $this->getLanguageList();

        $totals = [
            's1' => 0, 's2' => 0, 's3' => 0, 's4' => 0, 's5' => 0,
            's98' => 0, 's99' => 0, 's14' => 0, 's15' => 0, 's599' => 0, 'all' => 0
        ];

        $languageStats = [];

        foreach ($languages as $language) {
            $lgId = (string)$language['LgID'];

            $s1 = $termStat[$lgId][1] ?? 0;
            $s2 = $termStat[$lgId][2] ?? 0;
            $s3 = $termStat[$lgId][3] ?? 0;
            $s4 = $termStat[$lgId][4] ?? 0;
            $s5 = $termStat[$lgId][5] ?? 0;
            $s98 = $termStat[$lgId][98] ?? 0;
            $s99 = $termStat[$lgId][99] ?? 0;
            $s14 = $s1 + $s2 + $s3 + $s4;
            $s15 = $s14 + $s5;
            $s599 = $s5 + $s99;
            $all = $s15 + $s98 + $s99;

            $languageStats[] = [
                'id' => $lgId,
                'name' => $language['LgName'],
                's1' => $s1, 's2' => $s2, 's3' => $s3, 's4' => $s4, 's5' => $s5,
                's98' => $s98, 's99' => $s99, 's14' => $s14, 's15' => $s15,
                's599' => $s599, 'all' => $all
            ];

            $totals['s1'] += $s1;
            $totals['s2'] += $s2;
            $totals['s3'] += $s3;
            $totals['s4'] += $s4;
            $totals['s5'] += $s5;
            $totals['s98'] += $s98;
            $totals['s99'] += $s99;
            $totals['s14'] += $s14;
            $totals['s15'] += $s15;
            $totals['s599'] += $s599;
            $totals['all'] += $all;
        }

        return [
            'languages' => $languageStats,
            'totals' => $totals
        ];
    }

    /**
     * Get frequency statistics (terms created, active, known by time range).
     *
     * @return array{languages: array, totals: array} Frequency statistics
     */
    public function getFrequencyStatistics(): array
    {
        $termCreated = $this->getTermsCreatedByDay();
        $termActive = [];
        $termKnown = [];
        $this->getTermActivityByDay($termActive, $termKnown);

        $languages = $this->getLanguageList();

        $totals = [
            'ct' => 0, 'at' => 0, 'kt' => 0,
            'cy' => 0, 'ay' => 0, 'ky' => 0,
            'cw' => 0, 'aw' => 0, 'kw' => 0,
            'cm' => 0, 'am' => 0, 'km' => 0,
            'ca' => 0, 'aa' => 0, 'ka' => 0,
            'call' => 0, 'aall' => 0, 'kall' => 0
        ];

        $languageStats = [];

        foreach ($languages as $language) {
            $lgId = (int)$language['LgID'];

            $stats = $this->calculateFrequencyForLanguage(
                $lgId,
                $termCreated[$lgId] ?? [],
                $termActive[$lgId] ?? [],
                $termKnown[$lgId] ?? []
            );

            $stats['id'] = $lgId;
            $stats['name'] = $language['LgName'];
            $languageStats[] = $stats;

            foreach ($stats as $key => $value) {
                if (isset($totals[$key])) {
                    $totals[$key] += $value;
                }
            }
        }

        return [
            'languages' => $languageStats,
            'totals' => $totals
        ];
    }

    /**
     * Get term counts grouped by language and status.
     *
     * @return array<string, array<string, int>> Term counts
     */
    private function getTermCountsByLanguageAndStatus(): array
    {
        $sql = "SELECT WoLgID, WoStatus, count(*) AS value
                FROM {$this->tbpref}words GROUP BY WoLgID, WoStatus";
        $res = Connection::query($sql);

        $termStat = [];
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $lgId = (string)$record['WoLgID'];
                $status = (string)$record['WoStatus'];
                $termStat[$lgId][$status] = (int)$record['value'];
            }
            mysqli_free_result($res);
        }
        return $termStat;
    }

    /**
     * Get list of languages.
     *
     * @return array Language records
     */
    private function getLanguageList(): array
    {
        $sql = "SELECT LgID, LgName FROM {$this->tbpref}languages
                WHERE LgName <> '' ORDER BY LgName";
        $res = Connection::query($sql);

        $languages = [];
        if ($res instanceof \mysqli_result) {
            while ($record = mysqli_fetch_assoc($res)) {
                $languages[] = $record;
            }
            mysqli_free_result($res);
        }
        return $languages;
    }

    /**
     * Get terms created grouped by language and days ago.
     *
     * @return array<int, array<int, int>> Terms by language and day
     */
    private function getTermsCreatedByDay(): array
    {
        $sql = "SELECT WoLgID, TO_DAYS(curdate()) - TO_DAYS(cast(WoCreated as date)) Created,
                count(WoID) as value
                FROM {$this->tbpref}words
                WHERE WoStatus IN (1,2,3,4,5,99)
                GROUP BY WoLgID, Created";
        $res = Connection::query($sql);

        $termCreated = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $termCreated[$record['WoLgID']][$record['Created']] = $record['value'];
        }
        return $termCreated;
    }

    /**
     * Get term activity grouped by language and days ago.
     *
     * @param array &$termActive Reference to store active terms
     * @param array &$termKnown  Reference to store known terms
     *
     * @return void
     */
    private function getTermActivityByDay(array &$termActive, array &$termKnown): void
    {
        $sql = "SELECT WoLgID, WoStatus,
                TO_DAYS(curdate()) - TO_DAYS(cast(WoStatusChanged as date)) Changed,
                count(WoID) as value
                FROM {$this->tbpref}words
                GROUP BY WoLgID, WoStatus, WoStatusChanged";
        $res = Connection::query($sql);

        while ($record = mysqli_fetch_assoc($res)) {
            if (!empty($record['WoStatus'])) {
                $lgId = $record['WoLgID'];
                $changed = $record['Changed'];
                $value = (int)$record['value'];

                if ($record['WoStatus'] == 5 || $record['WoStatus'] == 99) {
                    if (!isset($termKnown[$lgId][$changed])) {
                        $termKnown[$lgId][$changed] = 0;
                    }
                    $termKnown[$lgId][$changed] += $value;

                    if (!isset($termActive[$lgId][$changed])) {
                        $termActive[$lgId][$changed] = 0;
                    }
                    $termActive[$lgId][$changed] += $value;
                } elseif ($record['WoStatus'] > 0 && $record['WoStatus'] < 5) {
                    if (!isset($termActive[$lgId][$changed])) {
                        $termActive[$lgId][$changed] = 0;
                    }
                    $termActive[$lgId][$changed] += $value;
                }
            }
        }
    }

    /**
     * Calculate frequency statistics for a single language.
     *
     * @param int   $lgId        Language ID
     * @param array $termCreated Terms created data
     * @param array $termActive  Terms active data
     * @param array $termKnown   Terms known data
     *
     * @return array Frequency statistics
     */
    private function calculateFrequencyForLanguage(
        int $lgId,
        array $termCreated,
        array $termActive,
        array $termKnown
    ): array {
        // $lgId is kept in signature for consistency and potential future use
        unset($lgId);
        // Calculate created stats
        $cw = 0;
        $cm = 0;
        $ca = 0;
        $call = 0;

        foreach ($termCreated as $created => $val) {
            if ($created === '0') {
                $cw += $val;
            } elseif ($created > 364) {
                $call += $val;
            } elseif ($created > 29) {
                $ca += $val;
            } elseif ($created > 6) {
                $cm += $val;
            } else {
                $cw += $val;
            }
        }

        $ct = $termCreated[0] ?? 0;
        $cy = $termCreated[1] ?? 0;
        $cm += $cw;
        $ca += $cm;
        $call += $ca;

        // Calculate active stats
        $aw = 0;
        $am = 0;
        $aa = 0;
        $aall = 0;

        foreach ($termActive as $active => $val) {
            if ($active === '0') {
                $aw += $val;
            } elseif ($active > 364) {
                $aall += $val;
            } elseif ($active > 29) {
                $aa += $val;
            } elseif ($active > 6) {
                $am += $val;
            } else {
                $aw += $val;
            }
        }

        $at = $termActive[0] ?? 0;
        $ay = $termActive[1] ?? 0;
        $am += $aw;
        $aa += $am;
        $aall += $aa;

        // Calculate known stats
        $kw = 0;
        $km = 0;
        $ka = 0;
        $kall = 0;

        foreach ($termKnown as $known => $val) {
            if ($known === '0') {
                $kw += $val;
            } elseif ($known > 364) {
                $kall += $val;
            } elseif ($known > 29) {
                $ka += $val;
            } elseif ($known > 6) {
                $km += $val;
            } else {
                $kw += $val;
            }
        }

        $kt = $termKnown[0] ?? 0;
        $ky = $termKnown[1] ?? 0;
        $km += $kw;
        $ka += $km;
        $kall += $ka;

        return [
            'ct' => $ct, 'at' => $at, 'kt' => $kt,
            'cy' => $cy, 'ay' => $ay, 'ky' => $ky,
            'cw' => $cw, 'aw' => $aw, 'kw' => $kw,
            'cm' => $cm, 'am' => $am, 'km' => $km,
            'ca' => $ca, 'aa' => $aa, 'ka' => $ka,
            'call' => $call, 'aall' => $aall, 'kall' => $kall
        ];
    }
}
