<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for page listing recent orders


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

require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/o-list.php';

$categoryslugs = as_request_parts(1);
$countslugs = count($categoryslugs);

$sort = ($countslugs && !AS_ALLOW_UNINDEXED_QUERIES) ? null : as_get('sort');
$start = as_get_start();
$userid = as_get_logged_in_userid();


// Get list of orders, plus category information

switch ($sort) {
	case 'hot':
		$selectsort = 'hotness';
		break;

	case 'likes':
		$selectsort = 'netlikes';
		break;

	case 'reviews':
		$selectsort = 'rcount';
		break;

	case 'views':
		$selectsort = 'views';
		break;

	default:
		$selectsort = 'created';
		break;
}

list($orders, $categories, $categoryid) = as_db_select_with_pending(
	as_db_os_selectspec($userid, as_opt_if_loaded('page_size_qs'), $start),
	as_db_category_nav_selectspec($categoryslugs, false, false, true),
	$countslugs ? as_db_slugs_to_category_id_selectspec($categoryslugs) : null
);

if ($countslugs) {
	if (!isset($categoryid)) {
		return include AS_INCLUDE_DIR . 'as-page-not-found.php';
	}

	$categorytitlehtml = as_html($categories[$categoryid]['title']);
	$nonetitle = as_lang_html_sub('main/no_orders_in_x', $categorytitlehtml);

} else {
	$nonetitle = as_lang_html('main/no_orders_found');
}


$categorypathprefix = AS_ALLOW_UNINDEXED_QUERIES ? 'orders/' : null; // this default is applied if sorted not by recent
$feedpathprefix = null;
$linkparams = array('sort' => $sort);

switch ($sort) {
	case 'hot':
		$sometitle = $countslugs ? as_lang_html_sub('main/hot_os_in_x', $categorytitlehtml) : as_lang_html('main/hot_os_title');
		$feedpathprefix = as_opt('feed_for_hot') ? 'hot' : null;
		break;

	case 'likes':
		$sometitle = $countslugs ? as_lang_html_sub('main/liked_os_in_x', $categorytitlehtml) : as_lang_html('main/liked_os_title');
		break;

	case 'reviews':
		$sometitle = $countslugs ? as_lang_html_sub('main/reviewed_os_in_x', $categorytitlehtml) : as_lang_html('main/reviewed_os_title');
		break;

	case 'views':
		$sometitle = $countslugs ? as_lang_html_sub('main/viewed_os_in_x', $categorytitlehtml) : as_lang_html('main/viewed_os_title');
		break;

	case 'dashboard':
		$sometitle = $countslugs ? as_lang_html_sub('main/viewed_os_in_x', $categorytitlehtml) : as_lang_html('main/viewed_os_title');
		break;

	default:
		$linkparams = array();
		$sometitle = $countslugs ? as_lang_html_sub('main/recent_os_in_x', $categorytitlehtml) : as_lang_html('main/recent_os_title');
		$categorypathprefix = 'orders/';
		$feedpathprefix = as_opt('feed_for_articles') ? 'orders' : null;
		break;
}


// Prepare and return content for theme

$as_content = as_o_list_page_content(
	$orders, // orders
	as_opt('page_size_qs'), // orders per page
	$start, // start offset
	$countslugs ? $categories[$categoryid]['pcount'] : as_opt('cache_pcount'), // total count
	$sometitle, // title if some orders
	$nonetitle, // title if no orders
	$categories, // categories for navigation
	$categoryid, // selected category id
	true, // show item counts in category navigation
	$categorypathprefix, // prefix for links in category navigation
	$feedpathprefix, // prefix for RSS feed paths
	$countslugs ? as_html_suggest_qs_tags(as_using_tags()) : as_html_suggest_write($categoryid), // suggest what to do next
	$linkparams, // extra parameters for page links
	$linkparams // category nav params
);

if (AS_ALLOW_UNINDEXED_QUERIES || !$countslugs) {
	$as_content['navigation']['sub'] = as_os_sub_navigation($sort, $categoryslugs);
}


return $as_content;
