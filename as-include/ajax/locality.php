<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Server-side response to Ajax location information requests


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


$locationid = as_post_text('locationid');
if (!strlen($locationid))
	$locationid = null;

$locations = as_db_select_with_pending( as_db_location_sub_selectspec($locationid) );

echo "AS_AJAX_RESPONSE\n1\n";

foreach ($locations as $location) {
	// sublocation information
	echo "\n" . $location['locationid'] . '/' . $location['title'];
}
