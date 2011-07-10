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

// -----------------------------------------------------------------------------

class _CacheMonitor extends EscherPlugin
{
	private $_flushes;

	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
		
		$this->observer->observe(array($this, 'flushCachesContent'), array('escher:site_change:content'));
		$this->observer->observe(array($this, 'flushCachesDesign'), array('escher:site_change:design'));
		$this->observer->observe(array($this, 'flushCachesSettings'), array('escher:site_change:settings'));
		$this->observer->observe(array($this, 'flushCachesUpgrade'), array('escher:version:upgrade'));
		
		$this->observer->observe(array($this, 'performFlushes'), array('SparkApplication:run:after', 'SparkPlug:redirect:before'));
	}

	//---------------------------------------------------------------------------

	public function flushCachesContent($event, $object)
	{
		// A very simple and conservative full cache flush.
		// In the future, we may add some intelligence to flush only the objects that have actually changed.
	
		$this->_flushes['escher:cache:request_flush:partial'][0] = true;
		$this->_flushes['escher:cache:request_flush:page'][0] = true;
	}

	//---------------------------------------------------------------------------

	public function flushCachesDesign($event, $object, $branch = EscherProductionStatus::Production, $affected = NULL)
	{
		// A very simple and conservative full cache flush. It is branch-aware, but not object-aware.
		// In the future, we may add some intelligence to flush only the objects that have actually changed.
		
		if ($object instanceof Branch)
		{
			$flushPlugs = !empty($affected['tag']);

			// push events are a special case, since they do not affect any branches above the push target
			
			if ($event === 'escher:site_change:design:branch:push')
			{
				$this->_flushes['escher:cache:request_flush:partial'][$branch] = true;
				$this->_flushes['escher:cache:request_flush:page'][$branch] = true;
				
				if ($flushPlugs)
				{
					$this->_flushes['escher:cache:request_flush:plug'][$branch] = true;
				}
				return;
			}
		}
		else
		{
			$flushPlugs = ($object instanceof Tag);
		}
		
		// Flush affected branches (the specified branch and those above it).
		// If target is production, branch, we know all branches are affected so we pass '0' as an optimization.
				
		if ($branch == EscherProductionStatus::Production)
		{
			$this->_flushes['escher:cache:request_flush:partial'][0] = true;
			$this->_flushes['escher:cache:request_flush:page'][0] = true;
			
			if ($flushPlugs)
			{
				$this->_flushes['escher:cache:request_flush:plug'][0] = true;
			}
		}
		elseif ($branch)
		{
			for ($id = $branch; $id <= EscherProductionStatus::Development; ++$id)
			{
				if (!isset($this->_flushes['escher:cache:request_flush:partial'][0]))
				{
					$this->_flushes['escher:cache:request_flush:partial'][$id] = true;
				}
				if (!isset($this->_flushes['escher:cache:request_flush:page'][0]))
				{
					$this->_flushes['escher:cache:request_flush:page'][$id] = true;
				}
				if ($flushPlugs && !isset($this->_flushes['escher:cache:request_flush:plug'][0]))
				{
					$this->_flushes['escher:cache:request_flush:plug'][$id] = true;
				}
			}
		}
	}

	//---------------------------------------------------------------------------

	public function flushCachesSettings($event, $changedPrefsNames)
	{
		$this->_flushes['escher:cache:request_flush:partial'][0] = true;
		$this->_flushes['escher:cache:request_flush:page'][0] = true;
	}

	//---------------------------------------------------------------------------

	public function flushCachesUpgrade($event)
	{
		$this->_flushes['escher:cache:request_flush:plug'][0] = true;
		
		// flush admin side too...
		
		$this->observer->notify('Spark:cache:request_flush');
	}

	//---------------------------------------------------------------------------

	public function performFlushes($event)
	{
		if (!empty($this->_flushes))
		{
			foreach ($this->_flushes as $event => $branches)
			{
				foreach ($branches as $branch => $ignore)
				{
					$this->observer->notify($event, $branch);
				}
			}
		}
	}

	//---------------------------------------------------------------------------
}
