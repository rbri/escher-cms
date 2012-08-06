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

//------------------------------------------------------------------------------

class Form
{
	public $id;
	public $group;
	public $errors;
	public $data;

	//---------------------------------------------------------------------------

	public function __construct($id, $group)
	{
		$this->id = $id;
		$this->group = $group;
		$this->errors = array();
	}
}

//------------------------------------------------------------------------------

class FormTags extends EscherParser
{
	private $_forms;			// array of all forms created for the current page, indexed by id
	private $_open_forms;	// currenty open (nested) forms
	private $_form_stack;	// form context stack

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);

		$this->_form_stack = array(new Form(NULL, NULL));	// push an unused sentinel to avoid constant checks for non-NULL current form

		$info = $this->factory->getPlug('FormTags');
		$langDir = dirname($info['file']) . '/languages';
		self::$lang->load('form', $langDir);
	}
	
	//---------------------------------------------------------------------------
	// Form Helpers
	//---------------------------------------------------------------------------
	
	protected final function pushForm($form)
	{
		if (!($form instanceof Form))
		{
			$this->reportError(self::$lang->get('not_a_form'), E_USER_WARNING);
			return;
		}
		
		$this->_form_stack[] = $form;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popForm()
	{
		if (count($this->_form_stack) > 1)	// don't pop sentinel!
		{
			array_pop($this->_form_stack);
		}
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentForm()
	{
		return end($this->_form_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function inNestedForm()
	{
		return count($this->_form_stack) > 2;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function findForm($id)
	{
		return @$this->_forms[$id];
	}
	
	//---------------------------------------------------------------------------
	
	protected final function fsubmitted()
	{
		return $this->input->post('esc_submitted');
	}
	
	//---------------------------------------------------------------------------
	
	protected final function fgroup()
	{
		return $this->input->post('esc_group');
	}
	
	//---------------------------------------------------------------------------
	
	protected final function fback()
	{
		return $this->input->post('esc_back');
	}
	
	//---------------------------------------------------------------------------
	
	protected final function fnext()
	{
		return $this->input->post('esc_next');
	}
	
	//---------------------------------------------------------------------------

	protected final function fsub()
	{
		return ($fsubmitted = $this->fsubmitted()) && ($this->currentForm()->id === $fsubmitted);
	}

	//---------------------------------------------------------------------------

	protected final function fvisited($id = NULL)
	{
		if (!$visited = $this->input->post('esc_visited'))
		{
			return false;
		}
		
		if ($id === NULL)
		{
			$id = $this->currentForm()->id;
		}

		return is_array($visited) ? in_array($id, $visited) : ($id === $visited);
	}

	//---------------------------------------------------------------------------

	protected final function fskip($conditional, &$submitted)
	{
		$submitted = false;

		if (!$fsubmitted = $this->fsubmitted())
		{
			return false;
		}
		
		// skip processing a form unless:
		// 1) the current form was submitted (and therefore needs validation), or
		// 2) the current form is the target of a "back" operation (in case we display but do not validate), or
		// 3) the current form is the target of a "next" operation (in case we display but do not validate)
		
		$currentFormID = $this->currentForm()->id;

		if ($currentFormID === $fsubmitted)
		{
			$submitted = true;
			return false;
		}
		
		// non-conditional forms are never skipped

		if (!$conditional)
		{
			return false;
		}
		
		// an unrelated form (not part of this form group) should not be skipped

		if ($this->currentForm()->group !== $this->fgroup())
		{
			return false;
		}
		
		if (($fsubmitted = $this->fback()) && ($currentFormID === $fsubmitted))
		{
			return false;
		}
		
		if (($fsubmitted = $this->fnext()) && ($currentFormID === $fsubmitted))
		{
			return false;
		}

		return true;
	}

	//---------------------------------------------------------------------------

	protected final function varsAvailable()
	{
		if (!$fsubmitted = $this->fsubmitted())
		{
			return false;
		}

		$currentFormID = $this->currentForm()->id;

		return
			($currentFormID === $fsubmitted) ||
			(($fsubmitted = $this->fback()) && ($currentFormID === $fsubmitted)) ||
			(($fsubmitted = $this->fnext()) && ($currentFormID === $fsubmitted));
	}

	//---------------------------------------------------------------------------

	protected final function gfvar($name, $default = NULL)
	{
		if (!$this->varsAvailable())
		{
			return NULL;
		}
		
		$val = $this->input->post($name, $default);
		
		if (($val === '') && !$this->fvisited())
		{
			$val = $default;
		}
		
		return $val;
	}

	//---------------------------------------------------------------------------

	protected final function gfvaralt($name, $default = NULL)
	{
		if (($val = $this->gfvar($name)) === NULL)
		{
			return $default;
		}
		
		$currentForm = $this->currentForm();

		if (isset($currentForm->data['options'][$name]))
		{
			$options =& $currentForm->data['options'][$name];
			
			if (!is_array($val))
			{
				return isset($options[$val]) ? $options[$val] : $val;
			}
			
			$alt = array();
			foreach ($val as $key)
			{
				$alt[$key] = isset($options[$key]) ? $options[$key] : $key;
			}
			return $alt;
		}

		return $val;
	}

	//---------------------------------------------------------------------------

	protected final function gfval($name, $alt = false, $default = NULL)
	{
		$val = $alt ? $this->gfvaralt($name, $default) : $this->gfvar($name, $default);

		if (is_array($val))
		{
			return array_map(array($this->output, 'escape'), $val);
		}

		return $this->output->escape($val);
	}

	//---------------------------------------------------------------------------

	protected final function sferr($name, $err)
	{
		$currentForm = $this->currentForm();
		$currentForm->errors[$name] = $err;
	}

	//---------------------------------------------------------------------------

	protected final function gferr($name)
	{
		$currentForm = $this->currentForm();
		if (isset($currentForm->errors[$name]))
		{
			return $this->output->escape($currentForm->errors[$name]);
		}
		return '';
	}

	//---------------------------------------------------------------------------

	protected final function gferrs()
	{
		// specially named error '*' gets displayed first
		
		$currentForm = $this->currentForm();

		if (isset($currentForm->errors['*']))
		{
			$firstErr = $currentForm->errors['*'];
			unset($currentForm->errors['*']);
			array_unshift($currentForm->errors, $firstErr);
		}
	
		return $currentForm->errors;
	}
	
	//---------------------------------------------------------------------------

	protected static function makeInListRule($value)
	{
		// construct an inlist rule for one or more values, escaping all commas as requried
		
		if (is_array($value))
		{
			foreach (array_keys($value) as $key)
			{
				$value[$key] = str_replace(',', '\,', $value[$key]);
			}
			return 'inlist[' . implode($value, ',') . ']';
		}
		else
		{
			return 'inlist[' . str_replace(',', '\,', $value) . ']';
		}
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_form()
	{
		$this->pushNamespace('form');
		return true;
	}
		
	protected function _xtag_ns_form()
	{
		$this->popNamespace('form');
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_form($atts)
	{
		extract($this->gatts(array(
			'id' => '',
		),$atts));

		$id || check($id, $this->output->escape(self::$lang->get('attribute_required', 'id', 'form:form')));

		if (!$form = $this->findForm($id))
		{
			$this->dup($this->_form_stack);
			$this->reportError(self::$lang->get('form_not_found', $id), E_USER_WARNING);
			return false;
		}

		$this->pushForm($form);
		return true;
	}
	
	protected function _xtag_form_form($atts)
	{
		$this->popForm();
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_form_open($atts, $conditional = false)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'name' => '',
			'action' => '',
			'method' => 'post',
			'group' => '',
			'nonce' => '1',
			'nonce_lifetime' => '86400',	// 24 hours
			'honeypot' => '1',
			'required_class' => 'required',
			'error_wraptag' => 'div',
			'error_class' => 'error',
			'error_id' => '',
			'error_breaktag' => 'br',
			'error_breakclass' => '',
			'on_submit_do' => '',
			'on_error_do' => '',
			'on_success_do' => '',
			'redirect' => '',
		),$atts));

		$id || check($id, $this->output->escape(self::$lang->get('attribute_required', 'id', ($conditional ? 'form:if_open' : 'form:open'))));
		$method || check($method, $this->output->escape(self::$lang->get('attribute_required', method, ($conditional ? 'form:if_open' : 'form:open'))));

		if ($this->findForm($id))
		{
			check(false, $this->output->escape(self::$lang->get('illegal_tag_nesting', ($conditional ? 'form:if_open' : 'form:open'))));
		}
		
		$this->pushForm($this->_forms[$id] = $this->_open_forms[] = $currentForm = new Form($id, $group));
		
		// should we skip processing this form?
		
		if ($this->fskip($conditional, $submitted))
		{
			return false;
		}

		// store form options where field tags can get to them
		
		$currentForm->data['required_class'] = $required_class;
		$currentForm->data['error_class'] = $error_class;
		
		($useNonce = $this->truthy($nonce)) && ($noncer = $this->loadNoncer(array('adapter'=>'database', 'lifetime'=>$nonce_lifetime, 'database'=>array('table'=>'nonce'))));

		$content = '';
		$extra = '';

		if ($submitted)
		{
			$useNonce && $nonce = $noncer->getNonce($this->gfvar('esc_nonce'));

			if ($this->gfvar('esc_honey') !== '')
			{
				$currentForm->errors['bot'] = self::$lang->get('bot_submission_detected');
				$suppressForm = true;
			}

			elseif ($useNonce && !$nonce)
			{
				$pageURI = $this->pageURI();
				$currentForm->errors['expired'] = self::$lang->get('form_expired') . ' ' . ucwords(self::$lang->get('please')) . ' <a href="' . $pageURI . '">' . self::$lang->get('try again') . '</a>.';
				$suppressForm = true;
			}

			elseif ($useNonce && $nonce['used'])
			{
				$currentForm->errors['duplicate'] = self::$lang->get('duplicate_submission_detected');
				$suppressForm = true;
			}
			
			elseif (!$this->validate())
			{
				if (empty($currentForm->errors))
				{
					$currentForm->errors['*'] = self::$lang->get('form_error');
				}
			}
		}
		
		// now parse content so form fields can read form options and set form errors
		
		if (empty($suppressForm))
		{
			$content = $this->getContent();
		}
		
		if ($submitted)
		{
			if (empty($suppressForm))
			{
				if (empty($currentForm->errors) && !$this->input->hasErrors())
				{
					if ($on_submit_do)
					{
						$this->parseSnippet($on_submit_do);
					}
					if (empty($currentForm->errors) && !$this->input->hasErrors() && $useNonce)
					{
						$noncer->useNonce($nonce['nonce']);
					}
				}
			}

			if (empty($currentForm->errors) && !$this->input->hasErrors())
			{
				if ($redirect)
				{
					$this->redirect($redirect);
				}
				elseif ($on_success_do)
				{
					$extra .= $this->parseSnippet($on_success_do);
				}
				elseif ($conditional)
				{
					return false;
				}
			}
		}
		
		$extra .= $this->_tag_form_hidden(array('name'=>'esc_submitted', 'value'=>$id));

		if ($group)
		{
			$extra .= $this->_tag_form_hidden(array('name'=>'esc_group', 'value'=>$group));
		}

		$visitedIDs = $this->input->post('esc_visited', $id);
		if (is_array($visitedIDs))
		{
			if (!in_array($id, $visitedIDs))
			{
				$visitedIDs[] = $id;
			}
		}
		elseif ($visitedIDs !== $id)
		{
			$visitedIDs = array($id, $visitedIDs);
		}
		$extra .= $this->_tag_form_hidden(array('name'=>'esc_visited', 'value'=>$visitedIDs));

		if ($useNonce)
		{
			if (!$submitted)
			{
				$nonce = $noncer->newNonce();
			}
			else
			{
				$nonce = $nonce['nonce'];
			}
			$extra .= $this->_tag_form_hidden(array('name'=>'esc_nonce', 'value'=>$nonce));
		}
		
		if ($this->truthy($honeypot))
		{
			$extra .= $this->_tag_form_text(array('name'=>'esc_honey', 'style'=>'display:none;'));
		}

		if ($submitted)
		{
			$currentForm->errors += $this->input->errors();
			if (!empty($currentForm->errors))
			{
				$extra .= $on_error_do
					? $this->parseSnippet($on_error_do)
					: $this->output->wrap($this->gferrs(), $error_wraptag, $error_class, $error_id, '', $error_breaktag, $error_breakclass)
					;
			}
		}
		
		$atts = $this->matts(compact('action', 'method'));
		return $this->output->tag($extra.$content, 'form', $class, $id, $atts);
	}

	protected function _xtag_form_open($atts)
	{
		array_pop($this->_open_forms);
		$this->popForm();
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_form_if_open($atts)
	{
		return $this->_tag_form_open($atts, true);
	}

	protected function _xtag_form_if_open($atts)
	{
		array_pop($this->_open_forms);
		$this->popForm();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_value($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'alt' => false,
			'escape' => false,
			'delim' => '|',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:value')));
		
		if ($this->truthy($escape))
		{
			return $this->gfval($name, $this->truthy($alt));
		}

		$value = $this->truthy($alt) ? $this->gfvaralt($name) : $this->gfvar($name);
		
		if (is_array($value))
		{
			$value = implode($delim, $value);
		}
		
		return $value;
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_form_if_value($atts)
	{
		extract($this->gatts(array(
			'name' => '',
			'alt' => false,
			'value' => '',
		),$atts));
		
		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:if_value')));

		$val = $this->truthy($alt) ? $this->gfvaralt($name) : $this->gfvar($name);

		if ($value === '')
		{
			return ($val !== '') && ($val !== NULL);
		}
		
		if (is_array($val))			// array of checkbox values: check against each
		{
			foreach ($val as $item)
			{
				if ($item === $value)
				{
					return true;
				}
			}
			return false;
		}
		
		return ($val === $value);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_error($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'wraptag' => 'div',
			'breaktag' => 'br',
			'breakclass' => '',
			'name' => '',
		),$atts));

		if ($this->hasContent())
		{
			$currentForm = $this->currentForm();
			$currentForm->errors[$name] = $this->getContent();
		}
		
		else
		{
			return $name
				? $this->output->tag($this->gferr($name), $wraptag, $class, $id)
				: $this->output->wrap($this->gferrs(), $wraptag, $class, $id, '', $breaktag, $breakclass)
				;
		}
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_form_if_error($atts)
	{
		extract($this->gatts(array(
			'name' => '',
		),$atts));
	
		$currentForm = $this->currentForm();
		return $name ? isset($currentForm->errors[$name]) : !empty($currentForm->errors);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_hidden($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'name' => '',
			'value' => '',
			'required' => false,
			'rule' => '',
		),$atts));
		
		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:hidden')));

		$origName = $name;
		
		if (is_array($value))
		{
			if (!empty($value))
			{
				if ($rule === '')
				{
					$rule = self::makeInListRule($value);	// anti-spoofing rule
				}
				$name .= '[]';
			}
		}
		else
		{
			if ($value !== '')
			{
				if ($rule === '')
				{
					$rule = "equal[{$value}]";	// anti-spoofing rule
				}
			}
		}

		if ($this->truthy($required) && (strpos($rule, 'required') === false))
		{
			$rule = 'required' . (($rule !== '') ? "|{$rule}" : '');
		}
		
		// careful not to use posted values that would break back/next

		$values = $this->fsub() ? $this->input->validate(NULL, $origName, $rule) : ($this->fsubmitted() && (strpos($origName, 'esc_') !== 0) ? $this->gfvar($name, $value) : $value);

		$type = 'hidden';
		$out = '';
		
		$idx = 0;
		foreach ((array)$values as $value)
		{
			$value = $this->output->escape($value);
			$atts = $this->matts(compact('type', 'name', 'value'));
			$out .= $this->output->tag(NULL, 'input', $class, $id ? ($id.'_'.$idx++) : '', $atts);
		}
		return $out;
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_form_text($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'style' => '',
			'name' => '',
			'label' => '',
			'default' => '',
			'size' => '',
			'maxlength' => '',
			'rule' => '',
			'type' => 'text',
			'placeholder' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:text')));

		$currentForm = $this->currentForm();

		// derive a maxlength value from the rule, if present
		
		if (!$maxlength && $rule)
		{
			if (preg_match('/length_max\[(\d+)\]|length_range\[\d+,(\d+)\]/', $rule, $matches))
			{
				$maxlength = isset($matches[2]) ? $matches[2] : $matches[1];
			}
		}
		
		$value = $this->output->escape($this->fsub() ? $this->input->validate($label, $name, $rule) : ($this->fsubmitted() ? $this->gfvar($name, $default) : $default));

		if (strpos($rule, 'required') !== false)
		{
			$class .= ($class ? ' ' : '') . $currentForm->data['required_class'];
		}

		if ($this->input->isError($name))
		{
			$class .= ($class ? ' ' : '') . $currentForm->data['error_class'];
		}
		
		if (($label !== '') && ($id === ''))
		{
			$id = $name;	// create a default ID since it is needed for the label
		}
		
		$atts = $this->matts(compact('type', 'name', 'value', 'size', 'maxlength', 'style', 'placeholder'), true);
		return ($label && $id ? $this->output->label($label, $id, $class) : '') . $this->output->tag(NULL, 'input', $class, $id, $atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_password($atts)
	{
		$atts['type'] = 'password';

		if (empty($atts['rule']))
		{
			$atts['rule'] = 'password';
		}
		elseif (strpos($atts['rule'], 'password') === false)
		{
			$atts['rule'] .= '|password';
		}

		return $this->_tag_form_text($atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_textarea($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'style' => '',
			'name' => '',
			'label' => '',
			'default' => '',
			'cols' => '',
			'rows' => '',
			'rule' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:textarea')));

		$currentForm = $this->currentForm();

		$value = $this->output->escape($this->fsub() ? $this->input->validate($label, $name, $rule) : ($this->fsubmitted() ? $this->gfvar($name, $default) : $default));

		if (strpos($rule, 'required') !== false)
		{
			$class .= ($class ? ' ' : '') . $currentForm->data['required_class'];
		}

		if ($this->input->isError($name))
		{
			$class .= ($class ? ' ' : '') . $currentForm->data['error_class'];
		}
		
		if (($label !== '') && ($id === ''))
		{
			$id = $name;	// create a default ID since it is needed for the label
		}
		
		$atts = $this->matts(compact('name', 'rows', 'cols', 'style'), true);
		return ($label && $id ? $this->output->label($label, $id, $class) : '') . $this->output->tag($value, 'textarea', $class, $id, $atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_select($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'style' => '',
			'name' => '',
			'label' => '',
			'default' => '',
			'options' => '',
			'multiple' => '',
			'size' => '',
			'delim' => '|',
			'kvdelim' => ';',
			'gdelim' => ':',
			'rule' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:select')));
		$options || check($options, $this->output->escape(self::$lang->get('attribute_required', 'options', 'form:select')));
		
		$currentForm = $this->currentForm();

		$origName = $name;
		if ($multiple !== '')
		{
			$name .= '[]';
		}

		// check for OPTGROUP
		
		if (strpos($options, $gdelim) !== false)
		{
			$splits = array_map('trim', preg_split("/{$gdelim}(.+?){$gdelim}/", $options, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE));
			$options = array();
			$groups = array();
			$getGroupLabel = true;
			foreach ($splits as $group)
			{
				if ($getGroupLabel)
				{
					$groupLabel = $group;
				}
				else
				{
					$options = array_merge($options, $groups[$groupLabel] = $this->kvlist($group, $delim, $kvdelim));
				}
				$getGroupLabel = !$getGroupLabel;
			}
		}
		else
		{
			 $options = $groups[''] = $this->kvlist($options, $delim, $kvdelim);
		}
		
		$currentForm->data['options'][$origName] = $options;	// save this for later lookups in gfvaralt()
		
		if ($rule != '')
		{
			$rules[] = $rule;
		}
		$rules[] = self::makeInListRule(array_keys($options));	// anti-spoofing rule
		$rule = implode('|', $rules);

		$selected = $this->fsub() ? $this->input->validate($label, $origName, $rule) : ($this->fsubmitted() ? $this->gfvar($origName, $this->glist($default)) : $this->glist($default));

		if (strpos($rule, 'required') !== false)
		{
			$class .= ($class ? ' ' : '') . $currentForm->data['required_class'];
		}

		if ($this->input->isError($origName))
		{
			$class .= ($class ? ' ' : '') . $currentForm->data['error_class'];
		}
		
		$values = '';
		reset($options);
		foreach ($groups as $group => $groupOpts)
		{
			if ($group !== '')
			{
				$values .= '<optgroup label="' . $group . '">';
			}
			for ($i = count($groupOpts); $i > 0; --$i)
			{
				$option = each($options);
				$key = $option[0];
				$val = $option[1];
				$atts = 'value='.'"'.$key.'"';
				if (in_array((string)$key, (array)$selected, true))
				{
					$atts .= ' selected="selected"';
				}
				$values .= $this->output->tag($val, 'option', '', '', $atts);
			}
			if ($group !== '')
			{
				$values .= '</optgroup>';
			}
		}
		
		if (($label !== '') && ($id === ''))
		{
			$id = $name;	// create a default ID since it is needed for the label
		}
		
		$atts = $this->matts(compact('name', 'multiple', 'size', 'style'), true);
		return ($label && $id ? $this->output->label($label, $id, $class) : '') . $this->output->tag($values, 'select', $class, $id, $atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_radio($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'style' => '',
			'wraptag' => 'div',
			'wrapclass' => '',
			'breaktag' => '',
			'breakclass' => '',
			'name' => '',
			'label' => '',
			'default' => '',
			'options' => '',
			'delim' => '|',
			'kvdelim' => ';',
			'rule' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:radio')));
		$options || check($options, $this->output->escape(self::$lang->get('attribute_required', 'options', 'form:radio')));
		
		$currentForm = $this->currentForm();

		$options = $this->kvlist($options, $delim, $kvdelim);

		$currentForm->data['options'][$name] = $options;	// save this for later lookups in gfvaralt()
		
		if ($rule != '')
		{
			$rules[] = $rule;
		}
		$rules[] = self::makeInListRule(array_keys($options));	// anti-spoofing rule
		$rule = implode('|', $rules);

		$selected = $this->output->escape($this->fsub() ? $this->input->validate($label, $name, $rule) : ($this->fsubmitted() ? $this->gfvar($name, $default) : $default));

		if (strpos($rule, 'required') !== false)
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $currentForm->data['required_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $currentForm->data['required_class'];
			}
		}

		if ($this->input->isError($name))
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $currentForm->data['error_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $currentForm->data['error_class'];
			}
		}
		
		$wid = $id;
		$type = 'radio';
		$radios = $label ? array($this->output->tag($label, 'span', $wrapclass)) : array();

		foreach ($options as $key => $val)
		{
			$value = (string)$key;
			$id = $name . '_' . $wid . '_' . $key;
			$atts = $this->matts(compact('type', 'name', 'id', 'value'));
			if ((string)$key === $selected)
			{
				$atts .= ' checked="checked"';
			}
			$radios[] = $this->output->tag(NULL, 'input', $class, '', $atts) . $this->output->label($val, $id, $class);
		}

		return $this->output->wrap($radios, $wraptag, $wrapclass, $wid, '', $breaktag, $breakclass);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_checkbox($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'style' => '',
			'wraptag' => 'div',
			'wrapclass' => '',
			'breaktag' => '',
			'breakclass' => '',
			'name' => '',
			'label' => '',
			'hidelabel' => false,
			'default' => '',
			'options' => '',
			'delim' => '|',
			'kvdelim' => ';',
			'rule' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:checkbox')));
		$options || check($options, $this->output->escape(self::$lang->get('attribute_required', 'options', 'form:checkbox')));
		
		$currentForm = $this->currentForm();

		$origName = $name;
		$name .= '[]';
		
		$options = $this->kvlist($options, $delim, $kvdelim);

		$currentForm->data['options'][$origName] = $options;	// save this for later lookups in gfvaralt()
		
		if ($rule != '')
		{
			$rules[] = $rule;
		}
		$rules[] = self::makeInListRule(array_keys($options));	// anti-spoofing rule
		$rule = implode('|', $rules);

		$selected = $this->fsub() ? $this->input->validate($label, $origName, $rule) : ($this->fsubmitted() ? $this->gfvar($origName, $this->glist($default)) : $this->glist($default));

		if (strpos($rule, 'required') !== false)
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $currentForm->data['required_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $currentForm->data['required_class'];
			}
		}

		if ($this->input->isError($origName))
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $currentForm->data['error_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $currentForm->data['error_class'];
			}
		}
		
		$wid = $id;
		$type = 'checkbox';
		if ($this->truthy($hidelabel))
		{
			$label = '';
		}
		$checkboxes = $label && $wid ? array($this->output->label($label, $wid, $wrapclass)) : array();

		foreach ($options as $key => $val)
		{
			$value = (string)$key;
			$id = $origName . '_' . $wid . '_' . $key;
			$atts = $this->matts(compact('type', 'name', 'id', 'value'));
			if (in_array((string)$key, (array)$selected, true))
			{
				$atts .= ' checked="checked"';
			}
			$checkboxes[] = $this->output->tag(NULL, 'input', $class, '', $atts) . $this->output->label($val, $id, $class);
		}

		return $this->output->wrap($checkboxes, $wraptag, $wrapclass, $wid, '', $breaktag, $breakclass);
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_form_button($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'type' => 'button',
			'name' => '',
			'value' => 'Go',
		),$atts));

		$value || check($value, $this->output->escape(self::$lang->get('attribute_required', 'value', 'form:button')));
		
		if ($type === 'submit')
		{
			$rule = "equal[{$value}]";	// anti-spoofing rule
		}

		$value = $this->output->escape($value);
			
		$atts = $this->matts(compact('type', 'name', 'value'));
		return $this->output->tag($this->hasCOntent() ? $this->getContent() : $value, 'button', $class, $id, $atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_submit($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'name' => 'submit',
			'value' => 'Submit',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:submit')));
		$value || check($value, $this->output->escape(self::$lang->get('attribute_required', 'value', 'form:submit')));

		$rule = "equal[{$value}]";	// anti-spoofing rule
		$value = $this->output->escape($value);

		$type = 'submit';
		$atts = $this->matts(compact('type', 'name', 'value'));
		return $this->output->tag(NULL, 'input', $class, $id, $atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_email($atts)
	{
		$atts['type'] = 'email';

		if (empty($atts['rule']))
		{
			$atts['rule'] = 'email';
		}
		elseif (strpos($atts['rule'], 'email') === false)
		{
			$atts['rule'] .= '|email';
		}

		return $this->_tag_form_text($atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_url($atts)
	{
		$atts['type'] = 'url';

		if (empty($atts['rule']))
		{
			$atts['rule'] = 'url';
		}
		elseif (strpos($atts['rule'], 'url') === false)
		{
			$atts['rule'] .= '|url';
		}

		return $this->_tag_form_text($atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_phone($atts)
	{
		$atts['type'] = 'text';

		if (empty($atts['rule']))
		{
			$atts['rule'] = 'phone';
		}
		elseif (strpos($atts['rule'], 'phone') === false)
		{
			$atts['rule'] .= '|phone';
		}

		return $this->_tag_form_text($atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_zip($atts)
	{
		$atts['type'] = 'text';
		$atts['size'] = '10';
		$atts['maxlength'] = '10';

		if (empty($atts['rule']))
		{
			$atts['rule'] = 'zipcode';
		}
		elseif (strpos($atts['rule'], 'zipcode') === false)
		{
			$atts['rule'] .= '|zipcode';
		}

		return $this->_tag_form_text($atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_date($atts)
	{
		$atts['type'] = 'text';
		$atts['size'] = '10';
		$atts['maxlength'] = '10';

		if (empty($atts['rule']))
		{
			$atts['rule'] = 'date';
		}
		elseif (strpos($atts['rule'], 'date') === false)
		{
			$atts['rule'] .= '|date';
		}

		return $this->_tag_form_text($atts);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_state($atts)
	{
		$atts['options'] = '
			;Please Choose…|
			AL;Alabama|
			AK;Alaska|
			AZ;Arizona|
			AR;Arkansas|
			CA;California|
			CO;Colorado|
			CT;Connecticut|
			DE;Delaware|
			DC;District of Columbia|
			FL;Florida|
			GA;Georgia|
			HI;Hawaii|
			ID;Idaho|
			IL;Illinois|
			IN;Indiana|
			IA;Iowa|
			KS;Kansas|
			KY;Kentucky|
			LA;Louisiana|
			ME;Maine|
			MD;Maryland|
			MA;Massachusetts|
			MI;Michigan|
			MN;Minnesota|
			MS;Mississippi|
			MO;Missouri|
			MT;Montana|
			NE;Nebraska|
			NV;Nevada|
			NH;New Hampshire|
			NJ;New Jersey|
			NM;New Mexico|
			NY;New York|
			NC;North Carolina|
			ND;North Dakota|
			OH;Ohio|
			OK;Oklahoma|
			OR;Oregon|
			PA;Pennsylvania|
			RI;Rhode Island|
			SC;South Carolina|
			SD;South Dakota|
			TN;Tennessee|
			TX;Texas|
			UT;Utah|
			VT;Vermont|
			VA;Virginia|
			WA;Washington|
			WV;West Virginia|
			WI;Wisconsin|
			WY;Wyoming|
			1;-----------------|
			AS;American Samoa|
			CZ;Canal Zone|
			GU;Guam|
			MP;Northern Mariana Islands|
			PR;Puerto Rico|
			VI;Virgin Islands|
			2;-----------------|
			AB;Alberta|
			BC;British Columbia|
			MB;Manitoba|
			NB;New Brunswick|
			NL;Newfoundland|
			NT;Northwest Territories|
			NS;Nova Scotia|
			NU;Nunavut|
			ON;Ontario|
			PE;Prince Edward Island|
			QC;Quebec|
			SK;Saskatchewan|
			YT;Yukon Territory|
		';

		return $this->_tag_form_select($atts);
	}
	
	//---------------------------------------------------------------------------

	protected function validate()
	{
		// a validation hook for derived classes
		
		return true;
	}

	//---------------------------------------------------------------------------

	// Built-in Validation Rules (and Overrides)

	//---------------------------------------------------------------------------

	public function validate_date($item, $param = NULL)
	{
		// We override this rule implementation to provide more user-friendly date_create()
		// validation then the SparkPlug default.

		if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $item, $matches))
		{
			return false;
		}
		
		$year = $matches[3];
		$month = $matches[1];
		$day = $matches[2];
		
		if (!checkdate($month, $day, $year))
		{
			return false;
		}
		
		if (!empty($param))
		{
			$date = "{$year}{$month}{$day}";
			$today = gmdate('Ymd');
			switch ($param)
			{
				case 'now':
				case 'today':
					return $date === $today;
				case '!now':
				case '!today':
					return $date !== $today;
				case 'past':
					return $date < $today;
				case '!past':
					return $date >= $today;
				case 'future':
					return $date > $today;
				case '!future':
					return $date <= $today;
			}
		}
		
		return true;
	}
	
	// --------------------------------------------------------------------------
	
	public function validate_date_range($item, $param)
	{
		// We override this rule implementation to provide more user-friendly date_create()
		// validation then the SparkPlug default.

		if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $item))
		{
			return false;
		}
		
		$param = explode(',', $param);
		if (count($param) !== 2)
		{
			return false;
		}
		
		$min = $param[0];
		if (!self::validate_date($min))
		{
			if ($min === '*')
			{
				$min = NULL;
			}
			else
			{
				return false;
			}
		}
		
		$max = $param[1];
		if (!self::validate_date($max))
		{
			if ($max === '*')
			{
				$max = NULL;
			}
			else
			{
				return false;
			}
		}
		
		$time = strtotime($item);

		return 
			(isset($min) ? (strtotime($min) <= $time) : true) &&
			(isset($max) ? ($time <= strtotime($max)) : true);
	}
	
	//---------------------------------------------------------------------------

	public function validate_match($item, &$param, $input)
	{
		// We override this rule implementation because we handle label substitution
		// differently. (Label is specified in the rule param since we don't have a
		// pre-built set of field info.

		if (($pos = strpos($param, ',')) !== false)
		{
			$name = substr($param, 0, $pos);
			$param = substr($param, $pos+1);
		}
		else
		{
			$name = $param;
		}
		
		if (!isset($input[$name]))
		{
			return false;
		}
		else
		{
			$result = ($item !== $input[$name]) ? false : ($item === '' ? NULL : true);
		}
		
		return $result;
	}

	//---------------------------------------------------------------------------
}

