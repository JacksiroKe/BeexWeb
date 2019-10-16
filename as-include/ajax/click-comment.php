<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax single clicks on comments


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

require_once AS_INCLUDE_DIR . 'app/cookies.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'pages/item-view.php';
require_once AS_INCLUDE_DIR . 'pages/item-submit.php';


// Load relevant information about this comment

$commentid = as_post_text('commentid');
$articleid = as_post_text('articleid');
$parentid = as_post_text('parentid');

$userid = as_get_logged_in_userid();

list($comment, $item, $parent, $children) = as_db_select_with_pending(
	as_db_full_post_selectspec($userid, $commentid),
	as_db_full_post_selectspec($userid, $articleid),
	as_db_full_post_selectspec($userid, $parentid),
	as_db_full_child_posts_selectspec($userid, $parentid)
);


// Check if there was an operation that succeeded

if (@$comment['basetype'] == 'C' && @$item['basetype'] == 'P' &&
	(@$parent['basetype'] == 'P' || @$parent['basetype'] == 'R')
) {
	$comment = $comment + as_page_q_post_rules($comment, $parent, $children, null); // array union

	if (as_page_q_single_click_c($comment, $item, $parent, $error)) {
		$comment = as_db_select_with_pending(as_db_full_post_selectspec($userid, $commentid));

		// If so, page content to be updated via Ajax

		echo "AS_AJAX_RESPONSE\n1";

		// If the comment was not deleted...

		if (isset($comment)) {
			$parent = $parent + as_page_q_post_rules($parent, ($articleid == $parentid) ? null : $item, null, $children);
			// in theory we should retrieve the parent's siblings for the above, but they're not going to be relevant
			$comment = $comment + as_page_q_post_rules($comment, $parent, $children, null);

			$usershtml = as_userids_handles_html(array($comment), true);

			$c_view = as_page_q_comment_view($item, $parent, $comment, $usershtml, false);

			$themeclass = as_load_theme_class(as_get_site_theme(), 'ajax-comment', null, null);
			$themeclass->initialize();


			// ... send back the HTML for it

			echo "\n";

			$themeclass->c_list_item($c_view);
		}

		return;
	}
}


echo "AS_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if something failed
