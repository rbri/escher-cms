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

// Build config.

if (defined('escher_site_id'))
{
	if (!empty($sites[escher_site_id]))
	{
		$config = array_merge($config, $sites[escher_site_id]);
	}
	unset($sites);
}

require($config['core_dir'].'/shared/escher_application.php');

class _EscherSite extends EscherApplication
{
	private $_productionStatus;
	private $_hostPrefix;
	private $_branchPrefix;
	private $_baseNameSpace;

	//---------------------------------------------------------------------------

	public function __construct($spark, $config)
	{
		parent::__construct($spark, $config);

		if (!empty($config['error_view']))
		{
			$this->pushViewDir($this->appDir() . '/views/error_' . $config['error_view']);
		}

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
		$this->_baseNameSpace = $this->getNameSpace();

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

				if ($this->_hostPrefix === $devHostPrefix)
				{
					if ($this->get_pref('development_branch_auto_routing'))
					{
						$this->_productionStatus = EscherProductionStatus::Development;
						$this->_branchPrefix = $this->_hostPrefix;
					}
					else
					{
						$this->showExceptionPage(new SparkHTTPException_InternalServerError('Unknown host.'));
					}
				}
				elseif ($this->_hostPrefix === $stagingHostPrefix)
				{
					if ($this->get_pref('staging_branch_auto_routing'))
					{
						$this->_productionStatus = EscherProductionStatus::Staging;
						$this->_branchPrefix = $this->_hostPrefix;
					}
					else
					{
						$this->showExceptionPage(new SparkHTTPException_InternalServerError('Unknown host.'));
					}
				}
			}
		}
		
		// flush the caches if requested from the admin side
		
		$changedPrefs = array();
		
		switch ($this->_productionStatus)
		{
			case EscherProductionStatus::Maintenance:	// Do we need to update the database schema? Are we in maintenance mode?
				return;											// If yes, get out early so we don't load any plugins...

			case EscherProductionStatus::Staging:
				$this->setNameSpace($this->_baseNameSpace . '.staging');
				if (!empty($this->_prefs['plug_cache_flush_staging']))
				{
					$changedPrefs['plug_cache_flush_staging'] = array('name'=>'plug_cache_flush_staging', 'val'=>0);
				}
				if (!empty($this->_prefs['page_cache_flush_staging']))
				{
					$changedPrefs['page_cache_flush_staging'] = array('name'=>'page_cache_flush_staging', 'val'=>0);
				}
				break;

			case EscherProductionStatus::Development:
				$this->setNameSpace($this->_baseNameSpace . '.dev');
				if (!empty($this->_prefs['plug_cache_flush_dev']))
				{
					$changedPrefs['plug_cache_flush_dev'] = array('name'=>'plug_cache_flush_dev', 'val'=>0);
				}
				if (!empty($this->_prefs['page_cache_flush_dev']))
				{
					$changedPrefs['page_cache_flush_dev'] = array('name'=>'page_cache_flush_dev', 'val'=>0);
				}
				break;
			
			default:
				if (!empty($this->_prefs['plug_cache_flush']))
				{
					$changedPrefs['plug_cache_flush'] = array('name'=>'plug_cache_flush', 'val'=>0);
				}
				if (!empty($this->_prefs['page_cache_flush']))
				{
					$changedPrefs['page_cache_flush'] = array('name'=>'page_cache_flush', 'val'=>0);
				}
		}

		if (!empty($changedPrefs))
		{
			$this->loadPlugins();
			$this->_prefsModel->updatePrefs($changedPrefs);
			$this->flushSiteCaches();
		}
		else
		{
			// by loading plugins via a notification, we avoid loading (and therefore disable)
			// plugins for cached pages

			$this->observer->observe(array($this, 'loadPlugins'), 'SparkApplication:run:before');
		}

		if (empty($this->_prefs['page_cache_active']))
		{
			$this->disableCache();
		}
		else
		{
			$this->setDefaultTTL($this->_prefs['page_cache_ttl']);
		}

		// observe cache flush events
		
		$this->observer->observe(array($this, 'flushSitePlugCache'), array('escher:cache:request_flush:plug'));

		if ($this->_prefs['page_cache_active'])
		{
			$this->observer->observe(array($this, 'flushSitePageCache'), array('escher:cache:request_flush:page'));
		}
	}

	//---------------------------------------------------------------------------

	public function is_admin()
	{
		return false;
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

	public function flushSiteCaches()
	{
		switch ($this->_productionStatus)
		{
			case EscherProductionStatus::Staging:
				if (!empty($this->_prefs['plug_cache_flush_staging']))
				{
					$this->flushSitePlugCache('escher:cache:request_flush:plug', EscherProductionStatus::Staging);
				}
				if (!empty($this->_prefs['page_cache_flush_staging']))
				{
					$this->flushSitePageCache('escher:cache:request_flush:page', EscherProductionStatus::Staging);
				}
				break;

			case EscherProductionStatus::Development:
				if (!empty($this->_prefs['plug_cache_flush_dev']))
				{
					$this->flushSitePlugCache('escher:cache:request_flush:plug', EscherProductionStatus::Development);
				}
				if (!empty($this->_prefs['page_cache_flush_dev']))
				{
					$this->flushSitePageCache('escher:cache:request_flush:page', EscherProductionStatus::Development);
				}
				break;
			
			default:
				if (!empty($this->_prefs['plug_cache_flush']))
				{
					$this->flushSitePlugCache('escher:cache:request_flush:plug', EscherProductionStatus::Production);
				}
				if (!empty($this->_prefs['page_cache_flush']))
				{
					$this->flushSitePageCache('escher:cache:request_flush:page', EscherProductionStatus::Production);
				}
		}
	}
	
	//---------------------------------------------------------------------------

	public function flushSitePlugCache($message, $branches, $requester = NULL)
	{
		if (!$savePlugCacheDir = $this->factory->getPlugCacheDir())
		{
			return;
		}
		
		if ($flushAllBranches = empty($branches))
		{
			$branches = array(EscherProductionStatus::Production, EscherProductionStatus::Staging, EscherProductionStatus::Development);
		}
		
		$savePlugCacheDir = rtrim($savePlugCacheDir, '/\\');
		
		// set plug cache directory for appropriate branch so correct cache directory is cleared

		foreach ((array)$branches as $branch)
		{
			switch ($branch)
			{
				case EscherProductionStatus::Staging:
					$this->factory->setPlugCacheDir($savePlugCacheDir . '.staging');
					break;
					
				case EscherProductionStatus::Development:
					$this->factory->setPlugCacheDir($savePlugCacheDir . '.dev');
					break;
					
				default:
					$this->factory->setPlugCacheDir($savePlugCacheDir);
					break;
			}

			$this->observer->notify('Spark:cache:request_flush');

			if (!$flushAllBranches)
			{
				$this->observer->notify('escher:cache:flush:plug', $branch, $requester);
			}
		}
		
		// optimization for reducing message flow and CacheSync operations
		
		if ($flushAllBranches)
		{
			$this->observer->notify('escher:cache:flush:plug', 0, $requester);	// 0 -> all branches
		}

		$this->factory->setPlugCacheDir($savePlugCacheDir);
	}

	//---------------------------------------------------------------------------

	public function flushSitePageCache($message, $branches, $requester = NULL)
	{
		if ($flushAllBranches = empty($branches))
		{
			$branches = array(EscherProductionStatus::Production, EscherProductionStatus::Staging, EscherProductionStatus::Development);
		}
		
		$saveNameSpace = $this->getNameSpace();
		
		foreach ((array)$branches as $branch)
		{
			switch ($branch)
			{
				case EscherProductionStatus::Staging:
					$this->setNameSpace($this->_baseNameSpace . '.staging');
					break;
	
				case EscherProductionStatus::Development:
					$this->setNameSpace($this->_baseNameSpace . '.dev');
					break;
	
				default:
					$this->setNameSpace($this->_baseNameSpace);
					break;
			}
			
			$this->observer->notify('SparkPageCache:request_flush');
			
			if (!$flushAllBranches)
			{
				$this->observer->notify('escher:cache:flush:page', $branch, $requester);
			}
		}

		// optimization for reducing message flow and CacheSync operations
		
		if ($flushAllBranches)
		{
			$this->observer->notify('escher:cache:flush:page', 0, $requester);	// 0 -> all branches
		}

		$this->setNameSpace($saveNameSpace);
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
