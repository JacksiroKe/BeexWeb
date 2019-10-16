<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax create comment requests


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
require_once AS_INCLUDE_DIR . 'app/limits.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';


// Load relevant information about this item and the comment parent

$articleid = as_post_text('c_articleid');
$parentid = as_post_text('c_parentid');
$userid = as_get_logged_in_userid();

list($item, $parent, $children) = as_db_select_with_pending(
	as_db_full_post_selectspec($userid, $articleid),
	as_db_full_post_selectspec($userid, $parentid),
	as_db_full_child_posts_selectspec($userid, $parentid)
);


// Check if the item and parent exist, and whether the user has permission to do this

if (@$item['basetype'] == 'P' && (@$parent['basetype'] == 'P' || @$parent['basetype'] == 'R') &&
	!as_user_post_permit_error('permit_post_c', $parent, AS_LIMIT_COMMENTS)
) {
	require_once AS_INCLUDE_DIR . 'app/captcha.php';
	require_once AS_INCLUDE_DIR . 'app/format.php';
	require_once AS_INCLUDE_DIR . 'app/post-create.php';
	require_once AS_INCLUDE_DIR . 'app/cookies.php';
	require_once AS_INCLUDE_DIR . 'pages/item-view.php';
	require_once AS_INCLUDE_DIR . 'pages/item-submit.php';
	require_once AS_INCLUDE_DIR . 'util/sort.php';


	// Try to create the new comment

	$usecaptcha = as_user_use_captcha(as_user_level_for_post($item));
	$commentid = as_page_q_add_c_submit($item, $parent, $children, $usecaptcha, $in, $errors);


	// If successful, page content will be updated via Ajax

	if (isset($commentid)) {
		$children = as_db_select_with_pending(as_db_full_child_posts_selectspec($userid, $parentid));

		$parent = $parent + as_page_q_post_rules($parent, ($articleid == $parentid) ? null : $item, null, $children);
		// in theory we should retrieve the parent's siblings for the above, but they're not going to be relevant

		foreach ($children as $key => $child) {
			$children[$key] = $child + as_page_q_post_rules($child, $parent, $children, null);
		}

		$usershtml = as_userids_handles_html($children, true);

		as_sort_by($children, 'created');

		$c_list = as_page_q_comment_follow_list($item, $parent, $children, true, $usershtml, false, null);

		$themeclass = as_load_theme_class(as_get_site_theme(), 'ajax-comments', null, null);
		$themeclass->initialize();

		echo "AS_AJAX_RESPONSE\n1\n";


		// send back the ID of the new comment
		echo as_anchor('C', $commentid) . "\n";


		// send back the HTML
		$themeclass->c_list_items($c_list['cs']);

		return;
	}
}

echo "AS_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if there were any problems
