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

class _Input extends SparkPlug
{
	private $_get;
	private $_post;
	private $_cookie;
	private $_errors;
	
	//---------------------------------------------------------------------------

	public function __construct($get, $post, $cookie)
	{
		parent::__construct();
		$this->_get = $get;
		$this->_post = $post;
		$this->_cookie = $cookie;
		$this->_errors = array();
	}

	//---------------------------------------------------------------------------
	
	public function get($key, $default = NULL)
	{
		return isset($this->_get[$key]) ? $this->_get[$key] : $default;
	}

	//---------------------------------------------------------------------------
	
	public function post($key, $default = NULL)
	{
		return isset($this->_post[$key]) ? $this->_post[$key] : $default;
	}

	//---------------------------------------------------------------------------
	
	public function cookie($key, $default = NULL)
	{
		return isset($this->_cookie[$key]) ? $this->_cookie[$key] : $default;
	}

	//---------------------------------------------------------------------------
	
	public function getVars()
	{
		return $this->_get;
	}

	//---------------------------------------------------------------------------
	
	public function postVars()
	{
		return $this->_post;
	}

	//---------------------------------------------------------------------------
	
	public function cookieVars()
	{
		return $this->_cookie;
	}

	//---------------------------------------------------------------------------
	
	public function errors()
	{
		return $this->_errors;
	}

	//---------------------------------------------------------------------------
	
	public function hasGetVars()
	{
		return !empty($this->_get);
	}

	//---------------------------------------------------------------------------
	
	public function hasPostVars()
	{
		return !empty($this->_post);
	}

	//---------------------------------------------------------------------------
	
	public function hasCookies()
	{
		return !empty($this->_cookie);
	}

	//---------------------------------------------------------------------------
	
	public function hasErrors()
	{
		return !empty($this->_errors);
	}

	//---------------------------------------------------------------------------
	
	public function isError($name)
	{
		return isset($this->_errors[$name]);
	}

	//---------------------------------------------------------------------------
	
	public function validate($label, $name, $rule)
	{
		$value =& $this->_post[$name];
		
		if (empty($label))
		{
			$label = $name;
		}

		// if no associated rules, we are done
		
		if (empty($rule))
		{
			return $value;
		}
		
		$isEmpty = ($value === '') || ($value === NULL);
		
		// check rules
				
		$rules = $this->extractRules($rule);
		
		foreach ($rules as $rule)
		{
			// extract parameter, if any, from rule (default to label if no explicit parameter provided)
	
			$param = $label;
			if (preg_match('/^(.*?)\[(.*?)\]$/', $rule, $match))
			{
				$rule	= $match[1];
	
				// trim spaces to the left and right of any comma that is not preceeded by a backslash
	
				$param = trim(preg_replace('/\s*(?<!\\\\),\s*/', '$1,', $match[2]));
			}
			
			// "required" rules, if present, must be first in rule list!
			
			if ($isEmpty && (strpos($rule, 'required') === false))
			{
				return '';
			}
			
			if (method_exists($this, $method = "validate_{$rule}"))
			{
				$result = $this->$method($value, $param);
				
				if ($result === false)
				{
					$this->_errors[$name] = self::$lang->get($rule, $label, $param);
				}
				elseif ($result !== true)
				{
					$value = $result;
				}
			}
		}

		return $value;
	}

	//---------------------------------------------------------------------------
	
	public function validate_required($item, $param)
	{
		return ($item !== NULL) && ($item !== '');
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_required_if($item, $param)
	{
		$other = @$this->_post[$param];
		return ($other === NULL) || ($other === '') || (($item !== NULL) && ($item !== ''));
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_regex($item, $param)
	{
		return preg_match($param, $item) ? true : false;
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_match($item, $param)
	{
		return ($item === @$this->_post[$param]);
	}
	
	//---------------------------------------------------------------------------

	public static function validate_cookie($item)
	{
		return isset($this->_cookie[$item]);
	}
	
	//---------------------------------------------------------------------------

	public static function validate_length($item, $param)
	{
		if (!is_numeric($param))
		{
			return false;
		}
		return (strlen($item) == $param);
	}

	//---------------------------------------------------------------------------

	public static function validate_length_min($item, $param)
	{
		if (!is_numeric($param))
		{
			return false;
		}
		return (strlen($item) >= $param);
	}

	//---------------------------------------------------------------------------

	public static function validate_length_max($item, $param)
	{
		if (!is_numeric($param))
		{
			return false;
		}
		return (strlen($item) <= $param);
	}

	//---------------------------------------------------------------------------

	public static function validate_length_range($item, $param)
	{
		if ($param === '')
		{
			return false;
		}
		$param = explode(',', $param);
		if (count($param) != 2)
		{
			return false;
		}
		if (!self::validate_numeric($param[0]) || !self::validate_numeric($param[1]))
		{
			return false;
		}
		$length = strlen($item);
		$min = intval($param[0]);
		$max = intval($param[1]);
		
		return ($min <= $length) && ($length <= $max);
	}

	//---------------------------------------------------------------------------

	public static function validate_alpha($item)
	{
		return ctype_alpha($item);
	}

	//---------------------------------------------------------------------------

	public static function validate_numeric($item)
	{
		return ctype_digit($item);
	}

	//---------------------------------------------------------------------------

	public static function validate_alphanum($item)
	{
		return ctype_alnum($item);
	}

	//---------------------------------------------------------------------------

	public static function validate_nonzero($item)
	{
		if (!is_numeric($item))
		{
			return false;
		}
		return ($item != 0);
	}

	//---------------------------------------------------------------------------

	public static function validate_notempty($item)
	{
		return !empty($item);
	}

	//---------------------------------------------------------------------------
	
	public function validate_equal($item, $param)
	{
		return ($item === $param);
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_not_equal($item, $param)
	{
		return ($item !== $param);
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_in_list($item, $param)
	{
		if ($param === '')
		{
			return false;
		}
		
		// temporarily replace any escaped commas, so we can use simple explode function with comma separator
		
		$param = str_replace('\,', '{#}', $param);

		// separate parameters

		$param = explode(',', $param);

		// restore commas
		
		foreach (array_keys($param) as $key)
		{
			$param[$key] = str_replace('{#}', ',', $param[$key]);
		}

		if (is_array($item))
		{
			$sect = array_intersect($item, $param);
			return (count($sect) == count($item));
		}

		return in_array($item, $param);
	}
	
	// --------------------------------------------------------------------

	public static function validate_name($item)
	{
		return preg_match('/^([a-z0-9_-])+$/i', $item) ? true : false;
	}

	// --------------------------------------------------------------------

	public static function validate_currency($item)
	{
		return preg_match('/^\$?([0-9]{1,3}(,?[0-9]{3})*)(\.[0-9]{2})?$/', $item) ? true : false;
	}

	// --------------------------------------------------------------------

	public static function validate_currency_min($item, $param)
	{
		if (!self::validate_currency($item))
		{
			return false;
		}
		if (!is_numeric($param))
		{
			return false;
		}
		$amount = str_replace(array('$', ','), '', $item);
		$min = floatval($param);
		
		return ($amount >= $min);
	}

	// --------------------------------------------------------------------

	public static function validate_currency_max($item, $param)
	{
		if (!self::validate_currency($item))
		{
			return false;
		}
		if (!is_numeric($param))
		{
			return false;
		}
		$amount = str_replace(array('$', ','), '', $item);
		$max = floatval($param);
		
		return ($amount <= $max);
	}

	// --------------------------------------------------------------------

	public static function validate_currency_range($item, $param)
	{
		if (!self::validate_currency($item))
		{
			return false;
		}
		if ($param === '')
		{
			return false;
		}
		$param = explode(',', $param);
		if (count($param) != 2)
		{
			return false;
		}
		if (!is_numeric($param[0]) || !is_numeric($param[1]))
		{
			return false;
		}
		$amount = str_replace(array('$', ','), '', $item);
		$min = floatval($param[0]);
		$max = floatval($param[1]);
		
		return ($min <= $amount) && ($amount <= $max);
	}

	// --------------------------------------------------------------------

	public static function validate_username($item)
	{
		$len = strlen($item);
	
		if (($len < 6) || ($len > 15))
		{
			return false;
		}
		
		if (!ctype_alpha($item[0]))
		{
			return false;
		}
		
		return self::validate_alphanum($item);
	}
		
	// --------------------------------------------------------------------

	public static function validate_password($item)
	{
		$len = strlen($item);
	
		if ($len < 8)
		{
			return false;
		}
		
		$numAlpha = 0;
		$numDigit = 0;
		$numSpecial = 0;
		
		for ($i = 0 ; $i < $len; ++$i)
		{
			$char = $item[$i];
			if (ctype_digit($char))
			{
				++$numDigit;
			}
			elseif (ctype_alpha($char))
			{
				++$numAlpha;
			}
			else
			{
				++$numSpecial;
			}
		}
	
		if ($numAlpha < 2)
		{
			return false;
		}
		
		if ($numDigit + $numSpecial < 2)
		{
			return false;
		}
		
		return true;
	}

	//---------------------------------------------------------------------------
	
	public function validate_email($item, $param)
	{
		return SparkUtil::valid_email($item);
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_url($item, $param)
	{
		return SparkUtil::valid_url($item, true);
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_web($item, $param)
	{
		return SparkUtil::valid_url($item, false);
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_date($item, $param)
	{
		if (!preg_match('#\d\d/\d\d/\d\d\d\d#', $item))
		{
			return false;
		}
		
		if ($param === 'future')
		{
			$date = substr($item, 6, 4) . substr($item, 0, 2) . substr($item, 3, 2);
			return ($date >= gmdate('Ymd'));
		}
		
		return true;
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_zip_code($item, $param)
	{
		$usRegEx = '[[:digit:]]{5}(-[[:digit:]]{4})?';
		$canadianRegEx = '[[:alpha:]][[:digit:]][[:alpha:]] [[:digit:]][[:alpha:]][[:digit:]]';
		$regEx = "/^(({$usRegEx})|({$canadianRegEx}))$/";

		return preg_match($regEx, $item) ? true : false;
	}
	
	//---------------------------------------------------------------------------
	
	public function validate_phone($item)
	{
		$item = preg_replace('/\s+/', ' ', $item);
		return preg_match('/^(?:\(\d{3}\)\ ?|\d{3}(?:\-?|\ ?))\d{3}[- ]?\d{4}(?:\s*x\s*\d+)?$/', $item) ? $item : false;
	}
	
	//---------------------------------------------------------------------------

	private function extractRules($rules)
	{
		// first determine if we can take the easy route (no regex rules)
	
		$hasRegEx = (strpos($rules, 'regex[') !== false);
		
		$rules = explode('|', $rules);
		
		if (!$hasRegEx)
		{
			return $rules;
		}
		
		// rejoin regex rules that were split into pieces because they contained the '|' character
		
		$result = array();
		$regex = false;
		
		foreach ($rules as $rule)
		{
			if ($regex)
			{
				$regex .= '|' . $rule;
				if (strpos($rule, $end) == strlen($rule)-2)
				{
					$result[] = $regex;
					$regex = false;
				}
				continue;
			}
		
			if (strncmp('regex', $rule, 5))
			{
				$result[] = $rule;
				continue;
			}
			
			$end = $rule[6] . ']';		// delimiter + closing bracket signifies end of regex

			if (strpos($rule, $end) == strlen($rule)-2)
			{
				$result[] = $rule;
				continue;
			}

			$regex = $rule;
		}

		return $result;
	}

	//---------------------------------------------------------------------------
	
}
