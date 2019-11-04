<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: User management (application level) for creating/modifying users


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

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

if (!defined('AS_MIN_PASSWORD_LEN')) {
	define('AS_MIN_PASSWORD_LEN', 8);
}

if (!defined('AS_NEW_PASSWORD_LEN')){
	/**
	 * @deprecated This was the length of the reset password generated by APS. No longer used.
	 */
	define('AS_NEW_PASSWORD_LEN', 8);
}


/**
 * Return $errors fields for any invalid aspect of user-entered $handle (username) and $email. Works by calling through
 * to all filter modules and also rejects existing values in database unless they belongs to $olduser (if set).
 * @param $handle
 * @param $email
 * @param $olduser
 * @return array
 */
function as_handle_email_filter(&$handle, &$email, $olduser = null)
{
	require_once AS_INCLUDE_DIR . 'db/users.php';
	require_once AS_INCLUDE_DIR . 'util/string.php';

	$errors = array();

	// sanitise 4-byte Unicode
	$handle = as_remove_utf8mb4($handle);

	$filtermodules = as_load_modules_with('filter', 'filter_handle');

	foreach ($filtermodules as $filtermodule) {
		$error = $filtermodule->filter_handle($handle, $olduser);
		if (isset($error)) {
			$errors['handle'] = $error;
			break;
		}
	}

	if (!isset($errors['handle'])) { // first test through filters, then check for duplicates here
		$handleusers = as_db_user_find_by_handle($handle);
		if (count($handleusers) && ((!isset($olduser['userid'])) || (array_search($olduser['userid'], $handleusers) === false)))
			$errors['handle'] = as_lang('users/handle_exists');
	}

	$filtermodules = as_load_modules_with('filter', 'filter_email');

	$error = null;
	foreach ($filtermodules as $filtermodule) {
		$error = $filtermodule->filter_email($email, $olduser);
		if (isset($error)) {
			$errors['email'] = $error;
			break;
		}
	}

	if (!isset($errors['email'])) {
		$emailusers = as_db_user_find_by_email($email);
		if (count($emailusers) && ((!isset($olduser['userid'])) || (array_search($olduser['userid'], $emailusers) === false)))
			$errors['email'] = as_lang('users/email_exists');
	}

	return $errors;
}


/**
 * Make $handle valid and unique in the database - if $allowuserid is set, allow it to match that user only
 * @param $handle
 * @return string
 */
function as_handle_make_valid($handle)
{
	require_once AS_INCLUDE_DIR . 'util/string.php';
	require_once AS_INCLUDE_DIR . 'db/maxima.php';
	require_once AS_INCLUDE_DIR . 'db/users.php';

	if (!strlen($handle))
		$handle = as_lang('users/signedup_user');

	$handle = preg_replace('/[\\@\\+\\/]/', ' ', $handle);

	for ($attempt = 0; $attempt <= 99; $attempt++) {
		$suffix = $attempt ? (' ' . $attempt) : '';
		$tryhandle = as_substr($handle, 0, AS_DB_MAX_HANDLE_LENGTH - strlen($suffix)) . $suffix;

		$filtermodules = as_load_modules_with('filter', 'filter_handle');
		foreach ($filtermodules as $filtermodule) {
			// filter first without worrying about errors, since our goal is to get a valid one
			$filtermodule->filter_handle($tryhandle, null);
		}

		$haderror = false;

		foreach ($filtermodules as $filtermodule) {
			$error = $filtermodule->filter_handle($tryhandle, null); // now check for errors after we've filtered
			if (isset($error))
				$haderror = true;
		}

		if (!$haderror) {
			$handleusers = as_db_user_find_by_handle($tryhandle);
			if (!count($handleusers))
				return $tryhandle;
		}
	}

	as_fatal_error('Could not create a valid and unique handle from: ' . $handle);
}


/**
 * Return an array with a single element (key 'password') if user-entered $password is valid, otherwise an empty array.
 * Works by calling through to all filter modules.
 * @param $password
 * @param $olduser
 * @return array
 */
function as_password_validate($password, $olduser = null)
{
	$error = null;
	$filtermodules = as_load_modules_with('filter', 'validate_password');

	foreach ($filtermodules as $filtermodule) {
		$error = $filtermodule->validate_password($password, $olduser);
		if (isset($error))
			break;
	}

	if (!isset($error)) {
		$minpasslen = max(AS_MIN_PASSWORD_LEN, 1);
		if (as_strlen($password) < $minpasslen)
			$error = as_lang_sub('users/password_min', $minpasslen);
	}

	if (isset($error))
		return array('password' => $error);

	return array();
}

/**
 * Create a new supplier (application level) with $email, $password, $handle and $level.
 * Set $confirmed to true if the email address has been confirmed elsewhere.
 * Handles user points, notification and optional email confirmation.
 * @param $email
 * @param $password
 * @param $handle
 * @param int $level
 * @param bool $confirmed
 * @return mixed
 */
function as_create_department_supplier($userid, $title, $content, $field1, $field2, $usertype)
{
	require_once AS_INCLUDE_DIR . 'db/users.php';
	require_once AS_INCLUDE_DIR . 'app/options.php';
	require_once AS_INCLUDE_DIR . 'app/emails.php';

	$supplierid = as_db_supplier_create($userid, $title, $content, $field1, $field2);
	
	$profiles = as_get_logged_in_profiles().', '.$usertype;
	as_db_user_set($userid, 'type', $usertype);
	as_db_user_set($userid, 'profiles', $profiles);
	
	return $supplierid;
}


/**
 * Create a new user (application level) with $email, $password, $handle and $level.
 * Set $confirmed to true if the email address has been confirmed elsewhere.
 * Handles user points, notification and optional email confirmation.
 * @param $email
 * @param $password
 * @param $handle
 * @param int $level
 * @param bool $confirmed
 * @return mixed
 */
function as_create_department_user($type, $firstname, $lastname, $gender, $country, $mobile, $email, $password, $handle, $level = AS_USER_LEVEL_BASIC, $confirmed = false)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'db/users.php';
	require_once AS_INCLUDE_DIR . 'db/points.php';
	require_once AS_INCLUDE_DIR . 'app/options.php';
	require_once AS_INCLUDE_DIR . 'app/emails.php';
	require_once AS_INCLUDE_DIR . 'app/cookies.php';

	$userid = as_db_user_create($type, $firstname, $lastname, $gender, $country, $mobile, $email, $password, $handle, $level, as_remote_ip_address());
	as_db_points_update_ifuser($userid, null);
	as_db_uapprovecount_update();

	if ($confirmed)
		as_db_user_set_flag($userid, AS_USER_FLAGS_EMAIL_CONFIRMED, true);

	if (as_opt('show_notice_welcome'))
		as_db_user_set_flag($userid, AS_USER_FLAGS_WELCOME_NOTICE, true);

	$custom = as_opt('show_custom_welcome') ? trim(as_opt('custom_welcome')) : '';

	if (as_opt('confirm_user_emails') && $level < AS_USER_LEVEL_EXPERT && !$confirmed) {
		$confirm = strtr(as_lang('emails/welcome_confirm'), array(
			'^url' => as_get_new_confirm_url($userid, $handle),
		));

		if (as_opt('confirm_user_required'))
			as_db_user_set_flag($userid, AS_USER_FLAGS_MUST_CONFIRM, true);

	} else
		$confirm = '';

	// we no longer use the 'approve_user_required' option to set AS_USER_FLAGS_MUST_APPROVE; this can be handled by the Permissions settings

	as_send_notification($userid, $firstname . " " . $lastname, $email, $handle, as_lang('emails/welcome_subject'), as_lang('emails/welcome_body'), array(
		'^password' => isset($password) ? as_lang('main/hidden') : as_lang('users/password_to_set'),
		'^url' => as_opt('site_url'),
		'^custom' => strlen($custom) ? ($custom . "\n\n") : '',
		'^confirm' => $confirm,
	));

	as_db_notification_create($userid, as_lang_html_sub('notify/welcome', as_opt('site_title')), 'welcome-here', as_opt('site_url'), '');
	
	as_report_event('u_signup', $userid, $handle, as_cookie_get(), array(
		'email' => $email,
		'level' => $level,
	));

	return $userid;
}


/**
 * Delete $userid and all their likes and flags. Their posts will become anonymous.
 * Handles recalculations of likes and flags for posts this user has affected.
 * @param $userid
 * @return mixed
 */
function as_delete_user($userid)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'db/likes.php';
	require_once AS_INCLUDE_DIR . 'db/users.php';
	require_once AS_INCLUDE_DIR . 'db/post-update.php';
	require_once AS_INCLUDE_DIR . 'db/points.php';

	$postids = as_db_userlikeflag_user_get($userid); // posts this user has flagged or liked on, whose counts need updating

	as_db_user_delete($userid);
	as_db_uapprovecount_update();
	as_db_userpointscount_update();

	foreach ($postids as $postid) { // hoping there aren't many of these - saves a lot of new SQL code...
		as_db_post_recount_likes($postid);
		as_db_post_recount_flags($postid);
	}

	$postuserids = as_db_posts_get_userids($postids);

	foreach ($postuserids as $postuserid) {
		as_db_points_update_ifuser($postuserid, array('alikeds', 'qlikeds', 'positivelikeds', 'negativelikeds'));
	}
}


/**
 * Set a new email confirmation code for the user and send it out
 * @param $userid
 * @return mixed
 */
function as_send_new_confirm($userid)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'db/users.php';
	require_once AS_INCLUDE_DIR . 'db/selects.php';
	require_once AS_INCLUDE_DIR . 'app/emails.php';

	$userinfo = as_db_select_with_pending(as_db_user_account_selectspec($userid, true));

	$emailcode = as_db_user_rand_emailcode();

	if (!as_send_notification($userid, "BeExpress User", $userinfo['email'], $userinfo['handle'], as_lang('emails/confirm_subject'), as_lang('emails/confirm_body'), array(
			'^url' => as_get_new_confirm_url($userid, $userinfo['handle'], $emailcode),
			'^code' => $emailcode,
	))) {
		as_fatal_error('Could not send email confirmation');
	}
	as_db_notification_create($userid, as_lang('notify/confirm_email'), 'confrim-email', as_opt('site_url'), '');    
}


/**
 * Set a new email confirmation code for the user and return the corresponding link. If the email code is also sent then that value
 * is used. Otherwise, a new email code is generated
 * @param $userid
 * @param $handle
 * @param $emailcode
 * @return mixed|string
 */
function as_get_new_confirm_url($userid, $handle, $emailcode = null)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'db/users.php';

	if (!isset($emailcode)) {
		$emailcode = as_db_user_rand_emailcode();
	}
	as_db_user_set($userid, 'emailcode', $emailcode);

	return as_path_absolute('confirm', array('c' => $emailcode, 'u' => $handle));
}


/**
 * Complete the email confirmation process for the user
 * @param $userid
 * @param $email
 * @param $handle
 * @return mixed
 */
function as_complete_confirm($userid, $email, $handle)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'db/users.php';
	require_once AS_INCLUDE_DIR . 'app/cookies.php';

	as_db_user_set_flag($userid, AS_USER_FLAGS_EMAIL_CONFIRMED, true);
	as_db_user_set_flag($userid, AS_USER_FLAGS_MUST_CONFIRM, false);
	as_db_user_set($userid, 'emailcode', ''); // to prevent re-use of the code

	as_report_event('u_confirmed', $userid, $handle, as_cookie_get(), array(
		'email' => $email,
	));
}


/**
 * Set the user level of user $userid with $handle to $level (one of the AS_USER_LEVEL_* constraints in /as-include/app/users.php)
 * Pass the previous user level in $oldlevel. Reports the appropriate event, assumes change performed by the logged in user.
 * @param $userid
 * @param $handle
 * @param $level
 * @param $oldlevel
 */
function as_set_user_level($userid, $fullname, $handle, $email, $level, $oldlevel, $profiles)
{
	require_once AS_INCLUDE_DIR . 'db/users.php';
	
	if ($level >= AS_USER_LEVEL_SUPER) $newtype = 'SA';
	elseif ($level >= AS_USER_LEVEL_ADMIN) $newtype = 'A';
	elseif ($level >= AS_USER_LEVEL_MODERATOR) $newtype = 'U';
	elseif ($level >= AS_USER_LEVEL_EDITOR) $newtype = 'U';
	elseif ($level >= AS_USER_LEVEL_EXPERT) $newtype = 'U';
	elseif ($level >= AS_USER_LEVEL_APPROVED) $newtype = 'U';
	else $newtype = 'U';
	
	as_db_user_set($userid, 'level', $level);
	as_db_user_set($userid, 'type', $newtype);
	as_db_user_set($userid, 'profiles', $profiles.', '.$newtype);
		
	as_db_uapprovecount_update();

	if ($level >= AS_USER_LEVEL_APPROVED) {
		// no longer necessary as AS_USER_FLAGS_MUST_APPROVE is deprecated, but kept for posterity
		as_db_user_set_flag($userid, AS_USER_FLAGS_MUST_APPROVE, false);
	}

	as_send_notification($userid, $fullname, $email, $handle, 
		($oldlevel < $level) ? as_lang('emails/elevated_subject_up') : as_lang('emails/elevated_subject_down'),
		($oldlevel < $level) ? as_lang('emails/elevated_body_up') : as_lang('emails/elevated_body_down'),
		array(
			'^old_level' => as_html(as_user_level_string($oldlevel)),
			'^new_level' => as_html(as_user_level_string($level)),
			'^priviledge' => as_block_words_replace($params['text'], as_get_block_words_preg()),
			'^url' => as_opt('site_url'),
		));	
	
	as_db_notification_create($userid, ($oldlevel < $level) ? as_lang('notify/elevated_up') : as_lang('notify/elevated_down'), 'user-level', as_opt('site_url'), '');   

	as_report_event('u_elevation', as_get_logged_in_userid(), as_get_logged_in_handle(), as_cookie_get(), array(
		'userid' => $userid,
		'handle' => $handle,
		'level' => $level,
		'oldlevel' => $oldlevel,
		'fullname' => $fullname,
	));
}

/**
 * Set the status of user $userid with $handle to blocked if $blocked is true, otherwise to unblocked. Reports the appropriate
 * event, assumes change performed by the logged in user.
 * @param $userid
 * @param $handle
 * @param $blocked
 */
function as_set_user_blocked($userid, $handle, $blocked)
{
	require_once AS_INCLUDE_DIR . 'db/users.php';

	as_db_user_set_flag($userid, AS_USER_FLAGS_USER_BLOCKED, $blocked);
	as_db_uapprovecount_update();

	as_report_event($blocked ? 'u_block' : 'u_unblock', as_get_logged_in_userid(), as_get_logged_in_handle(), as_cookie_get(), array(
		'userid' => $userid,
		'handle' => $handle,
	));
}


/**
 * Start the 'I forgot my password' process for $userid, sending reset code
 * @param $userid
 * @return mixed
 */
function as_start_reset_user($userid)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'db/users.php';
	require_once AS_INCLUDE_DIR . 'app/options.php';
	require_once AS_INCLUDE_DIR . 'app/emails.php';
	require_once AS_INCLUDE_DIR . 'db/selects.php';

	as_db_user_set($userid, 'emailcode', as_db_user_rand_emailcode());

	$userinfo = as_db_select_with_pending(as_db_user_account_selectspec($userid, true));

	if (!as_send_notification($userid, "BeExpress User", $userinfo['email'], $userinfo['handle'], as_lang('emails/reset_subject'), as_lang('emails/reset_body'), array(
		'^code' => $userinfo['emailcode'],
		'^url' => as_path_absolute('reset', array('c' => $userinfo['emailcode'], 'e' => $userinfo['email'])),
	))) {
		as_fatal_error('Could not send reset password email');
	}
	as_db_notification_create($userid, as_lang('notify/reset_password'), 'reset-password', as_opt('site_url'), '');   
}


/**
 * Successfully finish the 'I forgot my password' process for $userid, sending new password
 *
 * @deprecated This function has been replaced by as_finish_reset_user since APS 1.8
 * @param $userid
 * @return mixed
 */
function as_complete_reset_user($userid)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'util/string.php';
	require_once AS_INCLUDE_DIR . 'app/options.php';
	require_once AS_INCLUDE_DIR . 'app/emails.php';
	require_once AS_INCLUDE_DIR . 'app/cookies.php';
	require_once AS_INCLUDE_DIR . 'db/selects.php';

	$password = as_random_alphanum(max(AS_MIN_PASSWORD_LEN, AS_NEW_PASSWORD_LEN));

	$userinfo = as_db_select_with_pending(as_db_user_account_selectspec($userid, true));

	if (!as_send_notification($userid, "BeExpress", $userinfo['email'], $userinfo['handle'], as_lang('emails/new_password_subject'), as_lang('emails/new_password_body'), array(
		'^password' => $password,
		'^url' => as_opt('site_url'),
	))) {
		as_fatal_error('Could not send new password - password not reset');
	}

	as_db_user_set_password($userid, $password); // do this last, to be safe
	as_db_user_set($userid, 'emailcode', ''); // so can't be reused

	as_report_event('u_reset', $userid, $userinfo['handle'], as_cookie_get(), array(
		'email' => $userinfo['email'],
	));
	as_db_notification_create($userid, as_lang('notify/reset_password'), 'reset-password', as_opt('site_url'), '');   
}


/**
 * Successfully finish the 'I forgot my password' process for $userid, cleaning the emailcode field and logging in the user
 * @param mixed $userId The userid identifiying the user who will have the password reset
 * @param string $newPassword The new password for the user
 * @return void
 */
function as_finish_reset_user($userId, $newPassword)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	// For as_db_user_set_password(), as_db_user_set()
	require_once AS_INCLUDE_DIR . 'db/users.php';

	// For as_set_signed_in_user()
	require_once AS_INCLUDE_DIR . 'app/options.php';

	// For as_cookie_get()
	require_once AS_INCLUDE_DIR . 'app/cookies.php';

	// For as_db_select_with_pending(), as_db_user_account_selectspec()
	require_once AS_INCLUDE_DIR . 'db/selects.php';

	// For as_set_signed_in_user()
	require_once AS_INCLUDE_DIR . 'app/users.php';

	as_db_user_set_password($userId, $newPassword);

	as_db_user_set($userId, 'emailcode', ''); // to prevent re-use of the code

	$userInfo = as_db_select_with_pending(as_db_user_account_selectspec($userId, true));

	as_set_signed_in_user($userId, $userInfo['handle'], false, $userInfo['sessionsource']); // reinstate this specific session

	as_report_event('u_reset', $userId, $userInfo['handle'], as_cookie_get(), array(
		'email' => $userInfo['email'],
	));
}

/**
 * Flush any information about the currently logged in user, so it is retrieved from database again
 */
function as_logged_in_user_flush()
{
	global $as_cached_logged_in_user;

	$as_cached_logged_in_user = null;
}


/**
 * Set the avatar of $userid to the image in $imagedata, and remove $oldblobid from the database if not null
 * @param $userid
 * @param $imagedata
 * @param $oldblobid
 * @return bool
 */
function as_set_user_avatar($userid, $imagedata, $oldblobid = null)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	require_once AS_INCLUDE_DIR . 'util/image.php';

	$imagedata = as_image_constrain_data($imagedata, $width, $height, as_opt('avatar_store_size'));

	if (isset($imagedata)) {
		require_once AS_INCLUDE_DIR . 'app/blobs.php';

		$newblobid = as_create_blob($imagedata, 'jpeg', null, $userid, null, as_remote_ip_address());

		if (isset($newblobid)) {
			as_db_user_set($userid, array(
				'avatarblobid' => $newblobid,
				'avatarwidth' => $width,
				'avatarheight' => $height,
			));

			as_db_user_set_flag($userid, AS_USER_FLAGS_SHOW_AVATAR, true);
			as_db_user_set_flag($userid, AS_USER_FLAGS_SHOW_GRAVATAR, false);

			if (isset($oldblobid))
				as_delete_blob($oldblobid);

			return true;
		}
	}

	return false;
}
