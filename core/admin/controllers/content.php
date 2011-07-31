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

class _ContentController extends EscherAdminController
{
	private $_tabs;
	private $_filters;
	private $_filterClasses;
	private $_filterNames;
	private $_pageTypes;

	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct($app)
	{
		parent::__construct($app);

		$this->app->build_tabs($this->_tabs, array('models', 'pages', 'blocks', 'images', 'files', 'links', 'categories'), 'content');

		$this->_filters = array();
		$this->_filterClasses[0] = 'html';
		$this->_filterNames[0] = 'None (raw HTML)';
		
		$this->_pageTypes = array
		(
			'Page' => '<normal>',
			'PageCategory' => 'Category Page',
			'PageImage' => 'Image Page',
			'PageFile' => 'File Page',
			'PageScript' => 'Script Page',
			'PageStyle' => 'Style Page',
			'PageTheme' => 'Theme Page',
		);
		
		// observe requests to add new filters, page types

		$this->observer->observe(array($this, 'registerFilter'), 'escher:content:filter:request_add');
		$this->observer->observe(array($this, 'registerPageType'), 'escher:content:page_type:request_add');

		// send notification that we are now listening

		$this->observer->notify('escher:content:filter:register');
		$this->observer->notify('escher:content:page_type:register');
	}

	//---------------------------------------------------------------------------

	public function &get_tabs()
	{
		return $this->_tabs;
	}

	//---------------------------------------------------------------------------

	public function registerFilter($event, $filter)
	{
		$filterID = $filter->id();
		
		// check for collisions
		
		if (isset($this->_filterNames[$filterID]))
		{
			trigger_error("Filter \"{$filter->name()}\" disabled due to id conflict with filter \"{$this->_filterNames[$filterID]}\"");
			return;
		}
		
		$this->_filters[$filterID] = $filter;
		$this->_filterClasses[$filterID] = $filter->cssClass();
		
		// keep "None" at top of list
		
		$newFilters = $this->_filterNames;
		$newFilters[$filterID] = $filter->name();
		unset($newFilters[0]);
		asort($newFilters);

		$this->_filterNames = array(0=>$this->_filterNames[0]) + $newFilters;
	}

	//---------------------------------------------------------------------------

	public function registerPageType($event, $type, $name)
	{
		// keep "Normal" at top of list
		
		$newPageTypes = $this->_pageTypes;
		$newPageTypes[$type] = $name;
		unset($newPageTypes['Page']);
		asort($newPageTypes);
		
		$this->_pageTypes = array_merge(array('Page'=>$this->_pageTypes['Page']), $newPageTypes);
	}

	//---------------------------------------------------------------------------

	public function action_index($params)
	{
		$this->session->flashKeep('html_alert');

		if (in_array('pages', $this->_tabs))
		{
			$this->redirect('/content/pages');
		}
		elseif ($first = current($this->_tabs))
		{
			$this->redirect('/content/'.$first);
		}
		else
		{
			$this->getCommonVars($vars);
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}
	}

	//---------------------------------------------------------------------------

	public function action_models($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->models_add($this->dropParam($params));
			case 'edit':
				return $this->models_edit($this->dropParam($params));
			case 'delete':
				return $this->models_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->models_list($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_pages($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->pages_add($this->dropParam($params));
			case 'edit':
				return $this->pages_edit($this->dropParam($params));
			case 'delete':
				return $this->pages_delete($this->dropParam($params));
			case 'move':
				return $this->pages_move($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->pages_list($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_blocks($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->blocks_add($this->dropParam($params));
			case 'edit':
				return $this->blocks_edit($this->dropParam($params));
			case 'delete':
				return $this->blocks_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->blocks_edit($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_images($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->images_add($this->dropParam($params));
			case 'edit':
				return $this->images_edit($this->dropParam($params));
			case 'delete':
				return $this->images_delete($this->dropParam($params));
			case 'display':
				return $this->images_display($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->images_edit($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_files($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->files_add($this->dropParam($params));
			case 'edit':
				return $this->files_edit($this->dropParam($params));
			case 'delete':
				return $this->files_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->files_edit($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_links($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->links_add($this->dropParam($params));
			case 'edit':
				return $this->links_edit($this->dropParam($params));
			case 'delete':
				return $this->links_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->links_edit($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_categories($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->categories_add($this->dropParam($params));
			case 'edit':
				return $this->categories_edit($this->dropParam($params));
			case 'delete':
				return $this->categories_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->categories_list($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected function getCommonVars(&$vars)
	{
		parent::getCommonVars($vars);
		$vars['subtabs'] = $this->_tabs;
		$vars['selected_tab'] = 'content';
	}

	//---------------------------------------------------------------------------

	protected function models_list($params)
	{
		$model = $this->newAdminContentModel();
		
		$models = $model->fetchAllModels(true);
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'models');

		$vars['selected_subtab'] = 'models';
		$vars['action'] = 'list';
		$vars['models'] = $models;
		$vars['notice'] = $this->session->flashGet('notice');

		$this->observer->notify('escher:render:before:content:model:list', $models);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function models_add($params)
	{
		$model = $this->newAdminContentModel();
		
		$pageModel = $this->factory->manufacture('PageModel', array());

		$theme = intval($this->app->get_pref('theme'));
		$theme = $theme ? $model->fetchTheme($theme) : NULL;
		$themeID = $theme ? $theme->id : 0;
		$templates = $model->fetchTemplateNames($themeID, 1, true);
				
		$statuses = _Page::statusOptions();
		unset($statuses[_Page::Status_expired]);

		require($this->config->get('core_dir') . '/admin/lib/form_field_generator.php');
		$markupGenerator = $this->factory->manufacture('FormFieldGenerator');
		$markupGenerator->getCallbacks($callbacks);

		// send notification for any plugins that want to inject their own markup handlers
		// wrap callbacks in object so plugin can modify it (notification params cannot be passed by reference)
		
		$this->observer->notify('escher:content:model:part:add' . $pageModel->parts, (object)array('callbacks'=>&$callbacks));
		ksort($callbacks);

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'models', 'add');
		$this->getContentModelsPerms($vars, 'add');

		if (isset($params['pv']['save']) || isset($params['pv']['continue']))
		{
			// build page object from form data
			
			$this->buildPage($params['pv'], $templates, $pageModel, $vars, true);
			$pageModel->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();
		
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->modelExists($pageModel->name))
			{
				$errors['model_name'] = 'A model with this name already exists.';
			}
			elseif ($this->validatePage($params['pv'], $templates, $statuses, $callbacks, $vars, true, $errors))
			{
				// add model object
	
				try
				{
					$this->models_save($model, $pageModel, $vars);
					$this->session->flashSet('notice', 'Page Model added successfully.');
	
					if (isset($params['pv']['continue']))
					{
						$this->redirect('/content/models/edit/'.$pageModel->id);
					}
					else
					{
						$this->redirect('/content/models');
					}
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$pageModel->categories = array();
			$pageModel->parts['body'] = $this->factory->manufacture('Part', array('name'=>'body', 'type'=>'textarea'));
			$pageModel->meta['keywords'] = '';
			$pageModel->meta['description'] = '';
		}

		if (!is_array($pageModel->meta))
		{
			$pageModel->meta = array();
		}
		
		$vars['selected_subtab'] = 'models';
		$vars['action'] = 'add';
		$vars['model'] = $pageModel;
		$vars['templates'] = $templates;
		$vars['filterClasses'] = $this->_filterClasses;
		$vars['filterNames'] = $this->_filterNames;
		$vars['model_types'] = $this->_pageTypes;
		$vars['statuses'] = $statuses;
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['callbacks'] = $callbacks;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:model:add', $pageModel);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function models_edit($params)
	{
		if (!$modelID = @$params['pv']['model_id'])
		{
			if (!$modelID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'model not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$pageModel = $model->fetchModelByID($modelID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'model not found'));
		}

		$theme = intval($this->app->get_pref('theme'));
		$theme = $theme ? $model->fetchTheme($theme) : NULL;
		$themeID = $theme ? $theme->id : 0;
		$templates = $model->fetchTemplateNames($themeID, 1, true);
		
		$statuses = _Page::statusOptions();
		unset($statuses[_Page::Status_expired]);

		require($this->config->get('core_dir') . '/admin/lib/form_field_generator.php');
		$markupGenerator = $this->factory->manufacture('FormFieldGenerator');
		$markupGenerator->getCallbacks($callbacks);

		// send notification for any plugins that want to inject their own markup handlers
		// wrap callbacks in object so plugin can modify it (notification params cannot be passed by reference)
		
		$this->observer->notify('escher:content:model:part:edit' . $pageModel->parts, (object)array('callbacks'=>&$callbacks));
		ksort($callbacks);

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'models', 'edit');
		$this->getContentModelsPerms($vars, 'edit', $pageModel);

		if (isset($params['pv']['save']) || isset($params['pv']['continue']))
		{
			// build page model object from form data
			
			$oldName = $pageModel->name;
			$this->buildPage($params['pv'], $templates, $pageModel, $vars, true);
			$pageModel->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();
		
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($pageModel->name !== $oldName) && $model->modelExists($pageModel->name))
			{
				$errors['model_name'] = 'A model with this name already exists.';
			}
			elseif ($this->validatePage($params['pv'], $templates, $statuses, $callbacks, $vars, true, $errors))
			{
				// update page model object
	
				try
				{
					$this->models_save($model, $pageModel, $vars);
	
					if (isset($params['pv']['save']))
					{
						$this->session->flashSet('notice', 'Model saved successfully.');
						$this->redirect('/content/models');
					}
					
					$vars['notice'] = 'Model saved successfully.';
	
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}

				// must refresh page model parts from database so new field names get generated for newly added parts

				$pageModel->parts = $model->fetchAllModelParts($pageModel);
				$pageModel->meta = NULL;
				$model->fetchModelMeta($pageModel);
			}
		}
		else
		{
			$model->fetchModelCategories($pageModel, true);
			$pageModel->parts = $model->fetchAllModelParts($pageModel);
			$model->fetchModelMeta($pageModel);
			$vars['notice'] = $this->session->flashGet('notice');
		}

		$model->fetchModelEditor($pageModel);

		if (!is_array($pageModel->meta))
		{
			$pageModel->meta = array();
		}

		$vars['selected_subtab'] = 'models';
		$vars['action'] = 'edit';
		$vars['model_id'] = $modelID;
		$vars['model'] = $pageModel;
		$vars['templates'] = $templates;
		$vars['filterClasses'] = $this->_filterClasses;
		$vars['filterNames'] = $this->_filterNames;
		$vars['model_types'] = $this->_pageTypes;
		$vars['statuses'] = $statuses;
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['callbacks'] = $callbacks;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:model:edit', $pageModel);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function models_delete($params)
	{
		if (!$modelID = @$params['pv']['model_id'])
		{
			if (!$modelID = @$params[0])
			{
				$modelID = 0;
			}
		}

		if (!$modelID)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'model not found'));
		}
		
		$model = $this->newAdminContentModel();

		if (!$pageModel = $model->fetchModelByID($modelID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'model not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'models', 'delete');

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->deleteModelByID($modelID);
				$this->observer->notify('escher:db_change:content:model:delete', $pageModel);
				$this->session->flashSet('notice', 'Model deleted successfully.');
				$this->redirect('/content/models');
			}
		}

		$vars['selected_subtab'] = 'models';
		$vars['action'] = 'delete';
		$vars['model_id'] = $modelID;
		$vars['model_name'] = $pageModel->name;

		$this->observer->notify('escher:render:before:content:model:delete', $pageModel);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function models_save($model, $pageModel, $vars)
	{
		// save page model object
	
		if ($pageModel->id)
		{
			$this->updateObjectEdited($pageModel);
			$model->updateModel($pageModel);
			$this->observer->notify('escher:db_change:content:model:edit', $pageModel);
		}
		else
		{
			$this->updateObjectCreated($pageModel);
			$model->addModel($pageModel);
			$this->observer->notify('escher:db_change:content:model:add', $pageModel);
		}

		// save page parts
		
		if ($vars['can_edit_parts'] || $vars['can_add_parts'] || $vars['can_delete_parts'])
		{
			$existingPartNames = $model->fetchAllModelPartNames($pageModel);
	
			if ($vars['can_edit_parts'] || $vars['can_add_parts'])
			{
				foreach ($pageModel->parts as $part)
				{
					$isNewPart = !isset($existingPartNames[$part->name]);
					if (($vars['can_edit_parts'] && !$isNewPart) || ($vars['can_add_parts'] && $isNewPart))
					{
						$part->content_html = $part->filter_id ? $this->_filters[$part->filter_id]->filter($part->content) : $part->content;
						$part->model_id = $pageModel->id;
						$model->updateModelPart($part);
					}
				}
			}

			// delete parts that were not submitted
	
			if ($vars['can_delete_parts'])
			{
				foreach ($pageModel->parts as $part)
				{
					unset($existingPartNames[$part->name]);
				}
				if (!empty($existingPartNames))
				{
					$model->deleteModelParts($pageModel->id, $existingPartNames);
				}
			}
		}
					
		// save page model metadata
	
		unset($pageModel->meta['slug']);
		unset($pageModel->meta['breadcrumb']);
		if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
		{
			$model->saveModelMeta($pageModel, $vars);
		}
	}
	
	//---------------------------------------------------------------------------

	protected function pages_add($params)
	{
		if (!$parentID = @$params['pv']['parent_id'])
		{
			if (!$parentID = @$params[0])
			{
				$parentID = 0;
			}
		}
		
		if (!$pageModelID = @$params['pv']['model_id'])
		{
			if (!$pageModelID = @$params[1])
			{
				$pageModelID = 0;
			}
		}
		
		$model = $this->newAdminContentModel();
		
		// there can be only one!
		
		if (!$parentID && $model->rootPageExists())
		{
			throw new SparkException('root page already exists');
		}
		
		// if a page model is specified in the request, use it as a template to construct the page object

		$usingModel = false;
		if ($pageModelID)
		{
			if (preg_match('/^\d+$/', $pageModelID))
			{
				if ($page = $model->createPageFromModel(intval($pageModelID), $parentID))
				{
					$usingModel = true;
				}
			}
			elseif ($pageModelID === 'inherit')
			{
				// special case: copy the parent's page structure, essentially using the parent page as a model for the child

				if ($page = $model->createPageFromPage($parentID, $parentID))
				{
					$usingModel = true;
				}
			}
		}
		if (!$usingModel)
		{
			$curUser = $this->app->get_user();
			$pageType = isset($params['pv']['page_type']) && isset($this->_pageTypes[$params['pv']['page_type']]) ? $params['pv']['page_type'] : 'Page';
			$page = $this->factory->manufacture($pageType, array('parent_id'=>$parentID, 'author_id'=>$curUser->id));
			$usingModel = false;
		}

		$theme = intval($this->app->get_pref('theme'));
		$theme = $theme ? $model->fetchTheme($theme) : NULL;
		$themeID = $theme ? $theme->id : 0;
		$templates = $model->fetchTemplateNames($themeID, 1, true);
				
		$statuses = _Page::statusOptions();
		unset($statuses[_Page::Status_expired]);

		require($this->config->get('core_dir') . '/admin/lib/form_field_generator.php');
		$markupGenerator = $this->factory->manufacture('FormFieldGenerator');
		$markupGenerator->getCallbacks($callbacks);

		// send notification for any plugins that want to inject their own markup handlers
		// wrap callbacks in object so plugin can modify it (notification params cannot be passed by reference)
		
		$this->observer->notify('escher:content:page:part:add' . $page->parts, (object)array('callbacks'=>&$callbacks));
		ksort($callbacks);

		if ($parentID)
		{
			if (!$parentPage = $model->fetchSimplePageByID($parentID))
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'page not found'));
			}
		}

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'pages', 'add');
		$this->getContentPagesPerms($vars, 'add', $parentID ? $parentPage : $page);
		
		if (!$vars['can_save'])
		{
			$vars['selected_subtab'] = 'pages';
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}

		if (isset($params['pv']['save']) || isset($params['pv']['continue']))
		{
			// build page object from form data
			
			$this->buildPage($params['pv'], $templates, $page, $vars, false);
			$page->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();
			$page->level = 0;
			$page->position = 0;
			$page->published = '';
		
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->childPageExists($page->parent_id, $page->slug))
			{
				$errors['page_slug'] = 'This slug already in use by a sibling of this page.';
			}
			elseif ($this->validatePage($params['pv'], $templates, $statuses, $callbacks, $vars, false, $errors))
			{
				// add page object
	
				if ($parentID)
				{
					$page->parent_id = $parentID;
					$page->level = $parentPage->level + 1;
					$page->makeSlug();
				}
				else
				{
					$page->level = 0;
				}
	
				try
				{
					$this->pages_save($model, $page, $vars);
					$this->session->flashSet('notice', 'Page added successfully.');
					if (isset($params['pv']['continue']))
					{
						$this->redirect('/content/pages/edit/'.$page->id);
					}
					else
					{
						$this->redirect('/content/pages');
					}
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			if (!$usingModel)
			{
				$page->meta['keywords'] = '';
				$page->meta['description'] = '';
				$page->categories = array();
				$page->parts['body'] = $this->factory->manufacture('Part', array('name'=>'body', 'type'=>'textarea'));
			}
		}

		if (!is_array($page->meta))
		{
			$page->meta = array();
		}
		
		$vars['selected_subtab'] = 'pages';
		$vars['action'] = 'add';
		$vars['parent_id'] = $parentID;
		$vars['page'] = $page;
		$vars['can_inherit'] = ($parentID != 0);
		$vars['templates'] = $templates;
		$vars['filterClasses'] = $this->_filterClasses;
		$vars['filterNames'] = $this->_filterNames;
		$vars['page_types'] = $this->_pageTypes;
		$vars['statuses'] = $statuses;
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['callbacks'] = $callbacks;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:page:add', $page);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function pages_edit($params)
	{
		if (!$pageID = @$params['pv']['page_id'])
		{
			if (!$pageID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'page not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$page = $model->fetchPageByID($pageID))	// we want the entire page chain for displaying URI, so can't use fetchSimplePageByID()
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'page not found'));
		}

		$pageType = isset($params['pv']['page_type']) && isset($this->_pageTypes[$params['pv']['page_type']]) ? $params['pv']['page_type'] : $page->type;
		
		if ($pageType != $page->type)
		{
			$page = $this->factory->manufacture($pageType, $page);
		}

		$theme = intval($this->app->get_pref('theme'));
		$theme = $theme ? $model->fetchTheme($theme) : NULL;
		$themeID = $theme ? $theme->id : 0;
		$templates = $model->fetchTemplateNames($themeID, 1, true);
		
		$statuses = _Page::statusOptions();
		if ($page->status != _Page::Status_expired)
		{
			unset($statuses[_Page::Status_expired]);
		}

		require($this->config->get('core_dir') . '/admin/lib/form_field_generator.php');
		$markupGenerator = $this->factory->manufacture('FormFieldGenerator');
		$markupGenerator->getCallbacks($callbacks);

		// send notification for any plugins that want to inject their own markup handlers
		// wrap callbacks in object so plugin can modify it (notification params cannot be passed by reference)
		
		$this->observer->notify('escher:content:page:part:edit' . $page->parts, (object)array('callbacks'=>&$callbacks));
		ksort($callbacks);

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'pages', 'edit', $page);
		$this->getContentPagesPerms($vars, 'edit', $page);
		
		if (!$vars['can_save'])
		{
			$vars['selected_subtab'] = 'pages';
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}

		if (isset($params['pv']['save']) || isset($params['pv']['continue']))
		{
			// build page object from form data
			
			$oldSlug = $page->slug;
			$this->buildPage($params['pv'], $templates, $page, $vars, false);
			$page->categories = $categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();
		
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($page->slug !== $oldSlug) && $model->childPageExists($page->parent_id, $page->slug))
			{
				$errors['page_slug'] = 'This slug already in use by a sibling of this page.';
			}
			elseif ($this->validatePage($params['pv'], $templates, $statuses, $callbacks, $vars, false, $errors))
			{
				// update page object
	
				if ($page->parent_id)
				{
					$page->makeSlug();
				}

				try
				{
					$this->pages_save($model, $page, $vars);
					if (isset($params['pv']['save']))
					{
						$this->session->flashSet('notice', 'Page saved successfully.');
						$this->redirect('/content/pages');
					}
					$vars['notice'] = 'Page saved successfully.';
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}

				$model->purgeCachePage($page);				// remove from cache, since we updated it
				$page = $model->fetchPageByID($pageID);	// refresh for new URI
				$page->categories = $categories;				// make sure we don't lose these

				// must refresh page parts from database so new field names get generated for newly added parts

				$page->parts = $model->fetchAllPageParts($page);
				$page->meta = NULL;
				$model->fetchPageMeta($page);
			}
		}
		else
		{
			$model->fetchPageCategories($page, true);
			$page->parts = $model->fetchAllPageParts($page);
			$model->fetchPageMeta($page);
			$vars['notice'] = $this->session->flashGet('notice');
		}

		$model->fetchPageEditor($page);

		if (!is_array($page->meta))
		{
			$page->meta = array();
		}

		$vars['selected_subtab'] = 'pages';
		$vars['action'] = 'edit';
		$vars['page_id'] = $pageID;
		$vars['page'] = $page;
		$vars['can_inherit'] = ($page->parent_id != 0);
		$vars['templates'] = $templates;
		$vars['filterClasses'] = $this->_filterClasses;
		$vars['filterNames'] = $this->_filterNames;
		$vars['page_types'] = $this->_pageTypes;
		$vars['statuses'] = $statuses;
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['callbacks'] = $callbacks;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:page:edit', $page);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function pages_delete($params)
	{
		if (!$pageID = @$params['pv']['page_id'])
		{
			if (!$pageID = @$params[0])
			{
				$pageID = 0;
			}
		}

		if (!$pageID)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'page not found'));
		}
		
		$model = $this->newAdminContentModel();

		if (!$rootPage = $model->fetchSimplePageByID($pageID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'page not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'pages', 'delete', $rootPage);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$count = $model->deletePageByID($pageID);
				$this->observer->notify('escher:site_change:content:page:delete', $rootPage);
				$this->session->flashSet('notice', $count . ($count === 1 ? ' page' : ' pages') . ' deleted successfully.');
				$this->redirect('/content/pages');
			}
		}
		
		$model->fetchPageDescendents($rootPage);
		
		$vars['selected_subtab'] = 'pages';
		$vars['action'] = 'delete';
		$vars['page_id'] = $pageID;
		$vars['root_page'] = $rootPage;

		$this->observer->notify('escher:render:before:content:page:delete', $rootPage);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	// temporary method - until we implement ajax page tree updates!
	
	protected function pages_fetch_children_recursive($model, $parent)
	{
		$model->fetchPageAuthor($parent);
		$parent->children = $model->fetchPages($parent);
		foreach ($parent->children as $child)
		{
			$this->pages_fetch_children_recursive($model, $child);
		}
	}
	
	protected function pages_list($params)
	{
		$model = $this->newAdminContentModel();
		
		if ($rootPage = $model->fetchPageByURI('/'))
		{
			$this->pages_fetch_children_recursive($model, $rootPage);
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'pages');

		$vars['selected_subtab'] = 'pages';
		$vars['action'] = 'list';
		$vars['root_page'] = $rootPage;
		$vars['model_names'] = $model->fetchAllModelNames();
		$vars['notice'] = $this->session->flashGet('notice');
		$vars['tree_state'] = isset($params['cv']['escherpagelist']) ? json_decode($params['cv']['escherpagelist'], true) : NULL;
		$vars['order_pages_url'] = $this->urlTo('/content/pages/move');

		$this->observer->notify('escher:render:before:content:page:list', $rootPage);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function pages_move($params)
	{
		// move a page via ajax
		
		$this->getCommonVars($vars);	// needed to avoid errors on the 403 page that is rendered (but not displayed) for permissions errors
		$this->getContentPerms($vars, 'pages', 'move');

		if (!$vars['can_move'])
		{
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}

		$model = $this->newAdminContentModel();
		
		// Fetch the new tree order, represented as a hash of [page_id => parent_id] pairs.
		// Note that we do not update the entire page tree to match this new page order.
		// We honor only the first changed page. Therefore, this method does not allow
		// reordering the entire page tree in one fell swoop. Only a single page may be moved at a time.
		// This is a safer operation that allows us to more easily guarantee tree consistency.
		
		$newTree = @$params['pv']['page'];
		$newTree[1] = 0;
		
		$changedParent = false;

		if (!empty($newTree))
		{
			// fetch our existing tree order
			
			$model->fetchPageOrder($oldTree, $parents);
			
			// walk through both trees, comparing pages IDs until we find a mismatch,
			// at which point we have found the page that has been moved
			
			$movedPageID = $affectedParentID = 0;
			while (list($newPageID, $newParentID) = each($newTree))
			{
				list($oldPageID, $oldParentInfo) = each($oldTree);
				$oldParentID = $oldParentInfo['parent_id'];
				if (($newPageID != $oldPageID) || ($newParentID != $oldParentID))
				{
					$movedPageID = $newPageID;
					$affectedParentID = $newParentID;
					
					do
					{
						if ($newParentID != $oldTree[$newPageID]['parent_id'])
						{
							$movedPageID = $newPageID;
							$affectedParentID = $newParentID;
							break;
						}
					} while (list($newPageID, $newParentID) = each($newTree));
					break;
				}
			}

			// if the page's parent has changed, update it
			// finally, update the position of each of the moved page's (possibly new) siblings
	
			if ($movedPageID)
			{
				// sanity-check the affected pages
				
				if (!isset($oldTree[$movedPageID]))
				{
					throw new SparkHTTPException_BadRequest(NULL, array('reason'=>'page does not exist'));
				}
				if (!isset($oldTree[$affectedParentID]))
				{
					throw new SparkHTTPException_BadRequest(NULL, array('reason'=>'new parent page does not exist'));
				}
				
				// check that we have permission to make ordering changes under the affected parent pages

				if ($parentPage = $model->fetchPageByID($affectedParentID))
				{
					$this->getContentPerms($vars, 'pages', 'move', $parentPage);
				}
				if (!$parentPage || !$vars['can_move'])
				{
					throw new SparkHTTPException_Forbidden(NULL, $vars);
				}
				
				// are we moving page from one parent to a different parent?
				
				if (($oldParent = $oldTree[$movedPageID]['parent_id']) != $affectedParentID)
				{
					if ($parentPage = $model->fetchPageByID($oldParent))
					{
						$this->getContentPerms($vars, 'pages', 'move', $parentPage);
					}
					if (!$parentPage || !$vars['can_move'])
					{
						throw new SparkHTTPException_Forbidden(NULL, $vars);
					}
					
					$changedParent = true;
				}

				// find all pages with this parent - these are the affected pages
				
				$affectedSiblings = array();
				
				foreach ($newTree as $pageID => $parentID)
				{
					if (!isset($oldTree[$pageID]))
					{
						throw new SparkHTTPException_BadRequest(NULL, array('reason'=>'sibling page does not exist'));
					}
					if ($parentID == $affectedParentID)
					{
						$affectedSiblings[] = $pageID;
					}
				}
	
				// if the page changed level, find all the parents of pages whose level needs updating
				// change in level is equal to the difference in level of the original parent and the new parent

				$parentIDs = array();
				if ($levelDelta = $oldTree[$affectedParentID]['level'] - ($oldTree[$movedPageID]['level'] - 1))
				{
					$this->addParentID($parentIDs, $movedPageID, $parents);
				}

				$model->updatePageOrder($movedPageID, $affectedParentID, $affectedSiblings, $parentIDs, $levelDelta);
				$this->observer->notify('escher:site_change:content:page:move', NULL);
			}
		}
		
		$this->display('', 'application/json', $changedParent ? '205 Reset Content' : NULL);
	}
	
	private function addParentID(&$pageIDs, $parentID, $parents)
	{
		if (isset($parents[$parentID]))
		{
			$pageIDs[] = $parentID;
	
			foreach ($parents[$parentID] as $childID)
			{
				if (isset($parents[$childID]))
				{
					$this->addParentID($pageIDs, $childID, $parents);
				}
			}
		}
	}

	//---------------------------------------------------------------------------

	protected function pages_save($model, $page, $vars)
	{
		// save page object
	
		if ($page->id)
		{
			$this->updateObjectEdited($page);
			$model->updatePage($page, ($vars['can_delete_categories'] || $vars['can_add_categories']));
			$this->observer->notify('escher:site_change:content:page:edit', $page);
		}
		else
		{
			$this->updateObjectCreated($page);
			$model->addPage($page);
			$this->observer->notify('escher:site_change:content:page:add', $page);
		}

		// save page parts
		
		if ($vars['can_edit_parts'] || $vars['can_add_parts'] || $vars['can_delete_parts'])
		{
			$existingPartNames = $model->fetchAllPagePartNames($page);
	
			if ($vars['can_edit_parts'] || $vars['can_add_parts'])
			{
				foreach ($page->parts as $part)
				{
					$isNewPart = !isset($existingPartNames[$part->name]);
					if (($vars['can_edit_parts'] && !$isNewPart) || ($vars['can_add_parts'] && $isNewPart))
					{
						$part->content_html = $part->filter_id ? $this->_filters[$part->filter_id]->filter($part->content) : $part->content;
						$part->page_id = $page->id;
						$model->updatePagePart($part);
						$this->observer->notify('escher:site_change:content:part:save', $part->name, $page);
					}
				}
			}

			// delete parts that were not submitted
	
			if ($vars['can_delete_parts'])
			{
				foreach ($page->parts as $part)
				{
					unset($existingPartNames[$part->name]);
				}
				if (!empty($existingPartNames))
				{
					$model->deletePageParts($page->id, $existingPartNames);
					$this->observer->notify('escher:site_change:content:part:delete', $existingPartNames, $page);
				}
			}
		}

		// save page metadata
	
		if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
		{
			$model->savePageMeta($page, $vars);
		}

		$this->observer->notify('escher:site_change:content:page:save', $page);
	}
	
	//---------------------------------------------------------------------------

	protected function buildPage($params, $templates, $page, $perms, $isModel = false)
	{
		// build page object

		if ($isModel)
		{
			$page->name = $params['model_name'];
			$page->slug = '';
			$page->breadcrumb = '';
			$prefix = 'model';
		}
		else
		{
			$page->title = $params['page_title'];
			$page->slug = $page->parent_id ? $params['page_slug'] : '';
			$page->breadcrumb = $params['page_breadcrumb'];
			$prefix = 'page';
		}

		if ($perms['can_edit_status'])
		{
			$page->status = $params["{$prefix}_status"];
		}
		if ($perms['can_edit_magic'])
		{
			$page->magical = $params["{$prefix}_magic"] ? true : false;
		}
		if ($perms['can_edit_cacheable'])
		{
			$page->cacheable = $params["{$prefix}_cacheable"];
		}
		if ($perms['can_edit_secure'])
		{
			$page->secure = $params["{$prefix}_secure"];
		}
		if ($perms['can_edit_pagetype'])
		{
			$page->type = $params["{$prefix}_type"];
		}
		if ($perms['can_edit_template'])
		{
			$page->template_name = @$params["{$prefix}_template_name"];
		}

		// build page metadata
	
		$meta = array();
		foreach ($params as $key => $val)
		{
			if (preg_match('/meta_(.*)/', $key, $matches))
			{
				$meta[$matches[1]] = $val;
			}
		}
		$page->meta = $meta;

		// build page parts
		
		$parts = array();
		foreach ($params as $key=>$val)
		{
			if (preg_match('/^page_part_(.*)_content$/', $key, $matches))
			{
				$name = $matches[1];
				
				// if part is disabled, we nullify the content for security purposes
				
				if (isset($params["page_part_{$name}_disabled"]))
				{
					$val = '';
				}
				
				// if adding a new part, there will be a name field, so we grab the name from there

				if (isset($params["page_part_{$name}_name"]))
				{
					$data['name'] = ContentObject::filterSlug($params["page_part_{$name}_name"]);
					$data['new'] = true;
				}
				else	// otherwise, derive the part name from the content name field
				{
					$data['name'] = $name;
				}
				if (isset($params["page_part_{$name}_type"]))
				{
					$data['type'] = ContentObject::filterSlug($params["page_part_{$name}_type"]);
				}
				else
				{
					$data['type'] = 'textarea';
				}
				if (isset($params["page_part_{$name}_content_filter"]))
				{
					$data['filter_id'] = intval($params["page_part_{$name}_content_filter"]);
				}
				else
				{
					$data['filter_id'] = 0;
				}

				$data['content'] = $val;

				$parts[$name] = $this->factory->manufacture('Part', $data);
			}
		}
		$page->parts = $parts;
	}
	
	//---------------------------------------------------------------------------

	protected function validatePage($params, $templates, $statuses, $callbacks, $perms, $isModel, &$errors)
	{
		$errors = array();
		
		// set errors
		
		if ($isModel)
		{
			$prefix = 'model';
			if (empty($params['model_name']))
			{
				$errors['model_name'] = 'Model name is required.';
			}
		}
		else
		{
			$prefix = 'page';
			if (empty($params['page_title']))
			{
				$errors['page_title'] = 'Page title is required.';
			}
		}
		
		if (!empty($params["{$prefix}_slug"]) && !preg_match('/^[0-9A-Za-z\-\.]*$/', $params["{$prefix}_slug"]))
		{
			$errors["{$prefix}_slug"] = 'Page slug may contain only alphanumeric characters, hyphens and periods.';
		}

		if ($perms['can_edit_template'])
		{
			if (!isset($params["{$prefix}_template_name"]))
			{
				$errors["{$prefix}_template_name"] = 'No page template selected.';
			}
			elseif (($params["{$prefix}_template_name"] !== '') && (array_search($params["{$prefix}_template_name"], $templates) === false))
			{
				$errors["{$prefix}_template_name"] = 'Invalid page template selected.';
			}
		}
		
		if ($perms['can_edit_pagetype'])
		{
			if (array_search($params["{$prefix}_type"], array_keys($this->_pageTypes)) === false)
			{
				$errors["{$prefix}_type"] = 'Invalid page type selected.';
			}
		}
		
		if ($perms['can_edit_status'])
		{
			if (!isset($statuses[$params["{$prefix}_status"]]))
			{
				$errors["{$prefix}_status"] = 'Invalid page status selected.';
			}
		}
		
		if ($perms['can_edit_magic'])
		{
			if (!in_array($params["{$prefix}_magic"], array('0', '1')))
			{
				$errors["{$prefix}_magic"] = 'Invalid page magic selected.';
			}
		}
		
		if ($perms['can_edit_cacheable'])
		{
			if (!in_array($params["{$prefix}_cacheable"], array('-1', '0', '1')))
			{
				$errors["{$prefix}_cacheable"] = 'Invalid cacheable setting selected.';
			}
		}
		
		if ($perms['can_edit_secure'])
		{
			if (!in_array($params["{$prefix}_secure"], array('-1', '0', '1')))
			{
				$errors["{$prefix}_secure"] = 'Invalid security setting selected.';
			}
		}
		
		$this->validateParts($callbacks, $params, $errors);

		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	protected function validateParts($callbacks, $params, &$errors)
	{
		foreach ($params as $key=>$val)
		{
			if (preg_match('/^page_part_(.*)_name$/', $key, $matches))
			{
				if (empty($val))
				{
					$errors[$matches[0]] = 'Part name is required.';
				}
				elseif (!preg_match('/^[a-z]/i', $val))		// name must begin with a letter
				{
					$errors[$matches[0]] = 'Part name must begin with a letter.';
				}
			}
			
			if (preg_match('/^page_part_(.*)_content$/', $key, $matches))
			{
				$fieldName = $matches[0];
				$name = $matches[1];

				// don't validate disabled parts, since they come in empty
				// not that we nullify them in buildPage() to prevent spoofing of disabled parts
			
				if (isset($params["page_part_{$name}_disabled"]))
				{
					continue;
				}

				$typeField = "page_part_{$name}_type";
				$type = isset($params[$typeField]) ? ContentObject::filterSlug($params[$typeField]) : 'textarea';

				if (isset($callbacks[$type]))
				{
					$callback = $callbacks[$type];
					if (is_array($callback))
					{
						$callback[1] = 'validate_' . $callback[1];
					}
					else
					{
						$callback = 'validate_' . $callback;
					}
					if (is_callable($callback))
					{
						$atts['val'] = $val;
						
						// call_user_func() won't do pass by reference, and this is faster anyway
						
						if (is_array($callback))
						{
							if (!$valid = $callback[0]->$callback[1]($atts))
							{
								$errors[$fieldName] = 'field_error_'.$type;
							}
						}
						else
						{
							if (!$valid = $callback($atts))
							{
								$errors[$fieldName] = 'field_error_'.$type;
							}
						}
					}
				}
			}
		}
	}
	
	//---------------------------------------------------------------------------

	protected function blocks_add($params)
	{
		$model = $this->newAdminContentModel();
		
		$block = $this->factory->manufacture('Block', array());

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'blocks', 'add');

		if (isset($params['pv']['save']))
		{
			// build block object from form data
			
			$this->buildBlock($params['pv'], $block);
			$block->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->blockExists($block->name))
			{
				$errors['block_name'] = 'A block with this name already exists.';
			}
			elseif ($this->validateBlock($params['pv'], $errors))
			{
				try
				{
					$this->updateObjectCreated($block);
					$block->content_html = $block->filter_id ? $this->_filters[$block->filter_id]->filter($block->content) : $block->content;
					$model->addBlock($block);
					$this->observer->notify('escher:site_change:content:block:add', $block);
					$this->session->flashSet('notice', 'Block added successfully.');
					$this->redirect('/content/blocks/edit/'.$block->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$block->categories = array();
		}
		
		$vars['selected_subtab'] = 'blocks';
		$vars['action'] = 'add';
		$vars['block'] = $block;
		$vars['selected_block_id'] = 0;
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['filterClasses'] = $this->_filterClasses;
		$vars['filterNames'] = $this->_filterNames;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:content:block:add', $block);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function blocks_edit($params)
	{
		$model = $this->newAdminContentModel();

		$blockNames = $model->fetchBlockNames();

		if (!$blockID = @$params['pv']['selected_block_id'])
		{
			if (!$blockID = @$params[0])
			{
				$blockID = 0;
			}
		}

		if (!$blockID && $first = each($blockNames))
		{
			$blockID = $first[0];
		}

		if ($blockID)
		{
			$block = $model->fetchBlock(intval($blockID));
		}
		else
		{
			$block = NULL;
		}
		
		if ($block)
		{
			$model->fetchBlockEditor($block);
		}

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'blocks', 'edit', $block);
		
		if (isset($params['pv']['save']))
		{
			$params['pv']['block_name'] = $block->name;

			// build block object from form data
			
			$oldName = $block->name;
			$this->buildBlock($params['pv'], $block);
			$block->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();
			
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($block->name !== $oldName) && $model->blockExists($block->name))
			{
				$errors['block_name'] = 'A block with this name already exists.';
			}
			elseif ($this->validateBlock($params['pv'], $errors))
			{
				try
				{
					$this->updateObjectEdited($block);
					$block->content_html = $block->filter_id ? $this->_filters[$block->filter_id]->filter($block->content) : $block->content;
					$model->updateBlock($block, ($vars['can_delete_categories'] || $vars['can_add_categories']));
					$this->observer->notify('escher:site_change:content:block:edit', $block);
					$vars['notice'] = 'Block saved successfully.';
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			if ($block)
			{
				$model->fetchBlockCategories($block, true);
			}
			$vars['notice'] = $this->session->flashGet('notice');
		}

		$vars['selected_subtab'] = 'blocks';
		$vars['action'] = 'edit';
		$vars['blocks'] = $blockNames;
		$vars['block'] = $block;
		$vars['selected_block_id'] = $blockID;
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['filterClasses'] = $this->_filterClasses;
		$vars['filterNames'] = $this->_filterNames;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:block:edit', $block);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function blocks_delete($params)
	{
		if (!$blockID = @$params['pv']['block_id'])
		{
			if (!$blockID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'block not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$block = $model->fetchBlock(intval($blockID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'block not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'blocks', 'delete', $block);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->deleteBlockByID($blockID);
				$this->observer->notify('escher:site_change:content:block:delete', $block);
				$this->session->flashSet('notice', 'Block deleted successfully.');
				$this->redirect('/content/blocks');
			}
		}
		
		$vars['selected_subtab'] = 'blocks';
		$vars['action'] = 'delete';
		$vars['block_id'] = $blockID;
		$vars['block_name'] = $block->name;

		$this->observer->notify('escher:render:before:content:block:delete', $block);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function buildBlock($params, $block)
	{
		// build block object

		$block->name = $params['block_name'];
		$block->title = $params['block_title'];
		$block->content = $params['block_content'];
		$block->filter_id = $params['block_content_filter'];
		$block->published = '';
		$block->edited = '';
		$block->author_id = 0;
		$block->editor_id = 0;
	}
	
	//---------------------------------------------------------------------------

	protected function validateBlock($params, &$errors)
	{
		$errors = array();
		
		// set errors
		
		if (empty($params['block_name']))
		{
			$errors['block_name'] = 'Block name is required.';
		}
		
		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	protected function images_add($params)
	{
		$model = $this->newAdminContentModel();
		
		$image = $this->factory->manufacture('Image', array('branch'=>1));

		$curUser = $this->app->get_user();
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'images', 'add');
		$vars['can_upload'] = $curUser->allowed('content:images:add:upload');

		if (isset($params['pv']['save']))
		{
			require($this->config->get('core_dir') . '/admin/lib/image_helper.php');
			$imageHelper = $this->factory->manufacture('ImageHelper');

			// build image object from form data
			
			$imageHelper->buildImage($params['pv'], $image);
			$image->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->imageExists($image->slug, -1, 1, $ignore))
			{
				$errors['image_name'] = 'A content image with this name already exists.';
			}
			elseif ($imageHelper->validateImage($params['pv'], $vars['can_upload'], $errors))
			{
				if ($vars['can_upload'])
				{
					$imageHelper->loadImage($image);
				}
				try
				{
					$image->makeSlug();
					$image->theme_id = -1;
					$this->updateObjectCreated($image);
					$model->addImage($image);
					$this->observer->notify('escher:site_change:content:image:add', $image);
					if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
					{
						$model->saveImageMeta($image, $vars);
					}
					$this->session->flashSet('notice', 'Image added successfully.');
					$this->redirect('/content/images/edit/'.$image->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$image->meta = array();
			$image->categories = array();
		}
		
		$vars['selected_subtab'] = 'images';
		$vars['action'] = 'add';
		$vars['image'] = $image;
		$vars['max_upload_size'] = $this->app->get_pref('max_upload_size');
		$vars['category_titles'] = $model->fetchCategoryNames();

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:image:add', $image);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function images_edit($params)
	{
		$image = NULL;
		
		if (!$imageID = @$params['pv']['selected_image_id'])
		{
			$imageID = @$params[0];
		}

		$model = $this->newAdminContentModel();
		
		if ($imageID)
		{
			$image = $model->fetchImage(intval($imageID), NULL, NULL, false);
		}
		$imageNames = $model->fetchImageNames(-1, 1);		// themeID -1 -> content image
		
		if (!$image)
		{
			if (!$imageID && $first = each($imageNames))
			{
				$imageID = $first[0];
			}
			$image = $model->fetchImage(intval($imageID), NULL, NULL, false);
		}

		if ($image)
		{
			$model->fetchImageEditor($image);
		}
		else
		{
			$imageID = 0;
			$image = NULL;
		}

		if ($image && ($image->theme_id != -1))	// this is a design image!
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
		}

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'images', 'edit', $image);
		if ($vars['can_upload'] = $vars['can_edit'] && $image)
		{
			$curUser = $this->app->get_user();
			$perm = 'content:images:edit:' . ($curUser->id == $image->author_id ? 'own' : 'any') . ':replace';
			$vars['can_upload'] = $curUser->allowed($perm);
		}
		
		if (isset($params['pv']['save']))
		{
			require($this->config->get('core_dir') . '/admin/lib/image_helper.php');
			$imageHelper = $this->factory->manufacture('ImageHelper');

			$params['pv']['image_name'] = $image->slug;

			// build image object from form data
			
			$oldSlug = $image->slug;
			$imageHelper->buildImage($params['pv'], $image);
			$image->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($image->slug !== $oldSlug) && $model->imageExists($image->slug, -1, 1, $ignore))
			{
				$errors['image_name'] = 'A content image with this name already exists.';
			}
			elseif ($imageHelper->validateImage($params['pv'],  $vars['can_upload'], $errors))
			{
				$image = clone $image;			// clone the image so we don't remove cached content
				$image->content = NULL;			// only update image data if we load a new image
				if ($vars['can_upload'])
				{
					$imageHelper->loadImage($image);
				}
				try
				{
					$this->updateObjectEdited($image);
					$model->updateImage($image, ($vars['can_delete_categories'] || $vars['can_add_categories']));
					$this->observer->notify('escher:site_change:content:image:edit', $image);
					if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
					{
						$model->saveImageMeta($image, $vars);
					}
					$vars['notice'] = 'Image saved successfully.';
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}

				// must refresh image metadata from database so new field names get generated for newly added metadata

				$image->meta = NULL;
				$model->fetchImageMeta($image);
			}
		}
		else
		{
			if ($image)
			{
				$model->fetchImageMeta($image);
				$model->fetchImageCategories($image, true);
			}
			$vars['notice'] = $this->session->flashGet('notice');
		}
		
		if ($image)
		{
			$image->display_url = $this->urlTo('/content/images/display/' . $image->id);
			if (!is_array($image->meta))
			{
				$image->meta = array();
			}
		}
		
		$vars['selected_subtab'] = 'images';
		$vars['action'] = 'edit';
		$vars['images'] = $imageNames;
		$vars['image'] = $image;
		$vars['selected_image_id'] = $imageID;
		$vars['max_upload_size'] = $this->app->get_pref('max_upload_size');
		$vars['category_titles'] = $model->fetchCategoryNames();
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:content:image:edit', $image);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function images_delete($params)
	{
		if (!$imageID = @$params['pv']['image_id'])
		{
			if (!$imageID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$image = $model->fetchImage(intval($imageID), NULL, NULL, false))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
		}
		
		if ($image->theme_id != -1)	// this is a design image!
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'images', 'delete', $image);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->deleteImageByID($imageID);
				$this->observer->notify('escher:site_change:content:image:delete', $image);
				$this->session->flashSet('notice', 'Image deleted successfully.');
				$this->redirect('/content/images');
			}
		}
		
		$vars['selected_subtab'] = 'images';
		$vars['action'] = 'delete';
		$vars['image_id'] = $imageID;
		$vars['image_name'] = $image->slug;

		$this->observer->notify('escher:render:before:content:image:delete', $image);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function images_display($params)
	{
		if (!$imageID = intval(@$params[0]))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
		}
		
		$model = $this->newAdminContentModel();
		$image = $model->fetchImage($imageID, NULL, NULL, true);

		if ($image)
		{
			$curUser = $this->app->get_user();
			$suffix = ($curUser->id == $image->author_id) ? 'own' : 'any';
			if ($curUser->allowed('content:images:display:'.$suffix))
			{
				$this->display($image->content, $image->ctype);
			}
		}
	}
	
	//---------------------------------------------------------------------------

	protected function files_add($params)
	{
		$model = $this->newAdminContentModel();
		
		$file = $this->factory->manufacture('File', array());

		$statuses = _Page::statusOptions();
		unset($statuses[_Page::Status_expired]);

		$curUser = $this->app->get_user();
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'files', 'add');
		$vars['can_upload'] = $curUser->allowed('content:files:add:upload');

		if (isset($params['pv']['save']))
		{
			require($this->config->get('core_dir') . '/admin/lib/file_helper.php');
			$fileHelper = $this->factory->manufacture('FileHelper');

			// build file object from form data
			
			$fileHelper->buildFile($params['pv'], $file);
			$file->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->fileExists($file->slug))
			{
				$errors['file_name'] = 'A file with this name already exists.';
			}
			elseif ($fileHelper->validateFile($params['pv'],  $vars['can_upload'], $errors))
			{
				if ($vars['can_upload'])
				{
					$fileHelper->loadFile($file);
				}
				try
				{
					$file->makeSlug();
					$this->updateObjectCreated($file);
					$model->addFile($file);
					$this->observer->notify('escher:site_change:content:file:add', $file);
					if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
					{
						$model->saveFileMeta($file, $vars);
					}
					$this->session->flashSet('notice', 'File added successfully.');
					$this->redirect('/content/files/edit/'.$file->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$file->meta = array();
			$file->categories = array();
		}
		
		$vars['selected_subtab'] = 'files';
		$vars['action'] = 'add';
		$vars['file'] = $file;
		$vars['max_upload_size'] = $this->app->get_pref('max_upload_size');
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['statuses'] = $statuses;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:file:add', $file);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function files_edit($params)
	{
		$file = NULL;
		
		if (!$fileID = @$params['pv']['selected_file_id'])
		{
			$fileID = @$params[0];
		}

		$model = $this->newAdminContentModel();
		
		if ($fileID)
		{
			$file = $model->fetchFile(intval($fileID));
		}
		$fileNames = $model->fetchFileNames();
		
		if (!$file)
		{
			if (!$fileID && $first = each($fileNames))
			{
				$fileID = $first[0];
			}
			$file = $model->fetchFile(intval($fileID));
		}

		if ($file)
		{
			$model->fetchFileEditor($file);
		}
		else
		{
			$fileID = 0;
			$file = NULL;
		}
		
		$statuses = _Page::statusOptions();
		
		if ($file)
		{
			if ($file->status != _Page::Status_expired)
			{
				unset($statuses[_Page::Status_expired]);
			}
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'files', 'edit', $file);
		if ($vars['can_upload'] = $vars['can_edit'] && $file)
		{
			$curUser = $this->app->get_user();
			$perm = 'content:files:edit:' . ($curUser->id == $file->author_id ? 'own' : 'any') . ':replace';
			$vars['can_upload'] = $curUser->allowed($perm);
		}

		if (isset($params['pv']['save']))
		{
			require($this->config->get('core_dir') . '/admin/lib/file_helper.php');
			$fileHelper = $this->factory->manufacture('FileHelper');

			$params['pv']['file_name'] = $file->slug;

			// build file object from form data
			
			$oldSlug = $file->slug;
			$fileHelper->buildFile($params['pv'], $file);
			$file->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($file->slug !== $oldSlug) && $model->fileExists($file->slug))
			{
				$errors['file_name'] = 'A file with this name already exists.';
			}
			elseif ($fileHelper->validateFile($params['pv'],  $vars['can_upload'], $errors))
			{
				$file = clone $file;				// clone the file so we don't remove cached content
				$file->content = NULL;			// only update file data if we load a new file
				if ($vars['can_upload'])
				{
					$fileHelper->loadFile($file);
				}
				try
				{
					$this->updateObjectEdited($file);
					$model->updateFile($file, ($vars['can_delete_categories'] || $vars['can_add_categories']));
					$this->observer->notify('escher:site_change:content:file:edit', $file);
					if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
					{
						$model->saveFileMeta($file, $vars);
					}
					$vars['notice'] = 'File saved successfully.';
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}

				// must refresh file metadata from database so new field names get generated for newly added metadata

				$file->meta = NULL;
				$model->fetchFileMeta($file);
			}
		}
		else
		{
			if ($file)
			{
				$model->fetchFileMeta($file);
				$model->fetchFileCategories($file, true);
			}
			$vars['notice'] = $this->session->flashGet('notice');
		}
		
		if ($file)
		{
			if (!is_array($file->meta))
			{
				$file->meta = array();
			}
		}
		
		$vars['selected_subtab'] = 'files';
		$vars['action'] = 'edit';
		$vars['files'] = $fileNames;
		$vars['file'] = $file;
		$vars['selected_file_id'] = $fileID;
		$vars['max_upload_size'] = $this->app->get_pref('max_upload_size');
		$vars['category_titles'] = $model->fetchCategoryNames();
		$vars['statuses'] = $statuses;
	
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:content:file:edit', $file);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function files_delete($params)
	{
		if (!$fileID = @$params['pv']['file_id'])
		{
			if (!$fileID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'file not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$file = $model->fetchFile(intval($fileID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'file not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'files', 'delete', $file);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->deleteFileByID($fileID);
				$this->observer->notify('escher:site_change:content:file:delete', $file);
				$this->session->flashSet('notice', 'File deleted successfully.');
				$this->redirect('/content/files');
			}
		}
		
		$vars['selected_subtab'] = 'files';
		$vars['action'] = 'delete';
		$vars['file_id'] = $fileID;
		$vars['file_name'] = $file->slug;

		$this->observer->notify('escher:render:before:content:file:delete', $file);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function links_add($params)
	{
		$model = $this->newAdminContentModel();
		
		$link = $this->factory->manufacture('Link', array());

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'links', 'add');

		if (isset($params['pv']['save']))
		{
			// build link object from form data
			
			$this->buildLink($params['pv'], $link);
			$link->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->linkExists($link->name))
			{
				$errors['link_name'] = 'A link with this name already exists.';
			}
			elseif ($this->validateLink($params['pv'], $errors))
			{
				try
				{
					$this->updateObjectCreated($link);
					$model->addLink($link);
					$this->observer->notify('escher:site_change:content:link:add', $link);
					if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
					{
						$model->saveLinkMeta($link, $vars);
					}
					$this->session->flashSet('notice', 'Link added successfully.');
					$this->redirect('/content/links/edit/'.$link->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$link->meta = array();
			$link->categories = array();
		}
		
		$vars['selected_subtab'] = 'links';
		$vars['action'] = 'add';
		$vars['link'] = $link;
		$vars['selected_link_id'] = 0;
		$vars['category_titles'] = $model->fetchCategoryNames();
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:content:link:add', $link);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function links_edit($params)
	{
		$model = $this->newAdminContentModel();

		$linkNames = $model->fetchLinkNames();

		if (!$linkID = @$params['pv']['selected_link_id'])
		{
			if (!$linkID = @$params[0])
			{
				$linkID = 0;
			}
		}

		if (!$linkID && $first = each($linkNames))
		{
			$linkID = $first[0];
		}

		if ($linkID)
		{
			$link = $model->fetchLink(intval($linkID));
		}
		else
		{
			$link = NULL;
		}

		if ($link)
		{
			$model->fetchLinkEditor($link);
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'links', 'edit', $link);
		
		if (isset($params['pv']['save']))
		{
			$params['pv']['link_name'] = $link->name;

			// build link object from form data
			
			$oldName = $link->name;
			$this->buildLink($params['pv'], $link);
			$link->categories = isset($params['pv']['add_categories']) ? $model->fetchCategoriesByID($params['pv']['add_categories'], true) : array();
			
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($link->name !== $oldName) && $model->linkExists($link->name))
			{
				$errors['link_name'] = 'A link with this name already exists.';
			}
			elseif ($this->validateLink($params['pv'], $errors))
			{
				try
				{
					$this->updateObjectEdited($link);
					$model->updateLink($link, ($vars['can_delete_categories'] || $vars['can_add_categories']));
					$this->observer->notify('escher:site_change:content:link:edit', $link);
					if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
					{
						$model->saveLinkMeta($link, $vars);
					}
					$vars['notice'] = 'Link saved successfully.';
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}

				// must refresh file metadata from database so new field names get generated for newly added metadata

				$link->meta = NULL;
				$model->fetchLinkMeta($link);
			}
		}
		else
		{
			if ($link)
			{
				$model->fetchLinkMeta($link);
				$model->fetchLinkCategories($link, true);
			}
			$vars['notice'] = $this->session->flashGet('notice');
		}

		if ($link)
		{
			if (!is_array($link->meta))
			{
				$link->meta = array();
			}
		}
		
		$vars['selected_subtab'] = 'links';
		$vars['action'] = 'edit';
		$vars['links'] = $linkNames;
		$vars['link'] = $link;
		$vars['selected_link_id'] = $linkID;
		$vars['category_titles'] = $model->fetchCategoryNames();
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:link:edit', $link);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function links_delete($params)
	{
		if (!$linkID = @$params['pv']['link_id'])
		{
			if (!$linkID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'link not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$link = $model->fetchLink(intval($linkID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'link not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'links', 'delete', $link);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->deleteLinkByID($linkID);
				$this->observer->notify('escher:site_change:content:link:delete', $link);
				$this->session->flashSet('notice', 'Link deleted successfully.');
				$this->redirect('/content/links');
			}
		}
		
		$vars['selected_subtab'] = 'links';
		$vars['action'] = 'delete';
		$vars['link_id'] = $linkID;
		$vars['link_name'] = $link->name;

		$this->observer->notify('escher:render:before:content:link:delete', $link);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function buildLink($params, $link)
	{
		// build link object

		$link->name = $params['link_name'];
		$link->title = $params['link_title'];
		$link->description = $params['link_description'];
		$link->url = $params['link_url'];

		// build link metadata
	
		$meta = array();
		foreach ($params as $key => $val)
		{
			if (preg_match('/meta_(.*)/', $key, $matches))
			{
				$meta[$matches[1]] = $val;
			}
		}
		$link->meta = $meta;
	}
	
	//---------------------------------------------------------------------------

	protected function validateLink($params, &$errors)
	{
		$errors = array();
		
		// set errors
		
		if (empty($params['link_name']))
		{
			$errors['link_name'] = 'Link name is required.';
		}
		
		if (empty($params['link_url']) || !SparkUtil::valid_url($params['link_url']))
		{
			$errors['link_url'] = 'Link URL is not valid.';
		}
		
		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	protected function categories_add($params)
	{
		if (!$parentID = @$params['pv']['parent_id'])
		{
			if (!$parentID = @$params[0])
			{
				$parentID = 0;
			}
		}

		$model = $this->newAdminContentModel();

		$category = $this->factory->manufacture('Category', array('parent_id'=>$parentID));

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'categories', 'add');

		if (isset($params['pv']['save']))
		{
			// build category object from form data
			
			$this->buildCategory($params['pv'], $category);

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->childCategoryExists($category->parent_id, $category->slug))
			{
				$errors['category_slug'] = 'This slug already in use by a sibling of this category.';
			}
			elseif ($this->validateCategory($params['pv'], $errors))
			{
				// add category object
	
				if ($parentID)
				{
					if (!$parentCategory = $model->fetchCategory(intval($parentID)))
					{
						throw new SparkHTTPException_NotFound(NULL, array('reason'=>'category not found'));
					}
					$category->level = $parentCategory->level + 1;
				}
				else
				{
					$category->level = 0;
				}

				$category->makeSlug();

				try
				{
					$model->addCategory($category);
					$this->observer->notify('escher:site_change:content:category:add', $category);
					$this->session->flashSet('notice', 'Category added successfully.');
					$this->redirect('/content/categories');
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}

		$vars['selected_subtab'] = 'categories';
		$vars['action'] = 'add';
		$vars['parent_id'] = $parentID;
		$vars['category'] = $category;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:content:category:add', $category);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function categories_edit($params)
	{
		if (!$categoryID = @$params['pv']['category_id'])
		{
			if (!$categoryID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'category not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$category = $model->fetchCategory(intval($categoryID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'category not found'));
		}

		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'categories', 'edit');
		
		if (isset($params['pv']['save']))
		{
			// build category object from form data
			
			$oldSlug = $category->slug;
			$this->buildCategory($params['pv'], $category);
			
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($category->slug !== $oldSlug) && $model->childCategoryExists($category->parent_id, $category->slug))
			{
				$errors['category_slug'] = 'This slug already in use by a sibling of this category.';
			}
			elseif ($this->validateCategory($params['pv'], $errors))
			{
				$category->makeSlug();

				try
				{
					$model->updateCategory($category);
					$this->observer->notify('escher:site_change:content:category:edit', $category);
					$vars['notice'] = 'Category saved successfully.';
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		else
		{
			$vars['notice'] = $this->session->flashGet('notice');
		}

		$vars['selected_subtab'] = 'categories';
		$vars['action'] = 'edit';
		$vars['category'] = $category;
		$vars['category_id'] = $categoryID;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:content:category:edit', $category);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function categories_delete($params)
	{
		if (!$categoryID = @$params['pv']['category_id'])
		{
			if (!$categoryID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'category not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$category = $model->fetchCategory(intval($categoryID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'category not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'categories', 'delete');

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$count = $model->deleteCategoryByID($categoryID);
				$this->observer->notify('escher:site_change:content:category:delete', $category);
				$this->session->flashSet('notice', $count . ($count === 1 ? ' category' : ' categories') . ' deleted successfully.');
				$this->redirect('/content/categories');
			}
		}
		
		$model->fetchCategoryDescendents($category);

		$vars['selected_subtab'] = 'categories';
		$vars['action'] = 'delete';
		$vars['category_id'] = $categoryID;
		$vars['category_title'] = $category->title;
		$vars['category'] = $category;

		$this->observer->notify('escher:render:before:content:category:delete', $category);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function categories_list($params)
	{
		$model = $this->newAdminContentModel();
		
		$categories = $model->fetchAllCategories(true);
		
		$this->getCommonVars($vars);
		$this->getContentPerms($vars, 'categories');

		$vars['selected_subtab'] = 'categories';
		$vars['action'] = 'list';
		$vars['categories'] = $categories;
		$vars['notice'] = $this->session->flashGet('notice');
		$vars['tree_state'] = isset($params['cv']['eschercatlist']) ? json_decode($params['cv']['eschercatlist'], true) : NULL;

		$this->observer->notify('escher:render:before:content:category:list', $categories);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function buildCategory($params, $category)
	{
		// build category object
	
		$category->title = $params['category_title'];
		$category->slug = $params['category_slug'];
		$category->position = 0;
	}
	
	//---------------------------------------------------------------------------

	protected function validateCategory($params, &$errors)
	{
		$errors = array();
		
		// set errors
		
		if (empty($params['category_title']))
		{
			$errors['category_title'] = 'Category title is required.';
		}
		
		if (!empty($params['category_slug']))
		{
			if (!preg_match('/^[0-9A-Za-z\-\.]*$/', $params['category_slug']))
			{
				$errors['category_slug'] = 'Category slug may contain only alphanumeric characters, hyphens and periods.';
			}
		}
		
		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	protected function getContentPerms(&$vars, $type, $action = '', $object = NULL)
	{
		$curUser = $this->app->get_user();
		$prefix = "content:{$type}:";
		
		$vars['can_add'] = $curUser->allowed($prefix.'add');
		$vars['can_edit'] = $curUser->allowed($prefix.'edit');
		$vars['can_delete'] = $curUser->allowed($prefix.'delete');
		$vars['can_move'] = $curUser->allowed($prefix.'move');
				
		if ($object)
		{
			$suffix = ($curUser->id == $object->author_id) ? ':own' : ':any';
			if ($vars['can_edit'])
			{
				$vars['can_edit'] = $curUser->allowed($prefix.'edit'.$suffix);
			}
			if ($vars['can_delete'])
			{
				$vars['can_delete'] = $curUser->allowed($prefix.'delete'.$suffix);
			}
			{
				$vars['can_move'] = $curUser->allowed($prefix.'move'.$suffix);
			}
			
			// categories
			
			if ($vars['can_edit_categories'] = $vars['can_edit'])
			{
				$vars['can_edit_categories'] = $curUser->allowed($prefix.'edit'.$suffix.':categories');
			}
			if ($vars['can_add_categories'] = $vars['can_edit_categories'])
			{
				$vars['can_add_categories'] = $curUser->allowed($prefix.'edit'.$suffix.':categories:add');
			}
			if ($vars['can_delete_categories'] = $vars['can_edit_categories'])
			{
				$vars['can_delete_categories'] = $curUser->allowed($prefix.'edit'.$suffix.':categories:delete');
			}
		}

		$vars['can_save'] = ($action === 'add' && $vars['can_add']) || ($action === 'edit' && $vars['can_edit']);

		if (!isset($vars['can_edit_meta']))
		{
			$vars['can_edit_meta'] = $vars['can_add_meta'] = $vars['can_delete_meta'] = $vars['can_save'];
		}

		if (!isset($vars['can_edit_categories']))
		{
			$vars['can_edit_categories'] = $vars['can_add_categories'] = $vars['can_delete_categories'] = $vars['can_save'];
		}
	}

	//---------------------------------------------------------------------------

	protected function getContentModelsPerms(&$vars, $action = '', $object = NULL)
	{
		$curUser = $this->app->get_user();

		if ($action === 'add')
		{
			$vars['can_edit_parts'] = $vars['can_add_parts'] = $vars['can_delete_parts'] = $vars['can_add'];
			$vars['can_edit_template'] = $vars['can_edit_pagetype'] = $vars['can_edit_status'] = $vars['can_edit_magic'] = $vars['can_edit_cacheable'] = $vars['can_edit_secure'] = $vars['can_add'];
		}
		
		elseif ($object)
		{
			if ($object instanceof PageModel)
			{
				// metatdata
				
				if ($vars['can_edit_meta'])
				{
					$vars['can_edit_meta'] = $curUser->allowed('content:models:edit:meta');
				}
				if ($vars['can_add_meta'] = $vars['can_edit_meta'])
				{
					$vars['can_add_meta'] = $curUser->allowed('content:models:edit:meta:add');
				}
				if ($vars['can_delete_meta'] = $vars['can_edit_meta'])
				{
					$vars['can_delete_meta'] = $curUser->allowed('content:models:edit:meta:delete');
				}
				if ($vars['can_edit_meta'])
				{
					$vars['can_edit_meta'] = $curUser->allowed('content:models:edit:meta:change');
				}

				// categories
				
				if ($vars['can_edit_categories'] = $vars['can_edit'])
				{
					$vars['can_edit_categories'] = $curUser->allowed('content:models:edit:categories');
				}
				if ($vars['can_add_categories'] = $vars['can_edit_categories'])
				{
					$vars['can_add_categories'] = $curUser->allowed('content:models:edit:categories:add');
				}
				if ($vars['can_delete_categories'] = $vars['can_edit_categories'])
				{
					$vars['can_delete_categories'] = $curUser->allowed('content:models:edit:categories:delete');
				}

				// parts

				if ($vars['can_edit_parts'] = $vars['can_edit'])
				{
					$vars['can_edit_parts'] = $curUser->allowed('content:models:edit:parts');
				}
				if ($vars['can_add_parts'] = $vars['can_edit_parts'])
				{
					$vars['can_add_parts'] = $curUser->allowed('content:models:edit:parts:add');
				}
				if ($vars['can_delete_parts'] = $vars['can_edit_parts'])
				{
					$vars['can_delete_parts'] = $curUser->allowed('content:models:edit:parts:delete');
				}
				if ($vars['can_edit_parts'])
				{
					$vars['can_edit_parts'] = $curUser->allowed('content:models:edit:parts:change');
				}
				
				// other model attributes
									
				if ($vars['can_edit'])
				{
					$vars['can_edit_template'] =  $curUser->allowed('content:models:edit:template');
					$vars['can_edit_pagetype'] = $curUser->allowed('content:models:edit:pagetype');
					$vars['can_edit_status'] = $curUser->allowed('content:models:edit:status');
					$vars['can_edit_magic'] = $curUser->allowed('content:models:edit:magic');
					$vars['can_edit_cacheable'] = $curUser->allowed('content:models:edit:cacheable');
					$vars['can_edit_secure'] = $curUser->allowed('content:models:edit:secure');
				}
				else
				{
					$vars['can_edit_template'] = false;
					$vars['can_edit_pagetype'] = false;
					$vars['can_edit_status'] = false;
					$vars['can_edit_magic'] = false;
					$vars['can_edit_cacheable'] = false;
					$vars['can_edit_secure'] = false;
				}
			}
		}
	}
	
	//---------------------------------------------------------------------------

	protected function getContentPagesPerms(&$vars, $action = '', $object = NULL)
	{
		if ($object)
		{
			$curUser = $this->app->get_user();
			$suffix = ($curUser->id == $object->author_id) ? 'own' : 'any';

			if ($object instanceof Page)
			{
				if ($action === 'add')		// object is parent page in this case
				{
					if ($vars['can_add'])		
					{
						$vars['can_save'] = $vars['can_add'] = $curUser->allowed('content:pages:add:'.$suffix);
					}
					
					$vars['can_edit_parts'] = $vars['can_add_parts'] = $vars['can_delete_parts'] = $vars['can_add'];
					$vars['can_edit_template'] = $vars['can_edit_pagetype'] = $vars['can_edit_status'] = $vars['can_edit_magic'] = $vars['can_edit_cacheable'] = $vars['can_edit_secure'] = $vars['can_add'];
				}
				
				else
				{
					// metatdata
					
					if ($vars['can_edit_meta'])
					{
						$vars['can_edit_meta'] = $curUser->allowed('content:pages:edit:'.$suffix.':meta');
					}
					if ($vars['can_add_meta'] = $vars['can_edit_meta'])
					{
						$vars['can_add_meta'] = $curUser->allowed('content:pages:edit:'.$suffix.':meta:add');
					}
					if ($vars['can_delete_meta'] = $vars['can_edit_meta'])
					{
						$vars['can_delete_meta'] = $curUser->allowed('content:pages:edit:'.$suffix.':meta:delete');
					}
					if ($vars['can_edit_meta'])
					{
						$vars['can_edit_meta'] = $curUser->allowed('content:pages:edit:'.$suffix.':meta:change');
					}

					// parts
	
					if ($vars['can_edit_parts'] = $vars['can_edit'])
					{
						$vars['can_edit_parts'] = $curUser->allowed('content:pages:edit:'.$suffix.':parts');
					}
					if ($vars['can_add_parts'] = $vars['can_edit_parts'])
					{
						$vars['can_add_parts'] = $curUser->allowed('content:pages:edit:'.$suffix.':parts:add');
					}
					if ($vars['can_delete_parts'] = $vars['can_edit_parts'])
					{
						$vars['can_delete_parts'] = $curUser->allowed('content:pages:edit:'.$suffix.':parts:delete');
					}
					if ($vars['can_edit_parts'])
					{
						$vars['can_edit_parts'] = $curUser->allowed('content:pages:edit:'.$suffix.':parts:change');
					}
					
					// other page attributes
										
					if ($vars['can_edit'])
					{
						$vars['can_edit_template'] =  $curUser->allowed('content:pages:edit:'.$suffix.':template');
						$vars['can_edit_pagetype'] = $curUser->allowed('content:pages:edit:'.$suffix.':pagetype');
						$vars['can_edit_status'] = $curUser->allowed('content:pages:edit:'.$suffix.':status');
						$vars['can_edit_magic'] = $curUser->allowed('content:pages:edit:'.$suffix.':magic');
						$vars['can_edit_cacheable'] = $curUser->allowed('content:pages:edit:'.$suffix.':cacheable');
						$vars['can_edit_secure'] = $curUser->allowed('content:pages:edit:'.$suffix.':secure');
					}
					else
					{
						$vars['can_edit_template'] = false;
						$vars['can_edit_pagetype'] = false;
						$vars['can_edit_status'] = false;
						$vars['can_edit_magic'] = false;
						$vars['can_edit_cacheable'] = false;
						$vars['can_edit_secure'] = false;
					}
				}
			}
		}
	}
	
	//---------------------------------------------------------------------------

}
