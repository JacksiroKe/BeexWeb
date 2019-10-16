<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Changing items, review and comments (application level)


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

require_once AS_INCLUDE_DIR . 'app/post-create.php';
require_once AS_INCLUDE_DIR . 'app/updates.php';
require_once AS_INCLUDE_DIR . 'db/post-create.php';
require_once AS_INCLUDE_DIR . 'db/post-update.php';
require_once AS_INCLUDE_DIR . 'db/points.php';
require_once AS_INCLUDE_DIR . 'db/hotness.php';


define('AS_POST_STATUS_NORMAL', 0);
define('AS_POST_STATUS_HIDDEN', 1);
define('AS_POST_STATUS_QUEUED', 2);


/**
 * Change the fields of a item (application level) to $title, $content, $format, $tagstring, $notify, $extravalue
 * and $name, then reindex based on $text. For backwards compatibility if $name is null then the name will not be
 * changed. Pass the item's database record before changes in $oldarticle and details of the user doing this in
 * $userid, $handle and $cookieid. Set $remoderate to true if the item should be requeued for moderation if
 * modified. Set $silent to true to not mark the item as edited. Reports event as appropriate. See /as-include/app/posts.php
 * for a higher-level function which is easier to use.
 * @param $oldarticle
 * @param $title
 * @param $content
 * @param $format
 * @param $text
 * @param $tagstring
 * @param $notify
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $extravalue
 * @param $name
 * @param bool $remoderate
 * @param bool $silent
 */
function as_article_set_content($oldarticle, $title, $content, $format, $text, $tagstring, $notify, $userid, $handle, $cookieid, $extravalue = null, $name = null, $remoderate = false, $silent = false)
{
	as_post_unindex($oldarticle['postid']);

	$wasqueued = ($oldarticle['type'] == 'P_QUEUED');
	$titlechanged = strcmp($oldarticle['title'], $title) !== 0;
	$contentchanged = strcmp($oldarticle['content'], $content) !== 0 || strcmp($oldarticle['format'], $format) !== 0;
	$tagschanged = strcmp($oldarticle['tags'], $tagstring) !== 0;
	$setupdated = ($titlechanged || $contentchanged || $tagschanged) && (!$wasqueued) && !$silent;

	as_db_post_set_content($oldarticle['postid'], $title, $content, $format, $tagstring, $notify,
		$setupdated ? $userid : null, $setupdated ? as_remote_ip_address() : null,
		($titlechanged || $contentchanged) ? AS_UPDATE_CONTENT : AS_UPDATE_TAGS, $name);

	if (isset($extravalue)) {
		require_once AS_INCLUDE_DIR . 'db/metas.php';
		as_db_postmeta_set($oldarticle['postid'], 'as_q_extra', $extravalue);
	}

	if ($setupdated && $remoderate) {
		require_once AS_INCLUDE_DIR . 'app/posts.php';

		$reviews = as_post_get_article_reviews($oldarticle['postid']);
		$commentsfollows = as_post_get_article_commentsfollows($oldarticle['postid']);
		$closepost = as_post_get_article_closepost($oldarticle['postid']);

		foreach ($reviews as $review)
			as_post_unindex($review['postid']);

		foreach ($commentsfollows as $comment) {
			if ($comment['basetype'] == 'C')
				as_post_unindex($comment['postid']);
		}

		if (@$closepost['parentid'] == $oldarticle['postid'])
			as_post_unindex($closepost['postid']);

		as_db_post_set_type($oldarticle['postid'], 'P_QUEUED');
		as_update_counts_for_q($oldarticle['postid']);
		as_db_queuedcount_update();
		as_db_points_update_ifuser($oldarticle['userid'], array('qposts', 'aselects'));

		if ($oldarticle['flagcount'])
			as_db_flaggedcount_update();

	} elseif ($oldarticle['type'] == 'P') { // not hidden or queued
		as_post_index($oldarticle['postid'], 'P', $oldarticle['postid'], $oldarticle['parentid'], $title, $content, $format, $text, $tagstring, $oldarticle['categoryid']);
		if ($tagschanged) {
			as_db_tagcount_update();
		}
	}

	$eventparams = array(
		'postid' => $oldarticle['postid'],
		'title' => $title,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'tags' => $tagstring,
		'extra' => $extravalue,
		'name' => $name,
		'oldarticle' => $oldarticle,
	);

	as_report_event('q_edit', $userid, $handle, $cookieid, $eventparams + array(
		'silent' => $silent,
		'oldtitle' => $oldarticle['title'],
		'oldcontent' => $oldarticle['content'],
		'oldformat' => $oldarticle['format'],
		'oldtags' => $oldarticle['tags'],
		'titlechanged' => $titlechanged,
		'contentchanged' => $contentchanged,
		'tagschanged' => $tagschanged,
	));

	if ($setupdated && $remoderate)
		as_report_event('q_requeue', $userid, $handle, $cookieid, $eventparams);
}


/**
 * Set the selected review (application level) of $oldarticle to $selchildid. Pass details of the user doing this
 * in $userid, $handle and $cookieid, and the database records for the selected and deselected reviews in $reviews.
 * Handles user points values and notifications.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $oldarticle
 * @param $selchildid
 * @param $reviews
 */
function as_article_set_selchildid($userid, $handle, $cookieid, $oldarticle, $selchildid, $reviews)
{
	$oldselchildid = $oldarticle['selchildid'];

	$lastip = as_remote_ip_address();

	as_db_post_set_selchildid($oldarticle['postid'], isset($selchildid) ? $selchildid : null, $userid, $lastip);
	as_db_points_update_ifuser($oldarticle['userid'], 'aselects');
	as_db_unselpcount_update();

	if (isset($oldselchildid) && isset($reviews[$oldselchildid])) {
		as_db_points_update_ifuser($reviews[$oldselchildid]['userid'], 'aselecteds');

		as_report_event('a_unselect', $userid, $handle, $cookieid, array(
			'parentid' => $oldarticle['postid'],
			'parent' => $oldarticle,
			'postid' => $oldselchildid,
			'review' => $reviews[$oldselchildid],
		));

		if (!empty($oldarticle['closed']) && empty($oldarticle['closedbyid'])) {
			as_db_post_set_closed($oldarticle['postid'], null, $userid, $lastip);

			as_report_event('q_reopen', $userid, $handle, $cookieid, array(
				'postid' => $oldarticle['postid'],
				'oldarticle' => $oldarticle,
			));
		}
	}

	if (isset($selchildid)) {
		as_db_points_update_ifuser($reviews[$selchildid]['userid'], 'aselecteds');

		as_report_event('a_select', $userid, $handle, $cookieid, array(
			'parentid' => $oldarticle['postid'],
			'parent' => $oldarticle,
			'postid' => $selchildid,
			'review' => $reviews[$selchildid],
		));

		if (empty($oldarticle['closed']) && as_opt('do_close_on_select')) {
			as_db_post_set_closed($oldarticle['postid'], null, $userid, $lastip);

			as_report_event('q_close', $userid, $handle, $cookieid, array(
				'postid' => $oldarticle['postid'],
				'oldarticle' => $oldarticle,
				'reason' => 'review-selected',
				'originalid' => $reviews[$selchildid],
			));
		}
	}
}


/**
 * Reopen $oldarticle if it was closed. Pass details of the user doing this in $userid, $handle and $cookieid, and the
 * $oldclosepost (to match $oldarticle['closedbyid']) if any.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldarticle
 * @param $oldclosepost
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_article_close_clear($oldarticle, $oldclosepost, $userid, $handle, $cookieid)
{
	if (isset($oldarticle['closedbyid'])) {
		as_db_post_set_closed($oldarticle['postid'], null, $userid, as_remote_ip_address());

		if (isset($oldclosepost) && ($oldclosepost['parentid'] == $oldarticle['postid'])) {
			as_post_unindex($oldclosepost['postid']);
			as_db_post_delete($oldclosepost['postid']);
		}

		as_report_event('q_reopen', $userid, $handle, $cookieid, array(
			'postid' => $oldarticle['postid'],
			'oldarticle' => $oldarticle,
		));
	}
}


/**
 * Close $oldarticle as a duplicate of the item with id $originalpostid. Pass details of the user doing this in
 * $userid, $handle and $cookieid, and the $oldclosepost (to match $oldarticle['closedbyid']) if any. See
 * /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldarticle
 * @param $oldclosepost
 * @param $originalpostid
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_article_close_duplicate($oldarticle, $oldclosepost, $originalpostid, $userid, $handle, $cookieid)
{
	as_article_close_clear($oldarticle, $oldclosepost, $userid, $handle, $cookieid);

	as_db_post_set_closed($oldarticle['postid'], $originalpostid, $userid, as_remote_ip_address());

	as_report_event('q_close', $userid, $handle, $cookieid, array(
		'postid' => $oldarticle['postid'],
		'oldarticle' => $oldarticle,
		'reason' => 'duplicate',
		'originalid' => $originalpostid,
	));
}


/**
 * Close $oldarticle with the reason given in $note. Pass details of the user doing this in $userid, $handle and
 * $cookieid, and the $oldclosepost (to match $oldarticle['closedbyid']) if any.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldarticle
 * @param $oldclosepost
 * @param $note
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_article_close_other($oldarticle, $oldclosepost, $note, $userid, $handle, $cookieid)
{
	as_article_close_clear($oldarticle, $oldclosepost, $userid, $handle, $cookieid);

	$postid = as_db_post_create('NOTE', $oldarticle['postid'], $userid, isset($userid) ? null : $cookieid,
		as_remote_ip_address(), null, $note, '', null, null, $oldarticle['categoryid']);

	as_db_posts_calc_category_path($postid);

	if ($oldarticle['type'] == 'P')
		as_post_index($postid, 'NOTE', $oldarticle['postid'], $oldarticle['postid'], null, $note, '', $note, null, $oldarticle['categoryid']);

	as_db_post_set_closed($oldarticle['postid'], $postid, $userid, as_remote_ip_address());

	as_report_event('q_close', $userid, $handle, $cookieid, array(
		'postid' => $oldarticle['postid'],
		'oldarticle' => $oldarticle,
		'reason' => 'other',
		'note' => $note,
	));
}


/**
 * Set $oldarticle to hidden if $hidden is true, visible/normal if otherwise. All other parameters are as for as_article_set_status(...)
 * @deprecated Replaced by as_article_set_status.
 * @param $oldarticle
 * @param $hidden
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $reviews
 * @param $commentsfollows
 * @param $closepost
 */
function as_article_set_hidden($oldarticle, $hidden, $userid, $handle, $cookieid, $reviews, $commentsfollows, $closepost = null)
{
	as_article_set_status($oldarticle, $hidden ? AS_POST_STATUS_HIDDEN : AS_POST_STATUS_NORMAL, $userid, $handle, $cookieid, $reviews, $commentsfollows, $closepost);
}


/**
 * Set the status (application level) of $oldarticle to $status, one of the AS_POST_STATUS_* constants above. Pass
 * details of the user doing this in $userid, $handle and $cookieid, the database records for all reviews to the
 * item in $reviews, the database records for all comments on the item or the item's reviews in
 * $commentsfollows ($commentsfollows can also contain records for follow-on items which are ignored), and
 * $closepost to match $oldarticle['closedbyid'] (if any). Handles indexing, user points, cached counts and event
 * reports. See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldarticle
 * @param $status
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $reviews
 * @param $commentsfollows
 * @param $closepost
 */
function as_article_set_status($oldarticle, $status, $userid, $handle, $cookieid, $reviews, $commentsfollows, $closepost = null)
{
	require_once AS_INCLUDE_DIR . 'app/format.php';
	require_once AS_INCLUDE_DIR . 'app/updates.php';

	$washidden = ($oldarticle['type'] == 'P_HIDDEN');
	$wasqueued = ($oldarticle['type'] == 'P_QUEUED');
	$wasrequeued = $wasqueued && isset($oldarticle['updated']);

	as_post_unindex($oldarticle['postid']);

	foreach ($reviews as $review) {
		as_post_unindex($review['postid']);
	}

	foreach ($commentsfollows as $comment) {
		if ($comment['basetype'] == 'C')
			as_post_unindex($comment['postid']);
	}

	if (@$closepost['parentid'] == $oldarticle['postid'])
		as_post_unindex($closepost['postid']);

	$setupdated = false;
	$event = null;

	if ($status == AS_POST_STATUS_QUEUED) {
		$newtype = 'P_QUEUED';
		if (!$wasqueued)
			$event = 'q_requeue'; // same event whether it was hidden or shown before

	} elseif ($status == AS_POST_STATUS_HIDDEN) {
		$newtype = 'P_HIDDEN';
		if (!$washidden) {
			$event = $wasqueued ? 'q_reject' : 'q_hide';
			if (!$wasqueued)
				$setupdated = true;
		}

	} elseif ($status == AS_POST_STATUS_NORMAL) {
		$newtype = 'P';
		if ($wasqueued)
			$event = 'q_approve';
		elseif ($washidden) {
			$event = 'q_reshow';
			$setupdated = true;
		}

	} else
		as_fatal_error('Unknown status in as_article_set_status(): ' . $status);

	as_db_post_set_type($oldarticle['postid'], $newtype, $setupdated ? $userid : null, $setupdated ? as_remote_ip_address() : null, AS_UPDATE_VISIBLE);

	if ($wasqueued && $status == AS_POST_STATUS_NORMAL && as_opt('moderate_update_time')) { // ... for approval of a post, can set time to now instead
		if ($wasrequeued) // reset edit time to now if there was one, since we're approving the edit...
			as_db_post_set_updated($oldarticle['postid'], null);

		else { // ... otherwise we're approving original created post
			as_db_post_set_created($oldarticle['postid'], null);
			as_db_hotness_update($oldarticle['postid']);
		}
	}

	as_update_counts_for_q($oldarticle['postid']);
	as_db_points_update_ifuser($oldarticle['userid'], array('qposts', 'aselects'));

	if ($wasqueued || ($status == AS_POST_STATUS_QUEUED))
		as_db_queuedcount_update();

	if ($oldarticle['flagcount'])
		as_db_flaggedcount_update();

	if ($status == AS_POST_STATUS_NORMAL) {
		as_post_index($oldarticle['postid'], 'P', $oldarticle['postid'], $oldarticle['parentid'], $oldarticle['title'], $oldarticle['content'],
			$oldarticle['format'], as_viewer_text($oldarticle['content'], $oldarticle['format']), $oldarticle['tags'], $oldarticle['categoryid']);

		foreach ($reviews as $review) {
			if ($review['type'] == 'R') { // even if item visible, don't index hidden or queued reviews
				as_post_index($review['postid'], $review['type'], $oldarticle['postid'], $review['parentid'], null,
					$review['content'], $review['format'], as_viewer_text($review['content'], $review['format']), null, $review['categoryid']);
			}
		}

		foreach ($commentsfollows as $comment) {
			if ($comment['type'] == 'C') {
				$review = @$reviews[$comment['parentid']];

				if (!isset($review) || $review['type'] == 'R') { // don't index comment if it or its parent is hidden
					as_post_index($comment['postid'], $comment['type'], $oldarticle['postid'], $comment['parentid'], null,
						$comment['content'], $comment['format'], as_viewer_text($comment['content'], $comment['format']), null, $comment['categoryid']);
				}
			}
		}

		if ($closepost['parentid'] == $oldarticle['postid']) {
			as_post_index($closepost['postid'], $closepost['type'], $oldarticle['postid'], $closepost['parentid'], null,
				$closepost['content'], $closepost['format'], as_viewer_text($closepost['content'], $closepost['format']), null, $closepost['categoryid']);
		}
	}

	as_article_uncache($oldarticle['postid']); // remove hidden posts immediately

	$eventparams = array(
		'postid' => $oldarticle['postid'],
		'parentid' => $oldarticle['parentid'],
		'parent' => isset($oldarticle['parentid']) ? as_db_single_select(as_db_full_post_selectspec(null, $oldarticle['parentid'])) : null,
		'title' => $oldarticle['title'],
		'content' => $oldarticle['content'],
		'format' => $oldarticle['format'],
		'text' => as_viewer_text($oldarticle['content'], $oldarticle['format']),
		'tags' => $oldarticle['tags'],
		'categoryid' => $oldarticle['categoryid'],
		'name' => $oldarticle['name'],
	);

	if (isset($event)) {
		as_report_event($event, $userid, $handle, $cookieid, $eventparams + array(
				'oldarticle' => $oldarticle,
			));
	}

	if ($wasqueued && ($status == AS_POST_STATUS_NORMAL) && !$wasrequeued) {
		require_once AS_INCLUDE_DIR . 'db/selects.php';
		require_once AS_INCLUDE_DIR . 'util/string.php';

		as_report_event('q_post', $oldarticle['userid'], $oldarticle['handle'], $oldarticle['cookieid'], $eventparams + array(
			'notify' => isset($oldarticle['notify']),
			'email' => as_email_validate($oldarticle['notify']) ? $oldarticle['notify'] : null,
			'delayed' => $oldarticle['created'],
		));
	}
}


/**
 * Sets the category (application level) of $oldarticle to $categoryid. Pass details of the user doing this in
 * $userid, $handle and $cookieid, the database records for all reviews to the item in $reviews, the database
 * records for all comments on the item or the item's reviews in $commentsfollows ($commentsfollows can also
 * contain records for follow-on items which are ignored), and $closepost to match $oldarticle['closedbyid'] (if any).
 * Set $silent to true to not mark the item as edited. Handles cached counts and event reports and will reset category
 * IDs and paths for all reviews and comments. See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldarticle
 * @param $categoryid
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $reviews
 * @param $commentsfollows
 * @param $closepost
 * @param bool $silent
 */
function as_article_set_category($oldarticle, $categoryid, $userid, $handle, $cookieid, $reviews, $commentsfollows, $closepost = null, $silent = false)
{
	$oldpath = as_db_post_get_category_path($oldarticle['postid']);

	as_db_post_set_category($oldarticle['postid'], $categoryid, $silent ? null : $userid, $silent ? null : as_remote_ip_address());
	as_db_posts_calc_category_path($oldarticle['postid']);

	$newpath = as_db_post_get_category_path($oldarticle['postid']);

	as_db_category_path_pcount_update($oldpath);
	as_db_category_path_pcount_update($newpath);

	$otherpostids = array();
	foreach ($reviews as $review) {
		$otherpostids[] = $review['postid'];
	}

	foreach ($commentsfollows as $comment) {
		if ($comment['basetype'] == 'C')
			$otherpostids[] = $comment['postid'];
	}

	if (@$closepost['parentid'] == $oldarticle['postid'])
		$otherpostids[] = $closepost['postid'];

	as_db_posts_set_category_path($otherpostids, $newpath);

	$searchmodules = as_load_modules_with('search', 'move_post');
	foreach ($searchmodules as $searchmodule) {
		$searchmodule->move_post($oldarticle['postid'], $categoryid);
		foreach ($otherpostids as $otherpostid) {
			$searchmodule->move_post($otherpostid, $categoryid);
		}
	}

	as_report_event('q_move', $userid, $handle, $cookieid, array(
		'postid' => $oldarticle['postid'],
		'oldarticle' => $oldarticle,
		'categoryid' => $categoryid,
		'oldcategoryid' => $oldarticle['categoryid'],
	));
}


/**
 * Permanently delete a item (application level) from the database. The item must not have any reviews or
 * comments on it. Pass details of the user doing this in $userid, $handle and $cookieid, and $closepost to match
 * $oldarticle['closedbyid'] (if any). Handles unindexing, likes, points, cached counts and event reports.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldarticle
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $oldclosepost
 */
function as_article_delete($oldarticle, $userid, $handle, $cookieid, $oldclosepost = null)
{
	require_once AS_INCLUDE_DIR . 'db/likes.php';

	if ($oldarticle['type'] != 'P_HIDDEN')
		as_fatal_error('Tried to delete a non-hidden item');

	$params = array(
		'postid' => $oldarticle['postid'],
		'oldarticle' => $oldarticle,
	);

	as_report_event('q_delete_before', $userid, $handle, $cookieid, $params);

	if (isset($oldclosepost) && ($oldclosepost['parentid'] == $oldarticle['postid'])) {
		as_db_post_set_closed($oldarticle['postid'], null); // for foreign key constraint
		as_post_unindex($oldclosepost['postid']);
		as_db_post_delete($oldclosepost['postid']);
	}

	$useridlikes = as_db_userlike_post_get($oldarticle['postid']);
	$oldpath = as_db_post_get_category_path($oldarticle['postid']);

	as_post_unindex($oldarticle['postid']);
	as_db_post_delete($oldarticle['postid']); // also deletes any related likeds due to foreign key cascading
	as_update_counts_for_q(null);
	as_db_category_path_pcount_update($oldpath); // don't do inside as_update_counts_for_q() since post no longer exists
	as_db_points_update_ifuser($oldarticle['userid'], array('qposts', 'aselects', 'qlikeds', 'positivelikeds', 'negativelikeds'));

	foreach ($useridlikes as $likeruserid => $like) {
		// could do this in one query like in as_db_users_recalc_points() but this will do for now - unlikely to be many likes
		as_db_points_update_ifuser($likeruserid, ($like > 0) ? 'qpositivelikes' : 'qnegativelikes');
	}

	as_report_event('q_delete', $userid, $handle, $cookieid, $params);
}


/**
 * Set the author (application level) of $oldarticle to $userid and also pass $handle and $cookieid
 * of user. Updates points and reports events as appropriate.
 * @param $oldarticle
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_article_set_userid($oldarticle, $userid, $handle, $cookieid)
{
	require_once AS_INCLUDE_DIR . 'db/likes.php';

	$postid = $oldarticle['postid'];

	as_db_post_set_userid($postid, $userid);
	as_db_userlike_remove_own($postid);
	as_db_post_recount_likes($postid);

	as_db_points_update_ifuser($oldarticle['userid'], array('qposts', 'aselects', 'qlikeds', 'positivelikeds', 'negativelikeds'));
	as_db_points_update_ifuser($userid, array('qposts', 'aselects', 'qlikeds', 'qpositivelikes', 'qnegativelikes', 'positivelikeds', 'negativelikeds'));

	as_report_event('q_claim', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'oldarticle' => $oldarticle,
	));
}


/**
 * Remove post $postid from our index and update appropriate word counts. Calls through to all search modules.
 * @param $postid
 */
function as_post_unindex($postid)
{
	global $as_post_indexing_suspended;

	if ($as_post_indexing_suspended > 0)
		return;

	// Send through to any search modules for unindexing

	$searchmodules = as_load_modules_with('search', 'unindex_post');
	foreach ($searchmodules as $searchmodule) {
		$searchmodule->unindex_post($postid);
	}
}


/**
 * Delete the cache for a item. Used after it or its reviews/comments are hidden, to prevent them remaining visible to visitors/search engines.
 * @param int $articleId Post ID to delete.
 * @return bool
 */
function as_article_uncache($articleId)
{
	$cacheDriver = APS_Storage_CacheFactory::getCacheDriver();
	return $cacheDriver->delete("item:$articleId");
}


/**
 * Change the fields of an review (application level) to $content, $format, $notify and $name, then reindex based on
 * $text. For backwards compatibility if $name is null then the name will not be changed. Pass the review's database
 * record before changes in $oldreview, the item's in $item, and details of the user doing this in $userid,
 * $handle and $cookieid. Set $remoderate to true if the item should be requeued for moderation if modified. Set
 * $silent to true to not mark the item as edited. Handle indexing and event reports as appropriate. See
 * /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldreview
 * @param $content
 * @param $format
 * @param $text
 * @param $notify
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $item
 * @param $name
 * @param bool $remoderate
 * @param bool $silent
 */
function as_review_set_content($oldreview, $content, $format, $text, $notify, $userid, $handle, $cookieid, $item, $name = null, $remoderate = false, $silent = false)
{
	as_post_unindex($oldreview['postid']);

	$wasqueued = ($oldreview['type'] == 'R_QUEUED');
	$contentchanged = strcmp($oldreview['content'], $content) || strcmp($oldreview['format'], $format);
	$setupdated = $contentchanged && (!$wasqueued) && !$silent;

	as_db_post_set_content($oldreview['postid'], $oldreview['title'], $content, $format, $oldreview['tags'], $notify,
		$setupdated ? $userid : null, $setupdated ? as_remote_ip_address() : null, AS_UPDATE_CONTENT, $name);

	if ($setupdated && $remoderate) {
		require_once AS_INCLUDE_DIR . 'app/posts.php';

		$commentsfollows = as_post_get_review_commentsfollows($oldreview['postid']);

		foreach ($commentsfollows as $comment) {
			if ($comment['basetype'] == 'C' && $comment['parentid'] == $oldreview['postid'])
				as_post_unindex($comment['postid']);
		}

		as_db_post_set_type($oldreview['postid'], 'R_QUEUED');
		as_update_q_counts_for_a($item['postid']);
		as_db_queuedcount_update();
		as_db_points_update_ifuser($oldreview['userid'], array('aposts', 'aselecteds'));

		if ($oldreview['flagcount'])
			as_db_flaggedcount_update();

	} elseif ($oldreview['type'] == 'R' && $item['type'] == 'P') { // don't index if item or review are hidden/queued
		as_post_index($oldreview['postid'], 'R', $item['postid'], $oldreview['parentid'], null, $content, $format, $text, null, $oldreview['categoryid']);
	}

	$eventparams = array(
		'postid' => $oldreview['postid'],
		'parentid' => $oldreview['parentid'],
		'parent' => $item,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'name' => $name,
		'oldreview' => $oldreview,
	);

	as_report_event('a_edit', $userid, $handle, $cookieid, $eventparams + array(
		'silent' => $silent,
		'oldcontent' => $oldreview['content'],
		'oldformat' => $oldreview['format'],
		'contentchanged' => $contentchanged,
	));

	if ($setupdated && $remoderate)
		as_report_event('a_requeue', $userid, $handle, $cookieid, $eventparams);
}


/**
 * Set $oldreview to hidden if $hidden is true, visible/normal if otherwise. All other parameters are as for as_review_set_status(...)
 * @deprecated Replaced by as_review_set_status.
 * @param $oldreview
 * @param $hidden
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $item
 * @param $commentsfollows
 */
function as_review_set_hidden($oldreview, $hidden, $userid, $handle, $cookieid, $item, $commentsfollows)
{
	as_review_set_status($oldreview, $hidden ? AS_POST_STATUS_HIDDEN : AS_POST_STATUS_NORMAL, $userid, $handle, $cookieid, $item, $commentsfollows);
}


/**
 * Set the status (application level) of $oldreview to $status, one of the AS_POST_STATUS_* constants above. Pass
 * details of the user doing this in $userid, $handle and $cookieid, the database record for the item in $item,
 * and the database records for all comments on the review in $commentsfollows ($commentsfollows can also contain other
 * records which are ignored). Handles indexing, user points, cached counts and event reports. See /as-include/app/posts.php for
 * a higher-level function which is easier to use.
 * @param $oldreview
 * @param $status
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $item
 * @param $commentsfollows
 */
function as_review_set_status($oldreview, $status, $userid, $handle, $cookieid, $item, $commentsfollows)
{
	require_once AS_INCLUDE_DIR . 'app/format.php';

	$washidden = ($oldreview['type'] == 'R_HIDDEN');
	$wasqueued = ($oldreview['type'] == 'R_QUEUED');
	$wasrequeued = $wasqueued && isset($oldreview['updated']);

	as_post_unindex($oldreview['postid']);

	foreach ($commentsfollows as $comment) {
		if ($comment['basetype'] == 'C' && $comment['parentid'] == $oldreview['postid'])
			as_post_unindex($comment['postid']);
	}

	$setupdated = false;
	$event = null;

	if ($status == AS_POST_STATUS_QUEUED) {
		$newtype = 'R_QUEUED';
		if (!$wasqueued)
			$event = 'a_requeue'; // same event whether it was hidden or shown before

	} elseif ($status == AS_POST_STATUS_HIDDEN) {
		$newtype = 'R_HIDDEN';
		if (!$washidden) {
			$event = $wasqueued ? 'a_reject' : 'a_hide';
			if (!$wasqueued)
				$setupdated = true;
		}

		if ($item['selchildid'] == $oldreview['postid']) { // remove selected review
			as_article_set_selchildid(null, null, null, $item, null, array($oldreview['postid'] => $oldreview));
		}

	} elseif ($status == AS_POST_STATUS_NORMAL) {
		$newtype = 'R';
		if ($wasqueued)
			$event = 'a_approve';
		elseif ($washidden) {
			$event = 'a_reshow';
			$setupdated = true;
		}

	} else
		as_fatal_error('Unknown status in as_review_set_status(): ' . $status);

	as_db_post_set_type($oldreview['postid'], $newtype, $setupdated ? $userid : null, $setupdated ? as_remote_ip_address() : null, AS_UPDATE_VISIBLE);

	if ($wasqueued && ($status == AS_POST_STATUS_NORMAL) && as_opt('moderate_update_time')) { // ... for approval of a post, can set time to now instead
		if ($wasrequeued)
			as_db_post_set_updated($oldreview['postid'], null);
		else
			as_db_post_set_created($oldreview['postid'], null);
	}

	as_update_q_counts_for_a($item['postid']);
	as_db_points_update_ifuser($oldreview['userid'], array('aposts', 'aselecteds'));

	if ($wasqueued || $status == AS_POST_STATUS_QUEUED)
		as_db_queuedcount_update();

	if ($oldreview['flagcount'])
		as_db_flaggedcount_update();

	if ($item['type'] == 'P' && $status == AS_POST_STATUS_NORMAL) { // even if review visible, don't index if item is hidden or queued
		as_post_index($oldreview['postid'], 'R', $item['postid'], $oldreview['parentid'], null, $oldreview['content'],
			$oldreview['format'], as_viewer_text($oldreview['content'], $oldreview['format']), null, $oldreview['categoryid']);

		foreach ($commentsfollows as $comment) {
			if ($comment['type'] == 'C' && $comment['parentid'] == $oldreview['postid']) { // and don't index hidden/queued comments
				as_post_index($comment['postid'], $comment['type'], $item['postid'], $comment['parentid'], null, $comment['content'],
					$comment['format'], as_viewer_text($comment['content'], $comment['format']), null, $comment['categoryid']);
			}
		}
	}

	as_article_uncache($item['postid']); // remove hidden posts immediately

	$eventparams = array(
		'postid' => $oldreview['postid'],
		'parentid' => $oldreview['parentid'],
		'parent' => $item,
		'content' => $oldreview['content'],
		'format' => $oldreview['format'],
		'text' => as_viewer_text($oldreview['content'], $oldreview['format']),
		'categoryid' => $oldreview['categoryid'],
		'name' => $oldreview['name'],
	);

	if (isset($event)) {
		as_report_event($event, $userid, $handle, $cookieid, $eventparams + array(
				'oldreview' => $oldreview,
			));
	}

	if ($wasqueued && ($status == AS_POST_STATUS_NORMAL) && !$wasrequeued) {
		require_once AS_INCLUDE_DIR . 'util/string.php';

		as_report_event('a_post', $oldreview['userid'], $oldreview['handle'], $oldreview['cookieid'], $eventparams + array(
			'notify' => isset($oldreview['notify']),
			'email' => as_email_validate($oldreview['notify']) ? $oldreview['notify'] : null,
			'delayed' => $oldreview['created'],
		));
	}
}


/**
 * Permanently delete an review (application level) from the database. The review must not have any comments or
 * follow-on items. Pass the database record for the item in $item and details of the user doing this
 * in $userid, $handle and $cookieid. Handles unindexing, likes, points, cached counts and event reports.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldreview
 * @param $item
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_review_delete($oldreview, $item, $userid, $handle, $cookieid)
{
	require_once AS_INCLUDE_DIR . 'db/likes.php';

	if ($oldreview['type'] != 'R_HIDDEN')
		as_fatal_error('Tried to delete a non-hidden review');

	$useridlikes = as_db_userlike_post_get($oldreview['postid']);

	$params = array(
		'postid' => $oldreview['postid'],
		'parentid' => $oldreview['parentid'],
		'oldreview' => $oldreview,
	);

	as_report_event('a_delete_before', $userid, $handle, $cookieid, $params);

	as_post_unindex($oldreview['postid']);
	as_db_post_delete($oldreview['postid']); // also deletes any related likeds due to cascading

	if ($item['selchildid'] == $oldreview['postid']) {
		as_db_post_set_selchildid($item['postid'], null);
		as_db_points_update_ifuser($item['userid'], 'aselects');
		as_db_unselpcount_update();
	}

	as_update_q_counts_for_a($item['postid']);
	as_db_points_update_ifuser($oldreview['userid'], array('aposts', 'aselecteds', 'alikeds', 'positivelikeds', 'negativelikeds'));

	foreach ($useridlikes as $likeruserid => $like) {
		// could do this in one query like in as_db_users_recalc_points() but this will do for now - unlikely to be many likes
		as_db_points_update_ifuser($likeruserid, ($like > 0) ? 'apositivelikes' : 'anegativelikes');
	}

	as_report_event('a_delete', $userid, $handle, $cookieid, $params);
}


/**
 * Set the author (application level) of $oldreview to $userid and also pass $handle and $cookieid
 * of user. Updates points and reports events as appropriate.
 * @param $oldreview
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_review_set_userid($oldreview, $userid, $handle, $cookieid)
{
	require_once AS_INCLUDE_DIR . 'db/likes.php';

	$postid = $oldreview['postid'];

	as_db_post_set_userid($postid, $userid);
	as_db_userlike_remove_own($postid);
	as_db_post_recount_likes($postid);

	as_db_points_update_ifuser($oldreview['userid'], array('aposts', 'aselecteds', 'alikeds', 'positivelikeds', 'negativelikeds'));
	as_db_points_update_ifuser($userid, array('aposts', 'aselecteds', 'alikeds', 'apositivelikes', 'anegativelikes', 'positivelikeds', 'negativelikeds'));

	as_report_event('a_claim', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => $oldreview['parentid'],
		'oldreview' => $oldreview,
	));
}


/**
 * Change the fields of a comment (application level) to $content, $format, $notify and $name, then reindex based on
 * $text. For backwards compatibility if $name is null then the name will not be changed. Pass the comment's database
 * record before changes in $oldcomment, details of the user doing this in $userid, $handle and $cookieid, the
 * antecedent item in $item and the review's database record in $review if this is a comment on an review,
 * otherwise null. Set $remoderate to true if the item should be requeued for moderation if modified. Set $silent
 * to true to not mark the item as edited. Handles unindexing and event reports. See /as-include/app/posts.php for a
 * higher-level function which is easier to use.
 * @param $oldcomment
 * @param $content
 * @param $format
 * @param $text
 * @param $notify
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $item
 * @param $parent
 * @param $name
 * @param bool $remoderate
 * @param bool $silent
 */
function as_comment_set_content($oldcomment, $content, $format, $text, $notify, $userid, $handle, $cookieid, $item, $parent, $name = null, $remoderate = false, $silent = false)
{
	if (!isset($parent))
		$parent = $item; // for backwards compatibility with old review parameter

	as_post_unindex($oldcomment['postid']);

	$wasqueued = ($oldcomment['type'] == 'C_QUEUED');
	$contentchanged = strcmp($oldcomment['content'], $content) || strcmp($oldcomment['format'], $format);
	$setupdated = $contentchanged && (!$wasqueued) && !$silent;

	as_db_post_set_content($oldcomment['postid'], $oldcomment['title'], $content, $format, $oldcomment['tags'], $notify,
		$setupdated ? $userid : null, $setupdated ? as_remote_ip_address() : null, AS_UPDATE_CONTENT, $name);

	if ($setupdated && $remoderate) {
		as_db_post_set_type($oldcomment['postid'], 'C_QUEUED');
		as_db_ccount_update();
		as_db_queuedcount_update();
		as_db_points_update_ifuser($oldcomment['userid'], array('cposts'));

		if ($oldcomment['flagcount'])
			as_db_flaggedcount_update();

	} elseif ($oldcomment['type'] == 'C' && $item['type'] == 'P' && ($parent['type'] == 'P' || $parent['type'] == 'R')) { // all must be visible
		as_post_index($oldcomment['postid'], 'C', $item['postid'], $oldcomment['parentid'], null, $content, $format, $text, null, $oldcomment['categoryid']);
	}

	$eventparams = array(
		'postid' => $oldcomment['postid'],
		'parentid' => $oldcomment['parentid'],
		'parenttype' => $parent['basetype'],
		'parent' => $parent,
		'articleid' => $item['postid'],
		'item' => $item,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'name' => $name,
		'oldcomment' => $oldcomment,
	);

	as_report_event('c_edit', $userid, $handle, $cookieid, $eventparams + array(
		'silent' => $silent,
		'oldcontent' => $oldcomment['content'],
		'oldformat' => $oldcomment['format'],
		'contentchanged' => $contentchanged,
	));

	if ($setupdated && $remoderate)
		as_report_event('c_requeue', $userid, $handle, $cookieid, $eventparams);
}


/**
 * Convert an review to a comment (application level) and set its fields to $content, $format, $notify and $name. For
 * backwards compatibility if $name is null then the name will not be changed. Pass the review's database record before
 * changes in $oldreview, the new comment's $parentid to be, details of the user doing this in $userid, $handle and
 * $cookieid, the antecedent item's record in $item, the records for all reviews to that item in $reviews,
 * and the records for all comments on the (old) review and items following from the (old) review in
 * $commentsfollows ($commentsfollows can also contain other records which are ignored). Set $remoderate to true if the
 * item should be requeued for moderation if modified. Set $silent to true to not mark the item as edited.
 * Handles indexing (based on $text), user points, cached counts and event reports.
 * @param $oldreview
 * @param $parentid
 * @param $content
 * @param $format
 * @param $text
 * @param $notify
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $item
 * @param $reviews
 * @param $commentsfollows
 * @param $name
 * @param bool $remoderate
 * @param bool $silent
 */
function as_review_to_comment($oldreview, $parentid, $content, $format, $text, $notify, $userid, $handle, $cookieid, $item, $reviews, $commentsfollows, $name = null, $remoderate = false, $silent = false)
{
	require_once AS_INCLUDE_DIR . 'db/likes.php';

	$parent = isset($reviews[$parentid]) ? $reviews[$parentid] : $item;

	as_post_unindex($oldreview['postid']);

	$wasqueued = ($oldreview['type'] == 'R_QUEUED');
	$contentchanged = strcmp($oldreview['content'], $content) || strcmp($oldreview['format'], $format);
	$setupdated = $contentchanged && (!$wasqueued) && !$silent;

	if ($setupdated && $remoderate)
		$newtype = 'C_QUEUED';
	else
		$newtype = substr_replace($oldreview['type'], 'C', 0, 1);

	as_db_post_set_type($oldreview['postid'], $newtype, ($wasqueued || $silent) ? null : $userid,
		($wasqueued || $silent) ? null : as_remote_ip_address(), AS_UPDATE_TYPE);
	as_db_post_set_parent($oldreview['postid'], $parentid);
	as_db_post_set_content($oldreview['postid'], $oldreview['title'], $content, $format, $oldreview['tags'], $notify,
		$setupdated ? $userid : null, $setupdated ? as_remote_ip_address() : null, AS_UPDATE_CONTENT, $name);

	foreach ($commentsfollows as $commentfollow) {
		if ($commentfollow['parentid'] == $oldreview['postid']) // do same thing for comments and follows
			as_db_post_set_parent($commentfollow['postid'], $parentid);
	}

	as_update_q_counts_for_a($item['postid']);
	as_db_ccount_update();
	as_db_points_update_ifuser($oldreview['userid'], array('aposts', 'aselecteds', 'cposts', 'alikeds', 'clikeds'));

	$useridlikes = as_db_userlike_post_get($oldreview['postid']);
	foreach ($useridlikes as $likeruserid => $like) {
		// could do this in one query like in as_db_users_recalc_points() but this will do for now - unlikely to be many likes
		as_db_points_update_ifuser($likeruserid, ($like > 0) ? 'apositivelikes' : 'anegativelikes');
	}

	if ($setupdated && $remoderate) {
		as_db_queuedcount_update();

		if ($oldreview['flagcount'])
			as_db_flaggedcount_update();

	} elseif ($oldreview['type'] == 'R' && $item['type'] == 'P' && ($parent['type'] == 'P' || $parent['type'] == 'R')) // only if all fully visible
		as_post_index($oldreview['postid'], 'C', $item['postid'], $parentid, null, $content, $format, $text, null, $oldreview['categoryid']);

	if ($item['selchildid'] == $oldreview['postid']) { // remove selected review
		as_article_set_selchildid(null, null, null, $item, null, array($oldreview['postid'] => $oldreview));
	}

	$eventparams = array(
		'postid' => $oldreview['postid'],
		'parentid' => $parentid,
		'parenttype' => $parent['basetype'],
		'parent' => $parent,
		'articleid' => $item['postid'],
		'item' => $item,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'name' => $name,
		'oldreview' => $oldreview,
	);

	as_report_event('a_to_c', $userid, $handle, $cookieid, $eventparams + array(
		'silent' => $silent,
		'oldcontent' => $oldreview['content'],
		'oldformat' => $oldreview['format'],
		'contentchanged' => $contentchanged,
	));

	if ($setupdated && $remoderate) {
		// a-to-c conversion can be detected by presence of $event['oldreview'] instead of $event['oldcomment']
		as_report_event('c_requeue', $userid, $handle, $cookieid, $eventparams);
	}
}


/**
 * Set $oldcomment to hidden if $hidden is true, visible/normal if otherwise. All other parameters are as for as_comment_set_status(...)
 * @deprecated Replaced by as_comment_set_status.
 * @param $oldcomment
 * @param $hidden
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $item
 * @param $parent
 */
function as_comment_set_hidden($oldcomment, $hidden, $userid, $handle, $cookieid, $item, $parent)
{
	as_comment_set_status($oldcomment, $hidden ? AS_POST_STATUS_HIDDEN : AS_POST_STATUS_NORMAL, $userid, $handle, $cookieid, $item, $parent);
}


/**
 * Set the status (application level) of $oldcomment to $status, one of the AS_POST_STATUS_* constants above. Pass the
 * antecedent item's record in $item, details of the user doing this in $userid, $handle and $cookieid, and the
 * review's database record in $review if this is a comment on an review, otherwise null. Handles indexing, user
 * points, cached counts and event reports. See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldcomment
 * @param $status
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $item
 * @param $parent
 */
function as_comment_set_status($oldcomment, $status, $userid, $handle, $cookieid, $item, $parent)
{
	require_once AS_INCLUDE_DIR . 'app/format.php';

	if (!isset($parent))
		$parent = $item; // for backwards compatibility with old review parameter

	$washidden = ($oldcomment['type'] == 'C_HIDDEN');
	$wasqueued = ($oldcomment['type'] == 'C_QUEUED');
	$wasrequeued = $wasqueued && isset($oldcomment['updated']);

	as_post_unindex($oldcomment['postid']);

	$setupdated = false;
	$event = null;

	if ($status == AS_POST_STATUS_QUEUED) {
		$newtype = 'C_QUEUED';
		if (!$wasqueued)
			$event = 'c_requeue'; // same event whether it was hidden or shown before

	} elseif ($status == AS_POST_STATUS_HIDDEN) {
		$newtype = 'C_HIDDEN';
		if (!$washidden) {
			$event = $wasqueued ? 'c_reject' : 'c_hide';
			if (!$wasqueued)
				$setupdated = true;
		}

	} elseif ($status == AS_POST_STATUS_NORMAL) {
		$newtype = 'C';
		if ($wasqueued)
			$event = 'c_approve';
		elseif ($washidden) {
			$event = 'c_reshow';
			$setupdated = true;
		}

	} else
		as_fatal_error('Unknown status in as_comment_set_status(): ' . $status);

	as_db_post_set_type($oldcomment['postid'], $newtype, $setupdated ? $userid : null, $setupdated ? as_remote_ip_address() : null, AS_UPDATE_VISIBLE);

	if ($wasqueued && ($status == AS_POST_STATUS_NORMAL) && as_opt('moderate_update_time')) { // ... for approval of a post, can set time to now instead
		if ($wasrequeued)
			as_db_post_set_updated($oldcomment['postid'], null);
		else
			as_db_post_set_created($oldcomment['postid'], null);
	}

	as_db_ccount_update();
	as_db_points_update_ifuser($oldcomment['userid'], array('cposts'));

	if ($wasqueued || $status == AS_POST_STATUS_QUEUED)
		as_db_queuedcount_update();

	if ($oldcomment['flagcount'])
		as_db_flaggedcount_update();

	if ($item['type'] == 'P' && ($parent['type'] == 'P' || $parent['type'] == 'R') && $status == AS_POST_STATUS_NORMAL) {
		// only index if none of the things it depends on are hidden or queued
		as_post_index($oldcomment['postid'], 'C', $item['postid'], $oldcomment['parentid'], null, $oldcomment['content'],
			$oldcomment['format'], as_viewer_text($oldcomment['content'], $oldcomment['format']), null, $oldcomment['categoryid']);
	}

	as_article_uncache($item['postid']); // remove hidden posts immediately

	$eventparams = array(
		'postid' => $oldcomment['postid'],
		'parentid' => $oldcomment['parentid'],
		'parenttype' => $parent['basetype'],
		'parent' => $parent,
		'articleid' => $item['postid'],
		'item' => $item,
		'content' => $oldcomment['content'],
		'format' => $oldcomment['format'],
		'text' => as_viewer_text($oldcomment['content'], $oldcomment['format']),
		'categoryid' => $oldcomment['categoryid'],
		'name' => $oldcomment['name'],
	);

	if (isset($event)) {
		as_report_event($event, $userid, $handle, $cookieid, $eventparams + array(
			'oldcomment' => $oldcomment,
		));
	}

	if ($wasqueued && $status == AS_POST_STATUS_NORMAL && !$wasrequeued) {
		require_once AS_INCLUDE_DIR . 'db/selects.php';
		require_once AS_INCLUDE_DIR . 'util/string.php';

		$commentsfollows = as_db_single_select(as_db_full_child_posts_selectspec(null, $oldcomment['parentid']));
		$thread = array();

		foreach ($commentsfollows as $comment) {
			if ($comment['type'] == 'C' && $comment['parentid'] == $parent['postid'])
				$thread[] = $comment;
		}

		as_report_event('c_post', $oldcomment['userid'], $oldcomment['handle'], $oldcomment['cookieid'], $eventparams + array(
			'thread' => $thread,
			'notify' => isset($oldcomment['notify']),
			'email' => as_email_validate($oldcomment['notify']) ? $oldcomment['notify'] : null,
			'delayed' => $oldcomment['created'],
		));
	}
}


/**
 * Permanently delete a comment in $oldcomment (application level) from the database. Pass the database item in $item
 * and the review's database record in $review if this is a comment on an review, otherwise null. Pass details of the user
 * doing this in $userid, $handle and $cookieid. Handles unindexing, points, cached counts and event reports.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $oldcomment
 * @param $item
 * @param $parent
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_comment_delete($oldcomment, $item, $parent, $userid, $handle, $cookieid)
{
	if (!isset($parent))
		$parent = $item; // for backwards compatibility with old review parameter

	if ($oldcomment['type'] != 'C_HIDDEN')
		as_fatal_error('Tried to delete a non-hidden comment');

	$params = array(
		'postid' => $oldcomment['postid'],
		'parentid' => $oldcomment['parentid'],
		'oldcomment' => $oldcomment,
		'parenttype' => $parent['basetype'],
		'articleid' => $item['postid'],
	);

	as_report_event('c_delete_before', $userid, $handle, $cookieid, $params);

	as_post_unindex($oldcomment['postid']);
	as_db_post_delete($oldcomment['postid']);
	as_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
	as_db_ccount_update();

	as_report_event('c_delete', $userid, $handle, $cookieid, $params);
}


/**
 * Set the author (application level) of $oldcomment to $userid and also pass $handle and $cookieid
 * of user. Updates points and reports events as appropriate.
 * @param $oldcomment
 * @param $userid
 * @param $handle
 * @param $cookieid
 */
function as_comment_set_userid($oldcomment, $userid, $handle, $cookieid)
{
	require_once AS_INCLUDE_DIR . 'db/likes.php';

	$postid = $oldcomment['postid'];

	as_db_post_set_userid($postid, $userid);
	as_db_userlike_remove_own($postid);
	as_db_post_recount_likes($postid);

	as_db_points_update_ifuser($oldcomment['userid'], array('cposts'));
	as_db_points_update_ifuser($userid, array('cposts'));

	as_report_event('c_claim', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => $oldcomment['parentid'],
		'oldcomment' => $oldcomment,
	));
}
