<? header('HTTP/1.1 404 Not Found'); ?>
<html>
<head>
	<title>404 Page Not Found</title>
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
