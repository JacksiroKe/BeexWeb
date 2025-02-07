<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	File: as-plugin/facebook-login/as-plugin.php
	Description: Initiates Facebook login plugin


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

/*
	Plugin Name: Facebook Login
	Plugin URI:
	Plugin Description: Allows users to log in via Facebook
	Plugin Version: 1.1.5
	Plugin Date: 2012-09-13
	Plugin Author: AppSmata
	Plugin Author URI: http://www.appsmata.org/
	Plugin License: GPLv2
	Plugin Minimum AppSmata Version: 1.5
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI:
*/


if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


// login modules don't work with external user integration
if (!AS_FINAL_EXTERNAL_USERS) {
	as_register_plugin_module('login', 'as-facebook-login.php', 'as_facebook_login', 'Facebook Login');
	as_register_plugin_module('page', 'as-facebook-login-page.php', 'as_facebook_login_page', 'Facebook Login Page');
	as_register_plugin_layer('as-facebook-layer.php', 'Facebook Login Layer');
}
