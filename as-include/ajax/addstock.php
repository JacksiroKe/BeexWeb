$manager['firstname']<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax single clicks on stock


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

require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'APS/as-views.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'db/post-create.php';
require_once AS_INCLUDE_DIR . 'db/post-update.php';

$product = array();
$userid = as_get_logged_in_userid();
$bprice = as_post_text('item_bprice');
$sprice = as_post_text('item_sprice');
$business = as_post_text('item_biz');
$type = as_post_text('item_type');
$state = as_post_text('item_state');
	
$product['postid'] = (int)as_post_text('item_id');
$product['actual'] = (int)as_post_text('item_actual');
$product['available'] = (int)as_post_text('item_available');

$stockids = as_db_find_by_stockitem($product['postid'], $business);
if (count($stockids))
{
	$stockid = $stockids[0];
	as_db_stock_update($stockid, $userid, $product['available']);
}
else
{
	$stockid = as_db_stock_add($type, $business, $product['postid'], $userid, $product['actual'], $product['available']);
}

as_db_stock_entry('ENTRY', $stockid, $product['postid'], $userid, $product['actual'], $bprice, $sprice, $state);

list ($customers, $history) = as_db_select_with_pending( 
	as_db_recent_customers($business),
	as_db_product_stock_activity($stockid)
);

echo "AS_AJAX_RESPONSE\n1\n";
$htmlresult = '<div class="alert alert-success alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> ';
$htmlresult .= 'Stock for the item added successfully!</div>';
$htmlresult .= '<div class="nav-tabs-custom">';

$htmlresult .= '<ul class="nav nav-tabs pull-right">';
$htmlresult .= '<li><a href="#stock-entry" data-toggle="tab">RECEIVE</a></li>';
$htmlresult .= '<li><a href="#stock-exit" data-toggle="tab">ISSUE</a></li>';
$htmlresult .= '<li class="active"><a href="#stock-history" data-toggle="tab">HISTORY</a></li>';
$htmlresult .= '<li class="pull-left header"><i class="fa fa-info"></i>STOCK ACTIONS</li>';
$htmlresult .= '</ul>';

$htmlresult .= '<div class="tab-content no-padding">';

$htmlresult .= '<div class="tab-pane" id="stock-entry" style="position: relative;">';	
$htmlresult .= as_stock_add_form('get', $product).'</div>';

$htmlresult .= '<div class="tab-pane" id="stock-exit" style="position: relative;">';	
$htmlresult .= as_stock_issue_form('give', $product, $customers);
$htmlresult .= '</div>';

$htmlresult .= '<div class="tab-pane active" id="stock-history" style="position: relative;">';


$htmlresult .= as_stock_history($history, $product['available']) . '</div>';

$htmlresult .= '</div>';
$htmlresult .= '</div>xqx' . $product['available'] . 'xqx' . $product['actual'];

echo $htmlresult;
