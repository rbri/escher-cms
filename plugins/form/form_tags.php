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

// -----------------------------------------------------------------------------

class FormTags extends EscherParser
{
	protected $form_data;
	protected $form_errs;

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
		$info = $this->factory->getPlug('FormTags');
		$langDir = dirname($info['file']) . '/languages';
		self::$lang->load('form', $langDir);
	}
	
	//---------------------------------------------------------------------------
	// Form Helpers
	//---------------------------------------------------------------------------

	final protected function fsub($id = NULL)
	{
		if ($id === NULL)
		{
			$id = @$this->tag_form_id;
		}
		return ($submitted = $this->input->post('esc_submitted')) && isset($id) && ($submitted == $id);
	}

	//---------------------------------------------------------------------------

	final protected function gfvar($name, $default = NULL, $id = NULL)
	{
		return $this->fsub($id) ? $this->input->post($name, $default) : NULL;
	}

	//---------------------------------------------------------------------------

	final protected function gfvaralt($name, $default = NULL, $id = NULL)
	{
		if (($val = $this->gfvar($name, NULL, $id)) === NULL)
		{
			return $default;
		}

		if ($id === NULL)
		{
			$id = @$this->tag_form_id;
		}

		if (($alt = @$this->form_data[$id]['options'][$name][$val]) === NULL)
		{
			return $val;
		}

		return $alt;
	}

	//---------------------------------------------------------------------------

	final protected function gfval($name, $alt = false, $default = NULL, $id = NULL)
	{
		$val = $alt ? $this->gfvaralt($name, $default, $id) : $this->gfvar($name, $default, $id);

		if (is_array($val))
		{
			return array_map(array($this->output, 'escape'), $val);
		}

		return $this->output->escape($val);
	}

	//---------------------------------------------------------------------------

	final protected function sferr($name, $err)
	{
		$this->form_errs[$name] = $err;
	}

	//---------------------------------------------------------------------------

	final protected function gferr($name)
	{
		return isset($this->form_errs[$name]) ? $this->output->escape($this->form_errs[$name]) : '';
	}

	//---------------------------------------------------------------------------

	final protected function gferrs()
	{
		// specially named error '*' gets displayed first
		
		if (isset($this->form_errs['*']))
		{
			$firstErr = $this->form_errs['*'];
			unset($this->form_errs['*']);
			array_unshift($this->form_errs, $firstErr);
		}
		
		return $this->form_errs;
	}

	//---------------------------------------------------------------------------

	protected function validate()
	{
		// a validation hook for derived classes
		
		return true;
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

	protected function _tag_form_open($atts, $if = false)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'name' => '',
			'action' => '',
			'method' => 'post',
			'nonce' => '1',
			'nonce_lifetime' => '600',
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
		
		!isset($this->tag_form_id) || check(false, $this->output->escape(self::$lang->get('illegal_tag_nesting', ($if ? 'form:if_open' : 'form:open'))));
		$id || check($id, $this->output->escape(self::$lang->get('attribute_required', 'id', ($if ? 'form:if_open' : 'form:open'))));
		$method || check($method, $this->output->escape(self::$lang->get('attribute_required', method, ($if ? 'form:if_open' : 'form:open'))));
		
		$this->form_errs = array();
		$this->tag_form_id = $id;
		
		// store form options where field tags can get to them
		
		$this->form_data[$this->tag_form_id]['required_class'] = $required_class;
		$this->form_data[$this->tag_form_id]['error_class'] = $error_class;
		
		($useNonce = $this->truthy($nonce)) && ($noncer = $this->loadNoncer(array('adapter'=>'database', 'lifetime'=>$nonce_lifetime)));

		$content = '';
		$extra = '';

		if ($submitted = $this->fsub())
		{
			$useNonce && $nonce = $noncer->getNonce($this->gfvar('esc_nonce'));

			if ($this->gfvar('esc_honey') !== '')
			{
				$this->form_errs['bot'] = self::$lang->get('bot_submission_detected');
				$suppressForm = true;
			}

			elseif ($useNonce && !$nonce)
			{
				$pageURI = $this->pageURI();
				$this->form_errs['expired'] = self::$lang->get('form_expired') . ' ' . ucwords(self::$lang->get('please')) . ' <a href="' . $pageURI . '">' . self::$lang->get('try again') . '</a>.';
				$suppressForm = true;
			}

			elseif ($useNonce && $nonce['used'])
			{
				$this->form_errs['duplicate'] = self::$lang->get('duplicate_submission_detected');
				$suppressForm = true;
			}
			
			elseif (!$this->validate())
			{
				if (empty($this->form_errs))
				{
					$this->form_errs['*'] = self::$lang->get('form_error');
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
				$this->form_errs += $this->input->errors();

				if (empty($this->form_errs))
				{
					if ($on_submit_do)
					{
						$this->parseSnippet($on_submit_do);
					}
					if (empty($this->form_errs) && $useNonce)
					{
						$noncer->useNonce($nonce['nonce']);
					}
				}
			}

			if (!empty($this->form_errs))
			{
				$extra .= $on_error_do
					? $this->parseSnippet($on_error_do)
					: $this->output->wrap($this->gferrs(), $error_wraptag, $error_class, $error_id, '', $error_breaktag, $error_breakclass)
					;
			}
			elseif ($redirect)
			{
				$this->redirect($redirect);
			}
			elseif ($on_success_do)
			{
				$extra .= $this->parseSnippet($on_success_do);
			}
			elseif ($if)
			{
				return false;
			}
		}
		
		$id = $this->output->escape($id);

		$extra .= $this->_tag_form_hidden(array('name'=>'esc_submitted', 'value'=>$id));

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
			$extra .= $this->_tag_form_hidden(array('name'=>'esc_nonce', 'value'=>$this->output->escape($nonce)));
		}
		
		if ($this->truthy($honeypot))
		{
			$extra .= $this->_tag_form_text(array('name'=>'esc_honey', 'style'=>'display:none;'));
		}

		$atts = $this->matts(compact('action', 'method'));
		return $this->output->tag($extra.$content, 'form', $class, $id, $atts);
	}

	protected function _xtag_form_open($atts)
	{
		unset($this->tag_form_id);
	}
	
	//---------------------------------------------------------------------------

	protected function _tag_form_if_open($atts)
	{
		return $this->_tag_form_open($atts, true);
	}

	protected function _xtag_form_if_open($atts)
	{
		unset($this->tag_form_id);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_value($atts)
	{
		extract($this->gatts(array(
			'id' => NULL,
			'name' => '',
			'alt' => false,
			'escape' => false,
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:value')));
		
		if ($this->truthy($escape))
		{
			return $this->gfval($name, $this->truthy($alt), NULL, $id);
		}

		return $this->truthy($alt) ? $this->gfvaralt($name, NULL, $id) : $this->gfvar($name, NULL, $id);
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_form_if_value($atts)
	{
		extract($this->gatts(array(
			'id' => NULL,
			'name' => '',
			'alt' => false,
			'value' => '',
		),$atts));
		
		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:if_value')));

		$val = $this->truthy($alt) ? $this->gfvaralt($name, NULL, $id) : $this->gfvar($name, NULL, $id);

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
			$this->form_errs[$name] = $this->getContent();
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
		
		return $name ? isset($this->form_errs[$name]) : !empty($this->form_errs);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_form_hidden($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'name' => '',
			'value' => '',
		),$atts));
		
		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:hidden')));

		$rule = "equal[{$value}]";	// anti-spoofing rule

		$value = $this->output->escape($this->fsub() ? $this->input->validate(NULL, $name, $rule) : $value);

		$type = 'hidden';
		$atts = $this->matts(compact('type', 'name', 'value'));
		return $this->output->tag(NULL, 'input', $class, $id, $atts);
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

		// derive a maxlength value from the rule, if present
		
		if (!$maxlength && $rule)
		{
			if (preg_match('/length_max\[(\d+)\]|length_range\[\d+,(\d+)\]/', $rule, $matches))
			{
				$maxlength = isset($matches[2]) ? $matches[2] : $matches[1];
			}
		}
		
		$value = $this->output->escape($this->fsub() ? $this->input->validate($label, $name, $rule) : $default);

		if (strpos($rule, 'required') !== false)
		{
			$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['required_class'];
		}

		if ($this->input->isError($name))
		{
			$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['error_class'];
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

		$value = $this->output->escape($this->fsub() ? $this->input->validate($label, $name, $rule) : $default);

		if (strpos($rule, 'required') !== false)
		{
			$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['required_class'];
		}

		if ($this->input->isError($name))
		{
			$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['error_class'];
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
			'delim' => '|',
			'kvdelim' => ';',
			'gdelim' => ':',
			'rule' => '',
		),$atts));

		$name || check($name, $this->output->escape(self::$lang->get('attribute_required', 'name', 'form:select')));
		$options || check($options, $this->output->escape(self::$lang->get('attribute_required', 'options', 'form:select')));
		
		// check fopr OPTGROUP
		
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
		
		$this->form_data[$this->tag_form_id]['options'][$name] = $options;	// save this for later lookups in <value_option> tag
		
		if ($rule != '')
		{
			$rules[] = $rule;
		}
		$rules[] = self::makeInListRule(array_keys($options));	// anti-spoofing rule
		$rule = implode('|', $rules);

		$selected = $this->output->escape($this->fsub() ? $this->input->validate($label, $name, $rule) : $default);

		if (strpos($rule, 'required') !== false)
		{
			$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['required_class'];
		}

		if ($this->input->isError($name))
		{
			$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['error_class'];
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
				if ((string)$key === $selected)
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
		
		$atts = $this->matts(compact('name', 'multiple', 'style'), true);
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
		
		$options = $this->kvlist($options, $delim, $kvdelim);

		$this->form_data[$this->tag_form_id]['options'][$name] = $options;	// save this for later lookups in <value_option> tag
		
		if ($rule != '')
		{
			$rules[] = $rule;
		}
		$rules[] = self::makeInListRule(array_keys($options));	// anti-spoofing rule
		$rule = implode('|', $rules);

		$selected = $this->output->escape($this->fsub() ? $this->input->validate($label, $name, $rule) : $default);

		if (strpos($rule, 'required') !== false)
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $this->form_data[$this->tag_form_id]['required_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['required_class'];
			}
		}

		if ($this->input->isError($name))
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $this->form_data[$this->tag_form_id]['error_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['error_class'];
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
		
		$origName = $name;
		$name .= '[]';
		
		$options = $this->kvlist($options, $delim, $kvdelim);

		$this->form_data[$this->tag_form_id]['options'][$name] = $options;	// save this for later lookups in <value_option> tag
		
		if ($rule != '')
		{
			$rules[] = $rule;
		}
		$rules[] = self::makeInListRule(array_keys($options));	// anti-spoofing rule
		$rule = implode('|', $rules);

		$selected = $this->fsub() ? $this->input->validate($label, $origName, $rule) : $this->glist($default);

		if (strpos($rule, 'required') !== false)
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $this->form_data[$this->tag_form_id]['required_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['required_class'];
			}
		}

		if ($this->input->isError($origName))
		{
			if ($wraptag && $label)
			{
				$wrapclass .= ($wrapclass ? ' ' : '') . $this->form_data[$this->tag_form_id]['error_class'];
			}
			else
			{
				$class .= ($class ? ' ' : '') . $this->form_data[$this->tag_form_id]['error_class'];
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
	
	// --------------------------------------------------------------------------

	protected function _tag_form_button($atts)
	{
		extract($this->gatts(array(
			'class' => '',
			'id' => '',
			'name' => '',
			'value' => 'Go',
		),$atts));

		$value || check($value, $this->output->escape(self::$lang->get('attribute_required', 'value', 'form:button')));

		$type = 'button';
		$atts = $this->matts(compact('type', 'name', 'value'));
		return $this->output->tag(NULL, 'button', $class, $id, $atts);
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
		$value = $this->output->escape($this->fsub() ? $this->input->validate(NULL, $name, $rule) : $value);

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
			$atts['rule'] = 'zip_code';
		}
		elseif (strpos($atts['rule'], 'zip_code') === false)
		{
			$atts['rule'] .= '|zip_code';
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
	
	// --------------------------------------------------------------------------

	protected static function makeInListRule($value)
	{
		// construct an in_list rule for one or more values, escaping all commas as requried
		
		if (is_array($value))
		{
			foreach (array_keys($value) as $key)
			{
				$value[$key] = str_replace(',', '\,', $value[$key]);
			}
			return 'in_list[' . implode($value, ',') . ']';
		}
		else
		{
			return 'in_list[' . str_replace(',', '\,', $value) . ']';
		}
	}
	
	//---------------------------------------------------------------------------
}
