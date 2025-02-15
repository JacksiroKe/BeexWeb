<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	File: as-plugin/facebook-login/as-facebook-login-page.php
	Description: Page which performs Facebook login action


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

class as_facebook_login_page
{
	private $directory;

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
	}

	public function match_request($request)
	{
		return ($request == 'facebook-login');
	}

	public function process_request($request)
	{
		if ($request == 'facebook-login') {
			$app_id = as_opt('facebook_app_id');
			$app_secret = as_opt('facebook_app_secret');
			$tourl = as_get('to');
			if (!strlen($tourl))
				$tourl = as_path_absolute('');

			if (strlen($app_id) && strlen($app_secret)) {
				require_once $this->directory . 'facebook.php';

				$facebook = new Facebook(array(
					'appId' => $app_id,
					'secret' => $app_secret,
					'cookie' => true,
				));

				$fb_userid = $facebook->getUser();

				if ($fb_userid) {
					try {
						$user = $facebook->api('/me?fields=email,name,verified,location,website,about,picture.width(250)');

						if (is_array($user))
							as_log_in_external_user('facebook', $fb_userid, array(
								'email' => @$user['email'],
								'handle' => @$user['name'],
								'confirmed' => @$user['verified'],
								'name' => @$user['name'],
								'location' => @$user['location']['name'],
								'website' => @$user['website'],
								'about' => @$user['bio'],
								'avatar' => strlen(@$user['picture']['data']['url']) ? as_retrieve_url($user['picture']['data']['url']) : null,
							));

					} catch (FacebookApiException $e) {
					}

				} else {
					as_redirect_raw($facebook->getLoginUrl(array('redirect_uri' => $tourl)));
				}
			}

			as_redirect_raw($tourl);
		}
	}
}
