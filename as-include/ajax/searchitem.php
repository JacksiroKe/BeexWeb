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

$insearchitem = as_post_text('searchtext');
$businessid = as_post_text('item_biz');

$userid = as_get_logged_in_userid();
$itemresults = as_db_select_with_pending( as_db_products_selectspec('title', $businessid, $insearchitem));

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '<div class="box-body">';
$htmlresult .= '<ul class="products-list product-list-in-box">';

foreach ($itemresults as $result) 
{
	$htmlresult .= '<li class="item stock-item-result" alt="Click to Proceed with Stock Entry" onclick="as_show_stock_form('.$result['postid'].')">';
	$htmlresult .= '<div class="product-img">'.as_get_media_html($result['icon'], 200, 200).'</div>';
	$htmlresult .= '<div class="product-info">';
	$htmlresult .= '<span class="product-title" style="font-size: 20px;">'.$result['category']. ' - ' . $result['itemcode']. ' [' .$result['title'].']</span>';
	
	$htmlresult .= '<span class="product-description">';
	if ($result['content'] != '') $htmlresult .= $result['content'].'<br>';
	$htmlresult .= '<b>VOLUME:</b>' .$result['volume'].' <b>MASS:</b> ' . $result['mass'];
	$htmlresult .= '</span>';

	$htmlresult .= '<br><div class="box box-info" id="form_'.$result['postid'].'" style="display:none;">';
	$htmlresult .= '<div class="box-header with-border">
	  <h3 class="box-title">ADD NEW STOCK FOR THIS PRODUCT</h3>
	</div>';
	
	$htmlresult .= '<form class="form-horizontal" method="post">
	  <div class="box-body">';
	$htmlresult .= '<div class="form-group">
		  <label class="col-sm-3 control-label">Quantity</label>
		  <div class="col-sm-9">
			<input type="number" class="form-control" id="quantity_'.$result['postid'].'" placeholder="1" min="1" required>
		  </div>
		</div>';

	$htmlresult .= '<div class="form-group">
		  <label class="col-sm-3 control-label">Condition</label>
		  <div class="col-sm-9">
			<select class="form-control" id="condition_'.$result['postid'].'" required>
			<option value="1"> New </option>
			<option value="3"> Damaged </option>
			<option value="4"> Reject </option>
			</select>
		  </div>
		</div>';

	$htmlresult .= '<div class="form-group">
			<label class="col-sm-3 control-label">Type of Stock</label>
			<div class="col-sm-9">
			<select class="form-control" id="type_'.$result['postid'].'" required>
			<option value="CSTOCK"> Commercial Stock </option>
			<option value="IHSTOCK"> In-House Stock </option>
			</select>
			</div>
		</div>';

	$htmlresult .= '</div>';
	
	$htmlresult .= '<div class="box-footer">
		<input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Submit This Entry" onclick="as_show_waiting_after(this, false); return as_add_stock('.$result['postid'].');"/>
		<input type="reset" class="btn btn-default pull-right" style="margin-left: 10px"  value="Cancel" onclick="as_cancel_adding();"/>
	  </div>';
	$htmlresult .= '</form>';
	$htmlresult .= '</div>';
	$htmlresult .= '</div>';
	$htmlresult .= '</li>';
}

$htmlresult .= '</div>';
echo $htmlresult;