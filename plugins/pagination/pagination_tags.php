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

class PaginationTags extends EscherParser
{
	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_pagination()
	{
		$this->pushNamespace('pagination');
		return true;
	}
		
	protected function _xtag_ns_pagination()
	{
		$this->popNamespace('pagination');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_pagination_page_num($atts)
	{
		extract($this->gatts(array(
			'var' => 'p',
		),$atts, false));

 		return $this->pageNum($var);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pagination_prev_link($atts)
	{
		extract($this->gatts(array(
			'var' => 'p',
			'page' => '',
			'title' => 'Previous',
			'qsa' => '',
		),$atts, false));

		// determine previous page number
		
		if ($page === '')
		{
			$page = $this->pageNum($var) - 1;
		}
		$page = max(1, $page);
		
		// create new query string vars, with updated value for page index
		
		$vars = $this->input->getVars();
		$vars[$var] = $page;
		
		// we also append any requested post vars to the query string
		
		if ($qsa !== '')
		{
			foreach (array_map('trim', explode(',', $qsa)) as $key)
			{
				if ($val = $this->input->post($key))
				{
					$vars[$key] = $val;
				}
			}
		}
		
		unset($atts['var']);
		unset($atts['page']);
		$atts['href'] = $this->pageURL() . $this->makeQueryString($vars);
		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($title);

		return $this->output->tag($content, 'a', '', '', $atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pagination_next_link($atts)
	{
		extract($this->gatts(array(
			'var' => 'p',
			'page' => '',
			'title' => 'Next',
			'qsa' => '',
			'max' => '',
		),$atts, false));


		// determine next page number
		
		if ($page === '')
		{
			$page = $this->pageNum($var) + 1;
		}
		if (!empty($max))
		{
			$page = min($max, $page);
		}
		$page = max(1, $page);
		
		// create new query string vars, with updated value for page index
		
		$vars = $this->input->getVars();
		$vars[$var] = $page;
		
		// we also append any requested post vars to the query string
		
		if ($qsa !== '')
		{
			foreach (array_map('trim', explode(',', $qsa)) as $key)
			{
				if ($val = $this->input->post($key))
				{
					$vars[$key] = $val;
				}
			}
		}
		
		unset($atts['var']);
		unset($atts['page']);
		$atts['href'] = $this->pageURL() . $this->makeQueryString($vars);
		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($title);

		return $this->output->tag($content, 'a', '', '', $atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pagination_page_list($atts)
	{
		extract($this->gatts(array(
			'label_previous' => '&larr; Previous',
			'label_next' => 'Next &rarr;',
			'always_show_labels' => false,
			'id' => '',
			'class' => 'pagination',
			'class_selected' => 'selected',
			'cur_page' => 1,
			'last_page' => 1,
			'page_url' => $this->pageURL(),
			'hash' => NULL,
		),$atts, false));
		
		$cur_page !== '' || check($cur_page, $this->output->escape(self::$lang->get('attribute_required', 'cur_page', 'pagination:page_list')));
		$last_page !== '' || check($last_page, $this->output->escape(self::$lang->get('attribute_required', 'last_page', 'pagination:page_list')));

		$cur_page = intval($cur_page);
		$last_page = intval($last_page);
		$page_url = rtrim($page_url, '/') . '/';

		$vars = compact('page_url', 'label_previous', 'label_next', 'always_show_labels', 'id', 'class', 'class_current', 'cur_page', 'last_page', 'hash');

		$plugInfo = $this->factory->getPlug('SparkPagination');
		$this->app->view()->pushViewDir(dirname($plugInfo['file']) . '/views');
		$result = $this->app->render('pagination', $vars, true);
		$this->app->view()->popViewDir();
		return $result;
	}

	//---------------------------------------------------------------------------
	
	private function pageNum($var)
	{
 		return max(1, intval($this->input->get($var)));
	}
	
	//---------------------------------------------------------------------------
}
