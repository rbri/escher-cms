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

class EventLogSettingsController extends SettingsController
{	
	private $_plugDir;
	private $_model;

	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		$tabs =& parent::get_tabs();
		$this->app->append_tab($tabs, 'event-log');
	}

	//---------------------------------------------------------------------------

	public function action_event_log($params)
	{
		$myInfo = $this->factory->getPlug('EventLogSettingsController');
		$this->_plugDir = dirname($myInfo['file']);
		$this->_model = $this->newModel('EventLog');

		if (!$this->_model->installed())
		{
			return $this->event_log_install($params);
		}

		switch (@$params[0])
		{
			case 'view':
				return $this->event_log_view($this->dropParam($params));
			case 'clear':
				return $this->event_log_clear($this->dropParam($params));
			default:
				if (!$params['count'])
				{
					$this->redirect('/settings/event-log/view');
				}
		}
	
		throw new SparkHTTPException_NotFound(NULL, array('reason'=>"action not found: {$params[0]}"));
	}

	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private function event_log_install($params)
	{
		$this->observer->notify('escher:page:request_add_element:head', $this->getInternalStyleElement());
		
		$this->getCommonVars($vars);
		$vars['selected_subtab'] = 'event-log';
		$vars['action'] = 'install';

		if (isset($params['pv']['install']))
		{
			try
			{
				$this->_model->install();
				$this->_model->logEvent('installed Event Log plugin', $this->app->get_user()->id);
				$this->session->flashSet('notice', 'Installation successful.');
				$this->redirect('/settings/event-log');
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

	private function event_log_view($params)
	{
		if (!$eventsPerPage = $this->app->get_pref('event_log_events_per_page', 25))
		{
			$eventsPerPage = 25;
		}

		if (!$curPage = intval(@$params[0]))
		{
			$curPage = 1;
		}

		$this->observer->notify('escher:page:request_add_element:head', $this->getInternalStyleElement());

		$numEvents = $this->_model->countEvents();

		$curUser = $this->app->get_user();

		$this->getCommonVars($vars);
		$vars['can_clear'] = $curUser->allowed('settings:event-log:clear');

		$vars['selected_subtab'] = 'event-log';
		$vars['action'] = 'view';
		$vars['events'] = $this->_model->getEvents($eventsPerPage, ($curPage-1)*$eventsPerPage);

		$vars['class'] = 'event-pagination';
		$vars['page_url'] = $this->urlTo('/settings/event-log/view/');
		$vars['cur_page'] = $curPage;
		$vars['last_page'] = intval(ceil($numEvents/$eventsPerPage));

		$vars['label_previous'] = '&larr; Newer';
		$vars['label_next'] = 'Older &rarr;';

		$vars['notice'] = $this->session->flashGet('notice');

		$this->app->view()->pushViewDir($this->_plugDir . '/views');
		$this->render('main', $vars);
		$this->app->view()->popViewDir();
	}

	//---------------------------------------------------------------------------

	private function event_log_clear($params)
	{
		$this->getCommonVars($vars);
		$vars['selected_subtab'] = 'event-log';
		$vars['action'] = 'clear';

		if (isset($params['pv']['clear']))
		{
			try
			{
				$this->_model->clear();
				$this->_model->logEvent('Cleared Event Log', $this->app->get_user()->id);
				$this->session->flashSet('notice', 'Operation successful.');
				$this->redirect('/settings/event-log');
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

	private function getInternalStyleElement()
	{
		return <<<EOD
<style type="text/css">
	#event-list {
		width: 100%;
	}
	#event-list thead {
		background-color: #f5f5f5;
		font-size: 80%;
		text-align: left;
	}
	#event-list thead th {
		padding: 5px;
	}
	#event-list tbody {
		font-size: 80%;
	}
	#event-list tbody tr.odd {
	}
	#event-list tbody tr.even {
		background-color: #CEDE9E;
	}
	#event-list tbody td {
		padding: 5px 0 5px 0;
	}

	ol.event-pagination {
		background-color: #f5f5f5;
		font-size: 80%;
		padding: 12px 0 15px 35px;
		text-align: right;
	}
	ol.event-pagination li {
		padding: 0px 10px 0px 0px;
		display: inline;
	}
	ol.event-pagination li.selected {
		font-weight: bold;
		text-decoration: underline;
	}
	ol.event-pagination li a {
		color: black;
		text-decoration: none;
	}
</style>

EOD;
	}
	
//---------------------------------------------------------------------------
	
}
