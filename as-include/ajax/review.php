<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax create review requests


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

require_once AS_INCLUDE_DIR . 'app/posts.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'app/limits.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';


// Load relevant information about this item

$articleid = as_post_text('a_articleid');
$userid = as_get_logged_in_userid();

list($item, $childposts) = as_db_select_with_pending(
	as_db_full_post_selectspec($userid, $articleid),
	as_db_full_child_posts_selectspec($userid, $articleid)
);


// Check if the item exists, is not closed, and whether the user has permission to do this

if (@$item['basetype'] == 'P' && !as_post_is_closed($item) && !as_user_post_permit_error('permit_post_a', $item, AS_LIMIT_REVIEWS)) {
	require_once AS_INCLUDE_DIR . 'app/captcha.php';
	require_once AS_INCLUDE_DIR . 'app/format.php';
	require_once AS_INCLUDE_DIR . 'app/post-create.php';
	require_once AS_INCLUDE_DIR . 'app/cookies.php';
	require_once AS_INCLUDE_DIR . 'pages/item-view.php';
	require_once AS_INCLUDE_DIR . 'pages/item-submit.php';


	// Try to create the new review

	$usecaptcha = as_user_use_captcha(as_user_level_for_post($item));
	$reviews = as_page_q_load_as($item, $childposts);
	$reviewid = as_page_q_add_a_submit($item, $reviews, $usecaptcha, $in, $errors);

	// If successful, page content will be updated via Ajax

	if (isset($reviewid)) {
		$review = as_db_select_with_pending(as_db_full_post_selectspec($userid, $reviewid));

		$item = $item + as_page_q_post_rules($item, null, null, $childposts); // array union
		$review = $review + as_page_q_post_rules($review, $item, $reviews, null);

		$usershtml = as_userids_handles_html(array($review), true);

		$a_view = as_page_q_review_view($item, $review, false, $usershtml, false);

		$themeclass = as_load_theme_class(as_get_site_theme(), 'ajax-review', null, null);
		$themeclass->initialize();

		echo "AS_AJAX_RESPONSE\n1\n";


		// Send back whether the 'review' button should still be visible

		echo (int)as_opt('allow_multi_reviews') . "\n";


		// Send back the count of reviews

		$countreviews = $item['rcount'] + 1;

		if ($countreviews == 1) {
			echo as_lang_html('item/1_review_title') . "\n";
		} else {
			echo as_lang_html_sub('item/x_reviews_title', $countreviews) . "\n";
		}


		// Send back the HTML

		$themeclass->a_list_item($a_view);

		return;
	}
}


echo "AS_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if there were any problems
