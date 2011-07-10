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

require(escher_core_dir.'/publish/models/content_objects.php');

//------------------------------------------------------------------------------

class Branch extends EscherObject
{
	public $name;
}

//------------------------------------------------------------------------------

class _BranchModel extends EscherModel
{
	private static $_assetBranchInfo = array
	(
		'theme' => 'slug',
		'template' => 'name',
		'snippet' => 'name',
		'tag' => 'name',
		'style' => 'slug',
		'script' => 'slug',
		'image' => 'slug',
	);
	
	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllBranches()
	{
		$branches[EscherProductionStatus::Production] = $this->factory->manufacture('Branch', array('id'=>EscherProductionStatus::Production, 'name'=>'Production'));
		$branches[EscherProductionStatus::Staging] = $this->factory->manufacture('Branch', array('id'=>EscherProductionStatus::Staging, 'name'=>'Staging'));
		$branches[EscherProductionStatus::Development] = $this->factory->manufacture('Branch', array('id'=>EscherProductionStatus::Development, 'name'=>'Development'));

		return $branches;
	}

	//---------------------------------------------------------------------------
	
	public function fetchBranch($id)
	{
		switch ($id)
		{
			case EscherProductionStatus::Production:
				return $this->factory->manufacture('Branch', array('id'=>EscherProductionStatus::Production, 'name'=>'Production'));
			case EscherProductionStatus::Staging:
				return $this->factory->manufacture('Branch', array('id'=>EscherProductionStatus::Staging, 'name'=>'Staging'));
			case EscherProductionStatus::Development:
				return $this->factory->manufacture('Branch', array('id'=>EscherProductionStatus::Development, 'name'=>'Development'));
			default:
				return false;
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function rollbackBranchByID($id, &$affected)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$affected = array();
		
		$db = $this->loadDBWithPerm(EscherModel::PermWrite);
		
		$db->begin();

		try
		{
			foreach (self::$_assetBranchInfo as $table => $ignore)
			{
				$db->deleteRows($table, 'branch=?', $id);
				if ($db->affectedRows() > 0)
				{
					$affected[$table] = true;
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
		
		return !empty($affected);
	}

	//---------------------------------------------------------------------------
	
	public function pushBranchByID($id, &$affected)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$affected = array();

		$toBranch = $id - 1;		// target branch id of push
		
		$db = $this->loadDBWithPerm(EscherModel::PermWrite);
		
		$db->begin();

		try
		{
			foreach (self::$_assetBranchInfo as $table => $ignore)
			{
				if ($this->pushAsset($db, $table, ($table !== 'theme'), $id))
				{
					$affected[$table] = true;
				}
			}
			
			// after successfully pushing the branch, we can safely roll it back to a fresh starting state
			
			$this->rollbackBranchByID($id, $ignore);

			// permanently delete assets marked for deletion if pushing to production
			
			if ($toBranch === EscherProductionStatus::Production)
			{
				foreach (self::$_assetBranchInfo as $table => $ignore)
				{
					$db->deleteRows($table, 'branch=? AND branch_status=?', array(EscherProductionStatus::Production, ContentObject::branch_status_deleted));
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();

		return !empty($affected);
	}

	//---------------------------------------------------------------------------
	
	public function rollbackBranchPartialByID($id, $changes, &$affected)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$affected = array();

		if (!empty($changes))
		{
			$db = $this->loadDBWithPerm(EscherModel::PermWrite);
			
			$db->begin();
	
			try
			{
				foreach ($changes as $table => $assetIDs)
				{
					if (!isset(self::$_assetBranchInfo[$table]))
					{
						continue;
					}
					$where = 'branch=? AND ' . $db->buildFieldIn($table, 'id', $assetIDs);
					$bind = array_merge(array($id), $assetIDs);
					$db->deleteRows($table, $where, $bind);
					if ($db->affectedRows() > 0)
					{
						$affected[$table] = true;
					}
				}
			}
			catch (Exception $e)
			{
				$db->rollback();
				throw $e;
			}
			
			$db->commit();
		}

		return !empty($affected);
	}

	//---------------------------------------------------------------------------

	public function pushBranchPartialByID($id, $changes, &$affected)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}

		$affected = array();

		if (!empty($changes))
		{
			try
			{
				$toBranch = $id - 1;			// target branch id of push
				
				$db = $this->loadDBWithPerm(EscherModel::PermWrite);
				
				$db->begin();
		
				foreach ($changes as $table => $assetIDs)
				{
					if (!isset(self::$_assetBranchInfo[$table]))
					{
						continue;
					}
					if ($this->pushAsset($db, $table, ($table !== 'theme'), $id, $assetIDs))
					{
						$affected[$table] = true;
					}
				}
			
				// after successfully pushing the branch, we can safely roll back all pushed assets
			
				$this->rollbackBranchPartialByID($id, $changes, $ignore);

				// permanently delete assets marked for deletion if pushing to production
			
				if ($toBranch === EscherProductionStatus::Production)
				{
					$db->deleteRows($table, 'branch=? AND branch_status=?', array(EscherProductionStatus::Production, ContentObject::branch_status_deleted));
				}
			}
			catch (Exception $e)
			{
				$db->rollback();
				throw $e;
			}
		
			$db->commit();
		}

		return !empty($affected);
	}
	
	//---------------------------------------------------------------------------
	
	public function getBranchChanges($id, $table)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$isTheme = ($table === 'theme');
		$nameCol = self::$_assetBranchInfo[$table];
		$table = array($table, 'asset');
		
		$db = $this->loadDBWithPerm(EscherModel::PermRead);
		
		$select = 'asset.id, asset.created, asset.edited, author.name AS author, editor.name AS editor, asset.branch_status AS status, ' . "asset.{$nameCol} AS name";
		$joins = array
			(
				array('table'=>array('user', 'author'), 'conditions'=>array(array('leftField'=>'author_id', 'rightField'=>'id', 'joinOp'=>'='))),
				array('leftTable'=>$table, 'table'=>array('user', 'editor'), 'conditions'=>array(array('leftField'=>'editor_id', 'rightField'=>'id', 'joinOp'=>'='))),
			);
		$where = 'asset.branch=?';
		$orderBy = 'name';
		$bind = $id;
		
		// special case for themes, which have no theme_id field
		if (!$isTheme)
		{
			$select .= ', theme.slug AS theme'; 
			$joins[] = array('type'=>'left', 'leftTable'=>$table, 'table'=>'theme', 'conditions'=>array(array('leftField'=>'theme_id', 'rightField'=>'id', 'joinOp'=>'=')));
		}

		$rows = $db->query($db->buildSelect($table, $select, $joins, $where, $orderBy, NULL, NULL, true), $bind)->rows();
		foreach ($rows as &$row)
		{
			$row['status'] = ContentObject::branchStatusToText($row['status']);
		}

		return !empty($rows) ? $rows : false;
	}
	
	//---------------------------------------------------------------------------
	
	private function pushAsset($db, $table, $matchTheme, $fromBranch, $restrictTo = NULL)
	{
		$changes = $this->fetchAssetChanges($db, $table, $matchTheme, $fromBranch, $restrictTo);
		if (!empty($changes))
		{
			return $this->updateAsset($db, $table, $fromBranch-1, $changes);
		}
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	private function fetchAssetChanges($db, $table, $matchTheme, $fromBranch, $restrictTo = NULL)
	{
		$nameCol = self::$_assetBranchInfo[$table];

		$where = 'src.branch=?';
		$bind[] = $fromBranch;
		
		if (!empty($restrictTo))
		{
			$where .= ' AND ' . $db->buildFieldIn(array($table, 'src'), 'id', $restrictTo);
			$bind = array_merge($bind, $restrictTo);
		}
		
		$joins[0] = array
		(
			'type'=>'left',
			'table'=>array($table, 'dst'),
			'conditions'=>array(array('leftField'=>$nameCol, 'rightField'=>$nameCol, 'joinOp'=>'='), array('leftField'=>'branch', 'rightField'=>'branch+1', 'joinOp'=>'='))
		);
		
		if ($matchTheme)
		{
			$joins[0]['conditions'][] = array('leftField'=>'theme_id', 'rightField'=>'theme_id', 'joinOp'=>'=');
		}
		
		return $db->selectJoinRows
		(
			array($table, 'src'),
			'src.id as source_id, dst.id AS target_id',
			$joins,
			$where,
			$bind
		);
	}
	
	//---------------------------------------------------------------------------
	
	private function updateAsset($db, $table, $toBranch, $changes)
	{
		$nameCol = self::$_assetBranchInfo[$table];
		
		if ($hasMeta = ($table === 'image'))
		{
			$metaTable = 'image_meta';
			$metaID = 'image_id';
		}

		$changed = false;

		foreach ($changes as $change)
		{
			$row = $db->selectRow($table, '*', 'id=?', $srcID = $change['source_id']);
			unset($row['id']);
			
			// if target exists, update it
			
			if (isset($change['target_id']))
			{
				unset($row[$nameCol]);
				unset($row['theme_id']);
				unset($row['branch']);
				if (isset($row['rev']))
				{
					$row['rev'] = $db->getFunction('literal')->literal('rev+1');
				}
				$db->updateRows($table, $row, 'id=?', $dstID = $change['target_id']);
				if (!$changed)
				{
					$changed = ($db->affectedRows() > 0);
				}
			}
			
			// otherwise, (target does not exist) so we must create it
			
			else
			{
				$row['branch'] = $toBranch;
				$db->insertRow($table, $row);
				$dstID = $db->lastInsertID();
				$changed = true;
			}

			if ($hasMeta && ($meta = $db->selectRows($metaTable, 'name, data', "{$metaID}=?", $srcID)))
			{
				foreach ($meta as &$row)
				{
					$row[$metaID] = $dstID;
				}
				$db->deleteRows($metaTable, "{$metaID}=?", $dstID);
				$db->insertRows($metaTable, $meta);
				$changed = true;
			}
		}
		
		return $changed;
	}
	
	//---------------------------------------------------------------------------
	
}
