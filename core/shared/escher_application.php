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

define('escher_core_dir', $config['core_dir']);

@$plug_search_paths = $config['plug_search_paths'];
@$plug_cache_dir = $config['plug_cache_dir'];

require($config['sparkplug_dir'].'/sparkplug.php');

require('escher_base.php');

//------------------------------------------------------------------------------

abstract class EscherApplication extends SparkApplication
{	
	protected $_prefsModel;
	protected $_prefs;
	protected $_pluginsModel;
	protected $_plugins;

	//---------------------------------------------------------------------------

	public function __construct($spark, $config)
	{		
		parent::__construct($spark, $config);
	}

	//---------------------------------------------------------------------------

	abstract public function is_admin();

	//---------------------------------------------------------------------------

	public function is_installed()
	{
		return $this->config->get('db');
	}

	//---------------------------------------------------------------------------

	public function get_prefs_model()
	{
		return $this->_prefsModel;
	}

	//---------------------------------------------------------------------------

	public function &get_prefs()
	{
		return $this->_prefs;
	}

	//---------------------------------------------------------------------------

	public function get_pref($key, $default = NULL)
	{
		return isset($this->_prefs[$key]) ? $this->_prefs[$key] : $default;
	}

	//---------------------------------------------------------------------------

	public function put_pref($pref)
	{
		$this->_prefsModel->updatePrefs(array($pref));
	}

	//---------------------------------------------------------------------------

	public function setAutoLoadPlugin(&$plugin)
	{
		if (isset($plugin['enabled']) && !$plugin['enabled'])
		{
			return false;
		}
		if (isset($plugin['runs_where']))
		{
			if (!($plugin['runs_where'] & ($this->is_admin() ? PluginsModel::PluginRuns_backend : PluginsModel::PluginRuns_frontend)))
			{
				return false;
			}
		}
		if (!empty($plugin['auto_load']))
		{
			$this->_plugins[$plugin['name']] = $plugin['name'];
		}
		return true;
	}

	//---------------------------------------------------------------------------

	public function loadPlugin(&$plugin)
	{
		// load plugin code from database

		return $this->_pluginsModel->fetchPluginCode($plugin['name']);
	}

	//---------------------------------------------------------------------------

	public function get_date()
	{
		return gmdate('Y-m-d H:i:s');
	}

	//---------------------------------------------------------------------------
	
	public function format_date($date = 'now', $format = NULL, $formatType = 0)
	{
		$date = $this->factory->manufacture('SparkDateTime', $date);

		switch ($format)
		{
			case 'atom':
				return $date->format(DateTime::ATOM);
			case 'rss':
				return $date->format(DateTime::RSS);
			case '':
				$format = 'Y-m-d H:i:s T';
				$formatType = 0;
			default:
				$date->setTimeZone($this->get_pref('site_time_zone', 'UTC'));
				return ($formatType === 0) ? $date->format($format) : $date->strformat($format);
		}
	}

	//---------------------------------------------------------------------------

	public function fetchExternalWebPage($url)
	{
		$page = false;
		
		$userAgent = 'Escher CMS '. EscherVersion::CoreVersion . '/' . EscherVersion::SchemaVersion;
		$referrer = SparkUtil::self_url();

		if (ini_get('allow_url_fopen'))
		{
			$ctx = stream_context_create
			(
				array
				(
					'http'=>array
					(
						'method'=>'GET',
						'header'=>"Accept: text/html,application/xhtml+xml,application/xml\r\nReferer: {$referrer}\r\n",
						'user_agent'=>$userAgent,
						'timeout'=>5
					)
				)
			);
			$page = @file_get_contents($url, false, $ctx, 0, 12);
		}

		elseif (function_exists('curl_init'))
		{
			$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: {$userAgent}, Referer: {$referrer}"));
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				$page = curl_exec($ch);
				if (($status = curl_getinfo($ch, CURLINFO_HTTP_CODE)) != 200)
				{
					$page = false;
				}
			curl_close($ch);
		}
		
		return $page;
	}

	//---------------------------------------------------------------------------
	
}
