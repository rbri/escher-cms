<select name="selected_branch" disabled="disabled">
<option value="0">None</option>
<? if (!empty($branches)): ?>
<? foreach($branches as $id=>$name): ?>
<option value="<?= $id ?>"<?= ($id == $selected_branch) ? ' selected="selected"' : '' ?>><?= str_replace('  ', '&nbsp;&nbsp;', $this->escape($name)) ?></option>
<? endforeach; ?>
<? endif; ?>
</select>
