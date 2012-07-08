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

//------------------------------------------------------------------------------

class _Input extends SparkPlug
{
	private $_get;
	private $_post;
	private $_cookie;
	private $_errors;
	private $_validator;
	
	//---------------------------------------------------------------------------

	public function __construct($get, $post, $cookie)
	{
		parent::__construct();
		$this->_get = $get;
		$this->_post = $post;
		$this->_cookie = $cookie;
		$this->_errors = array();
		$this->_validator = $this->factory->manufacture('SparkValidator');
	}

	//---------------------------------------------------------------------------
	
	public function get($key, $default = NULL)
	{
		return isset($this->_get[$key]) ? $this->_get[$key] : $default;
	}

	//---------------------------------------------------------------------------
	
	public function post($key, $default = NULL)
	{
		return isset($this->_post[$key]) ? $this->_post[$key] : $default;
	}

	//---------------------------------------------------------------------------
	
	public function cookie($key, $default = NULL)
	{
		return isset($this->_cookie[$key]) ? $this->_cookie[$key] : $default;
	}

	//---------------------------------------------------------------------------
	
	public function getVars()
	{
		return $this->_get;
	}

	//---------------------------------------------------------------------------
	
	public function postVars()
	{
		return $this->_post;
	}

	//---------------------------------------------------------------------------
	
	public function cookieVars()
	{
		return $this->_cookie;
	}

	//---------------------------------------------------------------------------
	
	public function errors()
	{
		return $this->_errors;
	}

	//---------------------------------------------------------------------------
	
	public function hasGetVars()
	{
		return !empty($this->_get);
	}

	//---------------------------------------------------------------------------
	
	public function hasPostVars()
	{
		return !empty($this->_post);
	}

	//---------------------------------------------------------------------------
	
	public function hasCookies()
	{
		return !empty($this->_cookie);
	}

	//---------------------------------------------------------------------------
	
	public function hasErrors()
	{
		return !empty($this->_errors);
	}

	//---------------------------------------------------------------------------
	
	public function isError($name)
	{
		return isset($this->_errors[$name]);
	}

	//---------------------------------------------------------------------------
	
	public function validate($label, $name, $rule)
	{
		$fieldValue = @$this->_post[$name];
		$this->_validator->validateField(array('label'=>$label, 'rule'=>$rule), $name, $fieldValue, $this->_post, $this->_errors);
		return $fieldValue;
	}

	//---------------------------------------------------------------------------
	
}
