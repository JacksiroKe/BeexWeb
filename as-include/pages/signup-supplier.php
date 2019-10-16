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
$userid = as_get_logged_in_userid();

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

$topath = as_get('to');
$signuptype = ( as_get('signuptype') !== null ) ? as_get('signuptype') : 'business';

// Process submitted form

if (as_clicked('dosignup')) {
	require_once AS_INCLUDE_DIR . 'app/limits.php';

	if (as_user_limits_remaining(AS_LIMIT_SIGNUPS)) {
		require_once AS_INCLUDE_DIR . 'app/users-edit.php';
		
		$intitle = as_post_text('title');
		$incontent = as_post_text('content');
		$inlocation = as_post_text('location');
		$infield1 = as_post_text('field1');
		$infield2 = as_post_text('field2');
		$usertype = as_post_text('usertype');
		$profiles = as_post_text('profiles');

		if (!as_check_form_security_code('signup', as_post_text('code'))) {
			$pageerror = as_lang_html('misc/form_security_again');
		} else {
			// T&Cs validation
			if ($show_terms && !$interms)
				$errors['terms'] = as_lang_html('users/terms_not_accepted');

			if (empty($errors)) {
				// signup and redirect
				as_limits_increment(null, AS_LIMIT_SIGNUPS);

				$supplierid = as_create_new_supplier($userid, $intitle, $incontent, $infield1, $infield2, $usertype);		

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

$as_content['title'] = as_lang_html('users/signup_supplier_title');

$as_content['error'] = @$pageerror;

$siteurl = as_path_to_root().'signup-supplier?pg=ss';
if (isset($topath)) $siteurl .= '&&to='.$topath;
$title = 'Set Up Your ';

switch ($signuptype)
{
	case 'business':
		$pcode = 'bs';
		$siteurl .= '&&signuptype=individual';
		$title .= '<b>BUSINESS SUPPLIER PROFILE</b> / <a href="'.$siteurl.'">Change to Individual Profile</a>';
		break;
		
	case 'individual':
		$pcode = 'is';
		$siteurl .= '&&signuptype=business';
		$title .= '<b>INDIVIDUAL SUPPLIER PROFILE</b> / <a href="'.$siteurl.'">Change to Business Profile</a>';
		break;
}

$signupform = array(
	'title' => $title,
	'type' => 'form',
	'style' => 'tall',
	'tags' => 'method="post" action="' . as_self_html() . '"',

	'fields' => array(
		'title' => array(
			'label' => as_lang_html('users/'.$pcode.'_title_label'),
			'tags' => 'name="title" id="title" dir="auto"',
			'value' => as_html(@$intitle),
			'error' => as_html(@$errors['title']),
		),

		'content' => array(
			'label' => as_lang_html('users/'.$pcode.'_description_label'),
			'tags' => 'name="content" id="content" dir="auto"',
			'type' => 'textarea',
			'rows' => 2,
			'value' => as_html(@$incontent),
			'error' => as_html(@$errors['content']),
		),

		'location' => array(
			'label' => as_lang_html('users/'.$pcode.'_location_label'),
			'tags' => 'name="location" id="location" dir="auto"',
			'value' => as_html(@$inlocation),
			'error' => as_html(@$errors['location']),
		),

		'contact' => array(
			'label' => as_lang_html('users/'.$pcode.'_contact_label'),
			'tags' => 'name="contact" id="contact" dir="auto"',
			'value' => as_html(@$incontact),
			'error' => as_html(@$errors['contact']),
		),

		'field1' => array(
			'label' => as_lang_html('users/'.$pcode.'_field1_label'),
			'tags' => 'name="field1" id="field1" dir="auto"',
			'value' => as_html(@$infield1),
			'error' => as_html(@$errors['field1']),
		),

		'field2' => array(
			'label' => as_lang_html('users/'.$pcode.'_field2_label'),
			'tags' => 'name="field2" id="field2" dir="auto"',
			'value' => as_html(@$infield2),
			'error' => as_html(@$errors['field2']),
		),
		
	),

	'buttons' => array(
		'signup' => array(
			'tags' => 'onclick="as_show_waiting_after(this, false);"',
			'label' => as_lang_html('users/signup_supplier_button'),
		),
	),

	'hidden' => array(
		'usertype' => strtoupper($pcode),
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


$as_content['row_view'][] = array(
	'colms' => array(
		1 => array('class' => 'col-md-6', 'c_items' => array($signupform) ),
	),
);

return $as_content;
