<?php declare(strict_types=1);
/**
 * TTS Settings View
 *
 * Variables expected:
 * - $languageOptions: string HTML options for language select
 * - $currentLanguageCode: string Current language code (JSON-encoded for JS)
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

?>
<script type="application/json" id="tts-settings-config">
{"currentLanguageCode": <?php echo $currentLanguageCode; ?>}
</script>

<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post"
      x-data="{
          rate: 1,
          pitch: 1,
          demoText: 'Lorem ipsum dolor sit amet...'
      }">

    <div class="box">
        <h3 class="title is-5">Language Settings</h3>

        <!-- Language Code -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="get-language">Language</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="LgName" id="get-language" class="notempty" required>
                                <?php echo $languageOptions; ?>
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Voice Selection -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="voice">Voice</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="LgVoice" id="voice" class="notempty" required>
                                <!-- Populated by JavaScript based on browser capabilities -->
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
                <p class="help">Available voices depend on your web browser</p>
            </div>
        </div>
    </div>

    <div class="box">
        <h3 class="title is-5">Speech Settings</h3>

        <!-- Reading Rate -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="rate">Reading Rate</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <div class="columns is-vcentered is-mobile">
                            <div class="column is-narrow">
                                <span class="tag is-light">0.5x</span>
                            </div>
                            <div class="column">
                                <input type="range"
                                       name="LgTTSRate"
                                       id="rate"
                                       class="slider is-fullwidth"
                                       min="0.5"
                                       max="2"
                                       step="0.1"
                                       x-model="rate" />
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-light">2x</span>
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-info" x-text="rate + 'x'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pitch -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="pitch">Pitch</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <div class="columns is-vcentered is-mobile">
                            <div class="column is-narrow">
                                <span class="tag is-light">Low</span>
                            </div>
                            <div class="column">
                                <input type="range"
                                       name="LgPitch"
                                       id="pitch"
                                       class="slider is-fullwidth"
                                       min="0"
                                       max="2"
                                       step="0.1"
                                       x-model="pitch" />
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-light">High</span>
                            </div>
                            <div class="column is-narrow">
                                <span class="tag is-info" x-text="pitch"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="box">
        <h3 class="title is-5">Demo</h3>

        <!-- Demo Text -->
        <div class="field">
            <label class="label" for="tts-demo">Test Text</label>
            <div class="control">
                <textarea class="textarea"
                          id="tts-demo"
                          rows="3"
                          placeholder="Enter text to test speech synthesis..."
                          x-model="demoText"></textarea>
            </div>
            <p class="help">Enter any text to preview the voice settings</p>
        </div>

        <div class="field">
            <div class="control">
                <button type="button"
                        class="button is-info"
                        data-action="tts-demo">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('play', ['alt' => 'Play']); ?>
                    </span>
                    <span>Read Aloud</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="tts-cancel">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Save" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Save</span>
            </button>
        </div>
    </div>
</form>

<article class="message is-info mt-5">
    <div class="message-header">
        <p>Note</p>
    </div>
    <div class="message-body">
        Language settings depend on your web browser, as different browsers have different ways to read languages.
        Saving these settings will store them as a cookie on your browser and will not be accessible by the LWT database.
    </div>
</article>
