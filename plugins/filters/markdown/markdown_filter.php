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

// -----------------------------------------------------------------------------

class _MarkdownFilter extends EscherFilter
{
	private $markdown;
	private $smartypants;
	
	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct(array('id'=>3));

		if (!class_exists('MarkdownExtra_Parser'))
		{
			$myInfo = $this->factory->getPlug('MarkdownFilter');
			include(dirname($myInfo['file']) . '/markdown.class.php');
		}

		$this->markdown = new MarkdownExtra_Parser();
		
		try
		{
			$this->smartypants = $this->factory->manufacture('SmartyPantsFilter');
		}
		catch (Exception $e)
		{
		}
	}

	//---------------------------------------------------------------------------

	public function name()
	{
		return 'Markdown';
	}

	//---------------------------------------------------------------------------

	public function cssClass()
	{
		return 'markdown';
	}

	//---------------------------------------------------------------------------

	public function filter($text)
	{
		$text = $this->markdown->transform($text);
		
		if ($this->smartypants)
		{
			$text = $this->smartypants->filter($text);
		}
		
		return $text;
	}

	//---------------------------------------------------------------------------
}
