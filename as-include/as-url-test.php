<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Sits in an iframe and shows a green page with word 'OK'


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

if (as_gpc_to_string(@$_GET['param']) == AS_URL_TEST_STRING) {
	require_once AS_INCLUDE_DIR . 'app/admin.php';

	echo '<html><body style="margin:0; padding:0;">';
	echo '<table width="100%" height="100%" cellspacing="0" cellpadding="0">';
	echo '<tr valign="middle"><td align="center" style="border: 1px solid; background-color:#fff; ';
	echo as_admin_url_test_html();
	echo '/td></tr></table>';
	echo '</body></html>';
}
