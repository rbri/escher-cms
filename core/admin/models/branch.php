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
		$branches[1] = $this->factory->manufacture('Branch', array('id'=>1, 'name'=>'Production'));
		$branches[2] = $this->factory->manufacture('Branch', array('id'=>2, 'name'=>'Staging'));
		$branches[3] = $this->factory->manufacture('Branch', array('id'=>3, 'name'=>'Development'));

		return $branches;
	}

	//---------------------------------------------------------------------------
	
	public function fetchBranch($id)
	{
		switch ($id)
		{
			case 1:
				return $this->factory->manufacture('Branch', array('id'=>1, 'name'=>'Production'));
			case 2:
				return $this->factory->manufacture('Branch', array('id'=>2, 'name'=>'Staging'));
			case 3:
				return $this->factory->manufacture('Branch', array('id'=>3, 'name'=>'Development'));
			default:
				return false;
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function rollbackBranchByID($id)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$db = $this->loadDBWithPerm(EscherModel::PermWrite);
		
		$db->begin();

		try
		{
			$db->deleteRows('theme', 'branch=?', $id);
			$db->deleteRows('template', 'branch=?', $id);
			$db->deleteRows('snippet', 'branch=?', $id);
			$db->deleteRows('tag', 'branch=?', $id);
			$flushPlugCache = ($db->affectedRows() > 0);
			$db->deleteRows('style', 'branch=?', $id);
			$db->deleteRows('script', 'branch=?', $id);
			$db->deleteRows('image', 'branch=?', $id);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	
		if ($flushPlugCache)
		{
			$this->observer->notify('escher:cache:request_flush:plug', $id);
			
			// if staging is rolled back, development branch's code cache will also
			// need to be purged of stale tags
			
			 if ($id == EscherProductionStatus::Staging)
			 {
				$this->observer->notify('escher:cache:request_flush:plug', EscherProductionStatus::Development);
			 }
		}

		$this->observer->notify('escher:cache:request_flush:partial', $id);
		$this->observer->notify('escher:cache:request_flush:page', $id);

		 if ($id == EscherProductionStatus::Staging)
		 {
			$this->observer->notify('escher:cache:request_flush:partial', EscherProductionStatus::Development);
			$this->observer->notify('escher:cache:request_flush:page', EscherProductionStatus::Development);
		 }
	}

	//---------------------------------------------------------------------------
	
	public function pushBranchByID($id)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$toBranch = $id - 1;		// target branch id of push
		
		$db = $this->loadDBWithPerm(EscherModel::PermWrite);
		
		$db->begin();

		try
		{
			$this->pushAsset($db, 'theme', false, $id);
			$this->pushAsset($db, 'template', true, $id);
			$this->pushAsset($db, 'snippet', true, $id);
			$flushPlugCache = $this->pushAsset($db, 'tag', true, $id);
			$this->pushAsset($db, 'style', true, $id);
			$this->pushAsset($db, 'script', true, $id);
			$this->pushAsset($db, 'image', true, $id);
			
			// after successfully pushing the branch, we can safely roll it back to a fresh starting state
			
			$this->rollbackBranchByID($id);

			// permanently delete assets marked for deletion if pushing to production
			
			if ($toBranch === 1)
			{
				$db->deleteRows('theme', 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
				$db->deleteRows('template', 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
				$db->deleteRows('snippet', 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
				$db->deleteRows('tag', 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
				$db->deleteRows('style', 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
				$db->deleteRows('script', 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
				$db->deleteRows('image', 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();

		if ($flushPlugCache)
		{
			$this->observer->notify('escher:cache:request_flush:plug', $toBranch);
		}

		$this->observer->notify('escher:cache:request_flush:partial', $toBranch);
		$this->observer->notify('escher:cache:request_flush:page', $toBranch);
	}

	//---------------------------------------------------------------------------
	
	public function rollbackBranchPartialByID($id, $changes)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		if (!empty($changes))
		{
			$flushPlugCache = false;	// only need to flush if pushing tag changes

			$db = $this->loadDBWithPerm(EscherModel::PermWrite);
			
			$db->begin();
	
			try
			{
				foreach ($changes as $table => $assetIDs)
				{
					$where = 'branch=? AND ' . $db->buildFieldIn($table, 'id', $assetIDs);
					$bind = array_merge(array($id), $assetIDs);
					$db->deleteRows($table, $where, $bind);
					if ($table === 'tag')
					{
						$flushPlugCache = true;
					}
				}
			}
			catch (Exception $e)
			{
				$db->rollback();
				throw $e;
			}
			
			$db->commit();

			if ($flushPlugCache)
			{
				$this->observer->notify('escher:cache:request_flush:plug', $id);

				// if staging is rolled back, development branch's code cache will also
				// need to be purged of stale tags
				
				 if ($id == EscherProductionStatus::Staging)
				 {
					$this->observer->notify('escher:cache:request_flush:plug', EscherProductionStatus::Development);
				 }
			}
			
			$this->observer->notify('escher:cache:request_flush:partial', $id);
			$this->observer->notify('escher:cache:request_flush:page', $id);

			 if ($id == EscherProductionStatus::Staging)
			 {
				$this->observer->notify('escher:cache:request_flush:partial', EscherProductionStatus::Development);
				$this->observer->notify('escher:cache:request_flush:page', EscherProductionStatus::Development);
			 }
		}
	}

	//---------------------------------------------------------------------------

	public function pushBranchPartialByID($id, $changes)
	{
		if (!SparkUtil::valid_int($id) || ($id <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		if (!empty($changes))
		{
			try
			{
				$toBranch = $id - 1;			// target branch id of push
				$flushPlugCache = false;	// only need to flush if pushing tag changes
				
				$db = $this->loadDBWithPerm(EscherModel::PermWrite);
				
				$db->begin();
		
				foreach ($changes as $table => $assetIDs)
				{
					$this->pushAsset($db, $table, ($table !== 'theme'), $id, $assetIDs);
					if ($table === 'tag')
					{
						$flushPlugCache = true;
					}
				}
				$this->rollbackBranchPartialByID($id, $changes);
				if ($toBranch === 1)
				{
					$db->deleteRows($table, 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
				}
			}
			catch (Exception $e)
			{
				$db->rollback();
				throw $e;
			}
		
			$db->commit();
	
			if ($flushPlugCache)
			{
				$this->observer->notify('escher:cache:request_flush:plug', $toBranch);
			}
			
			$this->observer->notify('escher:cache:request_flush:partial', $toBranch);
			$this->observer->notify('escher:cache:request_flush:page', $toBranch);
		}
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
			$this->updateAsset($db, $table, $fromBranch-1, $changes);
			return true;
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
			$metaTable = "{$table}_meta";
			$metaID = "{$table}_id";
		}

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
			}
			
			// otherwise, (target does not exist) so we must create it
			
			else
			{
				$row['branch'] = $toBranch;
				$db->insertRow($table, $row);
				$dstID = $db->lastInsertID();
			}

			if ($hasMeta && ($meta = $db->selectRows($metaTable, 'name, data', "{$metaID}=?", $srcID)))
			{
				foreach ($meta as &$row)
				{
					$row[$metaID] = $dstID;
				}
				$db->deleteRows($metaTable, "{$metaID}=?", $dstID);
				$db->insertRows($metaTable, $meta);
			}
		}
	}
	
	//---------------------------------------------------------------------------
	
}
