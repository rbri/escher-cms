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

require('input.php');
require('output.php');
require('util.php');

//------------------------------------------------------------------------------

class Parser extends SparkPlug
{
	// main tag parsing regex pattern adapted from the lovely Textpattern

	const find_tags_regex = '@(</?et(?::\w+)+(?:\s+\w+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))*\s*/?>)@s';
	const split_tag_regex = '@:((?:\w+:?)+)(.*)/?>$@s';
	const split_atts_regex = '@(\w+)\s*=\s*(?:"((?:[^"]|"")*)"|\'((?:[^\']|\'\')*)\'|([^\s\'"/>]+))@s';

	private $_environment;

	private $_parse_stack;
	private $_namespace_stack;
	private $_raw_content_stack;
	private $_content_stack;

	protected $cacher;
	protected $input;
	protected $output;


	//---------------------------------------------------------------------------

	public function __construct($params, $cacher)
	{
		parent::__construct();

		$this->_environment = $params['env'];
		
		$this->_parse_stack = array();
		$this->_namespace_stack = array();
		$this->_raw_content_stack = array();
		$this->_content_stack = array();

		$this->cacher = $cacher;
		$this->input = $this->factory->manufacture('Input', (array)$params['qv'], (array)$params['pv'], (array)$params['cv']);
		$this->output = $this->factory->manufacture('Output');
		
		self::$lang->load('parse');

		$this->pushNamespace('core');
	}

	//---------------------------------------------------------------------------
	//
	// Public Entry Point
	//
	//---------------------------------------------------------------------------
	
	public final function parse($content)
	{
		$this->_parse_stack[] = $parsed = preg_split(self::find_tags_regex, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
		$out = $this->parseRange(0, count($parsed)-1);
		array_pop($this->_parse_stack);
		return $out;
	}

	//---------------------------------------------------------------------------
	
	// Private Methods
	
	//---------------------------------------------------------------------------
	
	private final function parseRange($start, $stop)
	{
		if ($stop < $start)
		{
			return '';
		}

		$parsed = end($this->_parse_stack);
		
		$level = 0;
		$out = '';
		$innerStart = $innerStop = $start;
		$innerElse = NULL;
		$isTag = true;

		for ($i = $start; $i <= $stop; ++$i)
		{
			$chunk =& $parsed[$i];
			
			if ($isTag = !$isTag)
			{
				if (!$level)
				{
					preg_match(self::split_tag_regex, $chunk, $tag);

					if (substr($chunk, -2, 1) === '/')
					{
						$out .= $this->processTag($tag[1], $tag[2]);
					}
					else
					{
						++$level;
						$innerStart = $i+1;
					}
				}
				elseif (substr($chunk, 1, 1) === '/')
				{
					if (--$level === 0)
					{
						if ($chunk != "</et:{$tag[1]}>")
						{
							$this->reportError(self::$lang->get('missing_closing_tag', "</et:{$tag[1]}>"));
						}
						$inner = ($innerElse === NULL) ? array($innerStart, $i-1) : array($innerStart, $innerElse-1, $innerElse+1, $i-1);
						$out .= $this->processTag($tag[1], $tag[2], $inner);
						$innerElse = NULL;
					}
				}
				elseif (substr($chunk, -2, 1) !== '/')
				{
					++$level;
				}
				elseif (($level === 1) && ($innerElse === NULL) && ($chunk === '<et:else />'))
				{
					$innerElse = $i;
				}
			}
			elseif (!$level)
			{
				$out .= $chunk;
			}
		}
		
		if ($level)
		{
			$this->reportError(self::$lang->get('missing_closing_tag', "</et:{$tag[1]}>"));
		}

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	private final function fetchRange($start, $stop)
	{
		if ($stop < $start)
		{
			return '';
		}

		$parsed = end($this->_parse_stack);
		
		return implode('', array_slice($parsed, $start, $stop-$start+1));
	}
	
	//---------------------------------------------------------------------------
	
	private final function findTagMethod($tag)
	{
		// if tag is fully scoped, don't bother searching the namespace stack
		
		if (strpos($tag, ':') !== false)
		{
			$tag = str_replace(':', '_', $tag);
			$method = '_tag_' . $tag;
			return method_exists($this, $method) ? $method : false;
		}

		// otherwise, search through current namespaces for the method and build its name
		
		for ($nsIndex = count($this->_namespace_stack) - 1; $nsIndex >= 0; --$nsIndex)
		{
			$namespace = $this->_namespace_stack[$nsIndex];
			$method = '_tag_' . $namespace . '_' . $tag;

			if (method_exists($this, $method))
			{
				return $method;
			}
		}
		
		// not found in any namespace
		
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	private final function processTag($tag, $atts, $inner = NULL)
	{
		if ($method = $this->findTagMethod($tag))
		{
			$atts = $this->parseAttributes($atts);

			$this->_raw_content_stack[] = $inner;
			$this->_content_stack[] = $inner;
			$out = $this->$method($atts);
			$content = array_pop($this->_content_stack);
			array_pop($this->_raw_content_stack);

			// a boolean result indicates whether to include the inner content (true) or the else content (false)

			if (is_bool($out))
			{
				if ($out)
				{
					$out = is_string($content) ? $content : $this->parseRange($inner[0], $inner[1]);
				}
				else
				{
					$out = (count($inner) != 4) ? '' : $this->parseRange($inner[2], $inner[3]);
				}
			}
			
			// allow tag to clean up after itself if necessary

			if ($inner && method_exists($this, $method = '_x' . ltrim($method, '_')))
			{
				$extra = $this->$method($atts);
				if (!empty($extra))
				{
					$out .= $extra;
				}
			}
		}
		else
		{
			$out = '';
			if ($tag === 'else')
			{
				$this->reportError(self::$lang->get('misplaced_else'), E_USER_WARNING);
			}
			else
			{
				$this->reportError(self::$lang->get('unknown_tag', "<et:{$tag}>"), E_USER_WARNING);
			}
		}
		
		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	private final function parseAttributes($str)
	{
		$atts = array();

		if (preg_match_all(self::split_atts_regex, $str, $match, PREG_SET_ORDER))
		{
			foreach ($match as $m)
			{
				switch (count($m))
				{
					case 3:
						$val = str_replace('""', '"', $m[2]);
						break;

					case 4:
						$val = str_replace("''", "'", $m[3]);
						if (strpos($m[3], '<et:') !== false)
						{
							$val = $this->parse($val);
						}
						break;

					case 5:
						$val = $m[4];
						$this->reportError(self::$lang->get('attribute_values_must_be_quoted'), E_USER_WARNING);
						break;
				}
				$atts[strtolower($m[1])] = $val;
			}
		}

		return $atts;
	}
		
	//---------------------------------------------------------------------------
	
	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected static function rtrimslash($str)
	{
		return rtrim($str, '/');
	}

	//---------------------------------------------------------------------------

	protected final function getEnvironment()
	{
		return $this->_environment;
	}

	//---------------------------------------------------------------------------

	protected final function dup(&$stack)
	{
		if (($top = end($stack)) !== NULL)
		{
			$stack[] = $top;
		}
	}

	//---------------------------------------------------------------------------

	protected final function pushNamespace($ns)
	{
		if (!is_string($ns))
		{
			$this->reportError(self::$lang->get('namespace_must_be_string'), E_USER_WARNING);
		}
		$this->_namespace_stack[] = $ns;
	}

	//---------------------------------------------------------------------------

	protected final function popNamespace($ns)
	{
		if ((count($this->_namespace_stack) === 1) || (array_pop($this->_namespace_stack) !== $ns))
		{
			$this->reportError(self::$lang->get('namespace_pop_error'), E_USER_WARNING);
		}
	}

	//---------------------------------------------------------------------------

	protected final function hasContent()
	{
		// Tags call this method if they need to determine whether they are being used as a single tag
		// or as a container tag.
		
		return end($this->_content_stack) !== NULL;
	}

	//---------------------------------------------------------------------------

	protected final function getContent($cacheIt = true)
	{
		// Tags call this method if they need to retrieve their inner content.
		// We parse it for them and, if $cacheIt==true, replace the top of the stack with the parsed content.
		// This prevents multiple passes through the parser for tags that call getContent() more than once.
		
		$content = end($this->_content_stack);
		if (is_array($content))
		{
			$content = $this->parseRange($content[0], $content[1]);
			if ($cacheIt)
			{
				$this->_content_stack[count($this->_content_stack)-1] = $content;
			}
		}
		return $content;
	}

	//---------------------------------------------------------------------------

	protected final function getRawContent()
	{
		// Tags call this method if they need to retrieve their raw (unparsed) inner content
		// as text.
		
		$content = end($this->_raw_content_stack);
		return $this->fetchRange($content[0], $content[1]);
	}

	//---------------------------------------------------------------------------

	protected final function getParsable()
	{
		// Tags call this method if they need to retrieve their raw (unparsed) inner content
		// as a parsable object.
		
		return end($this->_raw_content_stack);
	}

	//---------------------------------------------------------------------------
	
	protected final function parseParsable($parsable)
	{
		return $this->parseRange($parsable[0], $parsable[1]);
	}
	
	//---------------------------------------------------------------------------

	protected final function reportError($error, $level = E_USER_ERROR)
	{
		trigger_error($this->output->escape($error), $level);
	}

	//---------------------------------------------------------------------------

	protected final function genv($name)
	{
		return isset($this->_environment[$name]) ? $this->_environment[$name] : '';
	}

	//---------------------------------------------------------------------------
	
	protected final function glist($str, $delim = ',')
	{
  		return array_map('trim', explode($delim, str_replace(array('\r','\n','\t','\s'), ' ', $str)));
	}
	
	//---------------------------------------------------------------------------
	
	protected final function kvlist($str, $delim = ',', $kvdelim = ';')
	{
		$result = array();
  		foreach ($this->glist($str, $delim) as $key => $val)
  		{
			if (($pos = strpos($val, $kvdelim)) !== false)
			{
				$key = trim(substr($val, 0, $pos));
				$val = trim(substr($val, $pos+1));
			}
			$result[$key] = $val;
  		}
  		return $result;
	}
	
	//---------------------------------------------------------------------------

	protected final function gatts($defaults, $atts, $warn = true)
	{
		foreach($atts as $name => $value)
		{
			if (array_key_exists($name, $defaults))
			{
				$defaults[$name] = $value;
			}
			elseif ($warn)
			{
				$this->reportError(self::$lang->get('unknown_attribute', $name), E_USER_WARNING);
			}
		}
		
		return $defaults;
	}
	
	//---------------------------------------------------------------------------

	protected final function matts($atts, $dropIfEmpty = false)
	{
		$matts = '';

		foreach ($atts as $key=>$val)
		{
			if (!$dropIfEmpty || !empty($val))
			{
				$matts .= " {$key}=\"{$val}\"";
			}
		}
		
		return $matts;
	}

	//---------------------------------------------------------------------------

	protected final function truthy($val)
	{
		if ($val === false)
		{
			return false;
		}
		
		return
		(
			($val === true) ||
			($val === 1) ||
			($val === '1') ||
			($val === 'true') ||
			($val === 'yes') ||
			($val === 't') ||
			($val === 'y')
		);
	}

	//---------------------------------------------------------------------------

	protected final function falsy($val)
	{
		if ($val === true)
		{
			return false;
		}
		
		return
		(
			($val === false) ||
			($val === 0) ||
			($val === '0') ||
			($val === 'false') ||
			($val === 'no') ||
			($val === 'f') ||
			($val === 'n')
		);
	}

}
