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

require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'util/string.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'APS/as-views.php';

$insearchitem = as_post_text('searchtext');
$businessid = as_post_text('item_biz');

$userid = as_get_logged_in_userid();
$itemresults = as_db_select_with_pending( as_db_products_selectspec('title', $businessid, $insearchitem));

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '<div class="box-body">';
$htmlresult .= '<ul class="products-list product-list-in-box">';

foreach ($itemresults as $result) 
{
	$result['stock'] = (isset($result['quantity']) ? $result['quantity'] : 0);
	$htmlresult .= '<li class="item stock-item-result" alt="Click to Proceed with Stock Entry" onclick="as_show_quick_form('.$result['postid'].')">';
	$htmlresult .= '<div class="product-img">'.as_get_media_html($result['icon'], 200, 200).'</div>';
	$htmlresult .= '<div class="product-info">';
	$htmlresult .= '<span class="product-title" style="font-size: 20px;"><span style="color: #006400;">'.$result['category']. '</span> - ';
	$htmlresult .= '<span style="color: #f00;">' . $result['itemcode']. '</span> - ' .$result['title'].'</span>';
	
	$htmlresult .= '<span class="label label-info pull-right"><h5>QTY</h5><span style="font-size: 30px">'.$product['stock']. '</span></span>';
	$htmlresult .= '<span class="product-description">';
	if ($result['content'] != '') $htmlresult .= '<span style="color: #151B8D; font-size: 22px;">' . $result['content'] . '</span>';
	$htmlresult .= '<table><tr><td><b>VOLUME</b></td><td><b> : </b></td><td> ' .$result['volume'].'</td></tr>';
	$htmlresult .= '<tr><td><b>MASS</b></td><td><b> : </b></td><td> ' . $result['mass'].'</td></tr>';
	$htmlresult .= '<tr><td><b>TEXTURE</b></td><td><b> : </b></td><td> ' . $result['texture'].'</td></tr>';
	$htmlresult .= '</table></span>';	
	$htmlresult .= '</li>';
	
	$htmlresult .= '<li id="form_'.$result['postid'].'" style="display:none; background: #eee;">';	
	$htmlresult .= '<div id="itemresults_'.$result['postid'].'"></div>';
	$htmlresult .= '<div class="nav-tabs-custom">';

	$htmlresult .= '<ul class="nav nav-tabs pull-right">';
	$htmlresult .= '<li class="active"><a href="#stock-entry" data-toggle="tab">ADD STOCK</a></li>';
	$htmlresult .= '<li><a href="#stock-history" data-toggle="tab">STOCK HISTORY</a></li>';
	$htmlresult .= '<li class="pull-left header"><i class="fa fa-info"></i> ACTIONS</li>';
	$htmlresult .= '</ul>';

	$htmlresult .= '<div class="tab-content no-padding">';

	$htmlresult .= '<div class="tab-pane active" id="stock-entry" style="position: relative;">';	
	$htmlresult .= as_stock_add_form($result).'</div>';

	$htmlresult .= '<div class="chart tab-pane" id="stock-history" style="position: relative;">';
	
	$stockids = as_db_find_by_stockitem($result['postid'], $businessid);
	if (count($stockids))
	{
		$history = as_db_select_with_pending( as_db_product_stock_activity($stockids[0]));
		$htmlresult .= as_stock_history($history) . '</div>';
	}
	else
	{
		$htmlresult .= '<h3>No Stock History for this product at the moment</h3></div>';
	}

	$htmlresult .= '</div>';
	$htmlresult .= '</div>';
	$htmlresult .= '</li>';
}

$htmlresult .= '</div>';
echo $htmlresult;