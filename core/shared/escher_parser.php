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

require('core_tag_parser.php');

//------------------------------------------------------------------------------

class _EscherParser extends CoreTagParser
{
	private static $_default_iter = array
	(
		'page' => NULL,
		'id' => '',
		'category' => '',
		'status' => 'published',
		'limit' => '0',
		'start' => '1',
	);
	
	private $_cacheID;

	private $_indexes;
	private $_iter_stack;
	private $_param_stack;
	private $_yield_stack;
	private $_snippet_parse_stack;
	private $_block_parse_stack;
	private $_part_parse_stack;

	private $_context_stack;
	private $_page_stack;
	private $_child_stack;
	private $_sibling_stack;
	private $_block_stack;
	private $_image_stack;
	private $_file_stack;
	private $_link_stack;
	private $_category_stack;

	private $_production_status;
	private $_current_page;

	protected $prefs;
	protected $siteHost;
	protected $secureSiteHost;
	protected $content;
	protected $theme;
	protected $branch;
	protected $debug_level;
	protected $category_trigger;
	protected $drafts_are_published;
	
	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher);

		$this->_cacheID = 0;
		$this->_indexes = array();
		$this->_iter_stack = array();
		$this->_param_stack = array();
		$this->_yield_stack = array();
		$this->_snippet_parse_stack = array();
		$this->_block_parse_stack = array();
		$this->_part_parse_stack = array();

		$this->_context_stack = array();
		$this->_page_stack = array();
		$this->_child_stack = array();
		$this->_sibling_stack = array();
		$this->_block_stack = array();
		$this->_image_stack = array();
		$this->_file_stack = array();
		$this->_link_stack = array();
		$this->_category_stack = array();
		
		$this->_production_status = $params['production_status'];

		$this->prefs = $params['prefs'];
		$this->siteHost = $params['site_host'];
		$this->secureSiteHost = $params['secure_site_host'];
		$this->content = $content;
		$this->theme = $params['theme'];
		$this->branch = $this->_production_status;
		$this->debug_level = $params['debug_level'];
		$this->category_trigger = $this->getPref('category_trigger', 'category');
		$this->drafts_are_published = @$params['drafts_are_published'];

		if (!$this->_current_page = $this->content->fetchPageByURI($currentURI))
		{
			throw new SparkHTTPException_NotFound();
		}
		
		switch ($this->_current_page->status)
		{
			case _Page::Status_published:
			case _Page::Status_sticky:
				break;
			case _Page::Status_draft:
				if ($this->drafts_are_published)
				{
					break;
				}
			default:
				throw new SparkHTTPException_NotFound();
		}
		
		$this->pushNamespace('pages');
		$this->pushPageContext($this->_current_page);
		$this->pushPage($this->_current_page);
	}

	//---------------------------------------------------------------------------
	
	public final function currentPageTemplateContent(&$contentType, &$parsable, &$cacheable, &$secure, &$lastModTime, &$fileName, &$fileSize)
	{
		if (!$template = $this->currentPageContext()->fetchTemplate($this->content, $this->theme, $this->branch, $this->prefs))
		{
			$this->reportError(self::$lang->get('template_not_found', $this->currentPageContext()->activeTemplateName()), E_USER_WARNING);
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'template not found'));
		}
		
		$contentType = !empty($template->ctype) ? $template->ctype : 'text/html';
		$parsable = $template->isParsable();
		$cacheable = $this->_current_page->isCacheable();
		$secure = $this->_current_page->isSecure();
		$lastModTime = strtotime($this->_current_page->edited);
		$fileName = (!empty($template->download)) ? $template->slug : '';
		$fileSize = (!empty($template->size)) ? $template->size : '';

		$out = $template->content;
		
		if ($this->debug_level > 2)
		{
			if (empty($fileName) && ($contentType === 'text/html'))
			{
				$out .= "\n<!-- /TEMPLATE: {$template->name} -->\n";
			}
		}
		
		return $out;
	}

	//---------------------------------------------------------------------------
	
	public final function errorPageTemplateContent($status, $message, &$contentType)
	{
		if (!$page = $this->content->fetchPageByURI('error/'.$status))
		{
			if (!$page = $this->content->fetchPageByURI('error/default'))
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'error page not found'));
			}
		}

		if (!$template = $page->fetchTemplate($this->content, $this->theme, $this->branch, $this->prefs))
		{
			$this->reportError(self::$lang->get('template_not_found', $page->activeTemplateName()), E_USER_WARNING);
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'template not found'));
		}

		$this->pushPageContext($page);
		$this->setStatus($status, $message);

		if ($this->debug_level > 2)
		{
			$start = microtime();
		}

		$out = $this->parse($template->content);

		if ($this->debug_level > 2)
		{
			$stop = microtime();
			list($sm, $ss) = explode(' ', $start);
			list($em, $es) = explode(' ', $stop);
			$elapsed = number_format(($em + $es) - ($sm + $ss), 4);
			$out .= "\n<!-- /TEMPLATE: {$template->name} [Execution time: {$elapsed} seconds] -->\n";
		}

		$contentType = !empty($template->ctype) ? $template->ctype : 'text/html';
		$this->popPageContext();

		return $out;
	}

	//---------------------------------------------------------------------------
	//
	// Protected Methods
	//
	//---------------------------------------------------------------------------
	
	protected final function productionStatus()
	{
		return $this->_production_status;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function getPref($key, $default = '')
	{
		return isset($this->prefs[$key]) ? $this->prefs[$key] : $default;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function baseURL($full = true, $secure = false)
	{
		return $this->urlToStatic('', $full ? ($secure ? $this->secureSiteHost : $this->siteHost) : false, false);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function siteURL($full = true, $secure = false)
	{
		return $this->urlTo('', $full ? ($secure ? $this->secureSiteHost : $this->siteHost) : false, false);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pageURL($full = true, $page = NULL)
	{
		if (!$page)
		{
			$page = $this->currentPageContext();
		}

		return $this->urlTo($this->pageURI($page), $full ? ($page->isSecure() ? $this->secureSiteHost : $this->siteHost) : false, false);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function parseTemplate($name)
	{
		if (($template = $this->content->fetchTemplateContent($name, $this->theme, $this->branch)) === false)
		{
			$this->reportError(self::$lang->get('template_not_found', $name), E_USER_WARNING);
			return;
		}
		
		if ($this->debug_level > 2)
		{
			$start = microtime();
		}

		$out = $this->parse($template);
		
		if ($this->debug_level > 2)
		{
			$stop = microtime();
			list($sm, $ss) = explode(' ', $start);
			list($em, $es) = explode(' ', $stop);
			$elapsed = number_format(($em + $es) - ($sm + $ss), 4);
			$out .= "\n<!-- /TEMPLATE: {$template->name} [Execution time: {$elapsed} seconds] -->\n";
		}

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function parseSnippet($nameOrID, $params = NULL)
	{
		if (($snippet = $this->content->fetchSnippetContent($nameOrID, $this->theme, $this->branch)) === false)
		{
			$this->reportError(self::$lang->get('snippet_not_found', $nameOrID), E_USER_WARNING);
			return;
		}
		
		if (count(array_keys($this->_snippet_parse_stack, $nameOrID)) >= 10)
		{
			$this->reportError(self::$lang->get('snippet_recursion_limit', $nameOrID), E_USER_WARNING);
			return;
		}

		$this->_param_stack[] = $params;
		$this->_snippet_parse_stack[] = $nameOrID;

		if ($this->debug_level > 2)
		{
			$start = microtime();
		}

		$out = $this->parse($snippet);
		
		if ($this->debug_level > 2)
		{
			$stop = microtime();
			list($sm, $ss) = explode(' ', $start);
			list($em, $es) = explode(' ', $stop);
			$elapsed = number_format(($em + $es) - ($sm + $ss), 4);
			$out = "\n<!-- SNIPPET: {$nameOrID} -->\n" . $out . "\n<!-- /SNIPPET: {$nameOrID} [Execution time: {$elapsed} seconds] -->\n";
		}

		array_pop($this->_snippet_parse_stack);
		array_pop($this->_param_stack);

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function parseBlock($block)
	{
		if (!$this->getPref('parsing_in_blocks'))
		{
			return $block->content_html;
		}
		
		if (isset($this->_block_parse_stack[$block->id]))
		{
			$this->reportError(self::$lang->get('block_recursion_limit', $block->name), E_USER_WARNING);
			return;
		}

		$this->_block_parse_stack[$block->id] = true;

		$out = $this->parse($block->content_html);

		if ($this->debug_level > 2)
		{
			$out = "\n<!-- BLOCK: {$block->name} -->\n" . $out . "\n<!-- /BLOCK: {$block->name} -->\n";
		}

		array_pop($this->_block_parse_stack);

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function parsePart($part)
	{
		if (!$this->getPref('parsing_in_parts'))
		{
			return $part->content_html;
		}

		if (isset($this->_part_parse_stack[$part->id]))
		{
			$this->reportError(self::$lang->get('part_recursion_limit', $part->name), E_USER_WARNING);
			return;
		}

		$this->_part_parse_stack[$part->id] = true;
		$out = $this->parse($part->content_html);
		array_pop($this->_part_parse_stack);
		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function &pushIndex($val)
	{
		$index =& $this->_indexes[count($this->_indexes)];
		$index = $val;
		return $index;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popIndex()
	{
		array_pop($this->_indexes);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushIter($iter)
	{
		$this->_iter_stack[] = $iter;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popIter()
	{
		array_pop($this->_iter_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentIter()
	{
		return empty($this->_iter_stack) ? self::$_default_iter : end($this->_iter_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function dupPageContext()
	{
		$this->dup($this->_context_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushPageContext($page)
	{
		if (!($page instanceof Page))
		{
			$this->reportError(self::$lang->get('not_a_page'), E_USER_WARNING);
			return;
		}
		
		$this->_context_stack[] = $page;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popPageContext()
	{
		if (count($this->_context_stack) > 1)
		{
			array_pop($this->_context_stack);
		}
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentPageContext()
	{
		return end($this->_context_stack);
	}

	//---------------------------------------------------------------------------
	
	protected final function isCategoryPage()
	{
		return ($this->currentPageContext()->type === _PageCategory::PageType);
	}

	//---------------------------------------------------------------------------
	
	protected final function findNonVirtualParent($page)
	{
		$parent = $page;
		while ($parent && $parent->virtual)
		{
			$parent = $parent->parent();
		}
		return $parent;
	}

	//---------------------------------------------------------------------------
	
	protected final function pageURI($page = NULL)
	{
		return $page ? $page->uri() : $this->currentPageContext()->uri();
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushPage($page)
	{
		if (!($page instanceof Page))
		{
			$this->reportError(self::$lang->get('not_a_page'), E_USER_WARNING);
			return;
		}
		
		$this->_page_stack[] = $page;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popPage()
	{
		array_pop($this->_page_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentPage()
	{
		return end($this->_page_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushChild($page)
	{
		if (!($page instanceof Page))
		{
			$this->reportError(self::$lang->get('not_a_page'), E_USER_WARNING);
			return;
		}
		
		$this->_child_stack[] = $page;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popChild()
	{
		array_pop($this->_child_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentChild()
	{
		return end($this->_child_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushSibling($page)
	{
		if (!($page instanceof Page))
		{
			$this->reportError(self::$lang->get('not_a_page'), E_USER_WARNING);
			return;
		}
		
		$this->_sibling_stack[] = $page;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popSibling()
	{
		array_pop($this->_sibling_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentSibling()
	{
		return end($this->_sibling_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pageParent($childPage)
	{
		return $childPage->parent();
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushBlock($block)
	{
		if (!($block instanceof Block))
		{
			$this->reportError(self::$lang->get('not_a_block'), E_USER_WARNING);
			return;
		}
		
		$this->_block_stack[] = $block;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popBlock()
	{
		array_pop($this->_block_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentBlock()
	{
		return end($this->_block_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushImage($image)
	{
		if (!($image instanceof Image))
		{
			$this->reportError(self::$lang->get('not_an_image'), E_USER_WARNING);
			return;
		}
		
		$this->_image_stack[] = $image;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popImage()
	{
		array_pop($this->_image_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentImage()
	{
		return end($this->_image_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushFile($file)
	{
		if (!($file instanceof File))
		{
			$this->reportError(self::$lang->get('not_a_file'), E_USER_WARNING);
			return;
		}
		
		$this->_file_stack[] = $file;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popFile()
	{
		array_pop($this->_file_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentFile()
	{
		return end($this->_file_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushLink($link)
	{
		if (!($link instanceof Link))
		{
			$this->reportError(self::$lang->get('not_a_link'), E_USER_WARNING);
			return;
		}
		
		$this->_link_stack[] = $link;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popLink()
	{
		array_pop($this->_link_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentLink()
	{
		return end($this->_link_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushCategory($category)
	{
		if (!($category instanceof Category))
		{
			$this->reportError(self::$lang->get('not_a_category'), E_USER_WARNING);
			return;
		}
		
		$this->_category_stack[] = $category;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popCategory()
	{
		array_pop($this->_category_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentCategory()
	{
		return end($this->_category_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function categoryURL($full = true, $category = NULL)
	{
		if (!$category)
		{
			if (!$category = $this->currentCategory())
			{
				return '';
			}
		}
		
		$uri = $this->content->fetchCategoryURI($category);
		return $this->siteURL($full) . '/' . $this->category_trigger . $uri;
	}
	
	//---------------------------------------------------------------------------
	//
	// Here there be tags...
	//
	//---------------------------------------------------------------------------
	
	protected function _tag_phone_home($atts)
	{
		extract($this->gatts(array(
			'full' => true,
		),$atts,false));

		$url = $this->siteURL(!$this->falsy($full));
		$atts['href'] = empty($url) ? '/' : $url;
		unset($atts['full']);
		
		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($this->getPref('site_name'));

		return $this->output->tag($content, 'a', '', '', $atts);
	}
	
	//---------------------------------------------------------------------------
	// More Core Tags ("core" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_core_date($atts)
	{
		extract($this->gatts(array(
			'format' => '%A, %B %d, %Y',
			'date' => 'now',
			'timezone' => $this->prefs['site_time_zone'],
		),$atts));
		
		$date = $this->factory->manufacture('SparkDateTime', $date, $timezone);
		return $this->output->escape($date->strformat($format));
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_repeat($atts)
	{
		extract($this->gatts(array(
			'start' => 1,
			'stop' => NULL,
		),$atts));

		$stop || check($stop, $this->output->escape(self::$lang->get('attribute_required', 'stop', 'repeat')));

		$index =& $this->pushIndex($start);

		$out = '';

		$content = $this->getParsable();
		
		while ($index <= $stop)
		{
			$out .= $this->parseParsable($content);
			++$index;
		}

		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_index($atts)
	{
		if (!$count = count($this->_indexes))
		{
			return '';
		}
		
		return $this->_indexes[$count-1];
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_iteration($atts)
	{
		extract($this->gatts(self::$_default_iter ,$atts));
		$this->pushIter(compact(array_keys(self::$_default_iter)));
		return true;
	}
	
	protected function _xtag_core_iteration($atts)
	{
		$this->popIter();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_cache($atts)
	{
		if (!$this->cacher)
		{
			return true;
		}

		extract($this->gatts(array(
			'id' => '',
			'global' => false,
			'timeout' => NULL,
		),$atts));

		if (empty($id))
		{
			$id = '_idx_' . $this->_cacheID++;
		}
		
		if (!$this->truthy($global))
		{
			$id = rtrim($this->pageURI(), '/') . '/' . $id;
		}
		
		if (($content = $this->cacher->get($id)) === false)
		{
			$content = $this->getContent();
			if ($this->debug_level > 2)
			{
				$this->cacher->set($id, "<!-- begin served from cache -->" . $content . "<!-- end served from cache -->", $timeout);
			}
			else
			{
				$this->cacher->set($id, $content, $timeout);
			}
		}
		
		return $content;
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_core_block($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'name' => '',
			'default' => false,
		),$atts));

		$id || $name || check($id || $name, $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'block')));

		if (($block = $this->content->fetchBlock($id ? intval($id) : $name)) === false)
		{
			if ($default !== false)
			{
				return $default;
			}
			else
			{
				$this->reportError(self::$lang->get('block_not_found', $id ? $id : $name), E_USER_WARNING);
				return '';
			}
		}

		return $this->parseBlock($block);
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_core_image($atts)
	{
		!empty($atts['id']) || !empty($atts['name']) || check($atts['id'] || $atts['name'], $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'image')));

		if (isset($atts['name']))
		{
			$nameOrID = $atts['name'];
			unset($atts['name']);
		}
		else
		{
			$nameOrID = intval($atts['id']);
			unset($atts['id']);
		}
		
		if (!$image = $this->content->fetchImage($nameOrID, NULL, 1, false))
		{
			$this->reportError(self::$lang->get('image_not_found', $nameOrID), E_USER_WARNING);
			return '';
		}
		
		if (!isset($atts['alt'])) { $atts['alt'] = $image->alt; };
		if (!isset($atts['title'])) { $atts['title'] = $image->title; };
		if (!isset($atts['height'])) { $atts['height'] = $image->height; };
		if (!isset($atts['width'])) { $atts['width'] = $image->width; };
		
		if ($image->url == '')
		{
			if (!empty($this->prefs['auto_versioned_images']))
			{
				$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$image->rev}$2", $image->slug);
			}
			else
			{
				$filename = $image->slug;
			}
			$image->url = $this->siteURL(false) . $this->prefs['content_image_path'] . '/'  . $filename;
		}

		$atts['src'] = $image->url;

		$atts = $this->matts($atts);
		$out = $this->output->tag(NULL, 'img', '', '', $atts) . "\n";

		return rtrim($out, "\n");
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_core_file($atts)
	{
		!empty($atts['id']) || !empty($atts['name']) || check($atts['id'] || $atts['name'], $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'file')));

		if (isset($atts['name']))
		{
			$nameOrID = $atts['name'];
			unset($atts['name']);
		}
		else
		{
			$nameOrID = intval($atts['id']);
			unset($atts['id']);
		}
		
		if (!$file = $this->content->fetchFile($nameOrID, false))
		{
			$this->reportError(self::$lang->get('file_not_found', $nameOrID), E_USER_WARNING);
			return '';
		}
		
		if (!isset($atts['title'])) { $atts['title'] = $this->output->escape($file->title); };

		if ($file->url == '')
		{
			if (!empty($this->prefs['auto_versioned_files']))
			{
				$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$file->rev}$2", $file->slug);
			}
			else
			{
				$filename = $file->slug;
			}
			$file->url = $this->siteURL(false) . $this->prefs['content_file_path'] . '/'  . $filename;
		}
				
		$atts['href'] = $file->url;

		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($file->title);

		$out = $this->output->tag($content, 'a', '', '', $atts) . "\n";

		return rtrim($out, "\n");
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_core_link($atts)
	{
		!empty($atts['id']) || !empty($atts['name']) || check($atts['id'] || $atts['name'], $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'link')));

		if (isset($atts['name']))
		{
			$nameOrID = $atts['name'];
			unset($atts['name']);
		}
		else
		{
			$nameOrID = intval($atts['id']);
			unset($atts['id']);
		}
		
		$link = $this->content->fetchLink($nameOrID);
		if (empty($link))
		{
			$this->dup($this->_link_stack);
			$this->reportError(self::$lang->get('link_not_found', $nameOrID), E_USER_WARNING);
			return false;
		}
		
		$this->pushLink($link);
		
		if (!isset($atts['title'])) { $atts['title'] = $this->output->escape($link->title); };
		$atts['href'] = $link->url;

		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($link->title);

		$out = $this->output->tag($content, 'a', '', '', $atts) . "\n";

		return rtrim($out, "\n");
	}
	
	protected function _xtag_core_link($atts)
	{
		$this->popLink();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_base_url($atts)
	{
		extract($this->gatts(array(
			'full' => true,
			'secure' => false,
		),$atts));

		return $this->baseURL(!$this->falsy($full), $this->truthy($secure));
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_admin_url($atts)
	{
		return $this->prefs['admin_url'];
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_site_url($atts)
	{
		extract($this->gatts(array(
			'full' => true,
			'secure' => false,
		),$atts));

		$url = $this->siteURL(!$this->falsy($full), $this->truthy($secure));
		return empty($url) ? '/' : $url;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_site_name($atts)
	{
		return $this->output->escape($this->getPref('site_name'));
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_site_slogan($atts)
	{
		return $this->output->escape($this->getPref('site_slogan'));
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_magic($atts)
	{
		extract($this->gatts(array(
			'id' => '1',
		),$atts));

		--$id;
		
		$magic = $this->_current_page->magic;
		if (is_array($magic))
		{
			return isset($magic[$id]) ? $this->output->escape($magic[$id]) : '';
		}

		return '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_count_magic($atts)
	{
		$magic = $this->_current_page->magic;

		if (is_array($magic))
		{
			return count($magic);
		}

		return 0;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_send_email($atts)
	{
		extract($this->gatts(array(
			'from'       => '',
			'fromname'   => '',
			'to'         => '',
			'toname'     => '',
			'subject'    => '',
			'body'       => '',
			'altbody'    => '',
			'html'       => true,
		),$atts));
		
		$from || check($from, $this->output->escape(self::$lang->get('attribute_required', 'from', 'if_send_email')));
		$to || check($to, $this->output->escape(self::$lang->get('attribute_required', 'to', 'if_send_email')));
		$subject || check($subject, $this->output->escape(self::$lang->get('attribute_required', 'subject', 'if_send_email')));

		if ($contentIsBody = !$body)
		{
			$body = $this->getContent();
		}
		else
		{
			if (SparkUtil::valid_int($body))
			{
				$this->reportError(self::$lang->get('fetch_by_id_deprecated', 'snippet', $name), E_USER_WARNING);
			}
			$body = $this->parseSnippet($body);
		}

		try
		{
			$mailer = $this->factory->manufacture('SparkMailer');
			$mailer->isHTML($this->truthy($html))->sender($from)->from($from)->fromName($fromname)->addAddress($to, $toname);
			$mailer->subject($subject)->body($body);
			if (!empty($altbody))
			{
				$mailer->altBody($altbody);
			}
			$mailer->send();
		}
		catch (Exception $e)
		{
			$this->setErrorMessage($e->getMessage());
			return false;
		}

		// if the content was used as the email body, return an empty string on success, otherwise true

		return $contentIsBody ? '' : true;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_breadcrumbs($atts)
	{
		extract($this->gatts(array(
			'separator' => '',
			'aslinks' => true,
			'id' => '',
			'class' => '',
			'withmagic' => false,
		),$atts,false));

		unset($atts['separator']);
		unset($atts['aslinks']);
		unset($atts['id']);
		unset($atts['class']);
		unset($atts['withmagic']);
		
		$aslinks = !$this->falsy($aslinks);
		$separator = $this->output->escape($separator);
		$isCategoryPage = $this->isCategoryPage();

		$breadcrumbs = array();

		$first = true;
		$last = '';
		for ($page = $this->currentPageContext(); $page != NULL; $page = $page->parent())
		{
			if ($page->isHidden())
			{
					continue;		// skip hidden pages
			}
			if ($page->virtual)
			{
				if ($isCategoryPage || !$this->truthy($withmagic) || empty($page->breadcrumb) || ($page->breadcrumb == $last))
				{
					continue;		// skip empty or duplicate breadcrumb in virtual pages
				}
			}
			$next = $this->output->escape($last = !empty($page->breadcrumb) ? $page->breadcrumb : $page->title);
			if ($first)
			{
				$first = false;
			}
			elseif ($aslinks)
			{
				$atts['href'] = $this->pageURL(true, $page);
				$matts = $this->matts($atts);
				$next = $this->output->tag($next, 'a', '', '', $matts);
			}
			$breadcrumbs[] = $next;
		}
	
		// special case: breadcrumbs for category page
		
		if ($isCategoryPage)
		{
			$magic = $this->_current_page->magic;
			if (is_array($magic))
			{
				$this->content->cacheCategoryChain(array('uri'=>implode('/', $this->_current_page->magic)));
				
				$catSlug = $magic[count($magic)-1];
				for ($category = $this->content->fetchCategory($catSlug); $category != NULL;)
				{
					$next = $this->output->escape($last = $category->title);

					if ($aslinks)
					{
						$atts['href'] = $this->categoryURL(true, $category);
						$matts = $this->matts($atts);
						$next = $this->output->tag($next, 'a', '', '', $matts);
					}
					
					$breadcrumbs[] = $next;
					
					if (($parent = $category->parent()) || !$category->parent_id)
					{
						$category = $parent;
					}
					else
					{
						$category = $this->content->fetchCategory(intval($category->parent_id));
					}
				}
			}
		}
		
		if (!empty($id))
		{
			$id = ' id="'.$id.'"';
		}
		
		if (!empty($class))
		{
			$class = ' class="'.$class.'"';
		}
		
		return "<ol{$id}{$class}><li>".implode($separator.'</li><li>', array_reverse($breadcrumbs)).'</li></ol>';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_category($atts)
	{
		// return name/title/link of current category on a category listing page

		extract($this->gatts(array(
			'title' => false,
			'link' => false,
		),$atts));

		$magic = $this->_current_page->magic;
		if (is_array($magic) && !empty($magic))
		{
			$name = $magic[count($magic)-1];
		}

		if (!isset($name) || !$category = $this->content->fetchCategory($name))
		{
			return '';
		}
		
		if ($this->hasContent() || $this->truthy($link))
		{
			unset($atts['link']);
			$atts['href'] = $this->categoryURL(false, $category);
			$atts = $this->matts($atts);
			$content = $this->hasContent() ? $this->getContent() :  $this->output->escape($category->title);
			return $this->output->tag($content, 'a', '', '', $atts);
		}
		
		return $this->truthy($title) ? $this->output->escape($category->title) : $category->slug;
	}
	
	//---------------------------------------------------------------------------
	// Core Conditional Tags ("core" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_magic($atts)
	{
		extract($this->gatts(array(
			'id' => '1',
			'matches' => NULL,
		),$atts));

		--$id;
		
		if (!is_array($magic = $this->_current_page->magic))
		{
			return false;
		}
		elseif (isset($matches) && isset($magic[$id]))
		{
			return preg_match('@'.$matches.'@', $magic[$id]) ? true : false;
		}
		else
		{
			return isset($magic[$id]) ? true : false;
		}
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_core_if_pref($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'value' => NULL,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'if_pref')));

		if (!isset($this->prefs[$name]))
		{
			return false;
		}
		
		return ($value === NULL) || ($value === $this->prefs[$name]);
	}

	//---------------------------------------------------------------------------

	protected function _tag_core_if_maintenance($atts)
	{
		return ($this->_production_status == EscherProductionStatus::Maintenance);
	}

	//---------------------------------------------------------------------------

	protected function _tag_core_if_development($atts)
	{
		return ($this->_production_status == EscherProductionStatus::Development);
	}

	//---------------------------------------------------------------------------

	protected function _tag_core_if_staging($atts)
	{
		return ($this->_production_status == EscherProductionStatus::Staging);
	}

	//---------------------------------------------------------------------------

	protected function _tag_core_if_production($atts)
	{
		return ($this->_production_status == EscherProductionStatus::Production);
	}

	//---------------------------------------------------------------------------

	protected function _tag_core_if_debug($atts)
	{
		extract($this->gatts(array(
			'level'  => '1',
		),$atts));
		
		return ($this->debug_level >= $level);
	}

	//---------------------------------------------------------------------------
	// Design Tags ("design" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_design()
	{
		$this->pushNamespace('design');
		return true;
	}
		
	protected function _xtag_ns_design()
	{
		$this->popNamespace('design');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_design_style($atts)
	{
		extract($this->gatts(array(
			'name'   => 'default',
			'media'  => 'screen',
			'rel'    => 'stylesheet',
			'type'   => 'text/css',
			'title'  => '',
		),$atts));
		
		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'style')));

		$out = '';
		foreach ($this->content->fetchStyleChain($name, $this->theme, $this->branch) as $styleInfo)
		{
			$themeComponent = empty($styleInfo['theme']) ? '' : '/' . $styleInfo['theme'];

			if ($styleInfo['url'] == '')
			{
				if (!empty($this->prefs['auto_versioned_styles']))
				{
					$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$styleInfo['rev']}$2", $name);
				}
				else
				{
					$filename = $name;
				}
				if (!empty($this->prefs['theme_path']))
				{
					$styleInfo['url'] = $this->siteURL(false) . $this->prefs['theme_path'] . $themeComponent . $this->prefs['style_path'] . '/' . $filename;
				}
				else
				{
					$styleInfo['url'] = $this->siteURL(false) . $this->prefs['style_path'] . $themeComponent . '/'  . $filename;
				}
			}

			$href = $styleInfo['url'];

			$atts = $this->matts(compact('rel', 'type', 'media', 'title', 'href'));
			$out .= $this->output->tag(NULL, 'link', '', '', $atts) . "\n";
		}
		return rtrim($out, "\n");
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_design_script($atts)
	{
		extract($this->gatts(array(
			'name'   => '',
			'type'   => 'text/javascript',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'script')));

		$out = '';
		foreach ($this->content->fetchScriptChain($name, $this->theme, $this->branch) as $scriptInfo)
		{
			$themeComponent = empty($scriptInfo['theme']) ? '' : '/' . $scriptInfo['theme'];

			if ($scriptInfo['url'] == '')
			{
				if (!empty($this->prefs['auto_versioned_scripts']))
				{
					$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$scriptInfo['rev']}$2", $name);
				}
				else
				{
					$filename = $name;
				}
				if (!empty($this->prefs['theme_path']))
				{
					$scriptInfo['url'] = $this->siteURL(false) . $this->prefs['theme_path'] . $themeComponent . $this->prefs['script_path'] . '/' . $filename;
				}
				else
				{
					$scriptInfo['url'] = $this->siteURL(false) . $this->prefs['script_path'] . $themeComponent . '/'  . $filename;
				}
			}

			$src = $scriptInfo['url'];
			
			$atts = $this->matts(compact('type', 'src'));
			$out .= $this->output->tag('', 'script', '', '', $atts) . "\n";
		}
		return rtrim($out, "\n");
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_design_image($atts)
	{
		!empty($atts['name']) || check($atts['name'], $this->output->escape(self::$lang->get('attribute_required', 'name', 'image')));
		
		if (SparkUtil::valid_int($atts['name']))
		{
			$this->reportError(self::$lang->get('fetch_by_id_deprecated', 'design image', $atts['name']), E_USER_WARNING);
		}

		$contentImageOverride = isset($atts['prefer_content']) && $this->truthy($atts['prefer_content']);
		unset($atts['prefer_content']);

		if (!$image = $this->content->fetchDesignImageByName($atts['name'], $this->theme ? $this->theme : 0, $this->branch, false, $contentImageOverride))
		{
			$this->reportError(self::$lang->get('image_not_found', $atts['name']), E_USER_WARNING);
			return '';
		}

		unset($atts['name']);

		if (!isset($atts['alt'])) { $atts['alt'] = $image->alt; };
		if (!isset($atts['title'])) { $atts['title'] = $image->title; };
		if (!isset($atts['height'])) { $atts['height'] = $image->height; };
		if (!isset($atts['width'])) { $atts['width'] = $image->width; };

		if (empty($image->url))
		{
			if (!empty($this->prefs['auto_versioned_images']))
			{
				$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$image->rev}$2", $image->slug);
			}
			else
			{
				$filename = $image->slug;
			}
			if ($image->theme_id == -1)				// we have a content image override
			{
				$image->url = $this->siteURL(false) . $this->prefs['content_image_path'] . '/'  . $filename;
			}
			else
			{
				$themeComponent = empty($image->theme) ? '' : '/' . $image->theme;

				if (!empty($this->prefs['theme_path']))
				{
					$image->url = $this->siteURL(false) . $this->prefs['theme_path'] . $themeComponent . $this->prefs['image_path'] . '/' . $filename;
				}
				else
				{
					$image->url = $this->siteURL(false) . $this->prefs['image_path'] . $themeComponent . '/'  . $filename;
				}
			}
		}

		$atts['src'] = $image->url;
		
		$atts = $this->matts($atts);
		$out = $this->output->tag(NULL, 'img', '', '', $atts) . "\n";

		return rtrim($out, "\n");
	}
		
	//---------------------------------------------------------------------------

	protected function _tag_design_snippet($atts)
	{
		!empty($atts['name']) || !empty($atts['id']) || check(!empty($atts['name']) || !empty($atts['id']), $this->output->escape(self::$lang->get('attribute_required', 'name|id', 'snippet')));

		$name = isset($atts['name']) ? $atts['name'] : '';

		if ($name === '')
		{
			$name = (int) $atts['id'];
		}
		
		if (SparkUtil::valid_int($name))
		{
			$this->reportError(self::$lang->get('fetch_by_id_deprecated', 'snippet', $name), E_USER_WARNING);
		}
		
		unset($atts['name']);
		unset($atts['id']);

		$this->_yield_stack[] = $this->getContent();
		$out = $this->parseSnippet($name, $atts);
		array_pop($this->_yield_stack);
		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_design_param($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'default' => false,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'param')));

		$params = end($this->_param_stack);

		if (!isset($params[$name]))
		{
			if ($default !== false)
			{
				return $default;
			}
			else
			{
				$this->reportError(self::$lang->get('undefined_parameter', $name), E_USER_WARNING);
				return '';
			}
		}
		
		return $params[$name];
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_design_yield($atts)
	{
		return ($y = end($this->_yield_stack)) !== NULL ? $y : '';
	}
	
	//---------------------------------------------------------------------------
	// Meta Helpers
	//---------------------------------------------------------------------------

	final protected function meta($atts, $meta)
	{
		extract($this->gatts(array(
			'name' => '',
			'default' => '',
			'tag' => true,
		),$atts));

		$out = isset($meta[$name]) ? $this->output->escape($meta[$name]) : $default;
		
		if ($out !== '' && !$this->falsy($tag))
		{
			$atts = $this->matts(array('name'=>$name, 'content'=>$out));
			$out = $this->output->tag(NULL, 'meta', '', '', $atts);
		}
		
		return $out;
	}

	//---------------------------------------------------------------------------
	// Pages Tags ("pages" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_pages()
	{
		$this->pushNamespace('pages');
		return true;
	}
		
	protected function _xtag_ns_pages()
	{
		$this->popNamespace('pages');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_id($atts)
	{
		return $this->output->escape($this->currentPageContext()->id);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_slug($atts)
	{
		extract($this->gatts(array(
			'level' => false,
			'default' => false,
		),$atts));
		
		$page = $this->currentPageContext();

		if ($level !== false)
		{
			while ($page->level != $level)
			{
				if (!$parent = $page->parent())
				{
					if ($default !== false)
					{
						return $default;
					}
					else
					{
						$this->reportError(self::$lang->get('page_level_not_found', $level), E_USER_WARNING);
						return '';
					}
				}
				$page = $parent;
			}
		}

		return $page->slug;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_title($atts)
	{
		return $this->output->escape($this->currentPageContext()->title);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_breadcrumb($atts)
	{
		return $this->output->escape($this->currentPageContext()->breadcrumb);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_meta($atts)
	{
		!empty($atts['name']) || check(!empty($atts['name']), $this->output->escape(self::$lang->get('attribute_required', 'name', 'pages_meta')));

		return $this->meta($atts, $this->content->fetchPageMeta($this->currentPageContext()));
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_level($atts)
	{
		return $this->currentPageContext()->level;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_position($atts)
	{
		return $this->currentPageContext()->position;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_url($atts)
	{
		extract($this->gatts(array(
			'full' => true,
		),$atts));

		return $this->pageURL($this->truthy($full));
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_status($atts)
	{
		return $this->output->escape($this->currentPageContext()->statusText());
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_anchor($atts)
	{
		// Difference between this tag and pagelink is that this tag always uses
		// the current page context and will pass any id attribute through to the
		// underlying <a> tag, while pagelink will use a provided id attribute to
		// look up the page by ID.
		
		$page = $this->currentPageContext();

		$url = rtrim($this->pageURL(true, $page), '/');

		if (isset($atts['hash']))
		{
			$url .= '#' . $atts['hash'];
			unset($atts['hash']);
		}
		
		if (isset($atts['magic']))
		{
			$url .= $atts['magic'];
			unset($atts['magic']);
		}
		
		if (isset($atts['qs']))
		{
			$url .= '?' . $atts['qs'];
			unset($atts['qs']);
		}
		
		$atts['href'] = $url;
		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($page->title);

		return $this->output->tag($content, 'a', '', '', $atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_pagelink($atts)
	{
		if (!isset($atts['id']))
		{
			$page = $this->currentPageContext();
		}
		
		else	// look up page by id
		{
			$id = $atts['id'];
			unset($atts['id']);
			
			if (!$page = $this->content->fetchPageByID(intval($id)))
			{
				$this->reportError(self::$lang->get('page_not_found', $id), E_USER_WARNING);
				return false;
			}
		}

		$url = rtrim($this->pageURL(true, $page), '/');

		if (isset($atts['hash']))
		{
			$url .= '#' . $atts['hash'];
			unset($atts['hash']);
		}
		
		if (isset($atts['magic']))
		{
			$url .= $atts['magic'];
			unset($atts['magic']);
		}
		
		if (isset($atts['qs']))
		{
			$url .= '?' . $atts['qs'];
			unset($atts['qs']);
		}
		
		$atts['href'] = $url;
		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($page->title);

		return $this->output->tag($content, 'a', '', '', $atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_permurl(&$atts)
	{
		extract($this->gatts(array(
			'withtitle' => $this->getPref('permlink_titles', false) ? true : false,
		),$atts, false));

		$withtitle = $this->truthy($withtitle);

		$page = $this->currentPageContext();
		$isCategoryPage = $this->isCategoryPage();

		$title = $page->title;
		$url = $this->siteURL();
		
		if (!$isCategoryPage && $page->virtual)			// virtual pages don't have reliable permlinks!
		{
			$url .= $page->uri();
		}
		
		else
		{
			$url .=  '/' . $page->id;
			
			if ($isCategoryPage)
			{		
				$magic = $this->_current_page->magic;
				if (is_array($magic))
				{
					$name = $magic[count($magic)-1];
				}
		
				if (isset($name) && $category = $this->content->fetchCategory($name))
				{
					$title = $category->title;
					$url .= '/' . $category->id;
					
					if ($withtitle)
					{
						$categoryRootPage = $this->findNonVirtualParent($page);
						$pageSlug = $categoryRootPage ? $categoryRootPage->slug : $this->category_trigger;
						$url .= '/' . $categoryRootPage->slug . '/' . $category->slug;
						$withtitle = false;
					}
				}
			}
	
			if ($withtitle && !empty($page->slug))
			{
				$url .= '/' . $page->slug;
			}
		}

		if (isset($atts['hash']))
		{
			$url .= '#' . $atts['hash'];
		}

		if (!isset($atts['title']))
		{
			$atts['title'] = $title;
		}
	
		return $url;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_permlink($atts)
	{
		$url = $this->_tag_pages_permurl($atts);
		$title = $atts['title'];

		unset($atts['withtitle']);
		unset($atts['hash']);
		unset($atts['title']);

		$atts['href'] = $url;
		$atts = $this->matts($atts);

		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($title);

		return $this->output->tag($content, 'a', '', '', $atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_content($atts)
	{
		extract($this->gatts(array(
			'part' => 'body',
			'inherit' => false,
			'default' => false,
		),$atts));

		if (($part = $this->content->fetchPagePart($this->currentPageContext(), $partName = $part, $this->truthy($inherit))) === false)
		{
			if ($default !== false)
			{
				return $default;
			}
			else
			{
				$this->reportError(self::$lang->get('page_part_not_found', $partName), E_USER_WARNING);
				return '';
			}
		}
		
		return $this->parsePart($part);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_excerpt($atts)
	{
		extract($this->gatts(array(
			'maxchars' => '500',
			'endcap' => '…',
		),$atts));

		$page = $this->currentPageContext();
		
		// try for excerpt or summary part

		foreach (array('excerpt', 'summary') as $name)
		{
			if (($part = $this->content->fetchPagePart($page, $name, false)) !== false)
			{
				return $this->parsePart($part);
			}
		}
		
		// fall back to body part
		
		if (($part = $this->content->fetchPagePart($page, 'body', false)) !== false)
		{
			if ($maxchars == 0)	// no limit, so just return entire body part
			{
				return $this->parsePart($part);
			}
			
			// no 'excerpt' or 'summary' part, so generate one on the fly (with markup stripped)
		
			return SparkUtil::truncate(strip_tags($this->parsePart($part)), $maxchars, false, $endcap);
		}
		
		return '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_date($atts)
	{
		extract($this->gatts(array(
			'format' => '%A, %B %d, %Y',
			'for' => 'published',
		),$atts));

		return $this->output->escape($this->currentPageContext()->getDate($for, $format));
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_author($atts)
	{
		$this->content->fetchPageAuthor($page = $this->currentPageContext());
		return $page->author_name ? $this->output->escape($page->author_name) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_editor($atts)
	{
		$this->content->fetchPageEditor($page = $this->currentPageContext());
		return $page->editor_name ? $this->output->escape($page->editor_name) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_categories_each($atts)
	{
		extract($this->gatts(array(
			'parent' => '',
			'include' => '',
			'exclude' => '',
			'sort' => '',
			'order' => 'asc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		if (is_numeric($parent))
		{
			$parent = intval($parent);
		}

		if ($categories = $this->content->fetchCategories($parent, $include, $exclude, $sort, $order, array('page'=>$this->currentPageContext()->id)))
		{
			$content = $this->getParsable();
			
			$numCategories = count($categories);
			$whichCategory = 0;
			foreach ($categories as $category)
			{
				++$whichCategory;
				$category->isFirst = ($whichCategory == 1);
				$category->isLast = ($whichCategory == $numCategories);

				$this->pushCategory($category);
				$out .= $this->parseParsable($content);
				$this->popCategory();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_find($atts)
	{
		extract($this->gatts(array(
			'url' => '',
		),$atts));
		
		$url || check($url, $this->output->escape(self::$lang->get('attribute_required', 'url', 'find')));
		
		if (!$page = $this->content->fetchPageByURI($url))
		{
			$this->dup($this->_context_stack);
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_pages_find($atts)
	{
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_self($atts)
	{
		$this->pushPageContext($this->_current_page);
		return true;
	}

	protected function _xtag_pages_self($atts)
	{
		$this->popPageContext();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_parent($atts)
	{
		if (!$page = $this->pageParent($curPage = $this->currentPageContext()))
		{
			$this->dup($this->_context_stack);
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_pages_parent($atts)
	{
		$this->popPageContext();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_grand_parent($atts)
	{
		if ($page = $this->pageParent($curPage = $this->currentPageContext()))
		{
			$page = $this->pageParent($page);
		}
		if (!$page)
		{
			$this->dup($this->_context_stack);
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_pages_grand_parent($atts)
	{
		$this->popPageContext();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_great_grand_parent($atts)
	{
		if ($page = $this->pageParent($curPage = $this->currentPageContext()))
		{
			if ($page = $this->pageParent($page))
			{
				$page = $this->pageParent($page);
			}
		}
		if (!$page)
		{
			$this->dup($this->_context_stack);
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_pages_great_grand_parent($atts)
	{
		$this->popPageContext();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_count($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		return $this->content->countPages(NULL, $id, $category, $status, NULL, NULL, $limit, $offset);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_if_any($atts)
	{
		return ($this->_tag_pages_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_if_any_before($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$atts['limit'] = 1;
		$atts['start'] = 1;
		return ($offset > 0) && ($this->_tag_pages_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_if_any_after($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$atts['start'] = $start + 1;
		return ($limit > 0) && ($this->_tag_pages_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_first($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$pages = $this->content->fetchPages(NULL, $id, $category, $status, NULL, NULL, 1, $offset, $sort, $order);

		if (empty($pages) || !$page = $pages[0])
		{
			$this->dup($this->_context_stack);
			$this->dup($this->_page_stack);
			$this->reportError(self::$lang->get('page_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirst = true;
		$page->isLast = false;
		$this->pushPageContext($page);
		$this->pushPage($page);
		return true;
	}

	protected function _xtag_pages_first($atts)
	{
		$this->popPage();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_last($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$pages = $this->content->fetchPages(NULL, $id, $category, $status, NULL, NULL, $limit, $offset, $sort, $order);

		if (empty($pages) || !$page = $pages[count($pages)-1])
		{
			$this->dup($this->_context_stack);
			$this->dup($this->_page_stack);
			$this->reportError(self::$lang->get('page_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirst = false;
		$page->isLast = true;
		$this->pushPageContext($page);
		$this->pushPage($page);
		return true;
	}

	protected function _xtag_pages_last($atts)
	{
		$this->popPage();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_each($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		$offset = (max(1, $start) - 1) * $limit;
		if ($pages = $this->content->fetchPages(NULL, $id, $category, $status, NULL, NULL, $limit, $offset, $sort, $order))
		{
			$content = $this->getParsable();
			
			$numPages = count($pages);
			$whichPage = 0;
			foreach ($pages as $page)
			{
				++$whichPage;
				$page->isFirst = ($whichPage == 1);
				$page->isLast = ($whichPage == $numPages);

				$this->pushPageContext($page);
				$this->pushPage($page);
				$out .= $this->parseParsable($content);
				$this->popPage();
				$this->popPageContext();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_page($atts)
	{
		extract($this->gatts(array(
			'id' => '',
		),$atts));

		if (!$page = ($id ? $this->content->fetchPageByID(intval($id)) :  $this->currentPage()))
		{
			$this->dup($this->_context_stack);
			$this->reportError(self::$lang->get('page_not_found', $id), E_USER_WARNING);
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_pages_page($atts)
	{
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_if_first($atts)
	{
		return ($page = $this->currentPage()) ? $page->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_if_last($atts)
	{
		return ($page = $this->currentPage()) ? $page->isLast : false;
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_pages_if_page($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'slug' => '',
			'url' => '',
			'type' => '',
		),$atts));

		$id || $slug || $url || $type || check($id || $slug || $url || $type, $this->output->escape(self::$lang->get('attribute_required', 'id|slug|url|type', 'if_page')));

		$curPage = $this->currentPageContext();
		
		if ($id)
		{
			PublishContentModel::makeList($id);
			return (in_array($curPage->id, $id));
		}
		elseif ($slug)
		{
			$slug = array_map(array($this, 'rtrimslash'), PublishContentModel::makeList($slug));
			return (in_array($curPage->slug, $slug));
		}
		elseif ($url)
		{
			PublishContentModel::makeList($url);
			return (in_array($this->pageURI($curPage), $url));
		}
		elseif ($type)
		{
			switch ($type)
			{
				case 'category':
					return $this->isCategoryPage();
			}
		}
		return false;
	}

	//---------------------------------------------------------------------------

	protected function _tag_pages_if_here($atts)
	{
		return $this->currentPageContext()->id == $this->_current_page->id;
	}

	//---------------------------------------------------------------------------

	protected function _tag_pages_if_selected($atts)
	{
		$curPage = $this->currentPageContext();
		
		if ($curPage->id == $this->_current_page->id)
		{
			return true;
		}
		
		// check page lineage

		for ($page = $this->_current_page; $page !== NULL; $page = $page->parent())
		{
			if ($curPage->id == $page->id)
			{
				return true;
			}
		}

		return false;
	}

	//---------------------------------------------------------------------------

	protected function _tag_pages_if_parent($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'slug' => '',
		),$atts));

		$curPage = $this->currentPageContext();
		
		if (!$curPage->parent_id)
		{
			return false;
		}
		
		if ($id)
		{
			PublishContentModel::makeList($id);
			return (in_array($curPage->parent_id, $id));
		}
		
		if ($slug)
		{
			$slug = array_map(array($this, 'rtrimslash'), PublishContentModel::makeList($slug));
			return (in_array($curPage->parent()->slug, $slug));
		}
		
		return true;
	}

	//---------------------------------------------------------------------------

	protected function _tag_pages_if_children($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published',
		),$atts));

		return ($this->content->countPages($this->currentPageContext(), NULL, $category, $status) > 0);
	}

	//---------------------------------------------------------------------------

	protected function _tag_pages_if_siblings($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published',
		),$atts));

		return ($this->content->countPages($this->currentPageContext()->parent(), NULL, $category, $status) > 1);
	}

	//---------------------------------------------------------------------------

	protected function _tag_pages_if_content($atts)
	{
		extract($this->gatts(array(
			'part' => 'body',
			'inherit' => false,
			'find' => '',
		),$atts));

		return $this->content->pageHasParts($this->currentPageContext(), $part, $this->truthy($inherit), $find === 'any');
	}

	//---------------------------------------------------------------------------

	protected function _tag_pages_if_url($atts)
	{
		extract($this->gatts(array(
			'matches' => '',
		),$atts));

		$matches || check($matches, $this->output->escape(self::$lang->get('attribute_required', 'matches', 'if_url')));

		return preg_match('@'.$matches.'@', $this->pageURI()) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_pages_if_category($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'name' => '',
		),$atts));

		// can be used on normal page or on category listing page

		if ($this->isCategoryPage())
		{		
			$magic = $this->_current_page->magic;
			if (is_array($magic))
			{
				$magic = $magic[count($magic)-1];
				if ($id)
				{
					PublishContentModel::makeList($id);
					return (($category = $this->content->fetchCategory($magic)) && in_array($category->id, $id));
				}
				else
				{
					PublishContentModel::makeList($name);
					return (in_array($magic, $name) && ($category = $this->content->fetchCategory($magic)));
				}
			}
			return false;
		}

		return $this->content->pageHasCategories($this->currentPageContext(), $name);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_pages_if_category_list($atts)
	{
		return $this->isCategoryPage();
	}
	
	//---------------------------------------------------------------------------
	// Children Tags ("children" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_children()
	{
		$this->pushNamespace('children');
		return true;
	}
		
	protected function _xtag_ns_children()
	{
		$this->popNamespace('children');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_children_count($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		return $this->content->countPages($this->currentPageContext(), NULL, $category, $status, NULL, NULL, $limit, $offset);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_if_any($atts)
	{
		return ($this->_tag_children_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_if_any_before($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$atts['limit'] = 1;
		$atts['start'] = 1;
		return ($offset > 0) && ($this->_tag_children_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_if_any_after($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$atts['start'] = $start + 1;
		return ($limit > 0) && ($this->_tag_children_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_first($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$pages = $this->content->fetchPages($this->currentPageContext(), NULL, $category, $status, NULL, NULL, 1, $offset, $sort, $order);

		if (empty($pages) || !$page = $pages[0])
		{
			$this->dup($this->_context_stack);
			$this->dup($this->_child_stack);
			$this->reportError(self::$lang->get('child_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirst = true;
		$page->isLast = false;
		$this->pushPageContext($page);
		$this->pushChild($page);
		return true;
	}

	protected function _xtag_children_first($atts)
	{
		$this->popChild();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_last($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$pages = $this->content->fetchPages($this->currentPageContext(), NULL, $category, $status, NULL, NULL, $limit, $offset, $sort, $order);

		if (empty($pages) || !$page = $pages[count($pages)-1])
		{
			$this->dup($this->_context_stack);
			$this->dup($this->_child_stack);
			$this->reportError(self::$lang->get('child_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirst = false;
		$page->isLast = true;
		$this->pushPageContext($page);
		$this->pushChild($page);
		return true;
	}

	protected function _xtag_children_last($atts)
	{
		$this->popChild();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_each($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		$offset = (max(1, $start) - 1) * $limit;
		if ($pages = $this->content->fetchPages($this->currentPageContext(), NULL, $category, $status, NULL, NULL, $limit, $offset, $sort, $order))
		{
			$content = $this->getParsable();
			
			$numPages = count($pages);
			$whichPage = 0;
			foreach ($pages as $page)
			{
				++$whichPage;
				$page->isFirst = ($whichPage == 1);
				$page->isLast = ($whichPage == $numPages);

				$this->pushPageContext($page);
				$this->pushChild($page);
				$out .= $this->parseParsable($content);
				$this->popChild();
				$this->popPageContext();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_child($atts)
	{
		if (!$page = $this->currentChild())
		{
			$this->dup($this->_context_stack);
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_children_child($atts)
	{
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_if_first($atts)
	{
		return ($page = $this->currentChild()) ? $page->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_children_if_last($atts)
	{
		return ($page = $this->currentChild()) ? $page->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	// Siblings Tags ("siblings" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_siblings()
	{
		$this->pushNamespace('siblings');
		return true;
	}
		
	protected function _xtag_ns_siblings()
	{
		$this->popNamespace('siblings');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_count($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$count =  $this->content->countPages($this->currentPageContext()->parent(), NULL, $category, $status, NULL, NULL, $limit, $offset) - 1;
		return max(0, $count);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_if_any($atts)
	{
		return ($this->_tag_siblings_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_if_any_before($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$atts['limit'] = 1;
		$atts['start'] = 1;
		return ($offset > 0) && ($this->_tag_siblings_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_if_any_after($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$atts['start'] = $start + 1;
		return ($limit > 0) && ($this->_tag_siblings_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_previous($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published',
		),$atts));

		$pages = $this->content->fetchPageSiblings($this->currentPageContext(), $category, $status, 1, 0, NULL, NULL, PublishContentModel::siblings_before);

		if (empty($pages) || !$page = $pages[0])
		{
			$this->dup($this->_context_stack);
			$this->dup($this->_sibling_stack);
			$this->reportError(self::$lang->get('sibling_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirst = true;
		$page->isLast = true;
		$this->pushPageContext($page);
		$this->pushSibling($page);
		return true;
	}

	protected function _xtag_siblings_previous($atts)
	{
		$this->popSibling();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_next($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published',
		),$atts));

		$pages = $this->content->fetchPageSiblings($this->currentPageContext(), $category, $status, 1, 0, NULL, NULL, PublishContentModel::siblings_after);

		if (empty($pages) || !$page = $pages[count($pages)-1])
		{
			$this->dup($this->_context_stack);
			$this->dup($this->_sibling_stack);
			$this->reportError(self::$lang->get('sibling_not_found'), E_USER_WARNING);
			return false;
		}

		$page->isFirst = true;
		$page->isLast = true;
		$this->pushPageContext($page);
		$this->pushSibling($page);
		return true;
	}

	protected function _xtag_siblings_next($atts)
	{
		$this->popSibling();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_each($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => '',
			'order' => '',
			'which' => 'all',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';
		
		switch (strtolower($which))
		{
			case 'all':
				$which = PublishContentModel::siblings_all;
				break;
			case 'before':
				$which = PublishContentModel::siblings_before;
				break;
			case 'after':
				$which = PublishContentModel::siblings_after;
				break;
		}

		$offset = (max(1, $start) - 1) * $limit;
		if ($pages = $this->content->fetchPageSiblings($this->currentPageContext(), $category, $status, $limit, $offset, $sort, $order, $which))
		{
			$content = $this->getParsable();
			
			$numPages = count($pages);
			$whichPage = 0;
			foreach ($pages as $page)
			{
				++$whichPage;
				$page->isFirst = ($whichPage == 1);
				$page->isLast = ($whichPage == $numPages);

				$this->pushPageContext($page);
				$this->pushSibling($page);
				$out .= $this->parseParsable($content);
				$this->popSibling();
				$this->popPageContext();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_if_first($atts)
	{
		return ($page = $this->currentSibling()) ? $page->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_siblings_if_last($atts)
	{
		return ($page = $this->currentSibling()) ? $page->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	// Blocks Tags ("blocks" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_blocks()
	{
		$this->pushNamespace('blocks');
		return true;
	}
		
	protected function _xtag_ns_blocks()
	{
		$this->popNamespace('blocks');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_name($atts)
	{
		return ($block = $this->currentBlock()) ? $this->output->escape($block->name) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_title($atts)
	{
		return ($block = $this->currentBlock()) ? $this->output->escape($block->title) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_content($atts)
	{
		return ($block = $this->currentBlock()) ? $this->parseBlock($block) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_if_block($atts)
	{
		extract($this->gatts(array(
			'name' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'blocks:if_block')));

		return $this->content->blockExists($name);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_block($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'name' => '',
		),$atts));

		$id || $name || check($id || $name, $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'blocks:block')));

		if (!$block = $this->content->fetchBlock($handle = $id ? intval($id) : $name))
		{
			$this->dup($this->_block_stack);
			$this->reportError(self::$lang->get('block_not_found', $handle), E_USER_WARNING);
			return false;
		}

		$this->pushBlock($block);
		return true;
	}
	
	protected function _xtag_blocks_block($atts)
	{
		$this->popBlock();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_count($atts)
	{
		extract($this->gatts(array(
			'category' => '',
		),$atts));

		return $this->content->countBlocks($category);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_each($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'sort' => '',
			'order' => 'asc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		if ($blocks = $this->content->fetchBlocks($category, $sort, $order))
		{
			$content = $this->getParsable();
			
			$numBlocks = count($blocks);
			$whichBlock = 0;
			foreach ($blocks as $block)
			{
				++$whichBlock;
				$block->isFirst = ($whichBlock == 1);
				$block->isLast = ($whichBlock == $numBlocks);

				$this->pushBlock($block);
				$out .= $this->parseParsable($content);
				$this->popBlock();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_if_category($atts)
	{
		extract($this->gatts(array(
			'name' => '',
		),$atts));

		if ($block = $this->currentBlock())
		{
			return $this->content->blockHasCategories($block, $name);
		}
		
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_if_first($atts)
	{
		return ($block = $this->currentBlock()) ? $block->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_blocks_if_last($atts)
	{
		return ($block = $this->currentBlock()) ? $block->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	// Images Tags ("images" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_images()
	{
		$this->pushNamespace('images');
		return true;
	}
		
	protected function _xtag_ns_images()
	{
		$this->popNamespace('images');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_images_meta($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'default' => '',
		),$atts));

		$name !== '' || check($name !== '', $this->output->escape(self::$lang->get('attribute_required', 'name', 'images:meta')));

		if (!$image = $this->currentImage())
		{
			return '';
		}

		$meta = $this->content->fetchImageMeta($image);

		return isset($meta[$name]) ? $this->output->escape($meta[$name]) : $default;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_images_url($atts)
	{
		return ($image = $this->currentImage()) ? $this->output->escape($image->url) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_images_title($atts)
	{
		return ($image = $this->currentImage()) ? $this->output->escape($image->title) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_images_alt($atts)
	{
		return ($image = $this->currentImage()) ? $this->output->escape($image->alt) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_images_width($atts)
	{
		return ($image = $this->currentImage()) ? $this->output->escape($image->width) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_images_height($atts)
	{
		return ($image = $this->currentImage()) ? $this->output->escape($image->height) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_images_image($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'name' => '',
			'design' => false,
		),$atts));

		$id || $name || check($id || $name, $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'images:image')));

		$isContentImage = !$this->truthy($design);
		$theme = $isContentImage ? NULL : $this->theme;
		$branch = $isContentImage ? 1 : $this->branch;

		if (!$image = $this->content->fetchImage($handle = $id ? intval($id) : $name, $theme, $branch, false, $isContentImage))
		{
			$this->dup($this->_image_stack);
			$this->reportError(self::$lang->get('image_not_found', $handle), E_USER_WARNING);
			return false;
		}
		
		if ($image->url == '')
		{
			if (!empty($this->prefs['auto_versioned_images']))
			{
				$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$image->rev}$2", $image->slug);
			}
			else
			{
				$filename = $image->slug;
			}
			if (!$isContentImage)
			{
				$themeComponent = empty($image->theme) ? '' : '/' . $image->theme;
				if (!empty($this->prefs['theme_path']))
				{
					$image->url = $this->siteURL(false) . $this->prefs['theme_path'] . $themeComponent . $this->prefs['image_path'] . '/' . $filename;
				}
				else
				{
					$image->url = $this->siteURL(false) . $this->prefs['image_path'] . $themeComponent . '/'  . $filename;
				}
			}
			else
			{
				$image->url = $this->siteURL(false) . $this->prefs['content_image_path'] . '/'  . $filename;
			}
		}

		$this->pushImage($image);
		return true;
	}
	
	protected function _xtag_images_image($atts)
	{
		$this->popImage();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_images_count($atts)
	{
		extract($this->gatts(array(
			'category' => '',
		),$atts));

		return $this->content->countContentImages($category);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_images_each($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'sort' => '',
			'order' => 'asc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		if ($images = $this->content->fetchContentImages($category, $sort, $order, false))
		{
			$content = $this->getParsable();
			
			$numImages = count($images);
			$whichImage = 0;
			foreach ($images as $image)
			{
				++$whichImage;
				$image->isFirst = ($whichImage == 1);
				$image->isLast = ($whichImage == $numImages);

				if ($image->url == '')
				{
					if (!empty($this->prefs['auto_versioned_images']))
					{
						$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$image->rev}$2", $image->slug);
					}
					else
					{
						$filename = $image->slug;
					}
					$image->url = $this->siteURL(false) . $this->prefs['content_image_path'] . '/'  . $filename;
				}

				$this->pushImage($image);
				$out .= $this->parseParsable($content);
				$this->popImage();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_images_if_category($atts)
	{
		extract($this->gatts(array(
			'name' => '',
		),$atts));

		if ($image = $this->currentImage())
		{
			return $this->content->contentImageHasCategories($image, $name);
		}
		
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_images_if_first($atts)
	{
		return ($image = $this->currentImage()) ? $image->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_images_if_last($atts)
	{
		return ($image = $this->currentImage()) ? $image->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	// Files Tags ("files" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_files()
	{
		$this->pushNamespace('files');
		return true;
	}
		
	protected function _xtag_ns_files()
	{
		$this->popNamespace('files');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_files_meta($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'default' => '',
		),$atts));

		$name !== '' || check($name !== '', $this->output->escape(self::$lang->get('attribute_required', 'name', 'files:meta')));

		if (!$file = $this->currentfile())
		{
			return '';
		}

		$meta = $this->content->fetchfileMeta($file);

		return isset($meta[$name]) ? $this->output->escape($meta[$name]) : $default;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_name($atts)
	{
		return ($file = $this->currentFile()) ? $this->output->escape($file->slug) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_files_url($atts)
	{
		if (!$file = $this->currentFile())
		{
			return '';
		}
		
		return $this->output->escape($url);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_files_title($atts)
	{
		return ($file = $this->currentFile()) ? $this->output->escape($file->title) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_files_description($atts)
	{
		return ($file = $this->currentFile()) ? $this->output->escape($file->description) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_files_status($atts)
	{
		return ($file = $this->currentFile()) ? $this->output->escape($file->statusText()) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_files_size($atts)
	{
		static $formats = array('B','KB','MB','GB','TB','PB');
		
		extract($this->gatts(array(
			'format' => '',
		),$atts));
		
		$out = '';
	
		if (!(($file = $this->currentFile()) && ($fileSize = $file->size)))
		{
			return '';
		}

		if (!in_array($format, $formats))
		{
			for ($whichFormat = 0; $fileSize >= 1024; ++$whichFormat)
			{
				$fileSize /= 1024;
			}
			$whichFormat = min($whichFormat, count($formats)-1);
			$format = $formats[$whichFormat];
		}

		$fileSize = $file->size;
		
		switch ($format)
		{
			case 'PB':
				$fileSize /= 1024;
			case 'TB':
				$fileSize /= 1024;
			case 'GB':
				$fileSize /= 1024;
			case 'MB':
				$fileSize /= 1024;
			case 'KB':
				$fileSize /= 1024;
		}

		return number_format($fileSize, 2).$format;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_files_anchor($atts)
	{
		if (!$file = $this->currentFile())
		{
			return '';
		}

		if (!isset($atts['title'])) { $atts['title'] = $this->output->escape($file->title); };
		
		$atts['href'] = $file->url;

		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($file->title);

		$out = $this->output->tag($content, 'a', '', '', $atts) . "\n";

		return rtrim($out, "\n");
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_file($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'name' => '',
		),$atts));

		$id || $name || check($id || $name, $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'files:file')));

		if (!$file = $this->content->fetchFile($handle = $id ? intval($id) : $name))
		{
			$this->dup($this->_file_stack);
			$this->reportError(self::$lang->get('file_not_found', $handle), E_USER_WARNING);
			return false;
		}
		
		if ($file->url == '')
		{
			if (!empty($this->prefs['auto_versioned_files']))
			{
				$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$file->rev}$2", $file->slug);
			}
			else
			{
				$filename = $file->slug;
			}
			$file->url = $this->siteURL(false) . $this->prefs['content_file_path'] . '/'  . $filename;
		}
		
		$this->pushFile($file);
		return true;
	}
	
	protected function _xtag_files_file($atts)
	{
		$this->popFile();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_files_count($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		return $this->content->countFiles($id, $category, $status, $limit, $offset);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_if_any($atts)
	{
		return ($this->_tag_files_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_if_any_before($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$atts['limit'] = 1;
		$atts['start'] = 1;
		return ($offset > 0) && ($this->_tag_files_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_if_any_after($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$atts['start'] = $start + 1;
		return ($limit > 0) && ($this->_tag_files_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_each($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'id' => $curIter['id'],
			'category' => $curIter['category'],
			'status' => $curIter['status'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'sort' => 'created',
			'order' => 'desc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		$offset = (max(1, $start) - 1) * $limit;
		if ($files = $this->content->fetchFiles(false, $id, $category, $status, $limit, $offset, $sort, $order))
		{
			$content = $this->getParsable();
			
			$numFiles = count($files);
			$whichFile = 0;
			foreach ($files as $file)
			{
				++$whichFile;
				$file->isFirst = ($whichFile == 1);
				$file->isLast = ($whichFile == $numFiles);

				if ($file->url == '')
				{
					if (!empty($this->prefs['auto_versioned_files']))
					{
						$filename = preg_replace('/^(.*)(\..*)$/', "$1,{$file->rev}$2", $file->slug);
					}
					else
					{
						$filename = $file->slug;
					}
					$file->url = $this->siteURL(false) . $this->prefs['content_file_path'] . '/'  . $filename;
				}

				$this->pushFile($file);
				$out .= $this->parseParsable($content);
				$this->popFile();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_if_category($atts)
	{
		extract($this->gatts(array(
			'name' => '',
		),$atts));

		if ($file = $this->currentFile())
		{
			return $this->content->fileHasCategories($file, $name);
		}
		
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_if_first($atts)
	{
		return ($file = $this->currentFile()) ? $file->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_files_if_last($atts)
	{
		return ($file = $this->currentFile()) ? $file->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	// Links Tags ("links" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_links()
	{
		$this->pushNamespace('links');
		return true;
	}
		
	protected function _xtag_ns_links()
	{
		$this->popNamespace('links');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_links_meta($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'default' => '',
		),$atts));

		$name !== '' || check($name !== '', $this->output->escape(self::$lang->get('attribute_required', 'name', 'links:meta')));

		if (!$link = $this->currentlink())
		{
			return '';
		}

		$meta = $this->content->fetchlinkMeta($link);

		return isset($meta[$name]) ? $this->output->escape($meta[$name]) : $default;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_links_url($atts)
	{
		return ($link = $this->currentLink()) ? $link->url : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_links_name($atts)
	{
		return ($link = $this->currentLink()) ? $this->output->escape($link->name) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_links_title($atts)
	{
		return ($link = $this->currentLink()) ? $this->output->escape($link->title) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_links_description($atts)
	{
		return ($link = $this->currentLink()) ? $this->output->escape($link->description) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_links_anchor($atts)
	{
		if (!$link = $this->currentLink())
		{
			return '';
		}
		
		if (!isset($atts['title'])) { $atts['title'] = $this->output->escape($link->title); };

		$atts['href'] = $link->url;
		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() : $this->output->escape($link->title);

		return $this->output->tag($content, 'a', '', '', $atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_links_link($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'name' => '',
		),$atts));

		$id || $name || check($id || $name, $this->output->escape(self::$lang->get('attribute_required', 'id|name', 'links:link')));

		if (!$link = $this->content->fetchLink($handle = $id ? intval($id) : $name))
		{
			$this->dup($this->_link_stack);
			$this->reportError(self::$lang->get('link_not_found', $handle), E_USER_WARNING);
			return false;
		}

		$this->pushLink($link);
		return true;
	}
	
	protected function _xtag_links_link($atts)
	{
		$this->popLink();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_links_count($atts)
	{
		extract($this->gatts(array(
			'category' => '',
		),$atts));

		return $this->content->countLinks($category);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_links_each($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'sort' => '',
			'order' => 'asc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		if ($links = $this->content->fetchLinks($category, $sort, $order))
		{
			$content = $this->getParsable();
			
			$numLinks = count($links);
			$whichLink = 0;
			foreach ($links as $link)
			{
				++$whichLink;
				$link->isFirst = ($whichLink == 1);
				$link->isLast = ($whichLink == $numLinks);

				$this->pushLink($link);
				$out .= $this->parseParsable($content);
				$this->popLink();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_links_if_category($atts)
	{
		extract($this->gatts(array(
			'name' => '',
		),$atts));

		if ($link = $this->currentLink())
		{
			return $this->content->linkHasCategories($link, $name);
		}
		
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_links_if_first($atts)
	{
		return ($link = $this->currentLink()) ? $link->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_links_if_last($atts)
	{
		return ($link = $this->currentLink()) ? $link->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_links_if_here($atts)
	{
		if ($link = $this->currentLink())
		{
			$url = rtrim($link->url, ' /');
			$here = rtrim($this->pageURL(), ' /');
			return ($url === $here);
		}
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_links_if_selected($atts)
	{
		if ($link = $this->currentLink())
		{
			$url = rtrim($link->url, ' /') . '/';
			$here = rtrim($this->pageURL(), ' /') . '/';
			return strncmp($url, $here, strlen($url)) === 0;
		}
		return false;
	}
	
	//---------------------------------------------------------------------------
	// Categories Tags ("categories" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_categories()
	{
		$this->pushNamespace('categories');
		return true;
	}
		
	protected function _xtag_ns_categories()
	{
		$this->popNamespace('categories');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_categories_id($atts)
	{
		return ($category = $this->currentCategory()) ? $category->id : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_categories_name($atts)
	{
		return ($category = $this->currentCategory()) ? $category->slug : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_categories_title($atts)
	{
		return ($category = $this->currentCategory()) ? $this->output->escape($category->title) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_categories_used($atts)
	{
		return ($category = $this->currentCategory()) ? $this->output->escape($category->used): '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_categories_url($atts)
	{
		return ($category = $this->currentCategory()) ? $this->categoryURL(false, $category) : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_categories_anchor($atts)
	{
		if (!$category = $this->currentCategory())
		{
			return '';
		}
		
		$atts['href'] = $this->categoryURL(false, $category);
		$atts = $this->matts($atts);
		$content = $this->hasContent() ? $this->getContent() :  $this->output->escape($category->title);

		return $this->output->tag($content, 'a', '', '', $atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_categories_count($atts)
	{
		extract($this->gatts(array(
			'parent' => '',
			'recurse' => false,
		),$atts));

		if (is_numeric($parent))
		{
			$parent = intval($parent);
		}

		return $this->content->countCategories($parent, $this->truthy($recurse));
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_categories_category($atts)
	{
		extract($this->gatts(array(
			'id' => '',
			'name' => '',
			'validate' => false,
		),$atts));
		
		$category = NULL;

		// if on category listing page and no params, use the page's category

		if (empty($id) && empty($name))
		{
			if ($this->isCategoryPage())
			{
				$magic = $this->_current_page->magic;
				
				// Validate the URL by checking that the entire category hierarchy actually
				// exists as specified in URL. Do this to prevent DOS vulnerability when
				// page caching is enabled. Category page should always contain at least one
				// instance of the category tag with validate="true" to enable this functionality.
				
				if (!empty($magic))
				{
					if ($this->truthy($validate))
					{
						if (!$category = $this->content->cacheCategoryChain(array('uri'=>implode('/', $magic))))
						{
							$this->reportError(self::$lang->get('category_not_found', implode('>', $magic)), E_USER_WARNING);
							throw new SparkHTTPException_NotFound(NULL, array('reason'=>'category not found'));
						}
					}
					else
					{
						$name = $magic[count($magic)-1];
					}
				}
			}
		}
		
		if (!$category && ($handle = $id ? intval($id) : $name))
		{
			 $category = $this->content->fetchCategory($handle);
		}

		if ($category)
		{
			$this->pushCategory($category);
		}
		else
		{
			$this->dup($this->_category_stack);
			if ($handle)
			{
				$this->reportError(self::$lang->get('category_not_found', $handle), E_USER_WARNING);
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'category not found'));
			}
		}

		return true;
	}
	
	protected function _xtag_categories_category($atts)
	{
		$this->popCategory();
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_categories_each($atts)
	{
		extract($this->gatts(array(
			'parent' => '',
			'include' => '',
			'exclude' => '',
			'sort' => '',
			'order' => 'asc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';
		
		if (is_numeric($parent))
		{
			$parent = intval($parent);
		}

		if ($categories = $this->content->fetchCategories($parent, $include, $exclude, $sort, $order))
		{
			$content = $this->getParsable();
			
			$numCategories = count($categories);
			$whichCategory = 0;
			foreach ($categories as $category)
			{
				++$whichCategory;
				$category->isFirst = ($whichCategory == 1);
				$category->isLast = ($whichCategory == $numCategories);

				$this->pushCategory($category);
				$out .= $this->parseParsable($content);
				$this->popCategory();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_categories_children($atts)
	{
		return $this->_tag_categories_each(array('parent'=>$this->currentCategory()->id));
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_categories_if_first($atts)
	{
		return ($category = $this->currentCategory()) ? $category->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_categories_if_last($atts)
	{
		return ($category = $this->currentCategory()) ? $category->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_categories_if_here($atts)
	{
		if ($this->isCategoryPage())
		{
			if ($category = $this->currentCategory())
			{
				$url = rtrim($this->categoryURL(false, $category), ' /');
				$here = rtrim($this->pageURL(false, $this->currentPageContext()), ' /');
				return ($url === $here);
			}
		}
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_categories_if_selected($atts)
	{
		if ($this->isCategoryPage())
		{
			if ($category = $this->currentCategory())
			{
				$url = rtrim($this->categoryURL(false, $category), ' /');
				$here = rtrim($this->pageURL(false, $this->currentPageContext()), ' /');
				return strncmp($url, $here, strlen($url)) === 0;
			}
		}
		return false;
	}
			
	//---------------------------------------------------------------------------

	protected function _tag_categories_if_has_child($atts)
	{
		extract($this->gatts(array(
			'name' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'categories:if_has_child')));

		return $this->content->childCategoryExists($this->currentCategory()->id, $name);
	}

	//---------------------------------------------------------------------------
	
}
