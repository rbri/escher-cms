<? if (!empty($errors)): ?>
<div id="error">
	<p>An error occurred while attempting installation: <?= $this->escape($errors['install']) ?></p>
</div>
<? elseif (!empty($notice)): ?>
<div id="notice" class="flash">
	<p><?= $this->escape($notice) ?></p>
</div>
<? endif; ?>

<div class="title">
	Install Event Log
</div>

<form method="post" action="">
	<div class="form-area">
		Event log needs to add a table to your site database. Your existing site data will not be touched.
		However, you may wish to perform a database backup before proceeding with installation.
	</div>
	<div class="buttons">
		<button class="positive" type="submit" name="install">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Install
		</button>
	</div>
</form>
<div class="clear"></div>
