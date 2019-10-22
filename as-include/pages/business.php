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

$defaulticon ='appicon.png';
		
$in = array();

if (as_clicked('doregister')) {
	require_once AS_INCLUDE_DIR . 'app/limits.php';

	if (as_user_limits_remaining(AS_LIMIT_BUSINESSES)) {
		require_once AS_INCLUDE_DIR . 'app/post-create.php';
		
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

				$departid = as_create_new_business($in['type'], $in['location'], $in['contact'], $in['title'], $in['username'], $in['content'], $in['icon'], $in['tags'], $userid);
				as_redirect('business/' . $departid );
			}
		}

	} else
		$pageerror = as_lang('users/signup_limit');
}
if (is_numeric($request)) {
	list($business, $departments) = as_db_select_with_pending(
		as_db_business_selectspec($userid, $request),
		as_db_departments_list($request)
	);

	$editdepartment = @$departments[$editdepartid];

	if (isset($editdepartment)) {
		$parentid = as_get('addsub');
		if (isset($parentid))
			$editdepartment = array('parentid' => $parentid);

	} else {
		if (as_clicked('dosavedepartment')) {
			require_once AS_INCLUDE_DIR . 'app/post-create.php';

			$in['title'] = as_post_text('title');
			$in['content'] = as_post_text('content');
			$in['parentid'] = as_post_text('parent');
			if (null !== as_post_text('parent')) $in['parentid'] = 0;

			$editdepartment = array('parentid' => strlen($parentid) ? $parentid : null);
			if (is_array(@$_FILES["file"])) {
				$iconfileerror = $_FILES["file"]['error'];
				if ($iconfileerror === 1) $errors['posticon'] = as_lang('main/file_upload_limit_exceeded');
				elseif ($iconfileerror === 0 && $_FILES["file"]['size'] > 0) {
					require_once AS_INCLUDE_DIR . 'app/limits.php';

					$toobig = as_image_file_too_big($_FILES["file"]['tmp_name'], 500);

					if ($toobig) $errors['posticon'] = as_lang_sub('main/image_too_big_x_pc', (int)($toobig * 100));
				}
			}
			
			// Perform appropriate database action
			if (empty($errors)) {
				$posticon = as_upload_file($_FILES["file"], 'department.jpg', 'icon');
				
				if (isset($editdepartment['departid'])) { // changing existing department
					/*as_db_department_rename($editdepartment['departid'], $inname, $inslug);
					
					$recalc = false;

					if ($setparent) {
						as_db_department_set_parent($editdepartment['departid'], $inparentid);
						$recalc = true;
					} else {
						as_db_department_set_content($editdepartment['departid'], $incontent, $posticon);
						as_db_department_set_position($editdepartment['departid'], $inposition);
						$recalc = $hassubdepartment && $inslug !== $editdepartment['tags'];
					}
					
					as_redirect(as_request(), array('edit' => $editdepartment['departid'], 'saved' => true, 'recalc' => (int)$recalc));*/
				} else { // creating a new one
					$departid = as_db_department_create($request, $in['parentid'], $in['title'], $in['content'], $posticon, $userid);					
					//as_redirect(as_request(), array('edit' => $in['parentid'], 'added' => true));
					as_redirect('business/' . $request );
				}
			}
		}
	}

	$setmissing = as_post_text('missing') || as_get('missing');

	$setparent = !$setmissing && (as_post_text('setparent') || as_get('setparent')) && isset($editdepartment['departid']);

	$hassubdepartment = false;
	/*foreach ($departments as $department) {
		if (!strcmp($department['parentid'], $editdepartid))
			$hassubdepartment = true;
	}*/

	$as_content['title'] = $business['title'].' <small>Business</small>';
	$sincetime = as_time_to_string(as_opt('db_time') - $business['created']);
	$joindate = as_when_to_html($business['created'], 0);
	$contacts = explode('xx', $business['contact']);		
	$profile1 = array( 'type' => 'box', 'theme' => 'primary', 
		'body' => array(
			'type' => 'box-body box-profile',
			'items' => array(
				0 => array( 
					'tag' => array('avatar'),
					'img' => '<center>'.as_get_media_html($defaulticon, 200, 200).'</center>',
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
	
	switch (as_request_part(2)) {
		case 'new':
		case 'edit':
			$as_content['title'] = 'Department <small>Registration</small>';
			$iconoptions[''] = as_lang_html('main/icon_none');
			if ( isset($editdepartment['icon']) && strlen($editdepartment['icon'])){
				$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' .as_get_media_html($editdepartment['icon'], 35, 35) .
					'</span> <input name="file" type="file">';
				$iconvalue = $iconoptions['uploaded'];
			} else {
				$iconoptions['uploaded'] = '<input name="file" type="file">';
				$iconvalue = $iconoptions[''];
			}
			
			$formtitle = (isset($editdepartment['departid']) ? 'Edit Department in this Business: '.$editdepartment['title'] : 'Add a Department to this Business' );
			
			$bodycontent = array(
				'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
				'title' => $formtitle,
				'type' => 'form',
				'style' => 'tall',

				'ok' => as_get('saved') ? as_lang_html('main/department_saved') : (as_get('added') ? as_lang_html('main/department_added') : null),

				'fields' => array(
					'title' => array(
						'id' => 'name_display',
						'tags' => 'name="title" id="title"',
						'label' => as_lang_html(count($departments) ? 'main/department_name' : 'main/department_name_first'),
						'value' => as_html(isset($intitle) ? $intitle : @$editdepartment['title']),
						'error' => as_html(@$errors['title']),
					),
					
					'posticon' => array(
						'type' => 'select-radio',
						'label' => as_lang_html('main/department_icon'),
						'tags' => 'name="posticon"',
						'options' => $iconoptions,
						'value' => $iconvalue,
						'error' => as_html(@$errors['posticon']),
					),
					
					'content' => array(
						'id' => 'content_display',
						'tags' => 'name="content"',
						'label' => as_lang_html('main/department_description'),
						'value' => as_html(isset($incontent) ? $incontent : @$editdepartment['content']),
						'error' => as_html(@$errors['content']),
						'rows' => 2,
					),
				),

				'buttons' => array(
					'save' => array(
						'tags' => 'id="dosaveoptions"', // just used for as_recalc_click
						'label' => as_lang_html(isset($editdepartment['departid']) ? 'main/save_button' : 'main/add_a_department_button'),
					),

					'cancel' => array(
						'tags' => 'name="docancel"',
						'label' => as_lang_html('main/cancel_button'),
					),
				),

				'hidden' => array(
					'dosavedepartment' => '1', // for IE
					'edit' => @$editdepartment['departid'],
					'parent' => @$editdepartment['parentid'],
					'setparent' => (int)$setparent,
					'code' => as_get_form_security_code('business-departments'),
				),
			);

			break;

		default:
		
			$savedoptions = false;
			$securityexpired = false;
		
			$bodycontent = array(
				'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
				'title' => 'This Business has ' . count($departments) .' Departments, You may add more',
				'type' => 'form',
				'style' => 'tall',
				'ok' => $savedoptions ? as_lang_html('main/options_saved') : null,
		
				'style' => 'tall',
		
				'dash' => array( 'theme' => 'primary'),
		
				'tools' => array(
					'add' => array(
						'type' => 'link',
						'url' => $request.'/new',
						'tags' => 'name="doadddepartment"',
						'class' => 'btn btn-primary btn-block',
						'label' => as_lang_html('main/add_department_button'),
					),
				),
				
				'hidden' => array( 'code' => as_get_form_security_code('business-departments')),
			);
				
			if (count($departments)) {
				unset($bodycontent['fields']['intro']);
		
				$navdepartmenthtml = '';
				$k = 1;
				foreach ($departments as $department) {
					if (!isset($department['parentid'])) {
						$bodycontent['dash']['items'][] = array(
							'img' => as_get_media_html($department['icon'], 20, 20), 
							'label' => as_html($department['title']).' Department', 
							'numbers' => '1 User', 'description' => $department['content'], 
							'link' => as_path_html('business/dept', array('edit' => $department['departid'])),
							'infors' => array(
								'users' => array('icount' => 1, 'ilabel' => 'Users', 'ibadge' => 'users', 'inew' => 3),
							),
						);
					}
					$k++;
				}
		
			} 
		
			break;
	}

	$as_content['row_view'][] = array(
		'colms' => array(
			0 => array('class' => 'col-md-4', 'c_items' => array($profile1, $profile2) ),
			2 => array('class' => 'col-lg-8 col-xs-6', 'c_items' => array($bodycontent) ),
		),
	);
}
else {
	switch ( $request ) {
		case 'register':
			$as_content['title'] = 'Business <small>Registration</small>';
			
			$formcontent = array(
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
					0 => array('class' => 'col-md-9', 'c_items' => array($formcontent) ),
				),
			);
			break;
			
		default:		
			$as_content['title'] = 'Your Businesses <small>Dashboard</small>';
			
			$businesses = as_db_select_with_pending(as_db_business_list($userid));
			//url, updates,img, icon, label
			$item1 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-green', 'NEW'), 'title' => 'New Business', 'icon' => 'plus', 'link' => 'business/register');
			
			$item2 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-blue', 'NEW'), 'title' => 'Action 1', 'icon' => 'edit', 'link' => '#');
			
			$item3 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-red', 'NEW'), 'title' => 'Action 2', 'icon' => 'wrench', 'link' => '#');
			
			$item4 = array( 'type' => 'btn-app', 'theme' => 'aqua', 'info' => 'Get started',
				'updates' => array('bg-yellow', 'NEW'), 'title' => 'Action 3', 'icon' => 'cog', 'link' => '#');
			
			$dashlist = array( 'type' => 'dashlist', 'theme' => 'primary', 'title' => 'You have ' . count($businesses) .' Businesses, You may add more', 
				'tools' => array(
					'add' => array( 'type' => 'link', 'label' => 'NEW BUSINESS',
					'url' => $request.'business/register', 'class' => 'btn btn-primary btn-block' )
				),
			);
				
			if (count($businesses)){
				
				foreach ($businesses as $business => $biz){
					//$bizdeparts = as_db_select_with_pending(as_db_department_nav_selectspec($biz['businessid'], 1, true, false, true));

					$dashlist['items'][] = array('img' => as_get_media_html($defaulticon, 20, 20), 'label' => $biz['title'], 'numbers' => '1 User', 
					'description' => $biz['content'], 'link' => 'business/'.$biz['businessid'],
						'infors' => array(
							'depts' => array('icount' => 0, 'ilabel' => 'Departments', 'ibadge' => 'columns'),
							'users' => array('icount' => 1, 'ilabel' => 'Users', 'ibadge' => 'users', 'inew' => 3),
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