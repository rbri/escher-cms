<?php

/*
Copyright 2009-2011 Sam Weiss
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

// -----------------------------------------------------------------------------

class DatabaseInstallerSqlite extends DatabaseInstaller
{
	// --------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
	}

	// --------------------------------------------------------------------------

	public function driverName()
	{
		return 'sqlite';
	}

	// --------------------------------------------------------------------------

	public function displayName()
	{
		return 'Sqlite';
	}

	// --------------------------------------------------------------------------

	public function setConnectionDefaults(&$vars)
	{
		$vars['db_path'] = $this->session->get('db_path', $vars['db_dir'] . '/' . SparkInflector::dehumanize($vars['site_name']) . '.db');
	}

	// --------------------------------------------------------------------------

	public function validateConnectionFields($data, &$errors, &$vars)
	{
		static $dirHelp = 'Please change this directory\'s permissions so that the web server has read/write/execute access.';

		$vars['show_ignore_db_path_error'] = false;

		if (($dbpath = str_replace('\\', '/', $data['db_path'])) == '')
		{
			$errors['db_path'] = 'Database path is required.';
		}
		elseif (($dbpath[0] != '/') && !preg_match('#^([a-z]:)#i', $dbpath))
		{
			$errors['db_path'] = 'Absolute path is required.';
		}
		elseif (!$this->validPath($dbpath, $parts))
		{
			$errors['db_path'] = 'Invalid file path specified.';
		}
		elseif (!$this->validDir(rtrim($parts[1], '/'), $error))
		{
			$errors['db_path'] = $error;
		}
		elseif (empty($data['ignore_db_path_error']) && stripos($dbpath, str_replace('\\', '/', SparkUtil::doc_root())) !== false)
		{
			$vars['show_ignore_db_path_error'] = true;
			$errors['db_path'] = 'You have specified a database location that is web-accessible. If possible, it is more secure to locate your database outside of your web server\'s document root.';
		}
	}

	// --------------------------------------------------------------------------

	public function buildConnectionParams($data)
	{
		return array
		(
			'dsn' => 'sqlite:'.$data['db_path'],
		);
	}

	// --------------------------------------------------------------------------

	public function checkSupport($connectionParams, &$errorMsg)
	{
		return true;
	}

	//---------------------------------------------------------------------------

	private function validDir($path, &$error)
	{
		static $dirHelp = 'Please change this directory\'s permissions so that the web server has read/write/execute access.';

		if (!$this->validPath($path, $parts))
		{
			$error = 'Invalid file path specified.';
		}
		elseif (!is_dir($path))
		{
			$error = 'You have specified a directory that does not exist.';
		}
		elseif (!is_readable($path))
		{
			$error = 'You have specified a directory that is not readable by the web server. '.$dirHelp;
		}
		elseif (!is_writable($path))
		{
			$error = 'You have specified a directory that is not writable by the web server. '.$dirHelp;
		}
		elseif (!@file_exists($path.'/.'))
		{
			$error = 'You have specified a directory that is not traversable by the web server. '.$dirHelp;
		}
		else
		{
			return true;
		}
		return false;
	}
	
	//---------------------------------------------------------------------------

	private function validPath($path, &$parts)
	{
		$info = pathinfo($path);
		$parts[0] = $info['dirname'] . '/' . $info['basename'];
		$parts[1] = $info['dirname'];
		$parts[2] = $info['basename'];
		return true;
	}
	
}
