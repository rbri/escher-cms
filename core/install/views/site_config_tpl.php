
$sites['<?= $site_id ?>'] = array
(
//	'error_view' => 'detailed',
	'use_index_file' => true,
	'site_name' => '<?= $site_name ?>',
	'charset' => 'UTF-8',
//	'plug_cache_dir' => '<?= $plug_cache_dir ?>',
	'db' => array
	(
		'<?= $db_config['label'] ?>' => array
		(
<? foreach($db_config as $key => $val): ?>
<?= "			'{$key}' => " . (is_bool($val) ? ($val ? 'true' : 'false') : "'{$val}'") . ',' ?>

<? endforeach; ?>
		),
	),
	'sanitizer' => array
	(
		'in' => array
		(
			'active' => true,
			'safe' => 1,
			'deny_attribute' => 'style',
		),
		'out' => array
		(
			'active' => false,	// disabled by default because htmlawed not yet HTML5-compatible
			'tidy' => 1,
		)
	),
	'cache' => array
	(
		'adapter' => 'database',
		'lifetime' => '3600',
		'namespace' => 'partials',
	),
	'page_cache' => array		// page cache settings are layered over cache settings
	(
		'namespace' => 'pages',
		'active' => true,
		'send_content_type' => true,
		'send_last_modified' => true,
		'send_etag' => true,
	),
	'plugins' => array
	(
		'filters/ConvertLineBreaks',
		'filters/htmlawed',
		'filters/Markdown',
		'filters/SmartyPants',
		'filters/Textile',
		'filters/Tidy',
		'Archive',
		'Comment',
		'Cookie',
		'Feed',
		'Form',
		'Pagination',
		'Query',
		'Recaptcha',
		'Search',
	),
);
