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

class SearchModel extends PublishContentModel
{
	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
   /**
    * Search page titles and/or parts for a text string.
    *
    * @param string $find Text to search for
    * @param bool $searchTitles (true|false) whether to search in page titles
    * @param bool|string|array $searchParts (true|false|array) whether to search in page parts, optionally a part name (or array of part names) to search
    * @return array: list of page IDs with matching text
    */

	public function searchPages($find, $parentID = NULL, $searchTitles = true, $searchParts = true)
	{
		$db = $this->loadDBWithPerm(EscherModel::PermRead);

		$joins[] = array('type'=>'left', 'table'=>'page_part', 'conditions'=>array(array('leftField'=>'id', 'rightField'=>'page_id', 'joinOp'=>'=')));
		$where = array();
		$bind = array();
		
		if ($searchTitles)
		{
			$where[] = '{page}.title LIKE ?';
			$bind[] = "%{$find}%";
		}
		
		if ($searchParts)
		{
			$clause = '{page_part}.content LIKE ?';
			$bind[] = "%{$find}%";
			
			if (is_string($searchParts))
			{
				$clause = '(' . $clause . ' AND {page_part}.name = ?)';
				$bind[] = $searchParts;
			}

			elseif (is_array($searchParts))
			{
				$clause = '(' . $clause . ' AND ' . $db->buildFieldIn('page_part', 'name', $searchParts) . ')';
				$bind = array_merge($bind, $searchParts);
			}

			$where[] = $clause;
		}
		
		$where = '(' . implode(' OR ', $where) . ')';

		$status = array('published', 'sticky');
		{
			$where .= ' AND ' . $this->buildStatusIn($db, 'page', $status);
			$bind = array_merge($bind, $status);
		}
		
		// if a parent page is specified, only return children of that parent page (non-recursively)
		
		if (is_int($parentID))
		{
			$where .= ' AND {page}.parent_id = ?';
			$bind[] = $parentID;
		}

		$rows = array();
		
		foreach($db->selectJoinRows('page', '{page}.id', $joins, $where, $bind, true) as $row)
		{
			$rows[] = $row['id'];
		}

		return $rows;
	}

	//---------------------------------------------------------------------------
}
