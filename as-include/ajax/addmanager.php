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

require_once AS_INCLUDE_DIR . 'app/cookies.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'db/users.php';
require_once AS_INCLUDE_DIR . 'app/emails.php';
require_once AS_INCLUDE_DIR . 'app/post-update.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-business.php';

$userid = as_get_logged_in_userid();
$managerid = as_post_text('manager');
$businessid = as_post_text('business');
$manager = as_db_select_with_pending(as_db_user_account_selectspec($managerid, true));
$business = BxBusiness::get_single($userid, $businessid);

$managers = explode(',', $business->managers);
$newmanagers = array();

$alreadyadded = 0; //already added

if ($business->userid == $managerid) $alreadyadded = 1; // is owner
else 
{
	if (count($managers)) {
		foreach ($managers as $mid) 
		{
			if (!empty($mid) && $business->userid != $mid && $managerid != $mid)
			{ 
				$alreadyadded = 2; //not added
				$newmanagers[$mid] = $mid;
			}
		}
		$alreadyadded = 2; //not added
	}
	else 
	{
		$alreadyadded = 2; //not added
	}
}

$newmanagers[$managerid] = $managerid;

if ($alreadyadded == 2) 
{
	as_db_record_set('businesses', 'businessid', $businessid, 'managers', implode(', ', $newmanagers) );
}

$link = as_opt('site_url').'business/'.$businessid;

as_send_notification($userid, $manager['firstname'].' '.$manager['lastname'], $manager['email'], $manager['handle'], as_lang('emails/new_business_subject'), as_lang('emails/new_business_manager_subject'), array(
	'^business_title' => $business->title,
	'^business_username' => $business->username,
	'^business_location' => $business->location,
	'^business_contact' => $business->contact,
	'^business_description' => $business->content,
	'^business_url' => $link,
	'^url' => as_opt('site_url'),
));

as_db_notification_create($manager['userid'], as_lang_html_sub('notify/added_business_manager', $business->title), 'new-bs-manager', $link, '');

echo "AS_AJAX_RESPONSE\n1\n";

switch ($alreadyadded)
{
	case 0:
		$htmlresult .= '<div class="alert alert-danger alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> Oops! ';
		$htmlresult .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is already a manager of ' .  $business->title . ' Business!</div>xqx';
		break;

	case 1:
		$htmlresult .= '<div class="alert alert-danger alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> Oops! ';
		$htmlresult .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is the owner of ' .  $business->title . ' Business!</div>xqx';
		break;

	case 2:
		$htmlresult .= '<div class="alert alert-success alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> ';
		$htmlresult .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is now a manager of ' .  $business->title . ' Business!<br>';
		$htmlresult .= ($manager['gender'] == 1 ? 'He' : 'She' ).' will get a notification about this change of responsibility soon!</div>xqx';
		break;
}

$htmlresult .= '<ul class="products-list product-list-in-box" style="border-top: 1px solid #000">';
$owner = as_db_select_with_pending(as_db_user_profile($userid));

$htmlresult .= '<li class="item"><div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $owner).'</div>';
$htmlresult .= '<div class="product-info"><a href="'.as_path_html('user/' . $owner['handle']).'" class="product-title" style="font-size: 20px;">';
$htmlresult .= $owner['firstname'].' '.$owner['lastname'].'</a><span class="product-description">BUSINESS OWNER</span>';
$htmlresult .= "</div><br></li>";

if (count($managers)) {
	foreach ($managers as $mid) {
		if (!empty($mid) && $userid != $mid) {
			$manager = as_db_select_with_pending(as_db_user_profile($mid));
			$htmlresult .= '<li class="item"><div class="product-img">'.as_avatar(20, 'profile-user-img img-responsive', $manager).'</div>';
			$htmlresult .= '<div class="product-info"><a href="'.as_path_html('user/' . $manager['handle']).'" class="product-title" style="font-size: 20px;">';
			$htmlresult .= $manager['firstname'].' '.$manager['lastname'].'</a><span class="product-description">BUSINESS MANAGER</span>';
			$htmlresult .= "</div><br></li>";
		}
	}
}

$htmlresult .= '</ul>';

echo $htmlresult;
