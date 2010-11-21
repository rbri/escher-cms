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

$lang['prefs'] = array
(
	'comments_enabled' => NULL,
	'comments_apply_nofollow' => 'Apply "nofollow"',
	'comments_require_approval' => 'Require Approval',
	'comments_notification_email' => 'Notification Email',

	'comments_apply_nofollow_help' => 'Apply "nofollow" to links in comments?',
	'comments_require_approval_help' => 'Require administrator to approve comments before they appear on site?',
	'comments_notification_email_help' => 'Notify this email address whenever new comment is posted.',
);
