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

class MarkitupContentController extends ContentController
{	
	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct($app)
	{
		parent::__construct($app);

		$this->observer->observe(array($this, 'beforeRender'), array('escher:render:before:content'));
	}

	//---------------------------------------------------------------------------

	public function beforeRender($message, $object)
	{
		// how about we only include the needed sets?
	
		$sets[] = 'html';
		
		foreach (array('markdown', 'textile', 'wiki') as $set)
		{
			if (class_exists($set.'filter'))
			{
				$sets[] = $set;
			}
		}

		$this->observer->notify('escher:page:request_add_element:head', $this->getInternalScriptElement($sets));
	}

	//---------------------------------------------------------------------------

	public function action_preview($params)
	{
		// should do a referrer check here!
		
		if ($data = @$params['pv']['data'])
		{
			if ($parser = @$params[0])
			{
				$method = 'preview_'.$parser;
				if (is_callable(array($this, $method)))
				{
					$data = $this->{$method}($data);
				}
			}
		}

		echo $data;
	}

	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function preview_markdown($data)
	{
		try
		{
			$parser = $this->factory->manufacture('MarkdownFilter');
		}
		catch (Exception $e)
		{
			return $data;
		}
		return $parser->filter($data);
	}
	
	//---------------------------------------------------------------------------

	private function preview_textile($data)
	{
		try
		{
			$parser = $this->factory->manufacture('TextileFilter');
		}
		catch (Exception $e)
		{
			return $data;
		}
		return $parser->filter($data);
	}
	
	//---------------------------------------------------------------------------

	private function getInternalScriptElement($sets)
	{
		$url_prefix = $this->urlToStatic('/');
		$html = <<<EOD
<link rel="stylesheet" type="text/css" href="{$url_prefix}js/markitup/skins/markitup/style.css" />

EOD;

		foreach ($sets as $set)
		{
			$html .= <<<EOD
<link rel="stylesheet" type="text/css" href="{$url_prefix}js/markitup/sets/{$set}/style.css" />

EOD;
		}
			$html .= <<<EOD
<style type="text/css">
	#content .form-area textarea {
		width: 95%;
	}
</style>
<script src="{$url_prefix}js/markitup/jquery.markitup.js" type="text/javascript"></script>

EOD;
		foreach ($sets as $set)
		{
			$html .= <<<EOD
<script src="{$url_prefix}js/markitup/sets/{$set}/set.js" type="text/javascript"></script>

EOD;
		}
			$html .= <<<EOD
<script type="text/javascript">
	\$(document).ready(function(){

EOD;
		foreach ($sets as $set)
		{
			$html .= <<<EOD
		\$('textarea.{$set}').markItUp(mySettings_{$set});

EOD;
		}
			$html .= <<<EOD
	});
</script>

EOD;
		return $html;
	}
	
//---------------------------------------------------------------------------
	
}
