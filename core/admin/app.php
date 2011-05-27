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

// Build config.

if (defined('escher_site_id'))
{
	@$config['db'] = $sites[escher_site_id]['db'];
	if (!empty($sites[escher_site_id]['database_default']))
	{
		$config['database_default'] = $sites[escher_site_id]['database_default'];
	}
	unset($sites);
}

if (empty($config['auth']['database']))
{
	@$config['auth']['database'] = $config['database_default'];
}

define('escher_core_dir', $config['core_dir']);

@$plug_search_paths = $config['plug_search_paths'];
@$plug_cache_dir = $config['plug_cache_dir'];

require($config['sparkplug_dir'].'/sparkplug.php');

// Plugs that extend the application object are a special case, since they must be
// loaded before we instantiate the application object.

//$spark->findPlugs(array('ApplicationExtension'), $config['app_plug_dir']);

// Escher application class definition

require($config['core_dir'].'/shared/escher_admin_controller.php');

class _EscherAdmin extends SparkApplication
{
	private $_headElements;
	private $_user;
	private $_tabs;
	private $_prefsModel;
	private $_prefs;
	private $_pluginsModel;
	private $_plugins;
	
	//---------------------------------------------------------------------------

	public function __construct($spark, $config)
	{
		parent::__construct($spark, $config);

		$this->pushModelDir($this->config->get('core_dir') . '/publish/models');

		$this->config->set('app_name', 'Escher CMS');
		
		// If not yet installed, do the minimum necessarily to let us proceed successfully
		// to the installer.
		
		$this->_headElements = array();
		
		if (!$this->is_installed())
		{
			$userModel = $this->newModel('User');
			$this->_user = $this->factory->manufacture('User', array('id'=>0));
			$this->build_tabs($this->_tabs, array('content', 'design', 'settings'));
			return;
		}

		try
		{
			$userModel = $this->newModel('User');
			$authModel = $this->newModel('SparkAuth');
			if ($auth = @$authModel->authenticate())
			{
				$this->_user = @$userModel->fetchUser($auth['id'], true, true);
			}
			else
			{
				$this->_user = $this->factory->manufacture('User', array('id'=>0));
			}
			
			$this->build_tabs($this->_tabs, array('content', 'design', 'settings'));
	
			$this->_prefsModel = $this->newModel('Preferences');
			$this->_prefs = $this->_prefsModel->fetchPrefVals();
		}
		catch (Exception $e)
		{
			$this->showExceptionPage($e);
		}
		
		// Do we need to update SparkPlug or the database schema?
		// If yes, do not load any plugins...
		
		if
		(
			!EscherVersion::validateSparkPlugVersion($ignore)
			||
			!EscherVersion::validateSchemaVersion($this->get_pref('schema'))
		)
		{
			return;
		}
		
		// register notifications we are interested in
		
		$this->observer->observe(array($this, 'afterLogin'), array('SparkAuthController:login'));
		$this->observer->observe(array($this, 'beforeRender'), array('SparkView:render:before:main'));
		$this->observer->observe(array($this, 'addHeadElement'), array('escher:page:request_add_element:head'));
		$this->observer->observe(array($this, 'flushPlugCache'), array('escher:cache:request_flush:plug'));
		$this->observer->observe(array($this, 'flushPartialCache'), array('escher:cache:request_flush:partial'));
		$this->observer->observe(array($this, 'flushPageCache'), array('escher:cache:request_flush:page'));
		
		// load "always-enabled" auto-load plugins, listed in config
		
		$this->_pluginsModel = $this->newModel('Plugins');
		$this->_plugins = array();

		// load "always-enabled" auto-load plugins, listed in config
		
		if (!empty($config['plugins']))
		{
			$spark->findPlugs($config['plugins'], $this->config->get('app_plug_dir'), array($this, 'setAutoLoadPlugin'));
			foreach($this->_plugins as $name)
			{
				$this->_plugins[$name] = $this->factory->manufacture($name);
			}
		}

		// load additional admin-side plugins, per database

		$plugins = $this->_pluginsModel->fetchPluginInfo(PluginsModel::PluginState_enabled, PluginsModel::PluginRuns_backend);
		foreach ($plugins as $plugin)
		{
			$plugin['callback'] = array($this, 'loadPlugin');
			$spark->addPlug($plugin);
			if ($plugin['auto_load'])
			{
				$name = $plugin['name'];
				$this->_plugins[$name] = $this->factory->manufacture($name);
			}
		}
	}
	
	//---------------------------------------------------------------------------

	public function afterLogin()
	{
		// check for updates
		
		if ($this->checkForUpdate($currentVersion))
		{
			$this->session->flashSet('html_alert', 'A new version of Escher CMS (v.'.$currentVersion.') is available! Visit the <a href="http://www.eschercms.org/">Escher CMS web site</a> to download.');
		}
	}

	//---------------------------------------------------------------------------

	public function beforeRender($message, $view, $varsObj)
	{
		$varsObj->vars['head_elements'] = $this->_headElements;
	}

	//---------------------------------------------------------------------------

	public function addHeadElement($message, $element)
	{
		$this->_headElements[] = $element;
	}

	//---------------------------------------------------------------------------

	public function flushPlugCache($message, $branch)
	{
		switch ($branch)
		{
			case EscherProductionStatus::Staging:
				$pref = 'plug_cache_flush_staging';
				break;
			case EscherProductionStatus::Development:
				$pref = 'plug_cache_flush_dev';
				break;
			default:
				$pref = 'plug_cache_flush';
		}
		
		if (!$this->_prefs[$pref])
		{
			$this->_prefs[$pref] = 1;
			$changedPrefs[$pref] = array('name'=>$pref, 'val'=>1);
			$this->_prefsModel->updatePrefs($changedPrefs);
		}
	}

	//---------------------------------------------------------------------------

	public function flushPartialCache($message, $branch)
	{
		switch ($branch)
		{
			case EscherProductionStatus::Staging:
				$pref = 'partial_cache_flush_staging';
				break;
			case EscherProductionStatus::Development:
				$pref = 'partial_cache_flush_dev';
				break;
			default:
				$pref = 'partial_cache_flush';
		}
		
		if (!$this->_prefs[$pref])
		{
			$this->_prefs[$pref] = 1;
			$changedPrefs[$pref] = array('name'=>$pref, 'val'=>1);
			$this->_prefsModel->updatePrefs($changedPrefs);
		}
	}

	//---------------------------------------------------------------------------

	public function flushPageCache($message, $branch)
	{
		switch ($branch)
		{
			case EscherProductionStatus::Staging:
				$pref = 'page_cache_flush_staging';
				break;
			case EscherProductionStatus::Development:
				$pref = 'page_cache_flush_dev';
				break;
			default:
				$pref = 'page_cache_flush';
		}

		if (!$this->_prefs[$pref])
		{
			$this->_prefs[$pref] = 1;
			$changedPrefs[$pref] = array('name'=>$pref, 'val'=>1);
			$this->_prefsModel->updatePrefs($changedPrefs);
			
			// Configurations utilizing static page caching will not get a chance to
			// purge the cache on the publish side. So if using a file-based page cache,
			// we remove the page cache directory here on the admin side.

			if ($dir = $this->config->get('page_cache_dir'))
			{
				switch ($branch)
				{
					case EscherProductionStatus::Staging:
						$dir = rtrim($dir, '/\\') . '.staging';
						break;
					case EscherProductionStatus::Development:
						$dir = rtrim($dir, '/\\') . '.dev';
						break;
				}
				$cacher = $this->loadCacher(array('adapter' => 'file', 'cache_dir' => $dir));
				$cacher->clear();
			}
		}
	}

	//---------------------------------------------------------------------------

	public function setAutoLoadPlugin(&$plugin)
	{
		if (isset($plugin['enabled']) && !$plugin['enabled'])
		{
			return false;
		}
		if (isset($plugin['runs_where']) && !($plugin['runs_where'] & PluginsModel::PluginRuns_backend))
		{
			return false;
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

	public function fetchExternalWebPage($url)
	{
		$page = false;

		if (ini_get('allow_url_fopen'))
		{
			$ctx = stream_context_create
			(
				array
				(
					'http'=>array
					(
						'method'=>'GET',
						'header'=>"Accept: text/html,application/xhtml+xml,application/xml\r\n",
						'user_agent'=>'Escher CMS '. EscherVersion::CoreVersion . '/' . EscherVersion::SchemaVersion,
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
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Escher CMS'));
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

	public function get_date()
	{
		return gmdate('Y-m-d H:i:s');
	}

	//---------------------------------------------------------------------------

	public function is_installed()
	{
		return $this->config->get('db');
	}

	//---------------------------------------------------------------------------

	public function is_admin()
	{
		return true;
	}

	//---------------------------------------------------------------------------

	public function get_user()
	{
		return $this->_user;
	}

	//---------------------------------------------------------------------------

	public function get_pref($key, $default = NULL)
	{
		return isset($this->_prefs[$key]) ? $this->_prefs[$key] : $default;
	}

	//---------------------------------------------------------------------------

	public function &get_tabs()
	{
		return $this->_tabs;
	}

	//---------------------------------------------------------------------------

	public function build_tabs(&$tabs, $items, $prefix='')
	{
		$tabs = array();

		if (!empty($prefix))
		{
			if (!$this->_user->allowed($prefix))
			{
				return;
			}
			$prefix .= ':';
		}
		
		foreach ($items as $item)
		{
			if ($this->_user->allowed($prefix.$item))
			{
				$tabs[] = $item;
			}
		}
	}

	//---------------------------------------------------------------------------

	public function append_tab(&$tabs, $tab)
	{
		$tabs[] = $tab;
	}

	//---------------------------------------------------------------------------

	public function insert_tab(&$tabs, $tab, $after = NULL)
	{
		if ($after === NULL)
		{
			array_unshift($tabs, $tab);
		}
		else
		{
			$newTabs = array();
			foreach ($tabs as $each)
			{
				if (($newTabs[] = $each) === $after)
				{
					$newTabs[] = $tab;
				}
			}
			$tabs = $newTabs;
		}
	}

	//---------------------------------------------------------------------------

	private function checkForUpdate(&$currentVersion)
	{
		$currentVersion = false;
		
		if ($daysBetweenUpdates = @$this->_prefs['check_for_updates'])
		{
			$nextCheckTime = $this->factory->manufacture('SparkDateTime', $this->_prefs['last_update_check'])->addDays($daysBetweenUpdates)->getTimestamp();
			if ($nextCheckTime <= time())
			{
				$currentVersion = $this->fetchExternalWebPage('http://www.eschercms.org/info/version.txt');
		
				// record that we checked for an update

				if ($currentVersion !== false)
				{
					$currentVersion = trim($currentVersion);
					$changedPrefs[] = array('name'=>'last_update_check', 'val'=>SparkModel::now());
					$changedPrefs[] = array('name'=>'last_update_version', 'val'=>$currentVersion);
					$this->_prefsModel->updatePrefs($changedPrefs);
				}
			}
		}
		
		if ($currentVersion === false)
		{
			$currentVersion = $this->_prefs['last_update_version'];
		}

		return (version_compare(EscherVersion::CoreVersion, $currentVersion) < 0);
	}

	//---------------------------------------------------------------------------
}

// Instantiate the application object.

$app = $spark->manufacture('EscherAdmin', $spark, $config);
unset($config);

// Run it.

$app->run();
