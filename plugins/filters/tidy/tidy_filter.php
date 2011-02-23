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

// -----------------------------------------------------------------------------

class _TidyFilter extends EscherFilter
{
	private $tidy;
	
	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct(array('id'=>5));

		if (!class_exists('Tidy'))
		{
			trigger_error('Tidy php extension not loaded.');
			return;
		}

		$this->tidy = new Tidy();
	}

	//---------------------------------------------------------------------------

	public function name()
	{
		return 'Tidy';
	}

	//---------------------------------------------------------------------------

	public function cssClass()
	{
		return 'tidy';
	}

	//---------------------------------------------------------------------------

	public function filter($text, $config)
	{
		if (!$this->tidy)
		{
			return $text;
		}

		$this->tidy->parseString($text, $config, strtolower(str_replace('-', '', $this->config->get('charset', 'utf8'))));
		$this->tidy->cleanRepair();
		return $this->tidy;
	}

	//---------------------------------------------------------------------------
}
