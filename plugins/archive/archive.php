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

// -----------------------------------------------------------------------------

class _Archive extends EscherPlugin
{
	private $_plugDir;

	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
		
		$this->getConfig();					// install prefs if not present

		// listen for notification that content controller is ready for page type registration
		
		if ($this->app->is_admin())
		{
			$myInfo = $this->factory->getPlug('Archive');
			$this->_plugDir = dirname($myInfo['file']);

			$this->observer->observe(array($this, 'registerPageType'), 'escher:content:page_type:register');
			$this->observer->observe(array($this, 'archiveLoadLangFile'), array('SparkLang:load:preferences'));
		}
	}

	//---------------------------------------------------------------------------

	public function registerPageType($event)
	{
		$this->observer->notify('escher:content:page_type:request_add', 'ArchivePage', 'Archive');
		$this->observer->notify('escher:content:page_type:request_add', 'ArchiveDayIndex', 'Archive Day Index');
		$this->observer->notify('escher:content:page_type:request_add', 'ArchiveMonthIndex', 'Archive Month Index');
		$this->observer->notify('escher:content:page_type:request_add', 'ArchiveYearIndex', 'Archive Year Index');
	}

	//---------------------------------------------------------------------------

	public function archiveLoadLangFile($event, $file)
	{
		self::$lang->load($file, $this->_plugDir . '/languages');
	}

	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function getConfig()
	{
		if ($this->app->get_pref('archive_date_based_urls', NULL) === NULL)
		{
			$this->installPrefs();
		}

		$config = array
		(
			'date_based_urls' => $this->app->get_pref('archive_date_based_urls'),
			'breadcrumb_date_suffix' => $this->app->get_pref('archive_breadcrumb_date_suffix'),
		);

		return $config;
	}

	//---------------------------------------------------------------------------

	private function installPrefs()
	{
		$model = $this->newModel('Preferences');
		
		$model->addPrefs(array
		(
			array
			(
				'name' => 'archive_date_based_urls',
				'group_name' => 'plugins',
				'section_name' => 'archive',
				'position' => 10,
				'type' => 'yesnoradio',
				'val' => true,
			),
			array
			(
				'name' => 'archive_breadcrumb_date_suffix',
				'group_name' => 'plugins',
				'section_name' => 'archive',
				'position' => 20,
				'type' => 'yesnoradio',
				'val' => true,
			),
		));
	}

	//---------------------------------------------------------------------------
}
