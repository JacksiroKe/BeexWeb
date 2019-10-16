<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for most order listing pages, plus custom pages and plugin pages


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
 * Returns the $as_content structure for a order list page showing $orders retrieved from the
 * database. If $pagesize is not null, it sets the max number of orders to display. If $count is
 * not null, pagination is determined by $start and $count. The page title is $sometitle unless
 * there are no orders shown, in which case it's $nonetitle. $navcategories should contain the
 * categories retrived from the database using as_db_category_nav_selectspec(...) for $categoryid,
 * which is the current category shown. If $categorypathprefix is set, category navigation will be
 * shown, with per-category order counts if $categorypcount is true. The nav links will have the
 * prefix $categorypathprefix and possible extra $categoryparams. If $feedpathprefix is set, the
 * page has an RSS feed whose URL uses that prefix. If there are no links to other pages, $suggest
 * is used to suggest what the user should do. The $pagelinkparams are passed through to
 * as_html_page_links(...) which creates links for page 2, 3, etc..
 * @param $orders
 * @param $pagesize
 * @param $start
 * @param $count
 * @param $sometitle
 * @param $nonetitle
 * @param $navcategories
 * @param $categoryid
 * @param $categorypcount
 * @param $categorypathprefix
 * @param $feedpathprefix
 * @param $suggest
 * @param $pagelinkparams
 * @param $categoryparams
 * @param $dummy
 * @return array
 */
function as_o_list_page_content($orders, $pagesize, $start, $count, $sometitle, $nonetitle,
	$navcategories, $categoryid, $categorypcount, $categorypathprefix, $feedpathprefix, $suggest,
	$pagelinkparams = null, $categoryparams = null, $dummy = null)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'app/format.php';
	require_once AS_INCLUDE_DIR . 'app/updates.php';
	require_once AS_INCLUDE_DIR . 'app/posts.php';

	$userid = as_get_logged_in_userid();


	// Chop down to size, get user information for display

	if (isset($pagesize)) {
		$orders = array_slice($orders, 0, $pagesize);
	}

	$usershtml = as_userids_handles_html(as_any_get_userids_handles($orders));


	// Prepare content for theme

	$as_content = as_content_prepare(true, array_keys(as_category_path($navcategories, $categoryid)));

	$as_content['p_list']['form'] = array(
		'tags' => 'method="post" action="' . as_self_html() . '"',

		'hidden' => array(
			'code' => as_get_form_security_code('like'),
		),
	);

	$as_content['p_list']['ps'] = array();

	if (!empty($orders)) {
		$as_content['title'] = $sometitle;

		$defaults = as_post_html_defaults('P');
		if (isset($categorypathprefix)) {
			$defaults['categorypathprefix'] = $categorypathprefix;
		}

		foreach ($orders as $order) {
			$fields = as_any_to_q_html_fields($order, $userid, as_cookie_get(), $usershtml, null, as_post_html_options($order, $defaults));

			if (as_post_is_closed($order)) {
				$fields['closed'] = array(
					'state' => as_lang_html('main/closed'),
				);
			}

			$as_content['p_list']['ps'][] = $fields;
		}
	} else {
		$as_content['title'] = $nonetitle;
	}

	if (isset($userid) && isset($categoryid)) {
		$favoritemap = as_get_favorite_non_qs_map();
		$categoryisfavorite = @$favoritemap['category'][$navcategories[$categoryid]['backpath']];

		$as_content['favorite'] = as_favorite_form(AS_ENTITY_CATEGORY, $categoryid, $categoryisfavorite,
			as_lang_sub($categoryisfavorite ? 'main/remove_x_favorites' : 'main/add_category_x_favorites', $navcategories[$categoryid]['title']));
	}

	if (isset($count) && isset($pagesize)) {
		$as_content['page_links'] = as_html_page_links(as_request(), $start, $pagesize, $count, as_opt('pages_prev_next'), $pagelinkparams);
	}

	$as_content['canonical'] = as_get_canonical();

	if (empty($as_content['page_links'])) {
		$as_content['suggest_next'] = $suggest;
	}

	if (as_using_categories() && count($navcategories) && isset($categorypathprefix)) {
		$as_content['navigation']['cat'] = as_category_navigation($navcategories, $categoryid, $categorypathprefix, $categorypcount, $categoryparams);
	}

	// set meta description on category pages
	if (!empty($navcategories[$categoryid]['content'])) {
		$as_content['description'] = as_html($navcategories[$categoryid]['content']);
	}

	if (isset($feedpathprefix) && (as_opt('feed_per_category') || !isset($categoryid))) {
		$as_content['feed'] = array(
			'url' => as_path_html(as_feed_request($feedpathprefix . (isset($categoryid) ? ('/' . as_category_path_request($navcategories, $categoryid)) : ''))),
			'label' => strip_tags($sometitle),
		);
	}

	return $as_content;
}


/**
 * Return the sub navigation structure common to order listing pages
 * @param $sort
 * @param $categoryslugs
 * @return array
 */
function as_os_sub_navigation($sort, $categoryslugs)
{
	$request = 'orders';

	if (isset($categoryslugs)) {
		foreach ($categoryslugs as $slug) {
			$request .= '/' . $slug;
		}
	}

	$navigation = array(
		'recent' => array(
			'label' => as_lang('main/nav_most_recent'),
			'url' => as_path_html($request),
		),

		'dashboard' => array(
			'label' => as_lang('main/nav_dashboard'),
			'url' => as_path_html($request.'/dashboard'),
		),
	);

	if (isset($navigation[$sort])) {
		$navigation[$sort]['selected'] = true;
	} else {
		$navigation['recent']['selected'] = true;
	}

	if (!as_opt('do_count_q_views')) {
		unset($navigation['views']);
	}

	return $navigation;
}


/**
 * Return the sub navigation structure common to unreviewed pages
 * @param $by
 * @param $categoryslugs
 * @return array
 */
function as_unreviewed_sub_navigation($by, $categoryslugs)
{
	$request = 'unreviewed';

	if (isset($categoryslugs)) {
		foreach ($categoryslugs as $slug) {
			$request .= '/' . $slug;
		}
	}

	$navigation = array(
		'by-reviews' => array(
			'label' => as_lang('main/nav_no_review'),
			'url' => as_path_html($request),
		),

		'by-selected' => array(
			'label' => as_lang('main/nav_no_selected_review'),
			'url' => as_path_html($request, array('by' => 'selected')),
		),

		'by-positivelikes' => array(
			'label' => as_lang('main/nav_no_positiveliked_review'),
			'url' => as_path_html($request, array('by' => 'positivelikes')),
		),
	);

	if (isset($navigation['by-' . $by])) {
		$navigation['by-' . $by]['selected'] = true;
	} else {
		$navigation['by-reviews']['selected'] = true;
	}

	if (!as_opt('voting_on_as')) {
		unset($navigation['by-positivelikes']);
	}

	return $navigation;
}
