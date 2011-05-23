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

require('user_objects.php');
require('content_objects.php');

//------------------------------------------------------------------------------

class _PublishContentModel extends SparkModel
{
	const siblings_all = 0;
	const siblings_before = 1;
	const siblings_after = 2;
	
	private static $_firstLoad;

	protected $_cache;
	protected $_category_trigger;
	
	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
		
		if (!isset(self::$_firstLoad))
		{
			self::$_firstLoad = true;
			$this->factory->loadClass('Page');
			
			require(escher_core_dir.'/publish/models/page_category.php');
			require(escher_core_dir.'/publish/models/page_theme.php');
			require(escher_core_dir.'/publish/models/page_style.php');
			require(escher_core_dir.'/publish/models/page_script.php');
			require(escher_core_dir.'/publish/models/page_image.php');
			require(escher_core_dir.'/publish/models/page_file.php');
		}
		
		$this->_cache = array();
		$this->_category_trigger = @$params['category_trigger'];
	}

	//---------------------------------------------------------------------------
	
	public static function makeList(&$options)
	{
		return $options = array_map('trim', is_array($options) ? $options : explode(',', strval($options)));
	}

	//---------------------------------------------------------------------------
	
	public function purgeCachePage($page)
	{
		$cache =& $this->_cache['page'];
		unset($cache[$page->id]);
		unset($cache[$page->uri()]);
	}

	//---------------------------------------------------------------------------
	//
	// Fetch an entire chain of categories from a parent to designated descendant.
	//
	// This is the workhorse category lookup function and it performs no caching.
	// Returns raw database rows (not category objects).
	//
	//---------------------------------------------------------------------------

	public function fetchCategoryChain($categorySpec)
	{
		$fields = $this->getCategoryFields('*');

		if ($searchByID = isset($categorySpec['id']))
		{
			$categoryID = $categorySpec['id'];
			$lastCategoryNum = $categorySpec['level'];
		}
		else
		{
			$slugs = $this->getSlugs($categorySpec['uri']);
			$lastCategoryNum = count($slugs) - 1;
		}

		$selectTemplate = $this->buildSelectTemplate('category', $fields);

		$db = $this->loadDB();
		
		$select[] = $this->bindTemplate($selectTemplate, 0);
		$joins = array();

		for ($categoryIndex = 1; $categoryIndex <= $lastCategoryNum; ++$categoryIndex)
		{
			$category = 'category' . $categoryIndex;
			$select[] = $this->bindTemplate($selectTemplate, $categoryIndex);

			$cond = array(array('leftField'=>'id', 'rightField'=>'parent_id', 'joinOp'=>'='));
			if (!$searchByID)
			{
				$cond[] = array('rightField'=>'slug', 'value'=>'?', 'joinOp'=>'=');
			}
			$joins[] = array('table'=>array('category', $category), 'conditions'=>$cond);
		}

		$where = 'category0.parent_id=0';
		if ($searchByID)
		{
			$where .= ' AND ' . (isset($category) ? $category : 'category0') . '.id=?';
		}
		else
		{
			$rootSlug = array_shift($slugs);
			$slugs[] = $rootSlug;
	 		$where .= ' AND category0.slug=?';
		}
		
		if (!$result = $db->selectJoinRow(array('category', 'category0'), implode(', ', $select), $joins, $where, $searchByID ? $categoryID : $slugs))
		{
			return array();
		}

		$rows = array();
		
		$uri = '';
		$parent = NULL;
		for ($categoryIndex = 0; $categoryIndex <= $lastCategoryNum; ++$categoryIndex)
		{
			$row =& $rows[$categoryIndex];
			foreach ($fields as $field)
			{
				$row[$field] = $result["_{$categoryIndex}_{$field}"];
			}
			$slug = $row['slug'];
			
			$uri .= '/' . $slug;
			$row['uri'] = $uri;

			$parent = $row;
			unset($row);
		}

		return $rows;
	}

	//---------------------------------------------------------------------------

	public function cacheCategoryChain($categorySpec)
	{
		$category = false;
		
		$cache =& $this->_cache['category'];
		$parent = NULL;

		foreach ($this->fetchCategoryChain($categorySpec) as $row)
		{
			$uri = $row['uri'];
			unset($row['uri']);

			$category = $this->factory->manufacture('Category', $row);
			$category->setParent($parent);
			$category->setURI($uri);
			if ($parent && ($category->parent_id === NULL))
			{
				$category->parent_id = $parent->id;
			}
			$parent = $category;
			$cache[$category->id] = $category;
			$cache[$category->slug] = $category;
		}
		
		return $category;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchCategoryURI($category)
	{
		$cache =& $this->_cache['category'];

		if ((($cat = @$cache[$category->id]) !== NULL) || (($cat = @$cache[$category->slug]) !== NULL))
		{
			if ($uri = $cat->uri())
			{
				return $uri;
			}
		}

		$category = $this->cacheCategoryChain(array('id'=>$category->id, 'level'=>$category->level));
		return $category->uri();
	}
	
	//---------------------------------------------------------------------------
	//
	// Fetch a category. Uses caching for improved performance.
	//
	//---------------------------------------------------------------------------
	
	public function fetchCategory($nameOrID)
	{
		$cache =& $this->_cache['category'];

		if (($category = @$cache[$nameOrID]) !== NULL)
		{
			return $category;
		}

		$db = $this->loadDB();

		$field = is_integer($nameOrID) ? 'id' : 'slug';

		if ($row = $db->selectRow('category', '*', "{$field}=?", $nameOrID))
		{
			$category = $this->factory->manufacture('Category', $row);
		}
		else
		{
			$category = false;
		}
		
		if ($category)
		{
			if (!isset($cache[$category->id]))
			{
				$cache[$category->id] = $category;
			}
			if (!isset($cache[$category->slug]))
			{
				$cache[$category->slug] = $category;
			}
		}
		elseif (!isset($cache[$nameOrID]))
		{
			$cache[$nameOrID] = false;
		}

		return $category;
	}

	//---------------------------------------------------------------------------

	protected function filterCategoryColumn($col)
	{
		switch ($col)
		{
			case 'level':
			case 'position':
			case 'title':
			case 'priority':
				break;
			default:
				return '';
		}

		return '{category}.'.$col;
	}

	public function fetchCategories($parentNameOrID = NULL, $include = NULL, $exclude = NULL, $sort = NULL, $order = NULL, $for = NULL)
	{
		$cache =& $this->_cache['category'];

		$db = $this->loadDB();

		$parent = ($parentNameOrID === NULL) ? NULL : $this->fetchCategory($parentNameOrID);
		$parentID = $parent ? $parent->id : ($parentNameOrID === 0 ? 0 : NULL);

		if (!empty($sort))
		{
			$orderBy = $this->buildOrderBy($sort, $order, 'filterCategoryColumn');
		}
		else
		{
			$orderBy = NULL;
		}

		$where = array();
		$bind = array();

		if (!empty($include))
		{
			$where[] = $db->buildFieldIn('category', 'slug', $include);
			$bind = array_merge($bind, $include);
		}

		if (!empty($exclude))
		{
			$where[] = $db->buildFieldNotIn('category', 'slug', $exclude);
			$bind = array_merge($bind, $exclude);
		}

		if ($parentID !== NULL)
		{
			$where[] = '{category}.parent_id=?';
			$bind[] = $parentID;
		}
		
		if (!empty($for))
		{
			$type = key($for);
			$typeID = intval(current($for));
			$where[] = "{{$type}}.id=?";
			$bind[] = $typeID;
			$joins = array();
			$this->buildCategoriesJoin($type, $joins);
			$rows = $db->query($db->buildSelect($type, '{category}.*', $joins, implode(' AND ', $where), $orderBy), $bind)->rows();
		}
		else
		{
			$rows = $db->query($db->buildSelect('category', '*', NULL, implode(' AND ', $where), $orderBy), $bind)->rows();
		}

		$categories = array();
		foreach ($rows as $row)
		{
			$category = $this->factory->manufacture('Category', $row);
			$category->setParent($parent);
			if (!isset($cache[$category->id]))
			{
				$cache[$category->id] = $category;
			}
			if (!isset($cache[$category->slug]))
			{
				$cache[$category->slug] = $category;
			}
			$categories[] = $category;
		}

		return $categories;
	}

	//---------------------------------------------------------------------------
	
	public function countCategories($parentNameOrID = NULL, $recurse = false)
	{
		$db = $this->loadDB();

		if ($parentNameOrID === NULL)
		{
			return $db->countRows('category');
		}
		else
		{
			$parent = $this->fetchCategory($parentNameOrID);
			return $this->countCategoriesForParentIDs(array($parent->id), $recurse);
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function countCategoriesForParentIDs($parentIDs, $recurse = false)
	{
		$db = $this->loadDB();

		$where = $db->buildFieldIn('category', 'parent_id', $parentIDs);
		$bind = $parentIDs;

		if (!$recurse)
		{
			return $db->countRows('category', $where, $bind);
		}

		$rows = $db->selectRows('category', 'id', $where, $bind);
		$count = count($rows);
		
		if ($count > 0)
		{
			$ids = array();
			foreach($rows as $row)
			{
				$ids[] = $row['id'];
			}
			$count += $this->countCategoriesForParentIDs($ids, true);
		}
			
		return $count;
	}
	
	//---------------------------------------------------------------------------
	
	private static function compareCategories($t1, $t2)
	{
		return strcmp($t1->title, $t2->title);
	}
	
	public function sortCategories(&$categories)
	{
		if (!empty($categories))
		{
			static $callback = array('_AdminContentModel', 'compareCategories');
			foreach ($categories as $category)
			{
				if ($category->children)
				{
					$this->sortCategories($category->children);
				}
			}
			usort($categories, $callback);
		}
	}
	
	//---------------------------------------------------------------------------

	public function fetchChildCategoryID($parentID, $slug)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('category', 'id', 'parent_id=? AND slug=?', array($parentID, $slug));
		return isset($row['id']) ? $row['id'] : NULL;
	}

	//---------------------------------------------------------------------------

	public function childCategoryExists($parentID, $slug)
	{
		return ($this->fetchChildCategoryID($parentID, $slug) != 0);
	}

	//---------------------------------------------------------------------------
	//
	// Fetch an entire chain of pages from the root page to a specified leaf page.
	// Chain may be specified by URI or by [pageID, pageLevel].
	//
	// If the "virtual" parameter is true, the chain may consist partially of "virtual"
	// pages. A virtual page is any non-existant descendant of a page that has the
	// "magical" property set. Magic only works for uri-specified page chains.
	//
	// This is the workhorse page lookup function and it performs no caching.
	// Returns raw database rows (not page objects).
	//
	//---------------------------------------------------------------------------

	public function fetchPageChain($pageSpec, $virtual = false, $fields = '*')
	{
		$searchByID = !isset($pageSpec['uri']);

		$fields = $this->getPageFields($fields);

		if ($searchByID)
		{
			$virtual = false;
			$keys = array_flip($fields);
			if (!isset($keys['slug']))
			{
				$fields[] = 'slug';
			}
			if (!isset($keys['template_name']))
			{
				$fields[] = 'template_name';
			}
			if (!isset($keys['cacheable']))
			{
				$fields[] = 'cacheable';
			}
			if (!isset($keys['secure']))
			{
				$fields[] = 'secure';
			}
			$pageID = $pageSpec['id'];
			$lastPageNum = $pageSpec['level'];			// level zero is "/" (the root page)
		}
		else
		{
			$slugs = isset($pageSpec['slugs']) ? $pageSpec['slugs'] : $this->getSlugs($pageSpec['uri']);
			$lastPageNum = count($slugs);			// zero slugs indicates the URL is "/" (the root page)
		}

		$selectTemplate = $this->buildSelectTemplate('page', $fields);

		$db = $this->loadDB();
		
		$select[] = $this->bindTemplate($selectTemplate, 0);
		$joinType = $virtual ? 'LEFT' : '';
		$joins = array();
		$bind = NULL;

		for ($pageIndex = 1; $pageIndex <= $lastPageNum; ++$pageIndex)
		{
			$page = 'page' . $pageIndex;
			$select[] = $this->bindTemplate($selectTemplate, $pageIndex);
			$cond = array(array('leftField'=>'id', 'rightField'=>'parent_id', 'joinOp'=>'='));
			if (!$searchByID)
			{
				$cond[] = array('rightField'=>'slug', 'value'=>'?', 'joinOp'=>'=');
			}
			$joins[] = array('type'=>$joinType, 'table'=>array('page', $page), 'conditions'=>$cond);
		}

		$where = 'page0.parent_id=0';
		if ($searchByID && isset($page))
		{
			$where .= " AND {$page}.id=?";
			$bind = $pageID;
		}
		
		// $result will be one fat row containing entire page chain
		
		if (!$result = $db->selectJoinRow(array('page', 'page0'), implode(', ', $select), $joins, $where, $searchByID ? $bind : $slugs))
		{
			return array();
		}
		
		// convert $result into array of individual rows, one per page in chain

		$rows = array();
		
		$isCacheable = false;
		$isSecure = false;
		$templateName = '';
		$virtual = false;
		$magic = array();
		$uri = '';
		$parent = NULL;
		for ($pageIndex = 0; $pageIndex <= $lastPageNum; ++$pageIndex)
		{
			$row =& $rows[$pageIndex];
			foreach ($fields as $field)
			{
				$row[$field] = $result["_{$pageIndex}_{$field}"];
			}
			$slug = $row['slug'];
			
			// are we dealing with a virtual page?
			
			if (!isset($row['id']))
			{
				$slug = $slugs[$pageIndex-1];
				if ($virtual)
				{
					$magic[] = $slug;
					$row = $parent;
					$row['level']++;
					$row['magical'] = false;
					$row['magic'] = $magic;
					$row['slug'] = $slug;
					$row['breadcrumb'] = $slug;
					$row['virtual'] = true;
				}
			}
			elseif ($virtual = $row['magical'])
			{
				$magic = array();
			}

			$uri .= ($pageIndex === 1 ? '' : '/') . $slug;
			$row['uri'] = $uri;

			if ($row['secure'] == _Page::Secure_inherit)
			{
				$row['is_secure'] = $isSecure;
			}
			else
			{
				$isSecure = $row['is_secure'] = $row['secure'];
			}

			if ($row['cacheable'] == _Page::Cacheable_inherit)
			{
				$row['is_cacheable'] = $isCacheable;
			}
			else
			{
				$isCacheable = $row['is_cacheable'] = $row['cacheable'];
			}

			if ($row['template_name'] === '')
			{
				$row['active_template_name'] = $templateName;
			}
			else
			{
				$templateName = $row['active_template_name'] = $row['template_name'];
			}

			$parent = $row;
			unset($row);
		}

		return $rows;
	}

	//---------------------------------------------------------------------------

	public function cachePageChain($pageSpec, $virtual = false, $fields = '*')
	{
		$cache =& $this->_cache['page'];
		
		$page = false;
		$parent = NULL;
		
		foreach ($this->fetchPageChain($pageSpec, $virtual, $fields) as $row)
		{
			$uri = $row['uri'];
			unset($row['uri']);
			if ($row['id'] !== NULL)
			{
				if ($page = $this->createPageFromRow($row, $uri, $parent))
				{
					$parent = $page;
					if (!$page->virtual)
					{
						$cache[$page->id] = $page;
					}
				}
			}
			else
			{
				$page = false;
			}
			$cache[$uri] = $page;
		}
		
		return $page;
	}
	
	//---------------------------------------------------------------------------
	//
	// Fetch a single uncached page. Ignore page chain.
	//
	//---------------------------------------------------------------------------

	public function fetchPageSingle($uri)
	{
		$slugs = $this->getSlugs($uri);
		$lastPageNum = count($slugs);			// zero slugs indicates the URL is "/" (the root page)

		$db = $this->loadDB();

		if (!$statement = @$this->_cache['page_query'][$lastPageNum])
		{
			$lastPage = 'page' . $lastPageNum;
			
			$joins = array();
			for ($pageIndex = 1; $pageIndex <= $lastPageNum; ++$pageIndex)
			{
				$page = 'page' . $pageIndex;
				$cond = array(array('leftField'=>'id', 'rightField'=>'parent_id', 'joinOp'=>'='));
				$cond[] = array('rightField'=>'slug', 'value'=>'?', 'joinOp'=>'=');
				$joins[] = array('table'=>array('page', $page), 'conditions'=>$cond);
			}

			$sql = $db->buildSelect(array('page', 'page0'), "{$lastPage}.*", $joins, 'page0.parent_id=0');

			if (!$statement = $db->prepare($sql))
			{
				return false;
			}
			
			$this->_cache['page_query'][$lastPageNum] = $statement;
		}

		if (!$result = $db->execute($statement, $slugs))
		{
			return false;
		}

		if (!$row = $result->row())
		{
			return false;
		}

		$page = $this->createPageFromRow($row, $uri);

		return $page;
	}

	//---------------------------------------------------------------------------
	//
	// Fetch a single uncached child page. Ignore page chain.
	//
	//---------------------------------------------------------------------------

	public function fetchPageByParentAndSlug($parent, $slug)
	{
		$db = $this->loadDB();

		if (!$row = $db->selectRow('page', '*', 'parent_id=? AND slug=?', array($parent->id, $slug)))
		{
			return false;
		}

		$uri = $parent->uri() . '/' . $slug;
		$page = $this->createPageFromRow($row, $uri, $parent);

		return $page;
	}

	//---------------------------------------------------------------------------
	//
	// Fetch a page. Uses caching for improved performance.
	//
	//---------------------------------------------------------------------------

	public function fetchPage($pageSpec)
	{
		$cache =& $this->_cache['page'];

		if ($searchByID = isset($pageSpec['id']))
		{
			// if page has been cached already, return it

			if (($page = @$cache[$pageSpec['id']]) !== NULL)
			{
				return $page;
			}
		}
		
		if (isset($pageSpec['uri']))
		{
			if (($uri = rtrim($pageSpec['uri'], '/')) == '')
			{
				$uri = '/';
			}

			// if page has been cached already, return it

			if (($page = @$cache[$uri]) !== NULL)
			{
				return $page;
			}
		}
		
		if (!$searchByID && isset($uri))
		{
			// if the page's parent is cached, we can more efficiently construct this page
			// by using data from the parent

			if (($parentURI = dirname($uri)) === '')
			{
				$parentURI = '/';
			}
			if (($parent = @$cache[$parentURI]) !== NULL)
			{
				if (!$parent)
				{
					return false;
				}
				if ($parent->virtual || ((!$page = $this->fetchPageSingle($uri)) && $parent->magical))
				{
					$slug = basename($uri);
					$page = clone $parent;
					$page->slug = $slug;				// comment out if virtual pages should keep the magical page's slug!
					$page->breadcrumb = $slug;		// comment out if virtual pages should keep the magical page's breadcrumb!
					$page->level++;
					$page->magic[] = $slug;
					$page->parent_id = $parent->id;
					$page->virtual = true;
					$page->setURI($uri);
				}
				if ($page)
				{
					$page->setParent($parent);
					if (!$page->virtual)
					{
						$cache[$page->id] = $page;
					}
				}
				return $cache[$uri] = $page;
			}
		}

		// fall back on uncached case: fetch the entire page chain and cache all its pages
		
		return $this->cachePageChain($pageSpec, true);
	}

	//---------------------------------------------------------------------------
	
	public function fetchPageByURI($uri)
	{
		if (!$pageSpec = $this->makePageSpec($uri))
		{
			return false;
		}

		return $this->fetchPage($pageSpec);
	}

	//---------------------------------------------------------------------------
	
	public function fetchPageByID($id)
	{
		$cache =& $this->_cache['page'];

		// if page has been cached already, return it

		if (($page = @$cache[$id]) !== NULL)
		{
			return $page;
		}

		$db = $this->loadDB();

		$row = $db->selectRow('page', $select = 'id, level', 'id=?', $id);
		if (empty($row))
		{
			return false;
		}
	
		return $this->fetchPage($row);
	}

	//---------------------------------------------------------------------------

	public function fetchChildPage($parent, $slug, $virtual = false)
	{
		$cache =& $this->_cache['page'];
		
		$uri = $parent->uri() . '/' . $slug;

		// if page has been cached already, return it

		if (($page = @$cache[$uri]) !== NULL)
		{
			return $page;
		}
		
		if (!$parent->virtual)
		{
			$page = $this->fetchPageByParentAndSlug($parent, $slug);
		}
		
		if ($virtual && ($parent->virtual || (!$page && $parent->magical)))
		{
			$page = clone $parent;
			$page->slug = $slug;				// comment out if virtual pages should keep the magical page's slug!
			$page->breadcrumb = $slug;		// comment out if virtual pages should keep the magical page's breadcrumb!
			$page->level++;
			$page->magic[] = $slug;
			$page->parent_id = $parent->id;
			$page->virtual = true;
			$page->setURI($uri);
			$page->setParent($parent);
		}
		if ($page)
		{
			if (!$page->virtual)
			{
				$cache[$page->id] = $page;
			}
		}

		return $cache[$uri] = $page;
	}

	//---------------------------------------------------------------------------

	protected function filterPageColumn($col)
	{
		switch ($col)
		{
			case 'level':
			case 'position':
			case 'title':
			case 'status':
			case 'created':
			case 'edited':
			case 'published':
			case 'priority':
				break;
			case 'author':
				$col = 'author_id';
				break;
			case 'editor':
				$col = 'editor_id';
				break;
			default:
				return '';
		}

		return '{page}.'.$col;
	}

	//---------------------------------------------------------------------------

	public function fetchPageRows($parentPage, $ids = NULL, $categories = NULL, $status = NULL, $onOrAfter = NULL, $onOrBefore = NULL,
										$limit = NULL, $offset = NULL, $sort = NULL, $order = NULL)
	{
		$db = $this->loadDB();
		
		if (!is_numeric($limit))
		{
			$limit = NULL;
		}
		
		if (!is_numeric($offset))
		{
			$offset = NULL;
		}
				
		if (!empty($sort))
		{
			$orderBy = $this->buildOrderBy($sort, $order, 'filterPageColumn');
		}
		if (empty($orderBy))
		{
			$orderBy = '{page}.level, {page}.position DESC, {page}.created DESC';
		}
		if ($orderBy !== 'RAND')
		{
			$orderBy .= ', {page}.id';		// ensure consistency if dates are the same
		}
		
		$joins = array();
		
		if (empty($parentPage))
		{
			$where = '1';
			$bind = array();
		}
		else
		{
			$where = '{page}.parent_id=?';
			$bind[] = intval($parentPage->id);
		}

		if (!empty($ids))
		{
			$where .= ' AND ' . $db->buildFieldIn('page', 'id', $ids);
			$bind = array_merge($bind, $ids);
		}

		if (!empty($categories))
		{
			$this->buildCategoriesJoin('page', $joins);
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		$joins[] = array('leftTable'=>'page', 'table'=>'user', 'type'=>'left', 'conditions'=>array(array('leftField'=>'author_id', 'rightField'=>'id', 'joinOp'=>'=')));

		if (!empty($status) && ($status !== 'any'))
		{
			$where .= ' AND ' . $this->buildStatusIn($db, 'page', $status);
			$bind = array_merge($bind, $status);
		}
		
		if (!empty($onOrAfter))
		{
			$where .= ' AND {page}.published >= ?';
			$bind[] = $onOrAfter;
		}
		
		if (!empty($onOrBefore))
		{
			$where .= ' AND {page}.published < ?';
			$bind[] = $onOrBefore;
		}		

		$sql = $db->buildSelect('page', '{page}.*, {user}.name AS author_name', $joins, $where, $orderBy, $limit, $offset, true);

		return  $db->query($sql, $bind)->rows();
	}
	
	//---------------------------------------------------------------------------

	public function fetchPages($parentPage, $ids = NULL, $categories = NULL, $status = NULL, $onOrAfter = NULL, $onOrBefore = NULL,
										$limit = NULL, $offset = NULL, $sort = NULL, $order = NULL)
	{
		$rows = $this->fetchPageRows($parentPage, $ids, $categories, $status, $onOrAfter, $onOrBefore, $limit, $offset, $sort, $order);

		$pages = array();
		foreach ($rows as $row)
		{
			if ($parentPage)
			{
				$uri = ($parentPage->level ? $parentPage->uri() : '') . '/' . $row['slug'];
				if ($page = $this->createPageFromRow($row, $uri, $parentPage))
				{
					$page->author_name = $row['author_name'];
				}
			}
			else
			{
				$page = $this->fetchPage(array('id'=>$row['id'], 'level'=>$row['level']));
			}
			$pages[] = $page;
		}

		return $pages;
	}
	
	//---------------------------------------------------------------------------
	
	public function countPages($parentPage, $ids = NULL, $categories = NULL, $status = NULL, $onOrAfter = NULL, $onOrBefore = NULL,
										$limit = NULL, $offset = NULL)
	{
		if (empty($ids) && empty($categories) && (empty($status) || ($status === 'any')) && empty($onOrAfter) && empty($onOrBefore) && empty($limit) && empty($offset))
		{
			$db = $this->loadDB();
			if (empty($parentPage))
			{
				return $db->countRows('page');
			}
			return $db->countRows('page', 'parent_id=?', $parentPage->id);
		}
		
		$childRows = $this->fetchPageRows($parentPage, $ids, $categories, $status, $onOrAfter, $onOrBefore, $limit, $offset);
		return count($childRows);
	}

	//---------------------------------------------------------------------------
	
	public function fetchPageSiblings($page, $category, $status, $limit, $offset, $sort, $order, $which)
	{
		if (!$parentPage = $page->parent())
		{
			return array();
		}

		$db = $this->loadDB();
		
		if (!is_numeric($limit))
		{
			$limit = NULL;
		}
		
		if (!is_numeric($offset))
		{
			$offset = NULL;
		}
		
		if (empty($order))
		{
			$order = ($which == self::siblings_after) ? 'ASC' : 'DESC';
		}
		if (empty($sort))
		{
			$sort = 'position';
		}
		$orderBy = $this->buildOrderBy($sort, $order, 'filterPageColumn');

		$joins = array();
		
		$where = '{page}.parent_id=?';
		$bind[] = intval($parentPage->id);
		
		switch ($which)
		{
			case self::siblings_before:
				$where .= ' AND {page}.position<?';
				break;
			case self::siblings_after:
				$where .= ' AND {page}.position>?';
				break;
			default:
				$where .= ' AND {page}.position!=?';
		}
		$bind[] = intval($page->position);

		if (!empty($categories))
		{
			$this->buildCategoriesJoin('page', $joins);
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		$joins[] = array('leftTable'=>'page', 'table'=>'user', 'type'=>'left', 'conditions'=>array(array('leftField'=>'author_id', 'rightField'=>'id', 'joinOp'=>'=')));

		if (!empty($status) && ($status !== 'any'))
		{
			$where .= ' AND ' . $this->buildStatusIn($db, 'page', $status);
			$bind = array_merge($bind, $status);
		}
		
		$sql = $db->buildSelect('page', '{page}.*, {user}.name AS author_name', $joins, $where, $orderBy, $limit, $offset, true);

		$result = $db->query($sql, $bind);
		$rows = $result->rows();

		$pages = array();
		foreach ($rows as $row)
		{
			if ($parentPage)
			{
				$uri = ($parentPage->level ? $parentPage->uri() : '') . '/' . $row['slug'];
				if ($page = $this->createPageFromRow($row, $uri, $parentPage))
				{
					$page->author_name = $row['author_name'];
				}
			}
			else
			{
				$page = $this->fetchPage(array('id'=>$row['id'], 'level'=>$row['level']));
			}
			$pages[] = $page;
		}

		return $pages;
	}

	//---------------------------------------------------------------------------
	
	public function fetchPageAuthor($page)
	{
		return $this->fetchObjectUser($page, 'page', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchPageEditor($page)
	{
		return $this->fetchObjectUser($page, 'page', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function pageHasCategories($page, $categories = NULL)
	{
		$db = $this->loadDB();

		$joins = array();
		$this->buildCategoriesJoin('page', $joins);

		$where = '{page}.id=?';
		$bind[] = $page->id;
		
		if (!empty($categories))
		{
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		$row = $db->selectJoinRow('page', 'COUNT(*) as count', $joins, $where, $bind);
		return $row['count'] != 0;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchPageMeta($page)
	{
		return $this->fetchObjectMeta('page', $page);
	}

	//---------------------------------------------------------------------------
	
	public function fetchPageParts($page, $names, $inherit = false)
	{
		$result = array();
		
		self::makeList($names);
	
		// try to fulfill request from local cache
	
		foreach($names as $idx => $name)
		{
			if (($part = @$this->_cache['part'][$page->id][$name]) !== NULL)
			{
				if ($part)
				{
					$result[$name] = $part;
				}
				unset($names[$idx]);
			}
		}

		// any parts not cached?

		if (!empty($names))
		{
			$db = $this->loadDB();
	
			$where = 'page_id=?';
			$bind[] = $page->id;
			
			$where .= ' AND ' . $db->buildFieldIn('page_part', 'name', $names);
			$bind = array_merge($bind, $names);

			$names = array_flip($names);
			foreach ($db->selectRows('page_part', '*', $where, $bind) as $row)
			{
				$part = $this->factory->manufacture('Part', $row);
				$result[$part->name] = $this->_cache['part'][$page->id][$part->name] = $part;
				unset($names[$part->name]);
			}
			
			if ($inherit && !empty($names))
			{
			 	if ($parent = $page->parent())
			 	{
			 		// Note that we don't cache inherited parts, because that would result in cached parts
			 		// being returned even if $inherit == false.
			 		
					foreach ($this->fetchPageParts($parent, array_flip($names), true) as $name => $part)
					{
						$result[$name] = $part;
						unset($names[$name]);
					}
				}
				
				// It **is** safe to cache the non-existence of inherited parts, since this implies that
				// the part does not exist in the non-inherited case as well.
				
				foreach ($names as $name => $ignore)
				{
					if (!isset($result[$name]))
					{
						$this->_cache['part'][$page->id][$name] = false;
					}
				}
			}
		}

		return $result;
	}

	//---------------------------------------------------------------------------

	public function pageHasParts($page, $names, $inherit = false, $any = false)
	{
		self::makeList($names);
		
		// if no part names specified, return true if there is at least one part
		
		if (empty($names))
		{
			$db = $this->loadDB();
			if ($db->countRows('page_part', 'page_id=?', $page->id) > 0)
			{
				return true;
			}
			if ($inherit && ($parent = $page->parent()))
			{
				return $this->pageHasParts($parent, $names, true);
			}
			return false;
		}
		
		$parts = $this->fetchPageParts($page, $names, $inherit);
		return $any ? count($parts) > 0 :  count($parts) === count($names);
	}

	//---------------------------------------------------------------------------
	
	public function fetchPagePart($page, $name, $inherit = false)
	{
		if (!$parts = $this->fetchPageParts($page, $name, $inherit))
		{
			return false;
		}
		
		return $parts[$name];
	}

	//---------------------------------------------------------------------------
	
	public function fetchPagePartContent($page, $name, $inherit = false)
	{
		if (!$part = $this->fetchPagePart($page, $name, $inherit))
		{
			return false;
		}
		
		return $part->content_html;
	}

	//---------------------------------------------------------------------------

	public function blockExists($name)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('block', 'id', 'name=?',$name);
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function fetchBlock($nameOrID)
	{
		if (($block = @$this->_cache['block'][$nameOrID]) !== NULL)
		{
			return $block;
		}

		$db = $this->loadDB();

		$field = is_integer($nameOrID) ? 'id' : 'name';

		if ($row = $db->selectRow('block', '*', "{$field}=?", $nameOrID))
		{
			$block = $this->factory->manufacture('Block', $row);
		}
		else
		{
			$block = false;
		}

		return $this->_cache['block'][$nameOrID] = $block;
	}

	//---------------------------------------------------------------------------
	
	public function fetchBlockContent($nameOrID)
	{
		if ($block = $this->fetchBlock($nameOrID))
		{
			return $block->content_html;
		}
		
		return false;
	}

	//---------------------------------------------------------------------------
	
	protected function filterBlockColumn($col)
	{
		switch ($col)
		{
			case 'name':
			case 'priority':
				break;
			case 'author':
				$col = 'author_id';
				break;
			case 'editor':
				$col = 'editor_id';
				break;
			default:
				return '';
		}

		return '{block}.'.$col;
	}

	public function fetchBlocks($categories = NULL, $sort = NULL, $order = NULL)
	{
		$db = $this->loadDB();

		if (!empty($sort))
		{
			$orderBy = $this->buildOrderBy($sort, $order, 'filterBlockColumn');
		}
		else
		{
			$orderBy = NULL;
		}

		if (empty($categories))
		{
			$rows = $db->query($db->buildSelect('block', '*', NULL, NULL, $orderBy))->rows();
		}
		else
		{	
			$joins = array();
			$this->buildCategoriesJoin('block', $joins);
			$where = $db->buildFieldIn('category', 'slug', $categories);
			$rows = $db->query($db->buildSelect('block', '{block}.*', $joins, $where, $orderBy), $categories)->rows();
		}

		$blocks = array();
		foreach ($rows as $row)
		{
			$blocks[] = $this->factory->manufacture('Block', $row);
		}
		return $blocks;
	}

	//---------------------------------------------------------------------------
	
	public function countBlocks($categories = NULL)
	{
		$db = $this->loadDB();

		if (empty($categories))
		{
			return $db->countRows('block');
		}
		else
		{
			$joins = array();
			$this->buildCategoriesJoin('block', $joins);
			$where = $db->buildFieldIn('category', 'slug', $categories);
			$rows = $db->selectJoinRows('block', '{block}.id', $joins, $where, $categories, true);
			return count($rows);
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function blockHasCategories($block, $categories = NULL)
	{
		$db = $this->loadDB();

		$joins = array();
		$this->buildCategoriesJoin('block', $joins);

		$where = '{block}.id=?';
		$bind[] = $block->id;
		
		if (!empty($categories))
		{
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		$row = $db->selectJoinRow('block', 'COUNT(*) as count', $joins, $where, $bind);
		return $row['count'] != 0;
	}
	
	//---------------------------------------------------------------------------
	
	protected function filterImageColumn($col)
	{
		switch ($col)
		{
			case 'ctype':
			case 'alt':
			case 'title':
			case 'created':
			case 'edited':
			case 'priority':
				break;
			case 'author':
				$col = 'author_id';
				break;
			case 'editor':
				$col = 'editor_id';
				break;
			default:
				return '';
		}

		return '{image}.'.$col;
	}

	public function fetchContentImages($categories = NULL, $sort = NULL, $order = NULL, $withContent = false)
	{
		$db = $this->loadDB();

		if (!empty($sort))
		{
			$orderBy = $this->buildOrderBy($sort, $order, 'filterImageColumn');
		}
		else
		{
			$orderBy = NULL;
		}

		if ($withContent)
		{
			$select = '{image}.*';
		}
		else
		{
			$select = '{image}.id, {image}.slug, {image}.ctype, {image}.url, {image}.width, {image}.height, {image}.alt, {image}.title';
		}

		if (empty($categories))
		{
			$rows = $db->query($db->buildSelect('image', $select, NULL, 'theme_id = -1', $orderBy))->rows();
		}
		else
		{	
			$joins = array();
			$this->buildCategoriesJoin('image', $joins);
			$where = $db->buildFieldIn('category', 'slug', $categories) . ' AND theme_id = -1';
			$rows = $db->query($db->buildSelect('image', $select, $joins, $where, $orderBy), $categories)->rows();
		}

		$images = array();
		foreach ($rows as $row)
		{
			$images[] = $this->factory->manufacture('Image', $row);
		}
		return $images;
	}

	//---------------------------------------------------------------------------
	
	public function countContentImages($categories = NULL)
	{
		$db = $this->loadDB();

		if (empty($categories))
		{
			return $db->countRows('image', 'theme_id = -1');
		}
		else
		{
			$joins = array();
			$this->buildCategoriesJoin('image', $joins);
			$where = $db->buildFieldIn('category', 'slug', $categories) . ' AND theme_id = -1';
			$rows = $db->selectJoinRows('image', '{image}.id', $joins, $where, $categories, true);
			return count($rows);
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function contentImageHasCategories($image, $categories = NULL)
	{
		$db = $this->loadDB();

		$joins = array();
		$this->buildCategoriesJoin('image', $joins);

		$where = '{image}.id=?';
		$bind[] = $image->id;
		
		if (!empty($categories))
		{
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		$row = $db->selectJoinRow('image', 'COUNT(*) as count', $joins, $where, $bind);
		return $row['count'] != 0;
	}
	
	//---------------------------------------------------------------------------
	
	public function fileExists($slug)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('file', 'id', 'slug=?', $slug);
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function fetchFile($nameOrID, $withContent = false, $status = NULL)
	{
		if (($file = @$this->_cache['file'][$nameOrID]) !== NULL)
		{
			return $file;
		}

		$db = $this->loadDB();

		if ($withContent)
		{
			$select = '{file}.*';
		}
		else
		{
			$select = '{file}.id, {file}.slug, {file}.ctype, {file}.url, {file}.title, {file}.description, {file}.status, {file}.download, {file}.size, {file}.rev';
		}

		$field = is_integer($nameOrID) ? 'id' : 'slug';

		$where = "{$field}=?";
		$bind[] = $nameOrID;
		
		if (!empty($status) && ($status !== 'any'))
		{
			$where .= ' AND ' . $this->buildStatusIn($db, 'file', $status);
			$bind = array_merge($bind, $status);
		}

		if ($row = $db->selectRow('file', $select, $where, $bind))
		{
			$file = $this->factory->manufacture('File', $row);
		}
		else
		{
			$file = false;
		}

		return $this->_cache['file'][$nameOrID] = $file;
	}

	//---------------------------------------------------------------------------
	
	protected function filterFileColumn($col)
	{
		switch ($col)
		{
			case 'title':
			case 'status':
			case 'created':
			case 'edited':
			case 'size':
			case 'ctype':
			case 'priority':
				break;
			case 'name':
				$col = 'slug';
				break;
			case 'author':
				$col = 'author_id';
				break;
			case 'editor':
				$col = 'editor_id';
				break;
			default:
				return '';
		}

		return 'file.'.$col;
	}
	
	public function fetchFiles($withContent = false, $ids = NULL, $categories = NULL, $status = NULL, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL)
	{
		$db = $this->loadDB();

		$where = '1';
		$bind = array();
		$joins = array();
		
		if (!is_numeric($limit))
		{
			$limit = NULL;
		}
		
		if (!is_numeric($offset))
		{
			$offset = NULL;
		}
				
		if (!empty($sort))
		{
			$orderBy = $this->buildOrderBy($sort, $order, 'filterFileColumn');
		}
		if (empty($orderBy))
		{
			$orderBy = '{file}.slug DESC';
		}

		if (!empty($ids))
		{
			$where .= ' AND ' . $db->buildFieldIn('file', 'id', $ids);
			$bind = array_merge($bind, $ids);
		}

		if (!empty($categories))
		{
			$this->buildCategoriesJoin('file', $joins);
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		if (!empty($status) && ($status !== 'any'))
		{
			$where .= ' AND ' . $this->buildStatusIn($db, 'file', $status);
			$bind = array_merge($bind, $status);
		}

		if ($withContent)
		{
			$select = '{file}.*';
		}
		else
		{
			$select = '{file}.id, {file}.slug, {file}.ctype, {file}.url, {file}.title, {file}.description, {file}.status, {file}.download, {file}.size, {file}.rev';
		}

		$sql = $db->buildSelect('file', $select, $joins, $where, $orderBy, $limit, $offset, true);
		$result = $db->query($sql, $bind);
		$rows = $result->rows();

		$files = array();
		foreach ($rows as $row)
		{
			$files[] = $this->factory->manufacture('File', $row);
		}
		return $files;
	}

	//---------------------------------------------------------------------------
	
	public function countFiles($ids = NULL, $categories = NULL, $status = NULL, $limit = NULL, $offset = NULL)
	{
		if (empty($ids) && empty($categories) && (empty($status) || ($status === 'any')) && empty($limit) && empty($offset))
		{
			$db = $this->loadDB();
			return $db->countRows('file');
		}
		
		$files = $this->fetchFiles(false, $ids, $categories, $status, $limit, $offset);
		return count($files);
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchFileMeta($file)
	{
		return $this->fetchObjectMeta('file', $file);
	}

	//---------------------------------------------------------------------------
	
	public function fileHasCategories($file, $categories = NULL)
	{
		$db = $this->loadDB();

		$joins = array();
		$this->buildCategoriesJoin('file', $joins);

		$where = '{file}.id=?';
		$bind[] = $file->id;
		
		if (!empty($categories))
		{
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		$row = $db->selectJoinRow('file', 'COUNT(*) as count', $joins, $where, $bind);
		return $row['count'] != 0;
	}
	
	//---------------------------------------------------------------------------
	
	public function linkExists($name)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('link', 'id', 'name=?', $name);
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function fetchLink($nameOrID)
	{
		if (($link = @$this->_cache['link'][$nameOrID]) !== NULL)
		{
			return $link;
		}

		$db = $this->loadDB();

		$field = is_integer($nameOrID) ? 'id' : 'name';

		if ($row = $db->selectRow('link', '*', "{$field}=?", $nameOrID))
		{
			$link = $this->factory->manufacture('Link', $row);
		}
		else
		{
			$link = false;
		}

		return $this->_cache['link'][$nameOrID] = $link;
	}

	//---------------------------------------------------------------------------
	
	protected function filterLinkColumn($col)
	{
		switch ($col)
		{
			case 'name':
			case 'title':
			case 'created':
			case 'edited':
			case 'priority':
				break;
			case 'author':
				$col = 'author_id';
				break;
			case 'editor':
				$col = 'editor_id';
				break;
			default:
				return '';
		}

		return '{link}.'.$col;
	}

	public function fetchLinks($categories = NULL, $sort = NULL, $order = NULL)
	{
		$db = $this->loadDB();

		if (!empty($sort))
		{
			$orderBy = $this->buildOrderBy($sort, $order, 'filterLinkColumn');
		}
		else
		{
			$orderBy = NULL;
		}

		if (empty($categories))
		{
			$rows = $db->query($db->buildSelect('link', '*', NULL, NULL, $orderBy))->rows();
		}
		else
		{	
			$joins = array();
			$this->buildCategoriesJoin('link', $joins);
			$where = $db->buildFieldIn('category', 'slug', $categories);
			$rows = $db->query($db->buildSelect('link', '{link}.*', $joins, $where, $orderBy), $categories)->rows();
		}

		$links = array();
		foreach ($rows as $row)
		{
			$links[] = $this->factory->manufacture('Link', $row);
		}
		return $links;
	}

	//---------------------------------------------------------------------------
	
	public function countLinks($categories = NULL)
	{
		$db = $this->loadDB();

		if (empty($categories))
		{
			return $db->countRows('link');
		}
		else
		{
			$joins = array();
			$this->buildCategoriesJoin('link', $joins);
			$where = $db->buildFieldIn('category', 'slug', $categories);
			$rows = $db->selectJoinRows('link', '{link}.id', $joins, $where, $categories, true);
			return count($rows);
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchLinkMeta($link)
	{
		return $this->fetchObjectMeta('link', $link);
	}

	//---------------------------------------------------------------------------
	
	public function linkHasCategories($link, $categories = NULL)
	{
		$db = $this->loadDB();

		$joins = array();
		$this->buildCategoriesJoin('link', $joins);

		$where = '{link}.id=?';
		$bind[] = $link->id;
		
		if (!empty($categories))
		{
			$where .= ' AND ' . $db->buildFieldIn('category', 'slug', $categories);
			$bind = array_merge($bind, $categories);
		}
		
		$row = $db->selectJoinRow('link', 'COUNT(*) as count', $joins, $where, $bind);
		return $row['count'] != 0;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchTheme($slugOrID)
	{
		if (($theme = @$this->_cache['theme'][$slugOrID]) !== NULL)
		{
			return $theme;
		}

		$db = $this->loadDB();

		$field = is_integer($slugOrID) ? 'id' : 'slug';

		if ($row = $db->selectRow('theme', '*', "{$field}=?", $slugOrID))
		{
			$theme = $this->factory->manufacture('Theme', $row);
		}
		else
		{
			$theme = false;
		}

		return $this->_cache['theme'][$slugOrID] = $theme;
	}

	//---------------------------------------------------------------------------
	
	public function fetchTemplate($nameOrID, $theme = NULL, $branch = NULL)
	{
		if (SparkUtil::valid_int($nameOrID))
		{
			return $this->fetchDesignAssetByID('template', $nameOrID);
		}
		else
		{
			return $this->fetchDesignAssetByName('template', 'name', $nameOrID, $theme, $branch);
		}
	}

	//---------------------------------------------------------------------------
	
	public function fetchTemplateContent($nameOrID, $theme = NULL, $branch = NULL)
	{
		if ($template = $this->fetchTemplate($nameOrID, $theme, $branch))
		{
			return $template->content;
		}
		
		return false;
	}

	//---------------------------------------------------------------------------
	
	public function fetchSnippet($nameOrID, $theme = NULL, $branch = NULL)
	{
		if (SparkUtil::valid_int($nameOrID))
		{
			return $this->fetchDesignAssetByID('snippet', $nameOrID);
		}
		else
		{
			return $this->fetchDesignAssetByName('snippet', 'name', $nameOrID, $theme, $branch);
		}
	}

	//---------------------------------------------------------------------------

	public function fetchSnippetContent($nameOrID, $theme = NULL, $branch = NULL)
	{
		if ($snippet = $this->fetchSnippet($nameOrID, $theme, $branch))
		{
			return $snippet->content;
		}
		
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchTag($nameOrID, $theme = NULL, $branch = NULL)
	{
		if (SparkUtil::valid_int($nameOrID))
		{
			return $this->fetchDesignAssetByID('tag', $nameOrID);
		}
		else
		{
			return $this->fetchDesignAssetByName('tag', 'name', $nameOrID, $theme, $branch);
		}
	}

	//---------------------------------------------------------------------------
	
	public function fetchTags($theme = NULL, $branch = NULL)
	{
		if ($theme)
		{
			$lineage = explode(',', $theme->lineage);
			$lineage[] = $theme->id;
		}
		else
		{
			$lineage[] = 0;
		}

		$db = $this->loadDB();

		$where = $this->buildThemeSearchWhere($db, 'tag', 'name',  NULL, $lineage, $branch, $bind);
		$sql = $db->buildSelect('tag', '*', NULL, $where, 'theme_id ASC, branch DESC');
		
		$tags = array(); $deleted = array();
		foreach ($db->query($sql, $bind)->rows() as $row)
		{
			$themeID = $row['theme_id'];
			$name = $row['name'];
			
			if (!isset($tags[$themeID][$name]))
			{
				if ($row['branch_status'] == ContentObject::branch_status_deleted)
				{
					$deleted[$themeID][$name] = true;
				}
				elseif (!isset($deleted[$themeID][$name]))
				{
					$tags[$themeID][$name] = $this->factory->manufacture('Tag', $row);
				}
			}
		}
		return $tags;
	}

	//---------------------------------------------------------------------------
	
	public function fetchStyleChain($styleSlug, $theme = NULL, $branch = NULL)
	{
		$rows = $this->fetchDesignAssetChain('style', 'slug', $styleSlug, $theme, $branch, '{style}.rev,{style}.url');

		foreach ($rows as &$row)
		{
			if (empty($row['url']) && !empty($row['theme_style_url']))
			{
				$row['url'] = rtrim($row['theme_style_url'], '/') . '/' . $styleSlug;
			}
			unset($row['theme_style_url']); unset($row['theme_script_url']); unset($row['theme_image_url']);
		}

		return $rows;
	}

	//---------------------------------------------------------------------------
	
	public function fetchStyle($styleSlugOrID, $themeSlugOrID = NULL, $branch = NULL)
	{
		if (SparkUtil::valid_int($styleSlugOrID))
		{
			return $this->fetchDesignAssetByID('style', $styleSlugOrID);
		}
		else
		{
			return $this->fetchFinalDesignAssetByName('style', 'slug', $styleSlugOrID, $themeSlugOrID, $branch);
		}
	}

	//---------------------------------------------------------------------------
	
	public function fetchStyleContent($styleSlugOrID, $themeSlugOrID = NULL, $branch = NULL)
	{
		if ($style = $this->fetchStyle($styleSlugOrID, $themeSlugOrID, $branch))
		{
			return $style->content;
		}
		
		return false;
	}

	//---------------------------------------------------------------------------
	
	public function fetchScriptChain($scriptSlug, $theme = NULL, $branch = NULL)
	{
		$rows = $this->fetchDesignAssetChain('script', 'slug', $scriptSlug, $theme, $branch, '{script}.rev,{script}.url');

		foreach ($rows as &$row)
		{
			if (empty($row['url']) && !empty($row['theme_script_url']))
			{
				$row['url'] = rtrim($row['theme_script_url'], '/') . '/' . $scriptSlug;
			}
			unset($row['theme_style_url']); unset($row['theme_script_url']); unset($row['theme_image_url']);
		}

		return $rows;
	}

	//---------------------------------------------------------------------------
	
	public function fetchScript($scriptSlugOrID, $themeSlugOrID = NULL, $branch = NULL)
	{
		if (SparkUtil::valid_int($scriptSlugOrID))
		{
			return $this->fetchDesignAssetByID('script', $scriptSlugOrID);
		}
		else
		{
			return $this->fetchFinalDesignAssetByName('script', 'slug', $scriptSlugOrID, $themeSlugOrID, $branch);
		}
	}

	//---------------------------------------------------------------------------
	
	public function fetchScriptContent($scriptSlugOrID, $themeSlugOrID = NULL, $branch = NULL)
	{
		if ($script = $this->fetchScript($scriptSlugOrID, $themeSlugOrID, $branch))
		{
			return $script->content;
		}
		
		return false;
	}

	//---------------------------------------------------------------------------
	
	public function fetchImage($slugOrID, $theme = NULL, $branch = NULL, $withContent = false, $contentOverride = false)
	{
		if (SparkUtil::valid_int($slugOrID))
		{
			$select = $withContent ? '*' : '{image}.id, {image}.slug, {image}.ctype, {image}.url, {image}.width, {image}.height, {image}.alt, {image}.title, {image}.rev, {image}.created, {image}.edited, {image}.author_id, {image}.editor_id, {image}.theme_id';
			return $this->fetchDesignAssetByID('image', $slugOrID, $select);
		}
		elseif ($theme === NULL)
		{
			return $this->fetchContentImageByName($slugOrID, $withContent);
		}
		else
		{
			return $this->fetchDesignImageByName($slugOrID, $theme, $branch, $withContent, $contentOverride);
		}
	}

	//---------------------------------------------------------------------------
	
	public function fetchContentImageByName($slug, $withContent = false)
	{
		$cacheKey = $slug . '_-1';

		// we don't cache image content (conserves memory)

		if (($image = @$this->_cache['image'][$cacheKey]) !== NULL)
		{
			if (!$withContent || !$image)
			{
				return $image;
			}
		}

		$db = $this->loadDB();

		$select = $withContent ? '*' : '{image}.id, {image}.slug, {image}.ctype, {image}.url, {image}.width, {image}.height, {image}.alt, {image}.title, {image}.rev, {image}.created, {image}.edited, {image}.author_id, {image}.editor_id, {image}.theme_id';
	
		if ($row = $db->selectRow('image', $select, 'slug=? AND theme_id = -1', $slug))
		{
			$image = $this->factory->manufacture('Image', $row);
			if (!$withContent)
			{
				$this->_cache['image'][$cacheKey] = $image;
			}
		}
		else
		{
			$this->_cache['image'][$cacheKey] = false;
		}

		return $image;
	}

	//---------------------------------------------------------------------------
	
	public function fetchDesignImageByName($slug, $theme = NULL, $branch = NULL, $withContent = false, $contentOverride = false)
	{
		$select = $withContent ? '*' : '{image}.id, {image}.slug, {image}.ctype, {image}.url, {image}.width, {image}.height, {image}.alt, {image}.title, {image}.rev, {image}.created, {image}.edited, {image}.author_id, {image}.editor_id, {image}.theme_id';

		if ($theme && (is_string($theme) || is_int($theme)))
		{
			$theme = $this->fetchTheme($theme);
		}

		if ($image = $this->fetchDesignAssetByName('image', 'slug', $slug, $theme, $branch, $select, $contentOverride ? -1 : NULL))
		{
			if ($theme && ($image->theme_id != -1))	// if not a content image override, check for theme override url
			{
				$image->theme = $theme->slug;
				if (empty($image->url) && !empty($theme->image_url))
				{
					$image->url = rtrim($theme->image_url, '/') . '/' . $slug;
				}
			}
		}

		return $image;
	}

	//---------------------------------------------------------------------------
	
	public function fetchImageMeta($image)
	{
		return $this->fetchObjectMeta('image', $image);
	}

	//---------------------------------------------------------------------------
	
	public function createPageFromRow($row, $uri = NULL, $parent = NULL)
	{
		if (!$class = $row['type'])
		{
			$class = 'Page';
		}

		$page = $this->factory->manufacture($class, $row);
		
		if ($uri)
		{
			$page->setURI($uri);
		}
			
		if ($parent)
		{
			$page->setParent($parent);
		}
			
		if ($page->virtual)
		{
			$page = $page->fetchOverridePage($this);
		}
		
		return $page;
	}
	
	//---------------------------------------------------------------------------
	//
	// Protected Methods
	//
	//---------------------------------------------------------------------------
	
	public function fetchDesignAssetByID($table, $id, $select = '*')
	{
		if (($asset = @$this->_cache[$table][$id]) !== NULL)
		{
			return $asset;
		}
		
		$selectAll = ($select === '*');
		$select .= ',branch_status AS asset_branch_status';
		
		$db = $this->loadDB();

		$row = $db->selectRow($table, $select, 'id=?', $id);

		if ($row && ($row['asset_branch_status'] == ContentObject::branch_status_deleted))
		{
			$row = NULL;
		}

		if ($row)
		{
			unset($row['asset_branch_status']);
			$asset = $this->factory->manufacture($table, $row);
			if ($selectAll)
			{
				$this->_cache[$table][$id] = $asset;
			}
		}
		else
		{
			$this->_cache[$table][$id] = $asset = false;
		}

		return $asset;
	}

	//---------------------------------------------------------------------------
	
	public function fetchFinalDesignAssetByName($table, $nameCol, $name, $themeSlugOrID = NULL, $branch = 1, $select = '*')
	{
		// Retrieve a design asset by name in the specified theme and branch.
		// Theme may be specified by name (slug) or ID.
		// If a theme is specified, only that theme is considered. Ancestors of the specified theme are not considered.
		
		$cacheKey = $name . ($themeSlugOrID ? '_'.$themeSlugOrID : '') . ($branch ? '_'.$branch : '');

		if (($asset = @$this->_cache[$table][$cacheKey]) !== NULL)
		{
			return $asset;
		}

		if ($selectAll = ($select === '*'))
		{
			$select = "{{$table}}.*";
		}

		$select .= ",{{$table}}.branch_status AS asset_branch_status";
		
		if (empty($themeSlugOrID))
		{
			$themeSlugOrID = 0;
		}
					
		$db = $this->loadDB();

		$where = "{{$table}}.{$nameCol}=?";
		$bind[] = $name;
		
		if ($branch)
		{
			$where .= " AND {{$table}}.branch<=?";
			$bind[] = $branch;
		}
		
		if (SparkUtil::valid_int($themeSlugOrID))
		{
			$joins = NULL;
			$where .= " AND {{$table}}.theme_id=?";
		}
		else
		{
			$joins[] = array('table'=>'theme', 'conditions'=>array(array('leftField'=>'theme_id', 'rightField'=>'id', 'joinOp'=>'=')));
			$where .= ' AND {theme}.slug=?';
		}
		$bind[] = $themeSlugOrID;

		$sql = $db->buildSelect($table, $select, $joins, $where, "{{$table}}.theme_id DESC, {{$table}}.branch DESC", 1);
		$row = $db->query($sql, $bind)->row();
		
		if ($row && ($row['asset_branch_status'] == ContentObject::branch_status_deleted))
		{
			$row = NULL;
		}
		if ($row)
		{
			unset($row['asset_branch_status']);
			$asset = $this->factory->manufacture($table, $row);
			if ($selectAll)
			{
				$this->_cache[$table][$cacheKey] = $asset;
			}
		}
		else
		{
			$this->_cache[$table][$cacheKey] = $asset = false;
		}

		return $asset;
	}

	//---------------------------------------------------------------------------
	
	protected function fetchDesignAssetByName($table, $nameCol, $name, $theme = NULL, $branch = 1, $select = '*', $themeOverride = NULL)
	{
		// Retrieve a design asset by name in the specified theme and branch.
		// If a theme is specified, all ancestor themes in that theme's lineage are considered (i.e. theme inheritance is honored).
		// If asset is marked deleted in a non-production branch, additional queries may be required to locate the asset,
		// but this has no performance impact when searching the production branch only (i.e. $branch == 1).

		$cacheKey = $name . ($theme ? '_'.$theme->slug : '_0') . ($branch ? '_'.$branch : '');

		if (($asset = @$this->_cache[$table][$cacheKey]) !== NULL)
		{
			return $asset;
		}

		$db = $this->loadDB();

		$selectAll = ($select === '*');
		$select .= ',theme_id AS asset_theme_id,branch_status AS asset_branch_status';

		if ($theme)
		{
			$lineage = explode(',', $theme->lineage);
			$lineage[] = $theme->id;
		}
		else
		{
			$lineage[] = 0;
		}
		
		if (is_int($themeOverride))
		{
			$lineage[] = $themeOverride;
			$select .= ',' . $db->getFunction('cond')->condition("theme_id = {$themeOverride}", 2147483647, 'theme_id')->compile() . ' AS sort_by';
			$orderBy = 'sort_by DESC, branch DESC';
		}
		else
		{
			$orderBy = 'theme_id DESC, branch DESC';
		}

		while (true)
		{
			$where = $this->buildThemeSearchWhere($db, $table, $nameCol, $name, $lineage, $branch, $bind);
			$sql = $db->buildSelect($table, $select, NULL, $where, $orderBy, 1);
			$row = $db->query($sql, $bind)->row();

			if ($row && ($row['asset_branch_status'] == ContentObject::branch_status_deleted))
			{
				// should we restart search at theme's parent?
				
				$curThemeID = $row['asset_theme_id'];
				while (($pop = array_pop($lineage)) && ($pop != $curThemeID))
				{
				}
				if ($pop)
				{
					continue;
				}
				$row = NULL;
			}
			
			break;
		}

		if ($row)
		{
			unset($row['asset_theme_id']); unset($row['asset_branch_status']);
			$asset = $this->factory->manufacture($table, $row);
			if ($selectAll)
			{
				$this->_cache[$table][$cacheKey] = $asset;
			}
		}
		else
		{
			$this->_cache[$table][$cacheKey] = $asset = false;
		}

		return $asset;
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllDesignAssets($table, $nameCol, $theme = NULL, $branch = 1, $select = '*')
	{
		$selectAll = ($select === '*');
		$select .= ',branch_status AS asset_branch_status';

		if ($theme)
		{
			$lineage = explode(',', $theme->lineage);
			$lineage[] = $theme->id;
		}
		else
		{
			$lineage[] = 0;
		}

		$db = $this->loadDB();

		$where = $this->buildThemeSearchWhere($db, $table, $nameCol, NULL, $lineage, $branch, $bind);
		$sql = $db->buildSelect($table, $select, NULL, $where, 'theme_id DESC, branch DESC');

		$assets = array();

		foreach ($db->query($sql, $bind)->rows() as $row)
		{
			if (!isset($names[$name = $row[$nameCol]]))
			{
				$cacheKey = $name . ($theme ? '_'.$theme->slug : '') . ($branch ? '_'.$branch : '');

				if ($row['asset_branch_status'] != ContentObject::branch_status_deleted)
				{
					unset($row['asset_branch_status']);
					$assets[] = $asset = $this->factory->manufacture($table, $row);
					if ($selectAll)
					{
						$this->_cache[$table][$cacheKey] = $asset;
					}
				}
				else
				{
					$this->_cache[$table][$cacheKey] = false;
				}

				$names[$name] = true;
			}
		}

		return $assets;
	}

	//---------------------------------------------------------------------------
	
	public function fetchDesignAssetChain($table, $nameCol, $name, $theme = NULL, $branch = 1, $select = '*')
	{
		if ($selectAll = ($select === '*'))
		{
			$select = "{{$table}}.*";
		}

		$select .= ",{{$table}}.branch_status AS asset_branch_status";

		if ($theme)
		{
			$select .= ',{theme}.slug AS theme,{theme}.style_url AS theme_style_url,{theme}.script_url AS theme_script_url,{theme}.image_url AS theme_image_url';
			$lineage = explode(',', $theme->lineage);
			$lineage[] = $theme->id;
			$joins[] = array('type'=>'left', 'table'=>'theme', 'conditions'=>array(array('leftField'=>'theme_id', 'rightField'=>'id', 'joinOp'=>'=')));
		}
		else
		{
			$lineage[] = 0;
			$joins = NULL;
		}
		
		$rows = array();

		$db = $this->loadDB();

		$where = $this->buildThemeSearchWhere($db, $table, $nameCol, $name, $lineage, $branch, $bind);
		$sql = $db->buildSelect($table, $select, $joins, $where, "theme_id ASC, {{$table}}.branch DESC");

		$names = array();
		foreach ($db->query($sql, $bind)->rows() as $row)
		{
			if (!isset($names[$themeName = @$row['theme']]))
			{
				$names[$themeName] = true;
				if ($row['asset_branch_status'] != ContentObject::branch_status_deleted)
				{
					unset($row['asset_branch_status']);
					$rows[] = $row;
				}
			}
		}
		
		return $rows;
	}

	//---------------------------------------------------------------------------
	
	protected function buildThemeSearchWhere($db, $table, $nameCol, $name, $lineage, $branch, &$bind)
	{
		$bind = $lineage;
		if (count($lineage) === 1)
		{
			$where = "{{$table}}.theme_id=?";
		}
		else
		{
			$where = $db->buildFieldIn($table, 'theme_id', $bind);
		}
		
		if ($branch)
		{
			$bind[] = $branch;
			$where .= " AND {{$table}}.branch<=?";
		}

		if ($name)
		{
			$bind[] = $name;
			$where .= " AND {{$table}}.{$nameCol}=?";
		}

		return $where;
	}
	
	//---------------------------------------------------------------------------
	
	protected function fetchObjectUser($object, $table, $user)
	{
		if (!$object->{$user})
		{
			if ($object->{$user.'_id'})
			{
				if (($object->{$user} = @$this->_cache['user'][$object->{$user.'_id'}]) === NULL)
				{
					$db = $this->loadDB();
					$row = $db->selectRow('user', '*', 'id=?', $object->{$user.'_id'});
					$object->{$user} = $this->_cache['user'][$object->{$user.'_id'}] = ($row ? $this->factory->manufacture('User', $row) : false);
				}
			}
			else
			{
				$db = $this->loadDB();
				$joins[] = array('table'=>'user', 'conditions'=>array(array('leftField'=>"{$user}_id", 'rightField'=>'id', 'joinOp'=>'=')));
				$row = $db->selectJoinRow($table, '{user}.*', $joins, "{{$table}}.id=?", $object->id);
				$object->{$user.'_id'} = $row ? $row['id'] : 0;
				$object->{$user} = $this->_cache['user'][$object->{$user.'_id'}] = ($row ? $this->factory->manufacture('User', $row) : false);
			}
		}
		if ($object->{$user})
		{
			$object->{$user.'_name'} = $object->{$user}->name;
		}
		return $object->{$user};
	}

	//---------------------------------------------------------------------------
	
	protected function fetchObjectMeta($objType, $object)
	{
		if (!$object->meta)
		{
			if (($meta = @$this->_cache["{$objType}_meta"][$object->id]) !== NULL)
			{
				return $object->meta = $meta;
			}
	
			$db = $this->loadDB();
	
			if ($rows = $db->selectRows("{$objType}_meta", 'name, data', "{$objType}_id=?", $object->id))
			{
				foreach ($rows as $row)
				{
					$meta[$row['name']] = $row['data'];
				}
			}
			else
			{
				$meta = false;
			}
	
			$object->meta = $this->_cache["{$objType}_meta"][$object->id] = $meta;
		}
		return $object->meta;
	}

	//---------------------------------------------------------------------------
	
	protected function getSlugs($uri)
	{
		// ToDo: Sanity-check the length if the URI and the number of slugs for malicious DOS attempts.

		$slugs = explode('/', trim($uri, '/'));

		if (empty($slugs[0]))
		{
			array_shift($slugs);
		}
		
		return $slugs;
	}
	
	//---------------------------------------------------------------------------
	
	protected function buildCategoriesJoin($type, &$joins)
	{
		$joins[] = array('table'=>"{$type}_category", 'conditions'=>array(array('leftField'=>'id', 'rightField'=>"{$type}_id", 'joinOp'=>'=')));
		$joins[] = array('table'=>'category', 'conditions'=>array(array('leftField'=>'category_id', 'rightField'=>'id', 'joinOp'=>'=')));
	}
	
	//---------------------------------------------------------------------------
	//
	// Protected Methods
	//
	//---------------------------------------------------------------------------
	
	protected function makePageSpec($uri)
	{
		// create a page spec from a uri
		
		$slugs = $this->getSlugs($uri);

		// direct uri (not a permlink)
		
		if (!isset($slugs[0]) || !is_numeric($slugs[0]))
		{
			return array('uri' => $uri, 'slugs' => $slugs);
		}
		
		$id = $slugs[0];
		
		if (($page = @$this->_cache['page'][$id]) !== NULL)
		{
			return array('uri' => $page->uri(), 'id'=>$page->id, 'level'=>$page->level);
		}

		$db = $this->loadDB();
		if (!$row = $db->selectRow('page', 'level', 'id=?', $id))
		{
			return false;
		}
		$pageSpec = array('id'=>$id, 'level'=>$row['level']);

		// permlink: /pageID | /pageID/title
		
		if (!isset($slugs[1]) || !is_numeric($slugs[1]))
		{
			if (count($slugs) > 2)
			{
				return false;
			}
			if (isset($slugs[1]))		// we disallow incorrect or extra slugs to prevent DOS vulnerability on page cache
			{
				if (!$page = $this->fetchPage($pageSpec))
				{
					return false;
				}
				if ($page->slug != $slugs[1])
				{
					return false;
				}
			}
			return $pageSpec;
		}
		
		// permlink to category page: pageID/categoryID | pageID/categoryID/cat_slug/title

		$c = count($slugs);

		if (($c != 2) && ($c != 4))
		{
			return false;
		}

		if (!$page = $this->fetchPage($pageSpec))
		{
			return false;
		}

		if (!$category = $this->fetchCategory(intval($slugs[1])))
		{
			return false;
		}

		if (isset($slugs[2]))		// we disallow incorrect or extra slugs to prevent DOS vulnerability on page cache
		{
			if ($slugs[2] != $this->_category_trigger)
			{
				return false;
			}
			if (!isset($slugs[3]))
			{
				return false;
			}
			if ($category->slug != $slugs[3])
			{
				return false;
			}
		}

		return array('uri' => $page->uri() . $this->fetchCategoryURI($category));
	}
	
	//---------------------------------------------------------------------------

	protected function buildSelectTemplate($table, $fields)
	{
		$sql = '';
		foreach ($fields as $field)
		{
			$sql .= "{$table}?.{$field} AS _?_{$field}, ";
		}
		
		$sql = rtrim($sql, ', ');
		
		return $sql;
	}
	
	//---------------------------------------------------------------------------

	protected function bindTemplate($template, $bind)
	{
		return str_replace('?', $bind, $template);
	}

	//---------------------------------------------------------------------------
	
	protected function buildStatusIn($db, $table, &$status)
	{
		if (is_string($status))
		{
			$status = explode(',', $status);
		}
		$status = array_map(array('_Page', 'textToStatus'), $status);

		return $db->buildFieldIn($table, 'status', $status);
	}

	//---------------------------------------------------------------------------

	protected function filterOrder($order)
	{
		switch ($order)
		{
			case 'ASC':
			case 'DESC':
				return true;
			default:
				return false;
		}
	}

	protected function buildOrderBy($sort, $order, $filter)
	{
		$order = trim(strtoupper($order));
		
		if ($order === 'RAND')
		{
			return $order;
		}
		
		$sort = array_filter(array_map(array($this, $filter), explode(',', strtolower(str_replace(' ', '', $sort)))));

		if (empty($sort))
		{
			return '';
		}
		
		$order = array_filter(explode(',', str_replace(' ', '', $order)), array($this, 'filterOrder'));

		return implode(', ', array_multiplex($sort, array_stretch($order, count($sort)), ' '));
	}

	//---------------------------------------------------------------------------
	
	protected function getCategoryFields($fields)
	{
		if ($fields === '*')
		{
			return array
			(
				'id', 'slug', 'title', 'level', 'position', 'count', 'parent_id',
			);
		}

		return self::makeList($fields);
	}
	
	//---------------------------------------------------------------------------
	
	protected function getPageFields($fields)
	{
		if ($fields === '*')
		{
			return array
			(
				'id', 'type', 'slug', 'title', 'breadcrumb', 'status',
				'level', 'position', 'cacheable', 'secure', 'magical',
				'created', 'edited', 'published',
				'template_name',
				'parent_id', 'author_id', 'editor_id',
			);
		}

		return self::makeList($fields);
	}
	
	//---------------------------------------------------------------------------
}
