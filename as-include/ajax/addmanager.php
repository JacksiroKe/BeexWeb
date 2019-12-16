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
require_once AS_INCLUDE_DIR . 'APS/as-views.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-business.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-department.php';

$userid = as_get_logged_in_userid();
$type = as_post_text('item_type');
$role = as_post_text('new_role');
$managerid = as_post_text('user_id');
$identifier = as_post_text('item_id');

$manager = as_db_select_with_pending(as_db_user_account_selectspec($managerid, true));
$newmanagers = array();
$alreadyadded = 0; //already added
$htmlresult = '';

if ($type == 'depart') 
{
	$depart = BxDepartment::get_single($userid, $identifier);

	$managers = explode(',', $depart->managers);

	if ($depart->userid == $managerid) $alreadyadded = 1; // is owner
	else if (in_array($managerid, $managers)) $alreadyadded = 0;
	else $alreadyadded = 2;

	if (count($managers)) {
	  foreach ($managers as $mid) {
		if (!empty($mid) && $userid != $mid) $newmanagers[$mid] = $mid;
	  }
	}
	$newmanagers[$managerid] = $managerid;

	if ($alreadyadded == 2) 
	{
		as_db_record_set('businessdepts', 'departid', $identifier, 'managers', implode(', ', $newmanagers) );
	}

	$link = as_opt('site_url').'department/'.$identifier;

	echo "AS_AJAX_RESPONSE\n1\n";

	switch ($alreadyadded)
	{
		case 0:
			$htmlresult .= '<div class="alert alert-danger alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> Oops! ';
			$htmlresult .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is already a manager of ' .  $depart->title . ' Department!</div>xqx';
			break;

		case 1:
			$htmlresult .= '<div class="alert alert-danger alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> Oops! ';
			$htmlresult .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is the owner of ' .  $depart->title . ' Department!</div>xqx';
			break;

		case 2:
			$htmlresult .= '<div class="alert alert-success alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> ';
			$htmlresult .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is now a manager of ' .  $depart->title . ' Department! ';
			$htmlresult .= ($manager['gender'] == 1 ? 'He' : 'She' ).' will get a notification about this change of responsibility soon!</div>xqx';
			
			as_send_notification($userid, $manager['firstname'].' '.$manager['lastname'], $manager['email'], $manager['handle'], as_lang('emails/new_department_subject'), as_lang('emails/new_department_manager_subject'), array(
				'^department_title' => $depart->title,
				'^department_business' => $department->business,
				'^department_description' => $depart->content,
				'^department_url' => $link,
				'^url' => as_opt('site_url'),
			));

			as_db_notification_create($manager['userid'], as_lang_html_sub('notify/added_department_manager', $depart->title), 'new-dp-manager', $link, '');

			break;
	}

	$htmlresult .= '</ul>';
	if ($alreadyadded == 2) 
	{
		$betabiz = BxBusiness::get_single($userid, $identifier);
		$new_owners = explode(',', $betabiz->users);
		$new_managers = explode(',', $betabiz->managers);
		$htmlresult .= as_managers_list($type, $betabiz->businessid, $userid, $new_owners, $new_managers);
	}	
}
else 
{
	$business = BxBusiness::get_single($userid, $identifier);

	$managers = explode(',', $business->managers);

	if ($business->userid == $managerid) $alreadyadded = 1; // is owner
	else if (in_array($managerid, $managers)) $alreadyadded = 0;
	else $alreadyadded = 2;

	if (count($managers)) {
	  foreach ($managers as $mid) {
		if (!empty($mid) && $userid != $mid) $newmanagers[$mid] = $mid;
	  }
	}
	$newmanagers[$managerid] = $managerid;

	if ($alreadyadded == 2) 
	{
		as_db_record_set('businesses', 'businessid', $identifier, 'managers', implode(', ', $newmanagers) );
	}

	$link = as_opt('site_url').'business/'.$identifier;

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
			$htmlresult .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is now a manager of ' .  $business->title . ' Business! ';
			$htmlresult .= ($manager['gender'] == 1 ? 'He' : 'She' ).' will get a notification about this change of responsibility soon!</div>xqx';
			
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

			break;
	}

	$htmlresult .= '</ul>';
	if ($alreadyadded == 2) 
	{
		$betabiz = BxBusiness::get_single($userid, $identifier);
		$new_owners = explode(',', $betabiz->users);
		$new_managers = explode(',', $betabiz->managers);
		$htmlresult .= as_managers_list($type, $betabiz->businessid, $userid, $new_owners, $new_managers);
	}
}

echo $htmlresult;
