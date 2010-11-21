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

$lang['parse'] = array
(
	'page_not_found' => 'Page not found',
	'missing_closing_tag' => 'Missing closing tag: %1$s',
	'misplaced_else' => 'Unexpected else',
	'unknown_tag' => 'Unknown tag: %1$s',
	'attribute_values_must_be_quoted' => 'Attribute values must be quoted',
	'page_part_not_found' => 'Page part not found: %1$s',
	'page_level_not_found' => 'Page level not found: %1$s',
	'block_not_found' => 'Block not found: %1$s',
	'image_not_found' => 'Image not found: %1$s',
	'file_not_found' => 'File not found: %1$s',
	'link_not_found' => 'Link not found: %1$s',
	'template_not_found' => 'Template not found: %1$s',
	'snippet_not_found' => 'Snippet not found: %1$s',
	'snippet_recursion_limit' => 'Snippet recursion limit reached: %1$s',
	'block_recursion_limit' => 'Blocks may not reference themselves: %1$s',
	'part_recursion_limit' => 'Page parts may not reference themselves: %1$s',
	'child_not_found' => 'Child not found',
	'sibling_not_found' => 'Sibling not found',
	'category_not_found' => 'Category not found: %1$s',
	'unknown_attribute' => 'Unknown attribute: %1$s',
	'attribute_required' => '"%1$s" attribute is required in tag: %2$s',
	'out_of_scope' => '"%1$s" is out of scope in tag: %2$s',
	'redefined_err' => '%1$s=%2$s already defined in tag: %3$s',
	'illegal_tag_nesting' => 'illegally nested tag: %1$s',
	'namespace_must_be_string' => 'attempt to push non-string onto namespace stack',
	'namespace_pop_error' => 'unexpected namespace popped',
	'not_a_category' => 'cannot push non-category object onto category stack',
	'not_a_page' => 'cannot push non-page object onto page stack',
	'not_a_block' => 'cannot push non-block object onto block stack',
	'not_an_image' => 'cannot push non-image object onto image stack',
	'not_a_file' => 'cannot push non-file object onto file stack',
	'not_a_link' => 'cannot push non-link object onto link stack',
);
