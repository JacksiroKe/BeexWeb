<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax request to view full comment list


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

require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'app/cookies.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'pages/item-view.php';
require_once AS_INCLUDE_DIR . 'util/sort.php';


// Load relevant information about this item and check it exists

$articleid = as_post_text('c_articleid');
$parentid = as_post_text('c_parentid');
$userid = as_get_logged_in_userid();

list($item, $parent, $children, $duplicateposts) = as_db_select_with_pending(
	as_db_full_post_selectspec($userid, $articleid),
	as_db_full_post_selectspec($userid, $parentid),
	as_db_full_child_posts_selectspec($userid, $parentid),
	as_db_post_duplicates_selectspec($articleid)
);

if (isset($parent)) {
	$parent = $parent + as_page_q_post_rules($parent, null, null, $children + $duplicateposts);
	// in theory we should retrieve the parent's parent and siblings for the above, but they're not going to be relevant

	foreach ($children as $key => $child) {
		$children[$key] = $child + as_page_q_post_rules($child, $parent, $children, null);
	}

	$commentsfollows = $articleid == $parentid
		? as_page_q_load_c_follows($item, $children, array(), $duplicateposts)
		: as_page_q_load_c_follows($item, array(), $children);

	$usershtml = as_userids_handles_html($commentsfollows, true);

	as_sort_by($commentsfollows, 'created');

	$c_list = as_page_q_comment_follow_list($item, $parent, $commentsfollows, true, $usershtml, false, null);

	$themeclass = as_load_theme_class(as_get_site_theme(), 'ajax-comments', null, null);
	$themeclass->initialize();

	echo "AS_AJAX_RESPONSE\n1\n";


	// Send back the HTML

	$themeclass->c_list_items($c_list['cs']);

	return;
}


echo "AS_AJAX_RESPONSE\n0\n";
