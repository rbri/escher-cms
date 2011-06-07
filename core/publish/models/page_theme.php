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

class _PageTheme extends Page
{
	const PageType = 'PageTheme';

	public function fetchTemplate($model, $theme, $branch, $prefs)
	{
		if (empty($this->magic) || count($this->magic) < 2)
		{
			return false;
		}
		
		// magic is: /themeName/pageType/pageName
		
		$path = '/' . implode('/', $this->magic);

		if (!preg_match("/^(?:\/([^\/]+))?(\/.+)+(?:\/(.+))$/i", $path, $matches))
		{
			return false;
		}

		$theme = $matches[1];
		$type = $matches[2];
		$file = $matches[3];

		switch ($type)
		{
			case $prefs['style_path']:
				$type = 1;
				$typeName = 'styles';
				break;

			case $prefs['script_path']:
				$type = 2;
				$typeName = 'scripts';
				break;

			case $prefs['image_path']:
				$type = 3;
				$typeName = 'images';
				break;
		}

		// check for versioned file name

		if (!empty($prefs['auto_versioned_'.$typeName]))
		{
			if (preg_match('/(.*),(\d+)(\..+)/', $file, $matches))
			{
				$file = $matches[1].$matches[3];
				$rev = $matches[2];
			}
		}

		$template = false;
		
		switch ($type)
		{
			case 1:
				$template = $model->fetchStyle($file, $theme, $branch);
				break;

			case 2:
				$template = $model->fetchScript($file, $theme, $branch);
				break;

			case 3:
				$template = $model->fetchImage($file, $theme ? $theme : 0, $branch, true);
				break;
		}
		
		// if the request was for a versioned file name, ensure that we are serving the requested version
		
		if ($template && isset($rev))
		{
			if ($rev !== $template->rev)
			{
				$template = false;
			}
		}
		
		if ($template)
		{
			$this->created = $template->created;
			$this->edited = $template->edited;
			$this->author_id = $template->author_id;
			$this->editor_id = $template->editor_id;
	
			$this->author = $template->author;
			$this->author_name = $template->author_name;
			$this->editor = $template->editor;
			$this->editor_name = $template->editor_name;
		}
		
		return $template;
	}
}
