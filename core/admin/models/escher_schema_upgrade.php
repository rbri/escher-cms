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

	private function delete_perms($db, $startID, $endID)
	{
		$db->begin();

		try
		{
			for ($id = $startID; $id <= $endID; ++$id)
			{
				$ids[] = $id;
			}
			
			$numIDs = count($ids);
			$ids = implode(',', $ids);
			
			// note that we rely on foreign key constraints to keep the role_perm table consistent

			$db->deleteRows('perm', "id IN ({$ids})");
			$f = $db->getFunction('literal');
			$f->literal("\"id\"-{$numIDs}");
			$db->updateRows('perm', array('id'=>$f), 'id>?', $endID);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}
	
//------------------------------------------------------------------------------

	private function insert_perms($db, $startingID, $perms)
	{
		$db->begin();

		try
		{
			// perm IDs must be a single contiguous range
			// also, note that we rely on foreign key constraints to keep the role_perm table consistent
			
			$numPerms = count($perms);
			$offset = 1000;
			$dec = $offset - $numPerms;
		
			$f = $db->getFunction('literal');
			$f->literal('"id"+1000');
			$db->updateRows('perm', array('id'=>$f), 'id>=?', $startingID);
			$f->literal("\"id\"-{$dec}");
			$db->updateRows('perm', array('id'=>$f), 'id>=?', $startingID + $offset);

			$id = $startingID;
			foreach (array_keys($perms) as $key)
			{
				$perms[$key]['id'] = $id++;
			}
			
			$db->insertRows('perm', $perms);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
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
				@$di->drop("{$table}_url");
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
				$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'url, branch', "{$table}_url_branch");
				$db->query($ci->compile());
				$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', "{$table}_branch");
				$db->query($ci->compile());
			}

			$db->insertRows('pref', array
				(
					array
					(
						'name' => 'working_branch',
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
						'name' => 'development_draft_as_published',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 10,
						'type' => 'yesnoradio',
						'validation' => '',
						'val' => 'true',
					),
					array
					(
						'name' => 'development_branch_auto_routing',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 20,
						'type' => 'yesnoradio',
						'validation' => '',
						'val' => false,
					),
					array
					(
						'name' => 'development_branch_host_prefix',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 30,
						'type' => 'text',
						'validation' => '',
						'val' => 'dev',
					),
					array
					(
						'name' => 'development_debug_level',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 40,
						'type' => 'select',
						'data' => serialize(array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7', 8=>'8', 9=>'9')),
						'validation' => '',
						'val' => 9,
					),
					array
					(
						'name' => 'development_theme',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 50,
						'type' => 'theme',
						'validation' => '',
						'val' => '0',
					),
					array
					(
						'name' => 'staging_draft_as_published',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 110,
						'type' => 'yesnoradio',
						'validation' => '',
						'val' => 'true',
					),
					array
					(
						'name' => 'staging_branch_auto_routing',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 120,
						'type' => 'yesnoradio',
						'validation' => '',
						'val' => false,
					),
					array
					(
						'name' => 'staging_branch_host_prefix',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 130,
						'type' => 'text',
						'validation' => '',
						'val' => 'staging',
					),
					array
					(
						'name' => 'staging_debug_level',
						'group_name' => 'expert',
						'section_name' => 'branches',
						'position' => 140,
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
						'position' => 150,
						'type' => 'theme',
						'validation' => '',
						'val' => '0',
					),
				)
			);

			$db->updateRows('pref', array('name'=>'parsing_in_blocks'), 'name="enable_parsing_in_blocks"');
			$db->updateRows('pref', array('name'=>'parsing_in_parts'), 'name="enable_parsing_in_parts"');

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
			
			$perms = array
			(
				array
				(
					'group_name' => 'design',
					'name' => 'design:branches',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:branches:edit',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:branches:push',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:branches:rollback',
					),
			);
			$this->delete_perms($db, 215, 217);
			$this->insert_perms($db, 137, $perms);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
		
		// we allow role updates to fail because user may have deleted roles...

		try
		{
			@$db->insertRows('role_perm', array
				(
					array
					(
						'role_id' => 2,
						'perm_id' => 137,
					),
					array
					(
						'role_id' => 2,
						'perm_id' => 138,
					),
					array
					(
						'role_id' => 2,
						'perm_id' => 139,
					),
					array
					(
						'role_id' => 2,
						'perm_id' => 140,
					),
					array
					(
						'role_id' => 4,
						'perm_id' => 137,
					),
					array
					(
						'role_id' => 4,
						'perm_id' => 138,
					),
					array
					(
						'role_id' => 4,
						'perm_id' => 139,
					),
					array
					(
						'role_id' => 4,
						'perm_id' => 140,
					),
				)
			);
		}
		catch(Exception $e)
		{
		}
	}

	//---------------------------------------------------------------------------
}
