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

require(escher_core_dir.'/publish/models/content_objects.php');

//------------------------------------------------------------------------------

class _XMLImportExportModel extends EscherModel
{
	private static $_coreTables = array
	(
		'perm', 'role', 'user', 'user_role', 'role_perm', 'pref', 
		'theme', 'template', 'snippet', 'tag', 'style', 'script', 'image', 
		'category', 'model', 'model_meta', 'model_category', 'model_part', 'page', 'page_meta', 'page_category', 'page_part',
		'block', 'block_category', 'image_meta', 'image_category', 'file', 'file_meta', 'file_category', 'link', 'link_meta', 'link_category',
		'plugin',
	);

	private static $_ignoreTables = array
	(
		'nonce', 'cache',
	);

	//---------------------------------------------------------------------------

	public function __construct($params)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function toXML($params = NULL)
	{
		$format = isset($params['format']) ? $params['format'] : false;
		$onlyTables = !empty($params['only_tables']) ? $params['only_tables'] : NULL;
		$excludeTables = !empty($params['exclude_tables']) ? $params['exclude_tables'] : NULL;
		$binaryData = !empty($params['binary_data']) ? $params['binary_data'] : NULL;
		$cdata = !empty($params['cdata']) ? $params['cdata'] : NULL;
		$filters = !empty($params['filters']) ? $params['filters'] : NULL;
	
		$db = $this->loadDBWithPerm(EscherModel::PermRead);
				
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><escher-cms></escher-cms>');
		$xml->addAttribute('version', EscherVersion::SchemaVersion);
				
		$tables = $this->getTables($db, $onlyTables, $excludeTables);

		foreach ($tables as $tableName) 
		{
			$child = $xml->addChild($tableName.'s');

			if (($filter = @$filters[$tableName]) || ($filter = @$filters['*']))
			{
				$where = $filter[0];
				$bind = @$filter[1];
			}
			else
			{
				$where = $bind = NULL;
			}
			
			foreach ($db->selectRows($tableName, '*', $where, $bind) as $row)
			{
				$subchild = $child->addChild($tableName);
				foreach ($row as $column => $value)
				{
					$isBinary = !empty($binaryData[$tableName][$column]) || !empty($binaryData['*'][$column]);
					$isCDATA = $isBinary || !empty($cdata[$tableName][$column]) || !empty($cdata['*'][$column]);

					if ($isCDATA)
					{
						$node = $subchild->addChild($column);
						$node = dom_import_simplexml($node);
						$node->appendChild($node->ownerDocument->createCDATASection($isBinary ? base64_encode($value) : $value));
					}
					else
					{
						$subchild->addChild($column, SparkView::escape_xml($value));
					}
				}
			}
		}
		
		$xml = $xml->asXML();
		
		if ($format && extension_loaded('dom'))
		{
			$doc = new DOMDocument();
			$doc->preserveWhiteSpace = false;
			$doc->formatOutput = true;
			$doc->loadXML($xml);
			$xml = $doc->saveXML();
		}
		
		return $xml;
	}
	
	//---------------------------------------------------------------------------
	
	public function fromXML($xml, $params = NULL)
	{
		$onlyTables = !empty($params['only_tables']) ? $params['only_tables'] : NULL;
		$excludeTables = !empty($params['exclude_tables']) ? $params['exclude_tables'] : NULL;
		$binaryData = !empty($params['binary_data']) ? $params['binary_data'] : NULL;
		$nullData = !empty($params['null_data']) ? $params['null_data'] : NULL;
		$filters = !empty($params['filters']) ? $params['filters'] : NULL;

      if (($xml = simplexml_load_string($xml)) === false)
      {
			$errors = '';
			foreach (libxml_get_errors() as $error)
			{
				$errors .= $error->message . "\n";
			}
			throw new SparkHTTPException_BadRequest(NULL, array('reason'=>$errors));
      }
      
      // validate schema version
      
		if ($xml['version'] != EscherVersion::SchemaVersion)
		{
			throw new SparkHTTPException_BadRequest('XML file could not be imported', array('reason'=>'schema version mismatch'));
		}

		$db = $this->loadDBWithPerm(EscherModel::PermWrite);

		$tables = $this->getTables($db, $onlyTables, $excludeTables);

		$db->begin();

		try
		{
			// first, delete existing rows for each table (in reverse order to avoid foreign key constraint violations)
			
			foreach (array_reverse($tables) as $tableName) 
			{
				$container = $tableName.'s';
				
				if (array_key_exists($container, $xml) && (count($xml->$container->$tableName) > 0))
				{
					if (($filter = @$filters[$tableName]) || ($filter = @$filters['*']))
					{
						$where = $filter[0];
						$bind = @$filter[1];
					}
					else
					{
						$where = $bind = NULL;
					}
					$db->deleteRows($tableName, $where, $bind);
				}
			}
			
			// now we can insert the new rows...
			
			$xmlUpdates = $xml->{'escher-cms-updates'};

			foreach ($tables as $tableName) 
			{
				$container = $tableName.'s';
				
				if (array_key_exists($container, $xml) && (count($xml->$container->$tableName) > 0))
				{
					$iter = $xml->$container;
					$update = false;
				}
				elseif (array_key_exists($container, $xmlUpdates) && (count($xmlUpdates->$container->$tableName) > 0))
				{
					$iter = $xmlUpdates->$container;
					$update = true;
				}
				else
				{
					$iter = NULL;
				}
				if ($iter)
				{
					foreach ($iter->$tableName as $element)
					{
						$element = get_object_vars($element);
						
						unset($key);	// assumption: single primary key exists and is first column

						foreach ($element as $column => &$value)
						{
							if (!isset($key))
							{
								$key = $column;
							}
							if (is_object($value))
							{
								$value = (string)$value;
							}
							if (!empty($binaryData[$tableName][$column]) || !empty($binaryData['*'][$column]))
							{
								$value = base64_decode($value);
							}
							if (($value === '') && (!empty($nullData[$tableName][$column]) || !empty($nullData['*'][$column])))
							{
								$value = NULL;
							}
						}
						
						if ($update)
						{
							$db->upsertRow($tableName, $element, $key);
						}
						else
						{
							$db->insertRow($tableName, $element);
							
							// fixup for mysql auto-increment columns
							
							if (isset($element['id']) && ($element['id'] === '0'))
							{
								if (($rowID = $db->lastInsertID()) != 0)
								{
									$db->updateRows($tableName, array('id'=>0), 'id=?', $rowID);
								}
							}
						}
					}
				}
			}
		}
		catch (Exception $e)
		{
			$db->rollback();
			throw $e;
		}

		$db->commit();
	}
	
	//---------------------------------------------------------------------------
	
	private function getTables($db, $onlyTables = NULL, $excludeTables = NULL)
	{
		$allTables = $db->getFunction('metadata')->tables(true, true);
		$includeTables = empty($onlyTables) ? self::$_coreTables : $onlyTables;
		$excludeTables = empty($excludeTables) ? self::$_ignoreTables : array_merge(self::$_ignoreTables, $excludeTables);
		
		// We go through this rigmarole to keep the core tables in order so we
		// don't run afoul of foreign key constraints during import.
		
		$includeTables = array_intersect($includeTables, $allTables);
		if (empty($onlyTables))
		{
			$tables = array_diff(array_merge($includeTables, array_diff($allTables, $includeTables)), $excludeTables);
		}
		else
		{
			$tables = array_diff($includeTables, $excludeTables);
		}

		return $tables;
	}
	
	//---------------------------------------------------------------------------
	
}
