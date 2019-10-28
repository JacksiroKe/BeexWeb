<?php
	require_once '../as-include/as-base.php';
	require_once '../as-include/db/users.php';
	require_once '../as-include/db/selects.php';

	require_once '../as-include/app/format.php';
	require_once '../as-include/app/users.php';
	
	$infirstname = as_post_text('firstname');
	$inlastname = as_post_text('lastname');
	$ingender = as_post_text('gender');
	$incountry = as_post_text('country');
	$inmobile = as_post_text('mobile');
	$inemail = as_post_text('email');
	$inhandle = as_post_text('handle');
	$inpassword = as_post_text('password');
	$inremember = as_post_text('remember');
	
	$data = array();
	
	if (as_opt('suspend_register_users')) {
		$data['success'] = 0;
		$data['message'] = as_lang_html('users/register_suspended');
	}

	else if (as_user_permit_error()) {
		$data['success'] = 0;
		$data['message'] = as_lang_html('users/no_permission');
	}

	else if (empty($infirstname)) {
		$data['success'] = 3;
		$data['message'] = 'Your first name appears to be invalid';
	}
	
	else if (empty($inlastname)) {
		$data['success'] = 3;
		$data['message'] = 'Your last name appears to be invalid';
	}
	
	else if (empty($ingender)) {
		$data['success'] = 3;
		$data['message'] = 'You have not selected your gender';
	}
	
	else if (empty($incountry)) {
		$data['success'] = 3;
		$data['message'] = 'You have not selected your country';
	}
	
	else if (empty($inmobile)) {
		$data['success'] = 3;
		$data['message'] = 'Your mobile number appears to be invalid';
	}
	
	else if (empty($inemail)) {
		$data['success'] = 3;
		$data['message'] = 'Your email address appears to be invalid';
	}
	
	else if (empty($inhandle)) {
		$data['success'] = 3;
		$data['message'] = 'Your username appears to be invalid';
	}
	
	else {
		require_once AS_INCLUDE_DIR . 'app/limits.php';

		if (as_user_limits_remaining(AS_LIMIT_SIGNUPS)) {
			require_once AS_INCLUDE_DIR . 'app/users-edit.php';

			// core validation
			$errors = array_merge(
				as_handle_email_filter($inhandle, $inemail),
				as_password_validate($inpassword)
			);

			if (empty($errors)) {
				// register and redirect
				as_limits_increment(null, AS_LIMIT_SIGNUPS);

				$userid = as_create_department_user($infirstname, $inlastname, $ingender, $incountry, $inmobile, $inemail, $inpassword, $inhandle);
				as_set_signed_in_user($userid, $inhandle);
			
				$userinfo = as_db_select_with_pending(as_db_user_account_selectspec($userid, true));
				
				$data['success'] = 1;
				$data['message'] = 'Signed in successfully';
				$data['userid'] = $userid; 
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
			}

		} else {
			$data['success'] = 2;
			$data['message'] = as_lang('users/register_limit');
		}
	} 
	
	$output = json_encode(array('data' => $data));	
	echo $output;