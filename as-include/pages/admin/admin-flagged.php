<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for admin page showing posts with the most flags


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
	header('Location: ../../../');
	exit;
}

require_once AS_INCLUDE_DIR . 'app/admin.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/format.php';


// Find most flagged items, reviews, comments

$userid = as_get_logged_in_userid();

$items = as_db_select_with_pending(
	as_db_flagged_post_qs_selectspec($userid, 0, true)
);


// Check admin privileges (do late to allow one DB query)

if (as_user_maximum_permit_error('permit_hide_show')) {
	$as_content = as_content_prepare();
	$as_content['error'] = as_lang_html('users/no_permission');
	return $as_content;
}


// Check to see if any were cleared or hidden here

$pageerror = as_admin_check_clicks();


// Remove items the user has no permission to hide/show

if (as_user_permit_error('permit_hide_show')) { // if user not allowed to show/hide all posts
	foreach ($items as $index => $item) {
		if (as_user_post_permit_error('permit_hide_show', $item)) {
			unset($items[$index]);
		}
	}
}


// Get information for users

$usershtml = as_userids_handles_html(as_any_get_userids_handles($items));


// Prepare content for theme

$as_content = as_content_prepare();

$as_content['title'] = as_lang_html('admin/most_flagged_title');
$as_content['error'] = isset($pageerror) ? $pageerror : as_admin_page_error();

$as_content['p_list'] = array(
	'form' => array(
		'tags' => 'method="post" action="' . as_self_html() . '"',

		'hidden' => array(
			'code' => as_get_form_security_code('admin/click'),
		),
	),

	'ps' => array(),
);


if (count($items)) {
	foreach ($items as $item) {
		$postid = as_html(isset($item['opostid']) ? $item['opostid'] : $item['postid']);
		$elementid = 'p' . $postid;

		$htmloptions = as_post_html_options($item);
		$htmloptions['likeview'] = false;
		$htmloptions['tagsview'] = ($item['obasetype'] == 'P');
		$htmloptions['reviewsview'] = false;
		$htmloptions['viewsview'] = false;
		$htmloptions['contentview'] = true;
		$htmloptions['flagsview'] = true;
		$htmloptions['elementid'] = $elementid;

		$htmlfields = as_any_to_q_html_fields($item, $userid, as_cookie_get(), $usershtml, null, $htmloptions);

		if (isset($htmlfields['what_url'])) // link directly to relevant content
			$htmlfields['url'] = $htmlfields['what_url'];

		$htmlfields['form'] = array(
			'style' => 'light',

			'buttons' => array(
				'clearflags' => array(
					'tags' => 'name="admin_' . $postid . '_clearflags" onclick="return as_admin_click(this);"',
					'label' => as_lang_html('item/clear_flags_button'),
				),

				'hide' => array(
					'tags' => 'name="admin_' . $postid . '_hide" onclick="return as_admin_click(this);"',
					'label' => as_lang_html('item/hide_button'),
				),
			),
		);

		$as_content['p_list']['ps'][] = $htmlfields;
	}

} else
	$as_content['title'] = as_lang_html('admin/no_flagged_found');

$as_content['script_rel'][] = 'as-content/as-admin.js?' . AS_VERSION;


return $as_content;
