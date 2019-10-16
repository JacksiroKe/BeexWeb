<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Higher-level functions to create and manipulate posts


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

require_once AS_INCLUDE_DIR . 'as-db.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/post-create.php';
require_once AS_INCLUDE_DIR . 'app/post-update.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'util/string.php';


/**
 * Create a new post in the database, and return its postid.
 *
 * Set $type to 'P' for a new item, 'R' for an review, or 'C' for a comment. You can also use 'P_QUEUED',
 * 'R_QUEUED' or 'C_QUEUED' to create a post which is queued for moderator approval. For items, set $parentid to
 * the postid of the review to which the item is related, or null if (as in most cases) the item is not related
 * to an review. For reviews, set $parentid to the postid of the item being reviewed. For comments, set $parentid
 * to the postid of the item or review to which the comment relates. The $content and $format parameters go
 * together - if $format is '' then $content should be in plain UTF-8 text, and if $format is 'html' then $content
 * should be in UTF-8 HTML. Other values of $format may be allowed if an appropriate viewer module is installed. The
 * $title, $categoryid and $tags parameters are only relevant when creating a item - $tags can either be an array
 * of tags, or a string of tags separated by commas. The new post will be assigned to $userid if it is not null,
 * otherwise it will be by a non-user. If $notify is true then the author will be sent notifications relating to the
 * post - either to $email if it is specified and valid, or to the current email address of $userid if $email is '@'.
 * If you're creating a item, the $extravalue parameter will be set as the custom extra field, if not null. For all
 * post types you can specify the $name of the post's author, which is relevant if the $userid is null.
 * @param $type
 * @param $parentid
 * @param $title
 * @param $content
 * @param string $format
 * @param $categoryid
 * @param $tags
 * @param $userid
 * @param $notify
 * @param $email
 * @param $extravalue
 * @param $name
 * @return mixed
 */
function as_post_create($type, $parentid, $title, $content, $format = '', $categoryid = null, $tags = null, $userid = null,
	$notify = null, $email = null, $extravalue = null, $name = null)
{
	$handle = as_userid_to_handle($userid);
	$text = as_post_content_to_text($content, $format);

	switch ($type) {
		case 'P':
		case 'P_QUEUED':
			$followreview = isset($parentid) ? as_post_get_full($parentid, 'R') : null;
			$tagstring = as_post_tags_to_tagstring($tags);
			$postid = as_article_create($followreview, $userid, $handle, null, $title, $content, $format, $text, $tagstring,
				$notify, $email, $categoryid, $extravalue, $type == 'P_QUEUED', $name);
			break;

		case 'R':
		case 'R_QUEUED':
			$item = as_post_get_full($parentid, 'P');
			$postid = as_review_create($userid, $handle, null, $content, $format, $text, $notify, $email, $item, $type == 'R_QUEUED', $name);
			break;

		case 'C':
		case 'C_QUEUED':
			$parent = as_post_get_full($parentid, 'QA');
			$commentsfollows = as_db_single_select(as_db_full_child_posts_selectspec(null, $parentid));
			$item = as_post_parent_to_article($parent);
			$postid = as_comment_create($userid, $handle, null, $content, $format, $text, $notify, $email, $item, $parent, $commentsfollows, $type == 'C_QUEUED', $name);
			break;

		default:
			as_fatal_error('Post type not recognized: ' . $type);
			break;
	}

	return $postid;
}


/**
 * Change the data stored for post $postid based on any of the $title, $content, $format, $tags, $notify, $email,
 * $extravalue and $name parameters passed which are not null. The meaning of these parameters is the same as for
 * as_post_create() above. Pass the identify of the user making this change in $byuserid (or null for silent).
 * @param $postid
 * @param $title
 * @param $content
 * @param $format
 * @param $tags
 * @param $notify
 * @param $email
 * @param $byuserid
 * @param $extravalue
 * @param $name
 */
function as_post_set_content($postid, $title, $content, $format = null, $tags = null, $notify = null, $email = null, $byuserid = null, $extravalue = null, $name = null)
{
	$oldpost = as_post_get_full($postid, 'QAC');

	if (!isset($title))
		$title = $oldpost['title'];

	if (!isset($content))
		$content = $oldpost['content'];

	if (!isset($format))
		$format = $oldpost['format'];

	if (!isset($tags))
		$tags = as_tagstring_to_tags($oldpost['tags']);

	if (isset($notify) || isset($email))
		$setnotify = as_combine_notify_email($oldpost['userid'], isset($notify) ? $notify : isset($oldpost['notify']),
			isset($email) ? $email : $oldpost['notify']);
	else
		$setnotify = $oldpost['notify'];

	$byhandle = as_userid_to_handle($byuserid);

	$text = as_post_content_to_text($content, $format);

	switch ($oldpost['basetype']) {
		case 'P':
			$tagstring = as_post_tags_to_tagstring($tags);
			as_article_set_content($oldpost, $title, $content, $format, $text, $tagstring, $setnotify, $byuserid, $byhandle, null, $extravalue, $name);
			break;

		case 'R':
			$item = as_post_get_full($oldpost['parentid'], 'P');
			as_review_set_content($oldpost, $content, $format, $text, $setnotify, $byuserid, $byhandle, null, $item, $name);
			break;

		case 'C':
			$parent = as_post_get_full($oldpost['parentid'], 'QA');
			$item = as_post_parent_to_article($parent);
			as_comment_set_content($oldpost, $content, $format, $text, $setnotify, $byuserid, $byhandle, null, $item, $parent, $name);
			break;
	}
}


/**
 * Change the category of $postid to $categoryid. The category of all related posts (shown together on the same
 * item page) will also be changed. Pass the identify of the user making this change in $byuserid (or null for an
 * anonymous change).
 * @param $postid
 * @param $categoryid
 * @param $byuserid
 */
function as_post_set_category($postid, $categoryid, $byuserid = null)
{
	$oldpost = as_post_get_full($postid, 'QAC');

	if ($oldpost['basetype'] == 'P') {
		$byhandle = as_userid_to_handle($byuserid);
		$reviews = as_post_get_article_reviews($postid);
		$commentsfollows = as_post_get_article_commentsfollows($postid);
		$closepost = as_post_get_article_closepost($postid);
		as_article_set_category($oldpost, $categoryid, $byuserid, $byhandle, null, $reviews, $commentsfollows, $closepost);

	} else
		as_post_set_category($oldpost['parentid'], $categoryid, $byuserid); // keep looking until we find the parent item
}


/**
 * Set the selected best review of $articleid to $reviewid (or to none if $reviewid is null). Pass the identify of the
 * user in $byuserid (or null for an anonymous change).
 * @param $articleid
 * @param $reviewid
 * @param $byuserid
 */
function as_post_set_selchildid($articleid, $reviewid, $byuserid = null)
{
	$oldarticle = as_post_get_full($articleid, 'P');
	$byhandle = as_userid_to_handle($byuserid);
	$reviews = as_post_get_article_reviews($articleid);

	if (isset($reviewid) && !isset($reviews[$reviewid]))
		as_fatal_error('Review ID could not be found: ' . $reviewid);

	as_article_set_selchildid($byuserid, $byhandle, null, $oldarticle, $reviewid, $reviews);
}


/**
 * Close $articleid if $closed is true, otherwise reopen it. If $closed is true, pass either the $originalpostid of
 * the item that it is a duplicate of, or a $note to explain why it's closed. Pass the identifier of the user in
 * $byuserid (or null for an anonymous change).
 * @param $articleid
 * @param bool $closed
 * @param $originalpostid
 * @param $note
 * @param $byuserid
 */
function as_post_set_closed($articleid, $closed = true, $originalpostid = null, $note = null, $byuserid = null)
{
	$oldarticle = as_post_get_full($articleid, 'P');
	$oldclosepost = as_post_get_article_closepost($articleid);
	$byhandle = as_userid_to_handle($byuserid);

	if ($closed) {
		if (isset($originalpostid))
			as_article_close_duplicate($oldarticle, $oldclosepost, $originalpostid, $byuserid, $byhandle, null);
		elseif (isset($note))
			as_article_close_other($oldarticle, $oldclosepost, $note, $byuserid, $byhandle, null);
		else
			as_fatal_error('Article must be closed as a duplicate or with a note');

	} else
		as_article_close_clear($oldarticle, $oldclosepost, $byuserid, $byhandle, null);
}

/**
 * Return whether the given item is closed. This check takes into account the do_close_on_select option which
 * considers items with a selected review as closed.
 * @since 1.8.2
 * @param array $item
 * @return bool
 */
function as_post_is_closed(array $item)
{
	return isset($item['closedbyid']) || (isset($item['selchildid']) && as_opt('do_close_on_select'));
}


/**
 * Hide $postid if $hidden is true, otherwise show the post. Pass the identify of the user making this change in
 * $byuserid (or null for a silent change).
 * @deprecated Replaced by as_post_set_status.
 * @param $postid
 * @param bool $hidden
 * @param $byuserid
 */
function as_post_set_hidden($postid, $hidden = true, $byuserid = null)
{
	as_post_set_status($postid, $hidden ? AS_POST_STATUS_HIDDEN : AS_POST_STATUS_NORMAL, $byuserid);
}


/**
 * Change the status of $postid to $status, which should be one of the AS_POST_STATUS_* constants defined in
 * /as-include/app/post-update.php. Pass the identify of the user making this change in $byuserid (or null for a silent change).
 * @param $postid
 * @param $status
 * @param $byuserid
 */
function as_post_set_status($postid, $status, $byuserid = null)
{
	$oldpost = as_post_get_full($postid, 'QAC');
	$byhandle = as_userid_to_handle($byuserid);

	switch ($oldpost['basetype']) {
		case 'P':
			$reviews = as_post_get_article_reviews($postid);
			$commentsfollows = as_post_get_article_commentsfollows($postid);
			$closepost = as_post_get_article_closepost($postid);
			as_article_set_status($oldpost, $status, $byuserid, $byhandle, null, $reviews, $commentsfollows, $closepost);
			break;

		case 'R':
			$item = as_post_get_full($oldpost['parentid'], 'P');
			$commentsfollows = as_post_get_review_commentsfollows($postid);
			as_review_set_status($oldpost, $status, $byuserid, $byhandle, null, $item, $commentsfollows);
			break;

		case 'C':
			$parent = as_post_get_full($oldpost['parentid'], 'QA');
			$item = as_post_parent_to_article($parent);
			as_comment_set_status($oldpost, $status, $byuserid, $byhandle, null, $item, $parent);
			break;
	}
}


/**
 * Set the created date of $postid to $created, which is a unix timestamp.
 * @param $postid
 * @param $created
 */
function as_post_set_created($postid, $created)
{
	$oldpost = as_post_get_full($postid);

	as_db_post_set_created($postid, $created);

	switch ($oldpost['basetype']) {
		case 'P':
			as_db_hotness_update($postid);
			break;

		case 'R':
			as_db_hotness_update($oldpost['parentid']);
			break;
	}
}


/**
 * Delete $postid from the database, hiding it first if appropriate.
 * @param $postid
 */
function as_post_delete($postid)
{
	$oldpost = as_post_get_full($postid, 'QAC');

	if (!$oldpost['hidden']) {
		as_post_set_status($postid, AS_POST_STATUS_HIDDEN, null);
		$oldpost = as_post_get_full($postid, 'QAC');
	}

	switch ($oldpost['basetype']) {
		case 'P':
			$reviews = as_post_get_article_reviews($postid);
			$commentsfollows = as_post_get_article_commentsfollows($postid);
			$closepost = as_post_get_article_closepost($postid);

			if (count($reviews) || count($commentsfollows))
				as_fatal_error('Could not delete item ID due to dependents: ' . $postid);

			as_article_delete($oldpost, null, null, null, $closepost);
			break;

		case 'R':
			$item = as_post_get_full($oldpost['parentid'], 'P');
			$commentsfollows = as_post_get_review_commentsfollows($postid);

			if (count($commentsfollows))
				as_fatal_error('Could not delete review ID due to dependents: ' . $postid);

			as_review_delete($oldpost, $item, null, null, null);
			break;

		case 'C':
			$parent = as_post_get_full($oldpost['parentid'], 'QA');
			$item = as_post_parent_to_article($parent);
			as_comment_delete($oldpost, $item, $parent, null, null, null);
			break;
	}
}


/**
 * Return the full information from the database for $postid in an array.
 * @param $postid
 * @param $requiredbasetypes
 * @return array|mixed
 */
function as_post_get_full($postid, $requiredbasetypes = null)
{
	$post = as_db_single_select(as_db_full_post_selectspec(null, $postid));

	if (!is_array($post))
		as_fatal_error('Post ID could not be found: ' . $postid);

	if (isset($requiredbasetypes) && !is_numeric(strpos($requiredbasetypes, $post['basetype'])))
		as_fatal_error('Post of wrong type: ' . $post['basetype']);

	return $post;
}


/**
 * Return the handle corresponding to $userid, unless it is null in which case return null.
 *
 * @deprecated Deprecated from 1.7; use `as_userid_to_handle($userid)` instead.
 * @param $userid
 * @return mixed|null
 */
function as_post_userid_to_handle($userid)
{
	return as_userid_to_handle($userid);
}


/**
 * Return the textual rendition of $content in $format (used for indexing).
 * @param $content
 * @param $format
 * @return string
 */
function as_post_content_to_text($content, $format)
{
	$viewer = as_load_viewer($content, $format);

	if (!isset($viewer))
		as_fatal_error('Content could not be parsed in format: ' . $format);

	return $viewer->get_text($content, $format, array());
}


/**
 * Return tagstring to store in the database based on $tags as an array or a comma-separated string.
 * @param $tags
 * @return mixed|string
 */
function as_post_tags_to_tagstring($tags)
{
	if (is_array($tags))
		$tags = implode(',', $tags);

	return as_tags_to_tagstring(array_unique(preg_split('/\s*,\s*/', as_strtolower(strtr($tags, '/', ' ')), -1, PREG_SPLIT_NO_EMPTY)));
}


/**
 * Return the full database records for all reviews to item $articleid
 * @param $articleid
 * @return array
 */
function as_post_get_article_reviews($articleid)
{
	$reviews = array();

	$childposts = as_db_single_select(as_db_full_child_posts_selectspec(null, $articleid));

	foreach ($childposts as $postid => $post) {
		if ($post['basetype'] == 'R')
			$reviews[$postid] = $post;
	}

	return $reviews;
}


/**
 * Return the full database records for all comments or follow-on items for item $articleid or its reviews
 * @param $articleid
 * @return array
 */
function as_post_get_article_commentsfollows($articleid)
{
	$commentsfollows = array();

	list($childposts, $achildposts) = as_db_multi_select(array(
		as_db_full_child_posts_selectspec(null, $articleid),
		as_db_full_a_child_posts_selectspec(null, $articleid),
	));

	foreach ($childposts as $postid => $post) {
		if ($post['basetype'] == 'C')
			$commentsfollows[$postid] = $post;
	}

	foreach ($achildposts as $postid => $post) {
		if ($post['basetype'] == 'P' || $post['basetype'] == 'C')
			$commentsfollows[$postid] = $post;
	}

	return $commentsfollows;
}


/**
 * Return the full database record for the post which closed $articleid, if there is any
 * @param $articleid
 * @return array|mixed
 */
function as_post_get_article_closepost($articleid)
{
	return as_db_single_select(as_db_post_close_post_selectspec($articleid));
}


/**
 * Return the full database records for all comments or follow-on items for review $reviewid
 * @param $reviewid
 * @return array
 */
function as_post_get_review_commentsfollows($reviewid)
{
	$commentsfollows = array();

	$childposts = as_db_single_select(as_db_full_child_posts_selectspec(null, $reviewid));

	foreach ($childposts as $postid => $post) {
		if ($post['basetype'] == 'P' || $post['basetype'] == 'C')
			$commentsfollows[$postid] = $post;
	}

	return $commentsfollows;
}


/**
 * Return $parent if it's the database record for a item, otherwise return the database record for its parent
 * @param $parent
 * @return array|mixed
 */
function as_post_parent_to_article($parent)
{
	if ($parent['basetype'] == 'P')
		$item = $parent;
	else
		$item = as_post_get_full($parent['parentid'], 'P');

	return $item;
}
