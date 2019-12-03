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

$incounty = as_post_text('countyid');

$userid = as_get_logged_in_userid();
$sublocations = as_db_select_with_pending( as_db_latest_locations('SUB-COUNTY', $incounty) );

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '';
$htmlresult .= '<label>Sub-County:</label>
<select name="subcounty" id="subcounty" onchange="as_select_subcounty()" class="form-control" required>
<option>Select Sub-County</option>';
foreach ($sublocations as $result)
{
$htmlresult .= '<option value="'.$result['locationid'].'">'.$result['title'].'</option>';
}
$htmlresult .= '</select>';

echo $htmlresult;