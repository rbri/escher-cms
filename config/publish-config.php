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
	'sparkcache',
	'sparkdatetime',
	'sparkdb',
	'sparklang',
	'sparkmailer',
	'sparknonce',
	'sparkpagecache',
	'sparkpagination',
	'sparksanitizer',
	'sparktext',
	'sparkvalidator',
);

$config = array
(
	'error_view' => 'basic',
	'root_dir' => $rootDir = dirname(dirname(__FILE__)),
	'core_dir' => $rootDir . '/core',
	'app_dir' => $rootDir . '/core/publish',
	'lang_dir' => $rootDir . '/core/shared/languages',
	'app_plug_dir' => $rootDir . '/plugins',
	'sparkplug_dir' => $rootDir . '/sparkplug',
//	'admin_site' => 'https://admin.mysite.com',
	'routes' => array
	(
		':all' => 'publish/index',
	),
);
unset($rootDir);
