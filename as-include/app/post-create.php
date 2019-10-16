<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Creating items, reviews and comments (application level)


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

require_once AS_INCLUDE_DIR . 'db/maxima.php';
require_once AS_INCLUDE_DIR . 'db/post-create.php';
require_once AS_INCLUDE_DIR . 'db/points.php';
require_once AS_INCLUDE_DIR . 'db/hotness.php';
require_once AS_INCLUDE_DIR . 'util/string.php';


/**
 * Return value to store in database combining $notify and $email values entered by user $userid (or null for anonymous)
 * @param $userid
 * @param $notify
 * @param $email
 * @return null|string
 */
function as_combine_notify_email($userid, $notify, $email)
{
	return $notify ? (empty($email) ? (isset($userid) ? '@' : null) : $email) : null;
}


/**
 * Add a item (application level) - create record, update appropriate counts, index it, send notifications.
 * If item is follow-on from an review, $followreview should contain review database record, otherwise null.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $followreview
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $title
 * @param $content
 * @param $format
 * @param $text
 * @param $tagstring
 * @param $notify
 * @param $email
 * @param $categoryid
 * @param $extravalue
 * @param bool $queued
 * @param $name
 * @return mixed
 */
function as_item_create($followreview, $userid, $handle, $cookieid, $posticon, $volume, $weight, $buyprice, $saleprice, $state, $color, $texture, $quantity, $manufacturer, $content, $format, $text, $email, $categoryid = null, $queued = false, $name = null)
{
	require_once AS_INCLUDE_DIR . 'db/selects.php';

	$postid = as_db_item_create($queued ? 'P_QUEUED' : 'P', @$followreview['postid'], $userid, isset($userid) ? null : $cookieid, as_remote_ip_address(), $posticon, $volume, $weight, $buyprice, $saleprice, $state, $color, $texture, $quantity, $manufacturer, $content, $format, $categoryid, isset($userid) ? null : $name);
	$title = 'mj-item';
	
	if (isset($extravalue)) {
		require_once AS_INCLUDE_DIR . 'db/metas.php';
		as_db_postmeta_set($postid, 'as_q_extra', $extravalue);
	}

	as_db_posts_calc_category_path($postid);
	as_db_hotness_update($postid);

	if ($queued) {
		as_db_queuedcount_update();

	} else {
		as_post_index($postid, 'P', $postid, @$followreview['postid'], $title, $content, $format, $text, 'mjengo-king', $categoryid);
		as_update_item_counts($postid, $categoryid, (+ $quantity));
		as_db_points_update_ifuser($userid, 'qposts');
	}

	as_report_event($queued ? 'q_queue' : 'q_post', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => @$followreview['postid'],
		'parent' => $followreview,
		'title' => $title,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'categoryid' => $categoryid,
		//'extra' => $extravalue,
		'name' => $name,
		'notify' => $email,
		'email' => $email,
	));

	return $postid;
}

function as_order_create($userid, $handle, $cookieid, $postid, $categoryid, $quantity, $address)
{
	require_once AS_INCLUDE_DIR . 'db/selects.php';
	require_once AS_INCLUDE_DIR . 'db/recalc.php';

	$orderid = as_db_order_create($userid, $cookieid, as_remote_ip_address(), $postid, $quantity, $address);
	
	as_db_item_count_update($postid, (-$quantity));
	as_update_item_counts($postid, $categoryid, (- $quantity));
	
	return $orderid;
}

function as_order_createxxx($userid, $handle, $cookieid, $postid, $categoryid, $quantity, $address)
{
	require_once AS_INCLUDE_DIR . 'db/selects.php';
	require_once AS_INCLUDE_DIR . 'db/recalc.php';

	$orderid = as_db_order_create($userid, $cookieid, as_remote_ip_address(), $postid, $quantity, $address);
	$title = 'mj-order';
	
	if (isset($extravalue)) {
		require_once AS_INCLUDE_DIR . 'db/metas.php';
		as_db_postmeta_set($postid, 'as_q_extra', $extravalue);
	}

	as_db_posts_calc_category_path($postid);
	as_db_hotness_update($postid);
	
	//as_post_index($postid, 'P', $postid, @$followreview['postid'], $title, $content, $format, $text, 'mjengo-king', $categoryid);
	as_db_stock_count_update($postid, $quantity);
	as_update_item_counts($postid, $categoryid, (- $quantity));
	//as_db_points_update_ifuser($userid, 'qposts');

	/*as_report_event('mj_order', $userid, $handle, $cookieid, array(
		'orderid' => $orderid,
		'title' => $title,
		'name' => $name,
		'notify' => $email,
		'email' => $email,
	));*/

	return $postid;
}
/**
 * Perform various common cached count updating operations to reflect changes in the item whose id is $postid
 * @param $postid
 */
function as_update_item_counts($postid, $categoryid, $quantity)
{
	require_once AS_INCLUDE_DIR . 'db/recalc.php';
	
	if (isset($postid)) // post might no longer exist
		as_db_category_path_pcount_update(as_db_post_get_category_path($postid));

	as_db_pcount_update();
	as_db_unapcount_update();
	as_db_unselpcount_update();
	as_db_unupapcount_update();
	as_db_tagcount_update();
	
	as_db_stock_count_update($categoryid, $quantity);
}

/**
 * Return an array containing the elements of $inarray whose key is in $keys
 * @param $inarray
 * @param $keys
 * @return array
 */
function as_array_filter_by_keys($inarray, $keys)
{
	$outarray = array();

	foreach ($keys as $key) {
		if (isset($inarray[$key]))
			$outarray[$key] = $inarray[$key];
	}

	return $outarray;
}


/**
 * Suspend the indexing (and unindexing) of posts via as_post_index(...) and as_post_unindex(...)
 * if $suspend is true, otherwise reinstate it. A counter is kept to allow multiple calls.
 * @param bool $suspend
 */
function as_suspend_post_indexing($suspend = true)
{
	global $as_post_indexing_suspended;

	$as_post_indexing_suspended += ($suspend ? 1 : -1);
}


/**
 * Add post $postid (which comes under $articleid) of $type (Q/A/C) to the database index, with $title, $text,
 * $tagstring and $categoryid. Calls through to all installed search modules.
 * @param $postid
 * @param $type
 * @param $articleid
 * @param $parentid
 * @param $title
 * @param $content
 * @param $format
 * @param $text
 * @param $tagstring
 * @param $categoryid
 */
function as_post_index($postid, $type, $articleid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid)
{
	global $as_post_indexing_suspended;

	if ($as_post_indexing_suspended > 0)
		return;

	// Send through to any search modules for indexing

	$searches = as_load_modules_with('search', 'index_post');
	foreach ($searches as $search)
		$search->index_post($postid, $type, $articleid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid);
}


/**
 * Add an review (application level) - create record, update appropriate counts, index it, send notifications.
 * $item should contain database record for the item this is an review to.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $content
 * @param $format
 * @param $text
 * @param $notify
 * @param $email
 * @param $item
 * @param bool $queued
 * @param $name
 * @return mixed
 */
function as_review_create($userid, $handle, $cookieid, $content, $format, $text, $notify, $email, $item, $queued = false, $name = null)
{
	$postid = as_db_post_create($queued ? 'R_QUEUED' : 'R', $item['postid'], $userid, isset($userid) ? null : $cookieid,
		as_remote_ip_address(), null, $content, $format, null, as_combine_notify_email($userid, $notify, $email),
		$item['categoryid'], isset($userid) ? null : $name);

	as_db_posts_calc_category_path($postid);

	if ($queued) {
		as_db_queuedcount_update();

	} else {
		if ($item['type'] == 'P') // don't index review if parent item is hidden or queued
			as_post_index($postid, 'R', $item['postid'], $item['postid'], null, $content, $format, $text, null, $item['categoryid']);

		as_update_q_counts_for_a($item['postid']);
		as_db_points_update_ifuser($userid, 'aposts');
	}

	as_report_event($queued ? 'a_queue' : 'a_post', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => $item['postid'],
		'parent' => $item,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'categoryid' => $item['categoryid'],
		'name' => $name,
		'notify' => $notify,
		'email' => $email,
	));

	return $postid;
}


/**
 * Perform various common cached count updating operations to reflect changes in an review of item $articleid
 * @param $articleid
 */
function as_update_q_counts_for_a($articleid)
{
	as_db_post_rcount_update($articleid);
	as_db_hotness_update($articleid);
	as_db_rcount_update();
	as_db_unapcount_update();
	as_db_unupapcount_update();
}


/**
 * Add a comment (application level) - create record, update appropriate counts, index it, send notifications.
 * $item should contain database record for the item this is part of (as direct or comment on Q's review).
 * If this is a comment on an review, $review should contain database record for the review, otherwise null.
 * $commentsfollows should contain database records for all previous comments on the same item or review,
 * but it can also contain other records that are ignored.
 * See /as-include/app/posts.php for a higher-level function which is easier to use.
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $content
 * @param $format
 * @param $text
 * @param $notify
 * @param $email
 * @param $item
 * @param $parent
 * @param $commentsfollows
 * @param bool $queued
 * @param $name
 * @return mixed
 */
function as_comment_create($userid, $handle, $cookieid, $content, $format, $text, $notify, $email, $item, $parent, $commentsfollows, $queued = false, $name = null)
{
	require_once AS_INCLUDE_DIR . 'app/emails.php';
	require_once AS_INCLUDE_DIR . 'app/options.php';
	require_once AS_INCLUDE_DIR . 'app/format.php';
	require_once AS_INCLUDE_DIR . 'util/string.php';

	if (!isset($parent))
		$parent = $item; // for backwards compatibility with old review parameter

	$postid = as_db_post_create($queued ? 'C_QUEUED' : 'C', $parent['postid'], $userid, isset($userid) ? null : $cookieid,
		as_remote_ip_address(), null, $content, $format, null, as_combine_notify_email($userid, $notify, $email),
		$item['categoryid'], isset($userid) ? null : $name);

	as_db_posts_calc_category_path($postid);

	if ($queued) {
		as_db_queuedcount_update();

	} else {
		if ($item['type'] == 'P' && ($parent['type'] == 'P' || $parent['type'] == 'R')) { // only index if antecedents fully visible
			as_post_index($postid, 'C', $item['postid'], $parent['postid'], null, $content, $format, $text, null, $item['categoryid']);
		}

		as_db_points_update_ifuser($userid, 'cposts');
		as_db_ccount_update();
	}

	$thread = array();

	foreach ($commentsfollows as $comment) {
		if ($comment['type'] == 'C' && $comment['parentid'] == $parent['postid']) // find just those for this parent, fully visible
			$thread[] = $comment;
	}

	as_report_event($queued ? 'c_queue' : 'c_post', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => $parent['postid'],
		'parenttype' => $parent['basetype'],
		'parent' => $parent,
		'articleid' => $item['postid'],
		'item' => $item,
		'thread' => $thread,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'categoryid' => $item['categoryid'],
		'name' => $name,
		'notify' => $notify,
		'email' => $email,
	));

	return $postid;
}
