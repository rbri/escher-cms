<div class="field">
	<label for="db_path">Database Path:</label>
	<input type="text" name="db_path" size="60" value="<?= isset($db_path) ? $this->escape($db_path) : '' ?>" />
	<span class="help">Required. Enter the <strong>absolute</strong> path to your sqlite data file.</span>
	<?= isset($errors['db_path']) ? "<div class=\"error\">{$this->escape($errors['db_path'])}</div>" : '' ?>
</div>
<? if (!empty($show_ignore_db_path_error)): ?>
<div class="field">
	<input type="checkbox" name="ignore_db_path_error" value="1" <?= !empty($ignore_db_path_error) ? 'checked="checked"' : '' ?> />
	<label for="ignore_db_path_error">Ignore error. Go ahead with this location.</label>
</div>
<? endif; ?>
