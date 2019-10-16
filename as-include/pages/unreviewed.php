<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for page listing recent items without positiveliked/selected/any reviews


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
require_once AS_INCLUDE_DIR . 'app/p-list.php';


// Get list of unreviewed items, allow per-category if AS_ALLOW_UNINDEXED_QUERIES set in as-config.php

if (AS_ALLOW_UNINDEXED_QUERIES)
	$categoryslugs = as_request_parts(1);
else
	$categoryslugs = null;

$countslugs = @count($categoryslugs);
$by = as_get('by');
$start = as_get_start();
$userid = as_get_logged_in_userid();

switch ($by) {
	case 'selected':
		$selectby = 'selchildid';
		break;

	case 'positivelikes':
		$selectby = 'rmaxlike';
		break;

	default:
		$selectby = 'rcount';
		break;
}

list($items, $categories, $categoryid) = as_db_select_with_pending(
	as_db_unreviewed_qs_selectspec($userid, $selectby, $start, $categoryslugs, false, false, as_opt_if_loaded('page_size_una_qs')),
	AS_ALLOW_UNINDEXED_QUERIES ? as_db_category_nav_selectspec($categoryslugs, false, false, true) : null,
	$countslugs ? as_db_slugs_to_category_id_selectspec($categoryslugs) : null
);

if ($countslugs) {
	if (!isset($categoryid))
		return include AS_INCLUDE_DIR . 'as-page-not-found.php';

	$categorytitlehtml = as_html($categories[$categoryid]['title']);
}

$feedpathprefix = null;
$linkparams = array('by' => $by);

switch ($by) {
	case 'selected':
		if ($countslugs) {
			$sometitle = as_lang_html_sub('main/unselected_qs_in_x', $categorytitlehtml);
			$nonetitle = as_lang_html_sub('main/no_una_articles_in_x', $categorytitlehtml);

		} else {
			$sometitle = as_lang_html('main/unselected_qs_title');
			$nonetitle = as_lang_html('main/no_unselected_qs_found');
			$count = as_opt('cache_unselpcount');
		}
		break;

	case 'positivelikes':
		if ($countslugs) {
			$sometitle = as_lang_html_sub('main/unpositivelikeda_qs_in_x', $categorytitlehtml);
			$nonetitle = as_lang_html_sub('main/no_una_articles_in_x', $categorytitlehtml);

		} else {
			$sometitle = as_lang_html('main/unpositivelikeda_qs_title');
			$nonetitle = as_lang_html('main/no_unpositivelikeda_qs_found');
			$count = as_opt('cache_unupapcount');
		}
		break;

	default:
		$feedpathprefix = as_opt('feed_for_unreviewed') ? 'unreviewed' : null;
		$linkparams = array();

		if ($countslugs) {
			$sometitle = as_lang_html_sub('main/unreviewed_qs_in_x', $categorytitlehtml);
			$nonetitle = as_lang_html_sub('main/no_una_articles_in_x', $categorytitlehtml);

		} else {
			$sometitle = as_lang_html('main/unreviewed_qs_title');
			$nonetitle = as_lang_html('main/no_una_articles_found');
			$count = as_opt('cache_unapcount');
		}
		break;
}


// Prepare and return content for theme

$as_content = as_p_list_page_content(
	$items, // items
	as_opt('page_size_una_qs'), // items per page
	$start, // start offset
	@$count, // total count
	$sometitle, // title if some items
	$nonetitle, // title if no items
	AS_ALLOW_UNINDEXED_QUERIES ? $categories : array(), // categories for navigation (null if not shown on this page)
	AS_ALLOW_UNINDEXED_QUERIES ? $categoryid : null, // selected category id (null if not relevant)
	false, // show item counts in category navigation
	AS_ALLOW_UNINDEXED_QUERIES ? 'unreviewed/' : null, // prefix for links in category navigation (null if no navigation)
	$feedpathprefix, // prefix for RSS feed paths (null to hide)
	as_html_suggest_qs_tags(as_using_tags()), // suggest what to do next
	$linkparams, // extra parameters for page links
	$linkparams // category nav params
);

$as_content['navigation']['sub'] = as_unreviewed_sub_navigation($by, $categoryslugs);


return $as_content;
