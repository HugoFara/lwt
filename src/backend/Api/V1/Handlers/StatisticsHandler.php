<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

/**
 * Handler for statistics-related API operations.
 *
 * Extracted from api_v1.php.
 */
class StatisticsHandler
{
    /**
     * Return statistics about a group of texts.
     *
     * @param string $textsId Comma-separated text IDs
     *
     * @return array Text word count statistics
     */
    public function getTextsStatistics(string $textsId): array
    {
        return \return_textwordcount($textsId);
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for texts statistics.
     *
     * Transforms the raw statistics data into a format expected by the frontend:
     * - total: unique word count
     * - saved: count of words with any status (1-5, 98, 99)
     * - unknown: count of words without a saved status
     * - unknownPercent: percentage of unknown words
     * - statusCounts: word counts by status
     *
     * @param string $textsId Comma-separated text IDs
     *
     * @return array<string, array{total: int, saved: int, unknown: int, unknownPercent: int, statusCounts: array<string, int>}>
     */
    public function formatTextsStatistics(string $textsId): array
    {
        $raw = $this->getTextsStatistics($textsId);
        $result = [];

        // Get all text IDs from the request
        $textIds = array_map('intval', explode(',', $textsId));

        foreach ($textIds as $textId) {
            $textIdStr = (string) $textId;

            // Get unique word count (totalu)
            $total = isset($raw['totalu'][$textIdStr])
                ? (int) $raw['totalu'][$textIdStr]
                : 0;

            // Sum saved words from status counts (statu)
            $saved = 0;
            $statusCounts = [];
            if (isset($raw['statu'][$textIdStr])) {
                foreach ($raw['statu'][$textIdStr] as $status => $count) {
                    $saved += (int) $count;
                    $statusCounts[(string) $status] = (int) $count;
                }
            }

            // Unknown = total unique - saved unique
            $unknown = $total - $saved;

            // Calculate unknown percentage
            $unknownPercent = $total > 0
                ? (int) round(($unknown / $total) * 100)
                : 0;

            $result[$textIdStr] = [
                'total' => $total,
                'saved' => $saved,
                'unknown' => $unknown,
                'unknownPercent' => $unknownPercent,
                'statusCounts' => $statusCounts
            ];
        }

        return $result;
    }
}
