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

$insearchitem = as_post_text('search_text');
$businessid = as_post_text('item_biz');
$type = as_post_text('result_type');

$userid = as_get_logged_in_userid();

list ($products, $customers) = as_db_select_with_pending( 
	as_db_products_selectspec('title', $businessid, $insearchitem),
	as_db_recent_customers($businessid)
);

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = as_products_search($businessid, $products, $type);

echo $htmlresult;