<? if ($alert = $this->session->flashGet('html_alert')): ?>
<div id="alert">
	<p><?= $alert ?></p>
</div>
<? endif; ?>
<? if (!empty($warning)): ?>
<div id="error" class="flash">
	<p><?= $this->escape($warning) ?></p>
</div>
<? endif; ?>
<? if (!empty($notice)): ?>
<div id="notice" class="flash">
	<p><?= $this->escape($notice) ?></p>
</div>
<? endif; ?>
