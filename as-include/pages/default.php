<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for home page, Q&A listing page, custom pages and plugin pages


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

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/format.php';

// Determine whether path begins with as or not (item and review listing can be accessed either way)

$requestparts = explode('/', as_request());
if (as_is_logged_in()) {
	$userid = as_get_logged_in_userid();
	$usertype = as_get_logged_in_type();

	switch ($usertype) {
		case 'SA':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-sa.php';
			break;
			
		case 'A':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-a.php';
			break;	
		
		case 'ENR':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-enr.php';
			break;	
		
		case 'ER':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-er.php';
			break;	
		
		case 'SSP':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-ssp.php';
			break;	
		
		case 'MSP':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-msp.php';
			break;	
		
		case 'BC':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-bc.php';
			break;	
		
		case 'IC':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-ic.php';
			break;	
		
		case 'BS':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-bs.php';
			break;	
		
		case 'IS':
			$as_content = include AS_INCLUDE_DIR . 'pages/default-is.php';
			break;	
		
		default:
			$as_content = include AS_INCLUDE_DIR . 'pages/default-user.php';
			break;
	}
}
else {
	switch (as_request_part(0))
	{
		case 'signup':
			$as_content = include AS_INCLUDE_DIR . 'pages/signup.php';
			break;
			
		case 'forgot':
			$as_content = include AS_INCLUDE_DIR . 'pages/forgot.php';
			break;
			
		default:
			$as_content = include AS_INCLUDE_DIR . 'pages/signin.php';
			break;
	}  
}

return $as_content;
