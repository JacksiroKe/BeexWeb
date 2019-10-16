<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for user profile page, including wall


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

require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/limits.php';
require_once AS_INCLUDE_DIR . 'app/updates.php';

// Get the HTML to display for the handle, and if we're using external users, determine the userid

if (AS_FINAL_EXTERNAL_USERS) {
	$userid = as_handle_to_userid($handle);
	if (!isset($userid))
		return include AS_INCLUDE_DIR . 'as-page-not-found.php';

	$usershtml = as_get_users_html(array($userid), false, as_path_to_root(), true);
	$userhtml = @$usershtml[$userid];

} else
	$userhtml = as_html($handle);

$start = as_get_start();
$state = as_get_state();
// Find the user profile and articles and answers for this handle


$loginuserid = as_get_logged_in_userid();
$identifier = AS_FINAL_EXTERNAL_USERS ? $userid : $handle;

list($useraccount, $userprofile, $userfields, $usermessages, $userpoints, $userlevels, $navcategories, $userrank, $articles) =
	as_db_select_with_pending(
		AS_FINAL_EXTERNAL_USERS ? null : as_db_user_account_selectspec($handle, false),
		AS_FINAL_EXTERNAL_USERS ? null : as_db_user_profile_selectspec($handle, false),
		AS_FINAL_EXTERNAL_USERS ? null : as_db_userfields_selectspec(),
		AS_FINAL_EXTERNAL_USERS ? null : as_db_recent_messages_selectspec(null, null, $handle, false, as_opt_if_loaded('page_size_wall')),
		as_db_user_points_selectspec($identifier),
		as_db_user_levels_selectspec($identifier, AS_FINAL_EXTERNAL_USERS, true),
		as_db_category_nav_selectspec(null, true),
		as_db_user_rank_selectspec($identifier),
		as_db_user_recent_qs_selectspec($loginuserid, $identifier, as_opt_if_loaded('page_size_qs'), $start)
	);

if (!AS_FINAL_EXTERNAL_USERS && $handle !== as_get_logged_in_handle()) {
	foreach ($userfields as $index => $userfield) {
		if (isset($userfield['permit']) && as_permit_value_error($userfield['permit'], $loginuserid, as_get_logged_in_level(), as_get_logged_in_flags()))
			unset($userfields[$index]); // don't pay attention to user fields we're not allowed to view
	}
}

$as_content = as_content_prepare();
$fullname = as_db_name_find_by_handle($handle);

$as_content['title'] = $fullname;

$handle = as_request_part(1);
$username = as_get_logged_in_handle();

$gender = $useraccount['gender'] == 1 ? ' ('.as_lang('users/gender_male').')' : ' ('.as_lang('users/gender_female').')';
$usertime = as_time_to_string(as_opt('db_time') - $useraccount['created']);
$joindate = as_when_to_html($useraccount['created'], 0);

$genderoptions = array("1" => "Male", "2" => "Female");
$gendervaue = $useraccount['gender'] == "1" ? $genderoptions["1"] : $genderoptions["2"];

$loginlevel = as_get_logged_in_level();
$maxlevelassign = null;

$userid = $useraccount['userid'];
$maxuserlevel = $useraccount['level'];
foreach ($userlevels as $userlevel)
	$maxuserlevel = max($maxuserlevel, $userlevel['level']);

if (isset($loginuserid) && $loginuserid != $userid &&
	($loginlevel >= AS_USER_LEVEL_SUPER || $loginlevel > $maxuserlevel) &&
	!as_user_permit_error()
) { // can't change self - or someone on your level (or higher, obviously) unless you're a super admin

	if ($loginlevel >= AS_USER_LEVEL_SUPER)
		$maxlevelassign = AS_USER_LEVEL_SUPER;
	elseif ($loginlevel >= AS_USER_LEVEL_ADMIN)
		$maxlevelassign = AS_USER_LEVEL_MODERATOR;
	elseif ($loginlevel >= AS_USER_LEVEL_MODERATOR)
		$maxlevelassign = AS_USER_LEVEL_WRITER;

	if ($loginlevel >= AS_USER_LEVEL_ADMIN)
		$fieldseditable = true;

	if (isset($maxlevelassign) && ($useraccount['flags'] & AS_USER_FLAGS_USER_BLOCKED))
		$maxlevelassign = min($maxlevelassign, AS_USER_LEVEL_EDITOR); // if blocked, can't promote too high
}

if (as_clicked('dosave')) {
	require_once AS_INCLUDE_DIR . 'app/users-edit.php';
	require_once AS_INCLUDE_DIR . 'db/users.php';
	$userid = as_handle_to_userid($handle);
	
	$infirstname = as_post_text('firstname');
	$inlastname = as_post_text('lastname');
	$ingender = as_post_text('gender');
	$inmobile = as_post_text('mobile');
	$inemail = as_post_text('email');
	$inlevel = as_post_text('level');
	
	if (!as_check_form_security_code('user-edit-' . $handle, as_post_text('code'))) {
		$errors['page'] = as_lang_html('misc/form_security_again');
	} else {
		$filterhandle = $handle; // we're not filtering the handle...
		$errors = as_handle_email_filter($filterhandle, $inemail, $useraccount);
		unset($errors['handle']); // ...and we don't care about any errors in it

		as_db_user_set($userid, 'firstname', $infirstname);
		as_db_user_set($userid, 'lastname', $inlastname);
		as_db_user_set($userid, 'gender', $ingender);
		as_db_user_set($userid, 'mobile', $inmobile);
		
		
		if (!isset($errors['email'])) {
			if ($inemail != $useraccount['email']) {
				as_db_user_set($userid, 'email', $inemail);
				as_db_user_set_flag($userid, AS_USER_FLAGS_EMAIL_CONFIRMED, false);
			}
		}

		as_report_event('u_edit', $loginuserid, as_get_logged_in_handle(), as_cookie_get(), array(
			'userid' => $userid,
			'handle' => $useraccount['handle'],
		));
		
		if (isset($maxlevelassign)) {
			$inlevel = min($maxlevelassign, (int)as_post_text('level'));
			if ($inlevel != $useraccount['level']) {
				as_set_user_level($userid, $fullname, $useraccount['handle'], $useraccount['email'], $inlevel, $useraccount['level'], $useraccount['profiles']);
			}
		}

		if (empty($errors)) as_redirect('user/'.$handle);
	}
}

elseif (as_clicked('docancel')) {
	as_redirect('user/'.$handle);
}
         
$profile1 = array( 'type' => 'box', 'theme' => 'primary', 
	'body' => array(
		'type' => 'box-body box-profile',
		'items' => array(
			0 => array( 
				'tag' => array('avatar'),
				'img' => as_avatar(100, 'profile-user-img img-responsive', $useraccount),
			),
			
			1 => array( 
				'tag' => array('h3', 'profile-username text-center'),
				'data' => array( 'text' => $fullname ),
			),
			
			2 => array( 
				'tag' => array('p', 'text-muted text-center'),
				'data' => array( 'text' => $gender . ' - ' .as_user_type($useraccount['usertype'], true) ),
			),
						
			3 => array( 
				'tag' => array('list', 'list-group list-group-unbordered'),
				'data' => array( 
					'Level' => as_html(as_user_level_string($useraccount['level'])), 
					'Mobile' => $useraccount['mobile'], 
					'Country' => $useraccount['country'],
					as_lang_html('users/user_for') => $usertime . ' (' . as_lang_sub('main/since_x', $joindate['data']) . ')',
				),
			),
			4 => '',			
		),
	),
);

$edituser = array(
	'title' => 'Edit this User',
	'tags' => 'method="post" action="' . as_self_html() . '"',
	'style' => 'tall',
	'type' => 'form',

	'fields' => array(
		'firstname' => array(
			'label' => as_lang_html('users/firstname_label'),
			'tags' => 'name="firstname" id="firstname" dir="auto"',
			'value' => $useraccount['firstname'],
			'error' => as_html(@$errors['firstname']),
		),

		'lastname' => array(
			'label' => as_lang_html('users/lastname_label'),
			'tags' => 'name="lastname" id="lastname" dir="auto"',
			'value' => $useraccount['lastname'],
			'error' => as_html(@$errors['lastname']),
		),

		'gender' => array(
			'type' => 'radio',
			'label' => 'Gender:',
			'tags' => 'name="gender" id="gender" dir="auto"',
			'options' => $genderoptions, 
			'value' => $gendervaue,
			'error' => as_html(@$errors['gender']),
		),
		
		'level' => array(
			'type' => 'static',
			'label' => as_lang_html('users/user_type'),
			'tags' => 'name="level"',
			'value' => as_html(as_user_level_string($useraccount['level'])),
			'note' => (($useraccount['flags'] & AS_USER_FLAGS_USER_BLOCKED) && isset($maxlevelassign)) ? as_lang_html('users/user_blocked') : '',
			'id' => 'level',
		),
		
		'mobile' => array(
			'type' => 'phone',
			'label' => as_lang_html('users/mobile_label'),
			'tags' => 'name="mobile" id="mobile" dir="auto"',
			'value' => $useraccount['mobile'],
			'error' => as_html(@$errors['mobile']),
		),

		'email' => array(
			'type' => 'email',
			'label' => as_lang_html('users/email_label'),
			'tags' => 'name="email" id="email" dir="auto"',
			'value' => $useraccount['email'],
			'note' => as_opt('email_privacy'),
			'error' => as_html(@$errors['email']),
		),
		
	),

	'buttons' => array(
		'save' => array(
			'tags' => 'name="dosave" onclick="as_show_waiting_after(this, false);"',
			'label' => as_lang_html('users/save_user'),
		),

		'cancel' => array(
			'tags' => 'name="docancel"',
			'label' => as_lang_html('main/cancel_button'),
		),
	),

	'hidden' => array(
		'code' => as_get_form_security_code('user-edit-' . $handle),
	),
);

if (isset($maxlevelassign) && $useraccount['level'] < AS_USER_LEVEL_MODERATOR) {
	if ($useraccount['flags'] & AS_USER_FLAGS_USER_BLOCKED) {
		$edituser['buttons']['unblock'] = array(
			'tags' => 'name="dounblock"',
			'label' => as_lang_html('users/unblock_user_button'),
		);

		if (!as_user_permit_error('permit_hide_show')) {
			$edituser['buttons']['hideall'] = array(
				'tags' => 'name="dohideall" onclick="as_show_waiting_after(this, false);"',
				'label' => as_lang_html('users/hide_all_user_button'),
			);
		}

		if ($loginlevel >= AS_USER_LEVEL_ADMIN) {
			$edituser['buttons']['delete'] = array(
				'tags' => 'name="dodelete" onclick="as_show_waiting_after(this, false);"',
				'label' => as_lang_html('users/delete_user_button'),
			);
		}

	} else {
		$edituser['buttons']['block'] = array(
			'tags' => 'name="doblock"',
			'label' => as_lang_html('users/block_user_button'),
		);
	}
}

if (isset($maxlevelassign)) {
	$edituser['fields']['level']['type'] = 'select';

	$showlevels = array(AS_USER_LEVEL_BASIC);
	if (as_opt('moderate_users'))
		$showlevels[] = AS_USER_LEVEL_APPROVED;

	array_push($showlevels, AS_USER_LEVEL_EXPERT, AS_USER_LEVEL_EDITOR, AS_USER_LEVEL_MODERATOR, AS_USER_LEVEL_ADMIN, AS_USER_LEVEL_SUPER);

	$leveloptions = array();
	$catleveloptions = array('' => as_lang_html('users/category_level_none'));

	foreach ($showlevels as $showlevel) {
		if ($showlevel <= $maxlevelassign) {
			$leveloptions[$showlevel] = as_html(as_user_level_string($showlevel));
			if ($showlevel > AS_USER_LEVEL_BASIC)
				$catleveloptions[$showlevel] = $leveloptions[$showlevel];
		}
	}

	$edituser['fields']['level']['options'] = $leveloptions;


	// Category-specific levels
	if (as_using_categories()) {
		$catleveladd = strlen(as_get('catleveladd')) > 0;

		if (!$catleveladd && !count($userlevels)) {
			$edituser['fields']['level']['suffix'] = strtr(as_lang_html('users/category_level_add'), array(
				'^1' => '<a href="' . as_path_html(as_request(), array('state' => 'edit', 'catleveladd' => 1)) . '">',
				'^2' => '</a>',
			));
		} else {
			$edituser['fields']['level']['suffix'] = as_lang_html('users/level_in_general');
		}

		if ($catleveladd || count($userlevels))
			$userlevels[] = array('entitytype' => AS_ENTITY_CATEGORY);

		$index = 0;
		foreach ($userlevels as $userlevel) {
			if ($userlevel['entitytype'] == AS_ENTITY_CATEGORY) {
				$index++;
				$id = 'ls_' . +$index;

				$edituser['fields']['uc_' . $index . '_level'] = array(
					'label' => as_lang_html('users/category_level_label'),
					'type' => 'select',
					'tags' => 'name="uc_' . $index . '_level" id="' . as_html($id) . '" onchange="this.as_prev=this.options[this.selectedIndex].value;"',
					'options' => $catleveloptions,
					'value' => isset($userlevel['level']) ? as_html(as_user_level_string($userlevel['level'])) : '',
					'suffix' => as_lang_html('users/category_level_in'),
				);

				$edituser['fields']['uc_' . $index . '_cat'] = array();

				if (isset($userlevel['entityid']))
					$fieldnavcategories = as_db_select_with_pending(as_db_category_nav_selectspec($userlevel['entityid'], true));
				else
					$fieldnavcategories = $navcategories;

				as_set_up_category_field($as_content, $edituser['fields']['uc_' . $index . '_cat'],
					'uc_' . $index . '_cat', $fieldnavcategories, @$userlevel['entityid'], true, true);

				unset($edituser['fields']['uc_' . $index . '_cat']['note']);
			}
		}

		$as_content['script_lines'][] = array(
			"function as_update_category_levels()",
			"{",
			"\tglob=document.getElementById('level_select');",
			"\tif (!glob)",
			"\t\treturn;",
			"\tvar opts=glob.options;",
			"\tvar lev=parseInt(opts[glob.selectedIndex].value);",
			"\tfor (var i=1; i<9999; i++) {",
			"\t\tvar sel=document.getElementById('ls_'+i);",
			"\t\tif (!sel)",
			"\t\t\tbreak;",
			"\t\tsel.as_prev=sel.as_prev || sel.options[sel.selectedIndex].value;",
			"\t\tsel.options.length=1;", // just leaves "no upgrade" element
			"\t\tfor (var j=0; j<opts.length; j++)",
			"\t\t\tif (parseInt(opts[j].value)>lev)",
			"\t\t\t\tsel.options[sel.options.length]=new Option(opts[j].text, opts[j].value, false, (opts[j].value==sel.as_prev));",
			"\t}",
			"}",
		);

		$as_content['script_onloads'][] = array(
			"as_update_category_levels();",
		);

		$edituser['fields']['level']['tags'] .= ' id="level_select" onchange="as_update_category_levels();"';
	}
}

$as_content['row_view'][] = array(
	'colms' => array(
		0 => array('class' => 'col-md-3', 'c_items' => array($profile1) ),
		1 => array('class' => 'col-md-9', 'c_items' => array($edituser) ),
	),
);

return $as_content;
