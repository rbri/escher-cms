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
	@$config['db'] = $sites[escher_site_id]['db'];
	if (!empty($sites[escher_site_id]['database_default']))
	{
		$config['database_default'] = $sites[escher_site_id]['database_default'];
	}
	if (!empty($sites[escher_site_id]['database_default_ro']))
	{
		$config['database_default_ro'] = $sites[escher_site_id]['database_default_ro'];
	}
	unset($sites);
}

if (empty($config['auth']['database']))
{
	@$config['auth']['database'] = $config['database_default'];
}

// Plugs that extend the application object are a special case, since they must be
// loaded before we instantiate the application object.

//$spark->findPlugs(array('ApplicationExtension'), $config['app_plug_dir']);

// Escher application class definition

require($config['core_dir'].'/shared/escher_application.php');
require($config['core_dir'].'/shared/escher_admin_controller.php');

class _EscherAdmin extends EscherApplication
{
	private $_headElements;
	private $_user;
	private $_tabs;
	
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
			$authSession = $this->factory->manufacture('SparkAuthSession');
			if ($userInfo = @$authSession->loggedIn())
			{
				$this->_user = @$userModel->fetchUser($userInfo['id'], true, true);
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
		
		$this->observer->observe(array($this, 'flushSitePlugCache'), array('escher:cache:request_flush:plug'));
		if ($this->_prefs['partial_cache_active'])
		{
			$this->observer->observe(array($this, 'flushSitePartialCache'), array('escher:cache:request_flush:partial'));
		}
		if ($this->_prefs['page_cache_active'])
		{
			$this->observer->observe(array($this, 'flushSitePageCache'), array('escher:cache:request_flush:page'));
		}
		
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
		
		// check if the core code has been updated
		
		if (version_compare(@$this->_prefs['version'], EscherVersion::CoreVersion) != 0)
		{
			$version = array
			(
				'name' => 'version',
				'group_name' => 'system',
				'section_name' => 'version',
				'type' => 'hidden',
				'val' => EscherVersion::CoreVersion,
			);
			
			$this->_prefsModel->upsertPrefs(array($version));
			$this->observer->notify('escher:version:upgrade');
		}
	}
	
	//---------------------------------------------------------------------------

	public function is_admin()
	{
		return true;
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

	public function flushSitePlugCache($message, $branches)
	{
		if ($flushAllBranches = empty($branches))
		{
			$branches = array(EscherProductionStatus::Production, EscherProductionStatus::Staging, EscherProductionStatus::Development);
		}
		
		$changedPrefs = array();
		
		foreach ((array)$branches as $branch)
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
					break;
			}
			
			if (!$this->_prefs[$pref])
			{
				$this->_prefs[$pref] = 1;
				$changedPrefs[$pref] = array('name'=>$pref, 'val'=>1);
			}
		}

		if (!empty($changedPrefs))
		{
			$this->_prefsModel->updatePrefs($changedPrefs);
		}
	}

	//---------------------------------------------------------------------------

	public function flushSitePartialCache($message, $branches)
	{
		if ($flushAllBranches = empty($branches))
		{
			$branches = array(EscherProductionStatus::Production, EscherProductionStatus::Staging, EscherProductionStatus::Development);
		}
		
		$changedPrefs = array();
		
		foreach ((array)$branches as $branch)
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
					break;
			}
			
			if (!$this->_prefs[$pref])
			{
				$this->_prefs[$pref] = 1;
				$changedPrefs[$pref] = array('name'=>$pref, 'val'=>1);
			}
		}

		if (!empty($changedPrefs))
		{
			$this->_prefsModel->updatePrefs($changedPrefs);
		}
	}

	//---------------------------------------------------------------------------

	public function flushSitePageCache($message, $branches)
	{
		if ($flushAllBranches = empty($branches))
		{
			$branches = array(EscherProductionStatus::Production, EscherProductionStatus::Staging, EscherProductionStatus::Development);
		}
		
		// Configurations utilizing static page caching will not get a chance to
		// purge the cache on the publish side. In this case, we purge the page
		// cache directory here on the admin side.

		$cacheParams = $this->config->get('publish_page_cache');

		if (!empty($cacheParams['static']))
		{
			$cacher = $this->loadCacher($cacheParams);
			$namespace = $cacher->getNameSpace();

			foreach ((array)$branches as $branch)
			{
				switch ($branch)
				{
					case EscherProductionStatus::Staging:
						$cacher->setNameSpace($namespace . '.staging');
						break;
					case EscherProductionStatus::Development:
						$cacher->setNameSpace($namespace . '.dev');
						break;
					default:
						break;
				}
				$cacher->clear();
				if (!$flushAllBranches)
				{
					$this->observer->notify('escher:cache:flush:page', $branch);
				}
			}
			
			// optimization for reducing message flow and CacheSync operations
			
			if ($flushAllBranches)
			{
				$this->observer->notify('escher:cache:flush:page', 0);	// 0 -> all branches
			}
		}
		else
		{
			$changedPrefs = array();
			
			foreach ((array)$branches as $branch)
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
						break;
				}
		
				if (!$this->_prefs[$pref])
				{
					$this->_prefs[$pref] = 1;
					$changedPrefs[$pref] = array('name'=>$pref, 'val'=>1);
				}
			}
	
			if (!empty($changedPrefs))
			{
				$this->_prefsModel->updatePrefs($changedPrefs);
			}
		}
	}

	//---------------------------------------------------------------------------

	public function get_user()
	{
		return $this->_user;
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
