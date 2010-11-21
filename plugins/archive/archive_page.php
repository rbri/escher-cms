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

// -----------------------------------------------------------------------------

class _ArchivePage extends Page
{
	private $_model;

	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);

		$this->_model = $this->newModel('Archive');
	}

	//---------------------------------------------------------------------------

	public function fetchOverridePage($model)
	{
		$lastMagic = count($this->magic) - 1;

		$parent = $this->_model->findParent($this);

		switch ($lastMagic)
		{
			case 0:
				return $this->fetchYearArchivePage($model, $parent, $this->magic);
			case 1:
				return $this->fetchMonthArchivePage($model, $parent, $this->magic);
			case 2:
				return $this->fetchDayArchivePage($model, $parent, $this->magic);
			case 3:
				return $this->fetchChildPage($model, $parent, $this->magic);
			default:
				return false;
		}
	}

	//---------------------------------------------------------------------------

	private function fetchYearArchivePage($model, $parent, $params)
	{
		if (strlen($year = $params[0]) != 4)
		{
			return false;
		}
		
		if ($page = $this->_model->fetchArchiveIndexPage($model, $parent, $year, 'ArchiveYearIndex'))
		{
			$time = strtotime("{$year}-01-01");
			$page->status = self::Status_published;
			$page->breadcrumb = gmstrftime('%Y', $time);
			$page->title = gmstrftime($page->title, $time);
			$page->setParent($this->parent());
			$page->setURI($this->uri());
		}
		
		return $page;
	}
	
	//---------------------------------------------------------------------------

	private function fetchMonthArchivePage($model, $parent, $params)
	{
		if ((strlen($year = $params[0]) != 4) || !is_numeric($year))
		{
			return false;
		}
		
		if ((strlen($month = $params[1]) != 2) || !is_numeric($month) || ($month < 1) || ($month > 12))
		{
			return false;
		}
		
		if ($page = $this->_model->fetchArchiveIndexPage($model, $parent, $month, 'ArchiveMonthIndex'))
		{
			$time = strtotime("{$year}-{$month}-01");
			$page->status = self::Status_published;
			$page->breadcrumb = gmstrftime('%B', $time);
			$page->title = gmstrftime($page->title, $time);
			$page->setParent($this->parent());
			$page->setURI($this->uri());
		}
		
		return $page;
	}
	
	//---------------------------------------------------------------------------

	private function fetchDayArchivePage($model, $parent, $params)
	{
		if ((strlen($year = $params[0]) != 4) || !is_numeric($year))
		{
			return false;
		}
		
		if ((strlen($month = $params[1]) != 2) || !is_numeric($month) || ($month < 1) || ($month > 12))
		{
			return false;
		}
		
		if ((strlen($day = $params[2]) != 2) || !is_numeric($day) || ($day < 1) || ($day > 31))
		{
			return false;
		}
		
		if ($page = $this->_model->fetchArchiveIndexPage($model, $parent, $day, 'ArchiveDayIndex'))
		{
			$time = strtotime("{$year}-{$month}-{$day}");
			$page->status = self::Status_published;
			if ($this->app->get_pref('archive_breadcrumb_date_suffix'))
			{
				$page->breadcrumb = gmdate('jS', $time);
			}
			else
			{
				$page->breadcrumb = gmstrftime('%d', $time);
			}
			$page->title = gmstrftime($page->title, $time);
			$page->setParent($this->parent());
			$page->setURI($this->uri());
		}
		
		return $page;
	}
	
	//---------------------------------------------------------------------------

	private function fetchChildPage($model, $parent, $params)
	{
		if ($page = $model->fetchChildPage($parent, $params[3], false))
		{
			// check published date (we need to localize time zone here)
			
			$published = explode('-', $page->published('Y-m-d'));

			if (($published[0] !=  $params[0]) || ($published[1] !=  $params[1]) || ($published[2] !=  $params[2]))
			{
				return false;		// publication date did not match uri
			}
			$page->setParent($this->parent());
			$page->setURI($this->uri());
		}
		
		return $page;
	}
	
	//---------------------------------------------------------------------------
}
