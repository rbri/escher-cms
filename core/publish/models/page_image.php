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

class _PageImage extends Page
{
	const PageType = 'PageImage';

	public function fetchTemplate($model, $theme, $branch, $prefs)
	{
		if (empty($this->magic))
		{
			return false;
		}
		if (($lastMagic = count($this->magic) - 1) > 1)
		{
			return false;
		}
		
		$file = $this->magic[$lastMagic--];
		
		if ($theme = ($lastMagic === 0) ? $this->magic[$lastMagic] : 0)
		{
			$theme = $model->fetchTheme($theme);
		}
		
		if ($isContentImage = empty($theme) && (dirname($this->uri()) === $prefs['content_image_path']))
		{
			$theme = NULL;
			$branch = 1;
		}

		// check for versioned file name

		if (!empty($prefs['auto_versioned_images']))
		{
			if (preg_match('/(.*),(\d+)(\..+)/', $file, $matches))
			{
				$file = $matches[1].$matches[3];
				$rev = $matches[2];
			}
		}

		$template = $model->fetchImage($file, $theme, $branch, true, $isContentImage);

		// if the request was for a versioned file name, ensure that we are serving the requested version
		
		if ($template && isset($rev))
		{
			if ($rev !== $template->rev)
			{
				$template = false;
			}
		}
		
		return $template;
	}
}
