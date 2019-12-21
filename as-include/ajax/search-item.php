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
require_once AS_INCLUDE_DIR . 'APS/as-views.php';

$insearchitem = as_post_text('searched');
$businessid = as_post_text('business');

$userid = as_get_logged_in_userid();

$products = as_db_select_with_pending( as_db_products_selectspec('title', $businessid, $insearchitem) );

echo "AS_AJAX_RESPONSE\n1\n";

if (as_post_text('item_type') == 'stockexit')
	$htmlresult = as_stock_exit_items($businessid, $products);
else $htmlresult = as_stock_entry_items($businessid, $products);

echo $htmlresult;