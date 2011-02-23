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

//------------------------------------------------------------------------------

class _PluginsModel extends SparkModel
{
	const PluginState_uninstalled = 0;
	const PluginStates_installed = 1;
	const PluginState_enabled = 2;

	const PluginRuns_nowhere = 0;
	const PluginRuns_backend = 1;
	const PluginRuns_frontend = 2;
	const PluginRuns_both = 3;
	
	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function addPlugin($plugin)
	{
		$db = $this->loadDB();
	
		$row = array
		(
			'family' => $plugin['family'],
			'name' => $plugin['name'],
			'extends' => !empty($plugin['extends']) ? $plugin['extends'] : '',
			'load_order' => !empty($plugin['load_order']) ? $plugin['load_order'] : 0,
			'runs_where' => !empty($plugin['runs_where']) ? $plugin['runs_where'] : self::PluginRuns_both,
			'auto_load' => !empty($plugin['load_order']),
			'state' => intval(max(0, min(self::PluginState_enabled, $plugin['state']))),
			'code' => !empty($plugin['code']) ? $plugin['code'] : '',
		);

		$db->insertRow('plugin', $row);
	}

	//---------------------------------------------------------------------------
	
	public function fetchPluginInfo($states = self::PluginState_enabled, $runs = self::PluginRuns_both)
	{
		$states = (array)$states;

		$db = $this->loadDB();

		$where = $db->buildFieldIn('plugin', 'state', $states) . ' AND (runs_where & ?)';
		$bind = $states;
		$bind[] = intval($runs);
		
		$rows = $db->selectRows('plugin', 'id, family, name, extends, load_order, runs_where, auto_load, state', $where, $bind);
		foreach($rows as $key => $row)
		{
			$rows[$key]['order'] = $row['load_order'];
			unset($rows[$key]['load_order']);
		}
		return $rows;
	}

	//---------------------------------------------------------------------------
	
	public function fetchPluginCode($pluginName)
	{
		$db = $this->loadDB();

		$row = $db->selectRow('plugin', 'code', 'name=?', $pluginName);
		return $row['code'];
	}

//------------------------------------------------------------------------------
	
}
