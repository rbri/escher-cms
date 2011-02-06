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

require(escher_core_dir.'/publish/models/content_objects.php');

class _FormFieldGenerator extends SparkPlug
{
	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();
	}

	//---------------------------------------------------------------------------

	public function text($atts)
	{
		$extra = isset($atts['att']) ? (' ' . $atts['att']) : '';
		$disabled = isset($atts['disabled']);
		$atts = array('size'=>30, 'maxlength'=>255, 'name'=>$atts['name'], 'value'=>$atts['val'], 'id'=>$atts['name']);
		if ($disabled)
		{
			$atts['disabled'] = 'disabled';
		}
		return '<input type="text"' . $extra . $this->matts($atts) . ' />';
	}

	//---------------------------------------------------------------------------

	public function validate_text(&$atts)
	{
		if (strlen(@$atts['validation']))
		{
			return preg_match($atts['validation'], $atts['val']);
		}
		
		return true;
	}

	//---------------------------------------------------------------------------

	public function password($atts)
	{
		$disabled = isset($atts['disabled']);
		$atts = array('size'=>30, 'maxlength'=>255, 'name'=>$atts['name'], 'value'=>$atts['val'], 'id'=>$atts['name']);
		if ($disabled)
		{
			$atts['disabled'] = 'disabled';
		}
		return '<input type="password"' . $this->matts($atts) . ' />';
	}

	//---------------------------------------------------------------------------

	public function validate_password(&$atts)
	{
		return $this->validate_text($atts);
	}

	//---------------------------------------------------------------------------

	public function textarea($atts)
	{
		$disabled = isset($atts['disabled']);
		$atts = array('id'=>$atts['name'], 'name'=>$atts['name'], 'class'=>@$atts['class'] , 'value'=>$atts['val']);
		if ($disabled)
		{
			$atts['disabled'] = 'disabled';
		}
		if (!isset($atts['rows']))
		{
			$atts['rows'] = 3;
		}
		if (!isset($atts['cols']))
		{
			$atts['cols'] = 80;
		}
		
		$value = SparkView::escape_html($atts['value']);
		unset($atts['value']);
		
		return '<textarea' . $this->matts($atts) . '>' . $value . '</textarea>';
	}

	//---------------------------------------------------------------------------

	public function validate_textarea(&$atts)
	{
		if (strlen(@$atts['validation']))
		{
			return preg_match($atts['validation'], $atts['val']);
		}
		
		return true;
	}

	//---------------------------------------------------------------------------

	public function yesnoradio($atts)
	{ 
		$disabled = isset($atts['disabled']);
		$name = SparkView::escape_html($atts['name']);
		$checked0 = !$atts['val'] ? ' checked="checked"' : '';
		$checked1 = $atts['val'] ? ' checked="checked"' : '';
		
		return
			'<input ' . ($disabled ? 'disabled="disabled" ' : '') . 'type="radio" value="0" name="'.$name.'" id="'.$name.'-0"' . $checked0 . ' />' . 
			'<label for="'.$name.'-0">No</label>' .
			'<input ' . ($disabled ? 'disabled="disabled" ' : '') . 'type="radio" value="1" name="'.$name.'" id="'.$name.'-1"' . $checked1 . ' />' . 
			'<label for="'.$name.'-1">Yes</label>';
	}

	//---------------------------------------------------------------------------

	public function validate_yesnoradio(&$atts)
	{
		return ($atts['val'] === '0') || ($atts['val'] === '1');
	}

	//---------------------------------------------------------------------------

	public function checkbox($atts)
	{ 
		$disabled = isset($atts['disabled']);
		$name = SparkView::escape_html($atts['name']);
		$checked = $atts['val'] ? ' checked="checked"' : '';
		
		return
			'<input type="hidden" value="0" name="'.$name.'" />' . 
			'<input ' . ($disabled ? 'disabled="disabled" ' : '') . 'type="checkbox" value="1" name="'.$name.'" id="'.$name.'"' . $checked . ' />' . 
			'<label for="'.$name.'">Yes</label>';
	}

	//---------------------------------------------------------------------------

	public function validate_checkbox(&$atts)
	{
		return ($atts['val'] === '0') || ($atts['val'] === '1');
	}

	//---------------------------------------------------------------------------

	public function select($atts)
	{
		$val = $atts['val'];
		$disabled = isset($atts['disabled']);
		$options = isset($atts['data']) ? unserialize($atts['data']) : array();
		$atts = array('name'=>$atts['name'], 'id'=>$atts['name']);
		if ($disabled)
		{
			$atts['disabled'] = 'disabled';
		}
		$html = '<select'. $this->matts($atts). '>';
		
		foreach($options as $id => $name)
		{
			$selected = ($id == $val) ? ' selected="selected"' : '';
			$html .= '<option value="'.$id.'"' . $selected . '>' . SparkView::escape_html($name) . '</option>';
		}
	
		$html .= '</select>';

		return $html;
	}

	//---------------------------------------------------------------------------

	public function validate_select(&$atts)
	{
		$options = unserialize($atts['data']);
		return isset($options[$atts['val']]);
	}

	//---------------------------------------------------------------------------

	public function integer($atts)
	{ 
		return $this->text($atts);
	}

	//---------------------------------------------------------------------------

	public function validate_integer(&$atts)
	{
		if (!preg_match('/^(?:\+|\-)?(?:\d+(?:,\d{3})*)?$/', $atts['val']))
		{
			return false;
		}
		
		if (strlen(@$atts['validation']))
		{
			$range = explode(',', rtrim(ltrim($atts['validation'], '['), ']'));
			if (($atts['val'] < $range[0]) || ($atts['val'] > $range[1]))
			{
				return false;
			}
		}
		
		$atts['val'] = str_replace(array('+',','), '', $atts['val']);
		
		return true;
	}

	//---------------------------------------------------------------------------

	public function numeric($atts)
	{
		return $this->text($atts);
	}

	//---------------------------------------------------------------------------

	public function validate_numeric(&$atts)
	{
		if (!preg_match('/^(?:\+|\-)?(?:\d+(?:,\d{3})*)?(?:\.\d*)?$/', $atts['val']))
		{
			return false;
		}
		
		if (strlen(@$atts['validation']))
		{
			$range = explode(',', rtrim(ltrim($atts['validation'], '['), ']'));
			if (($atts['val'] < $range[0]) || ($atts['val'] > $range[1]))
			{
				return false;
			}
		}
		
		$atts['val'] = str_replace(array('+',','), '', $atts['val']);
		
		return true;
	}

	//---------------------------------------------------------------------------

	public function email($atts)
	{
		return $this->text($atts);
	}

	//---------------------------------------------------------------------------

	public function validate_email(&$atts)
	{
		if (self::emptyOK($atts))
		{
			return true;
		}

		if (!SparkUtil::valid_email($atts['val']))
		{
			return false;
		}
		
		return true;
	}

	//---------------------------------------------------------------------------

	public function url($atts)
	{
		return $this->text($atts);
	}

	//---------------------------------------------------------------------------

	public function validate_url(&$atts)
	{
		if (self::emptyOK($atts))
		{
			return true;
		}

		if (!SparkUtil::valid_url($atts['val']))
		{
			return false;
		}
		
		$atts['val'] = rtrim($atts['val'], '/');
		
		return true;
	}

	//---------------------------------------------------------------------------

	public function path($atts)
	{
		return $this->text($atts);
	}

	//---------------------------------------------------------------------------

	public function validate_path(&$atts)
	{
		if (self::emptyOK($atts))
		{
			return true;
		}

		$atts['validation'] = '/^(\/[a-z0-9;@&=\$\-\_\.\+\!\*\'\(\)\,]*)*$/i';

		if (!$this->validate_text($atts))
		{
			return false;
		}
		
		if (!empty($atts['val']))
		{
			$atts['val'] = '/' . trim($atts['val'], '/');
		}
		
		return true;
	}

	//---------------------------------------------------------------------------

	public function slug($atts)
	{
		return $this->text($atts);
	}

	//---------------------------------------------------------------------------

	public function validate_slug(&$atts)
	{
		if (self::emptyOK($atts))
		{
			return true;
		}

		$atts['validation'] = ContentObject::slugRegex();

		if (!$this->validate_text($atts))
		{
			return false;
		}
		
		$atts['val'] = ContentObject::filterSlug($atts['val']);
		return true;
	}

	//---------------------------------------------------------------------------

	public function timezone($atts)
	{
		$timezones = self::getTimeZones();

		$val = $atts['val'];
		$disabled = isset($atts['disabled']);
		$atts = array('name'=>$atts['name'], 'id'=>$atts['name']);
		if ($disabled)
		{
			$atts['disabled'] = 'disabled';
		}
		$html = '<select'. $this->matts($atts). '><option value="0">None</option>';
		
		foreach($timezones as $id => $name)
		{
			$selected = ($id == $val) ? ' selected="selected"' : '';
			$html .= '<option value="'.$id.'"' . $selected . '>' . str_replace('  ', '&nbsp;&nbsp;', SparkView::escape_html($name)) . '</option>';
		}
	
		$html .= '</select>';

		return $html;
	}

	//---------------------------------------------------------------------------

	public function validate_timezone(&$atts)
	{
		if (empty($atts['val']))
		{
			return true;
		}
		
		$timezones = self::getTimeZones();

		return isset($timezones[$atts['val']]);
	}

	//---------------------------------------------------------------------------

	public function theme($atts)
	{
		$model = $this->newModel('AdminContent');
		$themes = $model->fetchThemeNames(true);

		$val = $atts['val'];
		$disabled = isset($atts['disabled']);
		$atts = array('name'=>$atts['name'], 'id'=>$atts['name']);
		if ($disabled)
		{
			$atts['disabled'] = 'disabled';
		}
		$html = '<select'. $this->matts($atts). '><option value="0">None</option>';
		
		foreach($themes as $id => $name)
		{
			$selected = ($id == $val) ? ' selected="selected"' : '';
			$html .= '<option value="'.$id.'"' . $selected . '>' . str_replace('  ', '&nbsp;&nbsp;', SparkView::escape_html($name)) . '</option>';
		}
	
		$html .= '</select>';

		return $html;
	}

	//---------------------------------------------------------------------------

	public function validate_theme(&$atts)
	{
		if (empty($atts['val']))
		{
			return true;
		}
		
		$model = $this->newModel('AdminContent');
		$themes = $model->fetchThemeNames(false);

		return isset($themes[$atts['val']]);
	}

	//---------------------------------------------------------------------------

	public function getCallbacks(&$callbacks)
	{
		// Create dictionary of callbacks into markup generator.
		// The extra level of indirection serves two purposes:
		//   1. Increased security
		//   2. A simple way for plugins to inject custom markup handlers

		$callbacks['text'] = array($this, 'text');
		$callbacks['password'] = array($this, 'password');
		$callbacks['textarea'] = array($this, 'textarea');
		$callbacks['yesnoradio'] = array($this, 'yesnoradio');
		$callbacks['checkbox'] = array($this, 'checkbox');
		$callbacks['select'] = array($this, 'select');
		$callbacks['integer'] = array($this, 'integer');
		$callbacks['numeric'] = array($this, 'numeric');
		$callbacks['email'] = array($this, 'email');
		$callbacks['url'] = array($this, 'url');
		$callbacks['path'] = array($this, 'path');
		$callbacks['slug'] = array($this, 'slug');
		$callbacks['timezone'] = array($this, 'timezone');
		$callbacks['theme'] = array($this, 'theme');
	}

	//---------------------------------------------------------------------------

	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected final function matts($atts)
	{
		$matts = '';

		foreach ($atts as $key=>$val)
		{
			if ($val != '')
			{
				$matts .= ' ' . $key . '="' . SparkView::escape_html($val) . '"';
			}
		}
		
		return $matts;
	}

	//---------------------------------------------------------------------------

	// Private Methods
	
	//---------------------------------------------------------------------------

	private static function emptyOK($atts)
	{
		if ($atts['val'] === '')
		{
			return (strpos($atts['validation'], 'optional') !== false);
		}
		
		return false;
	}

	//---------------------------------------------------------------------------

	private static function getTimeZones()
	{
		$zones = array();

		foreach (DateTimeZone::listIdentifiers() as $tz)
		{
			$zones[$tz] = $tz;
		}
		
		return $zones;
	}

	//---------------------------------------------------------------------------

}
