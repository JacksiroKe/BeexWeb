<?php
/*
	Plugin Name: On-Site-Notifications
	Plugin URI: http://www.apspro.com/plugins/on-site-notifications
	Plugin Description: Facebook-like / Stackoverflow-like notifications on your question2answer forum that can replace all email-notifications.
	Plugin Version: → see as-plugin.php
	Plugin Date: → see as-plugin.php
	Plugin Author: apspro.com
	Plugin Author URI: http://www.apspro.com/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: → see as-plugin.php
	Plugin Update Check URI: https://raw.githubusercontent.com/apspro/apspro-on-site-notifications/master/as-plugin.php

	This program is free software. You can redistribute and modify it
	under the terms of the GNU General Public License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html

*/

	class notifications_admin {

		// initialize db-table 'eventlog' if it does not exist yet
		function init_queries($tableslc) {
			require_once AS_INCLUDE_DIR.'app/users.php';
			require_once AS_INCLUDE_DIR.'db/maxima.php';
			require_once AS_INCLUDE_DIR.'app/options.php';

			$result = array();

			$tablename = as_db_add_table_prefix('eventlog');

			// check if event logger has been initialized already (check for one of the options and existing table)
			if(as_opt('event_logger_to_database') && in_array($tablename, $tableslc)) {
				// options exist, but check if really enabled
				if(as_opt('event_logger_to_database')=='' && as_opt('event_logger_to_files')=='') {
					// enabled database logging
					as_opt('event_logger_to_database', 1);
				}
			}
			else {
				// not enabled, let's enable the event logger

				// set option values for event logger
				as_opt('event_logger_to_database', 1);
				as_opt('event_logger_to_files', '');
				as_opt('event_logger_directory', '');
				as_opt('event_logger_hide_header', '');

				if (!in_array($tablename, $tableslc)) {
					$result[] = 'CREATE TABLE IF NOT EXISTS ^eventlog ('.
						'datetime DATETIME NOT NULL,'.
						'ipaddress VARCHAR (15) CHARACTER SET ascii,'.
						'userid '.as_get_mysql_user_column_type().','.
						'handle VARCHAR('.AS_DB_MAX_HANDLE_LENGTH.'),'.
						'cookieid BIGINT UNSIGNED,'.
						'event VARCHAR (20) CHARACTER SET ascii NOT NULL,'.
						'params VARCHAR (800) NOT NULL,'.
						'KEY datetime (datetime),'.
						'KEY ipaddress (ipaddress),'.
						'KEY userid (userid),'.
						'KEY event (event)'.
					') ENGINE=MyISAM DEFAULT CHARSET=utf8';
				}
			}
			// memo: would be best to check if plugin is installed in as-plugin folder or using plugin_exists()
			// however this functionality is not available in aps v1.6.3

			// create table as_usermeta which stores the last visit of each user
			$tablename2 = as_db_add_table_prefix('usermeta');
			if (!in_array($tablename2, $tableslc)) {
				$result[] =
					'CREATE TABLE IF NOT EXISTS ^usermeta (
					meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					user_id bigint(20) unsigned NOT NULL,
					meta_key varchar(255) DEFAULT NULL,
					meta_value longtext,
					PRIMARY KEY (meta_id),
					UNIQUE (user_id,meta_key)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
				;
			}

			// create table as_usermeta which stores the last visit of each user
			$tablename3 = as_db_add_table_prefix('notifications');
			if (!in_array($tablename3, $tableslc)) {
				$result[] =
					'CREATE TABLE IF NOT EXISTS ^notifications (' .
					'plugin_id VARCHAR(50) NOT NULL, ' .
					'event_text VARCHAR(2000) NOT NULL, ' .
					'icon_class VARCHAR(50) NOT NULL, ' .
					'user_id ' . as_get_mysql_user_column_type() . ' NOT NULL, ' .
					'created_at DATETIME NOT NULL, ' .
					'KEY ^notifications_idx1 (user_id, created_at), ' .
					'KEY ^notifications_idx2 (plugin_id, user_id, created_at)' .
					') ENGINE=MyISAM  DEFAULT CHARSET=utf8'
				;
			}

			return $result;
		} // end init_queries

		// option's value is requested but the option has not yet been set
		function option_default($option) {
			switch($option) {
				case 'as_notifications_enabled':
					return 1; // true
				case 'as_notifications_nill':
					return 'N'; // days
				case 'as_notifications_maxage':
					return 365; // days
				case 'as_notifications_maxevshow':
					return 100; // max events to show in notify box
				case 'as_notifications_newwindow':
					return 1; // true
				case 'as_notifications_rtl':
					return 0; // false
				default:
					return null;
			}
		}

		function allow_template($template) {
			return ($template!='admin');
		}

		function admin_form(&$as_content){

			// process the admin form if admin hit Save-Changes-button
			$ok = null;
			if (as_clicked('as_notifications_save')) {
				as_opt('as_notifications_enabled', (bool)as_post_text('as_notifications_enabled')); // empty or 1
				as_opt('as_notifications_nill', as_post_text('as_notifications_nill')); // string
				as_opt('as_notifications_maxevshow', (int)as_post_text('as_notifications_maxevshow')); // int
				as_opt('as_notifications_newwindow', (bool)as_post_text('as_notifications_newwindow')); // int
				as_opt('as_notifications_rtl', (bool)as_post_text('as_notifications_rtl')); // int
				$ok = as_lang('admin/options_saved');
			}

			// form fields to display frontend for admin
			$fields = array();

			$fields[] = array(
				'type' => 'checkbox',
				'label' => as_lang('notifications_lang/enable_plugin'),
				'tags' => 'name="as_notifications_enabled"',
				'value' => as_opt('as_notifications_enabled'),
			);


			$fields[] = array(
				'type' => 'input',
				'label' => as_lang('notifications_lang/no_notifications_label'),
				'tags' => 'name="as_notifications_nill" style="width:100px;"',
				'value' => as_opt('as_notifications_nill'),
			);

			$fields[] = array(
				'type' => 'input',
				'label' => as_lang('notifications_lang/admin_maxeventsshow'),
				'tags' => 'name="as_notifications_maxevshow" style="width:100px;"',
				'value' => as_opt('as_notifications_maxevshow'),
			);

			$fields[] = array(
				'type' => 'checkbox',
				'label' => as_lang('notifications_lang/admin_newwindow'),
				'tags' => 'name="as_notifications_newwindow"',
				'value' => as_opt('as_notifications_newwindow'),
			);

			$fields[] = array(
				'type' => 'checkbox',
				'label' => as_lang('notifications_lang/admin_rtl'),
				'tags' => 'name="as_notifications_rtl"',
				'value' => as_opt('as_notifications_rtl'),
			);

			$fields[] = array(
				'type' => 'static',
				'note' => '<span style="font-size:12px;color:#789;">'.strtr( as_lang('notifications_lang/contact'), array(
							'^1' => '<a target="_blank" href="http://www.apspro.com/plugins/on-site-notifications">',
							'^2' => '</a>'
						  )).'</span>',
			);

			return array(
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'fields' => $fields,
				'buttons' => array(
					array(
						'label' => as_lang_html('main/save_button'),
						'tags' => 'name="as_notifications_save"',
					),
				),
			);
		}
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/
