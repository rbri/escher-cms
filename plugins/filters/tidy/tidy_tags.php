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

class TidyTags extends EscherParser
{
	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_tidy()
	{
		$this->pushNamespace('tidy');
		return true;
	}
		
	protected function _xtag_ns_tidy()
	{
		$this->popNamespace('tidy');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_tidy_filter($atts)
	{
		static $tidy = NULL;

		extract($this->gatts(array(
			'indent'  => $this->app->get_pref('tidy_indent', true),
			'clean'   => $this->app->get_pref('tidy_clean', true),
			'xhtml'   => $this->app->get_pref('tidy_xhtml', true),
			'wrap'    => $this->app->get_pref('tidy_wrap', 0),
		),$atts));
		
		$config = array
		(
			'indent'       => $this->truthy($indent),
			'clean'        => $this->truthy($clean),
			'output-xhtml' => $this->truthy($xhtml),
			'wrap'         => intval($wrap),
		);
		
		if (!isset($tidy))
		{
			$tidy = $this->factory->manufacture('TidyFilter');
		}
		
 		return $tidy->filter($this->getContent(), $config);
	}

	//---------------------------------------------------------------------------
}
