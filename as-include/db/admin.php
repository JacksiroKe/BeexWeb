<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Database access functions which are specific to the admin center


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
 * Return the current version of MySQL
 */
function as_db_mysql_version()
{
	return as_db_read_one_value(as_db_query_raw('SELECT VERSION()'));
}


/**
 * Return the total size in bytes of all relevant tables in the APS database
 */
function as_db_table_size()
{
	if (defined('AS_MYSQL_USERS_PREFIX')) { // check if one of the prefixes is a prefix itself of the other
		if (stripos(AS_MYSQL_USERS_PREFIX, AS_MYSQL_TABLE_PREFIX) === 0)
			$prefixes = array(AS_MYSQL_TABLE_PREFIX);
		elseif (stripos(AS_MYSQL_TABLE_PREFIX, AS_MYSQL_USERS_PREFIX) === 0)
			$prefixes = array(AS_MYSQL_USERS_PREFIX);
		else
			$prefixes = array(AS_MYSQL_TABLE_PREFIX, AS_MYSQL_USERS_PREFIX);

	} else
		$prefixes = array(AS_MYSQL_TABLE_PREFIX);

	$size = 0;
	foreach ($prefixes as $prefix) {
		$statuses = as_db_read_all_assoc(as_db_query_raw(
			"SHOW TABLE STATUS LIKE '" . $prefix . "%'"
		));

		foreach ($statuses as $status)
			$size += $status['Data_length'] + $status['Index_length'];
	}

	return $size;
}


/**
 * Return a count of the number of posts of $type in database.
 * Set $fromuser to true to only count non-anonymous posts, false to only count anonymous posts
 * @param $type
 * @param $fromuser
 * @return mixed|null
 */
function as_db_count_posts($type = null, $fromuser = null)
{
	$wheresql = '';

	if (isset($type))
		$wheresql .= ' WHERE type=' . as_db_argument_to_mysql($type, true);

	if (isset($fromuser))
		$wheresql .= (strlen($wheresql) ? ' AND' : ' WHERE') . ' userid ' . ($fromuser ? 'IS NOT' : 'IS') . ' NULL';

	return as_db_read_one_value(as_db_query_sub(
		'SELECT COUNT(*) FROM ^posts' . $wheresql
	));
}


/**
 * Return number of signuped users in database.
 */
function as_db_count_users()
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT COUNT(*) FROM ^users'
	));
}


/**
 * Return number of active users in database $table
 * @param $table
 * @return mixed|null
 */
function as_db_count_active_users($table)
{
	switch ($table) {
		case 'posts':
		case 'userlikes':
		case 'userpoints':
			break;

		default:
			as_fatal_error('as_db_count_active_users() called for unknown table');
			break;
	}

	return as_db_read_one_value(as_db_query_sub(
		'SELECT COUNT(DISTINCT(userid)) FROM ^' . $table
	));
}


/**
 * Return number of categories in the database
 */
function as_db_count_categories()
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT COUNT(*) FROM ^categories'
	));
}


/**
 * Return number of items in the database in $categoryid exactly, and not one of its subcategories
 * @param $categoryid
 * @return mixed|null
 */
function as_db_count_categoryid_qs($categoryid)
{
	return as_db_read_one_value(as_db_query_sub(
		"SELECT COUNT(*) FROM ^posts WHERE categoryid<=># AND type='P'",
		$categoryid
	));
}


/**
 * Return list of postids of visible or queued posts by $userid
 * @param $userid
 * @return array
 */
function as_db_get_user_visible_postids($userid)
{
	return as_db_read_all_values(as_db_query_sub(
		"SELECT postid FROM ^posts WHERE userid=# AND type IN ('P', 'R', 'C', 'P_QUEUED', 'R_QUEUED', 'C_QUEUED')",
		$userid
	));
}


/**
 * Return list of postids of visible or queued posts from $ip address
 * @param $ip
 * @return array
 */
function as_db_get_ip_visible_postids($ip)
{
	return as_db_read_all_values(as_db_query_sub(
		"SELECT postid FROM ^posts WHERE createip=UNHEX($) AND type IN ('P', 'R', 'C', 'P_QUEUED', 'R_QUEUED', 'C_QUEUED')",
		bin2hex(@inet_pton($ip))
	));
}


/**
 * Return an array whose keys contain the $postids which exist, and whose elements contain the number of other posts depending on each one
 * @param $postids
 * @return array
 */
function as_db_postids_count_dependents($postids)
{
	if (count($postids))
		return as_db_read_all_assoc(as_db_query_sub(
			"SELECT postid, COALESCE(childcount, 0) AS count FROM ^posts LEFT JOIN (SELECT parentid, COUNT(*) AS childcount FROM ^posts WHERE parentid IN (#) AND LEFT(type, 1) IN ('R', 'C') GROUP BY parentid) x ON postid=x.parentid WHERE postid IN (#)",
			$postids, $postids
		), 'postid', 'count');
	else
		return array();
}


/**
 * Return an array of the (up to) $count most recently created users who are awaiting approval and have not been blocked.
 * The array element for each user includes a 'profile' key whose value is an array of non-empty profile fields of the user.
 * @param $count
 * @return array
 */
function as_db_get_unapproved_users($count)
{
	$results = as_db_read_all_assoc(as_db_query_sub(
		"SELECT ^users.userid, UNIX_TIMESTAMP(created) AS created, createip, email, handle, flags, title, content FROM ^users LEFT JOIN ^userprofile ON ^users.userid=^userprofile.userid AND LENGTH(content)>0 WHERE level<# AND NOT (flags&#) ORDER BY created DESC LIMIT #",
		AS_USER_LEVEL_APPROVED, AS_USER_FLAGS_USER_BLOCKED, $count
	));

	$users = array();

	foreach ($results as $result) {
		$userid = $result['userid'];

		if (!isset($users[$userid])) {
			$users[$result['userid']] = $result;
			$users[$result['userid']]['profile'] = array();
			unset($users[$userid]['title']);
			unset($users[$userid]['content']);
		}

		if (isset($result['title']) && isset($result['content']))
			$users[$userid]['profile'][$result['title']] = $result['content'];
	}

	return $users;
}


/**
 * Return whether there are any blobs whose content has been stored as a file on disk
 */
function as_db_has_blobs_on_disk()
{
	return as_db_read_one_value(as_db_query_sub('SELECT blobid FROM ^blobs WHERE content IS NULL LIMIT 1'), true) != null;
}


/**
 * Return whether there are any blobs whose content has been stored in the database
 */
function as_db_has_blobs_in_db()
{
	return as_db_read_one_value(as_db_query_sub('SELECT blobid FROM ^blobs WHERE content IS NOT NULL LIMIT 1'), true) != null;
}


/**
 * Return the maximum position of the categories with $parentid
 * @param $parentid
 * @return mixed|null
 */
function as_db_category_last_pos($parentid)
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT COALESCE(MAX(position), 0) FROM ^categories WHERE parentid<=>#',
		$parentid
	));
}


/**
 * Return how many levels of subcategory there are below $categoryid
 * @param $categoryid
 * @return int
 */
function as_db_category_child_depth($categoryid)
{
	// This is potentially a very slow query since it counts all the multi-generational offspring of a particular category
	// But it's only used for admin purposes when moving a category around so I don't think it's worth making more efficient
	// (Incidentally, this could be done by keeping a count for every category of how many generations of offspring it has.)

	$result = as_db_read_one_assoc(as_db_query_sub(
		'SELECT COUNT(child1.categoryid) AS count1, COUNT(child2.categoryid) AS count2, COUNT(child3.categoryid) AS count3 FROM ^categories AS child1 LEFT JOIN ^categories AS child2 ON child2.parentid=child1.categoryid LEFT JOIN ^categories AS child3 ON child3.parentid=child2.categoryid WHERE child1.parentid=#;', // requires AS_CATEGORY_DEPTH=4
		$categoryid
	));

	for ($depth = AS_CATEGORY_DEPTH - 1; $depth >= 1; $depth--)
		if ($result['count' . $depth])
			return $depth;

	return 0;
}


/**
 * Create a new category with $parentid, $title (=name) and $tags (=slug) in the database
 * @param $parentid
 * @param $title
 * @param $tags
 * @return mixed
 */
function as_db_category_create($parentid, $title, $tags)
{
	$lastpos = as_db_category_last_pos($parentid);

	as_db_query_sub(
		'INSERT INTO ^categories (parentid, title, tags, position) VALUES (#, $, $, #)',
		$parentid, $title, $tags, 1 + $lastpos
	);

	$categoryid = as_db_last_insert_id();

	as_db_categories_recalc_backpaths($categoryid);

	return $categoryid;
}

/**
 * Recalculate the backpath columns for all categories from $firstcategoryid to $lastcategoryid (if specified)
 * @param $firstcategoryid
 * @param $lastcategoryid
 */
function as_db_categories_recalc_backpaths($firstcategoryid, $lastcategoryid = null)
{
	if (!isset($lastcategoryid))
		$lastcategoryid = $firstcategoryid;

	as_db_query_sub(
		"UPDATE ^categories AS x, (SELECT cat1.categoryid, CONCAT_WS('/', cat1.tags, cat2.tags, cat3.tags, cat4.tags) AS backpath FROM ^categories AS cat1 LEFT JOIN ^categories AS cat2 ON cat1.parentid=cat2.categoryid LEFT JOIN ^categories AS cat3 ON cat2.parentid=cat3.categoryid LEFT JOIN ^categories AS cat4 ON cat3.parentid=cat4.categoryid WHERE cat1.categoryid BETWEEN # AND #) AS a SET x.backpath=a.backpath WHERE x.categoryid=a.categoryid",
		$firstcategoryid, $lastcategoryid // requires AS_CATEGORY_DEPTH=4
	);
}


/**
 * Set the name of $categoryid to $title and its slug to $tags in the database
 * @param $categoryid
 * @param $title
 * @param $tags
 */
function as_db_category_rename($categoryid, $title, $tags)
{
	as_db_query_sub(
		'UPDATE ^categories SET title=$, tags=$ WHERE categoryid=#',
		$title, $tags, $categoryid
	);

	as_db_categories_recalc_backpaths($categoryid); // may also require recalculation of its offspring's backpaths
}

function as_db_product_create($category, $userid, $cookieid, $ip, $icon, $title, $tags, $itemcode, $volume, $mass, $texture, $content)
{
	as_db_query_sub(
		'INSERT INTO ^posts (categoryid, userid, cookieid, createip, icon, title, tags, itemcode, volume, mass, texture, content, created) 
		VALUES (#, #, #, UNHEX($), $, $, $, $, $, $, $, $, NOW())',
		$category, $userid, $cookieid, bin2hex(@inet_pton($ip)), $icon, $title, $tags, $itemcode, $volume, $mass, $texture, $content
	);
	
	$productid = as_db_last_insert_id();

	return $productid;
}

function as_db_product_update($category, $userid, $icon, $title, $tags, $itemcode, $volume, $mass, $texture, $content, $postid)
{
	as_db_query_sub(
		'UPDATE ^posts SET categoryid=$, icon=$, title=$, tags=$, itemcode=$, volume=$, mass=$, texture=$, content=$, updated=NOW() WHERE postid=#',
		$category, $icon, $title, $tags, $itemcode, $volume, $mass, $texture, $content, $postid
	);

	//as_db_categories_recalc_backpaths($categoryid); // may also require recalculation of its offspring's backpaths
}

/**
 * Set the content (=description) of $categoryid to $content
 * @param $categoryid
 * @param $content
 */
function as_db_category_set_content($categoryid, $content, $icon = null)
{
	as_db_query_sub(
		'UPDATE ^categories SET content=$, icon=$ WHERE categoryid=#',
		$content, $icon, $categoryid
	);
}


/**
 * Return the parentid of $categoryid
 * @param $categoryid
 * @return mixed|null
 */
function as_db_category_get_parent($categoryid)
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT parentid FROM ^categories WHERE categoryid=#',
		$categoryid
	));
}


/**
 * Move the category $categoryid into position $newposition under its parent
 * @param $categoryid
 * @param $newposition
 */
function as_db_category_set_position($categoryid, $newposition)
{
	as_db_ordered_move('categories', 'categoryid', $categoryid, $newposition,
		as_db_apply_sub('parentid<=>#', array(as_db_category_get_parent($categoryid))));
}


/**
 * Set the parent of $categoryid to $newparentid, placing it in last position (doesn't do necessary recalculations)
 * @param $categoryid
 * @param $newparentid
 */
function as_db_category_set_parent($categoryid, $newparentid)
{
	$oldparentid = as_db_category_get_parent($categoryid);

	if (strcmp($oldparentid, $newparentid)) { // if we're changing parent, move to end of old parent, then end of new parent
		$lastpos = as_db_category_last_pos($oldparentid);

		as_db_ordered_move('categories', 'categoryid', $categoryid, $lastpos, as_db_apply_sub('parentid<=>#', array($oldparentid)));

		$lastpos = as_db_category_last_pos($newparentid);

		as_db_query_sub(
			'UPDATE ^categories SET parentid=#, position=# WHERE categoryid=#',
			$newparentid, 1 + $lastpos, $categoryid
		);
	}
}


/**
 * Change the categoryid of any posts with (exact) $categoryid to $reassignid
 * @param $categoryid
 * @param $reassignid
 */
function as_db_category_reassign($categoryid, $reassignid)
{
	as_db_query_sub('UPDATE ^posts SET categoryid=# WHERE categoryid<=>#', $reassignid, $categoryid);
}


/**
 * Delete the category $categoryid in the database
 * @param $categoryid
 */
function as_db_category_delete($categoryid)
{
	as_db_ordered_delete('categories', 'categoryid', $categoryid,
		as_db_apply_sub('parentid<=>#', array(as_db_category_get_parent($categoryid))));
}


/**
 * Return the categoryid for the category with parent $parentid and $slug
 * @param $parentid
 * @param $slug
 * @return mixed|null
 */
function as_db_category_slug_to_id($parentid, $slug)
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT categoryid FROM ^categories WHERE parentid<=># AND tags=$',
		$parentid, $slug
	), true);
}



/**
 * Return the maximum position of the locations with $parentid
 * @param $parentid
 * @return mixed|null
 */
function as_db_location_last_pos($parentid)
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT COALESCE(MAX(position), 0) FROM ^locations WHERE parentid<=>#',
		$parentid
	));
}


/**
 * Return how many levels of sublocation there are below $locationid
 * @param $locationid
 * @return int
 */
function as_db_location_child_depth($locationid)
{
	// This is potentially a very slow query since it counts all the multi-generational offspring of a particular location
	// But it's only used for admin purposes when moving a location around so I don't think it's worth making more efficient
	// (Incidentally, this could be done by keeping a count for every location of how many generations of offspring it has.)

	$result = as_db_read_one_assoc(as_db_query_sub(
		'SELECT COUNT(child1.locationid) AS count1, COUNT(child2.locationid) AS count2, COUNT(child3.locationid) AS count3 FROM ^locations AS child1 LEFT JOIN ^locations AS child2 ON child2.parentid=child1.locationid LEFT JOIN ^locations AS child3 ON child3.parentid=child2.locationid WHERE child1.parentid=#;', // requires AS_CATEGORY_DEPTH=4
		$locationid
	));

	for ($depth = AS_CATEGORY_DEPTH - 1; $depth >= 1; $depth--)
		if ($result['count' . $depth])
			return $depth;

	return 0;
}


/**
 * Create a new location with $parentid, $title (=name) and $tags (=slug) in the database
 * @param $parentid
 * @param $title
 * @param $tags
 * @return mixed
 */
function as_db_location_create($parentid, $title, $tags)
{
	$lastpos = as_db_location_last_pos($parentid);

	as_db_query_sub(
		'INSERT INTO ^locations (parentid, title, tags, position) VALUES (#, $, $, #)',
		$parentid, $title, $tags, 1 + $lastpos
	);

	$locationid = as_db_last_insert_id();

	as_db_locations_recalc_backpaths($locationid);

	return $locationid;
}

/**
 * Recalculate the backpath columns for all locations from $firstlocationid to $lastlocationid (if specified)
 * @param $firstlocationid
 * @param $lastlocationid
 */
function as_db_locations_recalc_backpaths($firstlocationid, $lastlocationid = null)
{
	if (!isset($lastlocationid))
		$lastlocationid = $firstlocationid;

	as_db_query_sub(
		"UPDATE ^locations AS x, (SELECT cat1.locationid, CONCAT_WS('/', cat1.tags, cat2.tags, cat3.tags, cat4.tags) AS backpath FROM ^locations AS cat1 LEFT JOIN ^locations AS cat2 ON cat1.parentid=cat2.locationid LEFT JOIN ^locations AS cat3 ON cat2.parentid=cat3.locationid LEFT JOIN ^locations AS cat4 ON cat3.parentid=cat4.locationid WHERE cat1.locationid BETWEEN # AND #) AS a SET x.backpath=a.backpath WHERE x.locationid=a.locationid",
		$firstlocationid, $lastlocationid // requires AS_CATEGORY_DEPTH=4
	);
}


/**
 * Set the name of $locationid to $title and its slug to $tags in the database
 * @param $locationid
 * @param $title
 * @param $tags
 */
function as_db_location_rename($locationid, $title, $tags)
{
	as_db_query_sub(
		'UPDATE ^locations SET title=$, tags=$ WHERE locationid=#',
		$title, $tags, $locationid
	);

	as_db_locations_recalc_backpaths($locationid); // may also require recalculation of its offspring's backpaths
}

/**
 * Set the content (=description) of $locationid to $content
 * @param $locationid
 * @param $content
 */
function as_db_location_set_content($locationid, $content)
{
	as_db_query_sub(
		'UPDATE ^locations SET content=$ WHERE locationid=#',
		$content, $locationid
	);
}


/**
 * Return the parentid of $locationid
 * @param $locationid
 * @return mixed|null
 */
function as_db_location_get_parent($locationid)
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT parentid FROM ^locations WHERE locationid=#',
		$locationid
	));
}


/**
 * Move the location $locationid into position $newposition under its parent
 * @param $locationid
 * @param $newposition
 */
function as_db_location_set_position($locationid, $newposition)
{
	as_db_ordered_move('locations', 'locationid', $locationid, $newposition,
		as_db_apply_sub('parentid<=>#', array(as_db_location_get_parent($locationid))));
}


/**
 * Set the parent of $locationid to $newparentid, placing it in last position (doesn't do necessary recalculations)
 * @param $locationid
 * @param $newparentid
 */
function as_db_location_set_parent($locationid, $newparentid)
{
	$oldparentid = as_db_location_get_parent($locationid);

	if (strcmp($oldparentid, $newparentid)) { // if we're changing parent, move to end of old parent, then end of new parent
		$lastpos = as_db_location_last_pos($oldparentid);

		as_db_ordered_move('locations', 'locationid', $locationid, $lastpos, as_db_apply_sub('parentid<=>#', array($oldparentid)));

		$lastpos = as_db_location_last_pos($newparentid);

		as_db_query_sub(
			'UPDATE ^locations SET parentid=#, position=# WHERE locationid=#',
			$newparentid, 1 + $lastpos, $locationid
		);
	}
}


/**
 * Change the locationid of any posts with (exact) $locationid to $reassignid
 * @param $locationid
 * @param $reassignid
 */
function as_db_location_reassign($locationid, $reassignid)
{
	as_db_query_sub('UPDATE ^posts SET locationid=# WHERE locationid<=>#', $reassignid, $locationid);
}


/**
 * Delete the location $locationid in the database
 * @param $locationid
 */
function as_db_location_delete($locationid)
{
	as_db_ordered_delete('locations', 'locationid', $locationid,
		as_db_apply_sub('parentid<=>#', array(as_db_location_get_parent($locationid))));
}


/**
 * Return the locationid for the location with parent $parentid and $slug
 * @param $parentid
 * @param $slug
 * @return mixed|null
 */
function as_db_location_slug_to_id($parentid, $slug)
{
	return as_db_read_one_value(as_db_query_sub(
		'SELECT locationid FROM ^locations WHERE parentid<=># AND tags=$',
		$parentid, $slug
	), true);
}

/**
 * Create a new custom page (or link) in the database
 * @param $title
 * @param $flags
 * @param $tags
 * @param $heading
 * @param $content
 * @param $permit
 * @return mixed
 */
function as_db_page_create($title, $flags, $tags, $heading, $content, $permit = null)
{
	$position = as_db_read_one_value(as_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^pages'));

	as_db_query_sub(
		'INSERT INTO ^pages (title, nav, flags, permit, tags, heading, content, position) VALUES ($, \'\', #, #, $, $, $, #)',
		$title, $flags, $permit, $tags, $heading, $content, $position
	);

	return as_db_last_insert_id();
}


/**
 * Set the fields of $pageid to the values provided in the database
 * @param $pageid
 * @param $title
 * @param $flags
 * @param $tags
 * @param $heading
 * @param $content
 * @param $permit
 */
function as_db_page_set_fields($pageid, $title, $flags, $tags, $heading, $content, $permit = null)
{
	as_db_query_sub(
		'UPDATE ^pages SET title=$, flags=#, permit=#, tags=$, heading=$, content=$ WHERE pageid=#',
		$title, $flags, $permit, $tags, $heading, $content, $pageid
	);
}


/**
 * Move the page $pageid into navigation menu $nav and position $newposition in the database
 * @param $pageid
 * @param $nav
 * @param $newposition
 */
function as_db_page_move($pageid, $nav, $newposition)
{
	as_db_query_sub(
		'UPDATE ^pages SET nav=$ WHERE pageid=#',
		$nav, $pageid
	);

	as_db_ordered_move('pages', 'pageid', $pageid, $newposition);
}


/**
 * Delete the page $pageid in the database
 * @param $pageid
 */
function as_db_page_delete($pageid)
{
	as_db_ordered_delete('pages', 'pageid', $pageid);
}


/**
 * Move the entity identified by $idcolumn=$id into position $newposition (within optional $conditionsql) in $table in the database
 * @param $table
 * @param $idcolumn
 * @param $id
 * @param $newposition
 * @param $conditionsql
 */
function as_db_ordered_move($table, $idcolumn, $id, $newposition, $conditionsql = null)
{
	$andsql = isset($conditionsql) ? (' AND ' . $conditionsql) : '';

	as_db_query_sub('LOCK TABLES ^' . $table . ' WRITE');

	$oldposition = as_db_read_one_value(as_db_query_sub('SELECT position FROM ^' . $table . ' WHERE ' . $idcolumn . '=#' . $andsql, $id));

	if ($newposition != $oldposition) {
		$lastposition = as_db_read_one_value(as_db_query_sub('SELECT MAX(position) FROM ^' . $table . ' WHERE TRUE' . $andsql));

		$newposition = max(1, min($newposition, $lastposition)); // constrain it to within range

		// move it temporarily off the top because we have a unique key on the position column
		as_db_query_sub('UPDATE ^' . $table . ' SET position=# WHERE ' . $idcolumn . '=#' . $andsql, 1 + $lastposition, $id);

		if ($newposition < $oldposition)
			as_db_query_sub('UPDATE ^' . $table . ' SET position=position+1 WHERE position BETWEEN # AND #' . $andsql . ' ORDER BY position DESC', $newposition, $oldposition);
		else
			as_db_query_sub('UPDATE ^' . $table . ' SET position=position-1 WHERE position BETWEEN # AND #' . $andsql . ' ORDER BY position', $oldposition, $newposition);

		as_db_query_sub('UPDATE ^' . $table . ' SET position=# WHERE ' . $idcolumn . '=#' . $andsql, $newposition, $id);
	}

	as_db_query_sub('UNLOCK TABLES');
}


/**
 * Delete the entity identified by $idcolumn=$id (and optional $conditionsql) in $table in the database
 * @param $table
 * @param $idcolumn
 * @param $id
 * @param $conditionsql
 */
function as_db_ordered_delete($table, $idcolumn, $id, $conditionsql = null)
{
	$andsql = isset($conditionsql) ? (' AND ' . $conditionsql) : '';

	as_db_query_sub('LOCK TABLES ^' . $table . ' WRITE');

	$oldposition = as_db_read_one_value(as_db_query_sub('SELECT position FROM ^' . $table . ' WHERE ' . $idcolumn . '=#' . $andsql, $id));

	as_db_query_sub('DELETE FROM ^' . $table . ' WHERE ' . $idcolumn . '=#' . $andsql, $id);

	as_db_query_sub('UPDATE ^' . $table . ' SET position=position-1 WHERE position>#' . $andsql . ' ORDER BY position', $oldposition);

	as_db_query_sub('UNLOCK TABLES');
}


/**
 * Create a new user field with (internal) tag $title, label $content, $flags and $permit in the database.
 * @param $title
 * @param $content
 * @param $flags
 * @param $permit
 * @return mixed
 */
function as_db_userfield_create($title, $content, $flags, $permit = null)
{
	$position = as_db_read_one_value(as_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^userfields'));

	as_db_query_sub(
		'INSERT INTO ^userfields (title, content, position, flags, permit) VALUES ($, $, #, #, #)',
		$title, $content, $position, $flags, $permit
	);

	return as_db_last_insert_id();
}


/**
 * Change the user field $fieldid to have label $content, $flags and $permit in the database (the title column cannot be changed once set)
 * @param $fieldid
 * @param $content
 * @param $flags
 * @param $permit
 */
function as_db_userfield_set_fields($fieldid, $content, $flags, $permit = null)
{
	as_db_query_sub(
		'UPDATE ^userfields SET content=$, flags=#, permit=# WHERE fieldid=#',
		$content, $flags, $permit, $fieldid
	);
}


/**
 * Move the user field $fieldid into position $newposition in the database
 * @param $fieldid
 * @param $newposition
 */
function as_db_userfield_move($fieldid, $newposition)
{
	as_db_ordered_move('userfields', 'fieldid', $fieldid, $newposition);
}


/**
 * Delete the user field $fieldid in the database
 * @param $fieldid
 */
function as_db_userfield_delete($fieldid)
{
	as_db_ordered_delete('userfields', 'fieldid', $fieldid);
}


/**
 * Return the ID of a new widget, to be displayed by the widget module named $title on templates within $tags (comma-separated list)
 * @param $title
 * @param $tags
 * @return mixed
 */
function as_db_widget_create($title, $tags)
{
	$position = as_db_read_one_value(as_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^widgets'));

	as_db_query_sub(
		'INSERT INTO ^widgets (place, position, tags, title) VALUES (\'\', #, $, $)',
		$position, $tags, $title
	);

	return as_db_last_insert_id();
}


/**
 * Set the comma-separated list of templates for $widgetid to $tags
 * @param $widgetid
 * @param $tags
 */
function as_db_widget_set_fields($widgetid, $tags)
{
	as_db_query_sub(
		'UPDATE ^widgets SET tags=$ WHERE widgetid=#',
		$tags, $widgetid
	);
}


/**
 * Move the widget $widgetit into position $position in the database's order, and show it in $place on the page
 * @param $widgetid
 * @param $place
 * @param $newposition
 */
function as_db_widget_move($widgetid, $place, $newposition)
{
	as_db_query_sub(
		'UPDATE ^widgets SET place=$ WHERE widgetid=#',
		$place, $widgetid
	);

	as_db_ordered_move('widgets', 'widgetid', $widgetid, $newposition);
}


/**
 * Delete the widget $widgetid in the database
 * @param $widgetid
 */
function as_db_widget_delete($widgetid)
{
	as_db_ordered_delete('widgets', 'widgetid', $widgetid);
}
