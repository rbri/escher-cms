
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
		'active' => true,
		'safe' => 1,
		'deny_attribute' => 'style',
	),
	'cache' => array
	(
		'adapter' => 'database',
		'lifetime' => '3600',
		'table' => 'cache',
		'namespace' => 'partials',
		'page_cache_active' => true,
		'page_cache_namespace' => 'pages',
		'page_cache_send_content_type' => true,
		'page_cache_send_last_modified' => true,
		'page_cache_send_etag' => true,
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
		'CacheMonitor',
		'Comment',
		'Cookie',
		'Form',
		'Pagination',
		'Recaptcha',
	),
);
