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

//------------------------------------------------------------------------------

	private function upgrade_2($db)
	{
		$db->begin();

		try
		{
			$ct = $db->getFunction('create_table');

			$db->query('DROP TABLE IF EXISTS {image_meta}');
			$ct->table('image_meta');
			$ct->field('image_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
			$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
			$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
			$ct->primaryKey('image_id, name');
			$ct->foreignKey('image_id', 'image', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
			$db->query($ct->compile());
			
			$db->query('DROP TABLE IF EXISTS {file_meta}');
			$ct->table('file_meta');
			$ct->field('file_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
			$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
			$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
			$ct->primaryKey('file_id, name');
			$ct->foreignKey('file_id', 'file', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
			$db->query($ct->compile());
			
			$db->query('DROP TABLE IF EXISTS {link_meta}');
			$ct->table('link_meta');
			$ct->field('link_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
			$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
			$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
			$ct->primaryKey('link_id, name');
			$ct->foreignKey('link_id', 'link', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
			$db->query($ct->compile());
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

//------------------------------------------------------------------------------

	private function upgrade_3($db)
	{
		$db->begin();

		try
		{
			$at = $db->getFunction('alter_table');
			$di = $db->getFunction('drop_index');
			$ci = $db->getFunction('create_index');

			$at->table('theme');
			$at->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
			$db->query($at->compile());
			$at->table('theme');
			$at->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
			$db->query($at->compile());
			$di->table('theme');
			@$di->drop('theme_slug');
			try
			{
				@$db->query($di->compile());
			}
			catch(Exception $e)
			{
			}
			$ci->table('theme');
			$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, branch', 'theme_slug_branch');
			$db->query($ci->compile());
			$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'theme_branch');
			$db->query($ci->compile());

			foreach (array('template', 'snippet', 'tag') as $table)
			{
				$at->table($table);
				$at->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
				$db->query($at->compile());
				$at->table($table);
				$at->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
				$db->query($at->compile());
				$di->table($table);
				@$di->drop("{$table}_name_theme");
				try
				{
					@$db->query($di->compile());
				}
				catch(Exception $e)
				{
				}
				$ci->table($table);
				$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name, theme_id, branch', "{$table}_name_theme_branch");
				$db->query($ci->compile());
				$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', "{$table}_branch");
				$db->query($ci->compile());
			}

			foreach (array('style', 'script', 'image') as $table)
			{
				$at->table($table);
				$at->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
				$db->query($at->compile());
				$at->table($table);
				$at->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
				$db->query($at->compile());
				$di->table($table);
				@$di->drop("{$table}_slug_theme");
				try
				{
					@$db->query($di->compile());
				}
				catch(Exception $e)
				{
				}
				$ci->table($table);
				$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, theme_id, branch', "{$table}_slug_theme_branch");
				$db->query($ci->compile());
				$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', "{$table}_branch");
				$db->query($ci->compile());
			}

			$db->insertRows('pref', array
				(
					array
					(
						'name' => 'active_branch',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 5,
						'type' => 'select',
						'data' => serialize(array(EscherProductionStatus::Development=>'Development', EscherProductionStatus::Staging=>'Staging', EscherProductionStatus::Production=>'Production')),
						'validation' => '',
						'val' => EscherProductionStatus::Production,
					),
					array
					(
						'name' => 'enable_development_branch_auto_routing',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 10,
						'type' => 'yesnoradio',
						'validation' => '',
						'val' => false,
					),
					array
					(
						'name' => 'development_branch_host_prefix',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 20,
						'type' => 'text',
						'validation' => '',
						'val' => 'dev',
					),
					array
					(
						'name' => 'development_debug_level',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 30,
						'type' => 'select',
						'data' => serialize(array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7', 8=>'8', 9=>'9')),
						'validation' => '',
						'val' => 0,
					),
					array
					(
						'name' => 'development_theme',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 40,
						'type' => 'theme',
						'validation' => '',
						'val' => '0',
					),
					array
					(
						'name' => 'enable_staging_branch_auto_routing',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 110,
						'type' => 'yesnoradio',
						'validation' => '',
						'val' => false,
					),
					array
					(
						'name' => 'staging_branch_host_prefix',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 120,
						'type' => 'text',
						'validation' => '',
						'val' => 'staging',
					),
					array
					(
						'name' => 'staging_debug_level',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 130,
						'type' => 'select',
						'data' => serialize(array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7', 8=>'8', 9=>'9')),
						'validation' => '',
						'val' => 0,
					),
					array
					(
						'name' => 'staging_theme',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 140,
						'type' => 'theme',
						'validation' => '',
						'val' => '0',
					),
				)
			);

			$row = $db->selectRow('pref', '*', 'name="production_status"');
			if (!empty($row))
			{
				if (($row['val']) != EscherProductionStatus::Maintenance)
				{
					$row['val'] = 4 - $row['val'];
				}
				$db->updateRows('pref', array('val'=>$row['val'], 'data'=>serialize(array(EscherProductionStatus::Maintenance=>'Maintenance', EscherProductionStatus::Development=>'Development', EscherProductionStatus::Staging=>'Staging', EscherProductionStatus::Production=>'Production'))), 'name="production_status"');
			}
			else
			{
				$db->insertRows('pref', array
					(
						array
						(
							'name' => 'production_status',
							'group_name' => 'basic',
							'section_name' => '0site_info',
							'position' => 60,
							'type' => 'select',
							'data' => serialize(array(EscherProductionStatus::Maintenance=>'Maintenance', EscherProductionStatus::Development=>'Development', EscherProductionStatus::Staging=>'Staging', EscherProductionStatus::Production=>'Production')),
							'validation' => '',
							'val' => EscherProductionStatus::Development,
						)
					)
				);
			}

			$db->updateRows('perm', array('name'=>'settings:branches'), 'name="settings:revisions"');
			$db->updateRows('perm', array('name'=>'settings:branches:push'), 'name="settings:revisions:add"');
			$db->updateRows('perm', array('name'=>'settings:branches:rollback'), 'name="settings:revisions:delete"');

		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
}
