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

$insubcounty = as_post_text('subcountyid');
$infeedback = as_post_text('towns_feedback');

$userid = as_get_logged_in_userid();
$towns = as_db_select_with_pending( as_db_latest_locations('TOWN', $insubcounty) );

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '';
if ($infeedback == 'table') 
{
	$htmlresult .= '<table class="table table-bordered">';
	$htmlresult .= '<thead><tr><th valign="top" style="width:50px;">#</th><th valign="top">Title</th><th valign="top">Details</th><th valign="top">Created</th><th></th></tr></thead>';
	$htmlresult .= '<tbody>';
	$t = 1;
	foreach ($towns as $result)
	{
		$htmlresult .= '<tr class="row-item">';
		$htmlresult .= '<td valign="top">'.$t.'</td>';
		$htmlresult .= '<td valign="top">'.$result['title'].'</td>';
		$htmlresult .= '<td valign="top">'.$result['details'].'</td>';
		$htmlresult .= '<td valign="top">'.as_format_date($result['created'], true).'</td>';
		$htmlresult .= '<td valign="top"></td>';
		$htmlresult .= '</tr>';
		$t++;
	}
	$htmlresult .= '</tbody>';
	$htmlresult .= '<tfoot><tr><th valign="top" style="width:50px;">#</th><th valign="top">Title</th><th valign="top">Details</th><th valign="top">Created</th><th></th></tr></tfoot>';	
	$htmlresult .= '</table>';
}
else 
{
	$htmlresult .= '<label>Town:</label>
	<select name="town" id="town" onchange="as_select_town()" class="form-control">
	<option>Select Town</option>';
	foreach ($towns as $result)
	{
		$htmlresult .= '<option value="'.$result['locationid'].'">'.$result['title'].'</option>';
	}
	$htmlresult .= '</select>';

}

echo $htmlresult;