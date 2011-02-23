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

class _Output extends SparkPlug
{
	//---------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();
	}

	// --------------------------------------------------------------------

	public function escape($content, $encodeAll = false, $charset = 'UTF-8')
	{
		return SparkView::escape_html($content, $encodeAll, $charset);
	}

	//---------------------------------------------------------------------------
	
	public function tag($content, $tag, $class = '', $id = '', $atts = '')
	{
		if (!$tag)
		{
			return $content;
		}

		if ($id)
		{
			$atts .= ' id="'.$id.'"';
		}

		if ($class)
		{
			$atts .= ' class="'.$class.'"';
		}

		if ($atts && $atts[0] != ' ')
		{
			$atts = ' ' . $atts;
		}

		return $content !== NULL ? "<{$tag}{$atts}>{$content}</{$tag}>" : "<{$tag}{$atts} />";
	}

	//---------------------------------------------------------------------------
	
	public function wrap($list, $wraptag, $class = '', $id = '',  $atts = '', $breaktag = '', $breakclass = '', $breakatts = '')
	{
		if (!$list)
		{
			return '';
		}
		
		if ($breakclass)
		{
			$breakatts.= ' class="'.$breakclass.'"';
		}

		if ($breaktag == 'br' or $breaktag == 'hr')
		{
			if ($breakatts && $breakatts[0] != ' ')
			{
				$breakatts = ' ' . $breakatts;
			}
			$breaktag = "<{$breaktag}{$breakatts} />";
			return $wraptag ?	$this->tag(implode($breaktag, $list), $wraptag, $class, $id, $atts) : implode($breaktag, $list);
		}

		$content = $breaktag
			? $this->tag(implode("</{$breaktag}><{$breaktag}{$breakatts}>", $list), $breaktag, '', '', $breakatts)
			: implode(' ', $list)
			;
		
		return $wraptag ? $this->tag($content, $wraptag, $class, $id, $atts) : $content;
	}
	
	//---------------------------------------------------------------------------

	public function label($label, $for, $class = '', $atts = '')
	{
		return $this->tag($label, 'label', $class, '', $atts . " for=\"{$for}\"");
	}

	//---------------------------------------------------------------------------
}
