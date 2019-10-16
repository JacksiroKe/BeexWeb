<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax request based on write a item title


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

require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'util/string.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'app/format.php';


// Collect the information we need from the database

$intitle = as_post_text('title');
$dowritecheck = as_opt('do_write_check_qs');
$doexampletags = as_using_tags() && as_opt('do_example_tags');

if ($dowritecheck || $doexampletags) {
	$countqs = max($doexampletags ? AS_DB_RETRIEVE_ASK_TAG_QS : 0, $dowritecheck ? as_opt('page_size_write_check_qs') : 0);

	$relatedarticles = as_db_select_with_pending(
		as_db_search_posts_selectspec(null, as_string_to_words($intitle), null, null, null, null, 0, false, $countqs)
	);
}


// Collect example tags if appropriate

if ($doexampletags) {
	$tagweight = array();
	foreach ($relatedarticles as $item) {
		$tags = as_tagstring_to_tags($item['tags']);
		foreach ($tags as $tag) {
			@$tagweight[$tag] += exp($item['score']);
		}
	}

	arsort($tagweight, SORT_NUMERIC);

	$exampletags = array();

	$minweight = exp(as_match_to_min_score(as_opt('match_example_tags')));
	$maxcount = as_opt('page_size_write_tags');

	foreach ($tagweight as $tag => $weight) {
		if ($weight < $minweight)
			break;

		$exampletags[] = $tag;
		if (count($exampletags) >= $maxcount)
			break;
	}
} else {
	$exampletags = array();
}


// Output the response header and example tags

echo "AS_AJAX_RESPONSE\n1\n";

echo strtr(as_html(implode(',', $exampletags)), "\r\n", '  ') . "\n";


// Collect and output the list of related items

if ($dowritecheck) {
	$minscore = as_match_to_min_score(as_opt('match_write_check_qs'));
	$maxcount = as_opt('page_size_write_check_qs');

	$relatedarticles = array_slice($relatedarticles, 0, $maxcount);
	$limitedarticles = array();

	foreach ($relatedarticles as $item) {
		if ($item['score'] < $minscore)
			break;

		$limitedarticles[] = $item;
	}

	$themeclass = as_load_theme_class(as_get_site_theme(), 'ajax-writetitle', null, null);
	$themeclass->initialize();
	$themeclass->q_write_similar($limitedarticles, as_lang_html('item/write_same_q'));
}
