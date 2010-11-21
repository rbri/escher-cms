<p style="padding:10px 0 10px 0;">
	<span class="help">Note. You must create an empty MySQL database before submitting this page.</span>
</p>
<div class="field">
	<label for="db_host">Database Host:</label>
	<input type="text" name="db_host" size="60" value="<?= isset($db_host) ? $this->escape($db_host) : '' ?>" />
	<span class="help">Required. Enter the fully qualified domain name for your database host.</span>
	<?= isset($errors['db_host']) ? "<div class=\"error\">{$this->escape($errors['db_host'])}</div>" : '' ?>
</div>
<div class="field">
	<label for="db_host">Database Name:</label>
	<input type="text" name="db_name" size="60" value="<?= isset($db_name) ? $this->escape($db_name) : '' ?>" />
	<span class="help">Required. Enter your database name.</span>
	<?= isset($errors['db_name']) ? "<div class=\"error\">{$this->escape($errors['db_name'])}</div>" : '' ?>
</div>
<div class="field">
	<label for="db_user">Database User:</label>
	<input type="text" name="db_user" size="60" value="<?= isset($db_user) ? $this->escape($db_user) : '' ?>" />
	<span class="help">Required. Enter your database user name.</span>
	<?= isset($errors['db_user']) ? "<div class=\"error\">{$this->escape($errors['db_user'])}</div>" : '' ?>
</div>
<div class="field">
	<label for="db_pass">Database Password:</label>
	<input type="text" name="db_pass" size="60" value="<?= isset($db_pass) ? $this->escape($db_pass) : '' ?>" />
	<span class="help">Required. Enter your database password.</span>
	<?= isset($errors['db_pass']) ? "<div class=\"error\">{$this->escape($errors['db_pass'])}</div>" : '' ?>
</div>
