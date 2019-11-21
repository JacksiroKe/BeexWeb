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
$datatype = as_post_text('data_type');
$business = as_post_text('business_id');

list ($products, $customers) = as_db_select_with_pending( 
	as_db_products_selectspec('title', $business),
	as_db_recent_customers($business)
);

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '<table id="allorders" class="table table-bordered">';
$htmlresult .= '<thead>';
$htmlresult .= '<tr>';
$htmlresult .= '<th valign="top" style="width:50px;">#</th>';
$htmlresult .= '<th valign="top">ProductID</th>';
$htmlresult .= '<th valign="top">Category</th>';
$htmlresult .= '<th valign="top">Item Code</th>';
$htmlresult .= '<th valign="top">Actual Stock</th>';
$htmlresult .= '<th valign="top">Available Stock</th>';
$htmlresult .= '<th valign="top">Date of Entry</th>';
$htmlresult .= '<th valign="top" style="width:50px;"></th>';
$htmlresult .= '</tr>';
$htmlresult .= '</thead>';

$htmlresult .= '<tbody>';
if (count($products)) {
	$p = 1;
	foreach ($products as $product) {
		$htmlresult .= '<tr data-toggle="modal" data-target="#modal-item1"  title="Click on this product to edit or view" class="row-item">';
		$delivery = as_when_to_html($product['delivered'], 0);
		$deliverydate = isset($product['delivered']) ? $delivery['data'] : '';
		$deliveryago = as_time_to_string(as_opt('db_time') - $product['delivered']);

		$htmlresult .= '<td valign="top"> '.$p.' </td>';
		$htmlresult .= '<td valign="top"> '. as_get_media_html($product['icon'], 20, 20) . as_html($product['title']). ' </td>';
		$htmlresult .= '<td valign="top"> '.$product['category'] .' </td>';
		$htmlresult .= '<td valign="top"> '.$product['itemcode'].' </td>';
		$htmlresult .= '<td valign="top"> '.$product['actual'].' </td>';
		$htmlresult .= '<td valign="top"> '.$product['available'].' </td>';
		$htmlresult .= '<td valign="top"> ' . $deliverydate . ' (' .$deliveryago . ' ago) </td>';
		$htmlresult .= '<td valign="top"> </td>';

		$htmlresult .= '</tr>';
		$p++;
	}
}

$htmlresult .= '</tbody>';
$htmlresult .= '<tfoot>';
$htmlresult .= '<tr>';
$htmlresult .= '<th valign="top" style="width:50px;">#</th>
<th valign="top">ProductID</th>
<th valign="top">Category</th>
<th valign="top">Item Code</th>
<th valign="top">Actual Stock</th>
<th valign="top">Available Stock</th>
<th valign="top">Date of Entry</th>
<th valign="top" style="width:50px;"></th>
</tr>';
$htmlresult .= '</tfoot>';
$htmlresult .= '</table>';

echo $htmlresult;
