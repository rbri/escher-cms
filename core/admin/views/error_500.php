<? header('HTTP/1.1 500 Internal Server Error'); ?>
<html>
<head>
	<title>500 Internal Server Error</title>
	<style type="text/css">
		h1 {
			font-weight: normal;
			font-size: 150%;
			color: #A00;
			margin-bottom: 5px;
		}
		#message {
			margin-bottom: 5px;
		}
	</style>
</head>
<body>
	<h1><?= $heading ?></h1>
	<div id="message"><?= $message ?></div>
</body>
</html>
