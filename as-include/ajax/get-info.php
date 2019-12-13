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

$userid = as_get_logged_in_userid();
$htmlresult = '';

echo "AS_AJAX_RESPONSE\n1\n";

switch (as_post_text('infotype'))
{
	case 'customer':
		$customerid = as_post_text('customer');
		$customer = as_db_select_with_pending(as_db_customer_selectspec($customerid));
		$phone = explode('xx', $customer['phone']);

		$htmlresult .= '<h2><i class="fa fa-globe"></i> '.$customer['title'].'</h2>';
		$htmlresult .= '<address>';
		if (strlen($phone[0]) > 3) $htmlresult .= '<strong>'.trim($phone[0]).'</strong><br>';
		if (strlen($phone[1]) > 3) $htmlresult .= 'Phone: '.trim($phone[1]).'<br>';
		if (strlen($customer['email']) > 0) $htmlresult .= 'Email: '.$customer['email'].'<br>';
		$htmlresult .= $customer['code'] . ' ' . $customer['county'] .', ' . $customer['subcounty'] .', ' . $customer['town'];
		$htmlresult .= '</address>';
		break;
	
	case 'item':
		$postid = as_post_text('itemid');
		$product = as_db_select_with_pending(as_db_product_selectspec($postid));		
		$htmlresult .= '1xqx'.$product['title'].'xqx'.$product['itemcode'].'xqx'.$product['content'].
			'xqx'.$product['content'].'xqxAmount Due '.date('d/m/Y').': <b>Ksh. 0.00</b>';
		break;
}

echo $htmlresult;
