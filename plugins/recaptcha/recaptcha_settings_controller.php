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

class RecaptchaSettingsController extends SettingsController
{
	private $_plugDir;

	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------

	public function action_preferences($params)
	{
		if ($this->app->get_pref('recaptcha_public_key') === NULL)
		{
			$this->installPrefs();
		}

		$myInfo = $this->factory->getPlug('RecaptchaSettingsController');
		$this->_plugDir = dirname($myInfo['file']);
		$this->observer->observe(array($this, 'recaptchaLoadoadLangFile'), array('SparkLang:load:preferences'));
		return parent::action_preferences($params);
	}
	
	//---------------------------------------------------------------------------

	public function recaptchaLoadoadLangFile($event, $file)
	{
		self::$lang->load($file, $this->_plugDir . '/languages');
	}

	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function installPrefs()
	{
		$model = $this->newModel('Preferences');
		
		$model->addPrefs(array
		(
			array
			(
				'name' => 'recaptcha_public_key',
				'group_name' => 'plugins',
				'section_name' => 'recaptcha',
				'position' => 10,
				'type' => 'text',
				'val' => '',
			),
			array
			(
				'name' => 'recaptcha_private_key',
				'group_name' => 'plugins',
				'section_name' => 'recaptcha',
				'position' => 20,
				'type' => 'text',
				'val' => '',
			),
		));
	}

	//---------------------------------------------------------------------------
}
