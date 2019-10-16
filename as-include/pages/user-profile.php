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

if ($handle == $username)
{
	$profile1['body']['items'][] = array( 
		'tag' => array('link', 'btn btn-primary btn-block'),
		'href' => '#',
		'label' => '<b>Edit Your Account</b>',
	);

}
else {
	$profile1['body']['items'][] = array( 
		'tag' => array('link', 'btn btn-primary btn-block'),
		'href' => '#',
		'label' => '<b>Follow</b>',
	);
}

if (AS_USER_TYPE == 'A' || AS_USER_TYPE == 'SA') 
{		
	if ($handle != $username)
	{
		$profile1['body']['items'][] = array( 
			'tag' => array('link', 'btn btn-primary btn-block'),
			'href' => $handle.'/edit',
			'label' => '<b>Edit This User</b>',
		);
	}	
}

$profile2 = array( 'type' => 'box', 'theme' => 'information', 'title' => 'About Me', 
	'body' => array(
		'type' => 'box-body',
		'items' => array(
			0 => array(
				'tag' => array('strong'), 
				'itag' => array('book', 5), 
				'data' => array( 'text' => 'Education'),
			),
			1 => array(
				'tag' => array('p', 'text-muted'), 
				'data' => array(
					'text' => 'B.S. in Computer Science from the University of Tennessee at Knoxville',
				),
			),
			2 => '',
			3 => array(
				'tag' => array('strong'), 
				'itag' => array('map-marker', 5), 
				'data' => array( 'text' => 'Location' ),
			),
			4 => array(
				'tag' => array('p', 'text-muted'), 
				'data' => array( 'text' => 'Malibu, California' ),
			),
			5 => '',
			6 => array(
				'tag' => array('strong'), 
				'itag' => array('pencil', 5), 
				'data' => array( 'text' => 'Skills' ),
			),
			7 => array(
				'tag' => array('p'), 
				'data' => array(
					'sub-data' => array(
						'UI Design' => array('label', 'danger'),
						'Coding' => array('label', 'success'),
						'Javascript' => array('label', 'info', 'Javascript'),
						'PHP' => array('label', 'warning'),
						'Node.js' => array('label', 'primary'),
					),
				),
			),
			8 => '',
			9 => array(
				'tag' => array('strong'), 
				'itag' => array('file-text-o', 5), 
				'data' => array( 'text' => 'Notes' ),
			),
			10 => array(
				'tag' => array('p'), 
				'data' => array(
					'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam fermentum enim neque',
				),
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

$tabview = array( 
	'type' => 'nav-tabs-custom', 
	'navs' => array('activity' => 'Activity', 'timeline' => 'Timeline'),//, 'Settings' => 'settings'),
	'body' => array(
		'activity' => array( $posts ),
		'timeline' => array( $tlines ),
		//'settings' => array( $account ),
	),
);

$as_content['row_view'][] = array(
	'colms' => array(
		0 => array('class' => 'col-md-3', 'c_items' => array($profile1) ),
		1 => array('class' => 'col-md-9', 'c_items' => array($tabview) ),
	),
);

return $as_content;
