<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="Escher Content Management System Administration" />
<meta name="robots" content="noindex, nofollow" />
<title>Escher Content Management System Administration</title>
<link href="<?= $this->urlToStatic('/css/escher.css') ?>" media="screen" rel="stylesheet" type="text/css" />
<link href="<?= $this->urlToStatic('/js/jquery/css/smoothness/jquery-ui-1.8.6.custom.css') ?>" media="screen" rel="stylesheet" type="text/css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js" type="text/javascript"></script>
<script src="<?= $this->urlToStatic('/js/jquery_plugins.js') ?>" type="text/javascript"></script>
<script src="<?= $this->urlToStatic('/js/escher_common.js') ?>" type="text/javascript"></script>
<? if (!empty($head_elements)): ?>
<? foreach ($head_elements as $element): ?>
<?= $element ?>
<? endforeach; ?>
<? endif; ?>
</head>

<body>
	<? $this->render('header'); ?>
	<div id="content">
	<? $this->render(isset($content) ? $content : ($selected_tab . '/' . (isset($selected_subtab) ? $selected_subtab : $selected_tab))); ?>
	</div>
	<? $this->render('footer'); ?>
</body>
</html>
