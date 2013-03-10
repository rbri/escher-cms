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

require('parser.php');

//------------------------------------------------------------------------------

class CoreTagParser extends Parser
{
	private $_page_vars;
	private $_randoms;
	private $_cycles;
	private $_error_code;
	private $_error_text;
	private $_error_status;
	private $_error_message;

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher)
	{
		parent::__construct($params, $cacher);

		$this->_page_vars[] = array(array(), false, false);
		$this->_randoms = NULL;
		$this->_cycles = NULL;
		$this->_error_code = '';
		$this->_error_text = '';
		$this->_error_status = '';
		$this->_error_message = '';
	}

	//---------------------------------------------------------------------------

	public function makeQueryString($params)
	{
		$vars = array();

		foreach ($params as $key=>$val)
		{
			$vars[] = urlencode($key) . '=' . urlencode($val);
		}
		
		return empty($vars) ? '' : '?' . implode('&amp;', $vars);
	}

	//---------------------------------------------------------------------------
	
	// Protected Methods
	
	//---------------------------------------------------------------------------

	protected final function setStatus($status, $message = '')
	{
		static $statusMessages = array
		(
			'200' => 'OK',
			'301' => 'Moved Permanently',
			'302' => 'Found',
			'304' => 'Not Modified',
			'307' => 'Temporary Redirect',
			'401' => 'Unauthorized',
			'403' => 'Forbidden',
			'404' => 'Not Found',
			'410' => 'Gone',
			'414' => 'Request-URI Too Long',
			'500' => 'Internal Server Error',
			'501' => 'Not Implemented',
			'503' => 'Service Unavailable',
		);
		
		$code = trim($status);
		
		if (!is_numeric($code))
		{
			$code = '500';
		}
		
		$status = $code;
		$text = '';

		if (isset($statusMessages[$code]))
		{
			$text = $statusMessages[$code];
			$status .= (' ' . $text);

			if ($message === '')
			{
				$message = $text;
			}
		}

		$this->_error_code = $code;
		$this->_error_text = $text;
		$this->_error_status = $status;
		$this->_error_message = $message;
		
		$protocol = !empty($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1 ';
		
		header($protocol . ' ' . $status);
	}

	//---------------------------------------------------------------------------

	protected final function setErrorMessage($message)
	{
		$this->_error_message = $message;
	}

	//---------------------------------------------------------------------------
	//
	// Here there be tags...
	//
	//---------------------------------------------------------------------------
	
	//---------------------------------------------------------------------------
	// User Tags ("user" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_user()
	{
		$this->pushNamespace('user');
		return true;
	}
		
	protected function _xtag_ns_user()
	{
		$this->popNamespace('user');
	}
		
	//---------------------------------------------------------------------------
	// Core Tags ("core" namespace)
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_core()
	{
		$this->pushNamespace('core');
		return true;
	}
		
	protected function _xtag_ns_core()
	{
		$this->popNamespace('core');
	}
		
	//---------------------------------------------------------------------------
	// Variable Helpers
	//---------------------------------------------------------------------------
	
	protected final function &find_var($name, $update = NULL, $default = NULL)
	{
		$top = count($this->_page_vars) - 1;
		$topScope =& $this->_page_vars[$top];
		$scope =& $topScope;
		
		for (;;)
		{
			if (isset($scope[0][$name]))
			{
				$result =& $scope[0][$name];
				if ($update !== NULL)
				{
					$result = $update;
				}
				return $result;
			}
			if ($scope[1])		// stop searching if this is a local scope
			{
				break;
			}
			if (--$top < 0)		// stop searching if no more scopes
			{
				break;
			}
			
			// check parent scope
			
			unset($scope);
			$scope =& $this->_page_vars[$top];
			
			if ($scope[2])		// stop searching if this is a private scope
			{
				break;
			}
		}
		
		// var not found, so create it if requested to do so
		
		if ($default !== NULL)
		{
			$result =& $topScope[0][$name];
			$result = $default;
			return $result;
		}
		
		$result = NULL;
		return $result;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_var($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'default' => '',
			'value' => $this->getContent(),
			'return' => NULL,
			'trim' => false,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'var')));

		if ($return === NULL)
		{
			$return = ($value === NULL);
		}
		
		if ($trim && ($value !== NULL))
		{
			$value = trim($value);
		}

		$var = $this->find_var($name, $value, $value);
		
		return $this->truthy($return) ? (($var !== NULL) ? ($trim ? trim($var) : $var) : $default) : '';
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_var($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'value' => NULL,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'if_var')));

		$var = $this->find_var($name);

		if ($var !== NULL)
		{
			return ($value === NULL) ? true : ($value == $var);
		}
		
		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_inc_var($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'increment' => 1,
			'return' => false,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'if_var')));

		$var =& $this->find_var($name, NULL, 0);

		$var += $increment;
		
		return $this->truthy($return) ? $var : '';
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_scope($atts)
	{
		extract($this->gatts(array(
			'local' => false,
			'private' => false,
		),$atts));

		$this->_page_vars[] = array(array(), $this->truthy($local), $this->truthy($private));
		return true;
	}
	
	protected function _xtag_core_scope($atts)
	{
		array_pop($this->_page_vars);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_true($atts)
	{
		extract($this->gatts(array(
			'value' => NULL,
			'name' => NULL,
			'trim'  => true,
		),$atts));
		
		($value !== NULL) || $name || check($value || $name, $this->output->escape(self::$lang->get('attribute_required', 'value|name', 'if_true')));

		if ($value === NULL)
		{
			$value = $this->find_var($name);
		}

		if ($this->truthy($trim))
		{
			$value = trim($value);
		}

		return $this->truthy($value);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_false($atts)
	{
		extract($this->gatts(array(
			'value' => NULL,
			'name' => NULL,
			'trim'  => true,
		),$atts));
		
		($value !== NULL) || $name || check($value || $name, $this->output->escape(self::$lang->get('attribute_required', 'value|name', 'if_false')));

		if ($value === NULL)
		{
			$value = $this->find_var($name);
		}

		if ($this->truthy($trim))
		{
			$value = trim($value);
		}

		return $this->falsy($value);
	}

	//---------------------------------------------------------------------------
	// Misc Tags
	//---------------------------------------------------------------------------
	
	protected function _tag_core_discard($atts)
	{
		extract($this->gatts(array(
			'silent' => false,
		),$atts));
		
		if ($this->truthy($silent))
		{
			@$this->getContent();
		}
		else
		{
			$this->getContent();
		}
		return '';
	}

	//---------------------------------------------------------------------------

	protected function _tag_core_hide($atts)
	{
		return '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_empty($atts)
	{
		extract($this->gatts(array(
			'value' => NULL,
			'trim'  => true,
		),$atts));
		
		if ($value === NULL)
		{
			$value = $this->getContent();
		}

		if ($this->truthy($trim))
		{
			$value = trim($value);
		}

		return ($value === '');
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_not_empty($atts)
	{
		return !$this->_tag_core_if_empty($atts);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_different($atts)
	{
		$content = $this->getContent();
		$key = md5($t = $this->getRawContent());

		if (empty($this->tag_if_different[$key]) || $content != $this->tag_if_different[$key]) 
		{
			$this->tag_if_different[$key] = $content;
			return true;
		}
		
		return false;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_replace($atts)
	{
		extract($this->gatts(array(
			'from'  => '',
			'to'    => '',
			'delim' => ',',
		),$atts));
		
		$from = $this->glist($from, $delim);
		$to = $this->glist($to, $delim);
		
		return str_replace($from, $to, $this->getContent());
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_trim($atts)
	{
		return trim($this->getContent());
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_downcase($atts)
	{
		return strtolower($this->getContent());
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_upcase($atts)
	{
		return strtoupper($this->getContent());
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_capitalize($atts)
	{
		return ucwords(strtolower($this->getContent()));
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_slice($atts)
	{
		extract($this->gatts(array(
			'id'  => 1,
			'delim' => ',',
		),$atts));
		
		--$id;
		$parts = explode($delim, $this->getContent());

		return isset($parts[$id]) ? $parts[$id] : '';
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_escape_html($atts)
	{
		return $this->output->escape($this->getContent());
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_escape_url($atts)
	{
		return urlencode($this->getContent());
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_random($atts)
	{
		$this->_randoms[] = array();
		return true;
	}
	
	protected function _xtag_core_random($atts)
	{
		$options = array_pop($this->_randoms);
		if ($numOptions = count($options))
		{
			return $this->parseParsable($options[rand(0, $numOptions-1)]);
		}
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_option($atts)
	{
		$top = count($this->_randoms) - 1;
		
		if ($top >= 0)
		{
			$this->_randoms[$top][] = $this->getParsable();
		}
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_cycle($atts)
	{
		extract($this->gatts(array(
			'values' => NULL,
			'name'   => 'cycle',
			'reset'  => false,
			'random' => false,
		),$atts));

		if ($values)
		{
			$values = $this->_cycles['name'][0] = array_map('trim', explode(',', $values));
		}
		elseif (isset($this->_cycles['name']))
		{
			$values = $this->_cycles['name'][0];
		}
		
		if ($values)
		{
			if ($this->truthy($random))
			{
				return $values[rand(0, count($values)-1)];
			}
			if ($this->truthy($reset) || (!isset($this->_cycles['name'][1])))
			{
				$index = $this->_cycles['name'][1] = 0;
			}
			else
			{
				$index = $this->_cycles['name'][1] = ($this->_cycles['name'][1] + 1) % count($values);
			}
			return $values[$index];
		}
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_core_redirect($atts)
	{
		extract($this->gatts(array(
			'target' => '',
		),$atts));

		$target || check($target, $this->output->escape(self::$lang->get('attribute_required', 'target', 'redirect')));

		$this->redirect($target);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_exit($atts)
	{
		extract($this->gatts(array(
			'status' => '503',
			'message' => '',
		),$atts));

		$this->setStatus($status, $message);
		throw new SparkHTTPException($this->_error_code, $this->_error_text, $this->_error_message, 0, $this->_error_status);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_error_code($atts)
	{
		return $this->output->escape($this->_error_code);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_error_status($atts)
	{
		if (($status = trim($this->getContent())) !== '')
		{
			$this->setStatus($status);
			return '';
		}

		return $this->output->escape($this->_error_status);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_error_message($atts)
	{
		if (($message = trim($this->getContent())) !== '')
		{
			$this->setErrorMessage($message);
			return '';
		}
	
		return $this->output->escape($this->_error_message);
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_if_match($atts)
	{
		extract($this->gatts(array(
			'pattern' => '',
			'subject' => '',
			'ignore_case' => false,
			'is_regex' => false,
			'quote_regex' => false,
		),$atts));

		($pattern !== '') || check(($pattern !== ''), $this->output->escape(self::$lang->get('attribute_required', 'pattern', 'if_match')));
		($subject !== '') || check(($subject !== ''), $this->output->escape(self::$lang->get('attribute_required', 'subject', 'if_match')));
		
		if ($this->truthy($is_regex))
		{
			if ($this->truthy($quote_regex))
			{
				$pattern = '/'.preg_quote($pattern, '/').'/';
			}
			if ($this->truthy($ignore_case))
			{
				$pattern .= 'i';
			}
			return preg_match($pattern, $subject) ? true : false;
		}
		elseif ($this->truthy($ignore_case))
		{
			return !strcasecmp($pattern, $subject) ? true : false;
		}
		else
		{
			return !strcmp($pattern, $subject) ? true : false;
		}
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_core_server_protocol($atts)
	{
		return SparkUtil::is_https() ? 'https:' : 'http:';
	}

	//---------------------------------------------------------------------------
	
}
