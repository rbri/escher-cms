<?php

/*
Copyright 2009-2012 Sam Weiss
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

class _EscherSchemaModel extends EscherModel
{
	private static $_xml_params = array
	(
		'binary_data' => array
		(
			'file' => array
			(
				'content' => true,
			),
			'image' => array
			(
				'content' => true,
			),
		),
		'cdata' => array
		(
			'*' => array
			(
				'content' => true,
				'content_html' => true,
			),
		),
		'null_data' => array
		(
			'file' => array
			(
				'url' => true,
			),
			'image' => array
			(
				'url' => true,
			),
			'style' => array
			(
				'url' => true,
			),
			'script' => array
			(
				'url' => true,
			),
		),
	);

	public function create($params = NULL)
	{
		$db = $this->loadDB($params);

		$ct = $db->getFunction('create_table');
		$ci = $db->getFunction('create_index');

		$db->query('DROP TABLE IF EXISTS {perm}');
		$ct->table('perm');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('group_name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$db->query($ct->compile());

		$ci->table('perm');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'perm_name');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {role}');
		$ct->table('role');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$db->query($ct->compile());

		$ci->table('role');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'role_name');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {user}');
		$ct->table('user');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('email', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('login', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('password', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('nonce', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 63, '1');
		$ct->field('logged', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$db->query($ct->compile());

		$ci->table('user');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'email', 'user_email');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'login', 'user_login');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {user_role}');
		$ct->table('user_role');
		$ct->field('user_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('role_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('user_id, role_id');
		$ct->foreignKey('user_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('role_id', 'role', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {role_perm}');
		$ct->table('role_perm');
		$ct->field('role_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('perm_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('role_id, perm_id');
		$ct->foreignKey('role_id', 'role', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('perm_id', 'perm', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {pref}');
		$ct->table('pref');
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('user_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('group_name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('section_name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('position', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('type', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('att', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('validation', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('val', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->primaryKey('name, user_id');
		$ct->foreignKey('user_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {theme}');
		$ct->table('theme');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('family', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('lineage', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '0');
		$ct->field('style_url', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('script_url', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('image_url', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('parent_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
//		$ct->field('parent_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 'NULL', true);
		$ct->field('uuid', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 32, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagFixedLength);
		$ct->field('parent_uuid', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 32, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagFixedLength);
		$ct->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('parent_id', 'theme', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionSetNULL, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('theme');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, branch', 'theme_slug_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'theme_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'uuid, branch', 'theme_uuid_branch');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {template}');
		$ct->table('template');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('ctype', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'text/html');
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('theme_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('theme_id', 'theme', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('template');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name, theme_id, branch', 'template_name_theme_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'theme_id', 'template_theme');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'template_branch');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {snippet}');
		$ct->table('snippet');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('theme_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('theme_id', 'theme', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('snippet');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name, theme_id, branch', 'snippet_name_theme_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'theme_id', 'snippet_theme');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'snippet_branch');
		$db->query($ci->compile());
		
		$db->query('DROP TABLE IF EXISTS {tag}');
		$ct->table('tag');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('theme_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('theme_id', 'theme', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('tag');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name, theme_id, branch', 'tag_name_theme_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'theme_id', 'tag_theme');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'tag_branch');
		$db->query($ci->compile());
		
		$db->query('DROP TABLE IF EXISTS {style}');
		$ct->table('style');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('ctype', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'text/css');
		$ct->field('url', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'NULL', true);
		$ct->field('rev', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('theme_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('theme_id', 'theme', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('style');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, theme_id, branch', 'style_slug_theme_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'url, branch', 'style_url_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'theme_id', 'style_theme');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'style_branch');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {script}');
		$ct->table('script');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('ctype', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'application/javascript');
		$ct->field('url', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'NULL', true);
		$ct->field('rev', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('theme_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('theme_id', 'theme', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('script');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, theme_id, branch', 'script_slug_theme_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'url, branch', 'script_url_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'theme_id', 'script_theme');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'script_branch');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {image}');
		$ct->table('image');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('ctype', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'image/gif');
		$ct->field('url', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'NULL', true);
		$ct->field('width', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('height', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('alt', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('rev', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeBinary, 16*1024*1024-1);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('theme_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('branch', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('branch_status', iSparkDBQueryFunctionCreateTable::kFieldTypeByte, NULL, 0);
		$ct->field('priority', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('theme_id', 'theme', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('image');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, theme_id, branch', 'image_slug_theme_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'url, branch', 'image_url_branch');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'priority', 'image_priority');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'theme_id', 'image_theme');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'branch', 'image_branch');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {category}');
		$ct->table('category');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('level', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('position', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('count', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('parent_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
//		$ct->field('parent_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 'NULL', true);
		$ct->field('priority', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
//		$ct->foreignKey('parent_id', 'category', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('category');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, parent_id', 'category_slug_parent');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'position', 'category_position');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'priority', 'category_priority');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {model}');
		$ct->table('model');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('type', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('status', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('magical', iSparkDBQueryFunctionCreateTable::kFieldTypeBoolean, NULL, false);
		$ct->field('cacheable', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, -1);
		$ct->field('secure', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, -1);
		$ct->field('template_name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('model');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'model_name');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {model_meta}');
		$ct->table('model_meta');
		$ct->field('model_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->primaryKey('model_id, name');
		$ct->foreignKey('model_id', 'model', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());
		
		$db->query('DROP TABLE IF EXISTS {model_category}');
		$ct->table('model_category');
		$ct->field('model_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('category_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('model_id, category_id');
		$ct->foreignKey('model_id', 'model', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('category_id', 'category', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {model_part}');
		$ct->table('model_part');
		$ct->field('model_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('position', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('type', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('validation', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('content_html', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('filter_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('model_id, name');
		$ct->foreignKey('model_id', 'model', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {page}');
		$ct->table('page');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('level', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('position', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('breadcrumb', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('type', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('status', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('magical', iSparkDBQueryFunctionCreateTable::kFieldTypeBoolean, NULL, false);
		$ct->field('cacheable', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, -1);
		$ct->field('secure', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, -1);
		$ct->field('template_name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('published', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('parent_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
//		$ct->field('parent_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 'NULL', true);
		$ct->field('model_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('priority', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
//		$ct->foreignKey('parent_id', 'page', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('page');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'slug', 'page_slug');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'position', 'page_position');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'template_name', 'page_template_name');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'parent_id', 'page_parent');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug, parent_id', 'page_slug_parent');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'priority', 'page_priority');
		$db->query($ci->compile());
		
		$db->query('DROP TABLE IF EXISTS {page_meta}');
		$ct->table('page_meta');
		$ct->field('page_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->primaryKey('page_id, name');
		$ct->foreignKey('page_id', 'page', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());
		
		$db->query('DROP TABLE IF EXISTS {page_category}');
		$ct->table('page_category');
		$ct->field('page_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('category_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('page_id, category_id');
		$ct->foreignKey('page_id', 'page', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('category_id', 'category', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {page_part}');
		$ct->table('page_part');
		$ct->field('page_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('position', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('type', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'textarea');
		$ct->field('validation', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, '');
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('content_html', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('filter_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('page_id, name');
		$ct->foreignKey('page_id', 'page', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {block}');
		$ct->table('block');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('content_html', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('filter_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('priority', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('block');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'block_name');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'priority', 'block_priority');
		$db->query($ci->compile());
		
		$db->query('DROP TABLE IF EXISTS {block_category}');
		$ct->table('block_category');
		$ct->field('block_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('category_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('block_id, category_id');
		$ct->foreignKey('block_id', 'block', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('category_id', 'category', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {image_meta}');
		$ct->table('image_meta');
		$ct->field('image_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->primaryKey('image_id, name');
		$ct->foreignKey('image_id', 'image', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());
		
		$db->query('DROP TABLE IF EXISTS {image_category}');
		$ct->table('image_category');
		$ct->field('image_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('category_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('image_id, category_id');
		$ct->foreignKey('image_id', 'image', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('category_id', 'category', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {file}');
		$ct->table('file');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('ctype', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'image/gif');
		$ct->field('url', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, 'NULL', true);
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('description', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('status', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('download', iSparkDBQueryFunctionCreateTable::kFieldTypeBoolean, NULL, false);
		$ct->field('size', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('rev', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 1);
		$ct->field('content', iSparkDBQueryFunctionCreateTable::kFieldTypeBinary, 16*1024*1024-1);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('priority', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('file');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'slug', 'file_slug');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'priority', 'file_priority');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {file_meta}');
		$ct->table('file_meta');
		$ct->field('file_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->primaryKey('file_id, name');
		$ct->foreignKey('file_id', 'file', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());
		
		$db->query('DROP TABLE IF EXISTS {file_category}');
		$ct->table('file_category');
		$ct->field('file_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('category_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('file_id, category_id');
		$ct->foreignKey('file_id', 'file', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('category_id', 'category', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {link}');
		$ct->table('link');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('description', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('url', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('created', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('edited', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->field('author_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('editor_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('priority', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->foreignKey('author_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('editor_id', 'user', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionRestrict, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$ci->table('link');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'link_name');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'priority', 'link_priority');
		$db->query($ci->compile());
		
		$db->query('DROP TABLE IF EXISTS {link_meta}');
		$ct->table('link_meta');
		$ct->field('link_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('data', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->primaryKey('link_id, name');
		$ct->foreignKey('link_id', 'link', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());
		
		$db->query('DROP TABLE IF EXISTS {link_category}');
		$ct->table('link_category');
		$ct->field('link_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('category_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->primaryKey('link_id, category_id');
		$ct->foreignKey('link_id', 'link', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$ct->foreignKey('category_id', 'category', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {nonce}');
		$ct->table('nonce');
		$ct->field('nonce', iSparkDBQueryFunctionCreateTable::kFieldTypeString, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey);
		$ct->field('used', iSparkDBQueryFunctionCreateTable::kFieldTypeBoolean, NULL, false);
		$ct->field('expires', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {cache}');
		$ct->table('cache');
		$ct->field('namespace', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 63);
		$ct->field('ckey', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('val', iSparkDBQueryFunctionCreateTable::kFieldTypeBinary, 16*1024*1024-1);
		$ct->field('expires', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->primaryKey('namespace, ckey');
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {plugin}');
		$ct->table('plugin');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoIncrement);
		$ct->field('family', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('extends', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('load_order', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('runs_where', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('auto_load', iSparkDBQueryFunctionCreateTable::kFieldTypeBoolean, NULL, false);
		$ct->field('state', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, 0);
		$ct->field('version', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('feed', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('code', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$db->query($ct->compile());

		$ci->table('plugin');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'family', 'plugin_family');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'plugin_name');
		$db->query($ci->compile());
	}

	//---------------------------------------------------------------------------

	public function init($params = NULL)
	{
		$db = $this->loadDB($params);
		
		// install system user (to satisfy foreign key constraints)

		$db->insertRows('user',
			array
			(
				array
				(
					'id' => 0,
					'email' => '',
					'login' => '',
					'password' => '',
					'name' => 'system',
				)
			)
		);

		// MySQL will insert the row with id=1, regardless of the explicit 0 value
		// we pass in. The following shenanigans correct for this behavior.
		
		$db->updateRows('user', array('id'=>0));
		$db->query($db->getFunction('resetAutoIncrement')->table('user')->reset(1)->compile());
	
		$db->insertRows('pref',
			array
			(
				array
				(
					'name' => 'version',
					'group_name' => 'system',
					'section_name' => 'version',
					'type' => 'hidden',
					'val' => EscherVersion::CoreVersion,
				),
				array
				(
					'name' => 'schema',
					'group_name' => 'system',
					'section_name' => 'version',
					'type' => 'hidden',
					'val' => EscherVersion::SchemaVersion,
				),
				array
				(
					'name' => 'last_update_version',
					'group_name' => 'system',
					'section_name' => 'updates',
					'position' => 0,
					'type' => 'hidden',
					'validation' => '',
					'val' => EscherVersion::CoreVersion,
				),
				array
				(
					'name' => 'site_url',
					'group_name' => 'basic',
					'section_name' => '0site_info',
					'position' => 10,
					'type' => 'url',
					'validation' => '',
					'val' => isset($params['site_url']) ? $params['site_url'] : '',
				),
				array
				(
					'name' => 'site_name',
					'group_name' => 'basic',
					'section_name' => '0site_info',
					'position' => 20,
					'type' => 'text',
					'validation' => '',
					'val' => isset($params['site_name']) ? $params['site_name'] : '',
				),
				array
				(
					'name' => 'secure_site_url',
					'group_name' => 'security',
					'section_name' => 'site_security',
					'position' => 10,
					'type' => 'url',
					'validation' => '',
					'val' => isset($params['secure_site_url']) ? $params['secure_site_url'] : '',
				),
			)
		);

		$db->insertRows('role',
			array
			(
				array('id' => '1', 'name' => 'Administrator'),
			)
		);
	}

	//---------------------------------------------------------------------------

	public function installSite($fileName, $parentFileName = NULL, $preserveSiteName = true)
	{
		$userModel = $this->newModel('User');
		$saveAdminUser = $userModel->fetchUser(1, true);
		
		$prefModel = $this->newModel('Preferences');
		$savePrefs = array();

		$savePrefNames = array('site_url', 'secure_site_url');
		if ($preserveSiteName)
		{
			$savePrefNames[] = 'site_name';
		}
		foreach ($prefModel->fetchPrefs() as $pref)
		{
			if (in_array($pref['name'], $savePrefNames, true))
			{
				$savePrefs[] = $pref;
			}
		}

		if ($parentFileName)
		{
			$this->install1Site($parentFileName);
		}
		
		$this->install1Site($fileName);
				
		// restore install-time admin user and prefs
		
		$userModel->updateUser($saveAdminUser);
		$prefModel->updatePrefs($savePrefs);
		
		$prefModel->addPrefs(
			array
			(
				array
				(
					'name' => 'version',
					'group_name' => 'system',
					'section_name' => 'version',
					'type' => 'hidden',
					'val' => EscherVersion::CoreVersion,
				),
				array
				(
					'name' => 'schema',
					'group_name' => 'system',
					'section_name' => 'version',
					'type' => 'hidden',
					'val' => EscherVersion::SchemaVersion,
				),
				array
				(
					'name' => 'last_update_version',
					'group_name' => 'system',
					'section_name' => 'updates',
					'position' => 0,
					'type' => 'hidden',
					'validation' => '',
					'val' => EscherVersion::CoreVersion,
				),
			)
		);
	}
	
	//---------------------------------------------------------------------------

	public function installed()
	{
		try
		{
			$db = $this->loadDBWithPerm(EscherModel::PermRead);

			if (!$row = $db->selectRow('pref', 'val', 'name="schema"'))
			{
				return false;
			}

			return ($row['val'] > 0);
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	//---------------------------------------------------------------------------

	public function adminUserExists()
	{
		try
		{
			$db = $this->loadDBWithPerm(EscherModel::PermRead);

			if (!$row = $db->selectRow('user', 'id', 'id=1'))
			{
				return false;
			}

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	//---------------------------------------------------------------------------

	public function installDefaultSite()
	{
		// default site requires the comment plugin to be pre-installed
		
		if ($model = $this->factory->manufacture('CommentModel'))
		{
			$model->install();
		}
		
		$this->installSite('default.xml', 'empty.xml');
	}
	
	//---------------------------------------------------------------------------

	public function installDemoSite()
	{
		$this->installSite('creamery.xml', 'empty.xml', false);
	}
	
	//---------------------------------------------------------------------------

	public function installWelcomeSite()
	{
		$this->installSite('welcome.xml', 'empty.xml');
	}
	
	//---------------------------------------------------------------------------

	public function installEmptySite()
	{
		$this->installSite('empty.xml');
	}
	
	//---------------------------------------------------------------------------

	private function install1Site($fileName)
	{
		$siteDir = $this->config->get('app_dir') . '/sites';
		$file = "{$siteDir}/{$fileName}";

		if (($xml = file_get_contents($file)) === false)
		{
			throw new SparkHTTPException_NotFound(NULL, array('reason'=>'site xml file not found'));
		}
		
		$xmlModel = $this->newModel('XMLImportExport');
		$xmlModel->fromXML($xml, self::$_xml_params);
	}
	
	//---------------------------------------------------------------------------
	
}
