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

class htmlawedTags extends EscherParser
{
	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_htmlawed()
	{
		$this->pushNamespace('htmlawed');
		return true;
	}
		
	protected function _xtag_ns_htmlawed()
	{
		$this->popNamespace('htmlawed');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_htmlawed_filter($atts)
	{
		static $htmlawed = NULL;

		if (!isset($htmlawed))
		{
			$htmlawed = $this->factory->manufacture('htmlawedFilter');
		}
		
		$params = NULL;
		
		foreach ($atts as $key => $val)
		{
			$params[$key] = ctype_digit($val) ? intval($val) : $val;
		}
		
 		return $htmlawed->filter($this->getContent(), $params);
	}

	//---------------------------------------------------------------------------
}
