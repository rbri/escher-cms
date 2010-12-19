<html>
<head>
	<title><?= $this->escape($status) ?></title>
	<style type="text/css">
		h1 {
			font-weight: bold;
			font-size: 250%;
			color: #A00;
			padding: 15px;
			text-align: center;
			background-color: #e4f2fd;
		}
		h2 {
			font-weight: normal;
			font-size: 150%;
			color: #A00;
		}
		h3 {
			font-weight: normal;
			color: #A00;
		}
	</style>
</head>
<body id="error">
	<h1>Oops!</h1>
	<h2><?= $this->escape(!empty($message) ? $message : $status) ?></h2>
<? if (!empty($reason)): ?>
	<h3><em>(<?= $this->escape($reason) ?>)</em></h3>
<? endif; ?>
	<p>
<?
?>
	</p>
</body>
</html>
