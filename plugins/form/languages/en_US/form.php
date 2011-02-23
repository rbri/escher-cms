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

$lang['form'] = array
(
	'form_error' => 'There is a problem with the data entered into this form.',
	'duplicate_submission_detected' => 'Duplicate form submission detected.',
	'form_expired' => 'Form has expired.',
	'bot_submission_detected' => 'Automatic form submission rejected.',
	'required' => '"%1$s" is a required field',
	'required_if' => '"%1$s" is a required field',
	'regex' => '"%1$s" does not contain a valid value',
	'match' => '"%1$s" does not match',
	'cookie' => '"%1$s" cookie not found',
	'length' => '"%1$s" must contain exactly %2$s characters',
	'length_min' => '"%1$s" must contain at least %2$s characters',
	'length_max' => '"%1$s" may contain no more than %2$s characters',
	'length_range' => '"%1$s" must contain the specified number of characters',
	'alpha' => '"%1$s" must contain only alphabetic characters',
	'numeric' => '"%1$s" must contain only numeric digits',
	'alphanum' => '"%1$s" must contain only alphabetic or numeric characters',
	'not_zero' => '"%1$s" must contain a non-zero number',
	'not_empty' => '"%1$s" must contain a non-zero number',
	'equal' => '"%1$s" does not contain a valid value',
	'not_equal' => '"%1$s" does not contain a valid choice',
	'in_list' => 'A valid choice was not selected from the "%1$s" field',
	'name' => '"%1$s" must contain only alphabetic, dash and hyphen characters',
	'currency' => '"%1$s" must contain a valid currency amount',
	'currency_min' => '"%1$s" must contain a valid currency amount no less than $%2$s',
	'currency_max' => '"%1$s" must contain a valid currency amount no more than $%2$s',
	'currency_range' => '"%1$s" must contain a valid currency amount within the specified range',
	'username' => '"%1$s" must be between 6 and 15 characters in length, must begin with a letter and contain only alphanumeric characters',
	'password' => '"%1$s" must contain at least 8 characters, of which at least 2 must be alphabetic characters and at least 2 must be numeric digits or other special characters (@, #, $, %, *, etc.)',
	'email' => '"%1$s" must contain a properly formatted email address',
	'url' => '"%1$s" must contain a properly formatted URL',
	'date' => 'A valid date was not entered into the "%1$s" field',
	'phone' => 'A valid phone number was not entered into the "%1$s" field',
	'zip_code' => 'A valid zip code was not entered into the "%1$s" field',
	'general' => 'There is a problem with the data entered into the "%1$s" field',
);
