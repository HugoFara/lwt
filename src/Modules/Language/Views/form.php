<?php

/**
 * Language Form View
 *
 * Variables expected:
 * - $language: Language view object (stdClass)
 * - $sourceLg: string source language code
 * - $targetLg: string target language code
 * - $isNew: bool true if creating new language
 * - $parserInfo: array parser info from ParserRegistry::getParserInfo()
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Language\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 *
 * @psalm-suppress TypeDoesNotContainType View included from different contexts
 */

declare(strict_types=1);

namespace Lwt\Modules\Language\Views;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\Infrastructure\Language\LanguagePresets;
use Lwt\Modules\Dictionary\Domain\LocalDictionary;

// Type assertions for view variables
assert(is_object($language));
assert(is_string($sourceLg));
assert(is_string($targetLg));
assert(is_bool($isNew));
assert(is_array($parserInfo));
assert(is_array($allLanguages));

/**
 * @var object $language Language view object with optional properties
 * @var string $sourceLg
 * @var string $targetLg
 * @var bool $isNew
 * @var bool $isAdmin
 * @var LocalDictionary[] $dictionaries
 * @var array<string, array<string, mixed>> $parserInfo
 * @var array<int, array{id: int, name: string}> $allLanguages
 */

// Extract typed values from language object
$langId = isset($language->id) ? (int)$language->id : null;
$langName = isset($language->name) && is_string($language->name) ? $language->name : '';
$langTextSize = isset($language->textsize) && is_numeric($language->textsize) ? (int)$language->textsize : 100;
$langParserType = isset($language->parsertype) && is_string($language->parsertype) ? $language->parsertype : 'regex';
$langDict1Uri = isset($language->dict1uri) && is_string($language->dict1uri) ? $language->dict1uri : '';
$langDict2Uri = isset($language->dict2uri) && is_string($language->dict2uri) ? $language->dict2uri : '';
$langTranslatorUri = isset($language->translatoruri) && is_string($language->translatoruri)
    ? $language->translatoruri : '';
$langDict1Popup = !empty($language->dict1popup);
$langDict2Popup = !empty($language->dict2popup);
$langTranslatorPopup = !empty($language->translatorpopup);
$langSourceLang = isset($language->sourcelang) && is_string($language->sourcelang) ? $language->sourcelang : '';
$langTargetLang = isset($language->targetlang) && is_string($language->targetlang) ? $language->targetlang : '';
$langExportTemplate = isset($language->exporttemplate) && is_string($language->exporttemplate)
    ? $language->exporttemplate : '';
$langRegexpSplitSentences = isset($language->regexpsplitsent) && is_string($language->regexpsplitsent)
    ? $language->regexpsplitsent : '';
$langExceptionsSplitSentences = isset($language->exceptionsplitsent) && is_string($language->exceptionsplitsent)
    ? $language->exceptionsplitsent : '';
$langRegexpWordCharacters = isset($language->regexpwordchar) && is_string($language->regexpwordchar)
    ? $language->regexpwordchar : '';
$langCharSubstitutions = isset($language->charactersubst) && is_string($language->charactersubst)
    ? $language->charactersubst : '';
$langRemoveSpaces = !empty($language->removespaces);
$langSplitEachChar = !empty($language->spliteachchar);
$langRightToLeft = !empty($language->rightoleft);
$langShowRomanization = !empty($language->showromanization);
$langTtsVoiceApi = isset($language->ttsvoiceapi) && is_string($language->ttsvoiceapi) ? $language->ttsvoiceapi : '';
$langLocalDictMode = isset($language->localdictmode) && is_numeric($language->localdictmode)
    ? (int)$language->localdictmode : 0;
$langPiperVoiceId = isset($language->pipervoiceid) && is_string($language->pipervoiceid)
    ? $language->pipervoiceid : null;

// Pre-computed translated attribute strings (kept short to satisfy line-length rules)
$importMoreTitle = htmlspecialchars(__('language.form.import_more_entries'), ENT_QUOTES, 'UTF-8');

?>
<script type="application/json" id="language-form-config">
<?php echo json_encode([
    'languageId' => $langId,
    'languageName' => $langName,
    'sourceLg' => $sourceLg,
    'targetLg' => $targetLg,
    'languageDefs' => LanguagePresets::getAll(),
    'allLanguages' => $allLanguages
]); ?>
</script>

<?php
$formAction = url($isNew ? '/languages/new' : '/languages/' . (int) $langId . '/edit');
?>
<form class="validate"
      action="<?php echo $formAction; ?>"
      method="post"
      name="lg_form"
      x-data="{
          textSize: <?php echo $langTextSize; ?>,
          parserType: '<?php echo htmlspecialchars($langParserType, ENT_QUOTES, 'UTF-8'); ?>',
          showJapaneseOptions: <?php echo ($langName === 'Japanese') ? 'true' : 'false'; ?>,
          showTranslatorKey: false,
          showAdvanced: <?php echo $isNew ? 'false' : 'true'; ?>
      }">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="LgID" value="<?php echo $langId ?? ''; ?>" />

    <?php if (!$isNew) : ?>
    <!-- Edit Warning -->
    <article class="message is-warning mb-4">
        <div class="message-body">
            <strong><?php echo __('language.form.warning_label'); ?></strong>
            <?php echo __('language.form.warning_body'); ?>
        </div>
    </article>
    <?php endif; ?>

    <!-- Language Name (always visible) -->
    <div class="container mb-5" style="max-width: 400px;">
        <div class="field">
            <label class="label is-medium" for="LgName">
                <?php echo __('language.form.display_name_label'); ?>
            </label>
            <div class="control">
                <input type="text"
                       class="input is-medium notempty<?php echo $isNew ? '' : ' setfocus'; ?> checkoutsidebmp"
                       data_info="Study Language"
                       name="LgName"
                       id="LgName"
                       value="<?php echo htmlspecialchars($langName, ENT_QUOTES, 'UTF-8'); ?>"
                       maxlength="40"
                       @input="showJapaneseOptions = ($event.target.value === 'Japanese')"
                       required />
            </div>
            <p class="help"><?php echo __('language.form.display_name_help'); ?></p>
        </div>

        <!-- Save button (primary action) -->
        <div class="field mt-5">
            <div class="control">
                <?php if ($isNew) : ?>
                <button type="submit" name="op" value="Save" class="button is-primary is-medium is-fullwidth">
                    <span class="icon">
                        <?php echo IconHelper::render('save', ['alt' => __('language.form.save')]); ?>
                    </span>
                    <span><?php echo __('language.form.save'); ?></span>
                </button>
                <?php else : ?>
                <button type="submit" name="op" value="Change" class="button is-primary is-medium is-fullwidth">
                    <span class="icon">
                        <?php echo IconHelper::render('save', ['alt' => __('language.form.save')]); ?>
                    </span>
                    <span><?php echo __('language.form.save_changes'); ?></span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cancel link -->
        <div class="has-text-centered mt-3">
            <a href="<?php echo url('/languages'); ?>" class="has-text-grey">
                <?php echo __('language.form.cancel'); ?>
            </a>
        </div>
    </div>

    <!-- Advanced Settings (collapsible) -->
    <div class="container" style="max-width: 800px;">
        <div class="box" x-data="{ open: showAdvanced }">
            <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable"
                    @click="open = !open">
                <h4 class="title is-5 mb-0 is-flex is-align-items-center">
                    <span class="icon mr-2">
                        <?php echo IconHelper::render('settings', ['alt' => __('language.form.advanced_settings')]); ?>
                    </span>
                    <?php echo __('language.form.advanced_settings'); ?>
                </h4>
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </header>

            <div x-show="open" x-transition x-cloak class="mt-4">
                <!-- Dictionaries & Translation -->
                <h5 class="title is-6 mt-4 mb-3"><?php echo __('language.form.section_dictionaries'); ?></h5>

                <!-- Local Dictionaries (shown first - more valuable than online) -->
                <?php if (!$isNew) : ?>
                <div class="p-4 mb-4" style="border-radius: 6px; background-color: var(--lwt-panel-bg, #fafafa);">
                    <h6 class="title is-6 mb-3 is-flex is-align-items-center is-justify-content-space-between">
                        <span>
                            <?php echo IconHelper::render(
                                'book-open',
                                ['alt' => __('language.form.local_dictionaries')]
                            ); ?>
                            <?php echo __('language.form.local_dictionaries'); ?>
                        </span>
                        <a href="<?php echo url('/word/upload?tab=dictionary'); ?>"
                           class="button is-primary is-small">
                            <?php echo IconHelper::render('upload', ['alt' => __('language.form.import')]); ?>
                            <span class="ml-1"><?php echo __('language.form.import'); ?></span>
                        </a>
                    </h6>

                    <?php if (empty($dictionaries)) : ?>
                    <p class="has-text-grey">
                        <?php echo __('language.form.no_local_dictionaries'); ?>
                        <a href="<?php echo url('/word/upload?tab=dictionary'); ?>">
                            <?php echo __('language.form.import_one'); ?>
                        </a>
                        <?php echo __('language.form.no_local_dictionaries_help'); ?>
                    </p>
                    <?php else : ?>
                    <table class="table is-fullwidth is-narrow is-striped mb-0">
                        <thead>
                            <tr>
                                <th><?php echo __('language.form.dict_col_name'); ?></th>
                                <th><?php echo __('language.form.dict_col_entries'); ?></th>
                                <th><?php echo __('language.form.dict_col_status'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dictionaries as $dict) : ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($dict->name(), ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="tag is-light is-small ml-1"><?php
                                        echo strtoupper($dict->sourceFormat());
                                    ?></span>
                                </td>
                                <td><?php echo number_format($dict->entryCount()); ?></td>
                                <td>
                                    <?php if ($dict->isEnabled()) : ?>
                                    <span class="tag is-success is-light">
                                        <?php echo __('language.form.dict_status_enabled'); ?>
                                    </span>
                                    <?php else : ?>
                                    <span class="tag is-warning is-light">
                                        <?php echo __('language.form.dict_status_disabled'); ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="has-text-right">
                                    <a href="<?php echo url('/word/upload?tab=dictionary'); ?>"
                                       class="button is-small is-info is-outlined"
                                       title="<?php echo $importMoreTitle; ?>">
                                        <?php echo IconHelper::render(
                                            'upload',
                                            ['alt' => __('language.form.import')]
                                        ); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="help mt-2">
                        <a href="<?php echo url('/languages/' . $langId . '/dictionaries'); ?>">
                            <?php echo __('language.form.manage_dictionaries'); ?>
                        </a>
                        <?php echo __('language.form.manage_dictionaries_help'); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Local Dictionary Mode -->
                    <div class="field mt-3">
                        <label class="label is-small"><?php echo __('language.form.lookup_mode'); ?></label>
                        <div class="control">
                            <div class="select is-small">
                                <select name="LgLocalDictMode" id="LgLocalDictMode">
                                    <option value="0" <?php echo $langLocalDictMode === 0 ? 'selected' : ''; ?>>
                                        <?php echo __('language.form.lookup_mode_online_only'); ?>
                                    </option>
                                    <option value="1" <?php echo $langLocalDictMode === 1 ? 'selected' : ''; ?>>
                                        <?php echo __('language.form.lookup_mode_local_first'); ?>
                                    </option>
                                    <option value="2" <?php echo $langLocalDictMode === 2 ? 'selected' : ''; ?>>
                                        <?php echo __('language.form.lookup_mode_local_only'); ?>
                                    </option>
                                    <option value="3" <?php echo $langLocalDictMode === 3 ? 'selected' : ''; ?>>
                                        <?php echo __('language.form.lookup_mode_combined'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else : ?>
                <!-- Hidden field for new languages (default to local first) -->
                <input type="hidden" name="LgLocalDictMode" value="1" />
                <?php endif; ?>

                <!-- Online Dictionary URIs -->
                <!-- Dictionary 1 URI -->
                <div class="field">
                    <label class="label">
                        <?php echo __('language.form.dict1_uri'); ?>
                        <span
                            class="has-text-danger"
                            title="<?php echo htmlspecialchars(
                                __('language.form.required_marker_title'),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >*</span>
                    </label>
                    <div class="control">
                        <input type="url"
                               class="input notempty checkdicturl checkoutsidebmp"
                               name="LgDict1URI"
                               value="<?php echo htmlspecialchars($langDict1Uri, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               data_info="Dictionary 1 URI" />
                    </div>
                    <label class="checkbox mt-2">
                        <input type="checkbox" name="LgDict1PopUp" id="LgDict1PopUp" value="1"
                               <?php echo $langDict1Popup ? 'checked' : ''; ?> />
                        <span class="has-text-grey-dark"><?php echo __('language.form.open_in_popup'); ?></span>
                    </label>
                </div>

                <!-- Dictionary 2 URI -->
                <div class="field">
                    <label class="label"><?php echo __('language.form.dict2_uri'); ?></label>
                    <div class="control">
                        <input type="url"
                               class="input checkdicturl checkoutsidebmp"
                               name="LgDict2URI"
                               value="<?php echo htmlspecialchars($langDict2Uri, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               data_info="Dictionary 2 URI" />
                    </div>
                    <label class="checkbox mt-2">
                        <input type="checkbox" name="LgDict2PopUp" id="LgDict2PopUp" value="1"
                               <?php echo $langDict2Popup ? 'checked' : ''; ?> />
                        <span class="has-text-grey-dark"><?php echo __('language.form.open_in_popup'); ?></span>
                    </label>
                </div>

                <!-- Sentence Translator URI -->
                <div class="field">
                    <label class="label"><?php echo __('language.form.sentence_translator'); ?></label>
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="LgTranslatorName"
                                        @change="showTranslatorKey = ($event.target.value === 'libretranslate')">
                                    <option value="google_translate">
                                        <?php echo __('language.form.translator_google_webpage'); ?>
                                    </option>
                                    <option value="libretranslate">
                                        <?php echo __('language.form.translator_libretranslate'); ?>
                                    </option>
                                    <option value="ggl">
                                        <?php echo __('language.form.translator_google_api'); ?>
                                    </option>
                                    <option value="glosbe" class="is-hidden">
                                        <?php echo __('language.form.translator_glosbe'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <div class="control">
                            <input type="url"
                                   class="input checkdicturl checkoutsidebmp"
                                   name="LgGoogleTranslateURI"
                                   value="<?php echo htmlspecialchars($langTranslatorUri, ENT_QUOTES, 'UTF-8'); ?>"
                                   maxlength="200"
                                   data_info="GoogleTranslate URI"
                                   placeholder="<?php echo htmlspecialchars(
                                       __('language.form.translator_uri_placeholder'),
                                       ENT_QUOTES,
                                       'UTF-8'
                                   ); ?>" />
                        </div>
                    </div>

                    <div class="field" x-show="showTranslatorKey" x-transition>
                        <label class="label is-small" for="LgTranslatorKey">
                            <?php echo __('language.form.api_key'); ?>
                        </label>
                        <div class="control">
                            <input type="text" class="input is-small" id="LgTranslatorKey" name="LgTranslatorKey" />
                        </div>
                    </div>

                    <label class="checkbox mt-2">
                        <input type="checkbox" name="LgGoogleTranslatePopUp" id="LgGoogleTranslatePopUp" value="1"
                               <?php echo $langTranslatorPopup ? 'checked' : ''; ?> />
                        <span class="has-text-grey-dark"><?php echo __('language.form.open_in_popup'); ?></span>
                    </label>
                    <p id="translator_error" class="help is-danger"></p>
                </div>

                <!-- Source/Target Language Codes -->
                <div class="columns mt-4">
                    <div class="column">
                        <div class="field">
                            <label class="label"><?php echo __('language.form.source_lang_code'); ?></label>
                            <div class="control">
                                <input type="text"
                                       class="input"
                                       name="LgSourceLang"
                                       id="LgSourceLang"
                                       value="<?php echo htmlspecialchars($langSourceLang, ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="10"
                                       placeholder="e.g., de, ja, zh" />
                            </div>
                            <p class="help"><?php echo __('language.form.source_lang_code_help'); ?></p>
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label class="label"><?php echo __('language.form.target_lang_code'); ?></label>
                            <div class="control">
                                <input type="text"
                                       class="input"
                                       name="LgTargetLang"
                                       id="LgTargetLang"
                                       value="<?php echo htmlspecialchars($langTargetLang, ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="10"
                                       placeholder="e.g., en" />
                            </div>
                            <p class="help"><?php echo __('language.form.target_lang_code_help'); ?></p>
                        </div>
                    </div>
                </div>

                <hr class="my-5" />

                <!-- Display Settings -->
                <h5 class="title is-6 mb-3"><?php echo __('language.form.section_display'); ?></h5>

                <div class="field">
                    <label class="label"><?php echo __('language.form.text_size'); ?></label>
                    <div class="control">
                        <input name="LgTextSize"
                               type="number"
                               min="100"
                               max="250"
                               step="50"
                               class="input"
                               style="max-width: 120px;"
                               x-model="textSize"
                               value="<?php echo $langTextSize; ?>" />
                    </div>
                    <div class="field mt-2">
                        <div class="control">
                            <input type="text"
                                   class="input"
                                   id="LgTextSizeExample"
                                   :style="'font-size: ' + textSize + '%'"
                                   value="<?php echo htmlspecialchars(
                                       __('language.form.text_size_example'),
                                       ENT_QUOTES,
                                       'UTF-8'
                                   ); ?>"
                                   readonly />
                        </div>
                    </div>
                </div>

                <hr class="my-5" />

                <!-- Text Processing -->
                <h5 class="title is-6 mb-3"><?php echo __('language.form.section_text_processing'); ?></h5>

                <!-- Parser Type -->
                <div class="field">
                    <label class="label"><?php echo __('language.form.parser_type'); ?></label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="LgParserType" id="LgParserType" x-model="parserType">
                                <?php foreach ($parserInfo as $type => $info) :
                                    $infoAvailable = isset($info['available']) && $info['available'];
                                    $infoName = isset($info['name']) && is_string($info['name']) ? $info['name'] : '';
                                    ?>
                                <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo ($langParserType === $type) ? 'selected' : ''; ?>
                                        <?php echo !$infoAvailable ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($infoName, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php echo !$infoAvailable ? __('language.form.parser_unavailable') : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Character Substitutions -->
                <div class="field">
                    <label class="label"><?php echo __('language.form.character_substitutions'); ?></label>
                    <div class="control">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Character Substitutions"
                               name="LgCharacterSubstitutions"
                               value="<?php echo htmlspecialchars($langCharSubstitutions, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="500" />
                    </div>
                    <p class="help"><?php echo __('language.form.character_substitutions_help'); ?></p>
                </div>

                <!-- RegExp Split Sentences (not needed for mecab) -->
                <div class="field" x-show="parserType !== 'mecab'" x-transition x-cloak>
                    <label class="label">
                        <?php echo __('language.form.regexp_split_sentences'); ?>
                        <span
                            class="has-text-danger"
                            title="<?php echo htmlspecialchars(
                                __('language.form.required_marker_title'),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            x-show="parserType === 'regex'"
                        >*</span>
                    </label>
                    <div class="control">
                        <input type="text"
                               class="input checkoutsidebmp"
                               :class="{ 'notempty': parserType === 'regex' }"
                               name="LgRegexpSplitSentences"
                               value="<?php echo htmlspecialchars($langRegexpSplitSentences, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="500"
                               data_info="RegExp Split Sentences" />
                    </div>
                </div>

                <!-- Exceptions Split Sentences (not needed for mecab) -->
                <div class="field" x-show="parserType !== 'mecab'" x-transition x-cloak>
                    <label class="label"><?php echo __('language.form.exceptions_split_sentences'); ?></label>
                    <div class="control">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Exceptions Split Sentences"
                               name="LgExceptionsSplitSentences"
                               value="<?php
                                echo htmlspecialchars($langExceptionsSplitSentences, ENT_QUOTES, 'UTF-8');
                                ?>"
                               maxlength="500" />
                    </div>
                </div>

                <!-- RegExp Word Characters (only for regex parser) -->
                <div class="field" x-show="parserType === 'regex'" x-transition x-cloak>
                    <label class="label">
                        <?php echo __('language.form.regexp_word_characters'); ?>
                        <span
                            class="has-text-danger"
                            title="<?php echo htmlspecialchars(
                                __('language.form.required_marker_title'),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >*</span>
                    </label>
                    <div x-show="showJapaneseOptions" x-transition class="field">
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="LgRegexpAlt">
                                    <option value="regexp"><?php echo __('language.form.regexp_alt_regexp'); ?></option>
                                    <option value="mecab"><?php echo __('language.form.regexp_alt_mecab'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="control">
                        <input type="text"
                               class="input notempty checkoutsidebmp"
                               data_info="RegExp Word Characters"
                               name="LgRegexpWordCharacters"
                               value="<?php echo htmlspecialchars($langRegexpWordCharacters, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="500" />
                    </div>
                </div>

                <hr class="my-4" />

                <!-- Script options -->
                <div class="field" x-show="parserType === 'regex'" x-transition x-cloak>
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgSplitEachChar"
                               id="LgSplitEachChar"
                               value="1"
                               <?php echo $langSplitEachChar ? "checked" : ""; ?> />
                        <strong><?php echo __('language.form.split_each_char'); ?></strong>
                    </label>
                    <p class="help ml-5"><?php echo __('language.form.split_each_char_help'); ?></p>
                </div>

                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgRemoveSpaces"
                               id="LgRemoveSpaces"
                               value="1"
                               <?php echo $langRemoveSpaces ? "checked" : ""; ?> />
                        <strong><?php echo __('language.form.remove_spaces'); ?></strong>
                    </label>
                    <p class="help ml-5"><?php echo __('language.form.remove_spaces_help'); ?></p>
                </div>

                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgRightToLeft"
                               id="LgRightToLeft"
                               value="1"
                               <?php echo $langRightToLeft ? "checked" : ""; ?> />
                        <strong><?php echo __('language.form.right_to_left'); ?></strong>
                    </label>
                    <p class="help ml-5"><?php echo __('language.form.right_to_left_help'); ?></p>
                </div>

                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox"
                               name="LgShowRomanization"
                               id="LgShowRomanization"
                               value="1"
                               <?php echo $langShowRomanization ? "checked" : ""; ?> />
                        <strong><?php echo __('language.form.show_romanization'); ?></strong>
                    </label>
                    <p class="help ml-5"><?php echo __('language.form.show_romanization_help'); ?></p>
                </div>

                <hr class="my-5" />

                <!-- Export & TTS -->
                <h5 class="title is-6 mb-3"><?php echo __('language.form.section_export_tts'); ?></h5>

                <!-- Export Template -->
                <div class="field">
                    <label class="label"><?php echo __('language.form.export_template'); ?></label>
                    <div class="control">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Export Template"
                               name="LgExportTemplate"
                               value="<?php echo htmlspecialchars($langExportTemplate, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="1000" />
                    </div>
                    <p class="help"><?php echo __('language.form.export_template_help'); ?></p>
                </div>

                <!-- Third-Party Text-to-Speech Voice API -->
                <div class="field">
                    <label class="label"><?php echo __('language.form.tts_voice_api'); ?></label>
                    <div class="control mb-2">
                        <input type="text"
                               class="input"
                               name="LgVoiceAPIDemo"
                               value="<?php echo htmlspecialchars(
                                   __('language.form.tts_demo_default'),
                                   ENT_QUOTES,
                                   'UTF-8'
                               ); ?>"
                               placeholder="<?php echo htmlspecialchars(
                                   __('language.form.tts_demo_placeholder'),
                                   ENT_QUOTES,
                                   'UTF-8'
                               ); ?>" />
                    </div>
                    <div class="control">
                        <textarea class="textarea checkoutsidebmp"
                                  data_info="Third-Party Text-to-Speech API"
                                  name="LgTTSVoiceAPI"
                                  maxlength="2048"
                                  rows="4"
                                  placeholder="<?php echo htmlspecialchars(
                                      __('language.form.tts_json_placeholder'),
                                      ENT_QUOTES,
                                      'UTF-8'
                                  ); ?>"><?php
echo htmlspecialchars($langTtsVoiceApi, ENT_QUOTES, 'UTF-8');
?></textarea>
                    </div>
                    <div class="buttons mt-3">
                        <button type="button"
                                class="button is-small is-info is-outlined"
                                data-action="check-voice-api">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('check', ['alt' => __('language.form.check')]); ?>
                            </span>
                            <span><?php echo __('language.form.check_voice_api'); ?></span>
                        </button>
                        <button type="button"
                                class="button is-small is-success is-outlined"
                                data-action="test-voice-api">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('play', ['alt' => __('language.form.test')]); ?>
                            </span>
                            <span><?php echo __('language.form.test_voice_api'); ?></span>
                        </button>
                    </div>
                    <p class="help is-danger is-hidden" id="voice-api-message-zone"></p>
                </div>
            </div>
        </div>
    </div>
</form>
