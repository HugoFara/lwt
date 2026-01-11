<?php

declare(strict_types=1);

/**
 * Word list filter form view
 *
 * Variables expected:
 * - $languages: Array of languages for filter dropdown
 * - $texts: Array of texts for filter dropdown
 * - $currentlang: Current language filter
 * - $currenttext: Current text filter
 * - $currenttexttag: Current text tag filter
 * - $currenttextmode: Current text/tag mode (0=text, 1=tag)
 * - $currentstatus: Current status filter
 * - $currentquery: Current search query
 * - $currentquerymode: Current query mode
 * - $currentregexmode: Current regex mode
 * - $currenttag1: First tag filter
 * - $currenttag2: Second tag filter
 * - $currenttag12: Tag logic (0=OR, 1=AND)
 * - $currentsort: Current sort option
 * - $currentpage: Current page number
 * - $recno: Total record count
 * - $pages: Total pages
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Word;

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for variables passed from controller
assert(is_string($currentquery));
assert(is_int($recno));
assert(is_int($currentpage));
assert(is_int($pages));
assert(is_int($currentsort));

?>

<!-- NOTE: Search bar planned for future UI refactoring.
     Planned features:
     - Universal search across terms, romanization, and translations
     - Filter chips for active filters (language, status, tags)
     - Autocomplete suggestions
     - Advanced filter toggle for power users
-->
<form name="form1" action="#" data-search-placeholder="words">
    <div class="box mb-4">
        <div class="field has-addons">
            <div class="control is-expanded has-icons-left">
                <input type="text"
                       name="query"
                       class="input"
                       value="<?php echo htmlspecialchars($currentquery, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search terms... (e.g., lang:Spanish status:learning verb)"
                       disabled />
                <span class="icon is-left">
                    <?php echo IconHelper::render('search', ['alt' => 'Search']); ?>
                </span>
            </div>
            <div class="control">
                <button type="button" class="button is-info" disabled>
                    Search
                </button>
            </div>
        </div>
        <p class="help has-text-grey">
            <?php echo IconHelper::render('info', ['alt' => 'Info', 'class' => 'icon-inline']); ?>
            Search functionality is being redesigned. Full filtering will be available soon.
        </p>

        <?php if ($recno > 0) { ?>
        <!-- Results Summary & Pagination -->
        <div class="level mt-4 pt-4" style="border-top: 1px solid #dbdbdb;">
            <div class="level-left">
                <div class="level-item">
                    <span class="tag is-info is-medium">
                        <?php echo $recno; ?> Term<?php echo ($recno == 1 ? '' : 's'); ?>
                    </span>
                </div>
            </div>
            <div class="level-item">
                <?php PageLayoutHelper::buildPager($currentpage, $pages, '/words/edit', 'form1'); ?>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small">Sort</span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select name="sort" data-action="sort">
                                    <?php echo SelectOptionsBuilder::forWordSort($currentsort); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</form>
