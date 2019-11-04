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
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/post-update.php';
require_once AS_INCLUDE_DIR . 'APS/as-beex-business.php';

$handle = as_post_text('manager');
$businessid = as_post_text('business');
$manager = as_db_select_with_pending(as_db_user_account_selectspec($handle, false));
$business = BxBusiness::get_single(as_get_logged_in_userid(), $businessid);

as_db_record_set('businesses', 'businessid', $businessid, 'managers', $business->managers . $manager['userid'] . ',');

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
$resulthtml = '<div class="alert alert-success alert-dismissible"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> ';
$resulthtml .= $manager['firstname'] . ' ' . $manager['lastname'] . ' ('.$manager['email'].') is now a manager of ' .  $business->title . ' Business!<br>';
$resulthtml .= ($manager['gender'] == 1 ? 'He' : 'She' ).' will get a notification about this change of responsibility soon!</div>';
$resulthtml .= '<input class="btn btn-primary" value="ADD AS A MANAGER" name="doaddmanager" onclick="as_show_waiting_after(this, false); return as_add_manager('.
    $businessid.', this);">';

echo $resulthtml;