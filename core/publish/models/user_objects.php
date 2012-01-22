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

if (!defined('escher_user_objects'))
{

define('escher_user_objects', 1);

//------------------------------------------------------------------------------

class _Role extends EscherObject
{
	// database fields

	public $name;

	// cache fields...

	public $isAdmin;
	public $permissions;
}

//------------------------------------------------------------------------------

class _User extends EscherObject
{
	// database fields

	public $email;
	public $login;
	public $password;
	public $nonce;
	public $logged;
	public $created;
	public $name;

	// cache fields...

	public $isAdmin;
	public $roles;
	public $permissions;
	
	public function roleNames()
	{
		$names = array();
		if (!empty($this->roles))
		{
			foreach ($this->roles as $id => $role)
			{
				$names[$id] = $role->name;
			}
		}
		return $names;
	}

	public function allowed($perm)
	{
		return $this->isAdmin || isset($this->permissions[$perm]);
	}

	public function addPerm($perm)
	{
		$this->permissions[$perm] = true;
	}

	public function removePerm($perm)
	{
		unset($this->permissions[$perm]);
	}
}

//------------------------------------------------------------------------------

}