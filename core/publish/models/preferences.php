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

class _PreferencesModel extends SparkModel
{
	
	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function addPref($pref)
	{
		$db = $this->loadDB();
	
		$row = array
		(
			'name' => strtolower(str_replace(' ', '_', $pref['name'])),
			'group_name' => strtolower(str_replace(' ', '_', $pref['group_name'])),
			'section_name' => strtolower(str_replace(' ', '_', $pref['section_name'])),
			'position' => intval($pref['position']),
			'type' => $pref['type'] ? $pref['type'] : '',
			'validation' => $pref['validation'] ? $pref['validation'] : '',
			'val' => $pref['val'],
		);
		
		if (!empty($pref['user_id']))
		{
			$row['user_id'] = $pref['user_id'];
		}

		$db->insertRow('pref', $row);
	}

	//---------------------------------------------------------------------------
	
	public function addPrefs($prefs)
	{
		foreach ($prefs as $pref)
		{
			if (!isset($pref['position']))
			{
				$pref['position'] = 0;
			}
			if (!isset($pref['validation']))
			{
				$pref['validation'] = '';
			}
			
			$this->addPref($pref);
		}
	}

	//---------------------------------------------------------------------------
	
	public function updatePrefs($prefs, $userID = 0)
	{
		$db = $this->loadDB();
	
		foreach ($prefs as $pref)
		{
			$name = strtolower(str_replace(' ', '_', $pref['name']));
			$row = array
			(
				'val' => $pref['val'],
			);
			$db->updateRows('pref', $row, 'user_id=? AND name=?', array($userID, $name));
		}
	}

	//---------------------------------------------------------------------------
	
	public function fetchPref($name, $userID = 0)
	{
		$db = $this->loadDB();

		$row = $db->selectRow('pref', '*', 'user_id=? AND name=?', array($userID, $name));

		return $row;
	}

	//---------------------------------------------------------------------------
	
	public function fetchPrefVal($name, $userID = 0)
	{
		$db = $this->loadDB();

		$row = $db->selectRow('pref', 'val', 'user_id=? AND name=?', array($userID, $name));

		return $row['val'];
	}

	//---------------------------------------------------------------------------
	
	public function fetchPrefs($userID = 0, $groupName = NULL)
	{
		$db = $this->loadDB();

		$where = 'user_id=?';
		$bind[] = $userID;

		if ($groupName)
		{
			$where .= ' AND group_name=?';
			$bind[] = $groupName;
		}

		return $db->query($db->buildSelect('pref', '*', NULL, $where, $orderBy = 'group_name, section_name, position'), $bind)->rows();
	}

	//---------------------------------------------------------------------------
	
	public function fetchPrefVals($userID = 0, $groupName = NULL)
	{
		$db = $this->loadDB();

		$where = 'user_id=?';
		$bind[] = $userID;

		if ($groupName)
		{
			$where .= ' AND group_name=?';
			$bind[] = $groupName;
		}

		$rows = $db->selectRows('pref', 'name, val', $where, $bind);
		
		$prefs = array();
		foreach ($rows as $row)
		{
			$prefs[$row['name']] = $row['val'];
		}
		return $prefs;
	}

//------------------------------------------------------------------------------
	
}
