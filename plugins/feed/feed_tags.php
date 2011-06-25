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

class FeedTags extends EscherParser
{

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_uuid($atts)
	{
		$meta = $this->content->fetchPageMeta($this->currentPageContext());
		return isset($meta['uuid']) ? $this->output->escape($meta['uuid']) : '';
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_tag($atts)
	{
		$page = $this->currentPageContext();
		$host = preg_replace('#https?://#', '', $this->siteHost);
		$date = $page->published('Y-m-d');
		$id = $page->id;
		
		return "tag:{$host},{$date}:{$id}";
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_feed()
	{
		$this->pushNamespace('feed');
		return true;
	}
		
	protected function _xtag_ns_feed()
	{
		$this->popNamespace('feed');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_feed_id($atts)
	{
		if ($uuid = $this->_tag_pages_uuid($atts))
		{
			return "urn:uuid:{$uuid}";
		}
		return $this->_tag_pages_tag($atts);
	}
	
	//---------------------------------------------------------------------------
}
