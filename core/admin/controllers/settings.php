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

require(escher_core_dir.'/publish/models/user_objects.php');

class _SettingsController extends EscherAdminController
{	
	private $_tabs;

	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();
		$this->app->build_tabs($this->_tabs, array('preferences', 'roles', 'users', 'revisions', 'plugins'), 'settings');
	}

	//---------------------------------------------------------------------------

	public function &get_tabs()
	{
		return $this->_tabs;
	}

	//---------------------------------------------------------------------------

	public function action_index($params)
	{
		$this->session->flashKeep('html_alert');

		if (in_array('preferences', $this->_tabs))
		{
			$this->redirect('/settings/preferences');
		}
		elseif ($first = current($this->_tabs))
		{
			$this->redirect('/settings/'.$first);
		}
		else
		{
			$this->getCommonVars($vars);
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}
	}

	//---------------------------------------------------------------------------

	public function action_preferences($params)
	{
		if (!empty($params[0]))
		{
			$prefstab = $params[0];
			$this->dropParam($params);
		}
		else
		{
			$prefstab = 'basic';
		}
		
		$this->getCommonVars($vars);
		$vars['selected_subtab'] = 'preferences';

		$prefs = array();
		
		require($this->config->get('core_dir') . '/admin/lib/form_field_generator.php');
		$markupGenerator = $this->factory->manufacture('FormFieldGenerator');
		$markupGenerator->getCallbacks($callbacks);
		
		$model = $this->newModel('Preferences');

		foreach ($model->fetchPrefs() as $row)
		{
			$prefs[$row['group_name']][$row['section_name']][] = $row;
		}
		
		// eliminate prefs tabs for which user does not have permissions
		
		$curUser = $this->app->get_user();
		foreach (array_keys($prefs) as $group)
		{
			if (!$curUser->allowed('settings:preferences:'.$group))
			{
				unset($prefs[$group]);
			}
		}
		
		if (empty($prefs))
		{
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}

		if (!isset($prefs[$prefstab]))
		{
			$prefstab = current(array_keys($prefs));
		}

		$groupPrefs =& $prefs[$prefstab];
		
		// send notification for any plugins that want to inject their own markup handlers
		
		$this->observer->notify('escher:settings:prefs:' . $prefstab, $prefstab, $groupPrefs, (object)array('callbacks'=>&$callbacks));
	
		// remove any prefs without a markup callback
		
		foreach (array_keys($prefs) as $key0)
		{
			foreach (array_keys($prefs[$key0]) as $key1)
			{
				foreach (array_keys($prefs[$key0][$key1]) as $key2)
				{
					$type = $prefs[$key0][$key1][$key2]['type'];
					if (!isset($callbacks[$type]) || !is_callable($callbacks[$type]))
					{
						unset($prefs[$key0][$key1][$key2]);
					}
				}
				if (empty($prefs[$key0][$key1]))
				{
					unset($prefs[$key0][$key1]);
				}
			}
			if (empty($prefs[$key0]))
			{
				unset($prefs[$key0]);
			}
		}
		
		// create tabs
		
		$prefstabs = array();
		foreach ($prefs as $group_name => $row)
		{
			$prefstabs[$group_name] = $group_name;
		}
		
		if (isset($params['post']['save']))
		{
			$savePrefs = $groupPrefs;		// make a copy so we can tell which prefs were actually changed
			
			if ($this->validatePrefs($callbacks, $params['post'], $groupPrefs, $errors))
			{
				// determine which prefs need to be saved
				
				$changedPrefs = array();
				foreach ($groupPrefs as $key0 => $section)
				{
					foreach ($section as $key1 => $pref)
					{
						if ($pref['val'] !== $savePrefs[$key0][$key1]['val'])
						{
							$changedPrefs[$pref['name']] = $pref;
						}
					}
				}
	
				// save prefs

				if (!empty($changedPrefs))
				{
					// react to changes in built-in prefs
					
					if (isset($changedPrefs['theme']))								// force code cache flush
					{
						$changedPrefs['plug_cache_flush'] = array('name'=>'plug_cache_flush', 'val'=>1);
					}

					if (isset($changedPrefs['page_cache_active']))				// force page cache flush
					{
						$changedPrefs['page_cache_flush'] = array('name'=>'page_cache_flush', 'val'=>1);
					}

					if (isset($changedPrefs['partial_cache_active']))			// force partial cache flush
					{
						$changedPrefs['partial_cache_flush'] = array('name'=>'partial_cache_flush', 'val'=>1);
					}

					$model->updatePrefs($changedPrefs);
					$changedPrefsNames = implode(', ', array_keys($changedPrefs));
					$this->observer->notify('escher:site_change:settings:preferences:change', $changedPrefsNames);
					$vars['notice'] = 'Preferences saved.';
				}
			}
		}
		else
		{
			$vars['notice'] = $this->session->flashGet('notice');
			$vars['warning'] = $this->session->flashGet('warning');
		}
		
		$vars['selected_prefstab'] = $prefstab;
		$vars['prefstabs'] = $prefstabs;
		$vars['prefs'] = $groupPrefs;
		$vars['callbacks'] = $callbacks;
		$vars['lang'] = self::$lang;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		self::$lang->load('preferences');
		$this->observer->notify('escher:render:before:settings:preference:list', $groupPrefs);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	public function action_roles($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->roles_add($this->dropParam($params));
			case 'edit':
				return $this->roles_edit($this->dropParam($params));
			case 'delete':
				return $this->roles_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->roles_list($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_users($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->users_add($this->dropParam($params));
			case 'edit':
				return $this->users_edit($this->dropParam($params));
			case 'delete':
				return $this->users_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->users_list($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_revisions($params)
	{
		$this->getCommonVars($vars);
		$vars['selected_subtab'] = 'revisions';
		
		$this->observer->notify('escher:render:before:settings:revision:list');
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	public function action_plugins($params)
	{
		$this->getCommonVars($vars);
		$vars['selected_subtab'] = 'plugins';
		
		$this->observer->notify('escher:render:before:settings:plugin:list');
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	public function action_upgrade($params)
	{
		$this->getCommonVars($vars);
		$vars['selected_subtab'] = $vars['subtabs'][] = 'upgrade';

		$curUser = $this->app->get_user();

		if (!$curUser->allowed('settings:upgrade'))
		{
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}
		
		if (EscherVersion::validateSchemaVersion($this->app->get_pref('schema')))
		{
			$vars['content'] = 'settings/upgrade_not_needed';
		}
		
		elseif (isset($params['post']['upgrade']))
		{
			try
			{
				$model = $this->newModel('EscherSchemaUpgrade');
				$model->upgrade();
				$vars['content'] = 'settings/upgrade_success';
			}
			catch (Exception $e)
			{
				$vars['warning'] = $e->getMessage();
			}
			
			$this->observer->notify('EventLog:logevent', 'upgraded database to schema version ' . EscherVersion::SchemaVersion);

			// since we likely just upgraded the code, clear code caches

			$this->observer->notify('Spark:cache:request_flush');
			$this->observer->notify('escher:cache:request_flush:plug');
			$this->observer->notify('escher:site_change');
		}

		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected function getCommonVars(&$vars)
	{
		parent::getCommonVars($vars);
		$vars['subtabs'] = $this->_tabs;
		$vars['selected_tab'] = 'settings';
	}

	//---------------------------------------------------------------------------

	protected function validatePrefs($callbacks, $params, &$prefs, &$errors)
	{
		foreach ($prefs as $key1 => $section)
		{
			foreach ($section as $key2 => $pref)
			{
				if (isset($params[$pref['name']]))
				{
					$thisPref =& $prefs[$key1][$key2];
					$thisPref['val'] = $params[$pref['name']];
					
					if (!empty($pref['type']) && isset($callbacks[$pref['type']]))
					{
						$callback = $callbacks[$pref['type']];
						if (is_array($callback))
						{
							$callback[1] = 'validate_' . $callback[1];
						}
						else
						{
							$callback = 'validate_' . $callback;
						}
						if (is_callable($callback))
						{
							// call_user_func() won't do pass by reference, and this is faster anyway
							
							if (is_array($callback))
							{
								if (!$valid = $callback[0]->$callback[1]($thisPref))
								{
									$errors[$pref['name']] = 'Field error';
								}
							}
							else
							{
								if (!$valid = $callback($thisPref))
								{
									$errors[$pref['name']] = 'Field error';
								}
							}
						}
					}
				}
			}
		}

		return empty($errors);
	}

	//---------------------------------------------------------------------------

	protected function roles_list($params)
	{
		$model = $this->newModel('User');
		
		$roles = $model->fetchAllRoles(true);
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_add'] = $curUser->allowed('settings:roles:add');
		$vars['can_edit'] = $curUser->allowed('settings:roles:edit');
		$vars['can_delete'] = $curUser->allowed('settings:roles:delete');

		$vars['selected_subtab'] = 'roles';
		$vars['action'] = 'list';
		$vars['roles'] = $roles;
		$vars['notice'] = $this->session->flashGet('notice');

		$this->observer->notify('escher:render:before:settings:role:list', $roles);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function roles_add($params)
	{
		$model = $this->newModel('User');
		
		$role = $this->factory->manufacture('Role', array());

		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_add'] = $curUser->allowed('settings:roles:add');
		$vars['can_save'] = $vars['can_add'];

		if (isset($params['post']['save']) || isset($params['post']['continue']))
		{
			// build role object from form data
			
			$this->buildRole($params['post'], $role);

			if (!$vars['can_add'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->roleExists($role->name))
			{
				$errors['role_name'] = 'A role with this name already exists.';
			}
			elseif ($this->validateRole($params['post'], $errors))
			{
				// add role object
	
				try
				{
					$model->addRole($role);
					$this->observer->notify('escher:db_change:settings:role:add', $role);
					$this->session->flashSet('notice', 'Role added successfully.');
					if (isset($params['post']['continue']))
					{
						$this->redirect('/settings/roles/edit/'.$role->id);
					}
					else
					{
						$this->redirect('/settings/roles');
					}
				}
				catch(SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}

		$perms = $model->fetchAllPermissions(true);
		
		$permissions = array();
		foreach ($perms as $group)
		{
			foreach ($group as $name)
			{
				$val = isset($role->permissions[$name]);
				$indPerms = explode(':', $name);
				$atPerms =& $permissions;
				foreach ($indPerms as $indPerm)
				{
					if (!isset($atPerms[$indPerm]))
					{
						$atPerms[$indPerm]['val'] = $val;
					}
					$atPerms =& $atPerms[$indPerm];
				}
				unset($atPerms);
			}
		}

		$vars['selected_subtab'] = 'roles';
		$vars['action'] = 'add';
		$vars['permissions'] = $permissions;
		$vars['role'] = $role;
		$vars['lang'] = self::$lang;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		self::$lang->load('permissions');
		$this->observer->notify('escher:render:before:settings:role:add', $role);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function roles_edit($params)
	{
		if (!$roleID = @$params['post']['role_id'])
		{
			if (!$roleID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'role not found'));
			}
		}
		
		if ($roleID == 1)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'role not found'));
		}
		
		$model = $this->newModel('User');
		
		if (!$role = $model->fetchRole($roleID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'role not found'));
		}
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_add'] = $curUser->allowed('settings:roles:add');
		$vars['can_edit'] = $curUser->allowed('settings:roles:edit');
		$vars['can_delete'] = $curUser->allowed('settings:roles:delete');
		$vars['can_save'] = $vars['can_edit'];

		if (isset($params['post']['save']) || isset($params['post']['continue']))
		{
			// build role object from form data
			
			$oldName = $role->name;
			$this->buildRole($params['post'], $role);
			
			if (!$vars['can_edit'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($role->name !== $oldName) && $model->roleExists($role->name))
			{
				$errors['role_name'] = 'A role with this name already exists.';
			}
			elseif ($this->validateRole($params['post'], $errors))
			{
				// update role object
	
				try
				{
					$model->updateRole($role);
					$this->observer->notify('escher:db_change:settings:role:edit', $role);
					if (isset($params['post']['save']))
					{
						$this->session->flashSet('notice', 'Role saved successfully.');
						$this->redirect('/settings/roles');
					}
					$vars['notice'] = 'Role saved successfully.';
				}
				catch(SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$model->fetchRolePermissions($role);
		}

		$perms = $model->fetchAllPermissions(true);
		
		$permissions = array();
		foreach ($perms as $group)
		{
			foreach ($group as $name)
			{
				$val = isset($role->permissions[$name]);
				$indPerms = explode(':', $name);
				$atPerms =& $permissions;
				foreach ($indPerms as $indPerm)
				{
					if (!isset($atPerms[$indPerm]))
					{
						$atPerms[$indPerm]['val'] = $val;
					}
					$atPerms =& $atPerms[$indPerm];
				}
				unset($atPerms);
			}
		}

		$vars['selected_subtab'] = 'roles';
		$vars['action'] = 'edit';
		$vars['permissions'] = $permissions;
		$vars['role'] = $role;
		$vars['lang'] = self::$lang;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		self::$lang->load('permissions');
		$this->observer->notify('escher:render:before:settings:role:edit', $role);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function roles_delete($params)
	{
		if (!$roleID = @$params['post']['role_id'])
		{
			if (!$roleID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'role not found'));
			}
		}

		if ($roleID == 1)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'role not found'));
		}

		$model = $this->newModel('User');

		if (!$role = $model->fetchRole($roleID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'role not found'));
		}
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_edit'] = $curUser->allowed('settings:roles:edit');
		$vars['can_delete'] = $curUser->allowed('settings:roles:delete');

		if (isset($params['post']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->deleteRoleByID($roleID);
				$this->observer->notify('escher:db_change:settings:role:delete', $role);
				$this->session->flashSet('notice', 'Role deleted successfully.');
				$this->redirect('/settings/roles');
			}
		}

		$vars['action'] = 'delete';
		$vars['selected_subtab'] = 'roles';
		$vars['role_id'] = $roleID;
		$vars['role_name'] = $role->name;

		$this->observer->notify('escher:render:before:settings:role:delete', $role);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function buildRole($params, $role)
	{
		// build role object

		$role->name = $params['role_name'];
		
		$permissions = array();
		foreach ($params as $key=>$val)
		{
			if (preg_match('/perm_(.*)/', $key, $matches))
			{
				$name = str_replace('_', ':', $matches[1]);
				$permissions[$name] = true;
			}
		}
		$role->permissions = $permissions;
	}
	
	//---------------------------------------------------------------------------

	protected function validateRole($params, &$errors)
	{
		$errors = array();
		
		// set errors
		
		if (empty($params['role_name']))
		{
			$errors['role_name'] = 'Role name is required.';
		}
		elseif (!preg_match('/^[a-z]/i', $params['role_name']))		// name must begin with a letter
		{
			$errors['role_name'] = 'Role name must begin with a letter.';
		}

		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	protected function users_list($params)
	{
		$model = $this->newModel('User');
		
		$users = $model->fetchAllUsers(true, false, true);
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_add'] = $curUser->allowed('settings:users:add');
		$vars['can_edit'] = $curUser->allowed('settings:users:edit');
		$vars['can_delete'] = $curUser->allowed('settings:users:delete');

		$vars['selected_subtab'] = 'users';
		$vars['action'] = 'list';
		$vars['users'] = $users;
		$vars['notice'] = $this->session->flashGet('notice');
		$vars['warning'] = $this->session->flashGet('warning');

		$this->observer->notify('escher:render:before:settings:user:list', $users);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function users_add($params)
	{
		$model = $this->newModel('User');
		
		$roles = $model->fetchAllRoles(true);
		$user = $this->factory->manufacture('User', array());

		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_add'] = $curUser->allowed('settings:users:add');
		$vars['can_save'] = $vars['can_add'];
		$vars['show_mail_password'] = true;

		if (isset($params['post']['save']) || isset($params['post']['continue']))
		{
			// build user object from form data
			
			$this->buildUser($params['post'], $roles, $user);

			if (!empty($params['post']['mail_password']))
			{
				$vars['mail_password'] = true;
			}

			if (!$vars['can_add'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->userLoginExists($user->login))
			{
				$errors['user_login'] = 'A user with this login already exists.';
			}
			elseif ($model->userEmailExists($user->email))
			{
				$errors['user_email'] = 'A user with this email address already exists.';
			}
			elseif ($this->validateUser($params['post'], true, $errors))
			{
				// update user object
	
				try
				{
					$authModel = $this->factory->manufacture('SparkAuthModel');
					$savePassword = $user->password;
					$user->password = $authModel->encryptPassword($user->password);
	
					$model->addUser($user);
					$user->password = '';
					$this->observer->notify('escher:db_change:settings:user:add', $user);
	
					$message = 'User added successfully.';
	
					if (isset($params['post']['mail_password']))
					{
						try
						{
							$this->sendUserPassword($curUser, $user, $savePassword);
							$message .= ' Password email sent successfully.';
						}
						catch (Exception $e)
						{
							$warning = 'Password email could not be sent. ' . $e->getMessage();
						}
					}
	
					$this->session->flashSet('notice', $message);
					if (!empty($warning))
					{
						$this->session->flashSet('warning', $warning);
					}
	
					if (isset($params['post']['continue']))
					{
						$this->redirect('/settings/users/edit/'.$user->id);
					}
					else
					{
						$this->redirect('/settings/users');
					}
				}
				catch(SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		
		$vars['selected_subtab'] = 'users';
		$vars['action'] = 'add';
		$vars['roles'] = $roles;
		$vars['user'] = $user;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:settings:user:add', $user);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function users_edit($params)
	{
		if (!$userID = @$params['post']['user_id'])
		{
			if (!$userID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'user not found'));
			}
		}
		
		$model = $this->newModel('User');
		
		if (!$user = $model->fetchUser($userID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'user not found'));
		}

		$user->password = '';

		if ($user->id == 1)						// admin user only has one role: Administrator
		{
			$roles[1] = $model->fetchRole(1);
		}
		else
		{
			$roles = $model->fetchAllRoles(true);
		}

		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_add'] = $curUser->allowed('settings:users:add');
		$vars['can_edit'] = $curUser->allowed('settings:users:edit');
		$vars['can_delete'] = $curUser->allowed('settings:users:delete');
		$vars['can_save'] = $vars['can_edit'];
		$vars['show_mail_password'] = ($userID != $curUser->id);

		if (isset($params['post']['save']) || isset($params['post']['continue']))
		{
			// build user object from form data
			
			$oldLogin = $user->login;
			$oldEmail = $user->email;
			$this->buildUser($params['post'], $roles, $user);

			if (!empty($params['post']['mail_password']))
			{
				$vars['mail_password'] = true;
			}

			if (!$vars['can_edit'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($user->login !== $oldLogin) && $model->userLoginExists($user->login))
			{
				$errors['user_login'] = 'A user with this login already exists.';
			}
			elseif (($user->email !== $oldEmail) && $model->userEmailExists($user->email))
			{
				$errors['user_email'] = 'A user with this email address already exists.';
			}
			elseif ($this->validateUser($params['post'], false, $errors))
			{
				// update user object
	
				try
				{
					if (!empty($user->password))
					{
						$authModel = $this->factory->manufacture('SparkAuthModel');
						$savePassword = $user->password;
						$user->password = $authModel->encryptPassword($user->password);
					}
					$model->updateUser($user);
					$user->password = '';
					$this->observer->notify('escher:db_change:settings:user:edit', $user);
					
					$message = 'User saved successfully.';
					
					if (isset($params['post']['mail_password']))
					{
						try
						{
							$this->sendUserPassword($curUser, $user, $savePassword);
							$message .= ' Password email sent successfully.';
						}
						catch (Exception $e)
						{
							$warning = 'Password email could not be sent. ' . $e->getMessage();
						}
					}
	
					if (isset($params['post']['save']))
					{
						$this->session->flashSet('notice', $message);
						if (!empty($warning))
						{
							$this->session->flashSet('warning', $warning);
						}
						$this->redirect('/settings/users');
					}
					
					$vars['notice'] = $message;
					if (!empty($warning))
					{
						$vars['warning'] = $warning;
					}
				}
				catch(SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$model->fetchUserRoles($user);
			$vars['notice'] = $this->session->flashGet('notice');
			$vars['warning'] = $this->session->flashGet('warning');
		}
		
		$vars['selected_subtab'] = 'users';
		$vars['action'] = 'edit';
		$vars['roles'] = $roles;
		$vars['user'] = $user;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:settings:user:edit', $user);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function users_delete($params)
	{
		if (!$userID = @$params['post']['user_id'])
		{
			if (!$userID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'user not found'));
			}
		}

		if ($userID == 1)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'user not found'));
		}

		$model = $this->newModel('User');

		if (!$user = $model->fetchUser($userID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'user not found'));
		}
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_edit'] = $curUser->allowed('settings:users:edit');
		$vars['can_delete'] = $curUser->allowed('settings:users:delete');

		if (isset($params['post']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->deleteUserByID($userID);
				$this->observer->notify('escher:db_change:settings:user:delete', $user);
				$this->session->flashSet('notice', 'User deleted successfully.');
				$this->redirect('/settings/users');
			}
		}

		$vars['selected_subtab'] = 'users';
		$vars['action'] = 'delete';
		$vars['user_id'] = $userID;
		$vars['user_name'] = $user->name;

		$this->observer->notify('escher:render:before:settings:user:delete', $user);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function buildUser($params, $roles, $user)
	{
		// build user object

		$user->name = $params['user_name'];
		$user->email = $params['user_email'];
		$user->login = $params['user_login'];
		if (!empty($params['user_password']))
		{
			$user->password = $params['user_password'];
		}
		
		$user->roles = array();
		foreach ($params as $key=>$val)
		{
			if (preg_match('/role_(\d+)/', $key, $matches))
			{
				$roleID = $matches[1];
				$user->roles[$roleID] = $roles[$roleID];
			}
		}
		
		// user 1 is always an administrator
		
		if ($user->id == 1)
		{
			$user->roles[1] = $roles[1];
		}
	}
	
	//---------------------------------------------------------------------------

	protected function validateUser($params, $requirePassword = true, &$errors)
	{
		$errors = array();
		
		// set errors
		
		if (empty($params['user_name']))
		{
			$errors['user_name'] = 'Real name is required.';
		}
		elseif (!preg_match('/^[a-z][a-z\s]+$/i', $params['user_name']))
		{
			$errors['user_name'] = 'User name must contain only letters.';
		}

		if (empty($params['user_email']))
		{
			$errors['user_email'] = 'Email is required.';
		}
		elseif (!SparkUtil::valid_email($params['user_email']))
		{
			$errors['user_email'] = 'Not a valid email address.';
		}

		if (empty($params['user_login']))
		{
			$errors['user_login'] = 'Login name is required.';
		}
		elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/i', $params['user_login']))
		{
			$errors['user_login'] = 'User name must begin with a letter and contain only letters and digits.';
		}

		if (empty($params['user_password']))
		{
			if ($requirePassword)
			{
				$errors['user_password'] = 'Password is required.';
			}
			elseif (!empty($params['mail_password']))
			{
				$errors['user_password'] = 'Cannot mail an empty password.';
			}
		}
		elseif (strlen($params['user_password']) < 8)
		{
			$errors['user_password'] = 'Password too short. At least 8 characters required.';
		}

		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	protected function sendUserPassword($fromUser, $toUser, $password)
	{
		$mailer = $this->factory->manufacture('SparkMailer');
		$mailer->isHTML(false)->sender($fromUser->email)->from($fromUser->email)->fromName($fromUser->name)->addAddress($toUser->email, $toUser->name);
		$mailer->subject('Your ' . $this->app->get_pref('site_name') . ' Administrator Account');
		$mailer->body('Your password is: ' . $password . "\n\n" . 'Please log in and choose a new password as soon as possible.');
		$mailer->send();
	}
	
	//---------------------------------------------------------------------------

}
