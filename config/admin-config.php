<?php

/*
Copyright 2009-2012 Sam Weiss
All Rights Reserved.

This file is part of Escher.

Escher is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('escher'))
{
	header('HTTP/1.1 403 Forbidden');
	exit('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don\'t have permission to access the requested resource on this server.</p></body></html>');
}

error_reporting(E_ALL|E_STRICT);
ini_set('display_startup_errors', '0');
ini_set('display_errors', '0');

$plugs = array
(
	'sparkauth',
	'sparkcache',
	'sparkdatetime',
	'sparkdb',
	'sparkhasher',
	'sparklang',
	'sparkmailer',
	'sparkpagination',
//	'sparksanitizer',
	'sparksession',
	'sparktext',
);

$config = array
(
	'root_dir' => $rootDir = dirname(dirname(__FILE__)),
	'sparkplug_dir' => $rootDir . '/sparkplug',
//	'plug_cache_dir' => $rootDir . '/cache/admin',
	'core_dir' => $rootDir . '/core',
	'app_dir' => $rootDir . '/core/admin',
	'lang_dir' => $rootDir . '/core/shared/languages',
	'app_plug_dir' => $rootDir . '/plugins',
	'charset' => 'UTF-8',
	'default_controller' => 'content',
	'login_controller' => 'settings',
	'use_index_file' => true,
	'sanitizer' => array
	(
		'in' => array
		(
			'active' => false,
			'safe' => 1,
			'deny_attribute' => 'style',
		),
		'out' => array
		(
			'active' => false,
			'tidy' => 1,
		)
	),
	'session' => array
	(
		'adapter' => 'native',
		'name' => 'eschersessid',
		'update_time' => 300,
		'lifetime' => 1200,
		'path' => '/',
		'domain' => '',
		'match_ip' => 0,
		'match_user_agent' => true,
		'encrypted' => false,
		'encryption_key' => '',
		'hash_key' => '',
/*
		'database' => array
		(
			'config' => '',
			'table' => 'session',
			'columns' => array
			(
				'id' => 'id',
				'nonce' => 'nonce',
				'expires' => 'expires',
			),
		),
*/
	),
	'auth' => array
	(
		'timeout' => 1200,
		'session' => array
		(
			'id', 'nonce',
		),
		'database' => array
		(
			'config' => '',
			'table' => 'user',
			'columns' => array
			(
				'id' => 'id',
				'email' => 'email',
				'login' => 'login',
				'password' => 'password',
				'nonce' => 'nonce',
				'logged' => 'logged',
			),
		),
	),
	'plugins' => array
	(
		'filters/ConvertLineBreaks',
		'filters/Markdown',
		'filters/SmartyPants',
		'filters/Textile',
		'filters/Tidy',
		'Archive',
		'CacheMonitor',
		'Comment',
		'DesignEdit',
		'EventLog',
		'Feed',
		'Markitup',
		'Recaptcha',
		'Search',
	),
);
unset($rootDir);
