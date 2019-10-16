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

require_once AS_INCLUDE_DIR . 'app/cookies.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'app/post-create.php';

$itemid = as_post_text('o_itemid');
$categoryid = as_post_text('o_category');
$quantity = as_post_text('o_quantity');
$address = as_post_text('o_address');

$userid = as_get_logged_in_userid();
$cookieid = isset($userid) ? as_cookie_get() : as_cookie_get_create();

$orderid = as_order_create($userid, as_get_logged_in_handle(), $cookieid, $itemid, $categoryid, $quantity, $address);

echo "AS_AJAX_RESPONSE\n1\n";
echo "successfully";
