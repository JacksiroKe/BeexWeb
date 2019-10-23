<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Database functions for creating a item, review or comment


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
 * Create a new business in the database with type, categoryid, location, contact, title, username, content, icon, tags, userid, created
 * @param $email
 * @param $password
 * @param $handle
 * @param $level
 * @param $ip
 * @return mixed
 */
function as_db_business_create($bstype, $title, $contact, $location, $username, $content, $icon, $tags, $userid)
{
	as_db_query_sub(
			'INSERT INTO ^businesses (bstype, title, contact, location, username, content, icon, tags, userid, created) ' .
			'VALUES ($, $, $, $, $, $, $, $, #, NOW())',
			$bstype, $title, $contact, $location, $username, $content, $icon, $tags, $userid
		);
	return as_db_last_insert_id();
}

function as_db_department_create($depttype, $title, $content, $icon, $userid)
{
	as_db_query_sub(
			'INSERT INTO ^businessdepts (depttype, title, content, icon, userid, created) ' .
			'VALUES ($, $, $, $, #, NOW())',
			$depttype, $title, $content, $icon, $userid
		);
	return as_db_last_insert_id();
}

/**
 * Create a new post in the database and return its ID (based on auto-incrementing)
 * @param $type
 * @param $parentid
 * @param $userid
 * @param $cookieid
 * @param $ip
 * @param $title
 * @param $content
 * @param $format
 * @param $tagstring
 * @param $notify
 * @param $categoryid
 * @param $name
 * @return mixed
 */
function as_db_item_create($type, $parentid, $userid, $cookieid, $ip, $icon, $volume, $weight, $buyprice, $saleprice, $state, $color, $texture, $quantity, $manufacturer, $content, $format, $categoryid, $name = null)
{
	as_db_query_sub(
		'INSERT INTO ^posts (categoryid, type, parentid, userid, cookieid, createip, icon, volume, weight, buyprice, saleprice, state, color, texture, quantity, manufacturer, content, format, name, created) ' .
		'VALUES (#, $, #, $, #, UNHEX($), $, $, $, #, #, $, $, $, $, $, $, $, $, NOW())',
		$categoryid, $type, $parentid, $userid, $cookieid, bin2hex(@inet_pton($ip)), $icon, $volume, $weight, $buyprice, $saleprice, $state, $color, $texture, $quantity, $manufacturer, $content, $format, $name
	);

	return as_db_last_insert_id();
}

function as_db_order_create($userid, $cookieid, $ip, $itemid, $quantity, $address)
{
	as_db_query_sub(
		'INSERT INTO ^orders (itemid, userid, cookieid, createip, quantity, address, created) ' .
		'VALUES (#, #, UNHEX($), #, #, $, NOW())',
		$itemid, $userid, $cookieid, bin2hex(@inet_pton($ip)), $quantity, $address
	);
}

/**
 * Get the full category path (including categoryid) for $postid
 * @param $postid
 * @return array|null
 */
function as_db_post_get_category_path($postid)
{
	return as_db_read_one_assoc(as_db_query_sub(
		'SELECT categoryid, catidpath1, catidpath2, catidpath3 FROM ^posts WHERE postid=#',
		$postid
	)); // requires AS_CATEGORY_DEPTH=4
}


/**
 * Update the cached number of reviews for $articleid in the database, along with the highest netlikes of any of its reviews
 * @param $articleid
 */
function as_db_post_rcount_update($articleid)
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"UPDATE ^posts AS x, (SELECT COUNT(*) AS rcount, COALESCE(GREATEST(MAX(netlikes), 0), 0) AS rmaxlike FROM ^posts WHERE parentid=# AND type='R') AS a SET x.rcount=a.rcount, x.rmaxlike=a.rmaxlike WHERE x.postid=#",
			$articleid, $articleid
		);
	}
}


/**
 * Recalculate the number of items for each category in $path retrieved via as_db_post_get_category_path()
 * @param $path
 */
function as_db_category_path_pcount_update($path)
{
	as_db_ifcategory_pcount_update($path['categoryid']); // requires AS_CATEGORY_DEPTH=4
	as_db_ifcategory_pcount_update($path['catidpath1']);
	as_db_ifcategory_pcount_update($path['catidpath2']);
	as_db_ifcategory_pcount_update($path['catidpath3']);
}


/**
 * Update the cached number of items for category $categoryid in the database, including its subcategories
 * @param $categoryid
 */
function as_db_ifcategory_pcount_update($categoryid)
{
	if (as_should_update_counts() && isset($categoryid)) {
		// This seemed like the most sensible approach which avoids explicitly calculating the category's depth in the hierarchy

		as_db_query_sub(
			"UPDATE ^categories SET pcount=GREATEST( (SELECT COUNT(*) FROM ^posts WHERE categoryid=# AND type='P'), (SELECT COUNT(*) FROM ^posts WHERE catidpath1=# AND type='P'), (SELECT COUNT(*) FROM ^posts WHERE catidpath2=# AND type='P'), (SELECT COUNT(*) FROM ^posts WHERE catidpath3=# AND type='P') ) WHERE categoryid=#",
			$categoryid, $categoryid, $categoryid, $categoryid, $categoryid
		); // requires AS_CATEGORY_DEPTH=4
	}
}


/**
 * Add rows into the database title index, where $postid contains the words $wordids - this does the same sort
 * of thing as as_db_posttags_add_post_wordids() in a different way, for no particularly good reason.
 * @param $postid
 * @param $wordids
 */
function as_db_titlewords_add_post_wordids($postid, $wordids)
{
	if (count($wordids)) {
		$rowstoadd = array();
		foreach ($wordids as $wordid)
			$rowstoadd[] = array($postid, $wordid);

		as_db_query_sub(
			'INSERT INTO ^titlewords (postid, wordid) VALUES #',
			$rowstoadd
		);
	}
}


/**
 * Add rows into the database content index, where $postid (of $type, with the antecedent $articleid)
 * has words as per the keys of $wordidcounts, and the corresponding number of those words in the values.
 * @param $postid
 * @param $type
 * @param $articleid
 * @param $wordidcounts
 */
function as_db_contentwords_add_post_wordidcounts($postid, $type, $articleid, $wordidcounts)
{
	if (count($wordidcounts)) {
		$rowstoadd = array();
		foreach ($wordidcounts as $wordid => $count)
			$rowstoadd[] = array($postid, $wordid, $count, $type, $articleid);

		as_db_query_sub(
			'INSERT INTO ^contentwords (postid, wordid, count, type, articleid) VALUES #',
			$rowstoadd
		);
	}
}


/**
 * Add rows into the database index of individual tag words, where $postid contains the words $wordids
 * @param $postid
 * @param $wordids
 */
function as_db_tagwords_add_post_wordids($postid, $wordids)
{
	if (count($wordids)) {
		$rowstoadd = array();
		foreach ($wordids as $wordid)
			$rowstoadd[] = array($postid, $wordid);

		as_db_query_sub(
			'INSERT INTO ^tagwords (postid, wordid) VALUES #',
			$rowstoadd
		);
	}
}


/**
 * Add rows into the database index of whole tags, where $postid contains the tags $wordids
 * @param $postid
 * @param $wordids
 */
function as_db_posttags_add_post_wordids($postid, $wordids)
{
	if (count($wordids)) {
		as_db_query_sub(
			'INSERT INTO ^posttags (postid, wordid, postcreated) SELECT postid, wordid, created FROM ^words, ^posts WHERE postid=# AND wordid IN ($)',
			$postid, $wordids
		);
	}
}


/**
 * Return an array mapping each word in $words to its corresponding wordid in the database
 * @param $words
 * @return array
 */
function as_db_word_mapto_ids($words)
{
	if (count($words)) {
		return as_db_read_all_assoc(as_db_query_sub(
			'SELECT wordid, word FROM ^words WHERE word IN ($)', $words
		), 'word', 'wordid');
	}

	return array();
}


/**
 * Return an array mapping each word in $words to its corresponding wordid in the database, adding any that are missing
 * @param $words
 * @return array
 */
function as_db_word_mapto_ids_add($words)
{
	$wordtoid = as_db_word_mapto_ids($words);

	$wordstoadd = array();
	foreach ($words as $word) {
		if (!isset($wordtoid[$word]))
			$wordstoadd[] = $word;
	}

	if (count($wordstoadd)) {
		as_db_query_sub('LOCK TABLES ^words WRITE'); // to prevent two requests adding the same word

		$wordtoid = as_db_word_mapto_ids($words); // map it again in case table content changed before it was locked

		$rowstoadd = array();
		foreach ($words as $word) {
			if (!isset($wordtoid[$word]))
				$rowstoadd[] = array($word);
		}

		as_db_query_sub('INSERT IGNORE INTO ^words (word) VALUES $', $rowstoadd);

		as_db_query_sub('UNLOCK TABLES');

		$wordtoid = as_db_word_mapto_ids($words); // do it one last time
	}

	return $wordtoid;
}


/**
 * Update the titlecount column in the database for the words in $wordids, based on how many posts they appear in the title of
 * @param $wordids
 */
function as_db_word_titlecount_update($wordids)
{
	if (as_should_update_counts() && count($wordids)) {
		as_db_query_sub(
			'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^titlewords.wordid) AS titlecount FROM ^words LEFT JOIN ^titlewords ON ^titlewords.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid',
			$wordids
		);
	}
}


/**
 * Update the contentcount column in the database for the words in $wordids, based on how many posts they appear in the content of
 * @param $wordids
 */
function as_db_word_contentcount_update($wordids)
{
	if (as_should_update_counts() && count($wordids)) {
		as_db_query_sub(
			'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^contentwords.wordid) AS contentcount FROM ^words LEFT JOIN ^contentwords ON ^contentwords.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid',
			$wordids
		);
	}
}


/**
 * Update the tagwordcount column in the database for the individual tag words in $wordids, based on how many posts they appear in the tags of
 * @param $wordids
 */
function as_db_word_tagwordcount_update($wordids)
{
	if (as_should_update_counts() && count($wordids)) {
		as_db_query_sub(
			'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^tagwords.wordid) AS tagwordcount FROM ^words LEFT JOIN ^tagwords ON ^tagwords.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.tagwordcount=a.tagwordcount WHERE x.wordid=a.wordid',
			$wordids
		);
	}
}


/**
 * Update the tagcount column in the database for the whole tags in $wordids, based on how many posts they appear as tags of
 * @param $wordids
 */
function as_db_word_tagcount_update($wordids)
{
	if (as_should_update_counts() && count($wordids)) {
		as_db_query_sub(
			'UPDATE ^words AS x, (SELECT ^words.wordid, COUNT(^posttags.wordid) AS tagcount FROM ^words LEFT JOIN ^posttags ON ^posttags.wordid=^words.wordid WHERE ^words.wordid IN (#) GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid',
			$wordids
		);
	}
}


/**
 * Update the cached count in the database of the number of items (excluding hidden/queued)
 */
function as_db_pcount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_pcount', COUNT(*) FROM ^posts " .
			"WHERE type = 'P' " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}


/**
 * Update the cached count in the database of the number of reviews (excluding hidden/queued)
 */
function as_db_rcount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_rcount', COUNT(*) FROM ^posts " .
			"WHERE type = 'R' " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}


/**
 * Update the cached count in the database of the number of comments (excluding hidden/queued)
 */
function as_db_ccount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_ccount', COUNT(*) FROM ^posts " .
			"WHERE type = 'C' " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}


/**
 * Update the cached count in the database of the number of different tags used
 */
function as_db_tagcount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_tagcount', COUNT(*) FROM ^words " .
			"WHERE tagcount > 0 " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}


/**
 * Update the cached count in the database of the number of unreviewed items (excluding hidden/queued)
 */
function as_db_unapcount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_unapcount', COUNT(*) FROM ^posts " .
			"WHERE type = 'P' AND rcount = 0 AND closedbyid IS NULL " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}


/**
 * Update the cached count in the database of the number of items with no review selected (excluding hidden/queued)
 */
function as_db_unselpcount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_unselpcount', COUNT(*) FROM ^posts " .
			"WHERE type = 'P' AND selchildid IS NULL AND closedbyid IS NULL " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}


/**
 * Update the cached count in the database of the number of items with no positiveliked reviews (excluding hidden/queued)
 */
function as_db_unupapcount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_unupapcount', COUNT(*) FROM ^posts " .
			"WHERE type = 'P' AND rmaxlike = 0 AND closedbyid IS NULL " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}


/**
 * Update the cached count in the database of the number of posts which are queued for moderation
 */
function as_db_queuedcount_update()
{
	if (as_should_update_counts()) {
		as_db_query_sub(
			"INSERT INTO ^options (title, content) " .
			"SELECT 'cache_queuedcount', COUNT(*) FROM ^posts " .
			"WHERE type IN ('P_QUEUED', 'R_QUEUED', 'C_QUEUED') " .
			"ON DUPLICATE KEY UPDATE content = VALUES(content)"
		);
	}
}
