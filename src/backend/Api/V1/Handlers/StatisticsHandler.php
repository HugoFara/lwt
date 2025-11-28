<?php

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
     * @param string $textsId Comma-separated text IDs
     *
     * @return array Text word count statistics
     */
    public function formatTextsStatistics(string $textsId): array
    {
        return $this->getTextsStatistics($textsId);
    }
}
