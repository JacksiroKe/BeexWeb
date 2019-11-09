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

$userid = as_get_logged_in_userid();
$itemresults = as_db_select_with_pending( as_db_products_selectspec($userid, 'title', $insearchitem));

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '<div class="box-body">';
$htmlresult .= '<ul class="products-list product-list-in-box">';

foreach ($itemresults as $result) 
{
	$htmlresult .= '<li class="item">';
	$htmlresult .= '<div class="product-img">'.as_get_media_html($result['icon'], 200, 200).'</div>';
	$htmlresult .= '<div class="product-info">';
	$htmlresult .= '<a href="#" class="product-title" style="font-size: 20px;">'.$result['category']. ' - ' . $result['itemcode']. ' [' .$result['title'].']</a>';
	$htmlresult .= '<span class="product-description">'.$result['content']. '<br><b>VOLUME:</b>' .$result['volume'].' <b>MASS:</b> ' . $result['mass'] . '</span>';

	//volume, weight, buyprice, saleprice, state, color, texture, quantity, manufacturer, content
	$htmlresult .= '</div>';
	$htmlresult .= '</li>';
}

$htmlresult .= '</div>';
echo $insearchitem;