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

class _EventLog extends EscherPlugin
{
	private $_model;
	private $_plugDir;

	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
		
		if ($this->app->is_admin())
		{
			$myInfo = $this->factory->getPlug('EventLog');
			$this->_plugDir = dirname($myInfo['file']);

			$this->_model = $this->newModel('EventLog');
	
			if ($this->_model->installed())
			{
				$this->observer->observe(array($this, 'logSiteChangeEvent'), array('escher:site_change', 'escher:db_change'));
				$this->observer->observe(array($this, 'logAuthEvent'), array('SparkAuthController:login', 'SparkAuthController:logout'));
				$this->observer->observe(array($this, 'logEvent'), array('EventLog:logevent'));
			}

			$this->observer->observe(array($this, 'eventLogLoadLangFile'), array('SparkLang:load:permissions'));
		}
	}

	//---------------------------------------------------------------------------

	public function logSiteChangeEvent($event, $object, $parent = NULL)
	{
		if (!preg_match('/^(?:.+):+(?:.+):+(?:.+):+(.+):+(.+)/', $event, $matches))
		{
			return;
		}

		$verb = $matches[2] . ((substr($matches[2], -1) === 'e') ? 'd' : 'ed');
		$objectKind = $matches[1];
		
		if (is_string($object))
		{
			$objName = $object;
		}
		elseif (($objectKind === 'part') || ($objectKind === 'meta'))
		{
			$objName = implode(', ', $object);
		}
		else
		{
			$objName = isset($object->name) ? $object->name : $object->slug;
			
			if (empty($objName) && ($objectKind === 'page'))
			{
				$objName = '/';
			}
		}

		$message = $verb . ' ' . $objectKind . ' ' . '"'.$objName.'"';

		if ($parent && ($parent instanceof Page))
		{
			$parentName = isset($parent->name) ? $parent->name : $parent->slug;
			if (empty($parentName))
			{
				$parentName = '/';
			}
			$message .= ' for page ' . '"'.$parentName.'"';
		}
		
		$this->_model->logEvent($message, $this->app->get_user()->id);
	}

	//---------------------------------------------------------------------------

	public function logAuthEvent($event, $userInfo)
	{
		$message = ($event === 'SparkAuthController:login') ? 'logged in' : 'logged out';
		$message .= ' from ' . $userInfo['ip_address'];
		$this->_model->logEvent($message, $userInfo['id']);
	}

	//---------------------------------------------------------------------------

	public function logEvent($event, $message)
	{
		$this->_model->logEvent($message, $this->app->get_user()->id);
	}

	//---------------------------------------------------------------------------

	public function eventLogLoadLangFile($event, $file)
	{
		self::$lang->load($file, $this->_plugDir . '/languages');
	}

	//---------------------------------------------------------------------------
}
