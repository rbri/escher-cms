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

// -----------------------------------------------------------------------------

class RecaptchaTags extends FormTags
{
	private $_recaptchaError;
	
	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);

		$myInfo = $this->factory->getPlug('RecaptchaTags');
		$plugDir = dirname($myInfo['file']);

		require_once($plugDir . '/recaptchalib.php');
		
		$this->_recaptchaError = NULL;
	}
	
	//---------------------------------------------------------------------------

	protected function validate()
	{
		if ($this->gfvar('recaptcha_response_field') !== NULL)
		{
			$response = recaptcha_check_answer($this->app->get_pref('recaptcha_private_key'), SparkUtil::remote_ip(), $this->gfvar('recaptcha_challenge_field'), $this->gfvar('recaptcha_response_field'));
	
			if (!$response->is_valid)
			{
				$this->_recaptchaError = $response->error;
				return false;
			}
		}
		return true;
	}

	//---------------------------------------------------------------------------
	
	protected function _tag_form_recaptcha($atts)
	{
		if ($publicKey = $this->app->get_pref('recaptcha_public_key'))
		{
			return recaptcha_get_html($publicKey, $this->_recaptchaError, SparkUtil::is_https());
		}
		return '';
	}

	//---------------------------------------------------------------------------
}
