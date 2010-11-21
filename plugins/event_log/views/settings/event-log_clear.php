<? if (!empty($errors)): ?>
<div id="error">
	<p>An error occurred while attempting to clear event log: <?= $this->escape($errors['clear']) ?></p>
</div>
<? elseif (!empty($notice)): ?>
<div id="notice" class="flash">
	<p><?= $this->escape($notice) ?></p>
</div>
<? endif; ?>

<div class="title">
	Clear Event Log
</div>

<form method="post" action="">
	<div class="form-area">
		Clearing the event log will permanently remove all log entries.
		This action cannot be reversed.
	</div>
	<div class="buttons">
		<button class="negative" type="submit" name="clear">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Clear Event Log
		</button>
	</div>
	or <a href="<?= $this->urlTo('/settings/event-log') ?>">Cancel</a>
</form>
<div class="clear"></div>
