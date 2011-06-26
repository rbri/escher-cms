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
    * @param string $mode Method used to match search terms ("exact", "any", "all")
    * @param int $parentID If provided, only search pages with that are children of this page
    * @param string $status Status of pages to include in search
    * @param bool $searchTitles (true|false) whether to search in page titles
    * @param bool|string|array $searchParts (true|false|array) whether to search in page parts, optionally a part name (or array of part names) to search
    * @param int $limit Limits number of pages returned in search
    * @param int $offset Offset for paginating result set (CURRENTLY NOT IMPLEMENTED)
    * @param string $sort Optional comma-separated list of columns to sort by
    * @param string $order Optional sort direction, corresponding to $sort param
    * @return array: list of [page_ID=>part_ID] with matching text
    */

	public function searchPages($find, $mode, $parentID = NULL, $status = NULL, $searchTitles = true, $searchParts = true, $limit = NULL, $offset = NULL, $sort = NULL, $order = NULL)
	{
		$db = $this->loadDBWithPerm(EscherModel::PermRead);

		// collapase multiple whitespace characters, escape SQL special characters, trim

		$find = trim(str_replace(array('\\', '%', '_', '\''), array('\\\\', '\\%', '\\_', '\\\''), preg_replace('/\s+/', ' ', $find)));

		if (!SparkUtil::valid_int($limit))
		{
			$limit = NULL;
		}
		
		if (!SparkUtil::valid_int($offset))
		{
			$offset = NULL;
		}
				
		if (!empty($sort))
		{
			$orderBy = $this->buildOrderBy($sort, $order, 'filterPageColumn');
		}
		if (empty($orderBy))
		{
			$orderBy = '{page}.published DESC';
		}
		if ($orderBy !== 'RAND')
		{
			$orderBy .= ', {page}.id';		// ensure consistency if dates are the same
		}

		$result = array();

		if (!empty($status) && ($status !== 'any'))
		{
			$where[] = $this->buildStatusIn($db, 'page', $status);
			$bind = $status;
		}
		
		if (is_int($parentID))
		{
			$where[] = '{page}.parent_id = ?';
			$bind[] = $parentID;
		}
		
		// TODO
		// We should be using a SQL UNION statement so that orderby, limit and offset are correctly honored.
		// Instead, we are splicing the results of two separate queries, which doesn't produce exactly
		// what we want.
		
		$offset = NULL;	// ignored until we switch to UNION

		if ($searchTitles)
		{
			$titleBind = $bind;
			if ($mode === 'exact')
			{
				$where[] = '{page}.title LIKE ?';
				$titleBind[] = "%{$find}%";
			}
			else
			{
				$bool = ($mode === 'any') ? ' OR ' : ' AND ';
				$like = array();
				foreach (explode(' ', $find) as $term)
				{
					$like[] = '{page}.title LIKE ?';
					$titleBind[] = "%{$term}%";
				}
				$where[] = '(' . implode($bool, $like) . ')';
			}

			$sql = $db->buildSelect('page', 'id', NULL, implode(' AND ', $where), $orderBy, $limit, $offset, true);
			foreach($db->query($sql, $titleBind)->rows() as $row)
			{
				$result[$row['id']][] = NULL;
			}

			array_pop($where);
		}

		if ($searchParts)
		{
			$joinConds[] = array('leftField'=>'id', 'rightField'=>'page_id');

			if (is_string($searchParts))
			{
				$joinConds[] = array('rightField'=>'name', 'value'=>'?');
				$bind = array_merge(array($searchParts), $bind);
			}

			elseif (is_array($searchParts))
			{
				$db->makeList($searchParts);
				$markers = '('.$db->buildMarkers(count($searchParts)).')';
				$joinConds[] = array('rightField'=>'name', 'joinOp'=>'IN', 'value'=>$markers);
				$bind = array_merge($searchParts, $bind);
			}

			$joins[] = array('table'=>'page_part', 'conditions'=>$joinConds);

			if ($mode === 'exact')
			{
				$where[] = '{page_part}.content LIKE ?';
				$bind[] = "%{$find}%";
			}
			else
			{
				$bool = ($mode === 'any') ? ' OR ' : ' AND ';
				$like = array();
				foreach (explode(' ', $find) as $term)
				{
					$like[] = '{page_part}.content LIKE ?';
					$bind[] = "%{$term}%";
				}
				$where[] = '(' . implode($bool, $like) . ')';
			}
		
			$sql = $db->buildSelect('page', '{page}.id, {page_part}.name', $joins, implode(' AND ', $where), $orderBy, $limit, $offset, true);

			foreach($db->query($sql, $bind)->rows() as $row)
			{
				$result[$row['id']][] = $row['name'];
			}
		}
		
		// TODO
		// Following hack not necessary once we switch to UNION

		if ($limit)
		{
			while (count($result) > $limit)
			{
				array_pop($result);
			}
		}

		return $result;
	}

	//---------------------------------------------------------------------------
}
