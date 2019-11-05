<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for departments dashboard

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

require_once AS_INCLUDE_DIR . 'APS/as-beex-business.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-department.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-dept-cc.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-dept-fin.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-dept-hr.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-dept-sale.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-dept-stock.php';

$request = "";
$rootpage = "department";
$as_content = as_content_prepare();
$requestlower = strtolower(as_request());
$requestparts = as_request_parts();
$request2 =as_request_part(2);

if (isset($requestparts[1])) $request = strtolower($requestparts[1]);
$checkboxtodisplay = null;

function as_category_nav_to_browse(&$navigation, $categories, $categoryid, $favoritemap)
{
	foreach ($navigation as $key => $navlink) {
		$category = $categories[$navlink['categoryid']];

		if (!$category['childcount']) {
			unset($navigation[$key]['url']);
		} elseif ($navlink['selected']) {
			$navigation[$key]['state'] = 'open';
			$navigation[$key]['url'] = as_path_html('categories/' . as_category_path_request($categories, $category['parentid']));
		} else
			$navigation[$key]['state'] = 'closed';

		if (@$favoritemap[$navlink['categoryid']]) {
			$navigation[$key]['favorited'] = true;
		}
		
		$navigation[$key]['icon'] = as_get_media_html($category['icon'], 20, 20);
		$navigation[$key]['note'] =
			' - <a href="'.as_path_html('items/'.implode('/', array_reverse(explode('/', $category['backpath'])))).'">'.( ($category['pcount']==1)
				? as_lang_html_sub('main/1_article', '1', '1')
				: as_lang_html_sub('main/x_articles', number_format($category['pcount']))
			).'</a>';

		if (strlen($category['content']))
			$navigation[$key]['note'] .= as_html(' - ' . $category['content']);

		if (isset($navlink['subnav']))
			as_category_nav_to_browse($navigation[$key]['subnav'], $categories, $categoryid, $favoritemap);
	}
}

$userid = as_get_logged_in_userid();
$departmentid = as_get('identifier');

$defaulticon ='appicon.png';
$savedoptions = false;
$securityexpired = false;
		
$in = array();

if (is_numeric($request)) {
	$department = BxDepartment::get_single($userid, $request);
	$sections = BxDepartment::get_list($request, true); 

	if (as_clicked('doregister')) {
		require_once AS_INCLUDE_DIR . 'app/post-create.php';
		$subdepartment = new BxDepartment();
		$subdepartment->depttype = as_post_text('depttype');
		$subdepartment->title = as_post_text('title');
		$subdepartment->content = as_post_text('content');
		$subdepartment->parentid = as_post_text('parent');
		$subdepartment->business = $department->title;
		$subdepartment->userid = $userid;

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
			$subdepartment->icon = as_upload_file($_FILES["file"], 'department.jpg', 'icon');
			$departid = $subdepartment->create_department();
			as_redirect($rootpage . '/' . $request, array('alert' => 'success', 'message' => $subdepartment->title .' Sub-Department has been added successfully') );
		}
		else as_redirect($rootpage . '/' . $request );
	}
	else if (as_clicked('dodeletedept')) {
		require_once AS_INCLUDE_DIR . 'app/post-update.php';
		if (as_post_text('edit') !== null) as_db_department_delete(as_post_text('edit'));
		as_redirect($rootpage . '/' . $request );
	}
	else if (as_clicked('docancel')) {
		if (as_post_text('edit') == null) as_redirect($rootpage . '/' . $request );
		else as_redirect( 'department/' . as_post_text('edit'));
	}
	
	else if (as_clicked('dosavedepartment')) {
		require_once AS_INCLUDE_DIR . 'app/post-create.php';
		$subdepartment = new BxDepartment();
		$subdepartment->depttype = as_post_text('depttype');
		$subdepartment->title = as_post_text('title');
		$subdepartment->content = as_post_text('content');
		$subdepartment->parentid = as_post_text('parent');
		$subdepartment->business = $department->title;
		$subdepartment->userid = $userid;

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
			$subdepartment->icon = as_upload_file($_FILES["file"], 'department.jpg', 'icon');
			if (isset($subdepartment->departid))
			{ 
				// changing existing department
				as_db_record_set('businessdepts', 'departid', $subdepartment->departid, 'businessid', $request);
				as_db_record_set('businessdepts', 'departid', $subdepartment->departid, 'title', $intitle);
				as_db_record_set('businessdepts', 'departid', $subdepartment->departid, 'content', $incontent);
				as_redirect( 'department/' . $subdepartment->departid);
			} else { 
				// creating a new one
				$departid = $subdepartment->create_department();
				as_redirect($rootpage . '/' . $request, array('added' => true));
			}
		}
		else as_redirect($rootpage . '/' . $request );
	}
	if (isset($request2))
	{
		switch ( $request2 ) {
			case 'register':
				$in['section'] = new BxDepartment();
				$as_content['title'] = 'Sub-Department <small>REGISTRATION</small>';
				$iconoptions[''] = as_lang_html('main/icon_none');
				if ( isset($department->icon) && strlen($department->icon)){
					$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' .as_get_media_html($department->icon, 35, 35) .
						'</span> <input name="file" type="file">';
					$iconvalue = $iconoptions['uploaded'];
				} else {
					$iconoptions['uploaded'] = '<input name="file" type="file">';
					$iconvalue = $iconoptions[''];
				}
				
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
									as_lang_html('main/online_since') => $sincetime . ' (' . as_lang_sub('main/since_x', $joindate['data']) . ')',
								),
							),
							3 => '',			
							4 => array( 
								'tag' => array('link', 'btn btn-primary btn-block'),
								'href' => as_path_html('department/' . $department->departid),
								'label' => '<b>View This Department</b>',
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

				$formtitle = 'Add a Sub-Department to: ' . $department->title . ' DEPARTMENT';
				
				if (isset($hasalert)) $bodycontent['alert_view'] = array('type' => $hasalert, 'message' => $texttoshow);
				if (isset($hascallout)) $bodycontent['callout_view'] = array('type' => $hascallout, 'message' => $texttoshow);
			
				$bodycontent = array(
					'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
					'title' => $formtitle,
					'type' => 'form',
					'style' => 'tall',

					'ok' => as_get('saved') ? as_lang_html('main/department_saved') : (as_get('added') ? as_lang_html('main/department_added') : null),

					'fields' => array(
						'depttype' => array(
							'label' => as_lang_html('main/sub_select_dept_type'),
							'tags' => 'name="depttype" id="depttype"',
							'type' => 'select',
							'options' => BxDepartment::department_types(),
							//'value' => as_html(isset($indepttype) ? $indepttype : @$department->depttype']),
							'error' => as_html(@$errors['depttype']),
						),

						'title' => array(
							'id' => 'name_display',
							'tags' => 'name="title" id="title"',
							'label' => as_lang_html(count($sections) ? 'main/sub_department_name' : 'main/sub_department_name_first') . ' (Optional)',
							'value' => as_html(@$in['section']->title),
							'error' => as_html(@$errors['title']),
						),
						
						'posticon' => array(
							'type' => 'select-radio',
							'label' => as_lang_html('main/sub_department_icon') . ' (Optional)',
							'tags' => 'name="posticon"',
							'options' => $iconoptions,
							'value' => $iconvalue,
							'error' => as_html(@$errors['posticon']),
						),
						
						'content' => array(
							'id' => 'content_display',
							'tags' => 'name="content"',
							'label' => as_lang_html('main/sub_department_description') . ' (Optional)',
							'value' => as_html(@$in['section']->content),
							'error' => as_html(@$errors['content']),
							'rows' => 2,
						),
					),

					'buttons' => array(
						'save' => array(
							'tags' => 'name="doregister"', // just used for as_recalc_click
							'label' => as_lang_html(isset($section->departid) ? 'main/save_button' : 'main/add_a_sub_department_button'),
						),

						'cancel' => array(
							'tags' => 'name="docancel"',
							'label' => as_lang_html('main/cancel_button'),
						),
					),

					'hidden' => array(
						'parent' => @$department->departid,
						'code' => as_get_form_security_code('section-new'),
					),
				);
				if (isset($sectionid)) 
				{
					$bodycontent['buttons'][] = array(
						'tags' => 'name="dodeletedept" onclick="return confirm(' . as_js(as_lang_html('main/delete_confirm')) . ');"', // just used for as_recalc_click
						'label' => as_lang_html('main/delete_button'),
					);
				}
				$as_content['row_view'][] = array(
					'colms' => array(
						0 => array('class' => 'col-md-4', 'c_items' => array($profile1, $profile2) ),
						2 => array('class' => 'col-lg-8 col-xs-6', 'c_items' => array($bodycontent) ),
					),
				);
				break;
			
		}
	} else {          
		
		if (BxDepartment::is_dept_stock($department->depttype))
			$as_content = BxStockDept::stocks_view($department, $as_content);

		else if (BxDepartment::is_dept_sales($department->depttype))
			$as_content = BxDepartment::general_view($department, $as_content, $sections, $request);

		else if (BxDepartment::is_dept_finance($department->depttype))
			$as_content = BxDepartment::general_view($department, $as_content, $sections, $request);

		else if (BxDepartment::is_dept_hr($department->depttype))
			$as_content = BxDepartment::general_view($department, $as_content, $sections, $request);

		else if (BxDepartment::is_dept_cc($department->depttype))
			$as_content = BxCustomerCare::customers_view($department, $as_content);

		else 
			$as_content = BxDepartment::general_view($department, $as_content, $sections, $request);
		
	}
}
else {
	
}

return $as_content;