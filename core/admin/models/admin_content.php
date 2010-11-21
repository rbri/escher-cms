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

require(escher_core_dir.'/publish/models/publish_content.php');

//------------------------------------------------------------------------------

class _AdminContentModel extends _PublishContentModel
{
	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function addCategory($category)
	{
		$db = $this->loadDB();
	
		$row = array
		(
			'slug' => strtolower($category->slug),
			'title' => $category->title,
			'level' => $category->level,
			'position' => $category->position ? $category->position : 0,
			'count' => $category->count ? $category->count : 0,
			'parent_id' => $category->parent_id,
		);

		$db->begin();

		try
		{
			$db->insertRow('category', $row);
			$category->id = $db->lastInsertID();
			
			// if explicit position was not provided, ensure this category gets positioned after all its siblings
			// assumes the following always holds: postion <= id

			if (!$category->position)
			{
				$db->updateRows('category', array('position' => $db->getFunction('literal')->literal('id')), 'id=?', $category->id);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateCategory($category)
	{
		$db = $this->loadDB();
	
		$row = array
		(
			'title' => $category->title,
			'slug' => $category->slug,
		);

		$db->updateRows('category', $row, 'id=?', $category->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteCategoryByID($categoryID, $deleteChildren = true)
	{
		// delete a category and optionally delete its children as well

		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			if ($deleteChildren)
			{
				$categoryIDs = $this->findDescendants('category', $categoryID, $db);
			}
			$categoryIDs[] = $categoryID;

			$db->deleteRows('category', $db->buildFieldIn('category', 'id', $categoryIDs), $categoryIDs);
			$db->deleteRows('page_category', $db->buildFieldIn('page_category', 'category_id', $categoryIDs), $categoryIDs);
			$db->deleteRows('block_category', $db->buildFieldIn('block_category', 'category_id', $categoryIDs), $categoryIDs);
			$db->deleteRows('image_category', $db->buildFieldIn('image_category', 'category_id', $categoryIDs), $categoryIDs);
			$db->deleteRows('file_category', $db->buildFieldIn('file_category', 'category_id', $categoryIDs), $categoryIDs);
			$db->deleteRows('link_category', $db->buildFieldIn('link_category', 'category_id', $categoryIDs), $categoryIDs);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();

		return count($categoryIDs);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllCategories($sort = false)
	{
		$db = $this->loadDB();
		
		// we make the assumption that a child category may not be defined before its parent
		
		$allCategories = array();
		$rootCategories = array();

		foreach ($db->query($db->buildSelect('category', '*', NULL, NULL, 'id ASC, title'))->rows() as $row)
		{
			$allCategories[$row['id']] = $category = $this->factory->manufacture('Category', $row);
			if (!$row['parent_id'])
			{
				$rootCategories[$row['id']] = $category;
			}
			else
			{
				$allCategories[$row['parent_id']]->children[] = $category;
			}
		}
		
		unset($allCategories);
		
		if ($sort)
		{
			$this->sortCategories($rootCategories);
		}
		
		return $rootCategories;
	}

	//---------------------------------------------------------------------------
	
	public function fetchCategoryNames($hierarchical = true)
	{
		if ($hierarchical)
		{
			$categories = $this->fetchAllCategories(true);
			$names = $this->categoriesToHierList($categories);
		}
		
		else
		{
			$db = $this->loadDB();
			
			$names = array();
			foreach ($db->selectRows('category', 'id,title') as $row)
			{
				$names[$row['id']] = $row['title'];
			}
			asort($names);
		}

		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function categoriesToHierList($categories, $level = 0)
	{
		$list = array();
		
		foreach ($categories as $category)
		{
			$list[$category->id] = str_repeat('  ', $level) . $category->title;
			
			if ($category->children)
			{
				$list += $this->categoriesToHierList($category->children, $level+1);
			}
		}
		
		return $list;
	}
	
	//---------------------------------------------------------------------------

	public function fetchCategoryDescendents($parent, $db = NULL)
	{
		// recursively fetch the bare structure of the subtree of the specified root node
		
		if (!$db)
		{
			$db = $this->loadDB();
		}
		
		$result = $db->query($db->buildSelect('category', 'id, title', NULL, 'parent_id=?', 'title ASC'), $parent->id);

		foreach ($result->rows() as $row)
		{
			$child = $this->factory->manufacture('Category', $row);
			$this->fetchCategoryDescendents($child, $db);
			$parent->children[$row['id']] = $child;
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchCategoriesByID($ids, $sort = false)
	{
		$cache =& $this->_cache['categories'];

		$db = $this->loadDB();

		$where = $db->buildFieldIn('category', 'id', $ids);
		$bind = $ids;
		$rows = $db->selectRows('category', '*', $where, $bind);

		$categories = array();
		foreach ($rows as $row)
		{
			$category = $this->factory->manufacture('Category', $row);
			if (!isset($cache[$category->id]))
			{
				$cache[$category->id] = $category;
			}
			if (!isset($cache[$category->slug]))
			{
				$cache[$category->slug] = $category;
			}
			$categories[$category->id] = $category;
		}
		
		if ($sort)
		{
			$this->sortCategories($categories);
		}
		return $categories;
	}

	//---------------------------------------------------------------------------
	
	public function addModel($pageModel)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'name' => $pageModel->name,
			'type' => $pageModel->type,
			'status' => $pageModel->status,
			'magical' => $pageModel->magical ? true : false,
			'cacheable' => isset($pageModel->cacheable) ? $pageModel->cacheable : _Page::Cacheable_inherit,
			'secure' => isset($pageModel->secure) ? $pageModel->secure : _Page::Secure_inherit,
			'template_name' => $pageModel->template_name,
			'created' => $now,
			'edited' => $now,
			'author_id' => $pageModel->author_id,
			'editor_id' => $pageModel->editor_id ? $pageModel->editor_id : $pageModel->author_id,
		);

		$db->begin();

		try
		{
			$db->insertRow('model', $row);
			$pageModel->id = $db->lastInsertID();
			$this->updateModelCategories($pageModel);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateModel($pageModel, $updateCategories = true)
	{
		$db = $this->loadDB();
		$now = self::now();
		
		// need an efficient way to update publication date if being published for first time
	
		$row = array
		(
			'name' => $pageModel->name,
			'type' => $pageModel->type,
			'status' => $pageModel->status,
			'magical' => $pageModel->magical ? true : false,
			'cacheable' => $pageModel->cacheable,
			'secure' => $pageModel->secure,
			'template_name' => $pageModel->template_name,
			'edited' => $now,
			'editor_id' => $pageModel->editor_id,
		);

		$db->begin();

		try
		{
			$db->updateRows('model', $row, 'id=?', $pageModel->id);
			if ($updateCategories)
			{
				$this->updateModelCategories($pageModel);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function deleteModelByID($modelID)
	{
		// delete a model and all related objects (page metadata, parts, and categories)

		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('model_meta', 'model_id=?', $modelID);
			$db->deleteRows('model_part', 'model_id=?', $modelID);
			$db->deleteRows('model_category', 'model_id=?', $modelID);
			$db->deleteRows('model', 'id=?', $modelID);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllModels($sort = false)
	{
		$db = $this->loadDB();

		$models = array();
		
		foreach ($db->query($db->buildSelect('model', '*', NULL, NULL, 'name'))->rows() as $row)
		{
			$models[$row['id']] = $this->factory->manufacture('PageModel', $row);
		}

		return $models;
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllModelNames($sort = false)
	{
		$db = $this->loadDB();

		$names = array();
		foreach ($db->selectRows('model', 'id,name') as $row)
		{
			$names[$row['id']] = $row['name'];
		}

		asort($names);

		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchModelByID($id)
	{
		$db = $this->loadDB();

		if (!$row = $db->selectRow('model', '*', 'id=?', $id))
		{
			return false;
		}
		
		return $this->factory->manufacture('PageModel', $row);
	}

	//---------------------------------------------------------------------------
	
	public function fetchModelByName($name)
	{
		$db = $this->loadDB();

		if (!$row = $db->selectRow('model', '*', 'name=?', $name))
		{
			return false;
		}
		
		return $this->factory->manufacture('PageModel', $row);
	}

	//---------------------------------------------------------------------------

	public function modelExists($name)
	{
		return ($this->fetchModelByName($name) !== false);
	}

	//---------------------------------------------------------------------------
	
	public function updateModelCategories($pageModel)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('model_category', 'model_id=?', $pageModel->id);
			
			if (!empty($pageModel->categories))
			{
				$categories = is_array($pageModel->categories) ? $pageModel->categories : explode(',', $pageModel->categories);
				foreach($categories as $category)
				{
					if ($category instanceof category)
					{
						$db->insertRow('model_category', array('model_id'=>$pageModel->id, 'category_id'=>$category->id));
					}
					elseif (is_numeric($category) && ($category = intval($category)))
					{
						$db->insertRow('model_category', array('model_id'=>$pageModel->id, 'category_id'=>$category));
					}
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchModelCategories($pageModel, $sort = false)
	{
		if (!$pageModel->categories)
		{
			$pageModel->categories = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildCategoriesJoin('model', $joins);
	
			$cache =& $this->_cache['categories'];
			foreach ($db->selectJoinRows('model', '{category}.*', $joins, '{model}.id=?', $pageModel->id) as $row)
			{
				$category = $this->factory->manufacture('Category', $row);
				if (!isset($cache[$category->id]))
				{
					$cache[$category->id] = $category;
				}
				if (!isset($cache[$category->slug]))
				{
					$cache[$category->slug] = $category;
				}
				$pageModel->categories[$category->id] = $category;
			}
		}

		if ($sort)
		{
			$this->sortCategories($pageModel->categories);
		}
		return $pageModel->categories;
	}

	//---------------------------------------------------------------------------
	
	public function updateModelMeta($modelID, $meta)
	{
		$db = $this->loadDB();
	
		foreach ($meta as $key=>$val)
		{
			$val = trim($val);
			$db->upsertRow('model_meta', array('model_id'=>$modelID, 'name'=>$key, 'data'=>$val), 'model_id=? AND name=?', array($modelID, $key));
		}
	}

	//---------------------------------------------------------------------------
	
	public function deleteModelMeta($modelID, $names)
	{
		$db = $this->loadDB();

		$bind = array_merge(array($modelID), $names);

		$db->deleteRows('model_meta', 'model_id=? AND ' . $db->buildFieldIn('model_meta', 'name', $names), $bind);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllModelMetaNames($pageModel)
	{
		$db = $this->loadDB();

		$metaNames = array();
		
		foreach ($db->selectRows('model_meta', 'name', 'model_id=?', $pageModel->id) as $row)
		{
			$metaNames[$row['name']] = $row['name'];
		}
		
		asort($metaNames);

		return $metaNames;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchModelMeta($pageModel)
	{
		if (!$pageModel->meta)
		{
			$db = $this->loadDB();
	
			if ($rows = $db->selectRows('model_meta', 'name, data', 'model_id=?', $pageModel->id))
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
	
			$pageModel->meta = $meta;
		}
		return $pageModel->meta;
	}

	//---------------------------------------------------------------------------
	
	public function updateModelPart($part)
	{
		$db = $this->loadDB();

		$part->name = strtolower($part->name);

		$row = array
		(
			'name' => $part->name,
			'position' => $part->position,
			'type' => $part->type,
			'validation' => $part->validation,
			'content' => $part->content,
			'content_html' => $part->content_html,
			'filter_id' => $part->filter_id ? $part->filter_id : 0,
			'model_id' => $part->model_id,
		);

		if ($row['position'] === NULL)
		{
			unset($row['position']);
		}
		if ($row['type'] === NULL)
		{
			unset($row['type']);
		}
		if ($row['validation'] === NULL)
		{
			unset($row['validation']);
		}

		$db->upsertRow('model_part', $row, 'name=? AND model_id=?', array($part->name, $part->model_id));
	}

	//---------------------------------------------------------------------------
	
	public function deleteModelParts($modelID, $names)
	{
		$db = $this->loadDB();

		$bind = array_merge(array($modelID), $names);

		$db->deleteRows('model_part', 'model_id=? AND ' . $db->buildFieldIn('model_part', 'name', $names), $bind);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllModelPartNames($pageModel)
	{
		$db = $this->loadDB();

		$partNames = array();
		
		foreach ($db->selectRows('model_part', 'name', 'model_id=?', $pageModel->id) as $row)
		{
			$partNames[$row['name']] = $row['name'];
		}
		
		asort($partNames);

		return $partNames;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchAllModelParts($pageModel)
	{
		$db = $this->loadDB();

		$parts = array();
		
		foreach ($db->query($db->buildSelect('model_part', '*', NULL, 'model_id=?', 'position'), $pageModel->id)->rows() as $row)
		{
			$parts[$row['name']] = $this->factory->manufacture('Part', $row);
		}
		
		return $parts;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchModelAuthor($model)
	{
		return $this->fetchObjectUser($model, 'model', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchModelEditor($model)
	{
		return $this->fetchObjectUser($model, 'model', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function addPage($page)
	{
		$db = $this->loadDB();
		$now = self::now();
		$never = self::never();
	
		$row = array
		(
			'level' => $page->level,
			'position' => $page->position ? $page->position : 0,
			'title' => $page->title,
			'slug' => strtolower($page->slug),
			'breadcrumb' => $page->breadcrumb,
			'type' => get_class($page),
			'status' => $page->status,
			'magical' => $page->magical ? true : false,
			'cacheable' => isset($page->cacheable) ? $page->cacheable : _Page::Cacheable_inherit,
			'secure' => isset($page->secure) ? $page->secure : _Page::Secure_inherit,
			'template_name' => $page->template_name,
			'created' => $now,
			'edited' => $now,
			'published' => ($page->status == _Page::Status_published || ($page->status == _Page::Status_sticky)) ? $now : $never,
			'author_id' => $page->author_id,
			'editor_id' => $page->editor_id ? $page->editor_id : $page->author_id,
			'parent_id' => $page->parent_id ? $page->parent_id : 0,
			'model_id' => $page->model_id ? $page->model_id : 0,
		);

		$db->begin();

		try
		{
			$db->insertRow('page', $row);
			$page->id = $db->lastInsertID();
			$this->updatePageCategories($page);
			
			// if explicit position was not provided, ensure this page gets positioned after all its siblings
			// assumes the following always holds: postion <= id

			if (!$page->position)
			{
				$db->updateRows('page', array('position' => $db->getFunction('literal')->literal('id')), 'id=?', $page->id);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updatePage($page, $updateCategories = true)
	{
		$db = $this->loadDB();
		$now = self::now();
		$never = self::never();
		
		$row = array
		(
			'title' => $page->title,
			'slug' => strtolower($page->slug),
			'breadcrumb' => $page->breadcrumb,
			'type' => get_class($page),
			'status' => $page->status,
			'magical' => $page->magical ? true : false,
			'cacheable' => $page->cacheable,
			'secure' => $page->secure,
			'template_name' => $page->template_name,
			'edited' => $now,
		);

		if (isset($page->position))
		{
			$row['position'] = $page->position;
		}
		if ($page->editor_id)
		{
			$row['editor_id'] = $page->editor_id;
		}
		
		$db->begin();

		try
		{
			$db->updateRows('page', $row, 'id=?', $page->id);
			if ($updateCategories)
			{
				$this->updatePageCategories($page);
			}
			if ($page->status == _Page::Status_published || ($page->status == _Page::Status_sticky))
			{
				$db->updateRows('page', array('published' => $now), 'published=? AND id=?', array($never, $page->id));
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updatePageDates($page, $dates)
	{
		$db = $this->loadDB();
		$row = array();
	
		foreach (array('created', 'edited', 'published') as $for)
		{
			if (isset($dates[$for]))
			{
				$row[$for] = $dates[$for];
			}
		}

		$db->updateRows('page', $row, 'id=?', $page->id);
	}

	//---------------------------------------------------------------------------
	
	public function deletePageByID($pageID, $deleteChildren = true)
	{
		// delete a page and all related objects (page metadata, parts, and categories)
		// optionally do the same for all its children

		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			if ($deleteChildren)
			{
				$pageIDs = $this->findDescendants('page', $pageID, $db);
			}
			$pageIDs[] = $pageID;

			$db->deleteRows('page_meta', $db->buildFieldIn('page_meta', 'page_id', $pageIDs), $pageIDs);
			$db->deleteRows('page_part', $db->buildFieldIn('page_part', 'page_id', $pageIDs), $pageIDs);
			$db->deleteRows('page_category', $db->buildFieldIn('page_category', 'page_id', $pageIDs), $pageIDs);
			$db->deleteRows('page', $db->buildFieldIn('page', 'id', $pageIDs), $pageIDs);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();

		return count($pageIDs);
	}

	//---------------------------------------------------------------------------
	
	public function fetchSimplePageByID($id)
	{
		$db = $this->loadDB();

		if (!$row = $db->selectRow('page', '*', 'id=?', $id))
		{
			return false;
		}
		
		return $this->createPageFromRow($row);
	}

	//---------------------------------------------------------------------------

	public function fetchPageDescendents($parent, $db = NULL)
	{
		// recursively fetch the bare structure of the subtree of the specified root node
		
		if (!$db)
		{
			$db = $this->loadDB();
		}
		
		$result = $db->query($db->buildSelect('page', 'id, title, type', NULL, 'parent_id=?', 'position, created DESC'), $parent->id);

		foreach ($result->rows() as $row)
		{
			$child = $this->createPageFromRow($row);
			$this->fetchPageDescendents($child, $db);
			$parent->children[$row['id']] = $child;
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchAllPageCategoryIDs($page)
	{
		$db = $this->loadDB();

		$categoryIDs = array();
		
		foreach ($db->selectRows('page_category', 'category_id AS id', 'page_id=?', $page->id) as $row)
		{
			$categoryIDs[$row['id']] = $row['id'];
		}
		
		return $categoryIDs;
	}
	
	//---------------------------------------------------------------------------
	
	public function deletePageCategories($pageID, $ids)
	{
		$db = $this->loadDB();

		$bind = array_merge(array($pageID), $ids);

		$db->deleteRows('page_category', 'page_id=? AND ' . $db->buildFieldIn('page_category', 'category_id', $ids), $bind);
	}

	//---------------------------------------------------------------------------
	
	public function updatePageCategories($page)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('page_category', 'page_id=?', $page->id);
			
			if (!empty($page->categories))
			{
				$categories = is_array($page->categories) ? $page->categories : explode(',', $page->categories);
				foreach($categories as $category)
				{
					if ($category instanceof category)
					{
						$db->insertRow('page_category', array('page_id'=>$page->id, 'category_id'=>$category->id));
					}
					elseif (is_numeric($category) && ($category = intval($category)))
					{
						$db->insertRow('page_category', array('page_id'=>$page->id, 'category_id'=>$category));
					}
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchPageCategories($page, $sort = false)
	{
		if (!$page->categories)
		{
			$page->categories = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildCategoriesJoin('page', $joins);
	
			$cache =& $this->_cache['categories'];
			foreach ($db->selectJoinRows('page', '{category}.*', $joins, '{page}.id=?', $page->id) as $row)
			{
				$category = $this->factory->manufacture('Category', $row);
				if (!isset($cache[$category->id]))
				{
					$cache[$category->id] = $category;
				}
				if (!isset($cache[$category->slug]))
				{
					$cache[$category->slug] = $category;
				}
				$page->categories[$category->id] = $category;
			}
		}

		if ($sort)
		{
			$this->sortCategories($page->categories);
		}
		return $page->categories;
	}

	//---------------------------------------------------------------------------
	
	public function createPageFromModel($pageModelID, $parentID)
	{
		if (!$pageModel = $this->fetchModelByID($pageModelID))
		{
			return false;
		}
		
		$this->fetchModelCategories($pageModel, true);
		$pageModel->parts = $this->fetchAllModelParts($pageModel);
		$this->fetchModelMeta($pageModel);

		$modelFields = array
		(
			'type' => $pageModel->type,
			'status' => $pageModel->status,
			'magical' => $pageModel->magical,
			'cacheable' => $pageModel->cacheable,
			'secure' => $pageModel->secure,
			'template_name' => $pageModel->template_name,
			'meta' => $pageModel->meta,
			'categories' => $pageModel->categories,
			'parts' => $pageModel->parts,
			'parent_id' => $parentID,
			'model_id' => $pageModelID,
		);

		return $this->factory->manufacture($pageModel->type, $modelFields);
	}

	//---------------------------------------------------------------------------
	
	public function createPageFromPage($pageID, $parentID)
	{
		if (!$page = $this->fetchSimplePageByID($pageID))
		{
			return false;
		}

		// copy fields from model page
		
		$page->parent_id = $parentID;
		$this->fetchPageCategories($page, true);
		$page->parts = $this->fetchAllPageParts($page);
		$this->fetchPageMeta($page);

		// reset fields that should not be inherited

		$page->id = NULL;
		$page->level = NULL;
		$page->position = NULL;
		$page->title = NULL;
		$page->slug = NULL;
		$page->breadcrumb = NULL;
		$page->created = NULL;
		$page->edited = NULL;
		$page->author_id = NULL;
		$page->editor_id = NULL;
		$page->published = NULL;

		return $page;
	}

	//---------------------------------------------------------------------------

	public function fetchChildPageID($parentID, $slug)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('page', 'id', 'parent_id=? AND slug=?', array($parentID, $slug));
		return isset($row['id']) ? $row['id'] : NULL;
	}

	//---------------------------------------------------------------------------

	public function childPageExists($parentID, $slug)
	{
		return ($this->fetchChildPageID($parentID, $slug) != 0);
	}

	//---------------------------------------------------------------------------
	
	public function rootPageExists()
	{
		$db = $this->loadDB();
		return ($db->countRows('page', 'parent_id=0') != 0);
	}

	//---------------------------------------------------------------------------
	
	public function addPageMeta($pageID, $meta)
	{
		$db = $this->loadDB();
	
		$rows = array();
		foreach ($meta as $key=>$val)
		{
			$rows[] = array('page_id'=>$pageID, 'name'=>$key, 'data'=>$val);
		}

		$db->insertRows('page_meta', $rows);
	}

	//---------------------------------------------------------------------------
	
	public function updatePageMeta($pageID, $meta)
	{
		$db = $this->loadDB();
	
		foreach ($meta as $key=>$val)
		{
			$db->upsertRow('page_meta', array('page_id'=>$pageID, 'name'=>$key, 'data'=>trim($val)), 'page_id=? AND name=?', array($pageID, $key));
		}
	}

	//---------------------------------------------------------------------------
	
	public function deletePageMeta($pageID, $names)
	{
		$db = $this->loadDB();

		$bind = array_merge(array($pageID), $names);

		$db->deleteRows('page_meta', 'page_id=? AND ' . $db->buildFieldIn('page_meta', 'name', $names), $bind);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllPageMetaNames($page)
	{
		$db = $this->loadDB();

		$metaNames = array();
		
		foreach ($db->selectRows('page_meta', 'name', 'page_id=?', $page->id) as $row)
		{
			$metaNames[$row['name']] = $row['name'];
		}
		
		asort($metaNames);

		return $metaNames;
	}
	

	//---------------------------------------------------------------------------
	
	public function addPagePart($part)
	{
		$db = $this->loadDB();
	
		$part->name = strtolower($part->name);
		
		$row = array
		(
			'name' => $part->name,
			'position' => $part->position,
			'type' => $part->type,
			'validation' => $part->validation,
			'content' => $part->content,
			'content_html' => $part->content_html,
			'filter_id' => $part->filter_id,
			'page_id' => $part->page_id,
		);
		
		if ($row['position'] === NULL)
		{
			unset($row['position']);
		}
		if ($row['type'] === NULL)
		{
			unset($row['type']);
		}
		if ($row['validation'] === NULL)
		{
			unset($row['validation']);
		}

		$db->insertRow('page_part', $row);

		$part->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updatePagePart($part)
	{
		$db = $this->loadDB();

		$part->name = strtolower($part->name);

		$row = array
		(
			'name' => $part->name,
			'position' => $part->position,
			'type' => $part->type,
			'validation' => $part->validation,
			'content' => $part->content,
			'content_html' => $part->content_html,
			'filter_id' => $part->filter_id ? $part->filter_id : 0,
			'page_id' => $part->page_id,
		);

		if ($row['position'] === NULL)
		{
			unset($row['position']);
		}
		if ($row['type'] === NULL)
		{
			unset($row['type']);
		}
		if ($row['validation'] === NULL)
		{
			unset($row['validation']);
		}

		$db->upsertRow('page_part', $row, 'name=? AND page_id=?', array($part->name, $part->page_id));
	}

	//---------------------------------------------------------------------------
	
	public function deletePageParts($pageID, $names)
	{
		$db = $this->loadDB();

		$bind = array_merge(array($pageID), $names);

		$db->deleteRows('page_part', 'page_id=? AND ' . $db->buildFieldIn('page_part', 'name', $names), $bind);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllPageParts($page)
	{
		$db = $this->loadDB();

		$parts = array();
		
		foreach ($db->query($db->buildSelect('page_part', '*', NULL, 'page_id=?', 'position'), $page->id)->rows() as $row)
		{
			$parts[$row['name']] = $this->factory->manufacture('Part', $row);
		}
		
		return $parts;
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchAllPagePartNames($page)
	{
		$db = $this->loadDB();

		$partNames = array();
		
		foreach ($db->selectRows('page_part', 'name', 'page_id=?', $page->id) as $row)
		{
			$partNames[$row['name']] = $row['name'];
		}
		
		asort($partNames);

		return $partNames;
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
	
	public function addBlock($block)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'name' => $block->name,
			'title' => $block->title ? $block->title : '',
			'content' => $block->content,
			'content_html' => $block->content_html,
			'created' => $now,
			'edited' => $now,
			'author_id' => $block->author_id,
			'editor_id' => $block->editor_id ? $block->editor_id : $block->author_id,
			'filter_id' => $block->filter_id,
		);

		$db->begin();

		try
		{
			$db->insertRow('block', $row);
			$block->id = $db->lastInsertID();
			$this->updateBlockCategories($block);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateBlock($block, $updateCategories = true)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'title' => $block->title ? $block->title : '',
			'content' => $block->content,
			'content_html' => $block->content_html,
			'edited' => $now,
			'editor_id' => $block->editor_id,
			'filter_id' => $block->filter_id,
		);

		$db->begin();

		try
		{
			$db->updateRows('block', $row, 'id=?', $block->id);
			if ($updateCategories)
			{
				$this->updateBlockCategories($block);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}

		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function deleteBlockByID($blockID)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('block', 'id=?', $blockID);
			$db->deleteRows('block_category', 'block_id=?', $blockID);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchBlockNames()
	{
		$db = $this->loadDB();

		$names = array();
		foreach ($db->selectRows('block', 'id,name') as $row)
		{
			$names[$row['id']] = $row['name'];
		}

		asort($names);

		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchBlockCategories($block, $sort = false)
	{
		if (!$block->categories)
		{
			$block->categories = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildCategoriesJoin('block', $joins);
	
			$cache =& $this->_cache['categories'];
			foreach ($db->selectJoinRows('block', '{category}.*', $joins, '{block}.id=?', $block->id) as $row)
			{
				$category = $this->factory->manufacture('Category', $row);
				if (!isset($cache[$category->id]))
				{
					$cache[$category->id] = $category;
				}
				if (!isset($cache[$category->slug]))
				{
					$cache[$category->slug] = $category;
				}
				$block->categories[$category->id] = $category;
			}
		}

		if ($sort)
		{
			$this->sortCategories($block->categories);
		}
		return $block->categories;
	}

	//---------------------------------------------------------------------------
	
	public function updateBlockCategories($block)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('block_category', 'block_id=?', $block->id);
			
			if (!empty($block->categories))
			{
				$categories = is_array($block->categories) ? $block->categories : explode(',', $block->categories);
				foreach($categories as $category)
				{
					if ($category instanceof category)
					{
						$db->insertRow('block_category', array('block_id'=>$block->id, 'category_id'=>$category->id));
					}
					elseif (is_numeric($category) && ($category = intval($category)))
					{
						$db->insertRow('block_category', array('block_id'=>$block->id, 'category_id'=>$category));
					}
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchBlockAuthor($block)
	{
		return $this->fetchObjectUser($block, 'block', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchBlockEditor($block)
	{
		return $this->fetchObjectUser($block, 'block', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function addImage($image)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'slug' => $image->slug,
			'ctype' => $image->ctype,
			'url' => $image->url ? $image->url : NULL,
			'width' => $image->width,
			'height' => $image->height,
			'alt' => $image->alt,
			'title' => $image->title,
			'content' => $image->content ? array($image->content) : '',
			'created' => $now,
			'edited' => $now,
			'author_id' => $image->author_id,
			'editor_id' => $image->editor_id ? $image->editor_id : $image->author_id,
			'theme_id' => $image->theme_id,
		);

		$db->begin();

		try
		{
			$db->insertRow('image', $row);
			$image->id = $db->lastInsertID();
			if ($image->theme_id == -1)
			{
				$this->updateImageCategories($image);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateImage($image, $updateCategories = true)
	{
		$db = $this->loadDB();
		$now = self::now();

		$row = array
		(
			'ctype' => $image->ctype,
			'url' => $image->url ? $image->url : NULL,
			'width' => $image->width,
			'height' => $image->height,
			'alt' => $image->alt,
			'title' => $image->title,
			'rev' => $db->getFunction('literal')->literal('rev+1'),
			'edited' => $now,
			'editor_id' => $image->editor_id,
		);
		
		// only update content if non-NULL
		
		if ($image->content)
		{
			$row['content'] = array($image->content);
		}

		$db->begin();

		try
		{
			$db->updateRows('image', $row, 'id=?', $image->id);
			if ($updateCategories && $image->theme_id == -1)
			{
				$this->updateImageCategories($image);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function deleteImageByID($imageID, $deleteCategories)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('image', 'id=?', $imageID);
			if ($deleteCategories)
			{
				$db->deleteRows('image_category', 'image_id=?', $imageID);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchImageNames($themeID = NULL)
	{
		$db = $this->loadDB();

		$where = 'theme_id=?';
		$bind = $themeID ? $themeID : 0;
		
		$names = array();
		foreach ($db->selectRows('image', 'id,slug', $where, $bind) as $row)
		{
			$names[$row['id']] = $row['slug'];
		}

		asort($names);

		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchImageCategories($image, $sort = false)
	{
		if (!$image->categories)
		{
			$image->categories = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildCategoriesJoin('image', $joins);
	
			$cache =& $this->_cache['categories'];
			foreach ($db->selectJoinRows('image', '{category}.*', $joins, '{image}.id=?', $image->id) as $row)
			{
				$category = $this->factory->manufacture('Category', $row);
				if (!isset($cache[$category->id]))
				{
					$cache[$category->id] = $category;
				}
				if (!isset($cache[$category->slug]))
				{
					$cache[$category->slug] = $category;
				}
				$image->categories[$category->id] = $category;
			}
		}

		if ($sort)
		{
			$this->sortCategories($image->categories);
		}
		return $image->categories;
	}

	//---------------------------------------------------------------------------
	
	public function updateImageCategories($image)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('image_category', 'image_id=?', $image->id);
			
			if (!empty($image->categories))
			{
				$categories = is_array($image->categories) ? $image->categories : explode(',', $image->categories);
				foreach($categories as $category)
				{
					if ($category instanceof category)
					{
						$db->insertRow('image_category', array('image_id'=>$image->id, 'category_id'=>$category->id));
					}
					elseif (is_numeric($category) && ($category = intval($category)))
					{
						$db->insertRow('image_category', array('image_id'=>$image->id, 'category_id'=>$category));
					}
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchImageAuthor($image)
	{
		return $this->fetchObjectUser($image, 'image', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchImageEditor($image)
	{
		return $this->fetchObjectUser($image, 'image', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function addFile($file)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'slug' => $file->slug,
			'ctype' => $file->ctype,
			'url' => $file->url ? $file->url : NULL,
			'title' => $file->title,
			'description' => $file->description,
			'status' => $file->status,
			'size' => $file->size ? $file->size : 0,
			'content' => $file->content ? array($file->content) : '',
			'download' => $file->download ? 1 : 0,
			'created' => $now,
			'edited' => $now,
			'author_id' => $file->author_id,
			'editor_id' => $file->editor_id ? $file->editor_id : $file->author_id,
		);

		$db->begin();

		try
		{
			$db->insertRow('file', $row);
			$file->id = $db->lastInsertID();
			$this->updateFileCategories($file);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateFile($file, $updateCategories = true)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'ctype' => $file->ctype,
			'url' => $file->url ? $file->url : NULL,
			'title' => $file->title,
			'description' => $file->description,
			'status' => $file->status,
			'download' => $file->download ? 1 : 0,
			'size' => $file->size ? $file->size : 0,
			'rev' => $db->getFunction('literal')->literal('rev+1'),
			'edited' => $now,
			'editor_id' => $file->editor_id,
		);

		// only update content if non-NULL
		
		if ($file->content)
		{
			$row['content'] = array($file->content);
		}

		$db->begin();

		try
		{
			$db->updateRows('file', $row, 'id=?', $file->id);
			if ($updateCategories)
			{
				$this->updateFileCategories($file);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function deleteFileByID($fileID)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('file', 'id=?', $fileID);
			$db->deleteRows('file_category', 'file_id=?', $fileID);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchFileNames()
	{
		$db = $this->loadDB();

		$names = array();
		foreach ($db->selectRows('file', 'id,slug') as $row)
		{
			$names[$row['id']] = $row['slug'];
		}

		asort($names);

		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchFileCategories($file, $sort = false)
	{
		if (!$file->categories)
		{
			$file->categories = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildCategoriesJoin('file', $joins);
	
			$cache =& $this->_cache['categories'];
			foreach ($db->selectJoinRows('file', '{category}.*', $joins, '{file}.id=?', $file->id) as $row)
			{
				$category = $this->factory->manufacture('Category', $row);
				if (!isset($cache[$category->id]))
				{
					$cache[$category->id] = $category;
				}
				if (!isset($cache[$category->slug]))
				{
					$cache[$category->slug] = $category;
				}
				$file->categories[$category->id] = $category;
			}
		}

		if ($sort)
		{
			$this->sortCategories($file->categories);
		}
		return $file->categories;
	}

	//---------------------------------------------------------------------------
	
	public function updateFileCategories($file)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('file_category', 'file_id=?', $file->id);
			
			if (!empty($file->categories))
			{
				$categories = is_array($file->categories) ? $file->categories : explode(',', $file->categories);
				foreach($categories as $category)
				{
					if ($category instanceof category)
					{
						$db->insertRow('file_category', array('file_id'=>$file->id, 'category_id'=>$category->id));
					}
					elseif (is_numeric($category) && ($category = intval($category)))
					{
						$db->insertRow('file_category', array('file_id'=>$file->id, 'category_id'=>$category));
					}
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchFileAuthor($file)
	{
		return $this->fetchObjectUser($file, 'file', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchFileEditor($file)
	{
		return $this->fetchObjectUser($file, 'file', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function addLink($link)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'name' => $link->name,
			'title' => $link->title,
			'description' => !empty($link->description) ? $link->description : '',
			'url' => $link->url,
			'created' => $now,
			'edited' => $now,
			'author_id' => $link->author_id,
			'editor_id' => $link->editor_id ? $link->editor_id : $link->author_id,
		);

		$db->begin();

		try
		{
			$db->insertRow('link', $row);
			$link->id = $db->lastInsertID();
			$this->updateLinkCategories($link);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function updateLink($link, $updateCategories = true)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'name' => $link->name,
			'title' => $link->title,
			'description' => $link->description,
			'url' => $link->url,
			'edited' => $now,
			'editor_id' => $link->editor_id,
		);

		$db->begin();

		try
		{
			$db->updateRows('link', $row, 'id=?', $link->id);
			if ($updateCategories)
			{
				$this->updateLinkCategories($link);
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}

		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function deleteLinkByID($linkID)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('link', 'id=?', $linkID);
			$db->deleteRows('link_category', 'link_id=?', $linkID);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchLinkNames()
	{
		$db = $this->loadDB();

		$names = array();
		foreach ($db->selectRows('link', 'id,name') as $row)
		{
			$names[$row['id']] = $row['name'];
		}

		asort($names);

		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchLinkCategories($link, $sort = false)
	{
		if (!$link->categories)
		{
			$link->categories = array();
			
			$db = $this->loadDB();
	
			$joins = array();
			$this->buildCategoriesJoin('link', $joins);
	
			$cache =& $this->_cache['categories'];
			foreach ($db->selectJoinRows('link', '{category}.*', $joins, '{link}.id=?', $link->id) as $row)
			{
				$category = $this->factory->manufacture('Category', $row);
				if (!isset($cache[$category->id]))
				{
					$cache[$category->id] = $category;
				}
				if (!isset($cache[$category->slug]))
				{
					$cache[$category->slug] = $category;
				}
				$link->categories[$category->id] = $category;
			}
		}

		if ($sort)
		{
			$this->sortCategories($link->categories);
		}
		return $link->categories;
	}

	//---------------------------------------------------------------------------
	
	public function updateLinkCategories($link)
	{
		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			$db->deleteRows('link_category', 'link_id=?', $link->id);
			
			if (!empty($link->categories))
			{
				$categories = is_array($link->categories) ? $link->categories : explode(',', $link->categories);
				foreach($categories as $category)
				{
					if ($category instanceof category)
					{
						$db->insertRow('link_category', array('link_id'=>$link->id, 'category_id'=>$category->id));
					}
					elseif (is_numeric($category) && ($category = intval($category)))
					{
						$db->insertRow('link_category', array('link_id'=>$link->id, 'category_id'=>$category));
					}
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();
	}

	//---------------------------------------------------------------------------
	
	public function fetchLinkAuthor($link)
	{
		return $this->fetchObjectUser($link, 'link', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchLinkEditor($link)
	{
		return $this->fetchObjectUser($link, 'link', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function addTheme($theme)
	{	
		$db = $this->loadDB();
		$now = self::now();

		$row = array
		(
			'slug' => strtolower($theme->slug),
			'title' => $theme->title,
			'style_url' => $theme->style_url,
			'script_url' => $theme->script_url,
			'image_url' => $theme->image_url,
			'created' => $now,
			'edited' => $now,
			'author_id' => $theme->author_id,
			'editor_id' => $theme->editor_id ? $theme->editor_id : $theme->author_id,
		);
		
		if ($theme->parent_id && $parent = $db->selectRow('theme', 'lineage', 'id=?', $theme->parent_id))
		{
			$row['lineage'] = $parent['lineage'] . ',' . $theme->parent_id;
			$row['parent_id'] = $theme->parent_id;
		}
		
		$db->insertRow('theme', $row);
		$theme->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateTheme($theme)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'title' => $theme->title,
			'slug' => $theme->slug,
			'style_url' => $theme->style_url,
			'script_url' => $theme->script_url,
			'image_url' => $theme->image_url,
			'edited' => $now,
			'editor_id' => $theme->editor_id,
		);

		$db->updateRows('theme', $row, 'id=?', $theme->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteThemeByID($themeID, $deleteChildren = true)
	{
		// delete a theme and all related objects (templates, snippets, tags, styles, scripts, and images)
		// optionally do the same for all its children

		$db = $this->loadDB();
		
		$db->begin();

		try
		{
			if ($deleteChildren)
			{
				$themeIDs = $this->findDescendants('theme', $themeID, $db);
			}
			$themeIDs[] = $themeID;

			$db->deleteRows('template', $db->buildFieldIn('template', 'theme_id', $themeIDs), $themeIDs);
			$db->deleteRows('snippet', $db->buildFieldIn('snippet', 'theme_id', $themeIDs), $themeIDs);
			$db->deleteRows('tag', $db->buildFieldIn('tag', 'theme_id', $themeIDs), $themeIDs);
			$db->deleteRows('style', $db->buildFieldIn('style', 'theme_id', $themeIDs), $themeIDs);
			$db->deleteRows('script', $db->buildFieldIn('script', 'theme_id', $themeIDs), $themeIDs);
			$db->deleteRows('image', $db->buildFieldIn('image', 'theme_id', $themeIDs), $themeIDs);
			$db->deleteRows('theme', $db->buildFieldIn('theme', 'id', $themeIDs), $themeIDs);
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}
		
		$db->commit();

		return count($themeIDs);
	}

	//---------------------------------------------------------------------------

	public function installTheme($authorID, $themePath, $themeBaseURL = '', $parentThemeID = 0, $createTheme = true)
	{
		// install a theme located at the specified path

		if (file_exists($themePath))
		{
			// install theme
			
			$themeName = basename($themePath);

			if (!empty($themeBaseURL))
			{
				$themeBaseURL .= '/' . $themeName;
				$themeStyleURL = $themeBaseURL . '/styles';
				$themeScriptURL = $themeBaseURL . '/scripts';
				$themeImageURL = $themeBaseURL . '/images';
			}
			else
			{
				$themeStyleURL = '';
				$themeScriptURL = '';
				$themeImageURL = '';
			}
			
			$theme =	$this->factory->manufacture
			(
				'Theme', array
				(
					'id'=>0,
					'title'=>$themeName,
					'style_url'=>$themeStyleURL, 'script_url'=>$themeScriptURL, 'image_url'=>$themeImageURL,
					'author_id'=>$authorID, 'parent_id'=>$parentThemeID,
				)
			);
			$theme->makeSlug();

			if ($createTheme)
			{
				$this->addTheme($theme);
			}

			// install templates

			if (file_exists("{$themePath}/templates"))
			{
				$path = new DirectoryIterator("{$themePath}/templates");
				foreach ($path as $file)
				{
					if ($file->isDot() || $file->isDir())
					{
						continue;
					}

					$fileName = $file->getFilename();
					if ($fileName[0] === '.')
					{
						continue;
					}
					
					$info = pathinfo($file->getPathname());
					$name = $info['filename'];
					$extension = $info['extension'];
					
					$content = file_get_contents("{$themePath}/templates/{$fileName}");
					
					$this->addTemplate
					(
						$this->factory->manufacture
						(
							'Template', array
							(
								'name'=>$name,
								'content'=>$content,
								'ctype'=>"text/{$extension}",
								'author_id'=>$authorID,
								'theme_id'=>$theme->id,
							)
						)
					);
				}
			}
			
			// install snippets
			
			if (file_exists("{$themePath}/snippets"))
			{
				$path = new DirectoryIterator("{$themePath}/snippets");
				foreach ($path as $file)
				{
					if ($file->isDot() || $file->isDir())
					{
						continue;
					}

					$fileName = $file->getFilename();
					if ($fileName[0] === '.')
					{
						continue;
					}

					if (substr($fileName, -5) !== '.snip')
					{
						continue;
					}
					
					$name = substr($fileName, 0, -5);
					
					$content = file_get_contents("{$themePath}/snippets/{$fileName}");
					
					$this->addSnippet
					(
						$this->factory->manufacture
						(
							'Snippet', array
							(
								'name'=>$name, 'content'=>$content,
								'author_id'=>$authorID,
								'theme_id'=>$theme->id,
							)
						)
					);
				}
			}
			
			// install tags
			
			if (file_exists("{$themePath}/tags"))
			{
				$path = new DirectoryIterator("{$themePath}/tags");
				foreach ($path as $file)
				{
					if ($file->isDot() || $file->isDir())
					{
						continue;
					}

					$fileName = $file->getFilename();
					if ($fileName[0] === '.')
					{
						continue;
					}

					if (substr($fileName, -4) !== '.tag')
					{
						continue;
					}
					
					$name = substr($fileName, 0, -4);
					
					$content = file_get_contents("{$themePath}/tags/{$fileName}");
					
					$this->addTag
					(
						$this->factory->manufacture
						(
							'Tag', array
							(
								'name'=>$name, 'content'=>$content,
								'author_id'=>$authorID,
								'theme_id'=>$theme->id,
							)
						)
					);
				}
			}
			
			// install styles
			
			if (file_exists("{$themePath}/styles"))
			{
				$path = new DirectoryIterator("{$themePath}/styles");
				foreach ($path as $file)
				{
					if ($file->isDot() || $file->isDir())
					{
						continue;
					}

					$fileName = $file->getFilename();
					if ($fileName[0] === '.')
					{
						continue;
					}
					
					$content = file_get_contents("{$themePath}/styles/{$fileName}");
					
					$style = $this->factory->manufacture
					(
						'Style', array
						(
							'slug'=>$fileName, 'ctype'=>'text/css', 'content'=>$content,
							'author_id'=>$authorID,
							'theme_id'=>$theme->id,
						)
					);
					$style->makeSlug();
					$this->addStyle($style);
				}
			}

			// install scripts
			
			if (file_exists("{$themePath}/scripts"))
			{
				$path = new DirectoryIterator("{$themePath}/scripts");
				foreach ($path as $file)
				{
					if ($file->isDot() || $file->isDir())
					{
						continue;
					}

					$fileName = $file->getFilename();
					if ($fileName[0] === '.')
					{
						continue;
					}
					
					$content = file_get_contents("{$themePath}/scripts/{$fileName}");
					
					$script = $this->factory->manufacture
					(
						'Script', array
						(
							'slug'=>$fileName, 'ctype'=>'application/javascript', 'content'=>$content,
							'author_id'=>$authorID,
							'theme_id'=>$theme->id,
						)
					);
					$script->makeSlug();
					$this->addScript($script);
				}
			}

			// install images
			
			if (file_exists("{$themePath}/images"))
			{
				$path = new DirectoryIterator("{$themePath}/images");
				foreach ($path as $file)
				{
					if ($file->isDot() || $file->isDir())
					{
						continue;
					}

					$fileName = $file->getFilename();
					if ($fileName[0] === '.')
					{
						continue;
					}
					
					if (($imageSize = getimagesize("{$themePath}/images/{$fileName}")) === false)
					{
						continue;	// skip image on error
					}
					$width = $imageSize[0];
					$height = $imageSize[1];
					$contentType = $imageSize['mime'];
					$content = file_get_contents("{$themePath}/images/{$fileName}");
					
					$image = $this->factory->manufacture
					(
						'Image', array
						(
							'slug'=>$fileName, 'ctype'=>$contentType, 'content'=>$content,
							'width'=>$width, 'height'=>$height, 'alt'=>'', 'title'=>'',
							'author_id'=>$authorID,
							'theme_id'=>$theme->id,
						)
					);
					$image->makeSlug();
					$this->addImage($image);
				}
			}
		}
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllThemes($sort = false)
	{
		$db = $this->loadDB();
		
		// we make the assumption that a child theme may not be defined before its parent
		
		$allThemes = array();
		$rootThemes = array();

		$joins[] = array('table'=>'user', 'type'=>'left', 'conditions'=>array(array('leftField'=>'author_id', 'rightField'=>'id', 'joinOp'=>'=')));
		foreach ($db->query($db->buildSelect('theme', '{theme}.*, {user}.name AS author_name', $joins, NULL, '{theme}.id ASC, title'))->rows() as $row)
		{
			$allThemes[$row['id']] = $theme = $this->factory->manufacture('Theme', $row);
			if (!$row['parent_id'])
			{
				$rootThemes[$row['id']] = $theme;
			}
			else
			{
				$allThemes[$row['parent_id']]->children[] = $theme;
			}
		}
		
		unset($allThemes);
		
		if ($sort)
		{
			$this->sortThemes($rootThemes);
		}
		
		return $rootThemes;
	}

	//---------------------------------------------------------------------------
	
	public function fetchThemeNames($hierarchical = true)
	{
		if ($hierarchical)
		{
			$themes = $this->fetchAllThemes(true);
			$names = $this->themesToHierList($themes);
		}
		
		else
		{
			$db = $this->loadDB();
			
			$names = array();
			foreach ($db->selectRows('theme', 'id,title') as $row)
			{
				$names[$row['id']] = $row['title'];
			}
			asort($names);
		}

		return $names;
	}

	//---------------------------------------------------------------------------

	public function fetchThemeDescendents($parent, $db = NULL)
	{
		// recursively fetch the bare structure of the subtree of the specified root node
		
		if (!$db)
		{
			$db = $this->loadDB();
		}
		
		$result = $db->query($db->buildSelect('theme', 'id, title', NULL, 'parent_id=?', 'title ASC'), $parent->id);

		foreach ($result->rows() as $row)
		{
			$child = $this->factory->manufacture('Theme', $row);
			$this->fetchThemeDescendents($child, $db);
			$parent->children[$row['id']] = $child;
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function fetchThemeAuthor($theme)
	{
		return $this->fetchObjectUser($theme, 'theme', 'author');
	}

	//---------------------------------------------------------------------------

	public function themeExists($slug)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('theme', 'id', 'slug=?', $slug);
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	private static function compareThemes($t1, $t2)
	{
		return strcmp($t1->title, $t2->title);
	}
	
	public function sortThemes(&$themes)
	{
		if (!empty($themes))
		{
			static $callback = array('_AdminContentModel', 'compareThemes');
			foreach ($themes as $theme)
			{
				if ($theme->children)
				{
					$this->sortThemes($theme->children);
				}
			}
			usort($themes, $callback);
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function themesToHierList($themes, $level = 0)
	{
		$list = array();
		
		foreach ($themes as $theme)
		{
			$list[$theme->id] = str_repeat('  ', $level) . $theme->title;
			
			if ($theme->children)
			{
				$list += $this->themesToHierList($theme->children, $level+1);
			}
		}
		
		return $list;
	}
	
	//---------------------------------------------------------------------------
	
	public function addTemplate($template)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'name' => $template->name,
			'ctype' => $template->ctype,
			'content' => $template->content,
			'created' => $now,
			'edited' => $now,
			'author_id' => $template->author_id,
			'editor_id' => $template->editor_id ? $template->editor_id : $template->author_id,
			'theme_id' => $template->theme_id,
		);

		$db->insertRow('template', $row);
		
		$template->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateTemplate($template)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'ctype' => $template->ctype,
			'content' => $template->content,
			'edited' => $now,
			'editor_id' => $template->editor_id,
		);

		$db->updateRows('template', $row, 'id=?', $template->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteTemplateByID($templateID)
	{
		$db = $this->loadDB();
	
		$db->deleteRows('template', 'id=?', $templateID);
	}

	//---------------------------------------------------------------------------
	
	public function fetchTemplateNames($themeID = NULL, $searchAll = false)
	{
		$db = $this->loadDB();

		if ($themeID && $searchAll)		// search ancestor themes
		{
			$theme = $this->fetchTheme(intval($themeID));
			$lineage = $theme->lineage . ',' . $themeID;
			$where = "theme_id IN ({$lineage})";
			$bind = NULL;
		}
		else
		{
			$where = 'theme_id=?';
			$bind = $themeID ? $themeID : 0;
		}
		
		$names = array();
		foreach ($db->selectRows('template', 'id,name', $where, $bind) as $row)
		{
			$names[$row['id']] = $row['name'];
		}
		
		asort($names);
		
		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchTemplateAuthor($template)
	{
		return $this->fetchObjectUser($template, 'template', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchTemplateEditor($template)
	{
		return $this->fetchObjectUser($template, 'template', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function templateExists($name, $theme_id)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('template', 'id', 'name=? AND theme_id=?', array($name, $theme_id));
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function addSnippet($snippet)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'name' => $snippet->name,
			'content' => $snippet->content,
			'created' => $now,
			'edited' => $now,
			'author_id' => $snippet->author_id,
			'editor_id' => $snippet->editor_id ? $snippet->editor_id : $snippet->author_id,
			'theme_id' => $snippet->theme_id,
		);

		$db->insertRow('snippet', $row);

		$snippet->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateSnippetContent($snippet)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'content' => $snippet->content,
			'edited' => $now,
			'editor_id' => $snippet->editor_id,
		);

		$db->updateRows('snippet', $row, 'id=?', $snippet->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteSnippetByID($snippetID)
	{
		$db = $this->loadDB();
	
		$db->deleteRows('snippet', 'id=?', $snippetID);
	}

	//---------------------------------------------------------------------------
	
	public function fetchSnippetNames($themeID = NULL)
	{
		$db = $this->loadDB();

		$where = 'theme_id=?';
		$bind = $themeID ? $themeID : 0;
		
		$names = array();
		foreach ($db->selectRows('snippet', 'id,name', $where, $bind) as $row)
		{
			$names[$row['id']] = $row['name'];
		}
		
		asort($names);
		
		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchSnippetAuthor($snippet)
	{
		return $this->fetchObjectUser($snippet, 'snippet', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchSnippetEditor($snippet)
	{
		return $this->fetchObjectUser($snippet, 'snippet', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function snippetExists($name, $theme_id)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('snippet', 'id', 'name=? AND theme_id=?', array($name, $theme_id));
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function addTag($tag)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'name' => $tag->name,
			'content' => $tag->content,
			'created' => $now,
			'edited' => $now,
			'author_id' => $tag->author_id,
			'editor_id' => $tag->editor_id ? $tag->editor_id : $tag->author_id,
			'theme_id' => $tag->theme_id,
		);

		$db->insertRow('tag', $row);

		$tag->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateTagContent($tag)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'content' => $tag->content,
			'edited' => $now,
			'editor_id' => $tag->editor_id,
		);

		$db->updateRows('tag', $row, 'id=?', $tag->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteTagByID($tagID)
	{
		$db = $this->loadDB();
	
		$db->deleteRows('tag', 'id=?', $tagID);
	}

	//---------------------------------------------------------------------------
	
	public function fetchTag($nameOrID, $theme = NULL)
	{
		$cacheKey = $nameOrID . ($theme ? '_'.$theme->slug : '');

		if (($tag = @$this->_cache['tags'][$cacheKey]) !== NULL)
		{
			return $tag;
		}

		$db = $this->loadDB();

		if (empty($theme))
		{
			$theme = 0;
		}

		if (is_integer($nameOrID))
		{
			$field = 'id';
			$theme = NULL;
		}
		else
		{
			$field = 'name';
		}
		
		if ($theme)
		{
			$lineage = $theme->lineage . ',' . $theme->id;
			$result = $db->query($db->buildSelect('tag', '*', NULL, "name=? AND theme_id IN ({$lineage})", 'theme_id DESC', 1), $nameOrID);
			$row = $result->row();
		}
		else
		{
			$where = "{$field}=?";
			if ($theme !== NULL)
			{
				$where .= ' AND theme_id = 0';
			}
			$row = $db->selectRow('tag', '*', $where, $nameOrID);
		}
		
		if ($row)
		{
			$tag = $this->factory->manufacture('Tag', $row);
		}
		else
		{
			$tag = false;
		}

		return $this->_cache['tags'][$cacheKey] = $tag;
	}

	//---------------------------------------------------------------------------
	
	public function fetchTagNames($themeID = NULL)
	{
		$db = $this->loadDB();

		$where = 'theme_id=?';
		$bind = $themeID ? $themeID : 0;
		
		$names = array();
		foreach ($db->selectRows('tag', 'id,name', $where, $bind) as $row)
		{
			$names[$row['id']] = $row['name'];
		}
		
		asort($names);
		
		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchTagAuthor($tag)
	{
		return $this->fetchObjectUser($tag, 'tag', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchTagEditor($tag)
	{
		return $this->fetchObjectUser($tag, 'tag', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function tagExists($name, $theme_id)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('tag', 'id', 'name=? AND theme_id=?', array($name, $theme_id));
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function addStyle($style)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'slug' => $style->slug,
			'ctype' => $style->ctype,
			'url' => $style->url ? $style->url : NULL,
			'content' => $style->content,
			'created' => $now,
			'edited' => $now,
			'author_id' => $style->author_id,
			'editor_id' => $style->editor_id ? $style->editor_id : $style->author_id,
			'theme_id' => $style->theme_id,
		);

		$db->insertRow('style', $row);
		
		$style->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateStyleContent($style)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'ctype' => $style->ctype,
			'url' => $style->url ? $style->url : NULL,
			'rev' => $db->getFunction('literal')->literal('rev+1'),
			'content' => $style->content,
			'edited' => $now,
			'editor_id' => $style->editor_id,
		);

		$db->updateRows('style', $row, 'id=?', $style->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteStyleByID($styleID)
	{
		$db = $this->loadDB();
	
		$db->deleteRows('style', 'id=?', $styleID);
	}

	//---------------------------------------------------------------------------
	
	public function fetchStyleNames($themeID = NULL)
	{
		$db = $this->loadDB();

		$where = 'theme_id=?';
		$bind = $themeID ? $themeID : 0;
		
		$names = array();
		foreach ($db->selectRows('style', 'id,slug', $where, $bind) as $row)
		{
			$names[$row['id']] = $row['slug'];
		}
		
		asort($names);
		
		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchStyleAuthor($style)
	{
		return $this->fetchObjectUser($style, 'style', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchStyleEditor($style)
	{
		return $this->fetchObjectUser($style, 'style', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function styleExists($slug, $theme_id)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('style', 'id', 'slug=? AND theme_id=?', array($slug, $theme_id));
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function addScript($script)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'slug' => $script->slug,
			'ctype' => $script->ctype,
			'url' => $script->url ? $script->url : NULL,
			'content' => $script->content,
			'created' => $now,
			'edited' => $now,
			'author_id' => $script->author_id,
			'editor_id' => $script->editor_id ? $script->editor_id : $script->author_id,
			'theme_id' => $script->theme_id,
		);

		$db->insertRow('script', $row);
		
		$script->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateScriptContent($script)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'ctype' => $script->ctype,
			'url' => $script->url ? $script->url : NULL,
			'rev' => $db->getFunction('literal')->literal('rev+1'),
			'content' => $script->content,
			'edited' => $now,
			'editor_id' => $script->editor_id,
		);

		$db->updateRows('script', $row, 'id=?', $script->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteScriptByID($scriptID)
	{
		$db = $this->loadDB();
	
		$db->deleteRows('script', 'id=?', $scriptID);
	}

	//---------------------------------------------------------------------------
	
	public function fetchScriptNames($themeID = NULL)
	{
		$db = $this->loadDB();

		$where = 'theme_id=?';
		$bind = $themeID ? $themeID : 0;
		
		$names = array();
		foreach ($db->selectRows('script', 'id,slug', $where, $bind) as $row)
		{
			$names[$row['id']] = $row['slug'];
		}

		asort($names);

		return $names;
	}

	//---------------------------------------------------------------------------
	
	public function fetchScriptAuthor($script)
	{
		return $this->fetchObjectUser($script, 'script', 'author');
	}

	//---------------------------------------------------------------------------
	
	public function fetchScriptEditor($script)
	{
		return $this->fetchObjectUser($script, 'script', 'editor');
	}

	//---------------------------------------------------------------------------
	
	public function scriptExists($slug, $theme_id)
	{
		$db = $this->loadDB();
		$row = $db->selectRow('script', 'id', 'slug=? AND theme_id=?', array($slug, $theme_id));
		return isset($row['id']) ? true : false;
	}

	//---------------------------------------------------------------------------
	
	public function findDescendants($table, $id, $db = NULL)
	{
		// recursively determine the IDs of all descendants for the specified object
	
		if (!$db)
		{
			$db = $this->loadDB();
		}
		
		$ids = array();

		foreach ($db->selectRows($table, 'id', 'parent_id=?', $id) as $row)
		{
			$ids = array_merge($this->findDescendants($table, $ids[] = $row['id'], $db), $ids);
		}

		return $ids;
	}

	//---------------------------------------------------------------------------
	//
	// Private Methods
	//
	//---------------------------------------------------------------------------
	
//---------------------------------------------------------------------------
	
}
