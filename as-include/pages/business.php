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

$request = "";
$rootpage = "business";
$as_content = as_content_prepare();
$requestlower = strtolower(as_request());
$requestparts = as_request_parts();
$request2 =as_request_part(2);

if (isset($requestparts[1])) $request = strtolower($requestparts[1]);

$userid = as_get_logged_in_userid();
$departmentid = as_get('identifier');

$hasalert = as_get('alert');
$hascallout = as_get('callout');
$texttoshow = as_get('message');

$department = BxDepartment::get_single($userid, $departmentid);

$defaulticon ='appicon.png';
$usericon ='user.png';
$savedoptions = false;
$securityexpired = false;
		
$in = array();

if (as_clicked('doregister')) {
	require_once AS_INCLUDE_DIR . 'app/limits.php';

	if (as_user_limits_remaining(AS_LIMIT_BUSINESSES)) {
		require_once AS_INCLUDE_DIR . 'app/post-create.php';
		
		$newbiz = new BxBusiness();
		$newbiz->type = as_post_text('type');
		$newbiz->location = as_post_text('location');
		$newbiz->contact = as_post_text('phone')." xx ".as_post_text('email')." xx ".as_post_text('website');
		$newbiz->title = as_post_text('title');
		$newbiz->username = as_post_text('username');
		$newbiz->content = as_post_text('content');
		$newbiz->icon = as_post_text('icon');
		$newbiz->tags = as_post_text('tags');
		$newbiz->userid = $userid;

		if (!as_check_form_security_code('business-register', as_post_text('code'))) {
			$pageerror = as_lang_html('misc/form_security_again');
		} else {
			// T&Cs validation
			if ($show_terms && !$interms)
				$errors['terms'] = as_lang_html('users/terms_not_accepted');

			if (empty($errors)) {
				// creating a new one
				as_limits_increment(null, AS_LIMIT_BUSINESSES);				
				$businessid = $newbiz->create_business();				
				as_redirect($rootpage . '/' . $businessid, array('alert' => 'success', 'message' => $newbiz->title .' Business has been added successfully') );					
			}
		}

	} else
		$pageerror = as_lang('users/signup_limit');
}
if (is_numeric($request)) {
	$business = BxBusiness::get_single($userid, $request);
	$departments = BxDepartment::get_list($request);
	$managers = explode(',', $business->managers);	

	if (as_clicked('dodeletedept')) {
		require_once AS_INCLUDE_DIR . 'app/post-update.php';
		if (as_post_text('edit') !== null) as_db_department_delete(as_post_text('edit'));
		as_redirect($rootpage . '/' . $request, array('added' => true, 'message' => '') );
	}
	
	else if (as_clicked('docancel')) {
		if (as_post_text('edit') == null) as_redirect($rootpage . '/' . $request );
		else as_redirect( 'department/' . as_post_text('edit'));
	}
	
	else if (as_clicked('doupdate')) {
		require_once AS_INCLUDE_DIR . 'app/post-update.php';
		
		$business->location = as_post_text('location');
		$business->contact = as_post_text('phone')." xx ".as_post_text('email')." xx ".as_post_text('website');
		$business->title = as_post_text('title');
		$business->username = as_post_text('username');
		$business->content = as_post_text('content');
		$business->icon = as_post_text('icon');
		$business->tags = as_post_text('tags');

		if (!as_check_form_security_code('business-update', as_post_text('code'))) {
			$pageerror = as_lang_html('misc/form_security_again');
		} else {
			if (empty($errors)) {
				// register and redirect				
				if (isset($business->businessid))
				{ 
					$business->edit_business();
					as_redirect( $rootpage . '/' . $business->businessid);
				}
			}
		}
	}

	else if (as_clicked('domanage')) {
		require_once AS_INCLUDE_DIR . 'app/post-update.php';
		
		$business->managers = as_post_text('managers');

		if (!as_check_form_security_code('business-manage', as_post_text('code'))) {
			$pageerror = as_lang_html('misc/form_security_again');
		} else {
			if (empty($errors)) {				
				if (isset($business->businessid))
				{ 
					$business->edit_business();
					as_redirect( $rootpage . '/' . $business->businessid);
				}
			}
		}
	}
	
	$as_content['title'] = $business->title.' <small>BUSINESS</small>';
	$sincetime = as_time_to_string(as_opt('db_time') - $business->created);
	$joindate = as_when_to_html($business->created, 0);
	$contacts = explode('xx', $business->contact);		
	$profile1 = array( 'type' => 'box', 'theme' => 'primary',
		'body' => array(
			'type' => 'box-body box-profile',
			'items' => array(
				0 => array( 
					'tag' => array('avatar'),
					'img' => '<center>'.as_get_media_html($defaulticon, 200, 200).'</center>',
				),
			),
		),
	);
	
	if ($business->userid == $userid)
	{
		$profile1['body']['items']['link1'] = array( 
			'tag' => array('link', 'btn btn-primary btn-block'),
			'href' => as_path_html($rootpage . '/' . $request . '/edit'),
			'label' => 'Edit Your Business',
		);
		$profile1['body']['items']['modal-managers'] = array( 
			'tag' => array('modalbtn', 'btn btn-primary btn-block'),
			'label' => 'Add or Remove Manager(s)',
		);
	}
	else {
		$profile1['body']['items'][] = array( 
			'tag' => array('link', 'btn btn-primary btn-block'),
			'href' => as_path_html('#'),
			'label' => 'Contact them',
		);
	}

	$navtabs['aboutus'] = array( 'type' => 'box', 
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
					'data' => array( 'text' => $business->location ),
				),
				2 => '',
				3 => array(
					'tag' => array('strong'), 
					'itag' => array('file-text-o', 5), 
					'data' => array( 'text' => 'Description'),
				),
				4 => array(
					'tag' => array('p', 'text-muted'), 
					'data' => array( 'text' => $business->content),
				),
			),
		),
	);
	
	$navtabs['contactus'] = array( 'type' => 'box',
		'body' => array(
			'type' => 'box-body',
			'items' => array(			
				1 => array( 
					'tag' => array('h3', 'profile-username text-center'),
					'data' => array( 'text' => $business->title ),
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
			),			
		),
	);
	
	$profile2 = array( 'type' => 'tabs', 'navs' => array( 'aboutus' => 'About Us', 'contactus' => 'Contact Us'), 'pane' => $navtabs );
	
	$managershtml = '<ul class="products-list product-list-in-box" style="border-top: 1px solid #000">';
	$owner = as_db_select_with_pending(as_db_user_profile($userid));
	
	$managershtml .= '<li class="item"><div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $owner).'</div>';
	$managershtml .= '<div class="product-info"><a href="'.as_path_html('user/' . $owner['handle']).'" class="product-title" style="font-size: 20px;">';
	$managershtml .= $owner['firstname'].' '.$owner['lastname'].'</a><span class="product-description">BUSINESS OWNER</span>';
	$managershtml .= "</div><br></li>\n";

	if (count($managers)) {
		foreach ($managers as $mid) {
			if (!empty($mid) && $userid != $mid) {
				$manager = as_db_select_with_pending(as_db_user_profile($mid));
				$managershtml .= '<li class="item"><div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $manager).'</div>';
				$managershtml .= '<div class="product-info"><a href="'.as_path_html('user/' . $manager['handle']).'" class="product-title" style="font-size: 20px;">';
				$managershtml .= $manager['firstname'].' '.$manager['lastname'].'</a><span class="product-description">BUSINESS MANAGER</span>';
				$managershtml .= "</div><br></li>\n";
			}
		}
	}

	$managershtml .= '</ul>';

	$modalboxes = array(
		'modal-managers' => array(
			'class' => 'modal fade',
			'header' => array(
				'title' => 'BUSINESS MANAGERS',
			),
			'view' => array(
				'type' => 'form', 'style' => 'tall',
				'fields' => array(
					'namesearch' => array(
						'type' => 'custom',
						'html' => '<div class="form-group" id="searchdiv">
						<div class="col-sm-12">
						<label for="searchuser">Enter a User\'s Name, or Email Address</label>
						<input id="searchuser" autocomplete="off" onkeyup="as_searchuser_change(this.value);" type="text" value="" class="form-control">
						<input id="business_id" type="hidden" value="' . $business->businessid . '">
						</div>
						</div>
						<div class="form-group" id="results">
						<div class="col-sm-12">
						<div id="userresults"></div>
						</div>',
					),
					'managerlist' => array(
						'type' => 'custom',
						'html' => '<span id="managerlist">'.$managershtml.'</span>',
					),
				),
			),
		),
	);

	switch ($request2) {
		case 'edit':
			$as_content['title'] = 'Edit: ' .$business->title.' <small>BUSINESS</small>';
			$profile1['body']['items']['link1'] = array( 
				'tag' => array('link', 'btn btn-primary btn-block'),
				'href' => as_path_html($rootpage . '/' . $request ),
				'label' => 'View Your Business',
			);

			if (isset($modalboxes)) $formcontent['modals'] = $modalboxes;
			if (isset($hasalert)) $formcontent['alert_view'] = array('type' => $hasalert, 'message' => $texttoshow);
			if (isset($hascallout)) $formcontent['callout_view'] = array('type' => $hascallout, 'message' => $texttoshow);
			
			$formcontent = array(
				'title' => 'Update Your Business Information',
				'type' => 'form',
				'style' => 'tall',
				'tags' => 'method="post" action="' . as_self_html() . '"',
			
				'fields' => array(
					'title' => array(
						'label' => as_lang_html('main/bs_title_label'),
						'tags' => 'name="title" id="title" autocomplete="off"',
						'value' => as_html(@$business->title),
						'error' => as_html(@$errors['title']),
					),
					
					'username' => array(
						'label' => as_lang_html('main/bs_username_label'),
						'tags' => 'name="username" id="username" autocomplete="off"',
						'value' => as_html(@$business->username),
						'error' => as_html(@$errors['username']),
					),
	
					'location' => array(
						'label' => as_lang_html('main/bs_location_label'),
						'tags' => 'name="location" id="location" autocomplete="off"',
						'value' => as_html(@$business->location),
						'error' => as_html(@$errors['location']),
					),
					
					'content' => array(
						'label' => as_lang_html('main/bs_description_label'),
						'tags' => 'name="content" id="content" autocomplete="off"',
						'type' => 'textarea',
						'rows' => 2,
						'value' => as_html(@$business->content),
						'error' => as_html(@$errors['content']),
					),
			
					'phone' => array(
						'label' => as_lang_html('main/bs_phone_label'),
						'tags' => 'name="phone" id="phone" autocomplete="off"',
						'value' => as_html(@$contacts[0]),
						'error' => as_html(@$errors['phone']),
					),
			
					'email' => array(
						'label' => as_lang_html('main/bs_email_label'),
						'tags' => 'name="email" id="email" autocomplete="off"',
						'value' => as_html(@$contacts[1]),
						'error' => as_html(@$errors['email']),
					),
			
					'website' => array(
						'label' => as_lang_html('main/bs_website_label'),
						'tags' => 'name="website" id="website" autocomplete="off"',
						'value' => as_html(@$contacts[2]),
						'error' => as_html(@$errors['website']),
					),
				),
			
				'buttons' => array(
					'save' => array(
						'tags' => 'onclick="as_show_waiting_after(this, false);"',
						'label' => as_lang_html('main/business_update_button'),
					),
				),
			
				'hidden' => array(
					'type' => 'PUBLIC',
					'department' => '0',
					'icon' => 'business.jpg',
					'tags' => '',
					'doupdate' => '1',
					'code' => as_get_form_security_code('business-update'),
				),
			);

			$as_content['row_view'][] = array(
				'colms' => array(
					0 => array('class' => 'col-md-3', 'c_items' => array($profile1, $profile2) ),
					2 => array('class' => 'col-lg-9 col-xs-6', 'c_items' => array($formcontent) ),
				),
			);

			break;

		case 'newdept':
			$in['department'] = new BxDepartment();
			$as_content['title'] = 'Department <small>REGISTRATION</small>';
			$iconoptions[''] = as_lang_html('main/icon_none');
			if ( isset($department->icon) && strlen($department->icon)){
				$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' .as_get_media_html($department->icon, 35, 35) .
					'</span> <input name="file" type="file">';
				$iconvalue = $iconoptions['uploaded'];
			} else {
				$iconoptions['uploaded'] = '<input name="file" type="file">';
				$iconvalue = $iconoptions[''];
			}
			
			if (isset($departmentid)) 
			{
				$as_content['title'] = '' . $department->title.' <small>DEPARTMENT</small>';
				$sincetime = as_time_to_string(as_opt('db_time') - $department->created);
				$joindate = as_when_to_html($department->created, 0);
				//$contacts = explode('xx', $business['contact']);		
				$profile1 = array( 'type' => 'box', 'theme' => 'primary', 
					'body' => array(
						'type' => 'box-body box-profile',
						'items' => array(
							0 => array( 
								'tag' => array('avatar'),
								'img' => '<center>'. as_get_media_html($department->icon, 300, 300) .'</center>',
							),
							
							1 => array( 
								'tag' => array('h3', 'profile-username text-center'),
								'data' => array( 'text' => $department->title . '<br>DEPARTMENT' ),
							),
							
							2 => array( 
								'tag' => array('list', 'list-group list-group-unbordered'),
								'data' => array(
									//'Mobile:' => $contacts[0],
									//'Email:' => $contacts[1],
									//'Website:' => $contacts[2],
									as_lang_html('main/online_since') => $sincetime . ' (' . as_lang_sub('main/since_x', $joindate['data']) . ')',
								),
							),
							3 => '',			
							4 => array( 
								'tag' => array('link', 'btn btn-primary btn-block'),
								'href' => as_path_html('department/' . $department->departid),
								'label' => 'View This Department',
							),			
						),
					),
				);
				$profile2 = null;
				$iconoptions[''] = as_lang_html('admin/icon_none');
				if ( isset($department->icon) && strlen($department->icon)){
					$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' . as_get_media_html($department->icon, 35, 35) .
						'</span> <input name="file" type="file">';
					$iconvalue = $iconoptions['uploaded'];
				} else {
					$iconoptions['uploaded'] = '<input name="file" type="file">';
					$iconvalue = $iconoptions[''];
				}
			}

			$formtitle = (isset($department->departid) ? 'Edit: '.$department->title . ' Department' : 'Add a Department to this Business' );
			
			$bodycontent = array(
				'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
				'title' => $formtitle,
				'type' => 'form',
				'style' => 'tall',

				'ok' => as_get('saved') ? as_lang_html('main/department_saved') : (as_get('added') ? as_lang_html('main/department_added') : null),

				'fields' => array(
					'depttype' => array(
						'label' => as_lang_html('main/select_dept_type'),
						'tags' => 'name="depttype" id="depttype"',
						'type' => 'select',
						'options' => BxDepartment::department_types(),
						//'value' => as_html(isset($indepttype) ? $indepttype : @$department->depttype']),
						'error' => as_html(@$errors['depttype']),
					),

					'title' => array(
						'id' => 'name_display',
						'tags' => 'name="title" id="title"',
						'label' => as_lang_html(count($departments) ? 'main/department_name' : 'main/department_name_first') . ' (Optional)',
						'value' => as_html(isset($intitle) ? $intitle : @$department->title),
						'error' => as_html(@$errors['title']),
					),
					
					'posticon' => array(
						'type' => 'select-radio',
						'label' => as_lang_html('main/department_icon') . ' (Optional)',
						'tags' => 'name="posticon"',
						'options' => $iconoptions,
						'value' => $iconvalue,
						'error' => as_html(@$errors['posticon']),
					),
					
					'content' => array(
						'id' => 'content_display',
						'tags' => 'name="content"',
						'label' => as_lang_html('main/department_description') . ' (Optional)',
						'value' => as_html(isset($incontent) ? $incontent : @$department->content),
						'error' => as_html(@$errors['content']),
						'rows' => 2,
					),
				),

				'buttons' => array(
					'save' => array(
						'tags' => 'id="dosaveoptions" name="dosavedepartment"', // just used for as_recalc_click
						'label' => as_lang_html(isset($department->departid) ? 'main/save_button' : 'main/add_a_department_button'),
					),

					'cancel' => array(
						'tags' => 'name="docancel"',
						'label' => as_lang_html('main/cancel_button'),
					),
				),

				'hidden' => array(
					'edit' => @$department->departid,
					'parent' => @$department->parentid,
					//'setparent' => (int)$setparent,
					'code' => as_get_form_security_code('business-departments'),
				),
			);
			if (isset($departmentid)) 
			{
				$bodycontent['buttons'][] = array(
					'tags' => 'name="dodeletedept" onclick="return confirm(' . as_js(as_lang_html('main/delete_confirm')) . ');"', // just used for as_recalc_click
					'label' => as_lang_html('main/delete_button'),
				);
			}
			if (isset($modalboxes)) $bodycontent['modals'] = $modalboxes;
			if (isset($hasalert)) $bodycontent['alert_view'] = array('type' => $hasalert, 'message' => $texttoshow);
			if (isset($hascallout)) $bodycontent['callout_view'] = array('type' => $hascallout, 'message' => $texttoshow);
			
			$as_content['row_view'][] = array(
				'colms' => array(
					0 => array('class' => 'col-md-3', 'c_items' => array($profile1, $profile2) ),
					2 => array('class' => 'col-lg-9 col-xs-6', 'c_items' => array($bodycontent) ),
				),
			);
			break;

		case 'products':			
			list($products, $categories) = as_db_select_with_pending(
				as_db_question_selectspec($userid, $selectsort, $start),
				as_db_category_nav_selectspec($editproductid, true, false, true)
			);

			break;

		default:		
			$bodycontent = array(
				'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
				'title' => count($departments) .' DEPARTMENT' . (count($departments) == 1 ? '' : 'S'),
				'type' => 'form',
				'style' => 'tall',
				'ok' => $savedoptions ? as_lang_html('main/options_saved') : null,
		
				'style' => 'tall',
		
				'dash' => array( 'theme' => 'primary'),

				'icon' => array(
					'fa' => 'arrow-left',
					'url' => as_path_html($rootpage),
					'class' => 'btn btn-social btn-primary',
					'label' => as_lang_html('main/back_button'),
				),
		
				'tools' => array(
					'add' => array(
						'type' => 'link',
						'url' => $request.'/newdept',
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
					if (!isset($department->parentid)) {
						if ($department->content == null) $department->content = "...";
						$bodycontent['dash']['items'][$department->departid] = array(
							'img' => as_get_media_html($department->icon, 20, 20), 
							'label' => as_html($department->title).' Department', 
							'numbers' => '1 User', 'description' => $department->content,
							'link' => as_path_html('department/' . $department->departid),
							'infors' => array(
								'depts' => array('icount' => $department->sections, 'ilabel' => 'Sub-Departments', 'ibadge' => 'columns'),
								'users' => array('icount' => 1, 'ilabel' => 'Users', 'ibadge' => 'users', 'inew' => 3),
							),
						);
					}
					$k++;
				}
		
			}

			if (isset($modalboxes)) $bodycontent['modals'] = $modalboxes;
			if (isset($hasalert)) $bodycontent['alert_view'] = array('type' => $hasalert, 'message' => $texttoshow);
			if (isset($hascallout)) $bodycontent['callout_view'] = array('type' => $hascallout, 'message' => $texttoshow);
			
			$as_content['row_view'][] = array(
				'colms' => array(
					0 => array('class' => 'col-md-3', 'c_items' => array($profile1, $profile2) ),
					2 => array('class' => 'col-lg-9 col-xs-6', 'c_items' => array($bodycontent) ),
				),
			);
			break;
	}
}
else {
	switch ( $request ) {
		case 'register':
			$in['business'] = new BxBusiness;
			$as_content['title'] = 'Business <small>REGISTRATION</small>';
			
			$formcontent = array(
				'title' => as_lang_html('main/get_started'),
				'type' => 'form',
				'style' => 'tall',
				'tags' => 'method="post" action="' . as_self_html() . '"',
			
				'fields' => array(
					'title' => array(
						'label' => as_lang_html('main/bs_title_label'),
						'tags' => 'name="title" id="title" autocomplete="off"',
						'value' => as_html(@$in['business']->title),
						'error' => as_html(@$errors['title']),
					),
					
					'username' => array(
						'label' => as_lang_html('main/bs_username_label'),
						'tags' => 'name="username" id="username" autocomplete="off"',
						'value' => as_html(@$in['business']->username),
						'error' => as_html(@$errors['username']),
					),
	
					'location' => array(
						'label' => as_lang_html('main/bs_location_label'),
						'tags' => 'name="location" id="location" autocomplete="off"',
						'value' => as_html(@$in['business']->location),
						'error' => as_html(@$errors['location']),
					),
					
					'content' => array(
						'label' => as_lang_html('main/bs_description_label'),
						'tags' => 'name="content" id="content" autocomplete="off"',
						'type' => 'textarea',
						'rows' => 2,
						'value' => as_html(@$in['business']->content),
						'error' => as_html(@$errors['content']),
					),
			
					'phone' => array(
						'label' => as_lang_html('main/bs_phone_label'),
						'tags' => 'name="phone" id="phone" autocomplete="off"',
						'value' => as_html(@$in['phone']),
						'error' => as_html(@$errors['phone']),
					),
			
					'email' => array(
						'label' => as_lang_html('main/bs_email_label'),
						'tags' => 'name="email" id="email" autocomplete="off"',
						'value' => as_html(@$in['email']),
						'error' => as_html(@$errors['email']),
					),
			
					'website' => array(
						'label' => as_lang_html('main/bs_website_label'),
						'tags' => 'name="website" id="website" autocomplete="off"',
						'value' => as_html(@$in['website']),
						'error' => as_html(@$errors['website']),
					),
				),
			
				'buttons' => array(
					'save' => array(
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
					'code' => as_get_form_security_code('business-register'),
				),
			);
			if (isset($modalboxes)) $formcontent['modals'] = $modalboxes;
			if (isset($hasalert)) $formcontent['alert_view'] = array('type' => $hasalert, 'message' => $texttoshow);
			if (isset($hascallout)) $formcontent['callout_view'] = array('type' => $hascallout, 'message' => $texttoshow);
			
			$as_content['row_view'][] = array(
				'colms' => array(
					0 => array('class' => 'col-md-9', 'c_items' => array($formcontent) ),
				),
			);
			break;
			
		default:		
			$as_content['title'] = 'Your Businesses <small>DASHBOARD</small>';
			
			$businesses = BxBusiness::get_list($userid);

			$dashlist = array( 'type' => 'bslist', 'theme' => 'primary', 
				'title' => count($businesses) .' BUSINESS' . as_many(count($businesses), 'ES'), 
				'tools' => array(
					'add' => array( 'type' => 'link', 'label' => 'NEW BUSINESS',
					'url' => as_path_html($rootpage.'/register'), 'class' => 'btn btn-primary btn-block' )
				),
			);
				
			if (count($businesses)){				
				foreach ($businesses as $business){
					$managers = explode(',', $business->managers);
					$dashlist['items'][$business->businessid] = array('img' => as_path_html('./as-media/' . $defaulticon ), 
						'label' => $business->title . '| Your Role: ' . ($business->userid == $userid ? 'OWNER' : 'MANAGER'),
						'description' => $business->content, 'link' => 'business/'.$business->businessid,
						'numbers' => array(
							'users' => array('ncount' => count($managers), 'nlabel' => 'Manager'.as_many(count($managers)), 
								'tags' => 'data-toggle="modal" data-target="#managers_'.$business->businessid.'"'),
							'depts' => array('ncount' => $business->departments, 'nlabel' => 'Department'.as_many($business->departments),
								'tags' => 'data-toggle="modal" data-target="#modal-danger"'),
						),
					);
					
					$departments = BxDepartment::get_list($business->businessid);
					if (count($departments)) {				
						$navdepartmenthtml = '';
						$k = 1;
						foreach ($departments as $department) {
							if (!isset($department->parentid)) {
								if ($department->content == null) $department->content = "...";
								$dashlist['items'][$business->businessid]['parts'][] = array(
									'label' => as_html($department->title).' DEPT', 
									'description' => $department->content,
									'link' => as_path_html('department/' . $department->departid),
									'managers' => $department->managers,
									'sections' => $department->sections,
									'tags' => ' style="cursor:pointer;"',
								);
							}
							$k++;
						}
				
					}
				}
			}
			if (isset($modalboxes)) $dashlist['modals'] = $modalboxes;
			if (isset($hasalert)) $dashlist['alert_view'] = array('type' => $hasalert, 'message' => $texttoshow);
			if (isset($hascallout)) $dashlist['callout_view'] = array('type' => $hascallout, 'message' => $texttoshow);
			
			$as_content['row_view'][] = array(
				'colms' => array(
					0 => array('class' => 'col-lg-12 col-xs-12', 'c_items' => array($dashlist) ),
				),
			);
	
			break;
	}
}

return $as_content;