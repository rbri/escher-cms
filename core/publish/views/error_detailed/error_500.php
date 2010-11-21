<? header('HTTP/1.1 500 Internal Server Error'); ?>
<html>
<head>
	<title>500 Internal Server Error</title>
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
	</style>
</head>
<body id="error">
	<h1><?= $heading ?></h1>
	<h2><?= $message ?></h2>
	<p><?= isset($error) ? $error : (isset($exception) ? $exception->getMessage() : '') ?></p>
</body>
</html>
