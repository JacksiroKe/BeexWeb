<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for page listing recent items


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

if (AS_USER_TYPE_FULL == 'User') {
	as_redirect('signup-supplier', array('to' => 'products'));
}
require_once AS_INCLUDE_DIR.'db/selects.php';

$as_content = as_content_prepare();

$request = as_request_part(1);

$in = array();

$followpostid = as_get('follow');
$in['categoryid'] = as_clicked('dopost') ? as_get_category_field_value('category') : as_get('cat');
$userid = as_get_logged_in_userid();

$selectsort = 'created';
$start = as_get_start();
$editproductid = as_get('edit');

list($products, $categories) = as_db_select_with_pending(
	as_db_question_selectspec($userid, $selectsort, $start),
	as_db_category_nav_selectspec($editproductid, true, false, true)
);

if (!isset($categories[$in['categoryid']])) {
	$in['categoryid'] = null;
}

$catslist['All Categories'] = array('litem', 'fa fa-filter');

if (count($categories)) {
	foreach ($categories as $category) {
		$catslist[$category['title']] = array('litem', 'fa fa-filter');
	}
}

$categorylist = array( 'type' => 'box', 'theme' => 'primary', 'title' => 'Categories', 
	'tools' => array('minus' => array('type' => 'button', 'class' => 'tool', 'data-widget' => 'collapse')),
	'body' => array('type' => 'box-body no-padding'));
$categorylist['body']['items'][] = array(
	'tag' => array('ul', 'nav nav-pills nav-stacked'), 
	'data' => array( 'sub-data' => $catslist ),
);

switch ( $request ) {
	case 'sell':
		$as_content['title'] = 'Sell on Beex';
		
		$sellnow = array(
			'title' => 'Sell on Beex',
			'type' => 'form',
			'style' => 'tall',
			'tags' => 'method="post" action="' . as_self_html() . '"',

			'fields' => array(				
				'category' => array(
					'label' => as_lang_html('item/i_category_label'),
					'error' => as_html(@$errors['categoryid']),
				),
				
				'icon' => array(
					'label' => as_lang_html('item/i_icon_label'),
					'tags' => 'name="icon" id="icon" autocomplete="on"',
					'value' => as_html(@$in['icon']),
					'type' => 'file',
					'error' => as_html(@$errors['icon']),
				),
				
				'description' => array(
					'label' => 'Describe your Item',
					'tags' => 'name="description" id="description" dir="auto"',
					'value' => as_html(@$in['description']),
					'error' => as_html(@$errors['description']),
				),

				'price' => array(
					'label' => 'Selling Price (KSh):',
					'type' => 'number',
					'tags' => 'name="price" id="price" dir="auto" min="500" max="100000"',
					'value' => as_html(@$in['price']),
					'error' => as_html(@$errors['price']),
				),

			),

			'buttons' => array(
				'sellnow' => array(
					'tags' => 'onclick="as_show_waiting_after(this, false);"',
					'label' => as_lang_html('main/sellnow_button'),
				),
			),

			'hidden' => array(
				'dosellnow' => '1',
				'code' => as_get_form_security_code('sellnow'),
			),
		);
		
		as_set_up_category_field($as_content, $sellnow['fields']['category'], 'category', $categories, $in['categoryid'], true, as_opt('allow_no_sub_category'));
		
		$as_content['row_view'][] = array(
			'colms' => array(
				0 => array('class' => 'col-md-3',
					'extras' => array( 
						'<a href="'.as_path_html('products').'" class="btn btn-primary btn-block margin-bottom">Back to Products</a>'
					),
					'c_items' => array($categorylist),
				),
				1 => array('class' => 'col-md-9', 'c_items' => array($sellnow) ),
			),
		);
		break;
		
	default:
		$as_content['title'] = 'Products <small>Sell on Beex</small>';

		$carousel = array( 'type' => 'carousel', 'id' => 'carousel-example-generic', 'title' => 'Featured Products');

		$carousel['body']['indicators'] = array(
			'data-target' => 'carousel-example-generic',
			'slides' => array( 0 => 'active', 1 => '', 2 => '' ),
		);

		$carousel['body']['slides'][] = array(
			'class' => 'active',
			'caption' => 'First slide',
			'image' => array('http://placehold.it/900x500/39CCCC/ffffff&text=I+Love+Bootstrap', 'First slide'), 
		);

		$products[] = array();

		$recently = array( 'type' => 'box', 'theme' => 'primary', 'title' => 'Recent Posted Products', 
			'body' => array('type' => 'box-body no-padding'));
		$recently['body']['items'][] = array(
			'tag' => array('ul', 'products-list product-list-in-box'),
			'data' => array( 'sub-data' => $products));
		/*'data' => array(
				'sub-data' => array(
					'All Categories' => array('litem', 'fa fa-filter'),
					'Inbox' => array('litem', 'fa fa-filter'),
					'Sent' => array('litem', 'fa fa-filter', 12),
					'Drafts' => array('litem', 'fa fa-filter'),
					'Junk' => array('litem', 'fa fa-filter', 65),
					'Trash' => array('litem', 'fa fa-filter'),
				),
			),*/
		$as_content['row_view'][] = array(
			'colms' => array(
				0 => array('class' => 'col-md-3',
					'extras' => array( 
						'<a href="'.as_path_html('products/sell').'" class="btn btn-primary btn-block margin-bottom">Sell on Beex</a>'
					),
					'c_items' => array($categorylist),
				),
				//1 => array('class' => 'col-md-6', 'c_items' => array($recently) ),
				
				//2 => array('class' => 'col-md-3', 'c_items' => array($recently) ),
			),
		);
		break;
}
return $as_content;
