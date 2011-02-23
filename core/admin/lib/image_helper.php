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

class _ImageHelper extends SparkPlug
{
	//---------------------------------------------------------------------------

	public static function buildImage($params, $image)
	{
		// build image object

		$image->slug = trim($params['image_name']);
		$image->ctype = !empty($params['image_content_type']) ? trim($params['image_content_type']) : 'image/gif';
		$image->url = trim($params['image_url']);
		$image->width = trim($params['image_width']);
		$image->height = trim($params['image_height']);
		$image->alt = trim($params['image_alt']);
		$image->title = trim($params['image_title']);
		$image->theme_id = 0;
	}
	
	//---------------------------------------------------------------------------

	public static function loadImage($image)
	{
		$uploadedFileName = @$_FILES['image_upload']['tmp_name'];

		if (!empty($_FILES['image_upload']['size']) && !empty($uploadedFileName) && is_uploaded_file($uploadedFileName))
		{
			if (($imageSize = getimagesize($uploadedFileName)) !== false)
			{
				if (empty($image->slug) && !empty($_FILES['image_upload']['name']))
				{
					$image->slug = $_FILES['image_upload']['name'];
				}
				$image->ctype = $imageSize['mime'];
				$image->width = $imageSize[0];
				$image->height = $imageSize[1];
				$image->content = file_get_contents($uploadedFileName);
			}
			unlink($uploadedFileName);
		}
	}
	
	//---------------------------------------------------------------------------

	public function validateImage($params, $allowUpload, &$errors)
	{
		$errors = array();
		
		$uploadedFileName = @$_FILES['image_upload']['tmp_name'];

		if ($allowUpload)
		{
			if (isset($_FILES['image_upload']['error']))
			{
				$errCode = $_FILES['image_upload']['error'];
				if (($errCode != UPLOAD_ERR_OK) && ($errCode != UPLOAD_ERR_NO_FILE))
				{
					$errors['image_upload'] = 'There was a problem uploading your image.';
					switch ($errCode)
					{
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$errors['image_upload'] .= ' This image file exceeds the maximum allowable size.';
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$errors['image_upload'] .= ' No temporary directory.';
						case UPLOAD_ERR_CANT_WRITE:
							$errors['image_upload'] .= ' Could not write to file.';
					}
				}
			}
			else
			{
				if (!empty($_FILES['image_upload']['size']) && !empty($uploadedFileName) && is_uploaded_file($uploadedFileName))
				{
					if (getimagesize($uploadedFileName) === false)
					{
						$errors['image_upload'] = 'Unsupported image format.';
					}
					elseif (filesize($uploadedFileName) > $this->app->get_pref('max_upload_size'))
					{
						$errors['image_upload'] = 'This image file exceeds the maximum allowable size.';
					}
				}
			}
		}
		
		if (empty($params['image_name']) && (!$allowUpload || !$_FILES['image_upload']['size']))
		{
			$errors['image_name'] = 'Image name is required.';
		}

		if (!empty($params['image_url']))
		{
			if (!$this->validateURL($params['image_url'], $error))
			{
				$errors['image_url'] = $error;
			}
		}

		if ($params['image_width'] !== '')
		{
			if (!is_numeric($params['image_width']) || !preg_match('/^\d*$/', $params['image_width']))
			{
				$errors['image_width'] = 'Image width must be an integer.';
			}
		}
		
		if ($params['image_height'] !== '')
		{
			if (!is_numeric($params['image_height']) || !preg_match('/^\d*$/', $params['image_height']))
			{
				$errors['image_height'] = 'Image height must be an integer.';
			}
		}
		
		if (!empty($uploadedFileName) && (!$allowUpload || !empty($errors)))
		{
			unlink($uploadedFileName);
		}
		
		return empty($errors);
	}

	//---------------------------------------------------------------------------

	private static function validateURL($url, &$error)
	{
		if (!SparkUtil::valid_url($url) && !SparkUtil::valid_url_path($url))
		{
			$error = 'Not a valid URL.';
			return false;
		}
		return true;
	}

//------------------------------------------------------------------------------

}
