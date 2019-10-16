<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Database-level access to likes tables


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


/**
 * Set the like for $userid on $postid to $like in the database
 * @param $postid
 * @param $userid
 * @param $like
 */
function as_db_userlike_set($postid, $userid, $like)
{
	$like = max(min(($like), 1), -1);

	as_db_query_sub(
		'INSERT INTO ^userlikes (postid, userid, lyke, flag, likecreated) VALUES (#, #, #, 0, NOW()) ON DUPLICATE KEY UPDATE lyke=#, likeupdated=NOW()',
		$postid, $userid, $like, $like
	);
}


/**
 * Get the like for $userid on $postid from the database (or NULL if none)
 * @param $postid
 * @param $userid
 * @return mixed|null
 */
function as_db_userlike_get($postid, $userid)
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT lyke FROM ^userlikes WHERE postid=# AND userid=#',
		$postid, $userid
	), true);
}


/**
 * Set the flag for $userid on $postid to $flag (true or false) in the database
 * @param $postid
 * @param $userid
 * @param $flag
 */
function as_db_userflag_set($postid, $userid, $flag)
{
	$flag = $flag ? 1 : 0;

	as_db_query_sub(
		'INSERT INTO ^userlikes (postid, userid, lyke, flag) VALUES (#, #, 0, #) ON DUPLICATE KEY UPDATE flag=#',
		$postid, $userid, $flag, $flag
	);
}


/**
 * Clear all flags for $postid in the database
 * @param $postid
 */
function as_db_userflags_clear_all($postid)
{
	as_db_query_sub(
		'UPDATE ^userlikes SET flag=0 WHERE postid=#',
		$postid
	);
}


/**
 * Recalculate the cached count of positivelikes, negativelikes and netlikes for $postid in the database
 * @param $postid
 */
function as_db_post_recount_likes($postid)
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			'UPDATE ^posts AS x, (SELECT COALESCE(SUM(GREATEST(0,lyke)),0) AS positivelikes, -COALESCE(SUM(LEAST(0,lyke)),0) AS negativelikes FROM ^userlikes WHERE postid=#) AS a SET x.positivelikes=a.positivelikes, x.negativelikes=a.negativelikes, x.netlikes=a.positivelikes-a.negativelikes WHERE x.postid=#',
			$postid, $postid
		);
	}
}


/**
 * Recalculate the cached count of flags for $postid in the database
 * @param $postid
 */
function as_db_post_recount_flags($postid)
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			'UPDATE ^posts AS x, (SELECT COALESCE(SUM(IF(flag, 1, 0)),0) AS flagcount FROM ^userlikes WHERE postid=#) AS a SET x.flagcount=a.flagcount WHERE x.postid=#',
			$postid, $postid
		);
	}
}


/**
 * Returns all non-zero likes on post $postid from the database as an array of [userid] => [like]
 * @param $postid
 * @return array
 */
function as_db_userlike_post_get($postid)
{
	return as_db_read_all_assoc(as_db_query_sub(
		'SELECT userid, lyke FROM ^userlikes WHERE postid=# AND lyke!=0',
		$postid
	), 'userid', 'lyke');
}


/**
 * Returns all the postids from the database for posts that $userid has liked on or flagged
 * @param $userid
 * @return array
 */
function as_db_userlikeflag_user_get($userid)
{
	return as_db_read_all_values(as_db_query_sub(
		'SELECT postid FROM ^userlikes WHERE userid=# AND (lyke!=0 OR flag!=0)',
		$userid
	));
}


/**
 * Return information about all the non-zero likes and/or flags on the posts in postids, including user handles for internal user management
 * @param $postids
 * @return array
 */
function as_db_userlikeflag_posts_get($postids)
{
	if (AS_FINAL_EXTERNAL_USERS) {
		return as_db_read_all_assoc(as_db_query_sub(
			'SELECT postid, userid, lyke, flag, likecreated, likeupdated FROM ^userlikes WHERE postid IN (#) AND (lyke!=0 OR flag!=0)',
			$postids
		));
	} else {
		return as_db_read_all_assoc(as_db_query_sub(
			'SELECT postid, handle, lyke, flag, likecreated, likeupdated FROM ^userlikes LEFT JOIN ^users ON ^userlikes.userid=^users.userid WHERE postid IN (#) AND (lyke!=0 OR flag!=0)',
			$postids
		));
	}
}


/**
 * Remove all likes assigned to a post that had been cast by the owner of the post.
 *
 * @param int $postid The post ID from which the owner's likes will be removed.
 */
function as_db_userlike_remove_own($postid)
{
	as_db_query_sub(
		'DELETE uv FROM ^userlikes uv JOIN ^posts p ON uv.postid=p.postid AND uv.userid=p.userid WHERE uv.postid=#', $postid
	);
}
