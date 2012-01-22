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

// -----------------------------------------------------------------------------

// This class is never instantiated directly. It serves only to provide a common
// base class for implementation classes to extend, so that we may easily find
// and load all DatabaseInstaller plugins via SparkPlug's getExtensions() method.

abstract class _DatabaseInstaller extends SparkPlug
{
	// --------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
	}

	// --------------------------------------------------------------------------

	// Abstract Public Methods: To be implemented by implementation class

	// --------------------------------------------------------------------------

	abstract public function driverName();
	abstract public function displayName();
	abstract public function setConnectionDefaults(&$vars);
	abstract public function validateConnectionFields($data, &$errors, &$vars);
	abstract public function buildConnectionParams($data);
	abstract public function checkSupport($connectionParams, &$errorMsg);
}
