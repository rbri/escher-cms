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

class _DesignController extends EscherAdminController
{
	private $_tabs;

	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();
		$this->app->build_tabs($this->_tabs, array('branches', 'themes', 'templates', 'snippets', 'tags', 'styles', 'scripts', 'images'), 'design');
	}

	//---------------------------------------------------------------------------

	public function &get_tabs()
	{
		return $this->_tabs;
	}

	//---------------------------------------------------------------------------

	public function action_index($params)
	{
		$this->session->flashKeep('html_alert');

		if (in_array('templates', $this->_tabs))
		{
			$this->redirect('/design/templates');
		}
		elseif ($first = current($this->_tabs))
		{
			$this->redirect('/design/'.$first);
		}
		else
		{
			$this->getCommonVars($vars);
			throw new SparkHTTPException_Forbidden(NULL, $vars);
		}
	}

	//---------------------------------------------------------------------------

	public function action_branches($params)
	{
		switch (@$params[0])
		{
			case 'edit':
				return $this->branches_edit($this->dropParam($params));
			case 'push':
				return $this->branches_push($this->dropParam($params));
			case 'rollback':
				return $this->branches_rollback($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->branches_list($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_themes($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->themes_add($this->dropParam($params));
			case 'edit':
				return $this->themes_edit($this->dropParam($params));
			case 'delete':
				return $this->themes_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->themes_list($params);
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_templates($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->templates_add($this->dropParam($params));
			case 'edit':
				return $this->templates_edit($this->dropParam($params));
			case 'delete':
				return $this->templates_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->templates_edit($params);
				}
		}	
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_snippets($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->snippets_add($this->dropParam($params));
			case 'edit':
				return $this->snippets_edit($this->dropParam($params));
			case 'delete':
				return $this->snippets_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->snippets_edit($params);
				}
		}	
			
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_tags($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->tags_add($this->dropParam($params));
			case 'edit':
				return $this->tags_edit($this->dropParam($params));
			case 'delete':
				return $this->tags_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->tags_edit($params);
				}
		}	
			
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_styles($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->styles_add($this->dropParam($params));
			case 'edit':
				return $this->styles_edit($this->dropParam($params));
			case 'delete':
				return $this->styles_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->styles_edit($params);
				}
		}	
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	public function action_scripts($params)
	{
		switch (@$params[0])
		{
			case 'add':
				return $this->scripts_add($this->dropParam($params));
			case 'edit':
				return $this->scripts_edit($this->dropParam($params));
			case 'delete':
				return $this->scripts_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					return $this->scripts_edit($params);
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

	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected function getCommonVars(&$vars)
	{
		parent::getCommonVars($vars);
		$vars['subtabs'] = $this->_tabs;
		$vars['selected_tab'] = 'design';
	}

	//---------------------------------------------------------------------------

	protected function branches_list($params)
	{
		$model = $this->newModel('Branch');
		
		$branches = $model->fetchAllBranches();
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_manage'] = $curUser->allowed('design:branches');
		$vars['can_edit'] = $curUser->allowed('design:branches:edit');
		$vars['can_push'] = $curUser->allowed('design:branches:push');
		$vars['can_rollback'] = $curUser->allowed('design:branches:rollback');

		$vars['selected_subtab'] = 'branches';
		$vars['action'] = 'list';
		$vars['branches'] = $branches;
		$vars['notice'] = $this->session->flashGet('notice');

		$this->observer->notify('escher:render:before:design:branch:list', $branches);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function branches_edit($params)
	{
		if (!$branchID = @$params['pv']['branch_id'])
		{
			if (!$branchID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
			}
		}

		if ($branchID <= 1)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}

		$model = $this->newModel('Branch');

		if (!$branch = $model->fetchBranch($branchID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		if (!$toBranch = $model->fetchBranch($branchID-1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_manage'] = $curUser->allowed('design:branches');
		$vars['can_edit'] = $curUser->allowed('design:branches:edit');
		$vars['can_push'] = $curUser->allowed('design:branches:push');
		$vars['can_rollback'] = $curUser->allowed('design:branches:rollback');

		if (isset($params['pv']['push']) || isset($params['pv']['rollback']))
		{
			// grab/remember checkbox values
			
			foreach ($params['pv'] as $key => $val)
			{
				if (preg_match('/(.*)-(\d+)$/', $key, $matches))
				{
					$vars['ticks'][$matches[0]] = true;
					$changes[$matches[1]][] = $matches[2];
				}
			}

			try
			{
				if (isset($params['pv']['push']))
				{
					if (!$vars['can_push'])
					{
						$vars['warning'] = 'Permission denied.';
					}
					elseif (empty($vars['ticks']))
					{
						$vars['warning'] = 'No assets selected. Push canceled.';
					}
					elseif (!isset($params['pv']['push_confirmed']))
					{
						$vars['confirm_push'] = true;
					}
					elseif (!empty($changes))
					{
						$model->pushBranchPartialByID($branchID, $changes);
						$this->observer->notify('escher:site_change:design:branch:push', $branch);
						$vars['notice'] = 'Selected changes were pushed successfully.';
					}
				}
		
				elseif (isset($params['pv']['rollback']))
				{
					if (!$vars['can_rollback'])
					{
						$vars['warning'] = 'Permission denied.';
					}
					elseif (empty($vars['ticks']))
					{
						$vars['warning'] = 'No assets selected. Rollback canceled.';
					}
					elseif (!isset($params['pv']['rollback_confirmed']))
					{
						$vars['confirm_rollback'] = true;
					}
					elseif (!empty($changes))
					{
						$model->rollbackBranchPartialByID($branchID, $changes);
						$this->observer->notify('escher:site_change:design:branch:rollback', $branch);
						$vars['notice'] = 'Selected changes were rolled back successfully.';
					}
				}
			}
			catch (SparkDBException $e)
			{
				$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
			}
		}
		
		$changes = array();
		foreach (array('theme', 'template', 'snippet', 'tag', 'style', 'script', 'image') as $table)
		{
			if ($changedAssets = $model->getBranchChanges($branchID, $table))
			{
				$changes[$table] = $changedAssets;
			}
		}

		$vars['action'] = 'edit';
		$vars['selected_subtab'] = 'branches';
		$vars['branch'] = $branch;
		$vars['changes'] = $changes;
		$vars['lang'] = self::$lang;
		$vars['branch_name'] = $branch->name;
		$vars['to_branch_name'] = !empty($toBranch) ? $toBranch->name : '';

		$this->observer->notify('escher:render:before:design:branch:edit', $branch);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function branches_push($params)
	{
		if (!$branchID = @$params['pv']['branch_id'])
		{
			if (!$branchID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
			}
		}

		if ($branchID <= 1)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}

		$model = $this->newModel('Branch');

		if (!$branch = $model->fetchBranch($branchID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		if (!$toBranch = $model->fetchBranch($branchID-1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_manage'] = $curUser->allowed('design:branches');
		$vars['can_push'] = $curUser->allowed('design:branches:push');

		if (isset($params['pv']['push']))
		{
			if (!$vars['can_push'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->pushBranchByID($branchID);
				$this->observer->notify('escher:site_change:design:branch:push', $branch);
				$this->session->flashSet('notice', 'Branch pushed successfully.');
				$this->redirect('/design/branches');
			}
		}

		$vars['action'] = 'push';
		$vars['selected_subtab'] = 'branches';
		$vars['branch_id'] = $branchID;
		$vars['branch_name'] = $branch->name;
		$vars['to_branch_name'] = $toBranch->name;

		$this->observer->notify('escher:render:before:design:branch:push', $branch);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function branches_rollback($params)
	{
		if (!$branchID = @$params['pv']['branch_id'])
		{
			if (!$branchID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
			}
		}

		if ($branchID <= 1)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}

		$model = $this->newModel('Branch');

		if (!$branch = $model->fetchBranch($branchID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		if (!$toBranch = $model->fetchBranch($branchID-1))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'branch not found'));
		}
		
		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_manage'] = $curUser->allowed('design:branches');
		$vars['can_rollback'] = $curUser->allowed('design:branches:rollback');

		if (isset($params['pv']['rollback']))
		{
			if (!$vars['can_rollback'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$model->rollbackBranchByID($branchID);
				$this->observer->notify('escher:site_change:design:branch:rollback', $branch);
				$this->session->flashSet('notice', 'Branch rolled back successfully.');
				$this->redirect('/design/branches');
			}
		}

		$vars['action'] = 'rollback';
		$vars['selected_subtab'] = 'branches';
		$vars['branch_id'] = $branchID;
		$vars['branch_name'] = $branch->name;
		$vars['to_branch_name'] = $toBranch->name;

		$this->observer->notify('escher:render:before:design:branch:rollback', $branch);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function themes_add($params)
	{
		if (!$parentID = @$params['pv']['parent_id'])
		{
			if (!$parentID = @$params[0])
			{
				$parentID = 0;
			}
		}

		$model = $this->newAdminContentModel();

		$theme = $this->factory->manufacture('Theme', array('parent_id'=>$parentID));

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'themes', 'add');

		if (isset($params['pv']['save']))
		{
			// build theme object from form data
			
			$this->buildTheme($params['pv'], $theme);

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->themeExists($theme->slug))
			{
				$errors['theme_slug'] = 'A theme with this slug already exists.';
			}
			elseif ($this->validateTheme($params['pv'], $errors))
			{
				// add theme object
	
				if ($parentID)
				{
					if (!$parentTheme = $model->fetchTheme(intval($parentID)))
					{
						throw new SparkHTTPException_NotFound(NULL, array('reason'=>'theme not found'));
					}
					$theme->parent_id = $parentID;
					$theme->lineage = $parentTheme->lineage . ',' . $parentTheme->id;
				}
				else
				{
					$theme->lineage = '0';
				}
				$theme->makeSlug();
				try
				{
					$this->updateObjectCreated($theme);
					$model->addTheme($theme);
					$this->observer->notify('escher:site_change:design:theme:add', $theme);
					$this->session->flashSet('notice', 'Theme added successfully.');
					$this->redirect('/design/themes');
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		
		$vars['selected_subtab'] = 'themes';
		$vars['action'] = 'add';
		$vars['parent_id'] = $parentID;
		$vars['theme'] = $theme;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:design:theme:add', $theme);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function themes_edit($params)
	{
		if (!$themeID = @$params['pv']['theme_id'])
		{
			if (!$themeID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'theme not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$theme = $model->fetchTheme(intval($themeID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'theme not found'));
		}

		$model->fetchThemeAuthor($theme);
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'themes', 'edit', $theme);
		
		if (isset($params['pv']['save']))
		{
			// build theme object from form data
			
			$oldSlug = $theme->slug;
			$this->buildTheme($params['pv'], $theme);
			
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (($theme->slug !== $oldSlug) && $model->themeExists($theme->slug))
			{
				$errors['theme_slug'] = 'A theme with this slug already exists.';
			}
			elseif ($this->validateTheme($params['pv'], $errors))
			{
				$theme->makeSlug();
				try
				{
					$this->updateObjectEdited($theme);
					$model->updateTheme($theme);
					$this->observer->notify('escher:site_change:design:theme:edit', $theme);
					$vars['notice'] = 'Theme saved successfully.';
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

		$vars['selected_subtab'] = 'themes';
		$vars['action'] = 'edit';
		$vars['theme'] = $theme;
		$vars['theme_id'] = $themeID;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:theme:edit', $theme);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function themes_delete($params)
	{
		if (!$themeID = @$params['pv']['theme_id'])
		{
			if (!$themeID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'theme not found'));
			}
		}

		$model = $this->newAdminContentModel();

		if (!$theme = $model->fetchTheme(intval($themeID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'theme not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'themes', 'delete', $theme);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$count = $model->deleteThemeByID($themeID);
				$this->observer->notify('escher:site_change:design:theme:delete', $theme);
				$this->session->flashSet('notice', $count . ($count === 1 ? ' theme' : ' themes') . ' deleted successfully.');
				$this->redirect('/design/themes');
			}
		}
				
		$model->fetchThemeDescendents($theme);

		$vars['selected_subtab'] = 'themes';
		$vars['action'] = 'delete';
		$vars['theme_id'] = $themeID;
		$vars['theme_title'] = $theme->title;
		$vars['theme'] = $theme;

		$this->observer->notify('escher:render:before:design:theme:delete', $theme);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function themes_list($params)
	{
		$model = $this->newAdminContentModel();
		
		$themes = $model->fetchAllThemes(true);
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'themes');

		$vars['selected_subtab'] = 'themes';
		$vars['action'] = 'list';
		$vars['themes'] = $themes;
		$vars['notice'] = $this->session->flashGet('notice');
		$vars['tree_state'] = isset($params['cv']['escherthemelist']) ? json_decode($params['cv']['escherthemelist'], true) : NULL;

		$this->observer->notify('escher:render:before:design:theme:list', $themes);
		$this->render('main', $vars);
	}
	
	//---------------------------------------------------------------------------

	protected function templates_add($params)
	{
		if (!$themeID = $this->getSelectedTheme($params))
		{
			if (!$themeID = @$params[0])
			{
				$themeID = 0;
			}
		}

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();
		
		$template = $this->factory->manufacture('Template', array('theme_id'=>$themeID, 'branch'=>$branch));

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'templates', 'add');

		if (isset($params['pv']['save']))
		{
			// build template object from form data
			
			$this->buildTemplate($params['pv'], $template);

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->templateExists($template->name, $template->theme_id, $branch, $info))
			{
				$errors['template_name'] = 'A template with this name already exists.';
			}
			elseif ($this->validateTemplate($params['pv'], $errors))
			{
				try
				{
					$this->updateObjectCreated($template);
					$template->created = NULL;
					
					if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
					{
						$template->id = $info['id'];
						$model->undeleteTemplate($template);
					}
					else
					{
						$model->addTemplate($template);
					}
					$this->observer->notify('escher:site_change:design:template:add', $template);
					$this->session->flashSet('notice', 'Template added successfully.');
					$this->redirect('/design/templates/edit/'.$template->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}

		$vars['selected_subtab'] = 'templates';
		$vars['action'] = 'add';
		$vars['template'] = $template;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:design:template:add', $template);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function templates_edit($params)
	{
		$template = NULL;
		
		$themeID = $this->getSelectedTheme($params);

		if (!$templateID = @$params['pv']['selected_template_id'])
		{
			$templateID = @$params[0];
		}

		$targetTemplateID = $templateID;

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();
		
		// if a theme was specified, we only show templates for that theme
		
		if (isset($themeID))
		{
			$templateNames = $model->fetchTemplateNames($themeID, $branch);
			if (!isset($templateNames[$templateID]))
			{
				$templateID = 0;
			}
		}
		else
		{
			if ($templateID)
			{
				$template = $model->fetchTemplate(intval($templateID));
			}
			$templateNames = $model->fetchTemplateNames($themeID = $template ? $template->theme_id : 0, $branch);
		}
		
		if (!$template)
		{
			if (!$templateID && $first = each($templateNames))
			{
				$templateID = $first[0];
			}
			$template = $model->fetchTemplate(intval($templateID));
		}

		if ($template)
		{
			$model->fetchTemplateEditor($template);
		}
		else
		{
			$templateID = 0;
			$template = NULL;
		}

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'templates', 'edit', $template);
		
		if (isset($params['pv']['save']))
		{
			if (!$template)
			{
				$errors[] = $vars['warning'] = 'The template you attempted to edit no longer exists.';
			}
			elseif ($targetTemplateID != $templateID)
			{
				$errors[] = $vars['warning'] = 'Your changes were not saved because you attempted to edit a stale or deleted template.';
			}
			else
			{
				$params['pv']['template_name'] = $template->name;
	
				// build template object from form data
				
				$oldName = $template->name;
				$this->buildTemplate($params['pv'], $template);
	
				if (!$vars['can_save'])
				{
					$vars['warning'] = 'Permission denied.';
				}
				elseif (($template->name !== $oldName) && $model->templateExists($template->name, $template->theme_id, $branch, $info))
				{
					$errors['template_name'] = 'A template with this name already exists.';
				}
				elseif ($this->validateTemplate($params['pv'], $errors))
				{
					try
					{
						$this->updateObjectEdited($template);
						if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
						{
							$template->id = $info['id'];
							$model->undeleteTemplate($template);
						}
						else
						{
							// check the branch, as we may need to create it if it does not exist
							
							if ($template->branch != $branch)
							{
								$template->id = $model->copyTemplateToBranch($template->name, $themeID, $branch);
								$template->branch = $branch;
							}
		
							$model->updateTemplate($template);
						}
						$this->observer->notify('escher:site_change:design:template:edit', $template);
						$vars['notice'] = 'Template saved successfully.';
					}
					catch (SparkDBException $e)
					{
						$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
					}
				}
			}
		}
		else
		{
			$vars['notice'] = $this->session->flashGet('notice');
		}

		// template ID changed on us (due to branch change), update data for view

		if ($template && ($template->id != $templateID))
		{
			$templateID = $template->id;
			$templateNames = $model->fetchTemplateNames($template->theme_id, $branch);
		}

		$vars['selected_subtab'] = 'templates';
		$vars['action'] = 'edit';
		$vars['templates'] = $templateNames;
		$vars['template'] = $template;
		$vars['selected_template_id'] = $templateID;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:template:edit', $template);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function templates_delete($params)
	{
		if (!$templateID = @$params['pv']['template_id'])
		{
			if (!$templateID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'template not found'));
			}
		}

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();

		if (!$template = $model->fetchTemplate(intval($templateID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'template not found'));
		}

		$themeID = $template->theme_id;

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'templates', 'delete', $template);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				// check the branch, as we may need to create it if it does not exist
				
				if ($template->branch != $branch)
				{
					$template->id = $model->copyTemplateToBranch($template->name, $themeID, $branch, true);
					$template->branch = $branch;
				}
				else
				{
					$model->markTemplateDeletedByID($template->id);
				}
				
				$this->observer->notify('escher:site_change:design:template:delete', $template);
				$this->session->flashSet('notice', 'Template deleted successfully.');
				$this->redirect('/design/templates');
			}
		}
		
		$vars['selected_subtab'] = 'templates';
		$vars['action'] = 'delete';
		$vars['template_id'] = $templateID;
		$vars['template_name'] = $template->name;

		$this->observer->notify('escher:render:before:design:template:delete', $template);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function snippets_add($params)
	{
		if (!$themeID = $this->getSelectedTheme($params))
		{
			if (!$themeID = @$params[0])
			{
				$themeID = 0;
			}
		}
		
		$branch = $this->getWorkingBranch();
		
		$model = $this->newAdminContentModel();
		
		$snippet = $this->factory->manufacture('Snippet', array('theme_id'=>$themeID, 'branch'=>$branch));

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'snippets', 'add');

		if (isset($params['pv']['save']))
		{
			// build snippet object from form data
			
			$this->buildSnippet($params['pv'], $snippet);

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->snippetExists($snippet->name, $snippet->theme_id, $branch, $info))
			{
				$errors['snippet_name'] = 'A snippet with this name already exists.';
			}
			elseif ($this->validateSnippet($params['pv'], $errors))
			{
				try
				{
					$this->updateObjectCreated($snippet);
					$snippet->created = NULL;
					
					if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
					{
						$snippet->id = $info['id'];
						$model->undeleteSnippet($snippet);
					}
					else
					{
						$model->addSnippet($snippet);
					}
					$this->observer->notify('escher:site_change:design:snippet:add', $snippet);
					$this->session->flashSet('notice', 'Snippet added successfully.');
					$this->redirect('/design/snippets/edit/'.$snippet->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		
		$vars['selected_subtab'] = 'snippets';
		$vars['action'] = 'add';
		$vars['snippet'] = $snippet;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:snippet:add', $snippet);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function snippets_edit($params)
	{
		$snippet = NULL;
		
		$themeID = $this->getSelectedTheme($params);

		if (!$snippetID = @$params['pv']['selected_snippet_id'])
		{
			$snippetID = @$params[0];
		}

		$targetSnippetID = $snippetID;

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();
		
		// if a theme was specified, we only show snippets for that theme
		
		if (isset($themeID))
		{
			$snippetNames = $model->fetchSnippetNames($themeID, $branch);
			if (!isset($snippetNames[$snippetID]))
			{
				$snippetID = 0;
			}
		}
		else
		{
			if ($snippetID)
			{
				$snippet = $model->fetchSnippet(intval($snippetID));
			}
			$snippetNames = $model->fetchSnippetNames($themeID = $snippet ? $snippet->theme_id : 0, $branch);
		}
		
		if (!$snippet)
		{
			if (!$snippetID && $first = each($snippetNames))
			{
				$snippetID = $first[0];
			}
			$snippet = $model->fetchSnippet(intval($snippetID));
		}

		if ($snippet)
		{
			$model->fetchSnippetEditor($snippet);
		}
		else
		{
			$snippetID = 0;
			$snippet = NULL;
		}

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'snippets', 'edit', $snippet);
		
		if (isset($params['pv']['save']))
		{
			if (!$snippet)
			{
				$errors[] = $vars['warning'] = 'The snippet you attempted to edit no longer exists.';
			}
			elseif ($targetSnippetID != $snippetID)
			{
				$errors[] = $vars['warning'] = 'Your changes were not saved because you attempted to edit a stale or deleted snippet.';
			}
			else
			{
				$params['pv']['snippet_name'] = $snippet->name;
	
				// build snippet object from form data
				
				$oldName = $snippet->name;

				$this->buildSnippet($params['pv'], $snippet);
	
				if (!$vars['can_save'])
				{
					$vars['warning'] = 'Permission denied.';
				}
				elseif (($snippet->name !== $oldName) && $model->snippetExists($snippet->name, $snippet->theme_id, $branch, $info))
				{
					$errors['snippet_name'] = 'A snippet with this name already exists.';
				}
				elseif ($this->validateSnippet($params['pv'], $errors))
				{
					try
					{
						$this->updateObjectEdited($snippet);
						
						if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
						{
							$snippet->id = $info['id'];
							$model->undeleteSnippet($snippet);
						}
						else
						{
							// check the branch, as we may need to create it if it does not exist
							
							if ($snippet->branch != $branch)
							{
								$snippet->id = $model->copySnippetToBranch($snippet->name, $themeID, $branch);
								$snippet->branch = $branch;
							}
		
							$model->updateSnippetContent($snippet);
						}
						$this->observer->notify('escher:site_change:design:snippet:edit', $snippet);
						$vars['notice'] = 'Snippet saved successfully.';
					}
					catch (SparkDBException $e)
					{
						$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
					}
				}
			}
		}
		else
		{
			$vars['notice'] = $this->session->flashGet('notice');
		}

		// snippet ID changed on us (due to branch change), update data for view

		if ($snippet && ($snippet->id != $snippetID))
		{
			$snippetID = $snippet->id;
			$snippetNames = $model->fetchSnippetNames($snippet->theme_id, $branch);
		}

		$vars['selected_subtab'] = 'snippets';
		$vars['action'] = 'edit';
		$vars['snippets'] = $snippetNames;
		$vars['snippet'] = $snippet;
		$vars['selected_snippet_id'] = $snippetID;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:snippet:edit', $snippet);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function snippets_delete($params)
	{
		if (!$snippetID = @$params['pv']['snippet_id'])
		{
			if (!$snippetID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'snippet not found'));
			}
		}

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();

		if (!$snippet = $model->fetchSnippet(intval($snippetID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'snippet not found'));
		}

		$themeID = $snippet->theme_id;
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'snippets', 'delete', $snippet);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				// check the branch, as we may need to create it if it does not exist
				
				if ($snippet->branch != $branch)
				{
					$snippet->id = $model->copySnippetToBranch($snippet->name, $themeID, $branch, true);
					$snippet->branch = $branch;
				}
				else
				{
					$model->markSnippetDeletedByID($snippet->id);
				}
				
				$this->observer->notify('escher:site_change:design:snippet:delete', $snippet);
				$this->session->flashSet('notice', 'Snippet deleted successfully.');
				$this->redirect('/design/snippets');
			}
		}
		
		$vars['selected_subtab'] = 'snippets';
		$vars['action'] = 'delete';
		$vars['snippet_id'] = $snippetID;
		$vars['snippet_name'] = $snippet->name;

		$this->observer->notify('escher:render:before:design:snippet:delete', $snippet);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function tags_add($params)
	{
		if (!$themeID = $this->getSelectedTheme($params))
		{
			if (!$themeID = @$params[0])
			{
				$themeID = 0;
			}
		}
		
		$branch = $this->getWorkingBranch();
		
		$model = $this->newAdminContentModel();
		
		$tag = $this->factory->manufacture('Tag', array('theme_id'=>$themeID, 'branch'=>$branch));

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'tags', 'add');

		if (isset($params['pv']['save']))
		{
			// build tag object from form data
			
			$this->buildTag($params['pv'], $tag);

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->tagExists($tag->name, $tag->theme_id, $branch, $info))
			{
				$errors['tag_name'] = 'A tag with this name already exists.';
			}
			elseif ($this->validateTag($params['pv'], $errors))
			{
				try
				{
					$this->updateObjectCreated($tag);
					$tag->created = NULL;

					if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
					{
						$tag->id = $info['id'];
						$model->undeleteTag($tag);
					}
					else
					{
						$model->addTag($tag);
					}
					$this->observer->notify('escher:site_change:design:tag:add', $tag);
					
					// flush code cache for this branch and all brances above it
					
					for ($flushBranch = $branch; $flushBranch <= EscherProductionStatus::Development; ++$flushBranch)
					{
						$this->observer->notify('escher:cache:request_flush:plug', $flushBranch);
					}
					
					$this->session->flashSet('notice', 'Tag added successfully.');
					$this->redirect('/design/tags/edit/'.$tag->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
				
		$vars['selected_subtab'] = 'tags';
		$vars['action'] = 'add';
		$vars['tag'] = $tag;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:tag:add', $tag);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function tags_edit($params)
	{
		$tag = NULL;
		
		$themeID = $this->getSelectedTheme($params);

		if (!$tagID = @$params['pv']['selected_tag_id'])
		{
			$tagID = @$params[0];
		}

		$targetTagID = $tagID;

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();
		
		// if a theme was specified, we only show tags for that theme
		
		if (isset($themeID))
		{
			$tagNames = $model->fetchTagNames($themeID, $branch);
			if (!isset($tagNames[$tagID]))
			{
				$tagID = 0;
			}
		}
		else
		{
			if ($tagID)
			{
				$tag = $model->fetchTag(intval($tagID));
			}
			$tagNames = $model->fetchTagNames($themeID = $tag ? $tag->theme_id : 0, $branch);
		}
		
		if (!$tag)
		{
			if (!$tagID && $first = each($tagNames))
			{
				$tagID = $first[0];
			}
			$tag = $model->fetchTag(intval($tagID));
		}

		if ($tag)
		{
			$model->fetchTagEditor($tag);
		}
		else
		{
			$tagID = 0;
			$tag = NULL;
		}

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'tags', 'edit', $tag);
		
		if (isset($params['pv']['save']))
		{
			if (!$tag)
			{
				$errors[] = $vars['warning'] = 'The tag you attempted to edit no longer exists.';
			}
			elseif ($targetTagID != $tagID)
			{
				$errors[] = $vars['warning'] = 'Your changes were not saved because you attempted to edit a stale or deleted tag.';
			}
			else
			{
				$params['pv']['tag_name'] = $tag->name;
	
				// build tag object from form data
				
				$oldName = $tag->name;
				
				$this->buildTag($params['pv'], $tag);
	
				if (!$vars['can_save'])
				{
					$vars['warning'] = 'Permission denied.';
				}
				elseif (($tag->name !== $oldName) && $model->tagExists($tag->name, $tag->theme_id, $branch, $info))
				{
					$errors['tag_name'] = 'A tag with this name already exists.';
				}
				elseif ($this->validateTag($params['pv'], $errors))
				{
					try
					{
						$this->updateObjectEdited($tag);
						
						if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
						{
							$tag->id = $info['id'];
							$model->undeleteTag($tag);
						}
						else
						{
							// check the branch, as we may need to create it if it does not exist
							
							if ($tag->branch != $branch)
							{
								$tag->id = $model->copyTagToBranch($tag->name, $themeID, $branch);
								$tag->branch = $branch;
							}
		
							$model->updateTagContent($tag);
						}
						$this->observer->notify('escher:site_change:design:tag:edit', $tag);
						
						// flush code cache for this branch and all brances above it
						
						for ($flushBranch = $branch; $flushBranch <= EscherProductionStatus::Development; ++$flushBranch)
						{
							$this->observer->notify('escher:cache:request_flush:plug', $flushBranch);
						}
						
						$vars['notice'] = 'Tag saved successfully.';
					}
					catch (SparkDBException $e)
					{
						$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
					}
				}
			}
		}
		else
		{
			$vars['notice'] = $this->session->flashGet('notice');
		}

		// tag ID changed on us (due to branch change), update data for view

		if ($tag && ($tag->id != $tagID))
		{
			$tagID = $tag->id;
			$tagNames = $model->fetchTagNames($tag->theme_id, $branch);
		}

		$vars['selected_subtab'] = 'tags';
		$vars['action'] = 'edit';
		$vars['tags'] = $tagNames;
		$vars['tag'] = $tag;
		$vars['selected_tag_id'] = $tagID;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:tag:edit', $tag);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function tags_delete($params)
	{
		if (!$tagID = @$params['pv']['tag_id'])
		{
			if (!$tagID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'tag not found'));
			}
		}

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();

		if (!$tag = $model->fetchTag(intval($tagID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'tag not found'));
		}

		$themeID = $tag->theme_id;
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'tags', 'delete', $tag);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				// check the branch, as we may need to create it if it does not exist
				
				if ($tag->branch != $branch)
				{
					$tag->id = $model->copyTagToBranch($tag->name, $themeID, $branch, true);
					$tag->branch = $branch;
				}
				else
				{
					$model->markTagDeletedByID($tag->id);
				}
				
				$this->observer->notify('escher:site_change:design:tag:delete', $tag);

				// flush code cache for this branch and all brances above it
				
				for ($flushBranch = $branch; $flushBranch <= EscherProductionStatus::Development; ++$flushBranch)
				{
					$this->observer->notify('escher:cache:request_flush:plug', $flushBranch);
				}
				
				$this->session->flashSet('notice', 'Tag deleted successfully.');
				$this->redirect('/design/tags');
			}
		}
		
		$vars['selected_subtab'] = 'tags';
		$vars['action'] = 'delete';
		$vars['tag_id'] = $tagID;
		$vars['tag_name'] = $tag->name;

		$this->observer->notify('escher:render:before:design:tag:delete', $tag);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function styles_add($params)
	{
		if (!$themeID = $this->getSelectedTheme($params))
		{
			if (!$themeID = @$params[0])
			{
				$themeID = 0;
			}
		}
		
		$branch = $this->getWorkingBranch();
		
		$model = $this->newAdminContentModel();
		
		$style = $this->factory->manufacture('Style', array('theme_id'=>$themeID, 'branch'=>$branch));

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'styles', 'add');

		if (isset($params['pv']['save']))
		{
			// build style object from form data
			
			$this->buildStyle($params['pv'], $style);

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->styleExists($style->slug, $style->theme_id, $branch, $info))
			{
				$errors['style_slug'] = 'A style with this name already exists.';
			}
			elseif ($this->validateStyle($params['pv'], $errors))
			{
				$style->makeSlug();
				try
				{
					$this->updateObjectCreated($style);
					$style->created = NULL;
					
					if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
					{
						$style->id = $info['id'];
						$model->undeleteStyle($style);
					}
					else
					{
						$model->addStyle($style);
					}
					$this->observer->notify('escher:site_change:design:style:add', $style);
					$this->session->flashSet('notice', 'Style added successfully.');
					$this->redirect('/design/styles/edit/'.$style->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		
		$vars['selected_subtab'] = 'styles';
		$vars['action'] = 'add';
		$vars['style'] = $style;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:style:add', $style);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function styles_edit($params)
	{
		$style = NULL;
		
		$themeID = $this->getSelectedTheme($params);

		if (!$styleID = @$params['pv']['selected_style_id'])
		{
			$styleID = @$params[0];
		}

		$targetStyleID = $styleID;

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();
		
		// if a theme was specified, we only show styles for that theme
		
		if (isset($themeID))
		{
			$styleNames = $model->fetchStyleNames($themeID, $branch);
			if (!isset($styleNames[$styleID]))
			{
				$styleID = 0;
			}
		}
		else
		{
			if ($styleID)
			{
				$style = $model->fetchStyle(intval($styleID));
			}
			$styleNames = $model->fetchStyleNames($themeID = $style ? $style->theme_id : 0, $branch);
		}
		
		if (!$style)
		{
			if (!$styleID && $first = each($styleNames))
			{
				$styleID = $first[0];
			}
			$style = $model->fetchStyle(intval($styleID));
		}

		if ($style)
		{
			$model->fetchStyleEditor($style);
		}
		else
		{
			$styleID = 0;
			$style = NULL;
		}

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'styles', 'edit', $style);
		
		if (isset($params['pv']['save']))
		{
			if (!$style)
			{
				$errors[] = $vars['warning'] = 'The style you attempted to edit no longer exists.';
			}
			elseif ($targetStyleID != $styleID)
			{
				$errors[] = $vars['warning'] = 'Your changes were not saved because you attempted to edit a stale or deleted style.';
			}
			else
			{
				$params['pv']['style_name'] = $style->slug;
	
				// build style object from form data
				
				$oldSlug = $style->slug;
				
				$this->buildStyle($params['pv'], $style);
	
				if (!$vars['can_save'])
				{
					$vars['warning'] = 'Permission denied.';
				}
				elseif (($style->slug !== $oldSlug) && $model->styleExists($style->slug, $style->theme_id, $branch, $info))
				{
					$errors['style_slug'] = 'A style with this name already exists.';
				}
				elseif ($this->validateStyle($params['pv'], $errors))
				{
					try
					{
						$this->updateObjectEdited($style);
						
						if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
						{
							$style->id = $info['id'];
							$model->undeleteStyle($style);
						}
						else
						{
							// check the branch, as we may need to create it if it does not exist
							
							if ($style->branch != $branch)
							{
								$style->id = $model->copyStyleToBranch($style->slug, $themeID, $branch);
								$style->branch = $branch;
							}
		
							$model->updateStyle($style);
						}
						$this->observer->notify('escher:site_change:design:style:edit', $style);
						$vars['notice'] = 'Style saved successfully.';
					}
					catch (SparkDBException $e)
					{
						$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
					}
				}
			}
		}
		else
		{
			$vars['notice'] = $this->session->flashGet('notice');
		}

		// style ID changed on us (due to branch change), update data for view

		if ($style && ($style->id != $styleID))
		{
			$styleID = $style->id;
			$styleNames = $model->fetchStyleNames($style->theme_id, $branch);
		}

		$vars['selected_subtab'] = 'styles';
		$vars['action'] = 'edit';
		$vars['styles'] = $styleNames;
		$vars['style'] = $style;
		$vars['selected_style_id'] = $styleID;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:style:edit', $style);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function styles_delete($params)
	{
		if (!$styleID = @$params['pv']['style_id'])
		{
			if (!$styleID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'style not found'));
			}
		}

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();

		if (!$style = $model->fetchStyle(intval($styleID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'style not found'));
		}

		$themeID = $style->theme_id;
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'styles', 'delete', $style);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				// check the branch, as we may need to create it if it does not exist
				
				if ($style->branch != $branch)
				{
					$style->id = $model->copyStyleToBranch($style->slug, $themeID, $branch, true);
					$style->branch = $branch;
				}
				else
				{
					$model->markStyleDeletedByID($style->id);
				}
				
				$this->observer->notify('escher:site_change:design:style:delete', $style);
				$this->session->flashSet('notice', 'Style deleted successfully.');
				$this->redirect('/design/styles');
			}
		}
		
		$vars['selected_subtab'] = 'styles';
		$vars['action'] = 'delete';
		$vars['style_id'] = $styleID;
		$vars['style_name'] = $style->slug;

		$this->observer->notify('escher:render:before:design:style:delete', $style);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function scripts_add($params)
	{
		if (!$themeID = $this->getSelectedTheme($params))
		{
			if (!$themeID = @$params[0])
			{
				$themeID = 0;
			}
		}
		
		$branch = $this->getWorkingBranch();
		
		$model = $this->newAdminContentModel();
		
		$script = $this->factory->manufacture('Script', array('theme_id'=>$themeID, 'branch'=>$branch));

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'scripts', 'add');

		if (isset($params['pv']['save']))
		{
			// build script object from form data
			
			$this->buildScript($params['pv'], $script);

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($model->scriptExists($script->slug, $script->theme_id, $branch, $info))
			{
				$errors['script_slug'] = 'A script with this name already exists.';
			}
			elseif ($this->validateScript($params['pv'], $errors))
			{
				$script->makeSlug();
				try
				{
					$this->updateObjectCreated($script);
					$script->created = NULL;
					
					if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
					{
						$script->id = $info['id'];
						$model->undeleteScript($script);
					}
					else
					{
						$model->addScript($script);
					}
					$this->observer->notify('escher:site_change:design:script:add', $script);
					$this->session->flashSet('notice', 'Script added successfully.');
					$this->redirect('/design/scripts/edit/'.$script->id);
				}
				catch (SparkDBException $e)
				{
					$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
				}
			}
		}
		
		$vars['selected_subtab'] = 'scripts';
		$vars['action'] = 'add';
		$vars['script'] = $script;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
		
		$this->observer->notify('escher:render:before:design:script:add', $script);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function scripts_edit($params)
	{
		$script = NULL;
		
		$themeID = $this->getSelectedTheme($params);

		if (!$scriptID = @$params['pv']['selected_script_id'])
		{
			$scriptID = @$params[0];
		}

		$targetScriptID = $scriptID;

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();
		
		// if a theme was specified, we only show scripts for that theme
		
		if (isset($themeID))
		{
			$scriptNames = $model->fetchScriptNames($themeID, $branch);
			if (!isset($scriptNames[$scriptID]))
			{
				$scriptID = 0;
			}
		}
		else
		{
			if ($scriptID)
			{
				$script = $model->fetchScript(intval($scriptID));
			}
			$scriptNames = $model->fetchScriptNames($themeID = $script ? $script->theme_id : 0, $branch);
		}
		
		if (!$script)
		{
			if (!$scriptID && $first = each($scriptNames))
			{
				$scriptID = $first[0];
			}
			$script = $model->fetchScript(intval($scriptID));
		}

		if ($script)
		{
			$model->fetchScriptEditor($script);
		}
		else
		{
			$scriptID = 0;
			$script = NULL;
		}

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'scripts', 'edit', $script);
		
		if (isset($params['pv']['save']))
		{
			if (!$script)
			{
				$errors[] = $vars['warning'] = 'The script you attempted to edit no longer exists.';
			}
			elseif ($targetScriptID != $scriptID)
			{
				$errors[] = $vars['warning'] = 'Your changes were not saved because you attempted to edit a stale or deleted script.';
			}
			else
			{
				$params['pv']['script_name'] = $script->slug;
	
				// build script object from form data
				
				$oldSlug = $script->slug;
				
				$this->buildScript($params['pv'], $script);
	
				if (!$vars['can_save'])
				{
					$vars['warning'] = 'Permission denied.';
				}
				elseif (($script->slug !== $oldSlug) && $model->scriptExists($script->slug, $script->theme_id, $branch, $info))
				{
					$errors['script_slug'] = 'A script with this name already exists.';
				}
				elseif ($this->validateScript($params['pv'], $errors))
				{
					try
					{
						$this->updateObjectEdited($script);
						
						if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
						{
							$script->id = $info['id'];
							$model->undeleteScript($script);
						}
						else
						{
							// check the branch, as we may need to create it if it does not exist
							
							if ($script->branch != $branch)
							{
								$script->id = $model->copyScriptToBranch($script->slug, $themeID, $branch);
								$script->branch = $branch;
							}
		
							$model->updateScript($script);
						}
						$this->observer->notify('escher:site_change:design:script:edit', $script);
						$vars['notice'] = 'Script saved successfully.';
					}
					catch (SparkDBException $e)
					{
						$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
					}
				}
			}
		}
		else
		{
			$vars['notice'] = $this->session->flashGet('notice');
		}

		// script ID changed on us (due to branch change), update data for view

		if ($script && ($script->id != $scriptID))
		{
			$scriptID = $script->id;
			$scriptNames = $model->fetchScriptNames($script->theme_id, $branch);
		}

		$vars['selected_subtab'] = 'scripts';
		$vars['action'] = 'edit';
		$vars['scripts'] = $scriptNames;
		$vars['script'] = $script;
		$vars['selected_script_id'] = $scriptID;
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:script:edit', $script);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function scripts_delete($params)
	{
		if (!$scriptID = @$params['pv']['script_id'])
		{
			if (!$scriptID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'script not found'));
			}
		}

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();

		if (!$script = $model->fetchScript(intval($scriptID)))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'script not found'));
		}

		$themeID = $script->theme_id;
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'scripts', 'delete', $script);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				// check the branch, as we may need to create it if it does not exist
				
				if ($script->branch != $branch)
				{
					$script->id = $model->copyScriptToBranch($script->slug, $themeID, $branch, true);
					$script->branch = $branch;
				}
				else
				{
					$model->markScriptDeletedByID($script->id);
				}
				
				$this->observer->notify('escher:site_change:design:script:delete', $script);
				$this->session->flashSet('notice', 'Script deleted successfully.');
				$this->redirect('/design/scripts');
			}
		}
		
		$vars['selected_subtab'] = 'scripts';
		$vars['action'] = 'delete';
		$vars['script_id'] = $scriptID;
		$vars['script_name'] = $script->slug;

		$this->observer->notify('escher:render:before:design:script:delete', $script);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function images_add($params)
	{
		if (!$themeID = $this->getSelectedTheme($params))
		{
			if (!$themeID = @$params[0])
			{
				$themeID = 0;
			}
		}
		
		$branch = $this->getWorkingBranch();
		
		$model = $this->newAdminContentModel();
		
		$image = $this->factory->manufacture('Image', array('theme_id'=>$themeID, 'branch'=>$branch));

		$curUser = $this->app->get_user();
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'images', 'add');
		$vars['can_upload'] = $curUser->allowed('design:images:add:upload');

		if (isset($params['pv']['save']))
		{
			require($this->config->get('core_dir') . '/admin/lib/image_helper.php');
			$imageHelper = $this->factory->manufacture('ImageHelper');

			// build image object from form data
			
			$imageHelper->buildImage($params['pv'], $image);
			$image->theme_id = $themeID;

			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($imageHelper->validateImage($params['pv'], $vars['can_upload'], $errors))
			{
				if ($vars['can_upload'])
				{
					$imageHelper->loadImage($image);
				}
				if ($model->imageExists($image->slug, $image->theme_id, $branch, $info))
				{
					$errors['image_slug'] = 'An image with this name already exists.';
				}
				else
				{
					try
					{
						$image->makeSlug();
						$this->updateObjectCreated($image);
						$image->created = NULL;
	
						if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
						{
							$image->id = $info['id'];
							$model->undeleteImage($image);
						}
						else
						{
							$model->addImage($image);
						}
						$this->observer->notify('escher:site_change:design:image:add', $image);
						if ($vars['can_edit_meta'] || $vars['can_add_meta'] || $vars['can_delete_meta'])
						{
							$model->saveImageMeta($image, $vars);
						}
						$this->session->flashSet('notice', 'Image added successfully.');
						$this->redirect('/design/images/edit/'.$image->id);
					}
					catch (SparkDBException $e)
					{
						$errors[] = $vars['warning'] = $this->getDBErrorMsg($e);
					}
				}
			}
		}
		else
		{
			$image->meta = array();
		}
		
		$vars['selected_subtab'] = 'images';
		$vars['action'] = 'add';
		$vars['image'] = $image;
		$vars['max_upload_size'] = $this->app->get_pref('max_upload_size');
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;
		
		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:image:add', $image);
		$this->render('main', $vars);
	}

	//---------------------------------------------------------------------------

	protected function images_edit($params)
	{
		$image = NULL;
		
		$themeID = $this->getSelectedTheme($params);

		if (!$imageID = @$params['pv']['selected_image_id'])
		{
			$imageID = @$params[0];
		}

		$targetImageID = $imageID;

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();
		
		// if a theme was specified, we only show images for that theme
		
		if (isset($themeID))
		{
			$imageNames = $model->fetchImageNames($themeID, $branch);
			if (!isset($imageNames[$imageID]))
			{
				$imageID = 0;
			}
		}
		else
		{
			if ($imageID)
			{
				$image = $model->fetchImage(intval($imageID), NULL, NULL, false);
			}
			$imageNames = $model->fetchImageNames($themeID = $image ? $image->theme_id : 0, $branch);
		}
		
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

		if ($image && ($image->theme_id == -1))	// this is a content image!
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
		}

		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'images', 'edit', $image);
		if ($vars['can_upload'] = $vars['can_edit'] && $image)
		{
			$curUser = $this->app->get_user();
			$perm = 'design:images:edit:' . ($curUser->id == $image->author_id ? 'own' : 'any') . ':replace';
			$vars['can_upload'] = $curUser->allowed($perm);
		}
		
		if (isset($params['pv']['save']))
		{
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif (!$image)
			{
				$errors[] = $vars['warning'] = 'The image you attempted to edit no longer exists.';
			}
			elseif ($targetImageID != $imageID)
			{
				$errors[] = $vars['warning'] = 'Your changes were not saved because you attempted to edit a stale or deleted image.';
			}
			else
			{
				require($this->config->get('core_dir') . '/admin/lib/image_helper.php');
				$imageHelper = $this->factory->manufacture('ImageHelper');
	
				$params['pv']['image_name'] = $image->slug;
	
				// build image object from form data
				
				$oldSlug = $image->slug;
				
				$imageHelper->buildImage($params['pv'], $image);
	
				if ($imageHelper->validateImage($params['pv'], $vars['can_upload'], $errors))
				{
					$image = clone $image;			// clone the image so we don't remove cached content
					$image->content = NULL;			// only update image data if we load a new image
					if ($vars['can_upload'])
					{
						$imageHelper->loadImage($image);
					}
					if (($image->slug !== $oldSlug) && $model->imageExists($image->slug, $image->theme_id, $branch, $info))
					{
						$errors['image_slug'] = 'An image with this name already exists.';
					}
					else
					{
						try
						{
							$this->updateObjectEdited($image);
	
							if (isset($info['id']) && ($info['branch_status'] == ContentObject::branch_status_deleted) && ($info['branch'] == $branch))
							{
								$image->id = $info['id'];
								$model->undeleteImage($image);
							}
							else
							{
								// check the branch, as we may need to create it if it does not exist
								
								if ($image->branch != $branch)
								{
									$image->theme_id = $themeID;
									$image->id = $model->copyImageToBranch($image, $branch);
									$image->branch = $branch;
								}
			
								$model->updateImage($image);
							}
							$this->observer->notify('escher:site_change:design:image:edit', $image);
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
					}
	
					// must refresh image metadata from database so new field names get generated for newly added metadata
	
					$image->meta = NULL;
					$model->fetchImageMeta($image);
				}
			}
		}
		else
		{
			if ($image)
			{
				$model->fetchImageMeta($image);
			}
			$vars['notice'] = $this->session->flashGet('notice');
		}
		
		if ($image)
		{
			// image ID changed on us (due to branch change), update data for view
	
			if ($image->id != $imageID)
			{
				$imageID = $image->id;
				$imageNames = $model->fetchImageNames($image->theme_id, $branch);
			}

			$image->display_url = $this->urlTo('/design/images/display/' . $image->id);
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
		$vars['themes'] = $model->fetchThemeNames();
		$vars['selected_theme_id'] = $themeID;
		$vars['branches'] = $model->fetchBranchNames();
		$vars['selected_branch'] = $branch;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}

		$this->observer->notify('escher:render:before:design:image:edit', $image);
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

		$branch = $this->getWorkingBranch();

		$model = $this->newAdminContentModel();

		if (!$image = $model->fetchImage(intval($imageID), NULL, NULL, false))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
		}
		
		if (($themeID = $image->theme_id) == -1)	// this is a content image!
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'image not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getDesignPerms($vars, 'images', 'delete', $image);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				// check the branch, as we may need to create it if it does not exist
				
				if ($image->branch != $branch)
				{
					$image->id = $model->copyImageToBranch($image, $branch, true);
					$image->branch = $branch;
				}
				else
				{
					$model->markImageDeletedByID($image->id);
				}
				
				$this->observer->notify('escher:site_change:design:image:delete', $image);
				$this->session->flashSet('notice', 'Image deleted successfully.');
				$this->redirect('/design/images');
			}
		}
		
		$vars['selected_subtab'] = 'images';
		$vars['action'] = 'delete';
		$vars['image_id'] = $imageID;
		$vars['image_name'] = $image->slug;

		$this->observer->notify('escher:render:before:design:image:delete', $image);
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
			if ($curUser->allowed('design:images:display:'.$suffix))
			{
				$this->display($image->content, $image->ctype);
			}
		}
	}
	
	//---------------------------------------------------------------------------

	protected function buildTheme($params, $theme)
	{
		// build theme object

		$theme->title = $params['theme_title'];
		$theme->slug = $params['theme_slug'];
		$theme->style_url = $params['theme_style_url'];
		$theme->script_url = $params['theme_script_url'];
		$theme->image_url = $params['theme_image_url'];
	}
	
	//---------------------------------------------------------------------------

	protected function validateTheme($params, &$errors)
	{
		$errors = array();
		
		if (empty($params['theme_title']))
		{
			$errors['theme_title'] = 'Theme title is required.';
		}

		if (!empty($params['theme_slug']))
		{
			if (!preg_match('/^[0-9A-Za-z\-\.]*$/', $params['theme_slug']))
			{
				$errors['theme_slug'] = 'Theme slug may contain only alphanumeric characters, hyphens and periods.';
			}
		}
		
		if (!empty($params['theme_style_url']))
		{
			if (!$this->validateURL($params['theme_style_url'], $error))
			{
				$errors['theme_style_url'] = $error;
			}
		}
		
		if (!empty($params['theme_script_url']))
		{
			if (!$this->validateURL($params['theme_script_url'], $error))
			{
				$errors['theme_script_url'] = $error;
			}
		}
		
		if (!empty($params['theme_image_url']))
		{
			if (!$this->validateURL($params['theme_image_url'], $error))
			{
				$errors['theme_image_url'] = $error;
			}
		}
		
		return empty($errors);
	}

	//---------------------------------------------------------------------------

	protected function buildTemplate($params, $template)
	{
		// build template object

		$template->name = $params['template_name'];
		if (($template->ctype = $params['template_content_type']) === '')
		{
			$template->ctype = 'text/html';
		}
		$template->content = $params['template_content'];
	}
	
	//---------------------------------------------------------------------------

	protected function validateTemplate($params, &$errors)
	{
		$errors = array();
		
		if (empty($params['template_name']))
		{
			$errors['template_name'] = 'Template name is required.';
		}

		if (empty($params['template_content']))
		{
			$errors['template_content'] = 'Template body is required.';
		}
		
		return empty($errors);
	}

	//---------------------------------------------------------------------------

	protected function buildSnippet($params, $snippet)
	{
		// build snippet object

		$snippet->name = $params['snippet_name'];
		$snippet->content = $params['snippet_content'];
		$snippet->content_html = $params['snippet_content'];
	}
	
	//---------------------------------------------------------------------------

	protected function validateSnippet($params, &$errors)
	{
		$errors = array();
		
		if (empty($params['snippet_name']))
		{
			$errors['snippet_name'] = 'Snippet name is required.';
		}

		if (empty($params['snippet_content']))
		{
			$errors['snippet_content'] = 'Snippet body is required.';
		}
		
		return empty($errors);
	}

	//---------------------------------------------------------------------------

	protected function buildTag($params, $tag)
	{
		// build tag object

		$tag->name = $params['tag_name'];
		$tag->content = $params['tag_content'];
	}
	
	//---------------------------------------------------------------------------

	protected function validateTag($params, &$errors)
	{
		$errors = array();
		
		if (empty($params['tag_name']))
		{
			$errors['tag_name'] = 'Tag name is required.';
		}
		else
		{
			if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $params['tag_name']))
			{
				$errors['tag_name'] = 'Tag name must be a valid PHP identifier.';
			}
		}

		if (empty($params['tag_content']))
		{
			$errors['tag_content'] = 'Tag body is required.';
		}
		
		return empty($errors);
	}

	//---------------------------------------------------------------------------

	protected function buildStyle($params, $style)
	{
		// build style object

		$style->slug = $params['style_name'];
		if (($style->ctype = $params['style_content_type']) === '')
		{
			$style->ctype = 'text/css';
		}
		$style->url = $params['style_url'];
		$style->content = $params['style_content'];
	}
	
	//---------------------------------------------------------------------------

	protected function validateStyle($params, &$errors)
	{
		$errors = array();
		
		if (empty($params['style_name']))
		{
			$errors['style_name'] = 'Style name is required.';
		}
		
		if (!empty($params['style_url']))
		{
			if (!$this->validateURL($params['style_url'], $error))
			{
				$errors['style_url'] = $error;
			}
		}
		
		return empty($errors);
	}

	//---------------------------------------------------------------------------

	protected function buildScript($params, $script)
	{
		// build script object

		$script->slug = $params['script_name'];
		if (($script->ctype = $params['script_content_type']) === '')
		{
			$script->ctype = 'application/javascript';
		}
		$script->url = $params['script_url'];
		$script->content = $params['script_content'];
	}
	
	//---------------------------------------------------------------------------

	protected function validateScript($params, &$errors)
	{
		$errors = array();
		
		if (empty($params['script_name']))
		{
			$errors['script_name'] = 'Script name is required.';
		}

		if (!empty($params['script_url']))
		{
			if (!$this->validateURL($params['script_url'], $error))
			{
				$errors['script_url'] = $error;
			}
		}
		
		return empty($errors);
	}

	//---------------------------------------------------------------------------

	protected function validateURL($url, &$error)
	{
		if (!SparkUtil::valid_url($url) && !SparkUtil::valid_url_path($url))
		{
			$error = 'Not a valid URL.';
			return false;
		}
		return true;
	}

	//---------------------------------------------------------------------------

	protected function getSelectedTheme($params)
	{
		if (($themeID = @$params['pv']['selected_theme_id']) !== NULL)
		{
			$this->session->set('selected_theme_id', intval($themeID));
		}
		else
		{
			$themeID = $this->session->get('selected_theme_id', 0);
		}
		return $themeID;
	}
	
	//---------------------------------------------------------------------------

	protected function getDesignPerms(&$vars, $type, $action = '', $object = NULL)
	{
		$curUser = $this->app->get_user();
		$prefix = "design:{$type}:";
		
		$vars['can_add'] = $curUser->allowed($prefix.'add');
		$vars['can_edit'] = $curUser->allowed($prefix.'edit');
		$vars['can_delete'] = $curUser->allowed($prefix.'delete');
		
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
		}

		$vars['can_save'] = ($action === 'add' && $vars['can_add']) || ($action === 'edit' && $vars['can_edit']);
		$vars['can_edit_meta'] = $vars['can_add_meta'] = $vars['can_delete_meta'] = $vars['can_save'];
	}

	//---------------------------------------------------------------------------

}
