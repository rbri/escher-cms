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

class SearchTags extends EscherParser
{
	private $_results;
	private $_result_stack;

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
		$this->_results = NULL;
		$this->_result_stack = array();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_search()
	{
		$this->pushNamespace('search');
		return true;
	}
		
	protected function _xtag_ns_search()
	{
		$this->popNamespace('search');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_found($atts)
	{
		extract($this->gatts(array(
			'find' => '',
			'min' => 1,
			'max' => 0,
			'parent' => NULL,
			'intitle' => true,
			'inparts' => true,
			'parts' => '',
		),$atts));

		$searchTitles = $this->truthy($intitle);
		$searchParts = $this->truthy($inparts);

		if ($searchParts && !empty($parts))
		{
			$searchParts = explode(',', $parts);
		}
		
		if ($parent !== NULL)
		{
			$parent = intval($parent);
		}

		$this->_results = $this->content->searchPages($find, $parent, $searchTitles, $searchParts);
		
		return (count($this->_results) >= $min) && (!$max || count($this->_results) <= $max);
	}

	protected function _xtag_search_if_found($atts)
	{
		$this->_results = NULL;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_count($atts)
	{
		return count($this->_results);
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_search_first($atts)
	{
		if (empty($this->_results) || (!$page = $this->content->fetchPageByID($this->_results[0])))
		{
			$this->dupPageContext();
			$this->dup($this->_result_stack);
			$this->reportError(self::$lang->get('page_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirstSearchResult = true;
		$page->isLastSearchResult = false;
		$this->pushPageContext($page);
		$this->pushSearchResult($page);
		return true;
	}
	
	protected function _xtag_search_first($atts)
	{
		$this->popSearchResult();
		$this->popPageContext();
	}

	//---------------------------------------------------------------------------

	protected function _tag_search_last($atts)
	{
		if (empty($this->_results) || (!$page = $this->content->fetchPageByID($this->_results[count($this->_results)-1])))
		{
			$this->dupPageContext();
			$this->dup($this->_result_stack);
			$this->reportError(self::$lang->get('page_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirstSearchResult = false;
		$page->isLastSearchResult = true;
		$this->pushPageContext($page);
		$this->pushSearchResult($page);
		return true;
	}
	
	protected function _xtag_search_last($atts)
	{
		$this->popSearchResult();
		$this->popPageContext();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_search_each($atts)
	{
		$index =& $this->pushIndex(1);

		$out = '';

		if ($numResults = count($this->_results))
		{
			$content = $this->getParsable();
			
			$whichResult = 0;
			foreach ($this->_results as $pageID)
			{
				++$whichResult;
				
				$page = $this->content->fetchPageByID($pageID);
				$page->isFirstSearchResult = ($whichResult == 1);
				$page->isLastSearchResult = ($whichResult == $numResults);

				$this->pushPageContext($page);
				$this->pushSearchResult($page);
				$out .= $this->parseParsable($content);
				$this->popSearchResult();
				$this->popPageContext();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_page($atts)
	{
		if (!$page = $this->currentSearchResult())
		{
			$this->dupPageContext();
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_search_page($atts)
	{
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_first($atts)
	{
		return ($page = $this->currentSearchResult()) ? $page->isFirstSearchResult : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_last($atts)
	{
		return ($page = $this->currentSearchResult()) ? $page->isLastSearchResult : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushSearchResult($page)
	{
		if (!($page instanceof Page))
		{
			$this->reportError(self::$lang->get('not_a_page'), E_USER_WARNING);
			return;
		}
		
		$this->_result_stack[] = $page;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popSearchResult()
	{
		array_pop($this->_result_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentSearchResult()
	{
		return end($this->_result_stack);
	}
	
	//---------------------------------------------------------------------------
}
