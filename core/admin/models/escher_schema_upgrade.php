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

class _EscherSchemaUpgradeModel extends SparkModel
{
	public function upgrade($params = NULL)
	{
		$db = $this->loadDB($params);

		if (!$row = $db->selectRow('pref', 'val', 'name="schema"'))
		{
			throw new SparkException('Current schema version not detected.');
		}
		
		$upgradeFrom = intval($row['val']);
		$upgradeTo = EscherVersion::SchemaVersion;
		
		if ($upgradeFrom >= $upgradeTo)
		{
			return false;	// upgrade not necessary
		}
		
		try
		{
			for ($upgrade = $upgradeFrom+1; $upgrade <= $upgradeTo; ++$upgrade)
			{
				$method = 'upgrade_' . $upgrade;
				if (!method_exists($this, $method))
				{
					throw new SparkException('No update available for this version.');
				}
				$this->{$method}($db);
				$db->updateRows('pref', array('val'=>$upgrade), 'name="schema"');
			}
		}
		catch (Exception $e)
		{
			throw new SparkException('Schema update could not be completed: ' .  $e->getMessage());
		}
		
		return true;		// upgrade successful
	}

	//---------------------------------------------------------------------------
}
