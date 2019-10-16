<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for top scoring users page


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

require_once AS_INCLUDE_DIR . 'db/users.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/format.php';


// Get list of all users

$start = as_get_start();
$users = as_db_select_with_pending(as_db_newest_users_selectspec($start, as_opt_if_loaded('page_size_users')));

$usercount = as_opt('cache_userpointscount');
$pagesize = as_opt('page_size_users');
$users = array_slice($users, 0, $pagesize);
$usershtml = as_userids_handles_html($users);


// Prepare content for theme

$as_content = as_content_prepare();

$as_content['title'] = as_lang_html('main/newest_users');

$as_content['ranking'] = array(
	'title' => as_lang_html('main/newest_users'),
	'items' => array(),
	'rows' => ceil($pagesize / as_opt('columns_users')),
	'type' => 'users',
	'sort' => 'points',
	
	'tools' => array(
		'newusers' => array(
			'type' => 'label', 
			'theme' => 'red',
			'label' => '8 New Users',
		),
	),
	
);

if (count($users)) {
	foreach ($users as $userid => $user) {
		$when = as_when_to_html($user['created'], 7);
		$as_content['ranking']['items'][] = array(
			'avatar' => as_avatar(100, 'profile-user-img img-responsive', $user),
			'label' => $usershtml[$user['userid']],
			'lasttype' => as_user_type($user['type'], false),
			//'score' => as_html(as_format_number($user['points'], 0, true)),
			'score' => $when['data'],
			'raw' => $user,
		);
	}
} else {
	$as_content['title'] = as_lang_html('main/no_active_users');
}

$as_content['canonical'] = as_get_canonical();

$as_content['page_links'] = as_html_page_links(as_request(), $start, $pagesize, $usercount, as_opt('pages_prev_next'));

$as_content['navigation']['sub'] = as_users_sub_navigation();


return $as_content;
