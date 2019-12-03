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

$business = as_post_text('cust_bsid');
$title = as_post_text('cust_title');
$type = as_post_text('cust_type');
$idnumber = as_post_text('cust_idnumber');
$mobile = as_post_text('cust_mobile');
$email = as_post_text('cust_email');
$region = as_post_text('cust_region');
$city = as_post_text('cust_city');
$road = as_post_text('cust_road');

$contact = $mobile . ' xx ' . $email;
$location = $region . ' xx ' . $city . ' xx ' . $road;

$userid = as_get_logged_in_userid();

$customerid = as_db_customer_register($userid, $business, $title, $phone, $email, $location1, $location2, $location3);

if ($customerid) {
	echo "AS_AJAX_RESPONSE\n1\n";
	$resulthtml = '<div class="alert alert-success alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> ';
	$resulthtml .= 'Customer ' . $title . ' has been registered successfully!</div>';
}
else echo "AS_AJAX_RESPONSE\n0\n";
echo $resulthtml;
