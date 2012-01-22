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

require('util.php');

//------------------------------------------------------------------------------

class EscherVersion
{
	const CoreVersion = '0.9.2';
	const SchemaVersion = 7;
	const RequiresSparkPlugVersion = '1.1.0';
	
	//---------------------------------------------------------------------------

	public static function validateSchemaVersion($version)
	{
		$dbVersion = intval($version);
		if ($dbVersion > self::SchemaVersion)
		{
			exit('Database schema version is too new. Update your code to the latest version.');
		}
		
		return ($dbVersion === self::SchemaVersion);
	}

	//---------------------------------------------------------------------------

	public static function validateSparkPlugVersion(&$errorMsg)
	{
		if (version_compare(spark_plug_version, self::RequiresSparkPlugVersion) < 0)
		{
			$errorMsg = 'This version of Escher requires Spark/Plug version ' . EscherVersion::RequiresSparkPlugVersion . ' or later. Please upgrade Spark/Plug.';
			return false;
		}
		$errorMsg = '';
		return true;
	}

	//---------------------------------------------------------------------------
}

//------------------------------------------------------------------------------

class EscherProductionStatus
{
	const Maintenance = 0;
	const Production = 1;
	const Staging = 2;
	const Development = 3;
}

//------------------------------------------------------------------------------

class EscherModel extends SparkModel
{
	const PermRead = 0;
	const PermWrite = 1;
	
	private $_db_labels;

	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
		
		$this->_db_labels[self::PermRead] = $this->config->get('database_default_ro', 'default');
		$this->_db_labels[self::PermWrite] = NULL;
	}

	//---------------------------------------------------------------------------

	public function loadDBWithPerm($perm)
	{
		return parent::loadDB($this->_db_labels[$perm]);
	}

	//---------------------------------------------------------------------------
}

//------------------------------------------------------------------------------

class EscherObject extends SparkPlug
{
	public $id;
	
	public function __construct($fields)
	{
		parent::__construct();
		
		if (is_object($fields))
		{
			$fields = get_object_vars($fields);
		}
		
		if (!empty($fields))
		{
			foreach ((array)$fields as $key=>$field)
			{
				$this->$key = $field;
			}
		}
	}

	public function id()
	{
		return $this->id;
	}

	//---------------------------------------------------------------------------
}

//------------------------------------------------------------------------------

class EscherPlugin extends EscherObject
{
}

//------------------------------------------------------------------------------

abstract class EscherFilter extends EscherPlugin
{
	abstract public function name();
	abstract public function cssClass();
}

//------------------------------------------------------------------------------
