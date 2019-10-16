<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Handles the submission of events to the database (application level)


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

require_once AS_INCLUDE_DIR . 'db/events.php';
require_once AS_INCLUDE_DIR . 'app/updates.php';


/**
 * Add appropriate events to the database for an action performed on a item. The event of type $updatetype relates
 * to $lastpostid whose antecedent item is $articleid, and was caused by $lastuserid. Pass a unix $timestamp for
 * the event time or leave as null to use now. This will add an event to $articleid's and $lastuserid's streams. If
 * $otheruserid is set, it will also add an notification-style event for that user, unless they are the one who did it.
 * @param $articleid
 * @param $lastpostid
 * @param $updatetype
 * @param $lastuserid
 * @param $otheruserid
 * @param $timestamp
 */
function as_create_event_for_q_user($articleid, $lastpostid, $updatetype, $lastuserid, $otheruserid = null, $timestamp = null)
{
	as_db_event_create_for_entity(AS_ENTITY_ARTICLE, $articleid, $articleid, $lastpostid, $updatetype, $lastuserid, $timestamp); // anyone who favorited the item

	if (isset($lastuserid) && !AS_FINAL_EXTERNAL_USERS)
		as_db_event_create_for_entity(AS_ENTITY_USER, $lastuserid, $articleid, $lastpostid, $updatetype, $lastuserid, $timestamp); // anyone who favorited the user who did it

	if (isset($otheruserid) && ($otheruserid != $lastuserid))
		as_db_event_create_not_entity($otheruserid, $articleid, $lastpostid, $updatetype, $lastuserid, $timestamp); // possible other user to be informed
}


/**
 * Add appropriate events to the database for an action performed on a set of tags in $tagstring (namely, a item
 * being created with those tags or having one of those tags added afterwards). The event of type $updatetype relates
 * to the item $articleid, and was caused by $lastuserid. Pass a unix $timestamp for the event time or leave as
 * null to use now.
 * @param $tagstring
 * @param $articleid
 * @param $updatetype
 * @param $lastuserid
 * @param $timestamp
 */
function as_create_event_for_tags($tagstring, $articleid, $updatetype, $lastuserid, $timestamp = null)
{
	require_once AS_INCLUDE_DIR . 'util/string.php';
	require_once AS_INCLUDE_DIR . 'db/post-create.php';

	$tagwordids = as_db_word_mapto_ids(array_unique(as_tagstring_to_tags($tagstring)));
	foreach ($tagwordids as $wordid) {
		as_db_event_create_for_entity(AS_ENTITY_TAG, $wordid, $articleid, $articleid, $updatetype, $lastuserid, $timestamp);
	}
}


/**
 * Add appropriate events to the database for an action performed on $categoryid (namely, a item being created in
 * that category or being moved to it later on), along with all of its ancestor categories. The event of type
 * $updatetype relates to the item $articleid, and was caused by $lastuserid. Pass a unix $timestamp for the event
 * time or leave as null to use now.
 * @param $categoryid
 * @param $articleid
 * @param $updatetype
 * @param $lastuserid
 * @param $timestamp
 */
function as_create_event_for_category($categoryid, $articleid, $updatetype, $lastuserid, $timestamp = null)
{
	if (isset($categoryid)) {
		require_once AS_INCLUDE_DIR . 'db/selects.php';
		require_once AS_INCLUDE_DIR . 'app/format.php';

		$categories = as_category_path(as_db_single_select(as_db_category_nav_selectspec($categoryid, true)), $categoryid);
		foreach ($categories as $category) {
			as_db_event_create_for_entity(AS_ENTITY_CATEGORY, $category['categoryid'], $articleid, $articleid, $updatetype, $lastuserid, $timestamp);
		}
	}
}
