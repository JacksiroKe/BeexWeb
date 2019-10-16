<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Handles all requests to RSS feeds, first checking if they should be available


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
	header('Location: ../');
	exit;
}

@ini_set('display_errors', 0); // we don't want to show PHP errors to RSS readers

as_report_process_stage('init_feed');

require_once AS_INCLUDE_DIR . 'app/options.php';


// Functions used within this file

/**
 * Database failure handler function for RSS feeds - outputs HTTP and text errors
 * @param $type
 * @param int $errno
 * @param string $error
 * @param string $query
 */
function as_feed_db_fail_handler($type, $errno = null, $error = null, $query = null)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo as_lang_html('main/general_error');
	as_exit('error');
}


/**
 * Common function called when a non-existent feed is requested - outputs HTTP and text errors
 */
function as_feed_not_found()
{
	header('HTTP/1.0 404 Not Found');
	echo as_lang_html('misc/feed_not_found');
	as_exit();
}


/**
 * Common function to load appropriate set of items for requested feed, check category exists, and set up page title
 * @param array $categoryslugs
 * @param string $allkey
 * @param string $catkey
 * @param string $title
 * @param array $articleselectspec1
 * @param array $articleselectspec2
 * @param array $articleselectspec3
 * @param array $articleselectspec4
 * @return array
 */
function as_feed_load_ifcategory($categoryslugs, $allkey, $catkey, &$title,
	$articleselectspec1 = null, $articleselectspec2 = null, $articleselectspec3 = null, $articleselectspec4 = null)
{
	$countslugs = @count($categoryslugs);

	list($articles1, $articles2, $articles3, $articles4, $categories, $categoryid) = as_db_select_with_pending(
		$articleselectspec1,
		$articleselectspec2,
		$articleselectspec3,
		$articleselectspec4,
		$countslugs ? as_db_category_nav_selectspec($categoryslugs, false) : null,
		$countslugs ? as_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);

	if ($countslugs && !isset($categoryid))
		as_feed_not_found();

	if (isset($allkey))
		$title = (isset($categoryid) && isset($catkey)) ? as_lang_sub($catkey, $categories[$categoryid]['title']) : as_lang($allkey);

	return array_merge(
		is_array($articles1) ? $articles1 : array(),
		is_array($articles2) ? $articles2 : array(),
		is_array($articles3) ? $articles3 : array(),
		is_array($articles4) ? $articles4 : array()
	);
}


// Connect to database and get the type of feed and category requested (in some cases these are overridden later)

as_db_connect('as_feed_db_fail_handler');
as_initialize_postdb_plugins();

$requestlower = strtolower(as_request());
$foursuffix = substr($requestlower, -4);

if ($foursuffix == '.rss' || $foursuffix == '.xml') {
	$requestlower = substr($requestlower, 0, -4);
}

$requestlowerparts = explode('/', $requestlower);

$feedtype = @$requestlowerparts[1];
$feedparams = array_slice($requestlowerparts, 2);


// Choose which option needs to be checked to determine if this feed can be requested, and stop if no matches

$feedoption = null;
$categoryslugs = $feedparams;

switch ($feedtype) {
	case 'items':
		$feedoption = 'feed_for_articles';
		break;

	case 'hot':
		$feedoption = 'feed_for_hot';
		if (!AS_ALLOW_UNINDEXED_QUERIES)
			$categoryslugs = null;
		break;

	case 'unreviewed':
		$feedoption = 'feed_for_unreviewed';
		if (!AS_ALLOW_UNINDEXED_QUERIES)
			$categoryslugs = null;
		break;

	case 'reviews':
	case 'comments':
	case 'activity':
		$feedoption = 'feed_for_activity';
		break;

	case 'as':
		$feedoption = 'feed_for_as';
		break;

	case 'tag':
		if (strlen(@$feedparams[0])) {
			$feedoption = 'feed_for_tag_qs';
			$categoryslugs = null;
		}
		break;

	case 'search':
		if (strlen(@$feedparams[0])) {
			$feedoption = 'feed_for_search';
			$categoryslugs = null;
		}
		break;
}

$countslugs = @count($categoryslugs);

if (!isset($feedoption))
	as_feed_not_found();


// Check that all the appropriate options are in place to allow this feed to be retrieved

if (!(as_opt($feedoption) && ($countslugs ? (as_using_categories() && as_opt('feed_per_category')) : true)))
	as_feed_not_found();


// Retrieve the appropriate items and other information for this feed

require_once AS_INCLUDE_DIR . 'db/selects.php';

$sitetitle = as_opt('site_title');
$siteurl = as_opt('site_url');
$full = as_opt('feed_full_text');
$count = as_opt('feed_number_items');
$showurllinks = as_opt('show_url_links');

$linkrequest = $feedtype . ($countslugs ? ('/' . implode('/', $categoryslugs)) : '');
$linkparams = null;

switch ($feedtype) {
	case 'items':
		$items = as_feed_load_ifcategory($categoryslugs, 'main/recent_qs_title', 'main/recent_qs_in_x', $title,
			as_db_question_selectspec(null, 'created', 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'hot':
		$items = as_feed_load_ifcategory($categoryslugs, 'main/hot_qs_title', 'main/hot_qs_in_x', $title,
			as_db_question_selectspec(null, 'hotness', 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'unreviewed':
		$items = as_feed_load_ifcategory($categoryslugs, 'main/unreviewed_qs_title', 'main/unreviewed_qs_in_x', $title,
			as_db_unreviewed_qs_selectspec(null, null, 0, $categoryslugs, false, $full, $count)
		);
		break;

	case 'reviews':
		$items = as_feed_load_ifcategory($categoryslugs, 'main/recent_as_title', 'main/recent_as_in_x', $title,
			as_db_recent_a_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'comments':
		$items = as_feed_load_ifcategory($categoryslugs, 'main/recent_cs_title', 'main/recent_cs_in_x', $title,
			as_db_recent_c_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'as':
		$items = as_feed_load_ifcategory($categoryslugs, 'main/recent_qs_as_title', 'main/recent_qs_as_in_x', $title,
			as_db_question_selectspec(null, 'created', 0, $categoryslugs, null, false, $full, $count),
			as_db_recent_a_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count)
		);
		break;

	case 'activity':
		$items = as_feed_load_ifcategory($categoryslugs, 'main/recent_activity_title', 'main/recent_activity_in_x', $title,
			as_db_question_selectspec(null, 'created', 0, $categoryslugs, null, false, $full, $count),
			as_db_recent_a_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count),
			as_db_recent_c_qs_selectspec(null, 0, $categoryslugs, null, false, $full, $count),
			as_db_recent_edit_qs_selectspec(null, 0, $categoryslugs, null, true, $full, $count)
		);
		break;

	case 'tag':
		$tag = $feedparams[0];

		$items = as_feed_load_ifcategory(null, null, null, $title,
			as_db_tag_recent_qs_selectspec(null, $tag, 0, $full, $count)
		);

		$title = as_lang_sub('main/articles_tagged_x', $tag);
		$linkrequest = 'tag/' . $tag;
		break;

	case 'search':
		require_once AS_INCLUDE_DIR . 'app/search.php';

		$query = $feedparams[0];

		$results = as_get_search_results($query, 0, $count, null, true, $full);

		$title = as_lang_sub('main/results_for_x', $query);
		$linkrequest = 'search';
		$linkparams = array('q' => $query);

		$items = array();

		foreach ($results as $result) {
			$setarray = array(
				'title' => $result['title'],
				'url' => $result['url'],
			);

			if (isset($result['item']))
				$items[] = array_merge($result['item'], $setarray);
			elseif (isset($result['url']))
				$items[] = $setarray;
		}
		break;
}


// Remove duplicate items (perhaps referenced in an review and a comment) and cut down to size

require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/updates.php';
require_once AS_INCLUDE_DIR . 'app/posts.php';
require_once AS_INCLUDE_DIR . 'util/string.php';

if ($feedtype != 'search' && $feedtype != 'hot') // leave search results and hot items sorted by relevance
	$items = as_any_sort_and_dedupe($items);

$items = array_slice($items, 0, $count);
$blockwordspreg = as_get_block_words_preg();


// Prepare the XML output

$lines = array();

$lines[] = '<?xml version="1.0" encoding="utf-8"?>';
$lines[] = '<rss version="2.0">';
$lines[] = '<channel>';

$lines[] = '<title>' . as_xml($sitetitle . ' - ' . $title) . '</title>';
$lines[] = '<link>' . as_xml(as_path($linkrequest, $linkparams, $siteurl)) . '</link>';
$lines[] = '<description>Powered by AppSmata</description>';

foreach ($items as $item) {
	// Determine whether this is a item, review or comment, and act accordingly
	$options = array('blockwordspreg' => @$blockwordspreg, 'showurllinks' => $showurllinks);

	$time = null;
	$htmlcontent = null;

	if (isset($item['opostid'])) {
		$time = $item['otime'];

		if ($full)
			$htmlcontent = as_viewer_html($item['ocontent'], $item['oformat'], $options);

	} elseif (isset($item['postid'])) {
		$time = $item['created'];

		if ($full)
			$htmlcontent = as_viewer_html($item['content'], $item['format'], $options);
	}

	if ($feedtype == 'search') {
		$titleprefix = '';
		$urlxml = as_xml($item['url']);

	} else {
		switch (@$item['obasetype'] . '-' . @$item['oupdatetype']) {
			case 'Q-':
			case '-':
				$langstring = null;
				break;

			case 'Q-' . AS_UPDATE_VISIBLE:
				$langstring = $item['hidden'] ? 'misc/feed_hidden_prefix' : 'misc/feed_reshown_prefix';
				break;

			case 'Q-' . AS_UPDATE_CLOSED:
				$langstring = as_post_is_closed($item) ? 'misc/feed_closed_prefix' : 'misc/feed_reopened_prefix';
				break;

			case 'Q-' . AS_UPDATE_TAGS:
				$langstring = 'misc/feed_retagged_prefix';
				break;

			case 'Q-' . AS_UPDATE_CATEGORY:
				$langstring = 'misc/feed_recategorized_prefix';
				break;

			case 'A-':
				$langstring = 'misc/feed_a_prefix';
				break;

			case 'A-' . AS_UPDATE_SELECTED:
				$langstring = 'misc/feed_a_selected_prefix';
				break;

			case 'A-' . AS_UPDATE_VISIBLE:
				$langstring = $item['ohidden'] ? 'misc/feed_hidden_prefix' : 'misc/feed_a_reshown_prefix';
				break;

			case 'A-' . AS_UPDATE_CONTENT:
				$langstring = 'misc/feed_a_edited_prefix';
				break;

			case 'C-':
				$langstring = 'misc/feed_c_prefix';
				break;

			case 'C-' . AS_UPDATE_TYPE:
				$langstring = 'misc/feed_c_moved_prefix';
				break;

			case 'C-' . AS_UPDATE_VISIBLE:
				$langstring = $item['ohidden'] ? 'misc/feed_hidden_prefix' : 'misc/feed_c_reshown_prefix';
				break;

			case 'C-' . AS_UPDATE_CONTENT:
				$langstring = 'misc/feed_c_edited_prefix';
				break;

			case 'Q-' . AS_UPDATE_CONTENT:
			default:
				$langstring = 'misc/feed_edited_prefix';
				break;

		}

		$titleprefix = isset($langstring) ? as_lang($langstring) : '';

		$urlxml = as_xml(as_q_path($item['postid'], $item['title'], true, @$item['obasetype'], @$item['opostid']));
	}

	if (isset($blockwordspreg))
		$item['title'] = as_block_words_replace($item['title'], $blockwordspreg);

	// Build the inner XML structure for each item

	$lines[] = '<item>';
	$lines[] = '<title>' . as_xml($titleprefix . $item['title']) . '</title>';
	$lines[] = '<link>' . $urlxml . '</link>';

	if (isset($htmlcontent))
		$lines[] = '<description>' . as_xml($htmlcontent) . '</description>';

	if (isset($item['categoryname']))
		$lines[] = '<category>' . as_xml($item['categoryname']) . '</category>';

	$lines[] = '<guid isPermaLink="true">' . $urlxml . '</guid>';

	if (isset($time))
		$lines[] = '<pubDate>' . as_xml(gmdate('r', $time)) . '</pubDate>';

	$lines[] = '</item>';
}

$lines[] = '</channel>';
$lines[] = '</rss>';


// Disconnect here, once all output is ready to go

as_db_disconnect();


// Output the XML - and we're done!

header('Content-type: text/xml; charset=utf-8');
echo implode("\n", $lines);
