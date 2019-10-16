<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax voting requests


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

require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'app/cookies.php';
require_once AS_INCLUDE_DIR . 'app/likes.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/options.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';


$postid = as_post_text('postid');
$like = as_post_text('like');
$code = as_post_text('code');

$userid = as_get_logged_in_userid();
$cookieid = as_cookie_get();

if (!as_check_form_security_code('like', $code)) {
	$likeerror = as_lang_html('misc/form_security_reload');
} else {
	$post = as_db_select_with_pending(as_db_full_post_selectspec($userid, $postid));
	$likeerror = as_like_error_html($post, $like, $userid, as_request());
}

if ($likeerror === false) {
	as_like_set($post, $userid, as_get_logged_in_handle(), $cookieid, $like);

	$post = as_db_select_with_pending(as_db_full_post_selectspec($userid, $postid));

	$fields = as_post_html_fields($post, $userid, $cookieid, array(), null, array(
		'likeview' => as_get_like_view($post, true), // behave as if on item page since the like succeeded
	));

	$themeclass = as_load_theme_class(as_get_site_theme(), 'voting', null, null);
	$themeclass->initialize();

	echo "AS_AJAX_RESPONSE\n1\n";
	$themeclass->voting_inner_html($fields);

	return;

}

echo "AS_AJAX_RESPONSE\n0\n" . $likeerror;
