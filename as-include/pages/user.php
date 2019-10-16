<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for user profile page


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


// Determine the identify of the user

$handle = as_request_part(1);

if (!strlen($handle)) {
	$handle = as_get_logged_in_handle();
	as_redirect(!empty($handle) ? 'user/' . $handle : 'users');
}

switch (as_request_part(2)) {
	case 'wall':
		as_set_template('user-wall');
		$as_content = include AS_INCLUDE_DIR . 'pages/user-wall.php';
		break;

	case 'activity':
		as_set_template('user-activity');
		$as_content = include AS_INCLUDE_DIR . 'pages/user-activity.php';
		break;

	case 'items':
		as_set_template('user-items');
		$as_content = include AS_INCLUDE_DIR . 'pages/user-items.php';
		break;

	case 'reviews':
		as_set_template('user-reviews');
		$as_content = include AS_INCLUDE_DIR . 'pages/user-reviews.php';
		break;

	case 'edit':
		as_set_template('user-edit');
		$as_content = include AS_INCLUDE_DIR . 'pages/user-edit.php';
		break;

	case null:
		$as_content = include AS_INCLUDE_DIR . 'pages/user-profile.php';
		break;

	default:
		$as_content = include AS_INCLUDE_DIR . 'as-page-not-found.php';
		break;
}

return $as_content;
