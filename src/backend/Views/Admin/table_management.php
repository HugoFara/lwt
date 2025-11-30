<?php declare(strict_types=1);
/**
 * Table Set Management View
 *
 * Variables expected:
 * - $fixedTbpref: bool Whether prefix is fixed
 * - $tbpref: string Current table prefix
 * - $prefixes: array List of available prefixes
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

if ($fixedTbpref):
?>
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<td class="td1">
    <p>These features are not currently not available.<br /><br />
    Reason:<br /><b>DB_TABLE_PREFIX</b> is set to a fixed value in <i>.env</i>.<br />
    Please remove the definition<br /><span class="red"><b>DB_TABLE_PREFIX=<?php echo substr($tbpref, 0, -1); ?></b></span></br />
    in <i>.env</i> to make these features available.<br />
    Then try again.</p>
    <p class="right">
        &nbsp;<br />
        <button type="button" data-action="go-back">&lt;&lt; Back</button>
    </p>
</td>
</tr>
</table>
<?php else: ?>
<table class="tab2" style="width: auto;" cellspacing="0" cellpadding="5">

<tr>
<th class="th1 center">Select</th>
<td class="td1">
<form name="f1" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<p>Table Set: <select name="prefix">
<option value="-" selected="selected">[Choose...]</option>
<option value="">Default Table Set</option>
<?php foreach ($prefixes as $value): ?>
<option value="<?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
<?php endforeach; ?>
</select>
</p>
<p class="right">&nbsp;<br /><input type="submit" name="op" value="Start LWT with selected Table Set" />
</p>
</form>
</td>
</tr>

<tr>
<th class="th1 center">Create</th>
<td class="td1">
<form name="f2" class="inline table-create-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<p>New Table Set: <input type="text" name="newpref" value="" maxlength="20" size="20" />
</p>
<p class="right">&nbsp;<br /><input type="submit" name="op" value="Create New Table Set &amp; Start LWT" />
</p>
</form>
</td>
</tr>

<tr>
<th class="th1 center">Delete</th>
<td class="td1">
<form name="f3" class="inline table-delete-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<p>Table Set: <select name="delpref">
<option value="-" selected="selected">[Choose...]</option>
<?php foreach ($prefixes as $value): ?>
<?php if ($value != ''): ?>
   <option value="<?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
<?php endif; ?>
<?php endforeach; ?>
</select>
<br />
(You cannot delete the Default Table Set.)
</p>
<p class="right">&nbsp;<br /><span class="red2">YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;</span><input type="submit" name="op" value="DELETE Table Set" />
</p>
</form>
</td>
</tr>

<tr>
<td class="td1 right" colspan="2">
<button type="button" data-action="navigate" data-url="index.php">&lt;&lt; Back</button></td>
</tr>

</table>
<?php endif; ?>
