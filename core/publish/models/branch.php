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

require('content_objects.php');

//------------------------------------------------------------------------------

class Branch extends EscherObject
{
	public $name;
}

//------------------------------------------------------------------------------

class _BranchModel extends SparkModel
{
	
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
			$themeChanges = $db->selectJoinRows
			(
				array('theme', 'src'),
				'src.id as source_id, dst.id AS target_id',
				array(array('type'=>'left', 'table'=>array('theme', 'dst'), 'conditions'=>array(array('leftField'=>'slug', 'rightField'=>'slug', 'joinOp'=>'='), array('leftField'=>'branch', 'rightField'=>'branch+1', 'joinOp'=>'=')))),
				"src.branch={$id}"
			);
			$this->updateAsset($db, 'theme', 'slug', $toBranch, $themeChanges);
			unset($themeChanges);
			
			$this->pushThemedAsset($db, 'template', 'name', $id);
			$this->pushThemedAsset($db, 'snippet', 'name', $id);
			$this->pushThemedAsset($db, 'tag', 'name', $id);
			$this->pushThemedAsset($db, 'style', 'slug', $id);
			$this->pushThemedAsset($db, 'script', 'slug', $id);
			$this->pushThemedAsset($db, 'image', 'slug', $id);
			
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
		
			// after successfully pushing the branch, we can safely roll it back to a fresh starting state
			
			$this->rollbackBranchByID($id);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	private function pushThemedAsset($db, $table, $nameCol, $fromBranch)
	{
		$changes = $this->fetchThemedAssetChanges($db, $table, $nameCol, $fromBranch);
		$this->updateAsset($db, $table, $nameCol, $fromBranch-1, $changes);
	}
	
	//---------------------------------------------------------------------------
	
	private function fetchThemedAssetChanges($db, $table, $nameCol, $fromBranch)
	{
		return $db->selectJoinRows
		(
			array($table, 'src'),
			'src.id as source_id, dst.id AS target_id',
			array(array('type'=>'left', 'table'=>array($table, 'dst'), 'conditions'=>array(array('leftField'=>$nameCol, 'rightField'=>$nameCol, 'joinOp'=>'='), array('leftField'=>'theme_id', 'rightField'=>'theme_id', 'joinOp'=>'='), array('leftField'=>'branch', 'rightField'=>'branch+1', 'joinOp'=>'=')))),
			"src.branch={$fromBranch}"
		);
	}
	
	//---------------------------------------------------------------------------
	
	private function updateAsset($db, $table, $nameCol, $toBranch, $changes)
	{
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
