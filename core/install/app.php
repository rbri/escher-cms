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

define('escher_core_dir', $config['core_dir']);

require($config['sparkplug_dir'].'/sparkplug.php');
require($config['core_dir'].'/shared/escher_base.php');

class _EscherInstaller extends SparkApplication
{
	//---------------------------------------------------------------------------
	
	public function __construct($spark, $config)
	{
		parent::__construct($spark, $config);

		$this->pushModelDir($this->config->get('core_dir') . '/admin/models');
		$this->pushModelDir($this->config->get('core_dir') . '/publish/models');

		// load "always-enabled" plugins, listed in config
		
		if (!empty($config['plugins']))
		{
			$spark->findPlugs($config['plugins'], $this->config->get('app_plug_dir'));
		}
	}

	//---------------------------------------------------------------------------

	public function get_user()
	{
		return $this->factory->manufacture('User', array('id'=>1));
	}

	//---------------------------------------------------------------------------
}	

// Instantiate the application object.

$app = $spark->manufacture('EscherInstaller', $spark, $config);
unset($config);

// Run it.

$app->run();
