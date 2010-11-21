<select name="selected_theme_id">
<option value="0">None</option>
<? foreach($themes as $id=>$name): ?>
<option value="<?= $id ?>"<?= ($id == $selected_theme_id) ? ' selected="selected"' : '' ?>><?= str_replace('  ', '&nbsp;&nbsp;', $this->escape($name)) ?></option>
<? endforeach; ?>
</select>
