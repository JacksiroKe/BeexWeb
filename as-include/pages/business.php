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

$defaulticon = as_opt('site_url') . '/as-media/appicon.png';
		
$in = array();

if (as_clicked('doregister')) {
	require_once AS_INCLUDE_DIR . 'app/limits.php';

	if (as_user_limits_remaining(AS_LIMIT_BUSINESSES)) {
		require_once AS_INCLUDE_DIR . 'app/users-edit.php';
		
		$in['type'] = as_post_text('type');
		$in['department'] = as_post_text('department');
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

				$businessid = as_create_new_business($in['type'], $in['department'], $in['location'], $in['contact'], $in['title'], $in['username'], $in['content'], $in['icon'], $in['tags'], $userid);		

				as_redirect('business/' . $businessid );
			}
		}

	} else
		$pageerror = as_lang('users/signup_limit');
}
if (is_numeric($request)) {
	$business = as_db_select_with_pending(as_db_business_selectspec($userid, $request));
	$as_content['title'] = $business['title'].' <small>Business</small>';

	switch (as_request_part(2)) 
	{
		case 'edit':

			break;

		default:
			$sincetime = as_time_to_string(as_opt('db_time') - $business['created']);
			$joindate = as_when_to_html($business['created'], 0);
			$contacts = explode('xx', $business['contact']);		
			$profile1 = array( 'type' => 'box', 'theme' => 'primary', 
				'body' => array(
					'type' => 'box-body box-profile',
					'items' => array(
						0 => array( 
							'tag' => array('avatar'),
							'img' => '<center><img src="'.$defaulticon.'" width="100" height="100" class="img-circle" style="border-radius: 75px" alt="User Image" /></center>',
							
						),
						
						1 => array( 
							'tag' => array('h3', 'profile-username text-center'),
							'data' => array( 'text' => $business['title'] ),
						),
						
						2 => array( 
							'tag' => array('list', 'list-group list-group-unbordered'),
							'data' => array(
								'Mobile:' => $contacts[0],
								'Email:' => $contacts[1],
								'Website:' => $contacts[2],
								as_lang_html('main/online_since') => $sincetime . ' (' . as_lang_sub('main/since_x', $joindate['data']) . ')',
							),
						),
						3 => '',			
					),
				),
			);
			
			if ($business['userid'] == $userid)
			{
				$profile1['body']['items'][] = array( 
					'tag' => array('link', 'btn btn-primary btn-block'),
					'href' => $request.'/edit',
					'label' => '<b>Edit This Business</b>',
				);
			}
			else {
				$profile1['body']['items'][] = array( 
					'tag' => array('link', 'btn btn-primary btn-block'),
					'href' => '#',
					'label' => '<b>Follow</b>',
				);
			}
		
			$profile2 = array( 'type' => 'box', 'theme' => 'information', 'title' => 'About Us', 
				'body' => array(
					'type' => 'box-body',
					'items' => array(
						0 => array(
							'tag' => array('strong'), 
							'itag' => array('map-marker', 5), 
							'data' => array( 'text' => 'Location' ),
						),
						1 => array(
							'tag' => array('p', 'text-muted'), 
							'data' => array( 'text' => $business['location'] ),
						),
						2 => '',
						3 => array(
							'tag' => array('strong'), 
							'itag' => array('file-text-o', 5), 
							'data' => array( 'text' => 'Description'),
						),
						4 => array(
							'tag' => array('p', 'text-muted'), 
							'data' => array( 'text' => $business['content']),
						),
					),
				),
			);
			
			$posts[] = array(
				'type' => 'posts',
				'class' => 'post clearfix',
				'blocks' => array(
					'user-block' => array(
						'elem' => 'div',
						'user' => 'Diamond Ltd',
						'img' => 'http://localhost/beex/as-media/user.jpg',
						'text' => 'Posted a new deal - 3 days ago',
					),
					'para' => array(
						'elem' => 'p',
						'text' => 'New Mahogany products are now available at very affordable prices contact us today for quick sales.',
					),
				),
			);
			
			$tlines[]= array(
				'type' => 'tlines', 'class' => 'time-label', 'data' => array( 'text' => '10 Feb. 2014'),
			);
			
			$tlines[]= array(
				'type' => 'tlines',
				'data' => array(
					'itag' => array('envelope', 'blue'),
					'sub-data' => array(
						'time' => '12:05',
						'header' => '<a href="#">Support Team</a> sent you an email',
						'body' => 'Etsy doostang zoodles disqus groupon greplin oooj voxy zoodles,
								weebly ning heekya handango imeem plugg dopplr jibjab, movity
								jajah plickers sifteo edmodo ifttt zimbra. Babblely odeo kaboodle
								quora plaxo ideeli hulu weebly balihoo...',
					),
				),
			);
			
			$tlines[]= array(
				'type' => 'tlines', 'data' => array('itag' => array('clock-o', 'gray')),
			);
			
			$account = array(
				'tags' => 'method="post" action="' . as_self_html() . '"',
				'type' => 'form',
				'style' => 'tall',
			
				'fields' => array(
					/*'firstname' => array(
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
					),*/
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
						
			//$departments = as_db_select_with_pending(as_db_business_departments($userid, $request));
			//$departments = as_db_select_with_pending(as_db_business_departments($request));
			
			$editdepartid = as_post_text('edit');
			if (!isset($editdepartid))
				$editdepartid = as_get('edit');
			if (!isset($editdepartid))
				$editdepartid = as_get('addsub');

			$departments = as_db_select_with_pending(as_db_business_departments($request, $editdepartid, true, false, true));

			$bzcount = count($departments);
			$dashlist = array( 'type' => 'dashlist', 'theme' => 'primary', 'title' => 'This Business has ' . $bzcount .' Departments, You may add more', 
				'tools' => array(
					'add' => array( 'type' => 'link', 'label' => as_lang_html('main/add_department_button'),
					'url' => $request.'/newdept', 'class' => 'btn btn-primary btn-block' )
				),
			);
			
			/*'tools' => array(
				'add' => array(
					'type' => 'submit', 
					'tags' => 'name="doadddepartment"',
					'label' => as_lang_html('admin/add_department_button'),
				),
			),*/
			
			/*if ($bzcount){
				foreach ($businesses as $business => $biz){
					$dashlist['items'][$biz['businessid']] = array('img' => $defaulticon, 'label' => $biz['title'], 'numbers' => '1 User', 
					'description' => $biz['content'], 'link' => 'business/'.$biz['businessid'],
						'infors' => array(
							'depts' => array('icount' => 3, 'ilabel' => 'Departments', 'ibadge' => 'columns'),
							'users' => array('icount' => 10, 'ilabel' => 'Users', 'ibadge' => 'users', 'inew' => 3),
						),
					);
				}
			}*/
	
			
			$as_content['row_view'][] = array(
				'colms' => array(
					0 => array('class' => 'col-md-4', 'c_items' => array($profile1, $profile2) ),
					2 => array('class' => 'col-lg-8 col-xs-6', 'c_items' => array($dashlist) ),
				),
			);
		break;
	}
	
}
else {
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
					'department' => '0',
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
			
			$businesses = as_db_select_with_pending(as_db_business_list($userid));
			
			$bzcount = count($businesses);
			//url, updates,img, icon, label
			$item1 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-green', 'NEW'), 'title' => 'New Business', 'icon' => 'plus', 'link' => 'business/new');
			
			$item2 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-blue', 'NEW'), 'title' => 'Action 1', 'icon' => 'edit', 'link' => '#');
			
			$item3 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-red', 'NEW'), 'title' => 'Action 2', 'icon' => 'wrench', 'link' => '#');
			
			$item4 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-yellow', 'NEW'), 'title' => 'Action 3', 'icon' => 'cog', 'link' => '#');
	
			if ($bzcount){
				$dashlist = array( 'type' => 'dashlist', 'theme' => 'primary', 'title' => 'You have ' . $bzcount .' Businesses, You may add more' );
				
				foreach ($businesses as $business => $biz){
					$dashlist['items'][$biz['businessid']] = array('img' => $defaulticon, 'label' => $biz['title'], 'numbers' => '1 User', 
					'description' => $biz['content'], 'link' => 'business/'.$biz['businessid'],
						'infors' => array(
							'depts' => array('icount' => 3, 'ilabel' => 'Departments', 'ibadge' => 'columns'),
							'users' => array('icount' => 10, 'ilabel' => 'Users', 'ibadge' => 'users', 'inew' => 3),
						),
					);
				}
			}
	
			$as_content['row_view'][] = array(
				'colms' => array(
					1 => array('class' => 'col-lg-4 col-xs-6', 'c_items' => array($item1, $item2, $item3, $item4) ),
					2 => array('class' => 'col-lg-8 col-xs-6', 'c_items' => array($dashlist) ),
				),
			);
	
			break;
	}
}

return $as_content;