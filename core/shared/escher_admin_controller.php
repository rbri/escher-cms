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

require('escher_base.php');

//------------------------------------------------------------------------------

class EscherAdminController extends SparkController
{
	private $_vars;
	
	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{		
		parent::__construct();

		$this->getCommonVars($this->_vars);
	}
	
	//---------------------------------------------------------------------------

	public function _before_dispatch($method, $params)
	{
		if (!$this->app->is_installed())
		{
			if ((!$this instanceof SettingsController) || ($method !== 'action_install'))
			{
				$this->redirectStatic('/install.php');
			}
			return true;
		}
		
		if (!parent::_before_dispatch($method, $params))
		{
			return false;
		}
		
		// logout page is always accessible
		
		if ($method === 'action_logout')
		{
			return true;
		}
		
		// login page is always accessible
		
		if ($method === 'action_login')
		{
			if ($this->app->get_pref('require_secure_login') && !SparkUtil::is_https())
			{
				$this->redirect($params['base_uri'], true);
			}
			$vars = $this->_vars;
			$vars['tabs'] = array('settings');
			$vars['subtabs'] = array('login');
			$vars['selected_subtab'] = 'login';
			parent::setAuthView('main', $vars);
			return true;
		}

		$this->_vars['logged_in'] = true;
		
		if (!EscherVersion::validateSchemaVersion($this->app->get_pref('schema')))
		{
			if ((!$this instanceof SettingsController) || ($method !== 'action_upgrade'))
			{
				$this->redirect('/settings/upgrade');
			}
		}

		$curUser = $this->app->get_user();

		$subTab = str_replace('action_', '', $method);
		if ($subTab === 'index')
		{
			$subTab = '';
		}
		$this->_vars['selected_subtab'] = $subTab;
		
		$perm = str_replace('_', '-', $this->_vars['selected_tab']);
		if (!$curUser->allowed($perm))
		{
			$this->_vars['subtabs'] = array();
			throw new SparkHTTPException_Forbidden(NULL, $this->_vars);
		}

		if (!empty($subTab))
		{
			$perm .= ':' . str_replace('_', '-', $this->_vars['selected_subtab']);
			if (!$curUser->allowed($perm))
			{
				$this->_vars['subtabs'] = $this->get_tabs();
				throw new SparkHTTPException_Forbidden(NULL, $this->_vars);
			}
		}
		
		if ($action = @$params[0])
		{
			$perm .= ':' . $action;
			if (!$curUser->allowed($perm))
			{
				$this->_vars['subtabs'] = $this->get_tabs();
				throw new SparkHTTPException_Forbidden(NULL, $this->_vars);
			}
		}

		return true;
	}

	//---------------------------------------------------------------------------

	protected function getCommonVars(&$vars)
	{
		if (!isset($this->_vars))
		{
			$this->_vars = array
			(
				'site_url' => $this->app->get_pref('site_url'),
				'escher_version' => EscherVersion::CoreVersion,
				'tabs' => $this->app->get_tabs(),
				'image_root' => $this->urlToStatic('/img/'),
			);
		}

		$vars = $this->_vars;
	}

	//---------------------------------------------------------------------------

	protected function updateObjectCreated($object)
	{
		$curUser = $this->app->get_user();
		$object->author_id = $object->editor_id = $curUser->id;
		$object->author_name = $object->editor_name = $curUser->name;
		$object->created = $object->edited = $this->app->get_date();
	}
	
	//---------------------------------------------------------------------------

	protected function updateObjectEdited($object)
	{
		$curUser = $this->app->get_user();
		$object->editor_id = $curUser->id;
		$object->editor_name = $curUser->name;
		$object->edited = $this->app->get_date();
	}

	//---------------------------------------------------------------------------

	protected function getDBErrorMsg($e)
	{
		$msg = $e->getMessage();
		if ($e->dbErrorCode() == SparkDBException::kDuplicateRecord)
		{
			$msg = 'Attempt to create a duplicate record (' . $msg . ').';
		}
		return $msg;
	}
	
	//---------------------------------------------------------------------------
	
}
