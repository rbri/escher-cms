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
	private $_model;

	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
		
		$this->observer->observe(array($this, 'flushCaches'), array('escher:site_change'));
	}

	//---------------------------------------------------------------------------

	public function flushCaches($event, $object)
	{
		// A very simple and conservative full cache flush.
		// In the future, we may add some intelligence to flush only the objects that have actually changed.
	
		// Currently flushing all branches. But if a design asset changed, we could be smarter and only flush the current working branch...
		
		$this->observer->notify('escher:cache:request_flush:partial', EscherProductionStatus::Production);
		$this->observer->notify('escher:cache:request_flush:partial', EscherProductionStatus::Staging);
		$this->observer->notify('escher:cache:request_flush:partial', EscherProductionStatus::Development);

		$this->observer->notify('escher:cache:request_flush:page', EscherProductionStatus::Production);
		$this->observer->notify('escher:cache:request_flush:page', EscherProductionStatus::Staging);
		$this->observer->notify('escher:cache:request_flush:page', EscherProductionStatus::Development);
	}

	//---------------------------------------------------------------------------
}
