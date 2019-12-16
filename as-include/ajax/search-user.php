<?php
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

$type = as_post_text('item_type');
$identifier = as_post_text('item_id');
$insearchitem = as_post_text('searchtext');

$element = $type . '_' . $identifier . '_';

$userid = as_get_logged_in_userid();
$managers = as_db_select_with_pending( as_db_user_search_selectspec($insearchitem));

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '<div class="box-body">';
$htmlresult .= '<ul class="products-list product-list-in-box">';

foreach ($managers as $manager) 
{
	$gender = $manager['gender'] == 1 ? ' ('.as_lang('users/gender_male').')' : ' ('.as_lang('users/gender_female').')';
	$sincetime = as_time_to_string(as_opt('db_time') - $manager['created']);
	$joindate = as_when_to_html($manager['created'], 0);
	
	$htmlresult .= "\n".'<li class="item list-item-result" onclick="as_show_quick_form(\''.$element.'item_'.$manager['userid'].'\')">';
	$htmlresult .= '<div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $manager).'</div>';
	$htmlresult .= '<div class="product-info"><span class="product-title" style="font-size: 20px;">';
	$htmlresult .= $manager['firstname'].' '.$manager['lastname'].' - ' .$gender. ' ';	
	$htmlresult .= as_html(as_user_level_string($manager['level'])).'</span>';
	$htmlresult .= '<span class="product-description">';
	$htmlresult .= $manager['email'].' [@' .$manager['handle'].'] | User for '.$sincetime . ' (' . as_lang_sub('main/since_x', $joindate['data']) . ')';
	$htmlresult .= '</span>';
	$htmlresult .= "</div><br>\n</li>";
	
	$htmlresult .= '<li id="'.$element.'item_'.$manager['userid'].'" style="display:none;">';
	$htmlresult .= '<form class="form-horizontal"><div class="box-body">';

	$htmlresult .= '<div class="row">
				<div class="col-lg-6">
		  <div class="form-group">
		  <label class="col-sm-3 control-label">Role</label>
		  <div class="col-sm-9">';
	$htmlresult .= '<select class="form-control" id="'.$element.'role_'.$manager['userid'].'">
		  <option value="owners"> Owner </option>
		  <option value="managers"> Manager </option>
		  <option value="norole"> No Role </option>
		  </select>';
		  
	$htmlresult .= '</div></div></div>'."\n";
	$htmlresult .= '<div class="col-lg-6"><div class="input-group">
		  <input type="reset" class="btn btn-info pull-right" style="margin-left: 10px"  value="Change this Role" onclick="return as_change_role(\''.$type.'\', '.$identifier.', '.$manager['userid'].');"/>';
	$htmlresult .= '<input type="reset" class="btn btn-default pull-right" style="margin-left: 10px"  value="Cancel" onclick="as_show_quick_form(\''.$element.'item_'.$manager['userid'].'\');"/>
	  </div>
	</div>
	</div>';

	$htmlresult .= "\n</div>";

	$htmlresult .= "\n</form>";
	$htmlresult .= "\n</li>";
		
}

$htmlresult .= '</div>';
echo $htmlresult;