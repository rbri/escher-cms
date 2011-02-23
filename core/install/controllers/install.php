<?php

/*
Copyright 2009-2010 Sam Weiss
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

class _InstallController extends SparkController
{	
	private $_rootDir;
	private $_cacheDir;
	private $_configFile;
	private $_db_dir;
	private $_db_plugs;
	private $_db_drivers;

	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		$this->_rootDir = $this->config->get('root_dir');
		$this->_cacheDir = $this->config->get('cache_dir', $this->_rootDir . '/cache');
		$this->_configFile = $this->config->get('config_dir', $this->_rootDir . '/config') . '/site-config.php';
		$this->_db_dir = $this->config->get('db_dir', $this->_rootDir . '/db');
		$this->_db_plugs = NULL;
		$this->_db_drivers = array();

		try
		{
			$this->factory->loadClass('DatabaseInstaller');		// load the base class
		
			$plugs = $this->factory->getExtensions('DatabaseInstaller');
			if (!empty($plugs))
			{
				foreach ($plugs as $plugInfo)
				{
					$name = $plugInfo['name'];
					if ($name !== 'DatabaseInstaller')	// skip abstract parent
					{
						$plugin = $this->factory->manufacture($name);
						$this->_db_plugs[$plugin->driverName()] = array($plugInfo, $plugin);
						$this->_db_drivers[$plugin->driverName()] = $plugin->displayName();
					}
				}
			}
		}
		catch (Exception $e)
		{
		}
	}

	// --------------------------------------------------------------------------

	public function _before_dispatch($method, $params)
	{
		return true;	// override SparkAuthController (which is active only so we can access SparkAuthModel)
	}

	//---------------------------------------------------------------------------

	public function action_index($params)
	{
		$this->getCommonVars($vars);

		if ($this->installed())
		{
			if ($this->adminAccountExists())
			{
				$vars['content'] = 'installed';
			}
			else
			{
				$this->redirect('/install/step3');
			}
		}
		else
		{
			$vars['problems'] = array();
			$this->checkForPreInstallProblems($vars['problems']);

			if (empty($vars['problems']) && isset($params['pv']['continue']))
			{
				$this->redirect('/install/step1');
			}
		}

		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	public function action_step1($params)
	{
		$this->getCommonVars($vars);

		$vars['content'] = 'step1';

		if ($this->installed())
		{
			if ($this->adminAccountExists())
			{
				$vars['content'] = 'installed';
			}
			else
			{
				$this->redirect('/install/step3');
			}
		}
		elseif (isset($params['pv']['back']))
		{
			$this->redirect('/install');
		}
		else
		{
			$fields = array('site_url', 'site_name');
			
			// set default values
			$siteURL = dirname($this->urlToStatic('', true, true));
			if ($siteURL === 'http:')
			{
				$siteURL .= '//';
			}
			$vars['site_url'] = $this->session->get('site_url', $siteURL);
			$vars['site_name'] = $this->session->get('site_name', 'My Site');

			if (isset($params['pv']['continue']))
			{
				if ($this->validateStep1($params['pv'], $errors))
				{
					foreach ($fields as $field)
					{
						$vars[$field] = $this->session->set($field, $params['pv'][$field]);
					}
					$this->redirect('/install/step2');
				}
				else
				{
					$vars['errors'] = $errors;

					foreach ($fields as $field)
					{
						$vars[$field] = $params['pv'][$field];
					}
				}
			}
		}

		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	public function action_step2($params)
	{
		if (isset($params[0]))
		{
			if (isset($this->_db_plugs[$params[0]]))
			{
				$selectedDriver = $params[0];
			}
			$this->dropParam($params);
		}

		$this->getCommonVars($vars);

		$vars['content'] = 'step2';

		if ($this->installed())
		{
			if ($this->adminAccountExists())
			{
				$vars['content'] = 'installed';
			}
			else
			{
				$this->redirect('/install/step3');
			}
		}
		elseif (isset($params['pv']['back']))
		{
			$this->redirect('/install/step1');
		}
		else
		{
			$selectedDriver = $this->session->get('selected_driver', isset($selectedDriver) ? $selectedDriver : current(array_keys($this->_db_plugs)));
			
			// push selected driver plugin's view directory
			
			$plugInfo =& $this->_db_plugs[$selectedDriver][0];
			$plugIn =& $this->_db_plugs[$selectedDriver][1];
			
			$this->app->view()->pushViewDir(dirname($plugInfo['file']) . '/views');

			// set default values
			
			$vars['site_name'] = $this->session->get('site_name', 'My Site');
			$vars['db_dir'] = $this->_db_dir;
			$vars['page_base_url'] = $this->urlTo($params['base_uri']);
			$vars['db_drivers'] = $this->_db_drivers;
			$vars['selected_driver'] = $selectedDriver;
			
			$plugIn->setConnectionDefaults($vars);

			if (isset($params['pv']['continue']))
			{
				if ($this->validateStep2($params['pv'], $errors, $vars, $plugIn))
				{
					try
					{
						$config['label'] = 'default';
						$config['adapter'] = $params['pv']['selected_driver'];
						$config['charset'] = 'utf8';
						$config['persistent'] = false;
						$config['table_prefix'] = '';
						$config = array_merge($config, $this->_db_plugs[$config['adapter']][1]->buildConnectionParams($params['pv']));

						$dbConfig = $config;
						$config['db_config'] = $dbConfig;

						$config['site_url'] = $this->session->get('site_url');
						$config['site_name'] = $this->session->get('site_name');
						$this->initDB($config);
						
						$config['site_id'] = escher_site_id;
						$config['plug_cache_dir'] = $this->_cacheDir . '/code';
						$this->writeConfig($config);
						
						$this->initCache();
						
						$this->redirect('/install/step3');
					}
					catch (Exception $e)
					{
						$errors[] = '';
						$vars['warning'] = $e->getMessage();
					}
				}

				$vars['errors'] = $errors;

				foreach ($params['pv'] as $key => $val)
				{
					$vars[$key] = $val;
				}
			}
		}
		
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	public function action_step3($params)
	{
		$this->getCommonVars($vars);

		$vars['content'] = 'step3';

		if (!$this->installed())
		{
			$this->redirect('/install');
		}
		elseif ($this->adminAccountExists())
		{
			$vars['content'] = 'installed';
		}
		elseif (isset($params['pv']['back']))
		{
			$this->redirect('/install/step2');
		}
		else
		{
			$fields = array('account_name', 'account_email', 'account_login', 'account_password', 'account_password_again');
			
			// set default values
			
			$vars['account_name'] = '';
			$vars['account_email'] = '';
			$vars['account_login'] = 'admin';
			$vars['account_password'] = '';
			$vars['account_password_again'] = '';

			if (isset($params['pv']['continue']))
			{
				if ($this->validateStep3($params['pv'], $errors))
				{
					try
					{
						$userModel = $this->newModel('User');
						$authModel = $this->factory->manufacture('SparkAuthModel');

						$userModel->addUser
						(
							$this->factory->manufacture
							(
								'User', array
								(
									'name'=>$params['pv']['account_name'],
									'email'=>$params['pv']['account_email'],
									'login'=>$params['pv']['account_login'],
									'password'=>$authModel->encryptPassword($params['pv']['account_password']),
									'roles'=>array($this->factory->manufacture('Role', array('id'=>1))),
								)
							)
						);

						$this->redirect('/install/step4');
					}
					catch (Exception $e)
					{
						$errors[] = '';
						$vars['warning'] = $e->getMessage();
					}
				}
				
				$vars['errors'] = $errors;

				foreach ($fields as $field)
				{
					$vars[$field] = $params['pv'][$field];
				}
			}
		}
		
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	public function action_step4($params)
	{
		$this->getCommonVars($vars);

		$vars['content'] = 'step4';

		if (!$this->installed())
		{
			$this->redirect('/install');
		}
		elseif (!$this->adminAccountExists())
		{
			$this->redirect('/install/step3');
		}
		else
		{
			// set default values
			
			$vars['content_option'] = 2;
			
			if (isset($params['pv']['continue']))
			{
				if ($this->validateStep4($params['pv'], $errors))
				{
					try
					{
						switch ($params['pv']['content_option'])
						{
							case 1:
								break;
							
							case 2:
								$schema = $this->newModel('EscherSchema');
								$schema->installWelcomePage();
								break;

							case 3:
								$schema = $this->newModel('EscherSchema');
								$schema->installExampleSite();
								break;
						}
						
						$this->session->clear();
						$this->redirect('/install/success');
					}
					catch (Exception $e)
					{
						$errors[] = '';
						$vars['warning'] = $e->getMessage();
					}
				}
				
				$vars['errors'] = $errors;
				$vars['content_option'] = $params['pv']['content_option'];
			}
		}
		
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	public function action_success($params)
	{
		$this->getCommonVars($vars);

		$vars['content'] = 'success';

		if (!$this->installed())
		{
			$this->redirect('/install');
		}
		elseif (!$this->adminAccountExists())
		{
			$this->redirect('/install/step3');
		}
		
		$vars['config_file'] = $this->_configFile;
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function getCommonVars(&$vars)
	{
		$vars['escher_version'] = EscherVersion::CoreVersion;
		$vars['image_root'] = $this->urlToStatic('/img/');
		$vars['content'] = 'install';
	}

	//---------------------------------------------------------------------------

	private function installed()
	{
		$schema = $this->newModel('EscherSchema');
		return $schema->installed();
	}
	
	//---------------------------------------------------------------------------

	private function adminAccountExists()
	{
		$schema = $this->newModel('EscherSchema');
		return $schema->adminUserExists();
	}
	
	//---------------------------------------------------------------------------

	private function checkForPreInstallProblems(&$problems)
	{
		// check PHP requirements

		if (version_compare(PHP_VERSION, '5.2.0') < 0)
		{
			$problems[] = 'Escher requires PHP version 5.2.0 or later. Please upgrade PHP.';
		}
		
		foreach (array('date', 'pdo', 'session') as $extension)
		{
			if (!extension_loaded($extension))
			{
				$missingModules[$extension] = true;
				$problems[] = 'A required PHP extension is not enabled: ' . $extension;
			}
		}
		
		if (!isset($missingModules['pdo']))
		{
			$supportedDrivers = array_keys($this->_db_drivers);
			if (empty($supportedDrivers))
			{
				$problems[] = 'No database engine plugins enabled. Make sure that you have enabled at least one database plugin extension in the "install-config.php" file.';
			}
			else
			{
				$availableDrivers = PDO::getAvailableDrivers();
				$intersect = array_intersect($supportedDrivers, $availableDrivers);
				if (empty($intersect))
				{
					$problems[] = 'No supported PDO driver enabled. At least one of the following PDO drivers must be enabled: ' . implode(', ', $supportedDrivers);
				}
			}
		}
		
		// check that required files exist
		
		if (!file_exists($this->_configFile))
		{
			$problems[] = 'Could not find config file: ' . $this->_configFile;
		}
		elseif (!is_writable($this->_configFile))
		{
			$problems[] = 'Config file not writable: ' . $this->_configFile;
		}
		
		// check that directories have correct permissions, etc.
		
		if (!$this->validDir($this->_cacheDir, $error))
		{
			$problems[] = 'There is a problem with the cache directory located at ' . $this->_cacheDir . ': ' . $error;
		}
		
		return empty($problems);
	}
	
	//---------------------------------------------------------------------------

	private function checkForPostInstallProblems(&$problems)
	{
		// check that required files exist
		
		// check that directories have correct permissions

    $dirs = array
    (
    );
	}
	
	//---------------------------------------------------------------------------

	private function validateStep1($params, &$errors)
	{
		if (!$this->validURL($params['site_url']))
		{
			$errors['site_url'] = 'Invalid URL specified';
		}
		
		if (empty($params['site_name']))
		{
			$errors['site_name'] = 'Site Name is required';
		}
		
		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	private function validateStep2($params, &$errors, &$vars, $plugin)
	{
		// set errors
		
		if (!in_array($params['selected_driver'], array_keys($this->_db_drivers)))
		{
			$errors['selected_driver'] = 'Invalid database driver.';
		}
		
		$plugin->validateConnectionFields($params, $errors, $vars);
		
		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	private function validateStep3($params, &$errors)
	{
		$fields = array('account_name', 'account_email', 'account_login', 'account_password', 'account_password_again');

		// set errors
		
		foreach ($fields as $field)
		{
			if (empty($params[$field]))
			{
				$errors[$field] = 'This is a required field.';
			}
		}
		
		if (!$this->validName($params['account_name'], $error))
		{
			$errors['account_name'] = $error;
		}
		
		if (!$this->validEmail($params['account_email']))
		{
			$errors['account_email'] = 'Not a valid email address.';
		}
		
		if (!$this->validLogin($params['account_login'], $error))
		{
			$errors['account_login'] = $error;
		}
		
		if (!$this->validPassword($params['account_password'], $error))
		{
			$errors['account_password'] = $error;
		}
		
		if ($params['account_password_again'] !== $params['account_password'])
		{
			$errors['account_password_again'] = 'Password fields don\'t match';
		}
		
		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	private function validateStep4($params, &$errors)
	{
		return ($params['content_option'] >= 1) && ($params['content_option'] <= 3);
	}
	
	//---------------------------------------------------------------------------

	private function initDB($params)
	{
		$schema = $this->newModel('EscherSchema');
		$schema->create($params);
		$schema->init($params);
	}
	
	//---------------------------------------------------------------------------

	private function writeConfig($params)
	{
		$config = $this->render('site_config_tpl', $params, true);
		
		if (file_put_contents($this->_configFile, $config, FILE_APPEND) === false)
		{
			throw new SparkException('Could not write to configuration file.');
		}
	}
	
	//---------------------------------------------------------------------------

	private function initCache()
	{
		$htAccess = $this->render('cache_htaccess', NULL, true);
		
		$dirs = array('admin', 'code');

		foreach ($dirs as $dir)
		{
			if (!is_dir($path = $this->_cacheDir . '/' . $dir))
			{
				if (!mkdir($path, 0750))
				{
					throw new SparkException('Could not create directory: ' . $path);
				}
				if (file_put_contents($path . '/.htaccess', $htAccess, LOCK_EX) === false)
				{
					throw new SparkException('Could not create file: ' . $path . '/.htaccess');
				}
			}
		}
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
	
	//---------------------------------------------------------------------------

	private function validURL($url)
	{
		return SparkUtil::valid_url($url);
	}
	
	//---------------------------------------------------------------------------

	private function validEmail($email)
	{
		return SparkUtil::valid_email($email);
	}
	
	//---------------------------------------------------------------------------

	private function validName($name, &$error)
	{
		return true;
	}
	
	//---------------------------------------------------------------------------

	private function validLogin($login, &$error)
	{
		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/i', $login))
		{
			$error = 'Login name must start with a letter and consist of letters, digits and underscores.';
			return false;
		}
		return true;
	}
	
	//---------------------------------------------------------------------------

	private function validPassword($pw, &$error)
	{
		if (strlen($pw) < 8)
		{
			$error = 'Password must be at least 8 characters in length';
			return false;
		}
		return true;
	}
	
	//---------------------------------------------------------------------------

}
