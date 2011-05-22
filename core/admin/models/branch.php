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

class _BranchModel extends SparkModel
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
		if (!is_numeric($id) || (($id = intval($id)) <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('theme', 'branch=?', $id);
			$db->deleteRows('template', 'branch=?', $id);
			$db->deleteRows('snippet', 'branch=?', $id);
			$db->deleteRows('tag', 'branch=?', $id);
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
	}

	//---------------------------------------------------------------------------
	
	public function pushBranchByID($id)
	{
		if (!is_numeric($id) || (($id = intval($id)) <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$toBranch = $id - 1;		// target branch id of push
		
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$this->pushAsset($db, 'theme', false, $id);
			$this->pushAsset($db, 'template', true, $id);
			$this->pushAsset($db, 'snippet', true, $id);
			$this->pushAsset($db, 'tag', true, $id);
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
	}

	//---------------------------------------------------------------------------
	
	public function rollbackBranchPartialByID($id, $changes)
	{
		if (!is_numeric($id) || (($id = intval($id)) <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		if (!empty($changes))
		{
			$db = $this->loadDB();
			
			$db->begin();
	
			try
			{
				foreach ($changes as $table => $assetIDs)
				{
					$where = 'branch=? AND ' . $db->buildFieldIn($table, 'id', $assetIDs);
					$bind = array_merge(array($id), $assetIDs);
					$db->deleteRows($table, $where, $bind);
				}
			}
			catch (Exception $e)
			{
				$db->rollback();
				throw $e;
			}
			
			$db->commit();
		}
	}

	//---------------------------------------------------------------------------

	public function pushBranchPartialByID($id, $changes)
	{
		if (!is_numeric($id) || (($id = intval($id)) <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$toBranch = $id - 1;		// target branch id of push
		
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			if (!empty($changes))
			{
				foreach ($changes as $table => $assetIDs)
				{
					$this->pushAsset($db, $table, ($table !== 'theme'), $id, $assetIDs);
				}
				$this->rollbackBranchPartialByID($id, $changes);
				if ($toBranch === 1)
				{
					$db->deleteRows($table, 'branch=1 AND branch_status=?', ContentObject::branch_status_deleted);
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
	
	//---------------------------------------------------------------------------
	
	public function getBranchChanges($id, $table)
	{
		if (!is_numeric($id) || (($id = intval($id)) <= 1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$isTheme = ($table === 'theme');
		$nameCol = self::$_assetBranchInfo[$table];
		$table = array($table, 'asset');
		
		$db = $this->loadDB();
		
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
		$this->updateAsset($db, $table, $fromBranch-1, $changes);
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

		foreach ($changes as $change)
		{
			$row = $db->selectRow($table, '*', 'id=?', $change['source_id']);
			
			unset($row['id']);
			
			// if target exists, update it
			
			if (isset($change['target_id']))
			{
				unset($row[$nameCol]);
				unset($row['theme_id']);
				unset($row['branch']);
				$db->updateRows($table, $row, 'id=?', $change['target_id']);
			}
			
			// otherwise, (target does not exist) so we must create it
			
			else
			{
				$row['branch'] = $toBranch;
				$db->insertRow($table, $row);
			}
		}
	}
	
	//---------------------------------------------------------------------------
	
}
