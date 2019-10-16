<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for page listing orders


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


$orderslugs = as_request_parts(1);
$countslugs = count($orderslugs);

// Get information about appropriate orders and redirect to items page if order has no sub-orders

$userid = as_get_logged_in_userid();
$orders = as_db_select_with_pending(as_db_list_orders_selectspec());

// Prepare content for theme

$as_content = as_content_prepare();

$as_content['title'] = as_lang_html('main/dashboard_orders');

if (count($orders)) {
	$as_content['script_src'][] = 'as-content/as-tables.js?'.AS_VERSION;
	
	$as_content['listing'] = array(
		'items' => array(),
		'checker' => '',
		'headers' => array(
			'<center>'.as_lang('options/label_order').'</center>',
			'<center>'.as_lang('options/label_item').'</center>',
			'<center>'.as_lang('options/label_quantity').'</center>',
			'<center>'.as_lang('options/label_amount').'</center>',
			'<center>'.as_lang('options/label_weight').'</center>',
			'<center>'.as_lang('options/label_customer').'</center>',
			'<center>'.as_lang('options/label_address').'</center>',
			'<center>'.as_lang('options/label_ordered').'</center>',
		),
	);
	
	foreach ($orders as $order) {
		$orderid = $order['orderid'];		
		$amount = (int)$order['quantity'] * (int)$order['saleprice'];
		$weight = (int)$order['quantity'] * (int)$order['weight'];
		 
		$orderitem['onclick'] = ' title="Click on this item to edit or view" onclick="location=\''.as_path_html('orders/'.$orderid).'\'"';
		
		$orderitem['fields']['checkthis'] = array( 'data' => '<label><input id="chk-item-'. $orderid . '" class="chk-item" name="chk-item-checked[]" type="checkbox" value="'.$orderid. '"></label>' );
		
		$orderitem['fields']['orderno'] = array( 'data' => '<center>'.$orderid.'</center>' );
		$orderitem['fields']['item'] = array( 'data' => '<center>'.$order['categoryname'].'</center>');
		
		$orderitem['fields']['quantity'] = array( 'data' => '<center>'.$order['quantity'].' x '.$order['saleprice'].'/=</center>');
		$orderitem['fields']['amount'] = array( 'data' => '<center>'.$amount.'/=</center>');
		$orderitem['fields']['weight'] = array( 'data' => '<center>'.$weight.' kgs</center>');
		$orderitem['fields']['orderedby'] = array( 'data' => '<center>'.$order['orderedby'].'</center>');
		$orderitem['fields']['address'] = array( 'data' => '<center>'.$order['address'].'</center>');
		$orderitem['fields']['created'] = array( 'data' => '<center>'.as_lang_html_sub('main/x_ago', 
					as_html(as_time_to_string(as_opt('db_time') - $order['created']))).'</center>');
		
		$as_content['listing']['items'][] = $orderitem;
	}
	
	
} else {
	$as_content['title'] = as_lang_html('main/no_orders_found');
	$as_content['suggest_next'] = as_html_suggest_qs_tags(as_using_tags());
}

$as_content['navigation']['sub'] = as_os_sub_navigation('dashboard', null);


return $as_content;
