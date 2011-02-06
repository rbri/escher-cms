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

//------------------------------------------------------------------------------

class TidyPublishController extends PublishController
{	
	//---------------------------------------------------------------------------

	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected function display($output, $contentType = 'text/html', $status = NULL, $headers = NULL)
	{
		if (($contentType === 'text/html') && ($config = $this->getConfig()))
		{
			if ($this->app->get_pref('auto_tidy'))
			{
				$tidy = $this->factory->manufacture('TidyFilter');
				$output = $tidy->filter($output, $config);
			}
		}
		
		return parent::display($output, $contentType, $status, $headers);
	}

	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function getConfig()
	{
		if ($this->app->get_pref('auto_tidy') === NULL)
		{
			$this->installPrefs();
		}

		$config = array
		(
			'indent'       => $this->app->get_pref('tidy_indent', true),
			'clean'        => $this->app->get_pref('tidy_clean', false),
			'output-xhtml' => $this->app->get_pref('tidy_xhtml', true),
			'wrap'         => $this->app->get_pref('tidy_wrap', 0),
			'join-classes' => 0,
			'join-styles'  => 1,
			'merge-divs'   => 0,
//			'merge-spans'   => 0,
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
				'name' => 'auto_tidy',
				'group_name' => 'plugins',
				'section_name' => 'tidy',
				'position' => 1,
				'type' => 'yesnoradio',
				'val' => false,
			),
			array
			(
				'name' => 'tidy_indent',
				'group_name' => 'plugins',
				'section_name' => 'tidy',
				'position' => 2,
				'type' => 'yesnoradio',
				'val' => true,
			),
			array
			(
				'name' => 'tidy_clean',
				'group_name' => 'plugins',
				'section_name' => 'tidy',
				'position' => 3,
				'type' => 'yesnoradio',
				'val' => false,
			),
			array
			(
				'name' => 'tidy_xhtml',
				'group_name' => 'plugins',
				'section_name' => 'tidy',
				'position' => 4,
				'type' => 'yesnoradio',
				'val' => true,
			),
			array
			(
				'name' => 'tidy_wrap',
				'group_name' => 'plugins',
				'section_name' => 'tidy',
				'position' => 5,
				'type' => 'integer',
				'val' => 0,
			),
		));
	}

//---------------------------------------------------------------------------
	
}
