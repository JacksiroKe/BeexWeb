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

				$departid = as_create_new_business($in['type'], $in['department'], $in['location'], $in['contact'], $in['title'], $in['username'], $in['content'], $in['icon'], $in['tags'], $userid);		

				as_redirect('business/' . $departid );
			}
		}

	} else
		$pageerror = as_lang('users/signup_limit');
}
if (is_numeric($request)) {
	require_once AS_INCLUDE_DIR . 'db/post-create.php';
	$business = as_db_select_with_pending(as_db_business_selectspec($userid, $request));
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
	
	$editdepartmentid = as_post_text('edit');
	if (!isset($editdepartmentid)) $editdepartmentid = as_get('edit');
	if (!isset($editdepartmentid)) $editdepartmentid = as_get('addsub');
	
	// Process saving an old or new department

	if (as_clicked('docancel')) {
		if ($setmissing || $setparent)
			as_redirect(as_request(), array('edit' => $editdepartment['departmentid']));
		elseif (isset($editdepartment['departmentid']))
			as_redirect(as_request());
		else
			as_redirect(as_request(), array('edit' => @$editdepartment['parentid']));

	} elseif (as_clicked('dosetmissing')) {
		if (!as_check_form_security_code('admin/departments', as_post_text('code')))
			$securityexpired = true;

		else {
			$inreassign = as_get_department_field_value('reassign');
			as_db_department_reassign($editdepartment['departmentid'], $inreassign);
			as_redirect(as_request(), array('recalc' => 1, 'edit' => $editdepartment['departmentid']));
		}

	} elseif (as_clicked('dosavedepartment')) {
		if (!as_check_form_security_code('admin/departments', as_post_text('code'))) $securityexpired = true;

		elseif (as_post_text('dodelete')) {
			if (!$hassubdepartment) {
				$inreassign = as_get_department_field_value('reassign');
				as_db_department_reassign($editdepartment['departmentid'], $inreassign);
				as_db_department_delete($editdepartment['departmentid']);
				as_redirect(as_request(), array('recalc' => 1, 'edit' => $editdepartment['parentid']));
			}

		} else {
			require_once AS_INCLUDE_DIR . 'util/string.php';

			$inname = as_post_text('name');
			$incontent = as_post_text('content');
			$inparentid = $setparent ? as_get_department_field_value('parent') : $editdepartment['parentid'];
			$inposition = as_post_text('position');
			$errors = array();

			// Check the parent ID

			$indepartments = as_db_select_with_pending(as_db_department_nav_selectspec($inparentid, true));

			// Verify the name is legitimate for that parent ID

			if (empty($inname)) $errors['name'] = as_lang('main/field_required');
			elseif (as_strlen($inname) > AS_DB_MAX_CAT_PAGE_TITLE_LENGTH) $errors['name'] = as_lang_sub('main/max_length_x', AS_DB_MAX_CAT_PAGE_TITLE_LENGTH);
			else {
				foreach ($indepartments as $department) {
					if (!strcmp($department['parentid'], $inparentid) &&
						strcmp($department['departmentid'], @$editdepartment['departmentid']) &&
						as_strtolower($department['title']) == as_strtolower($inname)
					) {
						$errors['name'] = as_lang('main/department_already_used');
					}
				}
			}

			// Verify the slug is legitimate for that parent ID

			for ($attempt = 0; $attempt < 100; $attempt++) {
				switch ($attempt) {
					case 0:
						$inslug = as_post_text('slug');
						if (!isset($inslug)) $inslug = implode('-', as_string_to_words($inname));
						break;

					case 1:
						$inslug = as_lang_sub('main/department_default_slug', $inslug);
						break;

					default:
						$inslug = as_lang_sub('main/department_default_slug', $attempt - 1);
						break;
				}

				$matchdepartmentid = as_db_department_slug_to_id($inparentid, $inslug); // query against DB since MySQL ignores accents, etc...

				if (!isset($inparentid)) $matchpage = as_db_single_select(as_db_page_full_selectspec($inslug, false));
				else $matchpage = null;

				if (empty($inslug)) $errors['slug'] = as_lang('main/field_required');
				elseif (as_strlen($inslug) > AS_DB_MAX_CAT_PAGE_TAGS_LENGTH) $errors['slug'] = as_lang_sub('main/max_length_x', AS_DB_MAX_CAT_PAGE_TAGS_LENGTH);
				elseif (preg_match('/[\\+\\/]/', $inslug)) $errors['slug'] = as_lang_sub('admin/slug_bad_chars', '+ /');
				elseif (!isset($inparentid) && as_admin_is_slug_reserved($inslug)) $errors['slug'] = as_lang('admin/slug_reserved');
				elseif (isset($matchdepartmentid) && strcmp($matchdepartmentid, @$editdepartment['departmentid'])) 
					$errors['slug'] = as_lang('main/department_already_used');
				elseif (isset($matchpage)) $errors['slug'] = as_lang('admin/page_already_used');
				else unset($errors['slug']);

				if (isset($editdepartment['departmentid']) || !isset($errors['slug'])) break;
			}
			
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
				
				if (isset($editdepartment['departmentid'])) { // changing existing department
					as_db_department_rename($editdepartment['departmentid'], $inname, $inslug);
					
					$recalc = false;

					if ($setparent) {
						as_db_department_set_parent($editdepartment['departmentid'], $inparentid);
						$recalc = true;
					} else {
						as_db_department_set_content($editdepartment['departmentid'], $incontent, $posticon);
						as_db_department_set_position($editdepartment['departmentid'], $inposition);
						$recalc = $hassubdepartment && $inslug !== $editdepartment['tags'];
					}
					
					as_redirect(as_request(), array('edit' => $editdepartment['departmentid'], 'saved' => true, 'recalc' => (int)$recalc));

				} else { // creating a new one
					$departmentid = as_db_department_create($inparentid, $inname, $inslug);
					
					as_db_department_set_content($departmentid, $incontent, $posticon);

					if (isset($inposition)) as_db_department_set_position($departmentid, $inposition);

					as_redirect(as_request(), array('edit' => $inparentid, 'added' => true));
				}
			}
		}
	}

	$departments = as_db_select_with_pending(as_db_department_nav_selectspec($editdepartmentid, true, false, true));
	
	// Check admin privileges (do late to allow one DB query)
	//if (!as_admin_check_privileges($as_content)) return $as_content;
		
	// Work out the appropriate state for the page
	
	$editdepartment = @$departments[$editdepartmentid];
	
	if (isset($editdepartment)) {
		$parentid = as_get('addsub');
		if (isset($parentid))
			$editdepartment = array('parentid' => $parentid);
	
	} else {
		if (as_clicked('doadddepartment'))
			$editdepartment = array();
	
		elseif (as_clicked('dosavedepartment')) {
			$parentid = as_post_text('parent');
			$editdepartment = array('parentid' => strlen($parentid) ? $parentid : null);
		}
	}
	
	$setmissing = as_post_text('missing') || as_get('missing');
	
	$setparent = !$setmissing && (as_post_text('setparent') || as_get('setparent')) && isset($editdepartment['departid']);
	
	$hassubdepartment = false;
	foreach ($departments as $department) {
		if (!strcmp($department['parentid'], $editdepartmentid))
			$hassubdepartment = true;
	}
	
	$savedoptions = false;
	$securityexpired = false;

	switch (as_request_part(2)) 
	{
		case 'edit':

			break;

		default:
			{
				if ($setmissing) {
					$formcontent = array(
						'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
				
						'style' => 'tall',
						'title' => 'Add a Department to this Business',
				
						'fields' => array(
							'reassign' => array(
								'label' => isset($editdepartment)
									? as_lang_html_sub('main/department_no_sub_to', as_html($editdepartment['title']))
									: as_lang_html('main/department_none_to'),
								'loose' => true,
							),
						),
				
						'buttons' => array(
							'save' => array(
								'tags' => 'id="dosaveoptions"', // just used for as_recalc_click()
								'label' => as_lang_html('main/save_button'),
							),
				
							'cancel' => array(
								'tags' => 'name="docancel"',
								'label' => as_lang_html('main/cancel_button'),
							),
						),
				
						'hidden' => array(
							'dosetmissing' => '1', // for IE
							'edit' => @$editdepartment['departid'],
							'missing' => '1',
							'code' => as_get_form_security_code('main/departments'),
						),
					);
				
					as_set_up_department_field($as_content, $formcontent['fields']['reassign'], 'reassign',
						$departments, @$editdepartment['departid'], as_opt('allow_no_department'), as_opt('allow_no_sub_department'));
				
				
				} elseif (isset($editdepartment)) {
					$iconoptions[''] = as_lang_html('admin/icon_none');
					if ( isset($editdepartment['icon']) && strlen($editdepartment['icon'])){
						$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' .as_get_media_html($editdepartment['icon'], 35, 35) .
							'</span> <input name="file" type="file">';
						$iconvalue = $iconoptions['uploaded'];
					} else {
						$iconoptions['uploaded'] = '<input name="file" type="file">';
						$iconvalue = $iconoptions[''];
					}
					
					$formtitle = (isset($editdepartment['departid']) ? 'Edit department: '.$editdepartment['title'] : 'Add a Department to this Business' );
					
					$formcontent = array(
						'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
						'type' => 'form',
						'style' => 'tall',
						'title' => $formtitle,
				
						'style' => 'tall',
				
						'ok' => as_get('saved') ? as_lang_html('main/department_saved') : (as_get('added') ? as_lang_html('main/department_added') : null),
				
						'fields' => array(
							'name' => array(
								'id' => 'name_display',
								'tags' => 'name="name" id="name"',
								'label' => as_lang_html(count($departments) ? 'main/department_name' : 'main/department_name_first'),
								'value' => as_html(isset($inname) ? $inname : @$editdepartment['title']),
								'error' => as_html(@$errors['name']),
							),
				
							'items' => array(),
				
							'delete' => array(),
				
							'reassign' => array(),
				
							'slug' => array(
								'id' => 'slug_display',
								'tags' => 'name="slug"',
								'label' => as_lang_html('main/department_slug'),
								'value' => as_html(isset($inslug) ? $inslug : @$editdepartment['tags']),
								'error' => as_html(@$errors['slug']),
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
								'label' => as_lang_html(isset($editdepartment['departid']) ? 'main/save_button' : 'admin/add_department_button'),
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
							'code' => as_get_form_security_code('main/departments'),
						),
					);
				
				
					if ($setparent) {
						unset($formcontent['fields']['delete']);
						unset($formcontent['fields']['reassign']);
						unset($formcontent['fields']['items']);
						unset($formcontent['fields']['content']);
				
						$formcontent['fields']['parent'] = array(
							'label' => as_lang_html('main/department_parent'),
						);
				
						$childdepth = as_db_department_child_depth($editdepartment['departid']);
				
						as_set_up_department_field($as_content, $formcontent['fields']['parent'], 'parent',
							isset($indepartments) ? $indepartments : $departments, isset($inparentid) ? $inparentid : @$editdepartment['parentid'],
							true, true, AS_department_DEPTH - 1 - $childdepth, @$editdepartment['departid']);
				
						$formcontent['fields']['parent']['options'][''] = as_lang_html('main/department_top_level');
				
						@$formcontent['fields']['parent']['note'] .= as_lang_html_sub('main/department_max_depth_x', AS_department_DEPTH);
				
					} elseif (isset($editdepartment['departid'])) { // existing department
						if ($hassubdepartment) {
							$formcontent['fields']['name']['note'] = as_lang_html('main/department_no_delete_subs');
							unset($formcontent['fields']['delete']);
							unset($formcontent['fields']['reassign']);
				
						} else {
							$formcontent['fields']['delete'] = array(
								'tags' => 'name="dodelete" id="dodelete"',
								'label' =>
									'<span id="reassign_shown">' . as_lang_html('admin/delete_department_reassign') . '</span>' .
									'<span id="reassign_hidden" style="display:none;">' . as_lang_html('admin/delete_department') . '</span>',
								'value' => 0,
								'type' => 'checkbox',
							);
				
							$formcontent['fields']['reassign'] = array(
								'id' => 'reassign_display',
								'tags' => 'name="reassign"',
							);
				
							as_set_up_department_field($as_content, $formcontent['fields']['reassign'], 'reassign',
								$departments, $editdepartment['parentid'], true, true, null, $editdepartment['departid']);
						}
				
						$formcontent['fields']['items'] = array(
							'label' => as_lang_html('admin/total_qs'),
							'type' => 'static',
							'value' => '<a href="' . as_path_html('items/' . as_department_path_request($departments, $editdepartment['departid'])) . '">' .
								($editdepartment['pcount'] == 1
									? as_lang_html_sub('main/1_article', '1', '1')
									: as_lang_html_sub('main/x_articles', as_format_number($editdepartment['pcount']))
								) . '</a>',
						);
				
						if ($hassubdepartment && !as_opt('allow_no_sub_department')) {
							$nosubcount = as_db_count_departmentid_qs($editdepartment['departid']);
				
							if ($nosubcount) {
								$formcontent['fields']['items']['error'] =
									strtr(as_lang_html('main/department_no_sub_error'), array(
										'^q' => as_format_number($nosubcount),
										'^1' => '<a href="' . as_path_html(as_request(), array('edit' => $editdepartment['departid'], 'missing' => 1)) . '">',
										'^2' => '</a>',
									));
							}
						}
				
						as_set_display_rules($as_content, array(
							'position_display' => '!dodelete',
							'slug_display' => '!dodelete',
							'content_display' => '!dodelete',
							'parent_display' => '!dodelete',
							'children_display' => '!dodelete',
							'reassign_display' => 'dodelete',
							'reassign_shown' => 'dodelete',
							'reassign_hidden' => '!dodelete',
						));
				
					} else { // new department
						unset($formcontent['fields']['delete']);
						unset($formcontent['fields']['reassign']);
						unset($formcontent['fields']['slug']);
						unset($formcontent['fields']['items']);
				
						$as_content['focusid'] = 'name';
					}
				
					if (!$setparent) {
						$pathhtml = as_department_path_html($departments, @$editdepartment['parentid']);
				
						if (count($departments)) {
							$formcontent['fields']['parent'] = array(
								'id' => 'parent_display',
								'label' => as_lang_html('main/department_parent'),
								'type' => 'static',
								'value' => (strlen($pathhtml) ? $pathhtml : as_lang_html('main/department_top_level')),
							);
				
							$formcontent['fields']['parent']['value'] =
								'<a href="' . as_path_html(as_request(), array('edit' => @$editdepartment['parentid'])) . '">' .
								$formcontent['fields']['parent']['value'] . '</a>';
				
							if (isset($editdepartment['departid'])) {
								$formcontent['fields']['parent']['value'] .= ' - ' .
									'<a href="' . as_path_html(as_request(), array('edit' => $editdepartment['departid'], 'setparent' => 1)) .
									'" style="white-space: nowrap;">' . as_lang_html('main/department_move_parent') . '</a>';
							}
						}
				
						$positionoptions = array();
				
						$previous = null;
						$passedself = false;
				
						foreach ($departments as $key => $department) {
							if (!strcmp($department['parentid'], @$editdepartment['parentid'])) {
								if (isset($previous))
									$positionhtml = as_lang_html_sub('admin/after_x', as_html($passedself ? $department['title'] : $previous['title']));
								else
									$positionhtml = as_lang_html('admin/first');
				
								$positionoptions[$department['position']] = $positionhtml;
				
								if (!strcmp($department['departid'], @$editdepartment['departid']))
									$passedself = true;
				
								$previous = $department;
							}
						}
				
						if (isset($editdepartment['position']))
							$positionvalue = $positionoptions[$editdepartment['position']];
				
						else {
							$positionvalue = isset($previous) ? as_lang_html_sub('admin/after_x', as_html($previous['title'])) : as_lang_html('admin/first');
							$positionoptions[1 + @max(array_keys($positionoptions))] = $positionvalue;
						}
				
						$formcontent['fields']['position'] = array(
							'id' => 'position_display',
							'tags' => 'name="position"',
							'label' => as_lang_html('admin/position'),
							'type' => 'select',
							'options' => $positionoptions,
							'value' => $positionvalue,
						);
				
						if (isset($editdepartment['departid'])) {
							$catdepth = count(as_department_path($departments, $editdepartment['departid']));
				
							if ($catdepth < AS_department_DEPTH) {
								$childrenhtml = '';
				
								foreach ($departments as $department) {
									if (!strcmp($department['parentid'], $editdepartment['departid'])) {
										$childrenhtml .= (strlen($childrenhtml) ? ', ' : '') .
											'<a href="' . as_path_html(as_request(), array('edit' => $department['departid'])) . '">' . as_html($department['title']) . '</a>' .
											' (' . $department['pcount'] . ')';
									}
								}
				
								if (!strlen($childrenhtml))
									$childrenhtml = as_lang_html('main/department_no_subs');
				
								$childrenhtml .= ' - <a href="' . as_path_html(as_request(), array('addsub' => $editdepartment['departid'])) .
									'" style="white-space: nowrap;"><b>' . as_lang_html('main/department_add_sub') . '</b></a>';
				
								$formcontent['fields']['children'] = array(
									'id' => 'children_display',
									'label' => as_lang_html('main/department_subs'),
									'type' => 'static',
									'value' => $childrenhtml,
								);
							} else {
								$formcontent['fields']['name']['note'] = as_lang_html_sub('main/department_no_add_subs_x', AS_department_DEPTH);
							}
				
						}
					}
				
				} else {
					$formcontent = array(
						'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
						'title' => 'This Business has ' . count($departments) .' Departments, You may add more',
						'type' => 'form',
						'style' => 'tall',
						'ok' => $savedoptions ? as_lang_html('admin/options_saved') : null,
				
						'style' => 'tall',
				
						/*'fields' => array(
							'intro' => array(
								'label' => 'This Business has ' . count($departments) .' Departments, You may add more',
								'type' => 'static',
							),
						),*/
						
						'table' => array( 'id' => 'alldepartments', 'headers' => array('', 'Title', 'Items') ),
				
						'tools' => array(
							'add' => array(
								'type' => 'submit', 
								'tags' => 'name="doadddepartment"',
								'label' => as_lang_html('main/add_department_button'),
							),
						),
						
						'hidden' => array(
							'code' => as_get_form_security_code('main/departments'),
						),
					);
					
					/*$dashlist = array( 'type' => 'dashlist', 'theme' => 'primary', 'title' => 'This Business has ' . $bzcount .' Departments, You may add more', 
						'tools' => array(
							'add' => array( 'type' => 'link', 'label' => as_lang_html('main/add_department_button'),
							'url' => $request.'/newdept', 'class' => 'btn btn-primary btn-block' )
						),
					);*/
				
					if (count($departments)) {
						unset($formcontent['fields']['intro']);
				
						$navdepartmenthtml = '';
						$k = 1;
						foreach ($departments as $department) {
							if (!isset($department['parentid'])) {
								$count = $department['pcount'] == 1 ? as_lang_html_sub('main/1_article', '1', '1') : as_lang_html_sub('main/x_articles', as_format_number($department['pcount']));
								$formcontent['table']['rows'][] = array(
									'onclick' => ' title="Click on this item to edit or view"',
									'fields' => array(
										'id' => array( 'data' => $k),
										'title' => array( 'data' => as_get_media_html($department['icon'], 20, 20) .'<a href="' . as_path_html('main/departments', array('edit' => $department['departid'])) . '">' . as_html($department['title']) .'</a>' ),
										'count' => array( 'data' => ($count)),
									),
								);
							}
							$k++;
						}
				
					} else
						unset($formcontent['buttons']['save']);
				}
				
				if (as_get('recalc')) {
					$formcontent['ok'] = '<span id="recalc_ok">' . as_lang_html('admin/recalc_departments') . '</span>';
					$formcontent['hidden']['code_recalc'] = as_get_form_security_code('admin/recalc');
				
					$as_content['script_rel'][] = 'as-content/as-admin.js?' . AS_VERSION;
					$as_content['script_var']['as_warning_recalc'] = as_lang('admin/stop_recalc_warning');
				
					$as_content['script_onloads'][] = array(
						"as_recalc_click('dorecalcdepartments', document.getElementById('dosaveoptions'), null, 'recalc_ok');"
					);
				}
				
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
				
				/*'tools' => array(
					'add' => array(
						'type' => 'submit', 
						'tags' => 'name="doadddepartment"',
						'label' => as_lang_html('admin/add_department_button'),
					),
				),*/
				
				/*if ($bzcount){
					foreach ($businesses as $business => $biz){
						$dashlist['items'][$biz['departid']] = array('img' => $defaulticon, 'label' => $biz['title'], 'numbers' => '1 User', 
						'description' => $biz['content'], 'link' => 'business/'.$biz['departid'],
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
						2 => array('class' => 'col-lg-8 col-xs-6', 'c_items' => array($formcontent) ),
					),
				);
				
			}
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
			
			$dashlist = array( 'type' => 'dashlist', 'theme' => 'primary', 'title' => 'You have ' . $bzcount .' Businesses, You may add more', 
				'tools' => array(
					'add' => array( 'type' => 'link', 'label' => 'NEW BUSINESS',
					'url' => $request.'business/new', 'class' => 'btn btn-primary btn-block' )
				),
			);
				
			if ($bzcount){
				
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