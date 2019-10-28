<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for signup page


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.appsmata.org/license.php
*/

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once AS_INCLUDE_DIR . 'app/captcha.php';
require_once AS_INCLUDE_DIR . 'db/users.php';

// Get information about possible additional fields

$show_terms = as_opt('show_signup_terms');


// Check we haven't suspended registration, and this IP isn't blocked

if (as_opt('suspend_signup_users')) {
	$as_content = as_content_prepare();
	$as_content['error'] = as_lang_html('users/signup_suspended');
	return $as_content;
}

if (as_user_permit_error()) {
	$as_content = as_content_prepare();
	$as_content['error'] = as_lang_html('users/no_permission');
	return $as_content;
}


// Process submitted form

if (as_clicked('dosignup')) {
	require_once AS_INCLUDE_DIR . 'app/limits.php';

	if (as_user_limits_remaining(AS_LIMIT_SIGNUPS)) {
		require_once AS_INCLUDE_DIR . 'app/users-edit.php';

		$infirstname = as_post_text('firstname');
		$inlastname = as_post_text('lastname');
		$inremember = as_post_text('remember');
		$ingender = as_post_text('gender');
		$incountry = 'Kenya';
		$inmobile = as_post_text('mobile');
		$inemail = as_post_text('email');
		$inpassword = as_post_text('password');
		$inhandle = as_post_text('handle');
		$interms = (int)as_post_text('terms');

		if (!as_check_form_security_code('signup', as_post_text('code'))) {
			$pageerror = as_lang_html('misc/form_security_again');
		} else {
			// core validation
			$errors = array_merge(
				as_handle_email_filter($inhandle, $inemail),
				as_password_validate($inpassword)
			);

			// T&Cs validation
			if ($show_terms && !$interms)
				$errors['terms'] = as_lang_html('users/terms_not_accepted');

			if (as_opt('captcha_on_signup'))
				as_captcha_validate_post($errors);

			if (empty($errors)) {
				// signup and redirect
				as_limits_increment(null, AS_LIMIT_SIGNUPS);

				$userid = as_create_department_user('U', $infirstname, $inlastname, $ingender, $incountry, $inmobile, $inemail, $inpassword, $inhandle, AS_USER_LEVEL_BASIC);
			
				as_set_signed_in_user($userid, $inhandle);

				$topath = as_get('to');

				if (isset($topath))
					as_redirect_raw(as_path_to_root() . $topath); // path already provided as URL fragment
				else
					as_redirect('');
			}
		}

	} else
		$pageerror = as_lang('users/signup_limit');
}


// Prepare content for theme

$as_content = as_content_prepare();

$as_content['title'] = as_lang_html('users/signup_customer_title');

$as_content['error'] = @$pageerror;

$as_content['form'] = array(
	'tags' => 'method="post" action="' . as_self_html() . '"',

	'style' => 'tall',

	'fields' => array(
		'firstname' => array(
			'label' => as_lang_html('users/firstname_label'),
			'tags' => 'name="firstname" id="firstname" dir="auto"',
			'value' => as_html(@$inhandle),
			'error' => as_html(@$errors['firstname']),
		),

		'lastname' => array(
			'label' => as_lang_html('users/lastname_label'),
			'tags' => 'name="lastname" id="lastname" dir="auto"',
			'value' => as_html(@$inhandle),
			'error' => as_html(@$errors['lastname']),
		),

		'country' => array(
			'label' => as_lang_html('users/country_label'),
			'tags' => 'name="country" id="country" dir="auto"',
			'value' => as_html(@$inhandle),
			'error' => as_html(@$errors['country']),
		),

		'mobile' => array(
			'label' => as_lang_html('users/mobile_label'),
			'tags' => 'name="mobile" id="mobile" dir="auto"',
			'value' => as_html(@$inmobile),
			'error' => as_html(@$errors['mobile']),
		),

		'handle' => array(
			'label' => as_lang_html('users/handle_label'),
			'tags' => 'name="handle" id="handle" dir="auto"',
			'value' => as_html(@$inhandle),
			'error' => as_html(@$errors['handle']),
		),

		'password' => array(
			'type' => 'password',
			'label' => as_lang_html('users/password_label'),
			'tags' => 'name="password" id="password" dir="auto"',
			'value' => as_html(@$inpassword),
			'error' => as_html(@$errors['password']),
		),

		'email' => array(
			'label' => as_lang_html('users/email_label'),
			'tags' => 'name="email" id="email" dir="auto"',
			'value' => as_html(@$inemail),
			'note' => as_opt('email_privacy'),
			'error' => as_html(@$errors['email']),
		),
	),

	'buttons' => array(
		'signup' => array(
			'tags' => 'onclick="as_show_waiting_after(this, false);"',
			'label' => as_lang_html('users/signup_button'),
		),
	),

	'hidden' => array(
		'dosignup' => '1',
		'code' => as_get_form_security_code('signup'),
	),
);

// prepend custom message
$custom = as_opt('show_custom_signup') ? trim(as_opt('custom_signup')) : '';
if (strlen($custom)) {
	array_unshift($as_content['form']['fields'], array(
		'type' => 'custom',
		'note' => $custom,
	));
}

if (as_opt('captcha_on_signup'))
	as_set_up_captcha_field($as_content, $as_content['form']['fields'], @$errors);

// show T&Cs checkbox
if ($show_terms) {
	$as_content['form']['fields']['terms'] = array(
		'type' => 'checkbox',
		'label' => trim(as_opt('signup_terms')),
		'tags' => 'name="terms" id="terms"',
		'value' => as_html(@$interms),
		'error' => as_html(@$errors['terms']),
	);
}

$signinmodules = as_load_modules_with('signin', 'signin_html');

foreach ($signinmodules as $module) {
	ob_start();
	$module->signin_html(as_opt('site_url') . as_get('to'), 'signup');
	$html = ob_get_clean();

	if (strlen($html))
		@$as_content['custom'] .= '<br>' . $html . '<br>';
}

// prioritize 'handle' for keyboard focus
$as_content['focusid'] = isset($errors['handle']) ? 'handle'
	: (isset($errors['password']) ? 'password'
		: (isset($errors['email']) ? 'email' : 'handle'));


return $as_content;
