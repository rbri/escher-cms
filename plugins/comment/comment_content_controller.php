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

class CommentContentController extends ContentController
{	
	private $_plugDir;
	private $_model;

	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct($app)
	{
		parent::__construct($app);

		$tabs =& parent::get_tabs();
		$this->app->append_tab($tabs, 'comments');
	}

	//---------------------------------------------------------------------------

	public function action_comments($params)
	{
		$myInfo = $this->factory->getPlug('CommentContentController');
		$this->_plugDir = dirname($myInfo['file']);
		$this->_model = $this->factory->manufacture('CommentModel');

		if (!$this->_model->installed())
		{
			return $this->comments_install($params);
		}

		switch (@$params[0])
		{
			case 'view':
				return $this->comments_view($this->dropParam($params));
			case 'moderate':
				return $this->comments_moderate($this->dropParam($params));
			case 'delete':
				return $this->comments_delete($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					$this->redirect('/content/comments/view');
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected function getCommentPerms(&$vars)
	{
		$curUser = $this->app->get_user();
		
		$vars['can_view'] = $curUser->allowed('content:comments:view');
		$vars['can_moderate'] = $curUser->allowed('content:comments:moderate');
		$vars['can_approve'] = $curUser->allowed('content:comments:moderate:approve');
		$vars['can_edit'] = $curUser->allowed('content:comments:moderate:edit');
		$vars['can_delete'] = $curUser->allowed('content:comments:delete');
		$vars['can_save'] = ($vars['can_approve'] || $vars['can_edit']);
	}
	
	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function comments_install($params)
	{
		$this->observer->notify('escher:page:request_add_element:head', $this->getInternalStyleElement());
		
		$this->getCommonVars($vars);
		$vars['selected_subtab'] = 'comments';
		$vars['action'] = 'install';

		if (isset($params['pv']['install']))
		{
			try
			{
				$this->_model->install();
				$this->observer->notify('EventLog:logevent', 'installed Comments plugin');
				$this->session->flashSet('notice', 'Installation successful.');
				$this->redirect('/content/comments');
			}
			
			catch (Exception $e)
			{
				$vars['errors']['install'] = $e->getMessage();
			}
		}

		$this->app->view()->pushViewDir($this->_plugDir . '/views');
		$this->render('main', $vars);
		$this->app->view()->popViewDir();
	}

	//---------------------------------------------------------------------------

	private function comments_view($params)
	{
		$commentsPerPage = $this->app->get_pref('comments-comments_per_page', 25);

		if (!$curPage = intval(@$params[0]))
		{
			$curPage = 1;
		}

		$this->observer->notify('escher:page:request_add_element:head', $this->getInternalStyleElement());

		$numComments = $this->_model->countComments();

		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$this->getCommentPerms($vars);
		
		$vars['selected_subtab'] = 'comments';
		$vars['action'] = 'view';
		$vars['comments'] = $this->_model->fetchComments(NULL, NULL, $commentsPerPage, ($curPage-1)*$commentsPerPage);

		$vars['page_url'] = $this->urlTo('/content/comments/view/');
		$vars['cur_page'] = $curPage;
		$vars['last_page'] = intval(ceil($numComments/$commentsPerPage));

		$vars['notice'] = $this->session->flashGet('notice');

		$this->app->view()->pushViewDir($this->_plugDir . '/views');
		$this->observer->notify('escher:render:before:content:comments:view', NULL);
		$this->render('main', $vars);
		$this->app->view()->popViewDir();
	}

	//---------------------------------------------------------------------------

	private function comments_moderate($params)
	{
		if (!$commentID = @$params['pv']['comment_id'])
		{
			if (!$commentID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'comment not found'));
			}
		}

		if (!$comment = $this->_model->fetchComment($commentID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'comment not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getCommentPerms($vars);

		if (isset($params['pv']['save']))
		{
			$comment->message = $params['pv']['comment_message'];
			$comment->approved = ($params['pv']['comment_approved'] == 1);
			
			if (!$vars['can_save'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			elseif ($this->validateComment($params['pv'], $errors))
			{
				try
				{
					$this->_model->updateComment($comment);
					$this->observer->notify('escher:site_change:content:comment:edit', strval($comment->id));
					$this->session->flashSet('notice', 'Comment saved successfully.');
					$this->redirect('/content/comments');
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
		
		$vars['selected_subtab'] = 'comments';
		$vars['action'] = 'moderate';
		$vars['comment'] = $comment;

		if (!empty($errors))
		{
			$vars['errors'] = $errors;
		}
	
		$this->app->view()->pushViewDir($this->_plugDir . '/views');
		$this->observer->notify('escher:page:request_add_element:head', $this->getInternalStyleElement());
		$this->observer->notify('escher:render:before:content:comments:moderate', $comment);
		$this->render('main', $vars);
		$this->app->view()->popViewDir();
	}

	//---------------------------------------------------------------------------

	private function comments_delete($params)
	{
		if (!$commentID = @$params['pv']['comment_id'])
		{
			if (!$commentID = @$params[0])
			{
				throw new SparkHTTPException_NotFound(NULL, array('reason'=>'comment not found'));
			}
		}

		if (!$comment = $this->_model->fetchComment($commentID))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'comment not found'));
		}
		
		$this->getCommonVars($vars);
		$this->getCommentPerms($vars);

		if (isset($params['pv']['delete']))
		{
			if (!$vars['can_delete'])
			{
				$vars['warning'] = 'Permission denied.';
			}
			else
			{
				$this->_model->deleteCommentByID($comment->id);
				$this->observer->notify('escher:site_change:content:comment:delete', strval($comment->id));
				$this->session->flashSet('notice', 'Comment deleted successfully.');
				$this->redirect('/content/comments');
			}
		}
		
		$vars['selected_subtab'] = 'comments';
		$vars['action'] = 'delete';
		$vars['comment'] = $comment;

		$this->app->view()->pushViewDir($this->_plugDir . '/views');
		$this->observer->notify('escher:page:request_add_element:head', $this->getInternalStyleElement());
		$this->observer->notify('escher:render:before:content:comment:delete', $comment);
		$this->render('main', $vars);
		$this->app->view()->popViewDir();
	}
	
	//---------------------------------------------------------------------------

	private function validateComment($params, &$errors)
	{
		$errors = array();
		
		// set errors
		
		if (empty($params['comment_message']))
		{
			$errors['comment_message'] = 'Comment message is required.';
		}
		
		return empty($errors);
	}
	
	//---------------------------------------------------------------------------

	private function getInternalStyleElement()
	{
		return <<<EOD
<style type="text/css">
	#comments-list {
		width: 100%;
	}
	#comments-list thead {
		background-color: #f5f5f5;
		font-size: 80%;
		text-align: left;
	}
	#comments-list thead th {
		padding: 5px;
	}
	#comments-list tbody {
		font-size: 80%;
	}
	#comments-list tbody tr.odd {
	}
	#comments-list tbody tr.even {
		background-color: #CEDE9E;
	}
	#comments-list tbody td {
		padding: 5px 0 5px 0;
	}

	ol.comments-pagination {
		background-color: #f5f5f5;
		font-size: 80%;
		padding: 12px 0 15px 35px;
		text-align: right;
	}
	ol.comments-pagination li {
		padding: 0px 10px 0px 0px;
		display: inline;
	}
	ol.comments-pagination li.selected {
		font-weight: bold;
		text-decoration: underline;
	}
	ol.comments-pagination li a {
		color: black;
		text-decoration: none;
	}

	.comments-delete {
		margin-top: 20px;
	}
</style>

EOD;
	}
	
//---------------------------------------------------------------------------
	
}
