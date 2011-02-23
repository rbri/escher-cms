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

if (!defined('escher_util'))
{

define('escher_util', 1);

//------------------------------------------------------------------------------

function check($condition, $error)
{
	if (!$condition)
	{
		$trace = debug_backtrace();
	
		$file = $trace[0]['file'];
		$function = $trace[0]['function'];
		if (!empty($trace[0]['class']))
		{
			$function = $trace[0]['class'] . '::' . $function;
		}
		$line = $trace[0]['line'];
		
		trigger_error('check failed: [' . $error . '] in file ' . $file . ' in function "' . $function . '" at line ' . $line, E_USER_ERROR);
	}
}

//------------------------------------------------------------------------------

function array_stretch($array, $length, $fill = NULL)
{
	$result = $array;

	if (($diff = $length - count($result)) > 0)
	{
		if ($fill === NULL)
		{
			$fill = end($result);
		}
		while ($diff--)
		{
			$result[] = $fill;
		}
	}
	
	return $result;
}

//------------------------------------------------------------------------------

function array_multiplex($array1, $array2, $join = '')
{
	$result = $array1;
	$next = reset($array2);

	foreach ($result as &$val)
	{
		if ($next === false)
		{
			break;
		}
		$val .= ($join . $next);
		$next = next($array2);
	}
	
	while ($next !== false)
	{
		$result[] = $next;
		$next = next($array2);
	}
	
	return $result;
}

//------------------------------------------------------------------------------

}