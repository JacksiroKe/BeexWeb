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

$insearchitem = as_post_text('searchtext');
$businessid = as_post_text('item_biz');

$userid = as_get_logged_in_userid();
$itemresults = as_db_select_with_pending( as_db_user_search_selectspec($insearchitem));

echo "AS_AJAX_RESPONSE\n1\n";

$htmlresult = '<div class="box-body">';
$htmlresult .= '<ul class="products-list product-list-in-box">';

foreach ($itemresults as $result) 
{
	$gender = $result['gender'] == 1 ? ' ('.as_lang('users/gender_male').')' : ' ('.as_lang('users/gender_female').')';
	$sincetime = as_time_to_string(as_opt('db_time') - $result['created']);
	$joindate = as_when_to_html($result['created'], 0);
	   
	$htmlresult .= '<li class="item stock-item-result"  onclick="as_show_quick_form(\'mansearch_'.$result['userid'].'\')">';
	$htmlresult .= '<div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $result).'</div>';
	$htmlresult .= '<div class="product-info">';
	$htmlresult .= '<span class="product-title" style="font-size: 20px;">'.$result['firstname'].' '.$result['lastname'].' - ' .$gender. ' ' .
	as_html(as_user_level_string($result['level'])).'</span>';
	
	$htmlresult .= '<span class="product-description">';
	$htmlresult .= $result['email'].' [@' .$result['handle'].'] | User for '.$sincetime . ' (' . as_lang_sub('main/since_x', $joindate['data']) . ')';
	$htmlresult .= '</span>';
	$htmlresult .= '</li>';
	
	$htmlresult .= '<li id="mansearch_'.$result['userid'].'" style="display:none;">';	
	$htmlresult .= '<div id="itemresults_'.$result['userid'].'"></div>';
	$htmlresult .= '<form class="form-horizontal"><div class="box-body">';

	$htmlresult .= '<div class="row">
                <div class="col-lg-6">
					<div class="form-group">
					<label class="col-sm-3 control-label">Role</label>
					<div class="col-sm-9">
					<select class="form-control" id="mansearch_role_'.$result['userid'].'">
					<option value="managers"> Manager </option>
					<option value="owners"> Owner </option>
					</select>
					</div>
				</div>
                </div>
                
                <div class="col-lg-6">
                  <div class="input-group">
				  <input type="submit" class="btn btn-info pull-right" style="margin-left: 10px"  value="Assign this Role" onclick="return as_change_business_role(\'mansearch\', '.$businessid.', '.$result['userid'].');"/>
				  <input type="reset" class="btn btn-default pull-right" style="margin-left: 10px"  value="Cancel" onclick="as_show_quick_form(\'mansearch_'.$result['userid'].'\');"/>
			</div>
		</div>
		</div>';
		
	$htmlresult .= '</div>';
	
    $htmlresult .= '</form>';
	$htmlresult .= '</div>';
	$htmlresult .= '</li>';
}

$htmlresult .= '</div>';
echo $htmlresult;