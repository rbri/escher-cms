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

class ArchiveTags extends EscherParser
{
	private $_archive_stack;
	private $_date_stack;

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
		$this->_archive_stack = array();
		$this->_date_stack = array();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_archives()
	{
		$this->pushNamespace('archives');
		return true;
	}
		
	protected function _xtag_ns_archives()
	{
		$this->popNamespace('archives');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_if_index($atts)
	{
		extract($this->gatts(array(
			'type' => '',
		),$atts));

		$pageType = $this->currentPageContext()->type;
		
		switch ($pageType)
		{
			case 'ArchiveYearIndex':
				return (($type === '') || ($type === 'year'));
			case 'ArchiveMonthIndex':
				return (($type === '') || ($type === 'month'));
			case 'ArchiveDayIndex':
				return (($type === '') || ($type === 'day'));
			default:
				return false;
		}
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_count($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published,sticky',
			'limit' => '0',
			'offset' => '0',
		),$atts));

		$page = $this->currentPageContext();

		// compute out the date range of the archives to be counted

		if (!$this->getDateRange($page, $startDate, $endDate))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'date not found'));
		}

		// find our real parent so we count only children of the archives section
		
		$parent = $this->findNonVirtualParent($page);

		return $this->content->countPages($parent, NULL, $category, $status, $startDate, $endDate, $limit, $offset);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_first($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published,sticky',
			'limit' => '1',
			'offset' => '0',
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$page = $this->currentPageContext();

		// compute out the date range of the archives to be searched

		if (!$this->getDateRange($page, $startDate, $endDate))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'date not found'));
		}

		// find our real parent so we retrive only children of the archives section
		
		$parent = $this->findNonVirtualParent($page);
		$baseURI = $parent->uri();

		$pages = $this->content->fetchPages($parent, NULL, $category, $status, $startDate, $endDate, 1, $offset, $sort, $order);

		if (empty($pages) || !$page = $pages[0])
		{
			$this->dupPageContext();
			$this->dup($this->_archive_stack);
			$this->reportError(self::$lang->get('page_not_found'), E_USER_WARNING);
			return false;
		}
		
		// set an archive-like uri

		if ($this->app->get_pref('archive_date_based_urls'))
		{
			$published = $this->factory->manufacture('SparkDateTime', $page->published)->ymd('/');
			$page->setURI($baseURI . '/' . $published . '/' . $page->slug);
		}
		
		$page->isFirst = true;
		$page->isLast = false;
		$this->pushPageContext($page);
		$this->pushArchive($page);
		return true;
	}

	protected function _xtag_archives_first($atts)
	{
		$this->popArchive();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_last($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published,sticky',
			'limit' => '0',
			'offset' => '0',
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$page = $this->currentPageContext();

		// compute out the date range of the archives to be searched

		if (!$this->getDateRange($page, $startDate, $endDate))
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'date not found'));
		}

		// find our real parent so we can retrive only children of the archives section
		
		$parent = $this->findNonVirtualParent($page);
		$baseURI = $parent->uri();

		$pages = $this->content->fetchPages($parent, NULL, $category, $status, $startDate, $endDate, $limit, $offset, $sort, $order);

		if (empty($pages) || !$page = $pages[count($pages)-1])
		{
			$this->dupPageContext();
			$this->dup($this->_archive_stack);
			$this->reportError(self::$lang->get('page_not_found'), E_USER_WARNING);
			return false;
		}

		// set an archive-like uri

		if ($this->app->get_pref('archive_date_based_urls'))
		{
			$published = $this->factory->manufacture('SparkDateTime', $page->published)->ymd('/');
			$page->setURI($baseURI . '/' . $published . '/' . $page->slug);
		}
		
		$page->isFirst = false;
		$page->isLast = true;
		$this->pushPageContext($page);
		$this->pushArchive($page);
		return true;
	}

	protected function _xtag_archives_last($atts)
	{
		$this->popArchive();
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_each($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published,sticky',
			'limit' => '0',
			'offset' => '0',
			'sort' => 'published',
			'order' => 'desc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		$page = $this->currentPageContext();

		// compute out the date range of the archives to be searched

		if (!$this->getDateRange($page, $startDate, $endDate))
		{
			$this->reportError(self::$lang->get('unknown_tag', '<et:archives:each>'), E_USER_WARNING);
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'date not found'));
		}

		// find our real parent so we can retrieve only children of the archives section
		
		$parent = $this->findNonVirtualParent($page);
		$baseURI = $parent->uri();
		$published = $this->factory->manufacture('SparkDateTime');

		if ($pages = $this->content->fetchPages($parent, NULL, $category, $status, $startDate, $endDate, $limit, $offset, $sort, $order))
		{
			$content = $this->getParsable();
			
			$numPages = count($pages);
			$whichPage = 0;
			foreach ($pages as $page)
			{
				++$whichPage;
				
				// set an archive-like uri

				if ($this->app->get_pref('archive_date_based_urls'))
				{
					$published->set($page->published);
					$page->setURI($baseURI . '/' . $published->ymd('/') . '/' . $page->slug);
				}

				$page->isFirst = ($whichPage == 1);
				$page->isLast = ($whichPage == $numPages);

				$this->pushPageContext($page);
				$this->pushArchive($page);
				$out .= $this->parseParsable($content);
				$this->popArchive();
				$this->popPageContext();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_page($atts)
	{
		if (!$page = $this->currentArchive())
		{
			$this->dupPageContext();
			return false;
		}

		$this->pushPageContext($page);
		return true;
	}

	protected function _xtag_archives_page($atts)
	{
		$this->popPageContext();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_if_first($atts)
	{
		return ($page = $this->currentArchive()) ? $page->isFirst : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_if_last($atts)
	{
		return ($page = $this->currentArchive()) ? $page->isLast : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_dates_each($atts)
	{
		extract($this->gatts(array(
			'category' => '',
			'status' => 'published,sticky',
			'limit' => '0',
			'offset' => '0',
			'sort' => 'published',
			'order' => 'desc',
			'format' => '%Y-%m-%d',
			'for' => 'published',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		$page = $this->currentPageContext();

		// compute out the date range of the archives to be searched

		$this->getDateRange($page, $startDate, $endDate);

		// find our real parent so we can retrieve only children of the archives section
		
		$parent = $this->findNonVirtualParent($page);

		$rows = $this->content->fetchPageRows($parent, NULL, $category, $status, $startDate, $endDate, $limit, $offset, $sort, $order);
		if (!empty($rows))
		{
			// convert dates to selected timezone, format and remove duplicates
			
			$dates = array();
			foreach ($rows as $row)
			{
				$date = $this->app->format_date($row[$for] . ' UTC', $format, 1);
				$dates[$date] = $date;
			}
			unset($rows);

			$content = $this->getParsable();
			$numDates = count($dates);
			$whichDate = 0;
			foreach ($dates as $date)
			{
				++$whichDate;
				$this->pushDate(array($date, ($whichDate == 1), ($whichDate == $numDates)));
				$out .= $this->parseParsable($content);
				$this->popDate();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_archives_date($atts)
	{
		extract($this->gatts(array(
			'format' => '',
		),$atts));
		
		if ($date = $this->currentDate())
		{
			return $this->output->escape(($format === '') ? $date[0] : $this->factory->manufacture('SparkDateTime', $date[0], $this->prefs['site_time_zone'])->strformat($format));
		}
		else
		{
			return '';
		}
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_date_if_first($atts)
	{
		return ($date = $this->currentDate()) ? $date[1] : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_archives_date_if_last($atts)
	{
		return ($date = $this->currentDate()) ? $date[2] : false;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushArchive($page)
	{
		if (!($page instanceof Page))
		{
			$this->reportError(self::$lang->get('not_a_page'), E_USER_WARNING);
			return;
		}
		
		$this->_archive_stack[] = $page;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popArchive()
	{
		array_pop($this->_archive_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentArchive()
	{
		return end($this->_archive_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushDate($date)
	{
		$this->_date_stack[] = $date;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popDate()
	{
		array_pop($this->_date_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentDate()
	{
		return end($this->_date_stack);
	}
	
	//---------------------------------------------------------------------------
	
	private function getDateRange($page, &$startDate, &$endDate)
	{
		try
		{
			$pageType = $page->type;
	
			// compute out the date range of the archives to be searched
	
			$timezone = $this->app->get_pref('site_time_zone', 'UTC');
	
			switch ($pageType)
			{
				case 'ArchiveYearIndex':
					$dateTime = $page->slug . '-01-01 00:00:00';
					$startDate = $this->factory->manufacture('SparkDateTime', $dateTime, $timezone);
					$endDate = $this->factory->manufacture('SparkDateTime', $dateTime, $timezone);
					$endDate->addYears(1);
					$endDate->addSeconds(-1);
					break;
				case 'ArchiveMonthIndex':
					$dateTime = $page->parent()->slug . '-' . $page->slug . '-01 00:00:00';
					$startDate = $this->factory->manufacture('SparkDateTime', $dateTime, $timezone);
					$endDate = $this->factory->manufacture('SparkDateTime', $dateTime, $timezone);
					$endDate->addMonths(1);
					$endDate->addSeconds(-1);
					break;
				case 'ArchiveDayIndex':
					$dateTime = $page->parent()->parent()->slug . '-' . $page->parent()->slug . '-' . $page->slug . ' 00:00:00';
					$startDate = $this->factory->manufacture('SparkDateTime', $dateTime, $timezone);
					$endDate = $this->factory->manufacture('SparkDateTime', $dateTime, $timezone);
					$endDate->addDays(1);
					$endDate->addSeconds(-1);
					break;
	
				// this tag is only available on archive index pages
	
				default:
					return false;
			}
			
			// convert dates to GMT for proper comparison to dates stored in database
			
			$startDate = $startDate->setTimeZone('UTC')->ymdhms();
			$endDate = $endDate->setTimeZone('UTC')->ymdhms();
		}
		catch (Exception $e)
		{
			$startDate = $endDate = NULL;
			return false;
		}

		return true;
	}
	
	//---------------------------------------------------------------------------
}
