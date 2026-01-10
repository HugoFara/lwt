<?php declare(strict_types=1);
/**
 * Language Wizard View
 *
 * Variables expected:
 * - $currentNativeLanguage: string current native language setting
 * - $languageOptions: string HTML options for language select
 * - $languageDefsJson: string JSON-encoded language definitions
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Language\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Language\Views;

use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for view variables
assert(is_string($languageDefsJson));
assert(is_string($languageOptions));
assert(is_string($languageOptionsEmpty));

/**
 * @var string $languageDefsJson
 * @var string $languageOptions
 * @var string $languageOptionsEmpty
 */
?>
<script type="application/json" id="language-wizard-config">
<?php echo json_encode(['languageDefs' => json_decode($languageDefsJson, true)]); ?>
</script>

<div class="box has-background-warning-light mb-5" x-data="{ isOpen: true }">
    <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable"
            @click="isOpen = !isOpen"
            data-action="wizard-toggle">
        <h3 class="title is-5 mb-0 is-flex is-align-items-center">
            <span class="icon mr-2">
                <?php echo IconHelper::render('wand-2', ['alt' => 'Wizard']); ?>
            </span>
            <span>Language Settings Wizard</span>
        </h3>
        <span class="icon">
            <i :class="isOpen ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
        </span>
    </header>

    <div id="wizard_zone" x-show="isOpen" x-transition x-cloak class="mt-4">
        <div class="columns is-centered">
            <div class="column is-narrow">
                <div class="field">
                    <label class="label" for="l1">
                        <span class="tag is-info is-light mr-1">L1</span>
                        My native language is:
                    </label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="l1" id="l1">
                                <?php echo $languageOptions; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column is-narrow">
                <div class="field">
                    <label class="label" for="l2">
                        <span class="tag is-success is-light mr-1">L2</span>
                        I want to study:
                    </label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="l2" id="l2">
                                <?php echo $languageOptionsEmpty; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="has-text-centered mt-4">
            <button type="button"
                    class="button is-primary"
                    data-action="wizard-go">
                <span class="icon is-small">
                    <?php echo IconHelper::render('wand-2', ['alt' => 'Apply']); ?>
                </span>
                <span>Set Language Settings</span>
            </button>
        </div>

        <p class="has-text-grey has-text-centered is-size-7 mt-4">
            Select your native (L1) and study (L2) languages, and let the
            wizard set all language settings marked in yellow!<br />
            (You can adjust the settings afterwards.)
        </p>
    </div>
</div>
