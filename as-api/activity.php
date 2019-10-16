<?php
	require_once '../as-include/as-base.php';
	require_once '../as-include/db/users.php';
	require_once '../as-include/db/selects.php';

	require_once '../as-include/app/format.php';
	require_once '../as-include/app/users.php';

	
	$inemailhandle = as_post_text('emailhandle');
	$inpassword = as_post_text('password');
	$inremember = as_post_text('remember');
	
	$success = 0;
	$message = '';
	$data = array();
	if (strlen($inemailhandle) && strlen($inpassword)) {
		require_once AS_INCLUDE_DIR . 'app/limits.php';

		if (as_user_limits_remaining(AS_LIMIT_SIGNINS)) {
			as_limits_increment(null, AS_LIMIT_SIGNINS);

			$errors = array();

			if (as_opt('allow_login_email_only') || strpos($inemailhandle, '@') !== false) { // handles can't contain @ symbols
				$matchusers = as_db_user_find_by_email($inemailhandle);
			} else {
				$matchusers = as_db_user_find_by_handle($inemailhandle);
			}

			if (count($matchusers) == 1) { // if matches more than one (should be impossible), don't log in
				$inuserid = $matchusers[0];
				$userinfo = as_db_select_with_pending(as_db_user_account_selectspec($inuserid, true));

				$legacyPassOk = hash_equals(strtolower($userinfo['passcheck']), strtolower(as_db_calc_passcheck($inpassword, $userinfo['passsalt'])));

				if (AS_PASSWORD_HASH) {
					$haspassword = isset($userinfo['passhash']);
					$haspasswordold = isset($userinfo['passsalt']) && isset($userinfo['passcheck']);
					$passOk = password_verify($inpassword, $userinfo['passhash']);

					if (($haspasswordold && $legacyPassOk) || ($haspassword && $passOk)) {
						// upgrade password or rehash, when options like the cost parameter changed
						if ($haspasswordold || password_needs_rehash($userinfo['passhash'], PASSWORD_BCRYPT)) {
							as_db_user_set_password($inuserid, $inpassword);
						}
					} else {
						$success = 0;
						$message = as_lang('users/password_wrong');
					}
				} else {
					if (!$legacyPassOk) {
						$success = 0;
						$message = as_lang('users/password_wrong');
					}
				}

				if (!isset($errors['password'])) {
					as_set_signed_in_user($inuserid, $userinfo['handle'], !empty($inremember));
					$success = 1;
					$message = 'Logged in successfully';
					$data['userid'] = $inuserid; 
					$data['firstname'] = $userinfo['firstname'];
					$data['lastname'] = $userinfo['lastname'];
					$data['gender'] = $userinfo['gender'];
					$data['country'] = $userinfo['country'];
					$data['mobile'] = $userinfo['mobile'];
					$data['email'] = $userinfo['email'];
					$data['level'] = $userinfo['level'];
					$data['handle'] = $userinfo['handle'];
					$data['created'] = $userinfo['created'];
					$data['signedin'] = $userinfo['signedin'];
					$data['avatarblobid'] = $userinfo['avatarblobid'];
					$data['points'] = $userinfo['points'];
					$data['wallposts'] = $userinfo['wallposts'];
											
					$topath = as_post_text('to');
				}

			} else {
				$success = 0;
				$message = as_lang('users/user_not_found');
			}
		} else {
			$success = 0;
			$message = as_lang('users/login_limit');
		}

	} else {
		$success = 0;
		$message = 'You need to enter a username or email and a  password to proceed';
	}
	
	$output = json_encode(array('success' => $success, 'message' => $message, 'data' => $data));	
	echo $output;