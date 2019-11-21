$manager['firstname']<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax single clicks on comments


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
require_once AS_INCLUDE_DIR . 'APS/as-views.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'util/string.php';
require_once AS_INCLUDE_DIR . 'app/users.php';

$insearchitem = as_post_text('searchtext');
$businessid = as_post_text('item_biz');

$userid = as_get_logged_in_userid();

list ($itemresults, $customers) = as_db_select_with_pending( 
	as_db_products_selectspec('title', $businessid, $insearchitem),
	as_db_recent_customers($businessid)
);

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '<div class="box-body">';
$htmlresult .= '<ul class="products-list product-list-in-box">';

foreach ($itemresults as $result) 
{
	$texture = explode(';', $result['texture']);
	$result['actual_stock'] = (isset($result['actual']) ? $result['actual'] : 0);
	$result['available_stock'] = (isset($result['available']) ? $result['available'] : 0);
	$htmlresult .= '<li class="item list-item-result" alt="Click to Proceed with Stock Entry" onclick="as_show_quick_form(\'get_form_'.$result['postid'].'\')">';
	$htmlresult .= '<div class="product-img">'.as_get_media_html($result['icon'], 200, 200).'</div>';
	$htmlresult .= '<div class="product-info">';
	$htmlresult .= '<span class="product-title" style="font-size: 20px;">';
	$htmlresult .= '<span style="color: #006400;">'.$result['category'] . (isset($result['parentcat']) ? ' ' .$result['parentcat'] : ''). '</span>';
	$htmlresult .= ' - <span style="color: #f00;">' . $result['itemcode']. '</span></span>';
	
	$htmlresult .= '<table style="width:100%;"><tr><td>';
	$htmlresult .= '<span class="product-description" style="color: #000; width:320px;"><span style="font-size: 18px;"><b>'.$result['title'].':</b></span> ';
	if ($result['content'] != '') $htmlresult .= '<span style="color: #151B8D; font-size: 18px;">' . $result['content'] . '</span>';
	$htmlresult .= '<table><tr><td><b>Volume</b></td><td><b> : </b></td><td> ' .$result['volume'].'</td></tr>';
	$htmlresult .= '<tr><td><b>Mass</b></td><td><b> : </b></td><td> ' . $result['mass'].'</td></tr>';
	$htmlresult .= '<tr><td><b>Texture</b></td><td><b> / </b></td><td> <b>Color: '.$texture[0].' </b>; <span style="color: #151B8D; font-weight: bold;">Pattern: ' . $texture[1].'</span></td></tr>';
	$htmlresult .= '</table></span></td><td>';

	$htmlresult .= '<table><tr><td><span class="label label-info pull-right" style="width: 100px;"><b>ACTUAL</b><br><span id="get_actual_'.$result['postid'].'" style="font-size: 22px">'.$result['actual_stock']. '</span></span><br></td></tr>';
	$htmlresult .= '<tr><td><span class="label label-warning pull-right" style="width: 100px;"><b>AVAILABLE</b><br><span id="get_available_'.$result['postid'].'" style="font-size: 22px">'.$result['available_stock']. '</span></span></td></tr></table>';
	
	$htmlresult .= '</td></tr></table></li>';
	
	$htmlresult .= '<li id="get_form_'.$result['postid'].'" style="display:none;">';	
	$htmlresult .= '<div id="get_itemresults_'.$result['postid'].'">';
	$htmlresult .= '<div class="nav-tabs-custom">';

	$htmlresult .= '<ul class="nav nav-tabs pull-right">';
	$htmlresult .= '<li class="active"><a href="#stock-entry" data-toggle="tab">RECEIVE</a></li>';
	$htmlresult .= '<li><a href="#stock-exit" data-toggle="tab">ISSUE</a></li>';
	$htmlresult .= '<li><a href="#stock-history" data-toggle="tab">HISTORY</a></li>';
	$htmlresult .= '<li class="pull-left header"><i class="fa fa-info"></i>STOCK ACTIONS</li>';
	$htmlresult .= '</ul>';

	$htmlresult .= '<div class="tab-content no-padding">';

	$htmlresult .= '<div class="tab-pane active" id="stock-entry" style="position: relative;">';	
	$htmlresult .= as_stock_add_form('get', $result).'</div>';

	$htmlresult .= '<div class="tab-pane" id="stock-exit" style="position: relative;">';	
	$htmlresult .= as_stock_issue_form('give', $result, $customers).'</div>';
	
	$htmlresult .= '<div class="tab-pane" id="stock-history" style="position: relative;">';
	
	$stockids = as_db_find_by_stockitem($result['postid'], $businessid);
	if (count($stockids))
	{
		$history = as_db_select_with_pending( as_db_product_stock_activity($stockids[0]));
		$htmlresult .= as_stock_history($history, $result['available']) . '</div>';
	}
	else
	{
		$htmlresult .= '<h3>No Stock History for this product at the moment</h3></div>';
	}

	$htmlresult .= '</div>';
	$htmlresult .= '</div>';
	$htmlresult .= '</div>';
	$htmlresult .= '</li>';
}

$htmlresult .= '</div>';
echo $htmlresult;