<?php

/**
 * View for text display mode change result
 *
 * Variables expected:
 * - $showAll: int - Whether all words should be shown (0/1)
 * - $showLearning: int - Whether to show translations for learning words (0/1)
 * - $oldShowLearning: int - Previous value of showLearning (0/1)
 * - $waitingIconPath: string - Path to the waiting icon
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Text;

?>
<script type="application/json" id="set-mode-config">
<?php echo json_encode([
    'showLearningChanged' => $showLearning != $oldShowLearning,
    'showLearning' => (bool)$showLearning
]); ?>
</script>
<p><span id="waiting"><img src="<?php echo $waitingIconPath; ?>" alt="Please wait" title="Please wait" />&nbsp;&nbsp;Please wait ...</span>

<?php if ($showAll == 1): ?>
<b><i>Show All</i></b> is set to <b>ON</b>.
<br /><br />ALL terms are now shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term.
<br /><br />To concentrate more on the multi-word terms and to display them without superscript, set <i>Show All</i> to OFF.</p>
<?php else: ?>
<b><i>Show All</i></b> is set to <b>OFF</b>.
<br /><br />Multi-word terms now hide single words and shorter or overlapping multi-word terms. The creation and deletion of multi-word terms can be a bit slow in long texts.
<br /><br />To  manipulate ALL terms, set <i>Show All</i> to ON.</p>
<?php endif; ?>

<br /><br />

<?php if ($showLearning == 1): ?>
<b><i>Learning Translations</i></b> is set to <b>ON</b>.
<br /><br />Terms that have Learning Level&nbsp;1 will show their translations beneath the term in the reading mode.
<br /><br />To hide the translations, set <i>Learning Translations</i> to OFF.</p>
<?php else: ?>
<b><i>Learning Translations</i></b> is set to <b>OFF</b>.
<br /><br />No translations will be shown directly in the reading window.
<br /><br />To see translations for terms with Learning Level&nbsp;1 underneath the terms in the reading window, set <i>Learning Translations</i> to ON.</p>
<?php endif; ?>
