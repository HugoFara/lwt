<?php declare(strict_types=1);
/**
 * Annotated text edit view.
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $annExists: bool - Whether annotations exist
 * - $viewData: array - View data
 *
 * @category User_Interface
 * @package  Lwt
 */

namespace Lwt\Views\TextPrint;

use Lwt\Api\V1\Handlers\ImprovedTextHandler;
use Lwt\View\Helper\IconHelper;

$title = $viewData['title'];
$sourceUri = $viewData['sourceUri'];
?>
<h1>ANN.TEXT &#9654; <?php echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8');
if (isset($sourceUri) && substr(trim($sourceUri), 0, 1) != '#') {
    echo ' <a href="' . $sourceUri . '" target="_blank">' .
         IconHelper::render('link', ['title' => 'Text Source', 'alt' => 'Text Source']) . '</a>';
}
?></h1>

<div id="printoptions">
    <h2>Improved Annotated Text (Edit Mode)
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help', 'class' => 'click', 'data-action' => 'open-window', 'data-url' => 'docs/info.html#il']); ?>
    </h2>
    <input type="button" value="Display/Print Mode" data-action="navigate" data-url="/text/print?text=<?php echo $textId; ?>" />
</div>
<!-- noprint -->

<?php if (!$annExists): ?>
    <p>No annotated text found, and creation seems not possible.</p>
<?php else: ?>
    <?php
    // Annotations exist, set up for editing
    $handler = new ImprovedTextHandler();
    ?>
    <div data_id="<?php echo $textId; ?>" id="editimprtextdata">
        <?php echo $handler->editTermForm($textId); ?>
    </div>
<?php endif; ?>

<div class="noprint">
    <input type="button" value="Display/Print Mode"
    data-action="navigate" data-url="/text/print?text=<?php echo $textId; ?>" />
</div>
