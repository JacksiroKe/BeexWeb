<?php
/*
	Plugin Name: On-Site-Notifications
	Plugin URI: 
	Plugin Description: Facebook-like / Stackoverflow-like notifications on your site that can replace all email-notifications.
	Plugin Version: 1.3.0
	Plugin Date: 2018-08-23
	Plugin Author: apspro.com
	Plugin Author URI: apspro
	Plugin License: GPLv3
	Plugin Minimum AppSmata Version: 1.6
	Plugin Update Check URI: 

	This program is free software. You can redistribute and modify it
	under the terms of the GNU General Public License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html

*/

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

// language file
as_register_plugin_phrases('langs/as-notifications-lang-*.php', 'notifications_lang');

// page for ajax
as_register_plugin_module('page', 'as-notifications-page.php', 'as_notifications_page', 'Notifications Page');

// layer
as_register_plugin_layer('as-notifications-layer.php', 'Notifications Layer');

// admin
as_register_plugin_module('module', 'as-notifications-admin.php', 'notifications_admin', 'Notifications Admin');

// track events
as_register_plugin_module('event', 'as-notifications-event.php', 'notifications_event', 'History Check Mod');


// cache function for notification count +1
function apspro_notifycount_increase($userid)
{
	if(!empty($userid))
	{
		// central as_notifycount table
		as_db_query_sub('
			INSERT INTO ^notifycount (userid, notifycount) VALUES(#, 1) 
			ON DUPLICATE KEY UPDATE userid = #, notifycount = (notifycount+1)
			',
			$userid, $userid
		);
	}
}

// cache function to nill the notification count
function apspro_notifycount_nill($userid)
{
	if(!empty($userid))
	{
		// central as_notifycount table
		as_db_query_sub('
			INSERT INTO ^notifycount (userid, notifycount) VALUES(#, 0) 
			ON DUPLICATE KEY UPDATE userid = #, notifycount = 0
			',
			$userid, $userid
		);
	}
}
