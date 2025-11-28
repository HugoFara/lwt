<?php

/**
 * \file
 * \brief Builder for HTML select option elements.
 *
 * PHP version 8.1
 *
 * @category View
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/src-backend-View-Helper-SelectOptionsBuilder.html
 * @since    3.0.0
 */

namespace Lwt\View\Helper;

/**
 * Builder class for generating HTML select options.
 *
 * Provides methods for building various types of select option lists
 * used throughout the application.
 *
 * @since 3.0.0
 */
class SelectOptionsBuilder
{
    /**
     * Build seconds selection options (1-10 seconds).
     *
     * @param int|string|null $selected Currently selected value (default: 5)
     *
     * @return string HTML options string
     */
    public static function forSeconds(int|string|null $selected = null): string
    {
        $selected = $selected ?? 5;
        $result = '';
        for ($i = 1; $i <= 10; $i++) {
            $result .= FormHelper::buildOption($i, $i . ' sec', $selected);
        }
        return $result;
    }

    /**
     * Build playback rate selection options (0.5x to 1.5x).
     *
     * @param int|string|null $selected Currently selected value (default: '10' = 1.0x)
     *
     * @return string HTML options string
     */
    public static function forPlaybackRate(int|string|null $selected = null): string
    {
        $selected = $selected ?? '10';
        $result = '';
        for ($i = 5; $i <= 15; $i++) {
            $text = $i < 10 ? ' 0.' . $i . ' x ' : ' 1.' . ($i - 10) . ' x ';
            $result .= '<option value="' . $i . '"' . FormHelper::getSelected($selected, $i);
            $result .= '>&nbsp;' . $text . '&nbsp;</option>';
        }
        return $result;
    }

    /**
     * Build mobile display mode options.
     *
     * @param string|null $selected Current mobile type ("0", "1", or "2")
     *
     * @return string HTML options string
     */
    public static function forMobileDisplayMode(?string $selected = null): string
    {
        $selected = $selected ?? '0';
        $options = [
            '0' => 'Auto',
            '1' => 'Force Non-Mobile',
            '2' => 'Force Mobile'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build sentence count selection options.
     *
     * @param int|string|null $selected Currently selected value (default: 1)
     *
     * @return string HTML options string
     */
    public static function forSentenceCount(int|string|null $selected = null): string
    {
        $selected = $selected ?? 1;
        $options = [
            1 => 'Just ONE',
            2 => 'TWO (+previous)',
            3 => 'THREE (+previous,+next)'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build "words to do" button options.
     *
     * @param int|string|null $selected Currently selected value (default: "1")
     *
     * @return string HTML options string
     */
    public static function forWordsToDoButtons(int|string|null $selected = null): string
    {
        $selected = $selected ?? '1';
        $options = [
            '0' => 'I Know All & Ignore All',
            '1' => 'I Know All',
            '2' => 'Ignore All'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build regex mode selection options.
     *
     * @param string|null $selected Currently selected value
     *
     * @return string HTML options string
     */
    public static function forRegexMode(?string $selected = null): string
    {
        $selected = $selected ?? '';
        $options = [
            '' => 'Default',
            'r' => 'RegEx',
            "COLLATE 'utf8_bin' r" => 'RegEx CaseSensitive'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build tooltip type selection options.
     *
     * @param int|string|null $selected Currently selected value (default: 1)
     *
     * @return string HTML options string
     */
    public static function forTooltipType(int|string|null $selected = null): string
    {
        $selected = $selected ?? 1;
        $options = [
            1 => 'Native',
            2 => 'JqueryUI'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build language size selection options (100% to 250%).
     *
     * @param int|string|null $selected Currently selected value (default: 100)
     *
     * @return string HTML options string
     */
    public static function forLanguageSize(int|string|null $selected = null): string
    {
        $selected = $selected ?? 100;
        $options = [
            100 => '100 %',
            150 => '150 %',
            200 => '200 %',
            250 => '250 %'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build annotation position selection options.
     *
     * @param int|string|null $selected Currently selected value (default: 1)
     *
     * @return string HTML options string
     */
    public static function forAnnotationPosition(int|string|null $selected = null): string
    {
        $selected = $selected ?? 1;
        $options = [
            1 => 'Behind',
            3 => 'In Front Of',
            2 => 'Below',
            4 => 'Above'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build hover/click translation settings options.
     *
     * @param int|string|null $selected Currently selected value (default: 1)
     *
     * @return string HTML options string
     */
    public static function forHoverTranslation(int|string|null $selected = null): string
    {
        $selected = $selected ?? 1;
        $options = [
            1 => 'Never',
            2 => 'On Click',
            3 => 'On Hover'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build pagination options.
     *
     * @param int $currentPage Current page number
     * @param int $totalPages  Total number of pages
     *
     * @return string HTML options string
     */
    public static function forPagination(int $currentPage, int $totalPages): string
    {
        $result = '';
        for ($i = 1; $i <= $totalPages; $i++) {
            $result .= FormHelper::buildOption($i, (string)$i, $currentPage);
        }
        return $result;
    }

    /**
     * Build word sorting options.
     *
     * Note: The original code has duplicate option values (4 and 7 both map to
     * "Oldest first" in different contexts). This method preserves that behavior.
     *
     * @param int|string|null $selected Currently selected value (default: 1)
     *
     * @return string HTML options string
     */
    public static function forWordSort(int|string|null $selected = null): string
    {
        $selected = $selected ?? 1;
        // Using manual string building to preserve original order with duplicate-looking values
        $result = '';
        $result .= FormHelper::buildOption(1, 'Term A-Z', $selected);
        $result .= FormHelper::buildOption(2, 'Translation A-Z', $selected);
        $result .= FormHelper::buildOption(3, 'Newest first', $selected);
        $result .= FormHelper::buildOption(7, 'Oldest first', $selected);
        $result .= FormHelper::buildOption(4, 'Oldest first', $selected);
        $result .= FormHelper::buildOption(5, 'Status', $selected);
        $result .= FormHelper::buildOption(6, 'Score Value (%)', $selected);
        $result .= FormHelper::buildOption(7, 'Word Count Active Texts', $selected);
        return $result;
    }

    /**
     * Build tag sorting options.
     *
     * @param int|string|null $selected Currently selected value (default: 1)
     *
     * @return string HTML options string
     */
    public static function forTagSort(int|string|null $selected = null): string
    {
        $selected = $selected ?? 1;
        $options = [
            1 => 'Tag Text A-Z',
            2 => 'Tag Comment A-Z',
            3 => 'Newest first',
            4 => 'Oldest first'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build text sorting options.
     *
     * @param int|string|null $selected Currently selected value (default: 1)
     *
     * @return string HTML options string
     */
    public static function forTextSort(int|string|null $selected = null): string
    {
        $selected = $selected ?? 1;
        $options = [
            1 => 'Title A-Z',
            2 => 'Newest first',
            3 => 'Oldest first'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build AND/OR logical operator options.
     *
     * @param int|string|null $selected Currently selected value (default: 0)
     *
     * @return string HTML options string
     */
    public static function forAndOr(int|string|null $selected = null): string
    {
        $selected = $selected ?? 0;
        $options = [
            0 => '... OR ...',
            1 => '... AND ...'
        ];
        return self::buildFromArray($options, $selected);
    }

    /**
     * Build options from an associative array.
     *
     * @param array<int|string, string> $options  Array of value => label pairs
     * @param int|string|null           $selected Currently selected value
     *
     * @return string HTML options string
     */
    public static function buildFromArray(array $options, int|string|null $selected = null): string
    {
        $result = '';
        foreach ($options as $value => $label) {
            $result .= FormHelper::buildOption($value, $label, $selected);
        }
        return $result;
    }

    /**
     * Build a filter-off option for select elements.
     *
     * @param int|string|null $selected Currently selected value
     *
     * @return string HTML option element
     */
    public static function buildFilterOffOption(int|string|null $selected = null): string
    {
        return FormHelper::buildOption('', '[Filter off]', $selected);
    }

    /**
     * Build a choose prompt option for select elements.
     *
     * @return string HTML option element
     */
    public static function buildChooseOption(): string
    {
        return '<option value="" selected="selected">[Choose...]</option>';
    }
}
