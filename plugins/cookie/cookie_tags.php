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

class CookieTags extends EscherParser
{
	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_cookie()
	{
		$this->pushNamespace('cookie');
		return true;
	}
		
	protected function _xtag_ns_cookie()
	{
		$this->popNamespace('cookie');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_cookie_if_cookie($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'matches' => NULL,
		),$atts));

		if ($name === '')
		{
			return $this->input->hasCookies();
		}

		$cookieVal = $this->input->cookie($name);

		if (isset($matches) && $cookieVal !== NULL)
		{
			return preg_match('@'.$matches.'@', $cookieVal) ? true : false;
		}

		return $cookieVal !== NULL;
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_cookie_cookie($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'escape' => false,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'cookie')));
		
		$cookieVal = $this->input->cookie($name);

		return ($cookieVal !== NULL) ? ($this->truthy($escape) ? $this->output->escape($cookieVal) : $cookieVal) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_cookie_setcookie($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'value' => '',
			'expire' => 0,
			'path' => $this->currentPageContext()->baseURI(),
			'domain' => '',
			'secure' => false,
			'httponly' => false,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'cookie')));

		$cookieVal = $this->input->cookie($name);

		setcookie($name, $value, intval($expire), $path, $domain, $this->truthy($secure), $this->truthy($httponly));
	}

	//---------------------------------------------------------------------------
}
