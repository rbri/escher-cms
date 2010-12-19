<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="Escher Content Management System Administration" />
<meta name="robots" content="noindex, nofollow" />
<title>Permission Denied</title>
<link href="<?= $this->urlToStatic('/css/escher.css') ?>" media="screen" rel="stylesheet" type="text/css" />
<? if (!empty($head_elements)): ?>
<? foreach ($head_elements as $element): ?>
<?= $element ?>
<? endforeach; ?>
<? endif; ?>
</head>

<body>
	<? $this->render('header'); ?>
	<div id="content">
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
	<div class="title">
		Permission Denied
	</div>
	</div>
	<? $this->render('footer'); ?>
</body>
</html>
