<?php
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
$itemresults = as_db_select_with_pending( as_db_customer_search_selectspec($insearchitem, $businessid));

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult .= '<br><br><ul class="products-list product-list-in-box">';

foreach ($itemresults as $result) 
{
	$htmlresult .= '<li class="item list-item-result" onclick="as_select_customer('.$result['customerid'].', \''.strtoupper($result['title']).'\')">';
	$htmlresult .= '<div class="product-img">';
	$htmlresult .= '<img src="http://localhost/beexpress/site/as-media/user.jpg" width="20" height="20" class="profile-user-img img-responsive img-circle" style="border-radius: 15px" alt="User Image">';
	$htmlresult .= '</div>';
	$htmlresult .= '<div class="product-info">';
	$htmlresult .= '<span class="product-title" style="font-size: 20px;">'.$result['title'].'</span>';
	
	$htmlresult .= '<span class="product-description">';
	$htmlresult .= $result['code'] . ' ' . $result['county'] .', ' . $result['subcounty'] .', ' . $result['town'];
	$htmlresult .= '</span>';
	$htmlresult .= '</li>';
}

echo $htmlresult;