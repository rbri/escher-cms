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

$lang['prefs'] = array
(
	'tidy_indent' => 'Indent',
	'tidy_clean' => 'Clean',
	'tidy_xhtml' => 'XHTML Output',
	'tidy_wrap' => 'Wrap',

	'auto_tidy_help' => 'Automatically run HTML Tidy on all generated html pages?',
	'tidy_indent_help' => 'Enable HTML Tidy indent feature?',
	'tidy_clean_help' => 'Enable HTML Tidy clean feature?',
	'tidy_xhtml_help' => 'Enable HTML Tidy XHTML output feature?',
	'tidy_wrap_help' => 'Wrap lines containing more than this many characters (0 to disable)?',
);
