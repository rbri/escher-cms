<? if (!empty($errors)): ?>
<div id="error">
<? if (!empty($warning)): ?>
	<p><?= $this->escape($warning) ?></p>
<? else: ?>
	<p>Validation errors occurred. Please review the form for errors.</p>
<? endif; ?>
</div>
<? else: ?>
	<? $this->render('alert'); ?>
<? endif; ?>
