<?php

/*
Copyright 2009-2010 Sam Weiss
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

require('user_objects.php');

//------------------------------------------------------------------------------

class _UserModel extends SparkModel
{
	
	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function addPerms($perms)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			foreach ($perms as $perm)
			{
				$row = array
				(
					'group_name' => $perm['group_name'],
					'name' => $perm['name'],
				);
				$db->insertRow('perm', $row);
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
		
	public function fetchAllPermissions($sort = false)
	{
		$db = $this->loadDB();

		$perms = array();
		
		$rows =
			$sort
				? $db->query($db->buildSelect('perm', '*', NULL, NULL, 'id'))->rows()
				: $db->selectRows('perm')
					;
		
		foreach ($rows as $row)
		{
			$perms[$row['group_name']][$row['id']] = $row['name'];
		}

		return $perms;
	}

	//---------------------------------------------------------------------------
	
	public function addRole($role)
	{
		$db = $this->loadDB();
	
		$row = array
		(
			'name' => $role->name,
		);

		$db->begin();

		try
		{
			$db->insertRow('role', $row);
			$role->id = $db->lastInsertID();
			if (isset($role->permissions))
			{
				$this->updateRolePermissions($role);
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
	
	public function updateRole($role)
	{
		$db = $this->loadDB();
	
		$row = array
		(
			'name' => $role->name,
		);

		$db->begin();

		try
		{
			$db->updateRows('role', $row, 'id=?', $role->id);
			if (isset($role->permissions))
			{
				$this->updateRolePermissions($role);
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
	
	public function deleteRoleByID($roleID)
	{
		if ($roleID <= 1)
		{
			throw new SparkException('cannot delete administrator account', SparkException::kInternal);
		}
		
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('user_role', 'role_id=?', $roleID);
			$db->deleteRows('role_perm', 'role_id=?', $roleID);
			$db->deleteRows('role', 'id=?', $roleID);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateRolePermissions($role)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('role_perm', 'role_id=?', $role->id);
			
			if (!empty($role->permissions))
			{
				// convert perm names to perm ids
				
				$names = array_keys($role->permissions);
				$where = $db->buildFieldIn('perm', 'name', $names);
				$rows = $db->selectRows('perm', 'id', $where, $names);

				foreach($rows as $row)
				{
					$db->insertRow('role_perm', array('role_id'=>$role->id, 'perm_id'=>$row['id']));
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
	
	public function fetchAllRoles($sort = false)
	{
		$db = $this->loadDB();

		$roles = array();
		
		foreach ($db->query($db->buildSelect('role', '*', NULL, NULL, 'name'))->rows() as $row)
		{
			$roles[$row['id']] = $this->factory->manufacture('Role', $row);
		}
		
		$roles[1]->isAdmin = true;

		return $roles;
	}

	//---------------------------------------------------------------------------
	
	public function fetchRole($id)
	{
		$db = $this->loadDB();

		if ($row = $db->selectRow('role', '*', 'id=?', $id))
		{
			$role = $this->factory->manufacture('Role', $row);
			if ($id == 1)
			{
				$role->isAdmin = true;
			}
		}
		else
		{
			$role = false;
		}
		
		return $role;
	}

	//---------------------------------------------------------------------------
	
	public function fetchRolePermissions($role)
	{
		if (!$role->permissions)
		{
			$role->permissions = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildPermissionsJoin('role', $joins);
	
			foreach ($db->selectJoinRows('role', '{perm}.name', $joins, '{role}.id=?', $role->id) as $row)
			{
				$role->permissions[$row['name']] = true;
			}
		}

		return $role->permissions;
	}

	//---------------------------------------------------------------------------
	
	public function roleExists($name)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('role', 'id', 'name=?', $name);
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function addUser($user)
	{	
		$db = $this->loadDB();
		$now = self::now();

		$row = array
		(
			'name' => $user->name,
			'email' => $user->email,
			'login' => $user->login,
			'password' => $user->password,
			'created' => $now,
		);

		$db->insertRow('user', $row);
		$user->id = $db->lastInsertID();
		if (isset($user->roles))
		{
			$this->updateUserRoles($user);
		}
	}

	//---------------------------------------------------------------------------
	
	public function updateUser($user)
	{
		if (!$user->id)
		{
			throw new SparkException('cannot update system user', SparkException::kInternal);
		}
		
		$db = $this->loadDB();

		$row = array
		(
			'name' => $user->name,
			'email' => $user->email,
			'login' => $user->login,
		);
		
		if (!empty($user->password))
		{
			$row['password'] = $user->password;
		}

		$db->updateRows('user', $row, 'id=?', $user->id);
		if (isset($user->roles))
		{
			$this->updateUserRoles($user);
		}
	}

	//---------------------------------------------------------------------------
	
	public function deleteUserByID($userID)
	{
		if ($userID <= 1)
		{
			throw new SparkException('cannot delete administrator', SparkException::kInternal);
		}
		
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('user_role', 'user_id=?', $userID);
			$db->deleteRows('user', 'id=?', $userID);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateUserRoles($user)
	{
		if (!$user->id)
		{
			throw new SparkException('cannot update system user', SparkException::kInternal);
		}
		
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('user_role', 'user_id=?', $user->id);
			
			if (!empty($user->roles))
			{
				foreach($user->roles as $role)
				{
					$db->insertRow('user_role', array('user_id'=>$user->id, 'role_id'=>$role->id));
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
	
	public function fetchUser($id, $fetchRoles = false, $fetchPermissions = false)
	{
		if (!$id)
		{
			throw new SparkException('cannot fetch system user', SparkException::kInternal);
		}

		$db = $this->loadDB();

		if ($row = $db->selectRow('user', '*', 'id=?', $id))
		{
			$user = $this->factory->manufacture('User', $row);
			if ($fetchRoles)
			{
				$this->fetchUserRoles($user);
			}
			if ($fetchPermissions)
			{
				$this->fetchUserPermissions($user);
			}
			if ($id == 1)
			{
				$user->isAdmin = true;
			}
		}
		else
		{
			$user = false;
		}
				
		return $user;
	}

	//---------------------------------------------------------------------------
	
	public function fetchUserByEmail($email, $fetchRoles = false, $fetchPermissions = false)
	{
		if (!$email)
		{
			throw new SparkException('cannot fetch system user', SparkException::kInternal);
		}

		$db = $this->loadDB();

		if ($row = $db->selectRow('user', '*', 'email=?', $email))
		{
			$user = $this->factory->manufacture('User', $row);
			if ($fetchRoles)
			{
				$this->fetchUserRoles($user);
			}
			if ($fetchPermissions)
			{
				$this->fetchUserPermissions($user);
			}
			if ($user->id == 1)
			{
				$user->isAdmin = true;
			}
		}
		else
		{
			$user = false;
		}
				
		return $user;
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllUsers($fetchRoles = false, $fetchPermissions = false, $sort = false)
	{
		$db = $this->loadDB();

		$roles = array();
		
		foreach ($db->query($db->buildSelect('user', '*', NULL, 'id != 0', 'name'))->rows() as $row)
		{
			$user = $this->factory->manufacture('User', $row);
			if ($fetchRoles)
			{
				$this->fetchUserRoles($user);
			}
			if ($fetchPermissions)
			{
				$this->fetchUserPermissions($user);
			}
			$users[$row['id']] = $user;
		}
		
		$users[1]->isAdmin = true;
		
		return $users;
	}

	//---------------------------------------------------------------------------
	
	public function fetchUserRoles($user)
	{
		if (!$user->id)
		{
			throw new SparkException('cannot fetch system user', SparkException::kInternal);
		}

		if (!$user->roles)
		{
			$user->roles = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildRolesJoin('user', $joins);
	
			foreach ($db->selectJoinRows('user', '{role}.id,{role}.name', $joins, '{user}.id=?', $user->id) as $row)
			{
				$user->roles[$row['id']] = $row['name'];
			}
		}

		return $user->roles;
	}

	//---------------------------------------------------------------------------
	
	public function fetchUserPermissions($user)
	{
		if (!$user->id)
		{
			throw new SparkException('cannot fetch system user', SparkException::kInternal);
		}

		if (!$user->isAdmin && !$user->permissions)
		{
			$user->permissions = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildRolesJoin('user', $joins);
			$this->buildPermissionsJoin('role', $joins);

			foreach ($db->selectJoinRows('user', '{perm}.name', $joins, '{user}.id=?', $user->id, true) as $row)
			{
				$user->permissions[$row['name']] = true;
			}
			
			if ($db->selectRow('user_role', 'user_id', 'user_id=? AND role_id=1', $user->id))
			{
				$user->isAdmin = true;
			}
		}

		return $user->permissions;
	}

	//---------------------------------------------------------------------------
	
	public function userLoginExists($login)
	{
		if (empty($login))
		{
			return false;
		}
		
		$db = $this->loadDB();
		$row = $db->selectRow('user', 'id', 'login=?', $login);
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function userEmailExists($email)
	{
		if (empty($email))
		{
			return false;
		}
		
		$db = $this->loadDB();
		$row = $db->selectRow('user', 'id', 'email=?', $email);
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	protected function buildRolesJoin($type, &$joins)
	{
		$joins[] = array('table'=>"{$type}_role", 'conditions'=>array(array('leftField'=>'id', 'rightField'=>"{$type}_id", 'joinOp'=>'=')));
		$joins[] = array('table'=>'role', 'conditions'=>array(array('leftField'=>'role_id', 'rightField'=>'id', 'joinOp'=>'=')));
	}
	
	//---------------------------------------------------------------------------
	
	protected function buildPermissionsJoin($type, &$joins)
	{
		$joins[] = array('table'=>"{$type}_perm", 'conditions'=>array(array('leftField'=>'id', 'rightField'=>"{$type}_id", 'joinOp'=>'=')));
		$joins[] = array('table'=>'perm', 'conditions'=>array(array('leftField'=>'perm_id', 'rightField'=>'id', 'joinOp'=>'=')));
	}
	
	//---------------------------------------------------------------------------
	
}
