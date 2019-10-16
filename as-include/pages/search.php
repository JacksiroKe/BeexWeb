<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for search page


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

require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/options.php';
require_once AS_INCLUDE_DIR . 'app/search.php';


// Perform the search if appropriate

if (strlen(as_get('q'))) {
	// Pull in input parameters
	$inquery = trim(as_get('q'));
	$userid = as_get_logged_in_userid();
	$start = as_get_start();

	$display = as_opt_if_loaded('page_size_search');
	$count = 2 * (isset($display) ? $display : AS_DB_RETRIEVE_QS_AS) + 1;
	// get enough results to be able to give some idea of how many pages of search results there are

	// Perform the search using appropriate module

	$results = as_get_search_results($inquery, $start, $count, $userid, false, false);

	// Count and truncate results

	$pagesize = as_opt('page_size_search');
	$gotcount = count($results);
	$results = array_slice($results, 0, $pagesize);

	// Retrieve extra information on users

	$fullarticles = array();

	foreach ($results as $result) {
		if (isset($result['item']))
			$fullarticles[] = $result['item'];
	}

	$usershtml = as_userids_handles_html($fullarticles);

	// Report the search event

	as_report_event('search', $userid, as_get_logged_in_handle(), as_cookie_get(), array(
		'query' => $inquery,
		'start' => $start,
	));
}


// Prepare content for theme

$as_content = as_content_prepare(true);

if (strlen(as_get('q'))) {
	$as_content['search']['value'] = as_html($inquery);

	if (count($results))
		$as_content['title'] = as_lang_html_sub('main/results_for_x', as_html($inquery));
	else
		$as_content['title'] = as_lang_html_sub('main/no_results_for_x', as_html($inquery));

	$as_content['p_list']['form'] = array(
		'tags' => 'method="post" action="' . as_self_html() . '"',

		'hidden' => array(
			'code' => as_get_form_security_code('like'),
		),
	);

	$as_content['p_list']['ps'] = array();

	$qdefaults = as_post_html_defaults('P');

	foreach ($results as $result) {
		if (!isset($result['item'])) { // if we have any non-item results, display with less statistics
			$qdefaults['likeview'] = false;
			$qdefaults['reviewsview'] = false;
			$qdefaults['viewsview'] = false;
			break;
		}
	}

	foreach ($results as $result) {
		if (isset($result['item'])) {
			$fields = as_post_html_fields($result['item'], $userid, as_cookie_get(),
				$usershtml, null, as_post_html_options($result['item'], $qdefaults));
		} elseif (isset($result['url'])) {
			$fields = array(
				'what' => as_html($result['url']),
				'meta_order' => as_lang_html('main/meta_order'),
			);
		} else {
			continue; // nothing to show here
		}

		if (isset($qdefaults['blockwordspreg']))
			$result['title'] = as_block_words_replace($result['title'], $qdefaults['blockwordspreg']);

		$fields['title'] = as_html($result['title']);
		$fields['url'] = as_html($result['url']);

		$as_content['p_list']['ps'][] = $fields;
	}

	$as_content['page_links'] = as_html_page_links(as_request(), $start, $pagesize, $start + $gotcount,
		as_opt('pages_prev_next'), array('q' => $inquery), $gotcount >= $count);

	if (as_opt('feed_for_search')) {
		$as_content['feed'] = array(
			'url' => as_path_html(as_feed_request('search/' . $inquery)),
			'label' => as_lang_html_sub('main/results_for_x', as_html($inquery)),
		);
	}

	if (empty($as_content['page_links']))
		$as_content['suggest_next'] = as_html_suggest_qs_tags(as_using_tags());

} else
	$as_content['error'] = as_lang_html('main/search_explanation');


return $as_content;
