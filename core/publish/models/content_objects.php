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

if (!defined('escher_content_objects'))
{

define('escher_content_objects', 1);

//------------------------------------------------------------------------------

class ContentObject extends EscherObject
{
	const branch_status_none = 0;
	const branch_status_added = 1;
	const branch_status_edited = 2;
	const branch_status_deleted = 3;

	// database fields

	public $created;
	public $edited;
	public $author_id;
	public $editor_id;

	// cache fields...

	public $author;
	public $author_name;
	public $editor;
	public $editor_name;
	
	public function getDate($for, $format = '%x')
	{
		switch ($for)
		{
			case 'created':
				return $this->app->format_date($this->created . ' UTC', $format, 1);
			case 'edited':
				return $this->app->format_date($this->edited . ' UTC', $format, 1);
			case 'published':
				return $this->app->format_date($this->published . ' UTC', $format, 1);
			case 'now':
			default:
				return $this->app->format_date('now', $format, 1);
		}
	}

	public function created($format = 'Y-m-d')
	{
		return $this->app->format_date($this->created . ' UTC', $format, 0);
	}

	public function edited($format = 'Y-m-d')
	{
		return $this->app->format_date($this->edited . ' UTC', $format, 0);
	}

	public function published($format = 'Y-m-d')
	{
		return $this->app->format_date($this->published . ' UTC', $format, 0);
	}

	public static function slugRegex()
	{
		static $slugRegex = '/^([\w\-\.\,\;])*$/';
		return $slugRegex;
	}
	
	public static function filterSlug($slug)
	{
		return strtolower(trim(preg_replace(array('/[^0-9A-Za-z\-\_\. ]/', '/[\s_-]+/'), array('', '-'),  $slug), '-'));
	}

	public static function branchStatusToText($status)
	{
		switch ($status)
		{
			case self::branch_status_none:
				return 'none';
			case self::branch_status_added:
				return 'added';
			case self::branch_status_edited:
				return 'modified';
			case self::branch_status_deleted:
				return 'deleted';
		}
	}
}

//------------------------------------------------------------------------------

class _Theme extends ContentObject
{
	// database fields

	public $slug;
	public $title;
	public $lineage;
	public $style_url;
	public $script_url;
	public $image_url;
	public $parent_id;
	public $branch;
	public $deleted;

	// cache fields...

	public $children;

	public function makeSlug()
	{
		if (empty($this->slug))
		{
			$this->slug = $this->title;
		}

		$this->slug = self::filterSlug($this->slug);
	}
}

//------------------------------------------------------------------------------

class _Template extends ContentObject
{
	// database fields

	public $name;
	public $ctype;
	public $content;
	public $theme_id;
	public $branch;
	public $deleted;

	public function isParsable()
	{
		return true;
	}
}

//------------------------------------------------------------------------------

class _Snippet extends ContentObject
{
	// database fields

	public $name;
	public $content;
	public $theme_id;
	public $branch;
	public $deleted;
}

//------------------------------------------------------------------------------

class _Tag extends ContentObject
{
	// database fields

	public $name;
	public $content;
	public $theme_id;
	public $branch;
	public $deleted;
}

//------------------------------------------------------------------------------

class _Style extends ContentObject
{
	// database fields

	public $slug;
	public $ctype;
	public $url;
	public $rev;
	public $content;
	public $theme_id;
	public $branch;
	public $deleted;

	public function isParsable()
	{
		return true;
	}

	public function makeSlug()
	{
		$this->slug = self::filterSlug($this->slug);
	}
}

//------------------------------------------------------------------------------

class _Script extends ContentObject
{
	// database fields

	public $slug;
	public $ctype;
	public $url;
	public $rev;
	public $content;
	public $theme_id;
	public $branch;
	public $deleted;

	public function isParsable()
	{
		return true;
	}

	public function makeSlug()
	{
		$this->slug = self::filterSlug($this->slug);
	}
}

//------------------------------------------------------------------------------

class _Image extends ContentObject
{
	// database fields

	public $slug;
	public $ctype;
	public $url;
	public $width;
	public $height;
	public $alt;
	public $title;
	public $rev;
	public $content;
	public $theme_id;
	public $branch;
	public $deleted;
	public $priority;

	// cache fields...

	public $meta;
	public $categories;

	public function isParsable()
	{
		return false;
	}

	public function makeSlug()
	{
		if (empty($this->slug))
		{
			$this->slug = $this->title;
		}

		$this->slug = self::filterSlug($this->slug);
	}
}

//------------------------------------------------------------------------------

class _PageModel extends ContentObject
{
	const Status_inherit = -1;		// not currently implemented
	const Status_draft = 1;
	const Status_reviewed = 2;
	const Status_published = 3;
	const Status_sticky = 4;
	const Status_hidden = 5;
	const Status_expired = 6;

	const Cacheable_inherit = -1;
	const Cacheable_no = 0;
	const Cacheable_yes = 1;

	const Secure_inherit = -1;
	const Secure_no = 0;
	const Secure_yes = 1;

	// database fields

	public $name;
	public $type;
	public $status;
	public $magical;
	public $cacheable;
	public $secure;
	public $template_name;

	// cache fields...

	public $meta;
	public $categories;
	public $parts;

	public function __construct($fields)
	{
		parent::__construct($fields);
		if (!isset($this->cacheable))
		{
			$this->cacheable = self::Cacheable_inherit;
		}
		if (!isset($this->secure))
		{
			$this->secure = self::Secure_inherit;
		}
	}
	
	public static function statusOptions()
	{
		static $statusOptions = array
		(
			self::Status_draft => 'Draft',
			self::Status_reviewed => 'Reviewed',
			self::Status_published => 'Published',
			self::Status_sticky => 'Sticky',
			self::Status_hidden => 'Hidden',
			self::Status_expired => 'Expired',
		);
		
		return $statusOptions;
	}

	public static function textToStatus($str)
	{
		static $statusOptions = array
		(
			'draft' => self::Status_draft,
			'reviewed' => self::Status_reviewed,
			'published' => self::Status_published,
			'sticky' => self::Status_sticky,
			'hidden' => self::Status_hidden,
			'expired' => self::Status_expired,
		);
		return @$statusOptions[(trim(strtolower($str)))];
	}

	public static function statusToText($status)
	{
		$statusOptions = self::statusOptions();
		return @$statusOptions[$status];
	}

	public function statusText() { return self::statusToText($this->status); }
	public function isDraft() { return intval($this->status) === self::Status_draft; }
	public function isReviewed() { return intval($this->status) === self::Status_reviewed; }
	public function isPublished() { return intval($this->status) === self::Status_published; }
	public function isSticky() { return intval($this->status) === self::Status_sticky; }
	public function isHidden() { return intval($this->status) === self::Status_hidden; }
	public function isExpired() { return intval($this->status) === self::Status_expired; }
}

//------------------------------------------------------------------------------

class _Page extends _PageModel
{
	private $_parent;
	private $_uri;
	private $_base_uri;

	// database fields

	public $level;
	public $position;
	public $title;
	public $slug;
	public $breadcrumb;
	public $published;
	public $parent_id;
	public $model_id;
	public $priority;

	// cache fields...

	public $active_template_name;
	public $is_cacheable;
	public $is_secure;
	public $magic;
	public $virtual;
	public $children;
	
	public function __construct($fields)
	{
		parent::__construct($fields);
		$this->type = get_class($this);
		unset($this->name);
	}
	
	public function parent() { return $this->_parent; }
	public function uri() { return $this->_uri; }
	public function activeTemplateName() { return $this->active_template_name; }
	public function isCacheable() { return $this->is_cacheable; }
	public function isSecure() { return $this->is_secure; }

	public function baseURI()
	{ 
		if (!isset($this->_base_uri))
		{
			if (empty($this->magic))
			{
				$this->_base_uri = $this->_uri;
			}
			else
			{
				$magic = '';
				foreach ($this->magic as $slug)
				{
					$magic .= ('/' . $slug);
				}
				$this->_base_uri = preg_replace('@'.$magic.'/?$@', '',  $this->_uri);
			}
		}

		return $this->_base_uri;
	}

	public function setURI($uri)
	{
		$this->_uri = $uri;
		return $this;
	}

	public function setParent($parent)
	{
		if ($this->_parent = $parent)
		{
			if ($this->parent_id === NULL)
			{
				$this->parent_id = $parent->id;
			}
			$this->setInheritedProperties();
		}

		return $this;
	}

	public function setInheritedProperties()
	{
		if ($this->_parent)
		{
			if (($this->active_template_name = $this->template_name) === '')
			{
				$this->active_template_name = $this->_parent->active_template_name;
			}
			if (($this->is_cacheable = $this->cacheable) == self::Cacheable_inherit)
			{
				$this->is_cacheable = $this->_parent->is_cacheable;
			}
			if (($this->is_secure = $this->secure) == self::Secure_inherit)
			{
				$this->is_secure = $this->_parent->is_secure;
			}
		}
		 return $this;
	}

	public function makeSlug()
	{
		if (empty($this->slug))
		{
			$this->slug = str_replace('.', '-', $this->title);
		}
		
		$this->slug = self::filterSlug($this->slug);
	}
	
	public function fetchTemplate($model, $theme, $branch, $prefs)
	{
		return $model->fetchTemplate($this->active_template_name, $theme, $branch);
	}

	public function fetchOverridePage($model)
	{
		return $this;
	}
}

//------------------------------------------------------------------------------

class _Part extends ContentObject
{
	public $name;
	public $position;
	public $type;
	public $validation;
	public $content;
	public $content_html;
	public $filter_id;
	public $page_id;

	public function __construct($fields)
	{
		parent::__construct($fields);
		$this->id = "{$this->name}_{$this->page_id}";
	}
}

//------------------------------------------------------------------------------

class _Block extends ContentObject
{
	// database fields

	public $name;
	public $title;
	public $content;
	public $content_html;
	public $filter_id;
	public $priority;

	// cache fields...

	public $categories;
}

//------------------------------------------------------------------------------

class _File extends ContentObject
{
	// database fields

	public $slug;
	public $ctype;
	public $url;
	public $title;
	public $description;
	public $status;
	public $download;
	public $size;
	public $rev;
	public $content;
	public $priority;

	// cache fields...

	public $meta;
	public $categories;

	public function isParsable()
	{
		return false;
	}

	public function makeSlug()
	{
		$this->slug = self::filterSlug($this->slug);
	}

	public function statusText() { return _Page::statusToText($this->status); }
}

//------------------------------------------------------------------------------

class _Link extends ContentObject
{
	// database fields

	public $name;
	public $title;
	public $description;
	public $url;
	public $priority;

	// cache fields...

	public $meta;
	public $categories;
}

//------------------------------------------------------------------------------

class _Category extends ContentObject
{
	private $_parent;
	private $_uri;

	public $slug;
	public $title;
	public $level;
	public $position;
	public $count;
	public $parent_id;
	public $priority;

	// cache fields...

	public $children;

	public function setParent($parent) { $this->_parent = $parent; return $this; }
	public function parent() { return $this->_parent; }
	public function setURI($uri) { $this->_uri = $uri; return $this; }
	public function uri() { return $this->_uri; }

	public function makeSlug()
	{
		if (empty($this->slug))
		{
			$this->slug = str_replace('.', '-', $this->title);
		}
		$this->slug = self::filterSlug($this->slug);
	}
}

//------------------------------------------------------------------------------

}