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
	
	$editcategoryid = as_post_text('edit');
	if (!isset($editcategoryid))
		$editcategoryid = as_get('edit');
	if (!isset($editcategoryid))
		$editcategoryid = as_get('addsub');
	
	$categories = as_db_select_with_pending(as_db_category_nav_selectspec($editcategoryid, true, false, true));
	
	// Check admin privileges (do late to allow one DB query)
	//if (!as_admin_check_privileges($as_content)) return $as_content;
		
	// Work out the appropriate state for the page
	
	$editcategory = @$categories[$editcategoryid];
	
	if (isset($editcategory)) {
		$parentid = as_get('addsub');
		if (isset($parentid))
			$editcategory = array('parentid' => $parentid);
	
	} else {
		if (as_clicked('doaddcategory'))
			$editcategory = array();
	
		elseif (as_clicked('dosavecategory')) {
			$parentid = as_post_text('parent');
			$editcategory = array('parentid' => strlen($parentid) ? $parentid : null);
		}
	}
	
	$setmissing = as_post_text('missing') || as_get('missing');
	
	$setparent = !$setmissing && (as_post_text('setparent') || as_get('setparent')) && isset($editcategory['categoryid']);
	
	$hassubcategory = false;
	foreach ($categories as $category) {
		if (!strcmp($category['parentid'], $editcategoryid))
			$hassubcategory = true;
	}
	
	$savedoptions = false;
	$securityexpired = false;

	switch (as_request_part(2)) 
	{
		case 'edit':

			break;
		
		case 'newdept':
		case 'editdept':
			if ($setmissing) {
				$formcontent = array(
					'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
			
					'style' => 'tall',
					'title' => 'Add a Category',
			
					'fields' => array(
						'reassign' => array(
							'label' => isset($editcategory)
								? as_lang_html_sub('admin/category_no_sub_to', as_html($editcategory['title']))
								: as_lang_html('admin/category_none_to'),
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
						'edit' => @$editcategory['categoryid'],
						'missing' => '1',
						'code' => as_get_form_security_code('admin/categories'),
					),
				);
			
				as_set_up_category_field($as_content, $formcontent['fields']['reassign'], 'reassign',
					$categories, @$editcategory['categoryid'], as_opt('allow_no_category'), as_opt('allow_no_sub_category'));
			
			
			} elseif (isset($editcategory)) {
				$iconoptions[''] = as_lang_html('admin/icon_none');
				if ( isset($editcategory['icon']) && strlen($editcategory['icon'])){
					$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' .as_get_media_html($editcategory['icon'], 35, 35) .
						'</span> <input name="file" type="file">';
					$iconvalue = $iconoptions['uploaded'];
				} else {
					$iconoptions['uploaded'] = '<input name="file" type="file">';
					$iconvalue = $iconoptions[''];
				}
				
				$formtitle = (isset($editcategory['categoryid']) ? 'Edit Category: '.$editcategory['title'] : 'Add a Category' );
				
				$formcontent = array(
					'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
					'type' => 'form',
					'style' => 'tall',
					'title' => $formtitle,
			
					'style' => 'tall',
			
					'ok' => as_get('saved') ? as_lang_html('admin/category_saved') : (as_get('added') ? as_lang_html('admin/category_added') : null),
			
					'fields' => array(
						'name' => array(
							'id' => 'name_display',
							'tags' => 'name="name" id="name"',
							'label' => as_lang_html(count($categories) ? 'admin/category_name' : 'admin/category_name_first'),
							'value' => as_html(isset($inname) ? $inname : @$editcategory['title']),
							'error' => as_html(@$errors['name']),
						),
			
						'items' => array(),
			
						'delete' => array(),
			
						'reassign' => array(),
			
						'slug' => array(
							'id' => 'slug_display',
							'tags' => 'name="slug"',
							'label' => as_lang_html('admin/category_slug'),
							'value' => as_html(isset($inslug) ? $inslug : @$editcategory['tags']),
							'error' => as_html(@$errors['slug']),
						),
						
						'posticon' => array(
							'type' => 'select-radio',
							'label' => as_lang_html('admin/category_icon'),
							'tags' => 'name="posticon"',
							'options' => $iconoptions,
							'value' => $iconvalue,
							'error' => as_html(@$errors['posticon']),
						),
						
						'content' => array(
							'id' => 'content_display',
							'tags' => 'name="content"',
							'label' => as_lang_html('admin/category_description'),
							'value' => as_html(isset($incontent) ? $incontent : @$editcategory['content']),
							'error' => as_html(@$errors['content']),
							'rows' => 2,
						),
					),
			
					'buttons' => array(
						'save' => array(
							'tags' => 'id="dosaveoptions"', // just used for as_recalc_click
							'label' => as_lang_html(isset($editcategory['categoryid']) ? 'main/save_button' : 'admin/add_category_button'),
						),
			
						'cancel' => array(
							'tags' => 'name="docancel"',
							'label' => as_lang_html('main/cancel_button'),
						),
					),
			
					'hidden' => array(
						'dosavecategory' => '1', // for IE
						'edit' => @$editcategory['categoryid'],
						'parent' => @$editcategory['parentid'],
						'setparent' => (int)$setparent,
						'code' => as_get_form_security_code('admin/categories'),
					),
				);
			
			
				if ($setparent) {
					unset($formcontent['fields']['delete']);
					unset($formcontent['fields']['reassign']);
					unset($formcontent['fields']['items']);
					unset($formcontent['fields']['content']);
			
					$formcontent['fields']['parent'] = array(
						'label' => as_lang_html('admin/category_parent'),
					);
			
					$childdepth = as_db_category_child_depth($editcategory['categoryid']);
			
					as_set_up_category_field($as_content, $formcontent['fields']['parent'], 'parent',
						isset($incategories) ? $incategories : $categories, isset($inparentid) ? $inparentid : @$editcategory['parentid'],
						true, true, AS_CATEGORY_DEPTH - 1 - $childdepth, @$editcategory['categoryid']);
			
					$formcontent['fields']['parent']['options'][''] = as_lang_html('admin/category_top_level');
			
					@$formcontent['fields']['parent']['note'] .= as_lang_html_sub('admin/category_max_depth_x', AS_CATEGORY_DEPTH);
			
				} elseif (isset($editcategory['categoryid'])) { // existing category
					if ($hassubcategory) {
						$formcontent['fields']['name']['note'] = as_lang_html('admin/category_no_delete_subs');
						unset($formcontent['fields']['delete']);
						unset($formcontent['fields']['reassign']);
			
					} else {
						$formcontent['fields']['delete'] = array(
							'tags' => 'name="dodelete" id="dodelete"',
							'label' =>
								'<span id="reassign_shown">' . as_lang_html('admin/delete_category_reassign') . '</span>' .
								'<span id="reassign_hidden" style="display:none;">' . as_lang_html('admin/delete_category') . '</span>',
							'value' => 0,
							'type' => 'checkbox',
						);
			
						$formcontent['fields']['reassign'] = array(
							'id' => 'reassign_display',
							'tags' => 'name="reassign"',
						);
			
						as_set_up_category_field($as_content, $formcontent['fields']['reassign'], 'reassign',
							$categories, $editcategory['parentid'], true, true, null, $editcategory['categoryid']);
					}
			
					$formcontent['fields']['items'] = array(
						'label' => as_lang_html('admin/total_qs'),
						'type' => 'static',
						'value' => '<a href="' . as_path_html('items/' . as_category_path_request($categories, $editcategory['categoryid'])) . '">' .
							($editcategory['pcount'] == 1
								? as_lang_html_sub('main/1_article', '1', '1')
								: as_lang_html_sub('main/x_articles', as_format_number($editcategory['pcount']))
							) . '</a>',
					);
			
					if ($hassubcategory && !as_opt('allow_no_sub_category')) {
						$nosubcount = as_db_count_categoryid_qs($editcategory['categoryid']);
			
						if ($nosubcount) {
							$formcontent['fields']['items']['error'] =
								strtr(as_lang_html('admin/category_no_sub_error'), array(
									'^q' => as_format_number($nosubcount),
									'^1' => '<a href="' . as_path_html(as_request(), array('edit' => $editcategory['categoryid'], 'missing' => 1)) . '">',
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
			
				} else { // new category
					unset($formcontent['fields']['delete']);
					unset($formcontent['fields']['reassign']);
					unset($formcontent['fields']['slug']);
					unset($formcontent['fields']['items']);
			
					$as_content['focusid'] = 'name';
				}
			
				if (!$setparent) {
					$pathhtml = as_category_path_html($categories, @$editcategory['parentid']);
			
					if (count($categories)) {
						$formcontent['fields']['parent'] = array(
							'id' => 'parent_display',
							'label' => as_lang_html('admin/category_parent'),
							'type' => 'static',
							'value' => (strlen($pathhtml) ? $pathhtml : as_lang_html('admin/category_top_level')),
						);
			
						$formcontent['fields']['parent']['value'] =
							'<a href="' . as_path_html(as_request(), array('edit' => @$editcategory['parentid'])) . '">' .
							$formcontent['fields']['parent']['value'] . '</a>';
			
						if (isset($editcategory['categoryid'])) {
							$formcontent['fields']['parent']['value'] .= ' - ' .
								'<a href="' . as_path_html(as_request(), array('edit' => $editcategory['categoryid'], 'setparent' => 1)) .
								'" style="white-space: nowrap;">' . as_lang_html('admin/category_move_parent') . '</a>';
						}
					}
			
					$positionoptions = array();
			
					$previous = null;
					$passedself = false;
			
					foreach ($categories as $key => $category) {
						if (!strcmp($category['parentid'], @$editcategory['parentid'])) {
							if (isset($previous))
								$positionhtml = as_lang_html_sub('admin/after_x', as_html($passedself ? $category['title'] : $previous['title']));
							else
								$positionhtml = as_lang_html('admin/first');
			
							$positionoptions[$category['position']] = $positionhtml;
			
							if (!strcmp($category['categoryid'], @$editcategory['categoryid']))
								$passedself = true;
			
							$previous = $category;
						}
					}
			
					if (isset($editcategory['position']))
						$positionvalue = $positionoptions[$editcategory['position']];
			
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
			
					if (isset($editcategory['categoryid'])) {
						$catdepth = count(as_category_path($categories, $editcategory['categoryid']));
			
						if ($catdepth < AS_CATEGORY_DEPTH) {
							$childrenhtml = '';
			
							foreach ($categories as $category) {
								if (!strcmp($category['parentid'], $editcategory['categoryid'])) {
									$childrenhtml .= (strlen($childrenhtml) ? ', ' : '') .
										'<a href="' . as_path_html(as_request(), array('edit' => $category['categoryid'])) . '">' . as_html($category['title']) . '</a>' .
										' (' . $category['pcount'] . ')';
								}
							}
			
							if (!strlen($childrenhtml))
								$childrenhtml = as_lang_html('admin/category_no_subs');
			
							$childrenhtml .= ' - <a href="' . as_path_html(as_request(), array('addsub' => $editcategory['categoryid'])) .
								'" style="white-space: nowrap;"><b>' . as_lang_html('admin/category_add_sub') . '</b></a>';
			
							$formcontent['fields']['children'] = array(
								'id' => 'children_display',
								'label' => as_lang_html('admin/category_subs'),
								'type' => 'static',
								'value' => $childrenhtml,
							);
						} else {
							$formcontent['fields']['name']['note'] = as_lang_html_sub('admin/category_no_add_subs_x', AS_CATEGORY_DEPTH);
						}
			
					}
				}
			
			} else {
				$formcontent = array(
					'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
					'title' => 'Recent Categories',
					'type' => 'form',
					'style' => 'tall',
					'ok' => $savedoptions ? as_lang_html('admin/options_saved') : null,
			
					'style' => 'tall',
			
					'fields' => array(
						'intro' => array(
							'label' => as_lang_html('admin/categories_introduction'),
							'type' => 'static',
						),
					),
					
					'table' => array( 'id' => 'allcategories', 'headers' => array('', 'Title', 'Items') ),
			
					'tools' => array(
						'add' => array(
							'type' => 'submit', 
							'tags' => 'name="doaddcategory"',
							'label' => as_lang_html('admin/add_category_button'),
						),
					),
					
					'hidden' => array(
						'code' => as_get_form_security_code('admin/categories'),
					),
				);
			
				if (count($categories)) {
					unset($formcontent['fields']['intro']);
			
					$navcategoryhtml = '';
					$k = 1;
					foreach ($categories as $category) {
						if (!isset($category['parentid'])) {
							$count = $category['pcount'] == 1 ? as_lang_html_sub('main/1_article', '1', '1') : as_lang_html_sub('main/x_articles', as_format_number($category['pcount']));
							$formcontent['table']['rows'][] = array(
								'onclick' => ' title="Click on this item to edit or view"',
								'fields' => array(
									'id' => array( 'data' => $k),
									'title' => array( 'data' => as_get_media_html($category['icon'], 20, 20) .'<a href="' . as_path_html('admin/categories', array('edit' => $category['categoryid'])) . '">' . as_html($category['title']) .'</a>' ),
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
				$formcontent['ok'] = '<span id="recalc_ok">' . as_lang_html('admin/recalc_categories') . '</span>';
				$formcontent['hidden']['code_recalc'] = as_get_form_security_code('admin/recalc');
			
				$as_content['script_rel'][] = 'as-content/as-admin.js?' . AS_VERSION;
				$as_content['script_var']['as_warning_recalc'] = as_lang('admin/stop_recalc_warning');
			
				$as_content['script_onloads'][] = array(
					"as_recalc_click('dorecalccategories', document.getElementById('dosaveoptions'), null, 'recalc_ok');"
				);
			}
			$as_content['row_view'][] = array(
				'colms' => array(
					0 => array('class' => 'col-md-4', 'c_items' => array($profile1, $profile2) ),
					2 => array('class' => 'col-lg-8 col-xs-6', 'c_items' => array($formcontent) ),
				),
			);
			break;

		default:			
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