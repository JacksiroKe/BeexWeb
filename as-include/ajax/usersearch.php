<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax request based on user name or email

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


// Collect the information we need from the database

$insearch = as_post_text('namesearch');
$userresult = as_db_select_with_pending( as_db_user_search_selectspec($insearch) );
$userlist = array();
foreach ($userresult as $user) {
    $userlist[] = $user['fistname'] . ' ' . $user['lastname'] . ',';
}

echo "AS_AJAX_RESPONSE\n1\n";

echo strtr(as_html(implode(',', $userlist)), "\r\n", '  ') . "\n";
