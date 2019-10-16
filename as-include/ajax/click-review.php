<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax single clicks on review


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
require_once AS_INCLUDE_DIR . 'util/sort.php';


// Load relevant information about this review

$reviewid = as_post_text('reviewid');
$articleid = as_post_text('articleid');

$userid = as_get_logged_in_userid();

list($review, $item, $qchildposts, $achildposts) = as_db_select_with_pending(
	as_db_full_post_selectspec($userid, $reviewid),
	as_db_full_post_selectspec($userid, $articleid),
	as_db_full_child_posts_selectspec($userid, $articleid),
	as_db_full_child_posts_selectspec($userid, $reviewid)
);


// Check if there was an operation that succeeded

if (@$review['basetype'] == 'R' && @$item['basetype'] == 'P') {
	$reviews = as_page_q_load_as($item, $qchildposts);

	$item = $item + as_page_q_post_rules($item, null, null, $qchildposts); // array union
	$review = $review + as_page_q_post_rules($review, $item, $qchildposts, $achildposts);

	if (as_page_q_single_click_a($review, $item, $reviews, $achildposts, false, $error)) {
		list($review, $item) = as_db_select_with_pending(
			as_db_full_post_selectspec($userid, $reviewid),
			as_db_full_post_selectspec($userid, $articleid)
		);


		// If so, page content to be updated via Ajax

		echo "AS_AJAX_RESPONSE\n1\n";


		// Send back new count of reviews

		$countreviews = $item['rcount'];

		if ($countreviews == 1)
			echo as_lang_html('item/1_review_title');
		else
			echo as_lang_html_sub('item/x_reviews_title', $countreviews);


		// If the review was not deleted....

		if (isset($review)) {
			$item = $item + as_page_q_post_rules($item, null, null, $qchildposts); // array union
			$review = $review + as_page_q_post_rules($review, $item, $qchildposts, $achildposts);

			$commentsfollows = as_page_q_load_c_follows($item, $qchildposts, $achildposts);

			foreach ($commentsfollows as $key => $commentfollow) {
				$commentsfollows[$key] = $commentfollow + as_page_q_post_rules($commentfollow, $review, $commentsfollows, null);
			}

			$usershtml = as_userids_handles_html(array_merge(array($review), $commentsfollows), true);
			as_sort_by($commentsfollows, 'created');

			$a_view = as_page_q_review_view($item, $review, ($review['postid'] == $item['selchildid'] && $review['type'] == 'R'),
				$usershtml, false);

			$a_view['c_list'] = as_page_q_comment_follow_list($item, $review, $commentsfollows, false, $usershtml, false, null);

			$themeclass = as_load_theme_class(as_get_site_theme(), 'ajax-review', null, null);
			$themeclass->initialize();


			// ... send back the HTML for it

			echo "\n";

			$themeclass->a_list_item($a_view);
		}

		return;
	}
}


echo "AS_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if something failed
