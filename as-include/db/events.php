<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Database-level access to userevents and sharedevents tables


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
 * Add an event to the event streams for entity $entitytype with $entityid. The event of type $updatetype relates to
 * $lastpostid whose antecedent item is $articleid, and was caused by $lastuserid. Pass a unix $timestamp for the
 * event time or leave as null to use now. This will add the event both to the entity's shared stream, and the
 * individual user streams for any users following the entity not via its shared stream (See long comment in
 * /as-include/db/favorites.php). Also handles truncation.
 * @param $entitytype
 * @param $entityid
 * @param $articleid
 * @param $lastpostid
 * @param $updatetype
 * @param $lastuserid
 * @param $timestamp
 */
function as_db_event_create_for_entity($entitytype, $entityid, $articleid, $lastpostid, $updatetype, $lastuserid, $timestamp = null)
{
	require_once AS_INCLUDE_DIR . 'db/maxima.php';
	require_once AS_INCLUDE_DIR . 'app/updates.php';

	$updatedsql = isset($timestamp) ? ('FROM_UNIXTIME(' . as_db_argument_to_mysql($timestamp, false) . ')') : 'NOW()';

	// Enter it into the appropriate shared event stream for that entity

	as_db_query_sub(
		'INSERT INTO ^sharedevents (entitytype, entityid, articleid, lastpostid, updatetype, lastuserid, updated) ' .
		'VALUES ($, #, #, #, $, $, ' . $updatedsql . ')',
		$entitytype, $entityid, $articleid, $lastpostid, $updatetype, $lastuserid
	);

	// If this is for a item entity, check the shared event stream doesn't have too many entries for that item

	$articletruncated = false;

	if ($entitytype == AS_ENTITY_ARTICLE) {
		$truncate = as_db_read_one_value(as_db_query_sub(
			'SELECT updated FROM ^sharedevents WHERE entitytype=$ AND entityid=# AND articleid=# ORDER BY updated DESC LIMIT #,1',
			$entitytype, $entityid, $articleid, AS_DB_MAX_EVENTS_PER_Q
		), true);

		if (isset($truncate)) {
			as_db_query_sub(
				'DELETE FROM ^sharedevents WHERE entitytype=$ AND entityid=# AND articleid=# AND updated<=$',
				$entitytype, $entityid, $articleid, $truncate
			);

			$articletruncated = true;
		}
	}

	// If we didn't truncate due to a specific item, truncate the shared event stream for its overall length

	if (!$articletruncated) {
		$truncate = as_db_read_one_value(as_db_query_sub(
			'SELECT updated FROM ^sharedevents WHERE entitytype=$ AND entityid=$ ORDER BY updated DESC LIMIT #,1',
			$entitytype, $entityid, (int)as_opt('max_store_user_updates')
		), true);

		if (isset($truncate))
			as_db_query_sub(
				'DELETE FROM ^sharedevents WHERE entitytype=$ AND entityid=$ AND updated<=$',
				$entitytype, $entityid, $truncate
			);
	}

	// See if we can identify a user who has favorited this entity, but is not using its shared event stream

	$randomuserid = as_db_read_one_value(as_db_query_sub(
		'SELECT userid FROM ^userfavorites WHERE entitytype=$ AND entityid=# AND nouserevents=0 ORDER BY RAND() LIMIT 1',
		$entitytype, $entityid
	), true);

	if (isset($randomuserid)) {
		// If one was found, this means we have one or more individual event streams, so update them all
		as_db_query_sub(
			'INSERT INTO ^userevents (userid, entitytype, entityid, articleid, lastpostid, updatetype, lastuserid, updated) ' .
			'SELECT userid, $, #, #, #, $, $, ' . $updatedsql . ' FROM ^userfavorites WHERE entitytype=$ AND entityid=# AND nouserevents=0',
			$entitytype, $entityid, $articleid, $lastpostid, $updatetype, $lastuserid, $entitytype, $entityid
		);

		// Now truncate the random individual event stream that was found earlier
		// (in theory we should truncate them all, but truncation is just a 'housekeeping' activity, so it's not necessary)
		as_db_user_events_truncate($randomuserid, $articleid);
	}
}


/**
 * Add an event to the event stream for $userid which is not related to an entity they are following (but rather a
 * notification which is relevant for them, e.g. if someone reviews their item). The event of type $updatetype
 * relates to $lastpostid whose antecedent item is $articleid, and was caused by $lastuserid. Pass a unix
 * $timestamp for the event time or leave as null to use now. Also handles truncation of event streams.
 * @param $userid
 * @param $articleid
 * @param $lastpostid
 * @param $updatetype
 * @param $lastuserid
 * @param $timestamp
 */
function as_db_event_create_not_entity($userid, $articleid, $lastpostid, $updatetype, $lastuserid, $timestamp = null)
{
	require_once AS_INCLUDE_DIR . 'app/updates.php';

	$updatedsql = isset($timestamp) ? ('FROM_UNIXTIME(' . as_db_argument_to_mysql($timestamp, false) . ')') : 'NOW()';

	as_db_query_sub(
		"INSERT INTO ^userevents (userid, entitytype, entityid, articleid, lastpostid, updatetype, lastuserid, updated) " .
		"VALUES ($, $, 0, #, #, $, $, " . $updatedsql . ")",
		$userid, AS_ENTITY_NONE, $articleid, $lastpostid, $updatetype, $lastuserid
	);

	as_db_user_events_truncate($userid, $articleid);
}


/**
 * Trim the number of events in the event stream for $userid. If an event was just added for a particular item,
 * pass the item's id in $articleid (to help focus the truncation).
 * @param $userid
 * @param $articleid
 */
function as_db_user_events_truncate($userid, $articleid = null)
{
	// First try truncating based on there being too many events for this item

	$articletruncated = false;

	if (isset($articleid)) {
		$truncate = as_db_read_one_value(as_db_query_sub(
			'SELECT updated FROM ^userevents WHERE userid=$ AND articleid=# ORDER BY updated DESC LIMIT #,1',
			$userid, $articleid, AS_DB_MAX_EVENTS_PER_Q
		), true);

		if (isset($truncate)) {
			as_db_query_sub(
				'DELETE FROM ^userevents WHERE userid=$ AND articleid=# AND updated<=$',
				$userid, $articleid, $truncate
			);

			$articletruncated = true;
		}
	}

	// If that didn't happen, try truncating the stream in general based on its total length

	if (!$articletruncated) {
		$truncate = as_db_read_one_value(as_db_query_sub(
			'SELECT updated FROM ^userevents WHERE userid=$ ORDER BY updated DESC LIMIT #,1',
			$userid, (int)as_opt('max_store_user_updates')
		), true);

		if (isset($truncate))
			as_db_query_sub(
				'DELETE FROM ^userevents WHERE userid=$ AND updated<=$',
				$userid, $truncate
			);
	}
}
