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

class _FileHelper extends SparkPlug
{
	//---------------------------------------------------------------------------

	public static function buildFile($params, $file)
	{
		// build file object

		$file->slug = trim($params['file_name']);
		$file->ctype = !empty($params['file_content_type']) ? trim($params['file_content_type']) : '';
		$file->url = trim($params['file_url']);
		$file->title = trim($params['file_title']);
		$file->description = trim($params['file_description']);
		$file->status = trim($params['file_status']);
		$file->download = $params['file_download'] ? true : false;

		// build file metadata
	
		$meta = array();
		foreach ($params as $key => $val)
		{
			if (preg_match('/meta_(.*)/', $key, $matches))
			{
				$meta[$matches[1]] = $val;
			}
		}
		$file->meta = $meta;
	}
	
	//---------------------------------------------------------------------------

	public static function loadFile($file)
	{
		$uploadedFileName = @$_FILES['file_upload']['tmp_name'];

		if (!empty($_FILES['file_upload']['size']) && !empty($uploadedFileName) && is_uploaded_file($uploadedFileName))
		{
			if (empty($file->slug) && !empty($_FILES['file_upload']['name']))
			{
				$file->slug = $_FILES['file_upload']['name'];
			}
			$file->ctype = @$_FILES['file_upload']['type'];
			$file->size = filesize($uploadedFileName);
			$file->content = file_get_contents($uploadedFileName);
			unlink($uploadedFileName);
		}
	}
	
	//---------------------------------------------------------------------------

	public function validateFile($params, $allowUpload, &$errors)
	{
		$errors = array();
		
		if ($allowUpload)
		{
			if (isset($_FILES['file_upload']['error']))
			{
				$errCode = $_FILES['file_upload']['error'];
				if (($errCode != UPLOAD_ERR_OK) && ($errCode != UPLOAD_ERR_NO_FILE))
				{
					$errors['file_upload'] = 'There was a problem uploading your file.';
					switch ($errCode)
					{
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$errors['file_upload'] .= ' This file file exceeds the maximum allowable size.';
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$errors['file_upload'] .= ' No temporary directory.';
						case UPLOAD_ERR_CANT_WRITE:
							$errors['file_upload'] .= ' Could not write to file.';
					}
				}
			}
			else
			{
				$uploadedFileName = @$_FILES['file_upload']['tmp_name'];
				if (!empty($_FILES['file_upload']['size']) && !empty($uploadedFileName) && is_uploaded_file($uploadedFileName))
				{
					if (filesize($uploadedFileName) > $this->app->get_pref('max_upload_size'))
					{
						$errors['file_upload'] = 'This file file exceeds the maximum allowable size.';
					}
				}
			}
		}
		
		if (empty($params['file_name']) && (!$allowUpload || !$_FILES['file_upload']['size']))
		{
			$errors['file_name'] = 'File name is required.';
		}

		if (!empty($params['file_url']))
		{
			if (!$this->validateURL($params['file_url'], $error))
			{
				$errors['file_url'] = $error;
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
