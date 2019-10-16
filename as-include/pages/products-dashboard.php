<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for page listing categories


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


$categoryslugs = as_request_parts(1);
$countslugs = count($categoryslugs);

// Get information about appropriate categories and redirect to items page if category has no sub-categories

$userid = as_get_logged_in_userid();
$categories = as_db_select_with_pending(as_db_category_selectspec());

// Prepare content for theme

$as_content = as_content_prepare();

$as_content['title'] = as_lang_html('main/dashboard_stock');

if (count($categories)) {
	$as_content['script_src'][] = 'as-content/as-tables.js?'.AS_VERSION;
	
	$as_content['listing'] = array(
		'items' => array(),
		'checker' => '',
		'headers' => array(	
			'<center>'.as_lang('options/label_item').'</center>',
			'<center>'.as_lang('options/label_stock').'</center>',
			'<center>'.as_lang('options/label_stockout').'</center>',
			'<center>'.as_lang('main/last_update').'</center>',
		),
	);
	
	$parents = array();
	
	foreach ($categories as $getparent) {
		if ($getparent['childcount']) 
			$parents[$getparent['categoryid']] = array( 'name' => $getparent['title'], 'icon' => $getparent['icon']);
	}
	
	foreach ($categories as $category) {
		$itemid = $category['categoryid'];
		$parent = $category['parentid'];
		
		if (!$category['childcount']) {
			$stockitem['onclick'] = ' title="Click on this item to edit or view" onclick="location=\''.as_path_html('stocks/'.$itemid).'\'"';
		
			$stockitem['fields']['checkthis'] = array( 'data' => '<label><input id="chk-item-'. $itemid . '" class="chk-item" name="chk-item-checked[]" type="checkbox" value="'.$itemid. '">'.as_get_media_html(($category['parentid'] ? $parents[$category['parentid']]['icon'] : $category['icon'] ), 30, 30).' </label>' );
			
			$stockitem['fields']['item'] = array( 'data' => '<center>'.$category['title'].($category['parentid'] ? 
				' ' . $parents[$category['parentid']]['name'] : '' ).'</center>');	
			$stockitem['fields']['stock'] = array( 'data' => '<center>'.$category['stock'].'</center>');			
			$stockitem['fields']['stockout'] = array( 'data' => '<center>-</center>' );
			$stockitem['fields']['updated'] = array( 'data' => '<center>-</center>' );
			$as_content['listing']['items'][] = $stockitem;
		}
		
	}
	
	//$as_content['listing']['items'] = as_category_nav_to_browse($stocklist, $categories, $categoryid, $favoritemap);
	
} else {
	$as_content['title'] = as_lang_html('main/no_categories_found');
	$as_content['suggest_next'] = as_html_suggest_qs_tags(as_using_tags());
}

$as_content['navigation']['sub'] = as_qs_sub_navigation('dashboard', null);

return $as_content;
