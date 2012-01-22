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

class DesignEditDesignController extends DesignController
{	
	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct($app)
	{
		parent::__construct($app);

		$this->observer->observe(array($this, 'beforeRender'), array('escher:render:before:design'));
	}

	//---------------------------------------------------------------------------

	public function beforeRender($message, $object)
	{
		if (preg_match('/^escher:render:before:design:(.+?):/', $message, $matches))
		{
			$id = "{$matches[1]}_content";
			switch ($matches[1])
			{
				case 'template':
				case 'snippet':
					$syntax = 'html';
					break;
				case 'tag':
					$syntax = 'php';
					break;
				case 'style':
					$syntax = 'css';
					break;
				case 'script':
					$syntax = 'js';
					break;
				default:
					return;
			}
			$this->observer->notify('escher:page:request_add_element:head', $this->getInternalScriptElement($id, $syntax));
		}
	}

	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function getInternalScriptElement($id, $syntax)
	{
		$url_prefix = $this->urlToStatic('/');
		return <<<EOD
<script src="{$url_prefix}js/editarea/edit_area_full.js" type="text/javascript"></script>
<script type="text/javascript">
	editAreaLoader.init({
		id: "{$id}",				// textarea id
		syntax: "{$syntax}",		// syntax to be uses for highgliting
		start_highlight: true,	// to display with highlight mode on start-up
	});
</script>

EOD;
	}
	
//---------------------------------------------------------------------------
	
}
