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

$itemid = as_post_text('item_id');
$quantity = as_post_text('item_qty');
$bprice = as_post_text('item_bprice');
$sprice = as_post_text('item_sprice');
$business = as_post_text('item_biz');
$type = as_post_text('item_type');
$state = as_post_text('item_cdn');

$userid = as_get_logged_in_userid();

$stockids = as_db_find_by_stockitem($itemid, $business);
if (count($stockids))
{
	$stockid = $stockids[0];
   as_db_stock_entry('ENTRY', $stockids[0], $itemid, $userid, $quantity, $bprice, $sprice, $state);
}
else
{
    $stockid = as_db_stock_add($type, $business, $itemid, $userid, $quantity);
    as_db_stock_entry('ENTRY', $stockid, $itemid, $userid, $quantity, $bprice, $sprice, $state);
}

echo "AS_AJAX_RESPONSE\n1\n";
$htmlresult = '<div class="alert alert-success alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> ';
$htmlresult .= 'Stock for the item added successfully!</div>';
$htmlresult .= '<div class="nav-tabs-custom">';

$htmlresult .= '<ul class="nav nav-tabs pull-right">';
$htmlresult .= '<li><a href="#stock-entry" data-toggle="tab">ADD STOCK</a></li>';
$htmlresult .= '<li class="active"><a href="#stock-history" data-toggle="tab">STOCK HISTORY</a></li>';
$htmlresult .= '<li class="pull-left header"><i class="fa fa-info"></i> ACTIONS</li>';
$htmlresult .= '</ul>';

$htmlresult .= '<div class="tab-content no-padding">';

$htmlresult .= '<div class="tab-pane" id="stock-entry" style="position: relative;">';	
$htmlresult .= as_stock_add_form($result).'</div>';

$htmlresult .= '<div class="tab-pane active" id="stock-history" style="position: relative;">';

if (count($stockid))
{
	$history = as_db_select_with_pending( as_db_product_stock_activity($stockid));
	$htmlresult .= as_stock_history($history) . '</div>';
}
else
{
	$htmlresult .= '<h3>No Stock History for this product at the moment</h3></div>';
}

$htmlresult .= '</div>';
$htmlresult .= '</div>xqx';

$htmlresult .= '<table><tr><td><span class="label label-info pull-right" style="width: 100px;"><b>ACTUAL</b><br><span style="font-size: 22px">'.$result['stock']. '</span></span><br></td></tr>';
$htmlresult .= '<tr><td><span class="label label-warning pull-right" style="width: 100px;"><b>AVAILABLE</b><br><span style="font-size: 22px">'.$result['stock']. '</span></span></td></tr></table>';

echo $htmlresult;
