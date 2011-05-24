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
	if (!empty($sites[escher_site_id]))
	{
		$config = array_merge($config, $sites[escher_site_id]);
	}
	unset($sites);
}

define('escher_core_dir', $config['core_dir']);

@$plug_search_paths = $config['plug_search_paths'];
@$plug_cache_dir = $config['plug_cache_dir'];

require($config['sparkplug_dir'].'/sparkplug.php');
require($config['core_dir'].'/shared/escher_base.php');

class _EscherSite extends SparkApplication
{
	private $_pluginsModel;
	private $_prefsModel;
	private $_prefs;
	private $_plugins;
	private $_productionStatus;
	private $_hostPrefix;
	private $_branchPrefix;

	//---------------------------------------------------------------------------

	public function __construct($spark, $config)
	{
		parent::__construct($spark, $config);

		$this->pushViewDir($this->appDir() . '/views/error_' . $config['error_view']);

		try
		{
			$this->_pluginsModel = NULL;
			$this->_prefsModel = $this->newModel('Preferences');
			$this->_prefs = $this->_prefsModel->fetchPrefVals();
		}
		catch (Exception $e)
		{
			if (empty($config['db']))
			{
				if (!$adminURL = @$config['admin_site'])
				{
					$adminURL = $this->urlToStatic('/admin', true, true);
				}
				header('Location: ' . $adminURL);
				exit;
			}

			$this->showExceptionPage(new SparkHTTPException_InternalServerError('Problem accessing database.'));
		}
		
		$this->_hostPrefix = $this->_branchPrefix = NULL;

		// Outdated Spark/Plug and schema upgrades always throw us into maintenance mode
		
		if 
		(
			!EscherVersion::validateSparkPlugVersion($ignore)
			||
			!EscherVersion::validateSchemaVersion($this->_prefs['schema'])
		)
		{
			$this->_productionStatus = EscherProductionStatus::Maintenance;
		}
		else
		{
			$this->_productionStatus = $this->get_pref('production_status', EscherProductionStatus::Production);

			// check if there is a hostname override prefix, which overrides production status preference

			$devHostPrefix = $this->get_pref('development_branch_host_prefix', 'dev');
			$stagingHostPrefix = $this->get_pref('staging_branch_host_prefix', 'staging');
	
			if (preg_match("#^({$devHostPrefix}|{$stagingHostPrefix})\.#", SparkUtil::host(), $matches))
			{
				$this->_hostPrefix = $matches[1];

				if (($this->_hostPrefix === $devHostPrefix) && $this->get_pref('development_branch_auto_routing'))
				{
					$this->_productionStatus = EscherProductionStatus::Development;
					$this->_branchPrefix = $this->_hostPrefix;
				}
				elseif (($this->_hostPrefix === $stagingHostPrefix) && $this->get_pref('staging_branch_auto_routing'))
				{
					$this->_productionStatus = EscherProductionStatus::Staging;
					$this->_branchPrefix = $this->_hostPrefix;
				}
			}
		}
				
		switch ($this->_productionStatus)
		{
			case EscherProductionStatus::Maintenance:	// Do we need to update the database schema? Are we in maintenance mode?
				return;											// If yes, do not load any plugins...

			case EscherProductionStatus::Staging:		// FIXME! We need to add support for separate page caches for dev/staging branches.
			case EscherProductionStatus::Development:	// Until we do, we need to disable the page cache for those branches to avoid
				$this->disableCache();						// mixing up the cache with pages from different branches.
				break;
		}

		$this->setDefaultTTL($this->_prefs['page_cache_ttl']);

		// by loading plugins via a notification, we avoid loading (and therefore disable)
		// plugins for cached pages
		
		$this->observer->observe(array($this, 'loadPlugins'), 'SparkApplication:run:before');

		// observe cache flush events
		
		$this->observer->observe(array($this, 'flushPlugCache'), array('escher:cache:request_flush:plug'));
		$this->observer->observe(array($this, 'flushPageCache'), array('escher:cache:request_flush:page'));
		$this->observer->observe(array($this, 'flushPartialCache'), array('escher:cache:request_flush:partial'));
		
		// flush the caches if requested from the admin side
		
		$changedPrefs = array();
		if (!empty($this->_prefs['plug_cache_flush']))
		{
			$this->observer->notify('Spark:cache:request_flush');
			$changedPrefs['plug_cache_flush'] = array('name'=>'plug_cache_flush', 'val'=>0);
		}
		if (!empty($this->_prefs['page_cache_flush']))
		{
			$this->observer->notify('SparkPageCache:request_flush');
			$changedPrefs['page_cache_flush'] = array('name'=>'page_cache_flush', 'val'=>0);
		}
		if (!empty($changedPrefs))
		{
			$this->_prefsModel->updatePrefs($changedPrefs);
		}
	}

	//---------------------------------------------------------------------------

	public function loadPlugins()
	{
		$this->_pluginsModel = $this->newModel('Plugins');
		$this->_plugins = array();

		// load "always-enabled" auto-load plugins, listed in config
		
		if ($plugins = $this->config->get('plugins'))
		{
			$this->factory->findPlugs($plugins, $this->config->get('app_plug_dir'), array($this, 'setAutoLoadPlugin'));
			foreach($this->_plugins as $name)
			{
				$this->_plugins[$name] = $this->factory->manufacture($name);
			}
		}
		
		// load additional publish-side plugins, per database

		$plugins = $this->_pluginsModel->fetchPluginInfo(PluginsModel::PluginState_enabled, PluginsModel::PluginRuns_frontend);
		foreach ($plugins as $plugin)
		{
			$plugin['callback'] = array($this, 'loadPlugin');
			$this->factory->addPlug($plugin);
			if ($plugin['auto_load'])
			{
				$name = $plugin['name'];
				$this->_plugins[$name] = $this->factory->manufacture($name);
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
		if (isset($plugin['runs_where']) && !($plugin['runs_where'] & PluginsModel::PluginRuns_frontend))
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

	public function flushPlugCache()
	{
		$this->observer->notify('Spark:cache:request_flush');
	}

	//---------------------------------------------------------------------------

	public function flushPageCache()
	{
		$this->observer->notify('SparkPageCache:request_flush');
	}

	//---------------------------------------------------------------------------

	public function flushPartialCache()
	{
		if (!$this->_prefs['partial_cache_flush'])
		{
			$this->_prefs['partial_cache_flush'] = 1;
			$changedPrefs['partial_cache_flush'] = array('name'=>'partial_cache_flush', 'val'=>1);
			$this->_prefsModel->updatePrefs($changedPrefs);
		}
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

	public function is_admin()
	{
		return false;
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

	public function get_production_status()
	{
		return $this->_productionStatus;
	}

	//---------------------------------------------------------------------------

	public function get_host_prefix()
	{
		return $this->_hostPrefix;
	}

	//---------------------------------------------------------------------------

	public function get_branch_prefix()
	{
		return $this->_branchPrefix;
	}

	//---------------------------------------------------------------------------
}

// Instantiate the application object.

$app = $spark->manufacture('EscherSite', $spark, $config);
unset($config);

// Run it.

$app->run();
