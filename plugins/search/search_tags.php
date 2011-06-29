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

//------------------------------------------------------------------------------

class Excerpt extends EscherObject
{
}

// -----------------------------------------------------------------------------

class SearchTags extends EscherParser
{
	private $_search_term;
	private $_mode;
	private $_find;
	private $_limit;
	private $_start;
	private $_results;
	private $_page_ids;
	private $_result_stack;
	private $_excerpt_stack;

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
		$this->_result_stack = array();
		$this->_excerpt_stack = array();
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
	
	protected final function pushExcerpt($excerpt)
	{
		if (!($excerpt instanceof Excerpt))
		{
			$this->reportError(self::$lang->get('not_an_excerpt'), E_USER_WARNING);
			return;
		}
		
		$this->_excerpt_stack[] = $excerpt;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popExcerpt()
	{
		array_pop($this->_excerpt_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentExcerpt()
	{
		return end($this->_excerpt_stack);
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
		$curIter = $this->currentIter();

		extract($this->gatts(array(
			'find' => '',
			'mode' => '',
			'min' => 1,
			'max' => 0,
			'parent' => NULL,
			'intitle' => true,
			'inparts' => true,
			'parts' => '',
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$mode === '' || $mode === 'exact' || $mode === 'any' || $mode === 'all' || check($mode === '' || $mode === 'exact' || $mode === 'any' || $mode === 'all', $this->output->escape(self::$lang->get('unexpected_attribute_value', $mode, 'mode', 'search:if_found')));

		$find = trim(preg_replace('/\s+/', ' ', $find));

		$searchTitles = $this->truthy($intitle);
		$searchParts = $this->truthy($inparts);

		if ($searchParts && !empty($parts))
		{
			$searchParts = explode(',', $parts);
			if (count($searchParts) === 1)
			{
				$searchParts = $searchParts[0];
			}
		}
		
		if ($parent !== NULL)
		{
			$parent = intval($parent);
		}

		$this->_search_term = $find;
		$this->_limit = $limit;
		$this->_start = $start;

		if ($quoted = (($find !== '') && ($find[0] === '"') && ($find[strlen($find)-1] === '"')))
		{
			$find = trim(trim($find, '"'));
		}

		$mode = ($quoted || ($mode === '')) ? 'exact' : $mode;

		$this->_mode = $mode;
		$this->_find = $find;
		$this->_results = $this->content->searchPages($find, $mode, $parent, $status, $searchTitles, $searchParts);
		$this->_page_ids = array_keys($this->_results);
		
		return (count($this->_results) >= $min) && (!$max || count($this->_results) <= $max);
	}

	protected function _xtag_search_if_found($atts)
	{
		$this->_search_term = NULL;
		$this->_page_ids = NULL;
		$this->_results = NULL;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_term($atts)
	{
		return $this->output->escape($this->_search_term);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_count($atts)
	{
		extract($this->gatts(array(
			'limit' => $this->_limit,
			'start' => $this->_start,
		),$atts));
		
		if ($start)
		{
			if (!$limit)
			{
				return ($start > 1) ? 0 : count($this->_results);
			}
			$offset = (max(1, $start) - 1) * $limit;
			return max(0, min($limit, count($this->_results) - $offset));
		}
		
		return count($this->_results);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_any($atts)
	{
		return ($this->_tag_search_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_any_before($atts)
	{
		extract($this->gatts(array(
			'limit' => $this->_limit,
			'start' => $this->_start,
		),$atts));
		
		$offset = (max(1, $start) - 1) * $limit;
		$atts['start'] = 1;
		return ($offset > 0) && ($this->_tag_search_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_any_after($atts)
	{
		extract($this->gatts(array(
			'limit' => $this->_limit,
			'start' => $this->_start,
		),$atts));
		
		$atts['start'] = $start + 1;
		return ($limit > 0) && ($this->_tag_search_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_search_first($atts)
	{
		extract($this->gatts(array(
			'limit' => $this->_limit,
			'start' => $this->_start,
		),$atts));
		
		$offset = (max(1, $start) - 1) * $limit;

		if (empty($this->_results) || !isset($this->_page_ids[$offset]) || (!$page = $this->content->fetchPageByID($this->_page_ids[$offset])))
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
		extract($this->gatts(array(
			'limit' => $this->_limit,
			'start' => $this->_start,
		),$atts));
		
		$offset = (max(1, $start) - 1) * $limit;
		$count = $this>_tag_search_count($atts);
		$offset += ($count - 1);

		if (empty($this->_results) || !isset($this->_page_ids[$offset]) || (!$page = $this->content->fetchPageByID($this->_page_ids[$offset])))
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
		extract($this->gatts(array(
			'limit' => $this->_limit,
			'start' => $this->_start,
		),$atts));
		
		$index =& $this->pushIndex(1);

		$out = '';

		$offset = (max(1, $start) - 1) * $limit;
		if (($numResults = min($limit, count($this->_results) - $offset)) > 0)
		{
			$content = $this->getParsable();
			
			$whichResult = $atOffset = 0;
			foreach ($this->_results as $pageID => $info)
			{
				// skip all results prior to requested offset
				
				if ($atOffset++ < $offset)
				{
					continue;
				}
			
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
				
				if ($whichResult == $numResults)
				{
					break;
				}
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
	
	protected function _tag_search_excerpts_each($atts)
	{
		extract($this->gatts(array(
			'limit' => '5',
			'hilight' => 'strong',
			'maxchars' => '50',
			'separator' => ' &#8230;',
		),$atts));
		
		$maxchars = intval($maxchars);
		$limit = intval($limit);
		
		$page = $this->currentPageContext();

		// build an excerpt list from each of the page parts where a match was found
		
		$excerpts = array();
		$numExcerpts = 0;
		
		if ($this->_mode === 'exact')
		{
			$find = preg_quote($this->_find);
			$regex_search = "/(?:\G|\s).{0,{$maxchars}}{$find}.{0,{$maxchars}}(?:\s|$)/iu";
			$regex_hilite = "/({$find})/i";
		}
		else
		{
			$find = preg_replace('/\s+/', '|', preg_quote($this->_find));
			$regex_search = "/(?:\G|\s).{0,{$maxchars}}({$find}).{0,{$maxchars}}(?:\s|$)/iu";
			$regex_hilite = "/({$find})/i";
		}
		
		foreach ($this->_results[$page->id] as $part)
		{
			if (empty($part))
			{
				continue;	// NULL part signifies match to page title (not a part)
			}
	
			if (($part = $this->content->fetchPagePart($page, $part, false)) !== false)
			{
				$excerpt = preg_replace('/\s+/', ' ', strip_tags($this->parsePart($part)));

				preg_match_all($regex_search, $excerpt, $matches);

				foreach ($matches[0] as $match)
				{
					$match = preg_replace('/^[^>]+?>/', '', $match);
					$match = preg_replace($regex_hilite, "<{$hilight}>$1</{$hilight}>", $match);
					$excerpts[] = $match . $separator;
					
					if (++$numExcerpts === $limit)
					{
						break 2;
					}
				}
			}
		}
		
		// iterate over excerpts
		
		$index =& $this->pushIndex(1);

		$out = '';

		if ($numResults = count($excerpts))
		{
			$content = $this->getParsable();
			
			$whichResult = 0;
			foreach ($excerpts as $excerpt)
			{
				++$whichResult;
				
				$excerpt = $this->factory->manufacture('Excerpt', array('content'=>$excerpt));
				$excerpt->isFirst = ($whichResult == 1);
				$excerpt->isLast = ($whichResult == $numResults);

				$this->pushExcerpt($excerpt);
				$out .= $this->parseParsable($content);
				$this->popExcerpt();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_first_excerpt($atts)
	{
		return ($excerpt = $this->currentExcerpt()) ? $excerpt->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_if_last_excerpt($atts)
	{
		return ($excerpt = $this->currentExcerpt()) ? $excerpt->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_search_excerpt($atts)
	{
		return ($excerpt = $this->currentExcerpt()) ? $excerpt->content : '';
	}
	
	//---------------------------------------------------------------------------
}
