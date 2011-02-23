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

class _EventLogModel extends SparkModel
{
	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function installed()
	{
		try
		{
			$db = $this->loadDB();
			@$db->countRows('log');
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function install()
	{
		$this->installPrefs();
		$this->installPerms();
		$this->installTables();
	}
	
	//---------------------------------------------------------------------------
	
	public function clear()
	{
		$db = $this->loadDB();
		$db->deleteRows('log');
	}
	
	//---------------------------------------------------------------------------
	
	public function logEvent($event, $userID)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'time' => $now,
			'event' => $event,
			'user_id' => $userID,
		);

		$db->insertRow('log', $row);
	}

	//---------------------------------------------------------------------------
	
	public function countEvents()
	{
		return $this->loadDB()->countRows('log');
	}

	//---------------------------------------------------------------------------
	
	public function getEvents($limit = 25, $offset = 0)
	{
		$db = $this->loadDB();
	
		$joins[] = array('leftTable'=>'log', 'table'=>'user', 'conditions'=>array(array('leftField'=>'user_id', 'rightField'=>'id', 'joinOp'=>'=')));
		$sql = $db->buildSelect('log', '{log}.*, {user}.name AS user', $joins, NULL, '{log}.time DESC, {log}.id DESC', $limit, $offset);
		
		$events = array();
		foreach ($db->query($sql)->rows() as $row)
		{
			$row['time'] = $this->app->format_date($row['time']);
			$events[] = new EventLogEvent($row);
		}

		return $events;
	}

	//---------------------------------------------------------------------------
	
	private function installPrefs()
	{
		$model = $this->newModel('Preferences');
		
		$model->addPrefs(array
		(
			array
			(
				'name' => 'event_log_events_per_page',
				'group_name' => 'plugins',
				'section_name' => 'event log',
				'position' => 10,
				'type' => 'integer',
				'val' => 25,
			),
		));
	}
	
	//---------------------------------------------------------------------------
	
	private function installPerms()
	{
		$userModel = $this->newModel('user');
		
		$userModel->addPerms(
			array
			(
				array
				(
					'group_name' => 'settings',
					'name' => 'settings:event-log',
				),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:event-log:view',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:event-log:clear',
					),
			));
	}
	
	//---------------------------------------------------------------------------
	
	private function installTables()
	{
		$db = $this->loadDB();

		$ct = $db->getFunction('create_table');
		
		$ct->table('log');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
		$ct->field('time', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('event', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 255);
		$ct->field('user_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$db->query($ct->compile());
	}
	
}
	
//------------------------------------------------------------------------------

class EventLogEvent extends SparkModel
{
	public $when;
	public $what;
	public $who;
	
	//---------------------------------------------------------------------------

	public function __construct($row)
	{
		$this->when = $row['time'];
		$this->what = $row['event'];
		$this->who = $row['user'];
	}

	//---------------------------------------------------------------------------
}
