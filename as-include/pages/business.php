<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for user's dashboard

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

$as_content = as_content_prepare();
$request = as_request_part(1);
$userid = as_get_logged_in_userid();

$in = array();

if (as_clicked('doregister')) {
	require_once AS_INCLUDE_DIR . 'app/limits.php';

	if (as_user_limits_remaining(AS_LIMIT_BUSINESSES)) {
		require_once AS_INCLUDE_DIR . 'app/users-edit.php';
		
		$in['type'] = as_post_text('type');
		$in['category'] = as_post_text('category');
		$in['location'] = as_post_text('location');
		$in['contact'] = as_post_text('phone')." xx ".as_post_text('email')." xx ".as_post_text('website');
		$in['title'] = as_post_text('title');
		$in['username'] = as_post_text('username');
		$in['content'] = as_post_text('content');
		$in['icon'] = as_post_text('icon');
		$in['tags'] = as_post_text('tags');

		if (!as_check_form_security_code('business-new', as_post_text('code'))) {
			$pageerror = as_lang_html('misc/form_security_again');
		} else {
			// T&Cs validation
			if ($show_terms && !$interms)
				$errors['terms'] = as_lang_html('users/terms_not_accepted');

			if (empty($errors)) {
				// register and redirect
				as_limits_increment(null, AS_LIMIT_BUSINESSES);

				$businessid = as_create_new_business($in['type'], $in['category'], $in['location'], $in['contact'], $in['title'], $in['username'], $in['content'], $in['icon'], $in['tags'], $userid);		

				as_redirect('business/' . $businessid );
			}
		}

	} else
		$pageerror = as_lang('users/signup_limit');
}

switch ( $request ) {
	case 'new':
		$as_content['title'] = 'Business <small>Registration</small>';
		
		$newbs = array(
			'title' => as_lang_html('main/get_started'),
			'type' => 'form',
			'style' => 'tall',
			'tags' => 'method="post" action="' . as_self_html() . '"',
		
			'fields' => array(
				'title' => array(
					'label' => as_lang_html('main/bs_title_label'),
					'tags' => 'name="title" id="title" dir="auto"',
					'value' => as_html(@$in['title']),
					'error' => as_html(@$errors['title']),
				),
				
				'username' => array(
					'label' => as_lang_html('main/bs_username_label'),
					'tags' => 'name="username" id="username" dir="auto"',
					'value' => as_html(@$in['username']),
					'error' => as_html(@$errors['username']),
				),

				'location' => array(
					'label' => as_lang_html('main/bs_location_label'),
					'tags' => 'name="location" id="location" dir="auto"',
					'value' => as_html(@$in['location']),
					'error' => as_html(@$errors['location']),
				),
				
				'content' => array(
					'label' => as_lang_html('main/bs_description_label'),
					'tags' => 'name="content" id="content" dir="auto"',
					'type' => 'textarea',
					'rows' => 2,
					'value' => as_html(@$in['content']),
					'error' => as_html(@$errors['content']),
				),
		
				'phone' => array(
					'label' => as_lang_html('main/bs_phone_label'),
					'tags' => 'name="phone" id="phone" dir="auto"',
					'value' => as_html(@$in['phone']),
					'error' => as_html(@$errors['phone']),
				),
		
				'email' => array(
					'label' => as_lang_html('main/bs_email_label'),
					'tags' => 'name="email" id="email" dir="auto"',
					'value' => as_html(@$in['email']),
					'error' => as_html(@$errors['email']),
				),
		
				'website' => array(
					'label' => as_lang_html('main/bs_website_label'),
					'tags' => 'name="website" id="website" dir="auto"',
					'value' => as_html(@$in['website']),
					'error' => as_html(@$errors['website']),
				),
			),
		
			'buttons' => array(
				'register' => array(
					'tags' => 'onclick="as_show_waiting_after(this, false);"',
					'label' => as_lang_html('main/business_register_button'),
				),
			),
		
			'hidden' => array(
				'type' => 'PUBLIC',
				'category' => '0',
				'icon' => 'business.jpg',
				'tags' => '',
				'doregister' => '1',
				'code' => as_get_form_security_code('business-new'),
			),
		);
		$as_content['row_view'][] = array(
			'colms' => array(
				0 => array('class' => 'col-md-9', 'c_items' => array($newbs) ),
			),
		);
		break;
		
	default:		
		$as_content['title'] = 'Your Businesses <small>Dashboard</small>';
		
		list($businesses) = as_db_select_with_pending(
			as_db_business_list($userid)
		);
		$bzcount = count($businesses);
		//url, updates,img, icon, label
		$item1 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
			'updates' => array('bg-green', 'NEW'), 'title' => 'New Business', 'icon' => 'edit', 'link' => 'business/new');
		
		$item2 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
			'updates' => array('bg-blue', 'NEW'), 'title' => 'Action 1', 'icon' => 'edit', 'link' => '#');
		
		$item3 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
			'updates' => array('bg-red', 'NEW'), 'title' => 'Action 2', 'icon' => 'edit', 'link' => '#');
		
		$item4 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
			'updates' => array('bg-yellow', 'NEW'), 'title' => 'Action 3', 'icon' => 'edit', 'link' => '#');

		$defaulticon = as_opt('site_url') . '/as-media/appicon.png';
	
		$column1 = array( 'type' => 'small-box', 'theme' => 'yellow', 
			'count' => '44', 'title' => 'User Registrations', 
			'icon' => 'person-add', 'link' => '#');

		$column2 = array( 'type' => 'list', 'theme' => 'primary', 
			'title' => 'You have (' . $bzcount .') Businesses', 
			'body' => array('type' => 'product')
		);
		//print_r($businesses);

		/*if ($bzcount){
			foreach ($businesses as $business => $biz){
				$column2['body']['items'][] = array(
					'img' => $defaulticon, 'label' => 'Panel Doors', 'price' => 'Kshs. 2500',
					'description' => $biz['title'],
				);
			}
		}*/

		$as_content['row_view'][] = array(
			'colms' => array(
				1 => array('class' => 'col-lg-4 col-xs-6', 'c_items' => array($item1, $item2, $item3, $item4) ),
				2 => array('class' => 'col-lg-8 col-xs-6', 'c_items' => array($column2) ),
			),
		);

		break;
}
return $as_content;