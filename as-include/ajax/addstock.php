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

require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'db/post-create.php';
require_once AS_INCLUDE_DIR . 'db/post-update.php';

$itemid = as_post_text('item_id');
$business = as_post_text('item_biz');
$quantity = as_post_text('item_qty');
$state = as_post_text('item_cdn');
$type = as_post_text('item_type');
$userid = as_get_logged_in_userid();

$stockids = as_db_find_by_stockitem($itemid, $business);
if (count($stockids))
{
   as_db_stock_entry('ENTRY', $stockids[0], $itemid, $userid, $quantity, $state);
}
else
{
    $stockid = as_db_stock_add($type, $business, $itemid, $userid, $quantity);
    as_db_stock_entry('ENTRY', $stockid, $itemid, $userid, $quantity, $state);
}

echo "AS_AJAX_RESPONSE\n1\n";
$resulthtml = '<div class="alert alert-success alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> ';
$resulthtml .= 'Stock for the item added successfully!</div>';

echo $resulthtml;
