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

class _EscherSchemaModel extends EscherModel
{
	public function create($params = NULL)
	{
		$db = $this->loadDB($params);

		$ct = $db->getFunction('create_table');
		$ci = $db->getFunction('create_index');

		$db->query('DROP TABLE IF EXISTS {perm}');
		$ct->table('perm');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('group_name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$db->query($ct->compile());

		$ci->table('perm');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'perm_name');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {role}');
		$ct->table('role');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
		$ct->field('name', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$db->query($ct->compile());

		$ci->table('role');
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'name', 'role_name');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {user}');
		$ct->table('user');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
		$ct->field('email', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('login', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('password', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('nonce', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 64, '1');
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
		$ct->field('slug', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('title', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
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

		$db->query('DROP TABLE IF EXISTS {template}');
		$ct->table('template');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'position, parent_id', 'category_position_parent');
		$db->query($ci->compile());
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeNormal, 'priority', 'category_priority');
		$db->query($ci->compile());

		$db->query('DROP TABLE IF EXISTS {model}');
		$ct->table('model');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ci->index(iSparkDBQueryFunctionCreateIndex::kIndexTypeUnique, 'position, parent_id', 'page_position_parent');
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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
		$ct->field('namespace', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('ckey', iSparkDBQueryFunctionCreateTable::kFieldTypeString);
		$ct->field('val', iSparkDBQueryFunctionCreateTable::kFieldTypeBinary, 16*1024*1024-1);
		$ct->field('expires', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->primaryKey('namespace, ckey');
		$db->query($ct->compile());

		$db->query('DROP TABLE IF EXISTS {plugin}');
		$ct->table('plugin');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
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

		$db->insertRows('user', array
		(
			array
			(
				'id' => 0,
				'email' => '',
				'login' => '',
				'password' => '',
				'name' => 'system',
			)
		));

		// MySQL will insert the row with id=1, regardless of the explicit 0 value
		// we pass in. The following shenanigans correct for this behavior.
		
		$db->updateRows('user', array('id'=>0));
		$db->query($db->getFunction('resetAutoIncrement')->table('user')->reset(1)->compile());

		// install default preferences

		$db->insertRows('pref', array
		(
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
				'name' => 'site_slogan',
				'group_name' => 'basic',
				'section_name' => '0site_info',
				'position' => 30,
				'type' => 'text',
				'validation' => '',
				'val' => 'Your Site Slogan Here',
			),
			array
			(
				'name' => 'site_time_zone',
				'group_name' => 'basic',
				'section_name' => '0site_info',
				'position' => 40,
				'type' => 'timezone',
				'validation' => '',
				'val' => 'UTC',
			),
			array
			(
				'name' => 'site_maintenance_page',
				'group_name' => 'basic',
				'section_name' => '0site_info',
				'position' => 50,
				'type' => 'url',
				'validation' => 'optional',
				'val' => '',
			),
			array
			(
				'name' => 'production_status',
				'group_name' => 'basic',
				'section_name' => '0site_info',
				'position' => 60,
				'type' => 'select',
				'data' => serialize(array(EscherProductionStatus::Maintenance=>'Maintenance', EscherProductionStatus::Development=>'Development', EscherProductionStatus::Staging=>'Staging', EscherProductionStatus::Production=>'Production')),
				'validation' => '',
				'val' => EscherProductionStatus::Production,
			),
			array
			(
				'name' => 'debug_level',
				'group_name' => 'basic',
				'section_name' => '0site_info',
				'position' => 70,
				'type' => 'select',
				'data' => serialize(array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7', 8=>'8', 9=>'9')),
				'validation' => '',
				'val' => 0,
			),


			array
			(
				'name' => 'category_trigger',
				'group_name' => 'basic',
				'section_name' => '1url_handling',
				'position' => 10,
				'type' => 'slug',
				'validation' => '',
				'val' => 'category',
			),
			array
			(
				'name' => 'permlink_titles',
				'group_name' => 'basic',
				'section_name' => '1url_handling',
				'position' => 20,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => true,
			),


			array
			(
				'name' => 'content_image_path',
				'group_name' => 'basic',
				'section_name' => '2Content',
				'position' => 10,
				'type' => 'path',
				'validation' => '',
				'val' => '/img',
			),
			array
			(
				'name' => 'content_file_path',
				'group_name' => 'basic',
				'section_name' => '2Content',
				'position' => 20,
				'type' => 'path',
				'validation' => '',
				'val' => '/files',
			),
			array
			(
				'name' => 'max_upload_size',
				'group_name' => 'basic',
				'section_name' => '2Content',
				'position' => 30,
				'type' => 'integer',
				'validation' => '[0, 104857600]',
				'val' => '102400000',
			),


			array
			(
				'name' => 'theme',
				'group_name' => 'basic',
				'section_name' => 'design',
				'position' => 10,
				'type' => 'theme',
				'validation' => '',
				'val' => '0',
			),
			array
			(
				'name' => 'theme_path',
				'group_name' => 'basic',
				'section_name' => 'design',
				'position' => 20,
				'type' => 'path',
				'validation' => '',
				'val' => '/themes',
			),
			array
			(
				'name' => 'style_path',
				'group_name' => 'basic',
				'section_name' => 'design',
				'position' => 30,
				'type' => 'path',
				'validation' => '',
				'val' => '/styles',
			),
			array
			(
				'name' => 'script_path',
				'group_name' => 'basic',
				'section_name' => 'design',
				'position' => 40,
				'type' => 'path',
				'validation' => '',
				'val' => '/scripts',
			),
			array
			(
				'name' => 'image_path',
				'group_name' => 'basic',
				'section_name' => 'design',
				'position' => 50,
				'type' => 'path',
				'validation' => '',
				'val' => '/images',
			),


			array
			(
				'name' => 'parsing_in_blocks',
				'group_name' => 'expert',
				'section_name' => 'parser',
				'position' => 10,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'parsing_in_parts',
				'group_name' => 'expert',
				'section_name' => 'parser',
				'position' => 20,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),


			array
			(
				'name' => 'working_branch',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 5,
				'type' => 'select',
				'data' => serialize(array(EscherProductionStatus::Development=>'Development', EscherProductionStatus::Staging=>'Staging', EscherProductionStatus::Production=>'Production')),
				'validation' => '',
				'val' => EscherProductionStatus::Production,
			),
			array
			(
				'name' => 'development_draft_as_published',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 10,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => 'true',
			),
			array
			(
				'name' => 'development_branch_auto_routing',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 20,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'development_branch_host_prefix',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 30,
				'type' => 'text',
				'validation' => '',
				'val' => 'dev',
			),
			array
			(
				'name' => 'development_debug_level',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 40,
				'type' => 'select',
				'data' => serialize(array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7', 8=>'8', 9=>'9')),
				'validation' => '',
				'val' => 9,
			),
			array
			(
				'name' => 'development_theme',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 50,
				'type' => 'theme',
				'validation' => '',
				'val' => '0',
			),
			array
			(
				'name' => 'staging_draft_as_published',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 110,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => 'true',
			),
			array
			(
				'name' => 'staging_branch_auto_routing',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 120,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'staging_branch_host_prefix',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 130,
				'type' => 'text',
				'validation' => '',
				'val' => 'staging',
			),
			array
			(
				'name' => 'staging_debug_level',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 140,
				'type' => 'select',
				'data' => serialize(array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5', 6=>'6', 7=>'7', 8=>'8', 9=>'9')),
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'staging_theme',
				'group_name' => 'expert',
				'section_name' => 'branches',
				'position' => 150,
				'type' => 'theme',
				'validation' => '',
				'val' => '0',
			),


			array
			(
				'name' => 'plug_cache_flush',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'plug_cache_flush_staging',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'plug_cache_flush_dev',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'partial_cache_flush',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'partial_cache_flush_staging',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'partial_cache_flush_dev',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'page_cache_flush',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'page_cache_flush_staging',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),
			array
			(
				'name' => 'page_cache_flush_dev',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 0,
				'type' => 'hidden',
				'validation' => '',
				'val' => 0,
			),

			array
			(
				'name' => 'partial_cache_active',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 10,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'partial_cache_ttl',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 20,
				'type' => 'integer',
				'validation' => '',
				'val' => '600',
			),
			array
			(
				'name' => 'page_cache_active',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 30,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'page_cache_ttl',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 40,
				'type' => 'integer',
				'validation' => '',
				'val' => '600',
			),
			array
			(
				'name' => 'auto_versioned_styles',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 110,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'auto_versioned_scripts',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 120,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'auto_versioned_images',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 130,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'auto_versioned_files',
				'group_name' => 'performance',
				'section_name' => 'cache',
				'position' => 140,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),


			array
			(
				'name' => 'require_secure_login',
				'group_name' => 'security',
				'section_name' => 'admin_security',
				'position' => 10,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
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
			array
			(
				'name' => 'enforce_page_security',
				'group_name' => 'security',
				'section_name' => 'site_security',
				'position' => 20,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
			),
			array
			(
				'name' => 'automatic_redirect',
				'group_name' => 'security',
				'section_name' => 'site_security',
				'position' => 30,
				'type' => 'yesnoradio',
				'validation' => '',
				'val' => false,
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
				'name' => 'last_update_check',
				'group_name' => 'system',
				'section_name' => 'updates',
				'position' => 10,
				'type' => 'text',
				'att' => 'disabled="disabled"',
				'validation' => '',
				'val' => self::now(),
			),
			array
			(
				'name' => 'check_for_updates',
				'group_name' => 'system',
				'section_name' => 'updates',
				'position' => 20,
				'type' => 'select',
				'data' => serialize(array(0=>'Never', 1=>'Daily', 7=>'Weekly')),
				'validation' => '',
				'val' => 7,
			),
		));
		
		$db->insertRows('perm', array
		(
			array
			(
				'group_name' => 'content',
				'name' => 'content',
			),
				array
				(
					'group_name' => 'content',
					'name' => 'content:categories',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:categories:add',
					),
					array
					(
						'group_name' => 'content',
						'name' => 'content:categories:delete',
					),
					array
					(
						'group_name' => 'content',
						'name' => 'content:categories:edit',
					),
				array
				(
					'group_name' => 'content',
					'name' => 'content:models',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:models:add',
					),
					array
					(
						'group_name' => 'content',
						'name' => 'content:models:delete',
					),
					array
					(
						'group_name' => 'content',
						'name' => 'content:models:edit',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:meta',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:meta:add',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:meta:delete',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:meta:change',
							),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:categories',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:categories:add',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:categories:delete',
							),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:parts',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:parts:add',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:parts:delete',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:models:edit:parts:change',
							),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:template',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:pagetype',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:status',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:magic',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:cacheable',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:models:edit:secure',
						),
				array
				(
					'group_name' => 'content',
					'name' => 'content:pages',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:pages:add',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:pages:add:own',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:pages:add:any',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:pages:delete',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:pages:delete:own',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:pages:delete:any',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:pages:edit',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:pages:edit:own',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:meta',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:meta:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:meta:delete',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:meta:change',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:categories:delete',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:parts',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:parts:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:parts:delete',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:own:parts:change',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:template',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:pagetype',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:status',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:magic',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:cacheable',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:own:secure',
							),
						array
						(
							'group_name' => 'content',
							'name' => 'content:pages:edit:any',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:meta',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:meta:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:meta:delete',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:meta:change',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:categories:delete',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:parts',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:parts:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:parts:delete',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:pages:edit:any:parts:change',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:template',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:pagetype',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:status',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:magic',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:cacheable',
							),
							array
							(
								'group_name' => 'content',
								'name' => 'content:pages:edit:any:secure',
							),
				array
				(
					'group_name' => 'content',
					'name' => 'content:blocks',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:blocks:add',
					),
					array
					(
						'group_name' => 'content',
						'name' => 'content:blocks:delete',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:blocks:delete:own',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:blocks:delete:any',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:blocks:edit',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:blocks:edit:own',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:blocks:edit:own:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:blocks:edit:own:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:blocks:edit:own:categories:delete',
								),
						array
						(
							'group_name' => 'content',
							'name' => 'content:blocks:edit:any',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:blocks:edit:any:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:blocks:edit:any:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:blocks:edit:any:categories:delete',
								),
				array
				(
					'group_name' => 'content',
					'name' => 'content:images',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:images:add',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:images:add:upload',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:images:delete',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:images:delete:own',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:images:delete:any',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:images:edit',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:images:edit:own',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:images:edit:own:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:images:edit:own:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:images:edit:own:categories:delete',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:images:edit:own:replace',
							),
						array
						(
							'group_name' => 'content',
							'name' => 'content:images:edit:any',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:images:edit:any:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:images:edit:any:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:images:edit:any:categories:delete',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:images:edit:any:replace',
							),
					array
					(
						'group_name' => 'content',
						'name' => 'content:images:display',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:images:display:own',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:images:display:any',
						),
				array
				(
					'group_name' => 'content',
					'name' => 'content:files',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:files:add',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:files:add:upload',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:files:delete',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:files:delete:own',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:files:delete:any',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:files:edit',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:files:edit:own',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:files:edit:own:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:files:edit:own:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:files:edit:own:categories:delete',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:files:edit:own:replace',
							),
						array
						(
							'group_name' => 'content',
							'name' => 'content:files:edit:any',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:files:edit:any:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:files:edit:any:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:files:edit:any:categories:delete',
								),
							array
							(
								'group_name' => 'content',
								'name' => 'content:files:edit:any:replace',
							),
				array
				(
					'group_name' => 'content',
					'name' => 'content:links',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:links:add',
					),
					array
					(
						'group_name' => 'content',
						'name' => 'content:links:delete',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:links:delete:own',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:links:delete:any',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:links:edit',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:links:edit:own',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:links:edit:own:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:links:edit:own:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:links:edit:own:categories:delete',
								),
						array
						(
							'group_name' => 'content',
							'name' => 'content:links:edit:any',
						),
							array
							(
								'group_name' => 'content',
								'name' => 'content:links:edit:any:categories',
							),
								array
								(
									'group_name' => 'content',
									'name' => 'content:links:edit:any:categories:add',
								),
								array
								(
									'group_name' => 'content',
									'name' => 'content:links:edit:any:categories:delete',
								),
			array
			(
				'group_name' => 'design',
				'name' => 'design',
			),
				array
				(
					'group_name' => 'design',
					'name' => 'design:branches',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:branches:edit',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:branches:push',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:branches:rollback',
					),
				array
				(
					'group_name' => 'design',
					'name' => 'design:themes',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:themes:add',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:themes:delete',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:themes:delete:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:themes:delete:any',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:themes:edit',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:themes:edit:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:themes:edit:any',
						),
				array
				(
					'group_name' => 'design',
					'name' => 'design:templates',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:templates:add',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:templates:delete',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:templates:delete:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:templates:delete:any',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:templates:edit',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:templates:edit:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:templates:edit:any',
						),
				array
				(
					'group_name' => 'design',
					'name' => 'design:snippets',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:snippets:add',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:snippets:delete',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:snippets:delete:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:snippets:delete:any',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:snippets:edit',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:snippets:edit:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:snippets:edit:any',
						),
				array
				(
					'group_name' => 'design',
					'name' => 'design:tags',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:tags:add',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:tags:delete',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:tags:delete:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:tags:delete:any',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:tags:edit',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:tags:edit:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:tags:edit:any',
						),
				array
				(
					'group_name' => 'design',
					'name' => 'design:styles',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:styles:add',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:styles:delete',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:styles:delete:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:styles:delete:any',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:styles:edit',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:styles:edit:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:styles:edit:any',
						),
				array
				(
					'group_name' => 'design',
					'name' => 'design:scripts',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:scripts:add',
					),
					array
					(
						'group_name' => 'design',
						'name' => 'design:scripts:delete',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:scripts:delete:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:scripts:delete:any',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:scripts:edit',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:scripts:edit:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:scripts:edit:any',
						),
				array
				(
					'group_name' => 'design',
					'name' => 'design:images',
				),
					array
					(
						'group_name' => 'design',
						'name' => 'design:images:add',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:images:add:upload',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:images:delete',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:images:delete:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:images:delete:any',
						),
					array
					(
						'group_name' => 'design',
						'name' => 'design:images:edit',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:images:edit:own',
						),
							array
							(
								'group_name' => 'design',
								'name' => 'design:images:edit:own:replace',
							),
						array
						(
							'group_name' => 'design',
							'name' => 'design:images:edit:any',
						),
							array
							(
								'group_name' => 'design',
								'name' => 'design:images:edit:any:replace',
							),
					array
					(
						'group_name' => 'design',
						'name' => 'design:images:display',
					),
						array
						(
							'group_name' => 'design',
							'name' => 'design:images:display:own',
						),
						array
						(
							'group_name' => 'design',
							'name' => 'design:images:display:any',
						),
			array
			(
				'group_name' => 'settings',
				'name' => 'settings',
			),
				array
				(
					'group_name' => 'settings',
					'name' => 'settings:preferences',
				),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:preferences:basic',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:preferences:expert',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:preferences:performance',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:preferences:plugins',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:preferences:security',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:preferences:system',
					),
				array
				(
					'group_name' => 'settings',
					'name' => 'settings:roles',
				),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:roles:add',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:roles:delete',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:roles:edit',
					),
				array
				(
					'group_name' => 'settings',
					'name' => 'settings:users',
				),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:users:add',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:users:delete',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:users:edit',
					),
				array
				(
					'group_name' => 'settings',
					'name' => 'settings:plugins',
				),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:plugins:add',
					),
					array
					(
						'group_name' => 'settings',
						'name' => 'settings:plugins:delete',
					),
				array
				(
					'group_name' => 'settings',
					'name' => 'settings:upgrade',
				),
		));

		$db->insertRows('role', array
		(
			array('id' => '1', 'name' => 'Administrator'),
			array('id' => '2', 'name' => 'Publisher'),
			array('id' => '3', 'name' => 'Editor'),
			array('id' => '4', 'name' => 'Designer'),
			array('id' => '5', 'name' => 'Contributor'),
		));

		foreach (array(array(2,1),array(2,2),array(2,3),array(2,4),array(2,5),array(2,6),array(2,7),array(2,8),array(2,9),array(2,10),array(2,11),array(2,12),array(2,13),array(2,14),array(2,15),array(2,16),array(2,17),array(2,18),array(2,19),array(2,20),array(2,21),array(2,22),array(2,23),array(2,24),array(2,25),array(2,26),array(2,27),array(2,28),array(2,29),array(2,30),array(2,31),array(2,32),array(2,33),array(2,34),array(2,35),array(2,36),array(2,37),array(2,38),array(2,39),array(2,40),array(2,41),array(2,42),array(2,43),array(2,44),array(2,45),array(2,46),array(2,47),array(2,48),array(2,49),array(2,50),array(2,51),array(2,52),array(2,53),array(2,54),array(2,55),array(2,56),array(2,57),array(2,58),array(2,59),array(2,60),array(2,61),array(2,62),array(2,63),array(2,64),array(2,65),array(2,66),array(2,67),array(2,68),array(2,69),array(2,70),array(2,71),array(2,72),array(2,73),array(2,74),array(2,75),array(2,76),array(2,77),array(2,78),array(2,79),array(2,80),array(2,81),array(2,82),array(2,83),array(2,84),array(2,85),array(2,86),array(2,87),array(2,88),array(2,89),array(2,90),array(2,91),array(2,92),array(2,93),array(2,94),array(2,95),array(2,96),array(2,97),array(2,98),array(2,99),array(2,100),array(2,101),array(2,102),array(2,103),array(2,104),array(2,105),array(2,106),array(2,107),array(2,108),array(2,109),array(2,110),array(2,111),array(2,112),array(2,113),array(2,114),array(2,115),array(2,116),array(2,117),array(2,118),array(2,119),array(2,120),array(2,121),array(2,122),array(2,123),array(2,124),array(2,125),array(2,126),array(2,127),array(2,128),array(2,129),array(2,130),array(2,131),array(2,132),array(2,133),array(2,134),array(2,135),array(2,136),array(2,137),array(2,138),array(2,139),array(2,140),array(2,141),array(2,142),array(2,143),array(2,144),array(2,145),array(2,146),array(2,147),array(2,148),array(2,149),array(2,150),array(2,151),array(2,152),array(2,153),array(2,154),array(2,155),array(2,156),array(2,157),array(2,158),array(2,159),array(2,160),array(2,161),array(2,162),array(2,163),array(2,164),array(2,165),array(2,166),array(2,167),array(2,168),array(2,169),array(2,170),array(2,171),array(2,172),array(2,173),array(2,174),array(2,175),array(2,176),array(2,177),array(2,178),array(2,179),array(2,180),array(2,181),array(2,182),array(2,183),array(2,184),array(2,185),array(2,186),array(2,187),array(2,188),array(2,189),array(2,190),array(2,191),array(2,192),array(2,193),array(2,194),array(2,195),array(2,196),array(2,197),array(2,198),array(2,199),array(2,200),array(2,201),array(2,202),array(2,203),array(2,204),array(2,205),array(2,206),array(2,207),array(2,219),array(2,220),array(2,221),array(3,1),array(3,2),array(3,3),array(3,4),array(3,5),array(3,6),array(3,7),array(3,8),array(3,9),array(3,10),array(3,11),array(3,12),array(3,13),array(3,14),array(3,15),array(3,16),array(3,17),array(3,18),array(3,19),array(3,20),array(3,21),array(3,22),array(3,23),array(3,24),array(3,25),array(3,26),array(3,27),array(3,28),array(3,29),array(3,30),array(3,31),array(3,32),array(3,33),array(3,34),array(3,35),array(3,36),array(3,37),array(3,38),array(3,39),array(3,40),array(3,41),array(3,42),array(3,43),array(3,44),array(3,45),array(3,46),array(3,47),array(3,48),array(3,49),array(3,50),array(3,51),array(3,52),array(3,53),array(3,54),array(3,55),array(3,56),array(3,57),array(3,58),array(3,59),array(3,60),array(3,61),array(3,62),array(3,63),array(3,64),array(3,65),array(3,66),array(3,67),array(3,68),array(3,69),array(3,70),array(3,71),array(3,72),array(3,73),array(3,74),array(3,75),array(3,76),array(3,77),array(3,78),array(3,79),array(3,80),array(3,81),array(3,82),array(3,83),array(3,84),array(3,85),array(3,86),array(3,87),array(3,88),array(3,89),array(3,90),array(3,91),array(3,92),array(3,93),array(3,94),array(3,95),array(3,96),array(3,97),array(3,98),array(3,99),array(3,100),array(3,101),array(3,102),array(3,103),array(3,104),array(3,105),array(3,106),array(3,107),array(3,108),array(3,109),array(3,110),array(3,111),array(3,112),array(3,113),array(3,114),array(3,115),array(3,116),array(3,117),array(3,118),array(3,119),array(3,120),array(3,121),array(3,122),array(3,123),array(3,124),array(3,125),array(3,126),array(3,127),array(3,128),array(3,129),array(3,130),array(3,131),array(3,132),array(3,133),array(3,134),array(3,135),array(3,136),array(3,140),array(3,148),array(3,156),array(3,164),array(3,172),array(3,180),array(3,188),array(3,199),array(3,200),array(3,201),array(3,219),array(3,220),array(3,221),array(4,1),array(4,27),array(4,28),array(4,29),array(4,31),array(4,32),array(4,34),array(4,35),array(4,36),array(4,37),array(4,38),array(4,39),array(4,40),array(4,41),array(4,42),array(4,43),array(4,44),array(4,45),array(4,46),array(4,47),array(4,48),array(4,49),array(4,50),array(4,51),array(4,52),array(4,71),array(4,72),array(4,73),array(4,74),array(4,76),array(4,77),array(4,78),array(4,79),array(4,80),array(4,85),array(4,86),array(4,87),array(4,88),array(4,89),array(4,91),array(4,92),array(4,93),array(4,94),array(4,95),array(4,96),array(4,102),array(4,103),array(4,104),array(4,122),array(4,123),array(4,124),array(4,125),array(4,127),array(4,128),array(4,129),array(4,130),array(4,131),array(4,136),array(4,137),array(4,138),array(4,139),array(4,140),array(4,141),array(4,142),array(4,143),array(4,145),array(4,146),array(4,148),array(4,149),array(4,150),array(4,151),array(4,153),array(4,154),array(4,156),array(4,157),array(4,158),array(4,159),array(4,161),array(4,162),array(4,164),array(4,165),array(4,166),array(4,167),array(4,169),array(4,170),array(4,172),array(4,173),array(4,174),array(4,175),array(4,177),array(4,178),array(4,180),array(4,181),array(4,182),array(4,183),array(4,185),array(4,186),array(4,188),array(4,189),array(4,190),array(4,191),array(4,192),array(4,194),array(4,195),array(4,196),array(4,199),array(4,200),array(4,201),array(5,1),array(5,2),array(5,3),array(5,27),array(5,28),array(5,29),array(5,31),array(5,32),array(5,34),array(5,35),array(5,36),array(5,37),array(5,38),array(5,39),array(5,40),array(5,41),array(5,42),array(5,43),array(5,44),array(5,45),array(5,46),array(5,47),array(5,48),array(5,49),array(5,50),array(5,51),array(5,52),array(5,71),array(5,72),array(5,73),array(5,74),array(5,76),array(5,77),array(5,78),array(5,79),array(5,80),array(5,85),array(5,86),array(5,87),array(5,88),array(5,89),array(5,91),array(5,92),array(5,93),array(5,94),array(5,95),array(5,96),array(5,102),array(5,103),array(5,104),array(5,105),array(5,106),array(5,107),array(5,108),array(5,109),array(5,111),array(5,112),array(5,113),array(5,114),array(5,115),array(5,116),array(5,122),array(5,123),array(5,124),array(5,125),array(5,127),array(5,128),array(5,129),array(5,130),array(5,131)) as $row)
		{
			$db->insertRow('role_perm', array('role_id'=>$row[0], 'perm_id'=>$row[1]));
		}
		
	}

	//---------------------------------------------------------------------------

	public function installed()
	{
		try
		{
			$db = $this->loadDBWithPerm();

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
			$db = $this->loadDBWithPerm();

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

	public function installWelcomePage()
	{
		$content = $this->newModel('AdminContent');

		$templateBody = <<< EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<et:meta:description />
<et:meta:keywords />
<et:meta:distribution />
<et:meta:author />
<et:meta:robots />
<title><et:title /></title>
</head>
<body>
</et:content />
</body>
</html>
EOD;

		$pageBody = <<< EOD
<h1>Welcome to Escher!</h1>

EOD;

		$content->addTemplate
		(
			$this->factory->manufacture
			(
				'Template', array
				(
					'name'=>'default', 'ctype'=>'text/html', 'content'=>$templateBody,
					'author_id'=>1, 'editor_id'=>1,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);

		$content->addPage
		(
			$this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'', 'title'=>'Welcome to Escher!', 'breadcrumb'=>'Welcome', 'status'=>_Page::Status_published,
					'level'=>0, 'cacheable'=>_Page::Cacheable_yes, 'secure'=>_Page::Secure_no,
					'parent_id'=>0, 'author_id'=>1, 'editor_id'=>1,
					'template_name'=>'default',
				)
			)
		);

		$content->addPagePart
		(
			$this->factory->manufacture
			(
				'Part', array
				(
					'page_id'=>1, 'name'=>'body', 'filter_id'=>0,
					'content'=>$pageBody,
					'content_html'=>$pageBody,
				)
			)
		);
	}
	
	//---------------------------------------------------------------------------

	public function installExampleSite()
	{
		// add admin elements

		$userModel = $this->newModel('User');
		$authModel = $this->factory->manufacture('SparkAuthModel');
		$password = $authModel->encryptPassword('password');
		
		$userModel->addUser
		(
			$userModel->factory->manufacture
			(
				'User', array
				(
					'name'=>'John Q Publisher', 'email'=>'publisher@example.com',
					'login'=>'publisher', 'password'=>$password,
					'roles'=>array($this->factory->manufacture('Role', array('id'=>2))),
				)
			)
		);
		$userModel->addUser
		(
			$userModel->factory->manufacture
			(
				'User', array
				(
					'name'=>'John Q Editor', 'email'=>'editor@example.com',
					'login'=>'editor', 'password'=>$password,
					'roles'=>array($this->factory->manufacture('Role', array('id'=>3))),
				)
			)
		);
		$userModel->addUser
		(
			$userModel->factory->manufacture
			(
				'User', array
				(
					'name'=>'John Q Designer', 'email'=>'designer@example.com',
					'login'=>'designer', 'password'=>$password,
					'roles'=>array($this->factory->manufacture('Role', array('id'=>4))),
				)
			)
		);
		$userModel->addUser
		(
			$userModel->factory->manufacture
			(
				'User', array
				(
					'name'=>'John Q Contributor', 'email'=>'contributor@example.com',
					'login'=>'contributor', 'password'=>$password,
					'roles'=>array($this->factory->manufacture('Role', array('id'=>5))),
				)
			)
		);

		$prefs = $this->newModel('Preferences');
		$prefs->updatePrefs
		(
			array
			(
				array
				(
					'name' => 'site_name',
					'val' => 'Crystal Creamery',
				),
				array
				(
					'name' => 'site_slogan',
					'val' => 'Ice Creamy Goodness',
				),
				array
				(
					'name' => 'theme_path',
					'val' => '',
				),
			)
		);
		
		$this->installSite($this->config->get('app_dir') . '/sites/creamery');
	}
	
	//---------------------------------------------------------------------------

	public function installSite($siteDir)
	{
		$model = $this->newModel('AdminContent');

		// add design elements (images, scripts, styles, snippets, templates)

		$model->installTheme(1, "{$siteDir}/design", 1, '', 0, false);

		// add content elements (categories, images, blocks, pages)
		
		foreach (array('Navigation', 'Sidebar Home', 'Sidebar Tour', 'Tour', 'Free Images') as $title)
		{
			$category = $this->factory->manufacture
			(
				'Category', array
				(
					'slug'=>$title, 'title'=>$title, 
					'level'=>0, 'parent_id'=>0,
				)
			);
			$category->makeSlug();
			$model->addCategory($category);
		}

		$model->addLink
		(
			$this->factory->manufacture
			(
				'Link', array
				(
					'name'=>'escher-cms', 'title'=>'Escher CMS', 'url'=>'http://eschercms.org',
					'author_id'=>1,
				)
			)
		);
		$model->addLink
		(
			$this->factory->manufacture
			(
				'Link', array
				(
					'name'=>'michal-marcol', 'title'=>'Michal Marcol', 'url'=>'http://www.freedigitalphotos.net/images/view_photog.php?photogid=371',
					'author_id'=>1, 'categories'=>'5', 
				)
			)
		);
		$model->addLink
		(
			$this->factory->manufacture
			(
				'Link', array
				(
					'name'=>'simon-howden', 'title'=>'Simon Howden', 'url'=>'http://www.freedigitalphotos.net/images/view_photog.php?photogid=404',
					'author_id'=>1, 'categories'=>'5', 
				)
			)
		);
		$model->addLink
		(
			$this->factory->manufacture
			(
				'Link', array
				(
					'name'=>'suat-eman', 'title'=>'Suat Eman', 'url'=>'http://www.freedigitalphotos.net/images/view_photog.php?photogid=151',
					'author_id'=>1, 'categories'=>'5', 
				)
			)
		);
		$model->addLink
		(
			$this->factory->manufacture
			(
				'Link', array
				(
					'name'=>'spurrd', 'title'=>'Paul du Coudray', 'url'=>'http://www.spurrd.com/',
					'author_id'=>1,
				)
			)
		);
		
		if (file_exists("{$siteDir}/content/images"))
		{
			$path = new DirectoryIterator("{$siteDir}/content/images");
			foreach ($path as $file)
			{
				if ($file->isDot() || $file->isDir())
				{
					continue;
				}

				$fileName = $file->getFilename();
				if ($fileName[0] === '.')
				{
					continue;
				}
				
				if (($imageSize = getimagesize("{$siteDir}/content/images/{$fileName}")) === false)
				{
					continue;	// skip image on error
				}
				$width = $imageSize[0];
				$height = $imageSize[1];
				$contentType = $imageSize['mime'];
				$content = file_get_contents("{$siteDir}/content/images/{$fileName}");
				
				$image = $this->factory->manufacture
				(
					'Image', array
					(
						'slug'=>$fileName, 'ctype'=>$contentType, 'content'=>$content,
						'width'=>$width, 'height'=>$height, 'alt'=>'', 'title'=>'',
						'author_id'=>1,
						'theme_id'=>-1,
						'branch'=>1,
					)
				);
				$image->makeSlug();
				$model->addImage($image);
			}
		}

		$content = '<p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolor eos qui ration volupt sequi nesciunt. Proin varius urna. In varius bibendum nisl. Donec nec nisi ut nisi varius porttitor.</p>';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'prize', 'title'=>'Award Winning', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'2', 
				)
			)
		);

		$content = '<p>Mauris elit lacus, iaculis a, cons nec, vehicula condimentum at. Suspendisse sapien enim, tempus ut, facilisis at, vestibulum nec, nunc. Aliquam erat volutpat. Ut sit amet quam. Cum soci natoque penati et magnis dis partur montes, nascet ridiculus mus. Aen vel urna. In justo nisl, cursus in, molestie a, vulputate nec. </p>';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'green', 'title'=>'Live Green', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'2', 
				)
			)
		);

		$content = '<p>Donec tempor mollis ante. Nunc felis. Proin ac pede vel nulla lacinia congue. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi cursus, dui quis pretium blandit, erat massa lacinia neque, eu porttitor lacus erat ac leo.</p>';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'recycle', 'title'=>'We Recycle', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'2', 
				)
			)
		);

		$content = '<p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolor eos qui ration volupt sequi nesciunt. Proin varius urna. In varius bibendum nisl. Donec nec nisi ut nisi varius porttitor.</p>';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'open', 'title'=>'Always Open!', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'3', 
				)
			)
		);

		$content = '<p>Mauris elit lacus, iaculis a, cons nec, vehicula condimentum at. Suspendisse sapien enim, tempus ut, facilisis at, vestibulum nec, nunc. Aliquam erat volutpat. Ut sit amet quam. Cum soci natoque penati et magnis dis partur montes, nascet ridiculus mus. Aen vel urna. In justo nisl, cursus in, molestie a, vulputate nec. </p>';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'gift', 'title'=>'Crystal Gift Cards', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'3', 
				)
			)
		);

		$content = '<p>Iaculis a, cons nec, condi<a href="/"> vehicula</a>. Suspendisse sapien enim, tempus ut, facilisis at, vestibulum nec, nunc. Aliquam erat volutpat. Ut sit amet quam. Cum soci natoque penati et magnis dis partur montes, nascet ridiculus mus. Aen vel urna. In justo nisl, cursus in, molestie.</p>';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'truck', 'title'=>'Catering', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'3', 
				)
			)
		);

		$content = '<span>Fromus neatus farmus alwaze.</span><br /> In portor auctor neque. Morbi gravida elit non ante. Praesent eros elit, consequat et, rhoncuses atet, rhoncus posuere, massa. Praesent tempor, felis varius gravida!';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'icecream1', 'title'=>'Sundaes 4 2', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'4', 
				)
			)
		);

		$content = '<span>Bestes icecreamum inde wurlde.</span><br /> In portor auctor neque. Morbi gravida elit non ante. Praesent eros elit, consequat et, rhoncuses atet, rhoncus posuere, massa. Praesent tempor, felis varius gravida!';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'icecream2', 'title'=>'Famous Innoventions', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'4', 
				)
			)
		);

		$content = '<span>Whata treet itis tu heerit.</span><br /> In portor auctor neque. Morbi gravida elit non ante. Praesent eros elit, consequat et, rhoncuses atet, rhoncus posuere, massa. Praesent tempor, felis varius gravida!';
		$model->addBlock
		(
			$this->factory->manufacture
			(
				'Block', array
				(
					'name'=>'icecream3', 'title'=>'Strawberry Celeb', 'content'=>$content, 'content_html'=>$content,
					'filter_id'=>0, 'author_id'=>1, 'categories'=>'4', 
				)
			)
		);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'', 'title'=>'Home Page', 'breadcrumb'=>'Home', 'status'=>_Page::Status_published,
					'level'=>0, 'cacheable'=>_Page::Cacheable_yes, 'secure'=>_Page::Secure_no,
					'template_name'=>'default',
					'parent_id'=>0, 'author_id'=>1,
					'categories'=>'1',
				)
			)
		);
			$content = '<h2>Yummy!</h2><p><span>Vivamust consumey morfoamy.</span></p><p>In portor auctor neque. Morbi gravida elit non ante. Praesent eros elit, consequat et, rhoncuses atet, rhoncus posuere, massa. Praesent tempor, felis varius gravida!</p>';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>1, 'name'=>'body', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$content = 'sidebar-home';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>1, 'name'=>'sidebar_category', 'filter_id'=>0, 'type'=>'text',
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'error', 'title'=>'Error Pages', 'breadcrumb'=>'', 'status'=>_Page::Status_hidden,
					'level'=>1, 'cacheable'=>_Page::Cacheable_no, 'secure'=>_Page::Secure_no,
					'template_name'=>'error_default',
					'parent_id'=>1, 'author_id'=>1,
				)
			)
		);
			$model->addPage
			(
				$this->factory->manufacture
				(
					'Page', array
					(
						'slug'=>'default', 'title'=>'Error', 'breadcrumb'=>'', 'status'=>_Page::Status_hidden,
						'level'=>2, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
						'template_name'=>'',
						'parent_id'=>2, 'author_id'=>1,
					)
				)
			);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'PageImage', array
				(
					'slug'=>'images', 'title'=>'Images', 'breadcrumb'=>'', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
				)
			)
		);
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>4, 'name'=>'no-map', 'type'=>'checkbox', 'filter_id'=>0,
						'content'=>'1', 'content_html'=>'1',
					)
				)
			);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'PageScript', array
				(
					'slug'=>'scripts', 'title'=>'Scripts', 'breadcrumb'=>'', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
				)
			)
		);
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>5, 'name'=>'no-map', 'type'=>'checkbox', 'filter_id'=>0,
						'content'=>'1', 'content_html'=>'1',
					)
				)
			);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'PageStyle', array
				(
					'slug'=>'styles', 'title'=>'Styles', 'breadcrumb'=>'', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
				)
			)
		);
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>6, 'name'=>'no-map', 'type'=>'checkbox', 'filter_id'=>0,
						'content'=>'1', 'content_html'=>'1',
					)
				)
			);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'PageImage', array
				(
					'slug'=>'img', 'title'=>'Content Images', 'breadcrumb'=>'', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
				)
			)
		);
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>7, 'name'=>'no-map', 'type'=>'checkbox', 'filter_id'=>0,
						'content'=>'1', 'content_html'=>'1',
					)
				)
			);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'sitemap', 'title'=>'Site Map', 'breadcrumb'=>'Site Map', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>false, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
				)
			)
		);
			$content = '<h2>Site Map</h2>';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>8, 'name'=>'body', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>8, 'name'=>'no-map', 'type'=>'checkbox', 'filter_id'=>0,
						'content'=>'1', 'content_html'=>'1',
					)
				)
			);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'feed.rss', 'title'=>'RSS Feed', 'breadcrumb'=>'RSS Feed', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>false, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'rss',
					'parent_id'=>1, 'author_id'=>1,
				)
			)
		);

		$model->addPage
		(
			$this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'tour', 'title'=>'Tour', 'breadcrumb'=>'Tour', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>false, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
					'categories'=>'1',
				)
			)
		);
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>10, 'name'=>'body', 'filter_id'=>0,
						'content'=>'', 'content_html'=>'',
					)
				)
			);
			$content = 'sidebar-tour';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>10, 'name'=>'sidebar_category', 'filter_id'=>0, 'type'=>'text',
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);

		$model->addPage
		(
			$locationsPage = $this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'locations', 'title'=>'Locations', 'breadcrumb'=>'Locations', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>false, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
					'categories'=>'1',
				)
			)
		);
			$content = '<iframe width="868" height="480" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/maps?f=q&amp;source=s_q&amp;hl=en&amp;geocode=&amp;q=pioneer+square+seattle+wa&amp;sll=47.645833,-122.543441&amp;sspn=0.439931,0.476532&amp;ie=UTF8&amp;hq=&amp;hnear=Pioneer+Square,+Seattle,+Washington&amp;t=h&amp;z=14&amp;ll=47.598533,-122.333236&amp;output=embed"></iframe><br /><small><a href="http://maps.google.com/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=pioneer+square+seattle+wa&amp;sll=47.645833,-122.543441&amp;sspn=0.439931,0.476532&amp;ie=UTF8&amp;hq=&amp;hnear=Pioneer+Square,+Seattle,+Washington&amp;t=h&amp;z=14&amp;ll=47.598533,-122.333236" style="color:#0000FF;text-align:left">View Larger Map</a></small>';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>11, 'name'=>'body', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>11, 'name'=>'sidebar', 'filter_id'=>0,
						'content'=>'', 'content_html'=>'',
					)
				)
			);

		$model->addPage
		(
			$aboutPage = $this->factory->manufacture
			(
				'Page', array
				(
					'slug'=>'about', 'title'=>'About', 'breadcrumb'=>'About', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>false, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'',
					'parent_id'=>1, 'author_id'=>1,
					'categories'=>'1',
				)
			)
		);
			$content = <<< EOD
<h2>Founded in 1989...</h2>

<p>Duis vitae mi. Nunc tristique mauris malesuada odio. Integer pharetra, urna sit amet scelerisque sollicitudin, felis est rhoncus nibh, sed sollicitudin ipsum lacus quis metus. In hac habitasse platea dictumst. Sed quis sem eu libero tristique vehicula. In fermentum, pede ut congue placerat, arcu erat tempor augue, id ornare ipsum ante vitae tellus. Duis bibendum quam vitae lectus. Curabitur euismod nulla a risus. Suspendisse erat. Nulla facilisi. Integer odio felis, sollicitudin in, feugiat non, dictum sit amet, elit.</p>
EOD;
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>12, 'name'=>'body', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$content = <<< EOD
<div class="box" style="margin-top:50px">
   <h3>Main Office</h3>
   123 Main St.<br />
   Seattle, WA 12345<br />
   (123) 456-7890
</div>
EOD;
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>12, 'name'=>'sidebar', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);

		$model->addPage
		(
			$newsPage = $this->factory->manufacture
			(
				'ArchivePage', array
				(
					'slug'=>'news', 'title'=>'Articles', 'breadcrumb'=>'News', 'status'=>_Page::Status_published,
					'level'=>1, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
					'template_name'=>'article',
					'parent_id'=>1, 'author_id'=>1,
					'categories'=>'1',
				)
			)
		);
			$content = 'sidebar_articles';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>13, 'name'=>'sidebar_category', 'filter_id'=>0, 'type'=>'text',
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$model->addPage
			(
				$this->factory->manufacture
				(
					'ArchiveYearIndex', array
					(
						'slug'=>'yearly-archive', 'title'=>'%Y Archive', 'breadcrumb'=>'Yearly Archive', 'status'=>_Page::Status_hidden,
						'level'=>2, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
						'template_name'=>'',
						'parent_id'=>13, 'author_id'=>1,
					)
				)
			);
			$model->addPage
			(
				$this->factory->manufacture
				(
					'ArchiveMonthIndex', array
					(
						'slug'=>'monthly-archive', 'title'=>'%B %Y Archive', 'breadcrumb'=>'Monthly Archive', 'status'=>_Page::Status_hidden,
						'level'=>2, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
						'template_name'=>'',
						'parent_id'=>13, 'author_id'=>1,
					)
				)
			);
			$model->addPage
			(
				$this->factory->manufacture
				(
					'ArchiveDayIndex', array
					(
						'slug'=>'daily-archive', 'title'=>'%B %d, %Y Archive', 'breadcrumb'=>'Daily Archive', 'status'=>_Page::Status_hidden,
						'level'=>2, 'magical'=>true, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
						'template_name'=>'',
						'parent_id'=>13, 'author_id'=>1,
					)
				)
			);
			$model->addPage
			(
				$page = $this->factory->manufacture
				(
					'Page', array
					(
						'slug'=>'the-finest-ice-cream', 'title'=>'The Finest Ice Cream You\'ve Ever Tasted', 'breadcrumb'=>'The Finest Ice Cream', 'status'=>_Page::Status_published,
						'level'=>2, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
						'template_name'=>'',
						'parent_id'=>13, 'author_id'=>1,
					)
				)
			);
			$date = '2010-05-15 00:00:00';
			$model->updatePageDates($page, array('created'=>$date, 'edited'=>$date, 'published'=>$date));
			$content = <<< EOD
<p>Vivamus mollis porttitor odio. Vestibulum id mi. Suspendisse a tortor quis metus imperdiet eleifend. Pellentesque libero nulla, aliquam in, tristique vel, elementum et, odio. Fusce id ligula. Vivamus dapibus imperdiet ante. Aliquam id eros ut velit eleifend aliquet. Nulla commodo viverra orci. Sed auctor. Maecenas commodo. Mauris adipiscing lectus quis quam. Nulla a nisl in augue suscipit tincidunt. Nunc eros. Nam eget sapien eget lorem ultrices pulvinar. Vivamus dolor.</p>

<p>Suspendisse auctor risus nec odio. Sed tortor sem, tempor eu, molestie ac, viverra et, arcu. Donec aliquet pede eget tellus. Pellentesque facilisis nibh in ante. Mauris et lectus. Nullam pede. Nam sed pede sollicitudin mi congue vestibulum. Suspendisse et diam sed odio venenatis dictum.</p>

<p>Integer eget nunc sit amet felis interdum sollicitudin. Mauris posuere, quam eu aliquam iaculis, ipsum magna pellentesque arcu, id rutrum augue erat luctus magna.</p>

<p>Praesent ut lacus. Fusce interdum metus laoreet mi. Duis aliquet. Nunc malesuada leo id nulla. Praesent condimentum metus nec odio. Ut ut velit dapibus nibh interdum eleifend. Proin tempor sodales enim. Donec ultricies. Duis bibendum urna id ante. Donec ipsum lorem, venenatis et, tempus ut, vehicula et, mi.</p>
EOD;
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>17, 'name'=>'body', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$content = 'Vivamus mollis porttitor odio. Vestibulum id mi. Suspendisse a tortor quis metus imperdiet eleifend. Pellentesque libero nulla, aliquam in, tristique vel, elementum et, odio. Fusce id ligula. Vivamus dapibus imperdiet ante. Aliquam id eros ut velit eleifend aliquet. Nulla commodo viverra orci. Sed auctor. Maecenas commodo. Mauris adipiscing lectus quis quam. Nulla a nisl in augue suscipit tincidunt. Nunc eros. Nam eget sapien eget lorem ultrices pulvinar. Vivamus dolor.';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>17, 'name'=>'summary', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);

			$model->addPage
			(
				$page = $this->factory->manufacture
				(
					'Page', array
					(
						'slug'=>'we-deliver', 'title'=>'Having a party? We deliver.', 'breadcrumb'=>'We Deliver', 'status'=>_Page::Status_published,
						'level'=>2, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
						'template_name'=>'',
						'parent_id'=>13, 'author_id'=>1,
					)
				)
			);
			$date = '2010-06-01 00:00:00';
			$model->updatePageDates($page, array('created'=>$date, 'edited'=>$date, 'published'=>$date));
			$content = <<< EOD
Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?

Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.
EOD;
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>18, 'name'=>'body', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$content = 'Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>18, 'name'=>'summary', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);

			$model->addPage
			(
				$page = $this->factory->manufacture
				(
					'Page', array
					(
						'slug'=>'hiring', 'title'=>'We\'re Hiring!', 'breadcrumb'=>'We\'re Hiring', 'status'=>_Page::Status_published,
						'level'=>2, 'cacheable'=>_Page::Cacheable_inherit, 'secure'=>_Page::Secure_inherit,
						'template_name'=>'',
						'parent_id'=>13, 'author_id'=>1,
					)
				)
			);
			$date = '2010-06-15 00:00:00';
			$model->updatePageDates($page, array('created'=>$date, 'edited'=>$date, 'published'=>$date));
			$content = <<< EOD
At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.

Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.

Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.
EOD;
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>19, 'name'=>'body', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
			$content = 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.';
			$model->addPagePart
			(
				$this->factory->manufacture
				(
					'Part', array
					(
						'page_id'=>19, 'name'=>'summary', 'filter_id'=>0,
						'content'=>$content, 'content_html'=>$content,
					)
				)
			);
	
		$locationsPage->position = 0;
		$model->updatePage($locationsPage, false);
		$newsPage->position = $locationsPage->id;
		$model->updatePage($newsPage, false);
		$aboutPage->position = $newsPage->id;
		$model->updatePage($aboutPage, false);
		$locationsPage->position = $aboutPage->id;
		$model->updatePage($locationsPage, false);
		
	}
}
