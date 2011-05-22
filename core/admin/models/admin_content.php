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
	
	public function fetchBranchNames()
	{
		return array
		(
			EscherProductionStatus::Production => 'Production',
			EscherProductionStatus::Staging => 'Staging',
			EscherProductionStatus::Development => 'Development',
		);
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

			/*
				Caution! The following code reuses the $categoryIDs parameter in subsequent invocations of buildFieldIn(),
				despite the fact that its contents may be modified. This is safe here only because we are confident
				that the $categoryIDs array cannot contain a NULL value (which, if present,  would be removed by the first invocation).
			*/

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
		$cache =& $this->_cache['category'];

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
	
	public function addModelMeta($id, $meta)
	{
		$this->addObjectMeta('model', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function updateModelMeta($id, $meta)
	{
		$this->updateObjectMeta('model', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function deleteModelMeta($id, $names)
	{
		$this->deleteObjectMeta('model', $id, $names);
	}

	//---------------------------------------------------------------------------
	
	public function fetchModelMeta($pageModel)
	{
		$this->fetchObjectMeta('model', $pageModel);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllModelMetaNames($pageModel)
	{
		return $this->fetchAllObjectMetaNames('model', $pageModel);
	}
	
	//---------------------------------------------------------------------------

	public function saveModelMeta($pageModel, $perms)
	{
		$this->saveObjectMeta('model', $pageModel, $perms);
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
	
			$cache =& $this->_cache['category'];
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

			/*
				Caution! The following code reuses the $pageIDs parameter in subsequent invocations of buildFieldIn(),
				despite the fact that its contents may be modified. This is safe here only because we are confident
				that the $pageIDs array cannot contain a NULL value (which, if present,  would be removed by the first invocation).
			*/
			
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
	
	public function addPageMeta($id, $meta)
	{
		$this->addObjectMeta('page', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function updatePageMeta($id, $meta)
	{
		$this->updateObjectMeta('page', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function deletePageMeta($id, $names)
	{
		$this->deleteObjectMeta('page', $id, $names);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllPageMetaNames($page)
	{
		return $this->fetchAllObjectMetaNames('page', $page);
	}
	
	//---------------------------------------------------------------------------

	public function savePageMeta($page, $perms)
	{
		$this->saveObjectMeta('page', $page, $perms);
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
	
			$cache =& $this->_cache['category'];
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
	
			$cache =& $this->_cache['category'];
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
			'branch' => $image->branch,
			'branch_status' => ContentObject::branch_status_added,
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
	
	public function fetchImageNames($themeID, $branch)
	{
		return $this->fetchDesignAssetNames('image', 'slug', $themeID, $branch);
	}

	//---------------------------------------------------------------------------
	
	public function addImageMeta($id, $meta)
	{
		$this->addObjectMeta('image', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function updateImageMeta($id, $meta)
	{
		$this->updateObjectMeta('image', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function deleteImageMeta($id, $names)
	{
		$this->deleteObjectMeta('image', $id, $names);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllImageMetaNames($image)
	{
		return $this->fetchAllObjectMetaNames('image', $image);
	}
	
	//---------------------------------------------------------------------------

	public function saveImageMeta($image, $perms)
	{
		$this->saveObjectMeta('image', $image, $perms);
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
	
			$cache =& $this->_cache['category'];
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
	
	public function addFileMeta($id, $meta)
	{
		$this->addObjectMeta('file', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function updateFileMeta($id, $meta)
	{
		$this->updateObjectMeta('file', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function deleteFileMeta($id, $names)
	{
		$this->deleteObjectMeta('file', $id, $names);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllFileMetaNames($file)
	{
		return $this->fetchAllObjectMetaNames('file', $file);
	}
	
	//---------------------------------------------------------------------------

	public function saveFileMeta($file, $perms)
	{
		$this->saveObjectMeta('file', $file, $perms);
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
	
			$cache =& $this->_cache['category'];
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
	
	public function addLinkMeta($id, $meta)
	{
		$this->addObjectMeta('link', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function updateLinkMeta($id, $meta)
	{
		$this->updateObjectMeta('link', $id, $meta);
	}

	//---------------------------------------------------------------------------
	
	public function deleteLinkMeta($id, $names)
	{
		$this->deleteObjectMeta('link', $id, $names);
	}

	//---------------------------------------------------------------------------
	
	public function fetchAllLinkMetaNames($link)
	{
		return $this->fetchAllObjectMetaNames('link', $link);
	}
	
	//---------------------------------------------------------------------------

	public function saveLinkMeta($link, $perms)
	{
		$this->saveObjectMeta('link', $link, $perms);
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
	
			$cache =& $this->_cache['category'];
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

			/*
				Caution! The following code reuses the $themeIDs parameter in subsequent invocations of buildFieldIn(),
				despite the fact that its contents may be modified. This is safe here only because we are confident
				that the $themeIDs array cannot contain a NULL value (which, if present,  would be removed by the first invocation).
			*/
			
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

	public function installTheme($authorID, $themePath, $branch, $themeBaseURL = '', $parentThemeID = 0, $createTheme = true)
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
					'branch'=>$branch,
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
								'branch'=>$branch,
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
								'branch'=>$branch,
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
								'branch'=>$branch,
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
							'branch'=>$branch,
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
							'branch'=>$branch,
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
							'branch'=>$branch,
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
			'branch' => $template->branch,
			'branch_status' => ContentObject::branch_status_added,
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
	
	public function deleteTemplateByID($id)
	{
		return $this->deleteDesignAssetByID('template', $id);
	}

	//---------------------------------------------------------------------------
	
	public function markTemplateDeletedByID($id)
	{
		return $this->markDesignAssetDeletedByID('template', $id);
	}

	//---------------------------------------------------------------------------
	
	public function undeleteTemplate($template)
	{
		return $this->undeleteDesignAsset('template', $template);
	}

	//---------------------------------------------------------------------------
	
	public function copyTemplateToBranch($name, $themeID, $branch, $delete = false)
	{
		return $this->copyDesignAssetToBranch('template', 'name', $name, $themeID, $branch, $delete);
	}

	//---------------------------------------------------------------------------
	
	public function templateExists($name, $themeID, $branch, &$info)
	{
		return $this->designAssetExists('template', 'name', $name, $themeID, $branch, $info);
	}

	//---------------------------------------------------------------------------
	
	public function fetchTemplateNames($themeID, $branch, $searchAll = false)
	{
		return $this->fetchDesignAssetNames('template', 'name', $themeID, $branch, true);
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
			'branch' => $snippet->branch,
			'branch_status' => ContentObject::branch_status_added,
		);

		$db->insertRow('snippet', $row);

		$snippet->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateSnippetContent($snippet)
	{
		return $this->updateDesignAssetContent('snippet', $snippet);
	}

	//---------------------------------------------------------------------------
	
	public function deleteSnippetByID($id)
	{
		return $this->deleteDesignAssetByID('snippet', $id);
	}

	//---------------------------------------------------------------------------
	
	public function markSnippetDeletedByID($id)
	{
		return $this->markDesignAssetDeletedByID('snippet', $id);
	}

	//---------------------------------------------------------------------------
	
	public function undeleteSnippet($snippet)
	{
		return $this->undeleteDesignAsset('snippet', $snippet);
	}

	//---------------------------------------------------------------------------
	
	public function copySnippetToBranch($name, $themeID, $branch, $delete = false)
	{
		return $this->copyDesignAssetToBranch('snippet', 'name', $name, $themeID, $branch, $delete);
	}

	//---------------------------------------------------------------------------
	
	public function snippetExists($name, $themeID, $branch, &$info)
	{
		return $this->designAssetExists('snippet', 'name', $name, $themeID, $branch, $info);
	}

	//---------------------------------------------------------------------------
	
	public function fetchSnippetNames($themeID, $branch)
	{
		return $this->fetchDesignAssetNames('snippet', 'name', $themeID, $branch);
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
			'branch' => $tag->branch,
			'branch_status' => ContentObject::branch_status_added,
		);

		$db->insertRow('tag', $row);

		$tag->id = $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function updateTagContent($tag)
	{
		return $this->updateDesignAssetContent('tag', $tag);
	}

	//---------------------------------------------------------------------------
	
	public function deleteTagByID($id)
	{
		return $this->deleteDesignAssetByID('tag', $id);
	}

	//---------------------------------------------------------------------------
	
	public function markTagDeletedByID($id)
	{
		return $this->markDesignAssetDeletedByID('tag', $id);
	}

	//---------------------------------------------------------------------------
	
	public function undeleteTag($tag)
	{
		return $this->undeleteDesignAsset('tag', $tag);
	}

	//---------------------------------------------------------------------------
	
	public function copyTagToBranch($name, $themeID, $branch, $delete = false)
	{
		return $this->copyDesignAssetToBranch('tag', 'name', $name, $themeID, $branch, $delete);
	}

	//---------------------------------------------------------------------------
	
	public function tagExists($name, $themeID, $branch, &$info)
	{
		return $this->designAssetExists('tag', 'name', $name, $themeID, $branch, $info);
	}

	//---------------------------------------------------------------------------
	
	public function fetchTagNames($themeID, $branch)
	{
		return $this->fetchDesignAssetNames('tag', 'name', $themeID, $branch);
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
			'branch' => $style->branch,
			'branch_status' => ContentObject::branch_status_added,
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
	
	public function deleteStyleByID($id)
	{
		return $this->deleteDesignAssetByID('style', $id);
	}

	//---------------------------------------------------------------------------
	
	public function markStyleDeletedByID($id)
	{
		return $this->markDesignAssetDeletedByID('style', $id);
	}

	//---------------------------------------------------------------------------
	
	public function undeleteStyle($style)
	{
		return $this->undeleteDesignAsset('style', $style);
	}

	//---------------------------------------------------------------------------
	
	public function copyStyleToBranch($slug, $themeID, $branch, $delete = false)
	{
		return $this->copyDesignAssetToBranch('style', 'slug', $slug, $themeID, $branch, $delete);
	}

	//---------------------------------------------------------------------------
	
	public function styleExists($slug, $themeID, $branch, &$info)
	{
		return $this->designAssetExists('style', 'slug', $slug, $themeID, $branch, $info);
	}

	//---------------------------------------------------------------------------
	
	public function fetchStyleNames($themeID, $branch)
	{
		return $this->fetchDesignAssetNames('style', 'slug', $themeID, $branch);
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
			'branch' => $script->branch,
			'branch_status' => ContentObject::branch_status_added,
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
	
	public function deleteScriptByID($id)
	{
		return $this->deleteDesignAssetByID('script', $id);
	}

	//---------------------------------------------------------------------------
	
	public function markScriptDeletedByID($id)
	{
		return $this->markDesignAssetDeletedByID('script', $id);
	}

	//---------------------------------------------------------------------------
	
	public function undeleteScript($script)
	{
		return $this->undeleteDesignAsset('script', $script);
	}

	//---------------------------------------------------------------------------
	
	public function copyScriptToBranch($slug, $themeID, $branch, $delete = false)
	{
		return $this->copyDesignAssetToBranch('script', 'slug', $slug, $themeID, $branch, $delete);
	}

	//---------------------------------------------------------------------------
	
	public function scriptExists($slug, $themeID, $branch, &$info)
	{
		return $this->designAssetExists('script', 'slug', $slug, $themeID, $branch, $info);
	}

	//---------------------------------------------------------------------------
	
	public function fetchScriptNames($themeID, $branch)
	{
		return $this->fetchDesignAssetNames('script', 'slug', $themeID, $branch);
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
	// Protected Methods
	//
	//---------------------------------------------------------------------------
	
	public function updateDesignAssetContent($table, $asset)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'content' => $asset->content,
			'edited' => $now,
			'editor_id' => $asset->editor_id,
		);

		$db->updateRows($table, $row, 'id=?', $asset->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteDesignAssetByID($table, $assetID)
	{
		$db = $this->loadDB();
	
		$db->deleteRows($table, 'id=?', $assetID);
	}

	//---------------------------------------------------------------------------
	
	public function markDesignAssetDeletedByID($table, $assetID)
	{
		$db = $this->loadDB();
	
		$row = array
		(
			'content' => '',
			'branch_status' => ContentObject::branch_status_deleted,
		);

		$db->updateRows($table, $row, 'id=?', $assetID);
	}

	//---------------------------------------------------------------------------
	
	public function undeleteDesignAsset($table, $asset)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'content' => $asset->content,
			'created' => isset($asset->created) ? $asset->created : $now,
			'edited' => $now,
			'author_id' => $asset->author_id,
			'editor_id' => $asset->editor_id ? $asset->editor_id : $asset->author_id,
			'branch_status' => ContentObject::branch_status_added,
		);

		$db->updateRows($table, $row, 'id=?', $asset->id);
	}

	//---------------------------------------------------------------------------
	
	public function copyDesignAssetToBranch($table, $nameCol, $name, $themeID, $branch, $delete = false)
	{
		$db = $this->loadDB();
		
		$row = $db->query($db->buildSelect($table, '*', NULL, "{$nameCol}=? AND theme_id=? AND branch<=?", 'branch DESC', 1), array($name, $themeID, $branch))->row();
		if (empty($row))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>"{$table} not found"));
		}
		
		if ($delete)
		{
			$row['content'] = '';
			$row['branch_status'] = ContentObject::branch_status_deleted;
		}
		else
		{
			$row['branch_status'] = ContentObject::branch_status_edited;
		}

		// already exists?
		
		if ($row['branch'] == $branch)
		{
			if ($delete)
			{
				$db->updateRows($table, $row, 'id=?', $row['id']);
			}
			return $row['id'];
		}
		
		unset($row['id']);
		$row['branch'] = $branch;
		
		$db->insertRow($table, $row);

		return $db->lastInsertID();
	}

	//---------------------------------------------------------------------------
	
	public function designAssetExists($table, $nameCol, $name, $themeID, $branch, &$info)
	{
		$db = $this->loadDB();
		$row = $db->query($db->buildSelect($table, 'id,branch,branch_status', NULL, "{$nameCol}=? AND theme_id=? AND branch<=?", 'branch DESC', 1), array($name, $themeID, $branch))->row();
		if (isset($row['id']))
		{
			$info['id'] = $row['id'];
			$info['branch'] = $row['branch'];
			$info['branch_status'] = $row['branch_status'];
		}
		return (isset($row['id']) && ($row['branch_status'] != ContentObject::branch_status_deleted));
	}

	//---------------------------------------------------------------------------
	
	public function fetchDesignAssetNames($table, $nameColumn, $themeID, $branch, $searchAncestorThemes = false)
	{
		$db = $this->loadDB();

		if ($searchAncestorThemes && $themeID && ($theme = $this->fetchTheme((int) $themeID)))
		{
			$lineage = explode(',', $theme->lineage);
			$lineage[] = $themeID;
			$where = $db->buildFieldIn('template', 'theme_id', $lineage);
			$bind = $lineage;
		}
		else
		{
			$where = 'theme_id=?';
			$bind[] = $themeID ? $themeID : 0;
		}

		$where .= ' AND branch<=?';
		$bind[] = $branch ? $branch : 0;
		
		$names = array(); $deleted = array();
		foreach ($db->query($db->buildSelect($table, "id,{$nameColumn},branch_status", NULL, $where, "{$nameColumn}, theme_id DESC, branch DESC"), $bind)->rows() as $row)
		{
			if (!isset($names[$name = $row[$nameColumn]]))
			{
				if ($row['branch_status'] == ContentObject::branch_status_deleted)
				{
					$deleted[$name] = true;
				}
				elseif (!isset($deleted[$name]))
				{
					$names[$name] = $row['id'];
				}
			}
		}

		return array_flip($names);
	}

	//---------------------------------------------------------------------------
	
	protected function addObjectMeta($objType, $id, $meta)
	{
		$db = $this->loadDB();
	
		$rows = array();
		foreach ($meta as $key=>$val)
		{
			$key = strtolower(preg_replace('/\s+/', '_', trim($key)));
			$rows[] = array("{$objType}_id"=>$id, 'name'=>$key, 'data'=>trim($val));
		}

		$db->insertRows("{$objType}_meta", $rows);
	}

	//---------------------------------------------------------------------------
	
	protected function updateObjectMeta($objType, $id, $meta)
	{
		$db = $this->loadDB();
	
		foreach ($meta as $key=>$val)
		{
			$key = strtolower(preg_replace('/\s+/', '_', trim($key)));
			$db->upsertRow("{$objType}_meta", array("{$objType}_id"=>$id, 'name'=>$key, 'data'=>trim($val)), "{$objType}_id=? AND name=?", array($id, $key));
		}
	}

	//---------------------------------------------------------------------------
	
	protected function deleteObjectMeta($objType, $id, $names)
	{
		$db = $this->loadDB();

		$bind = array_merge(array($id), $names);

		$db->deleteRows("{$objType}_meta", "{$objType}_id=? AND " . $db->buildFieldIn("{$objType}_meta", 'name', $names), $bind);
	}

	//---------------------------------------------------------------------------
	
	protected function fetchAllObjectMetaNames($objType, $obj)
	{
		$db = $this->loadDB();

		$metaNames = array();
		
		foreach ($db->selectRows("{$objType}_meta", 'name', "{$objType}_id=?", $obj->id) as $row)
		{
			$metaNames[$row['name']] = $row['name'];
		}
		
		asort($metaNames);

		return $metaNames;
	}
	
	//---------------------------------------------------------------------------

	protected function saveObjectMeta($objType, $obj, $perms)
	{
		$existingMetaNames = $this->fetchAllObjectMetaNames($objType, $obj);
		$updateMeta = array();

		if ($perms['can_edit_meta'] || $perms['can_add_meta'])
		{
			foreach ($obj->meta as $name=>$meta)
			{
				$isNewMeta = !isset($existingMetaNames[$name]);
				if (($perms['can_edit_meta'] && !$isNewMeta) || ($perms['can_add_meta'] && $isNewMeta))
				{
					$updateMeta[$name] = $meta;
				}
			}
		}

		if (!empty($updateMeta))
		{
			$this->updateObjectMeta($objType, $obj->id, $updateMeta);
		}

		// delete metadata that were not submitted

		if ($perms['can_delete_meta'])
		{
			foreach ($obj->meta as $name=>$data)
			{
				unset($existingMetaNames[$name]);
			}
			if (!empty($existingMetaNames))
			{
				$this->deleteObjectMeta($objType, $obj->id, $existingMetaNames);
			}
		}
	}

	//---------------------------------------------------------------------------
	//
	// Private Methods
	//
	//---------------------------------------------------------------------------
	
//---------------------------------------------------------------------------
	
}
