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

	class as_html_theme_layer extends as_html_theme_base {

		function head_script(){
			as_html_theme_base::head_script();
			// only load if enabled and user logged-in
			if(as_opt('as_notifications_enabled') && as_is_logged_in()) {
				$this->output('<script type="text/javascript">
						var eventnotifyAjaxURL = "'.as_path('eventnotify').'";
					</script>');
				$this->output('<script type="text/javascript" src="'.AS_HTML_THEME_LAYER_URLTOROOT.'script.js"></script>');
				$this->output('<link rel="stylesheet" type="text/css" href="'.AS_HTML_THEME_LAYER_URLTOROOT.'styles.css">');

				// hack for snow flat theme (aps v1.7) to show the notification icon outside the user's drop down
				if(as_opt('site_theme')=='SnowFlat') {
					$this->output('
					<script type="text/javascript">
						$(document).ready(function(){
							// $("#osnbox").detach().appendTo(".qam-account-items-wrapper");
							var elem = $("#osnbox").detach();
							$(".qam-account-items-wrapper").prepend(elem);
						});
					</script>
					');
				}

				// hack for snow theme (aps v1.6) to position the notification box more to the right
				if(as_opt('site_theme')=='Snow') {
					$this->output('
					<style type="text/css">
						#nfyWrap {
							left:-100px;
						}
					</style>
					');
				}

				// from aps v1.7 we can use: $isRTL = $this->isRTL; but prior aps versions can not, so we provide an admin option
				if(as_opt('as_notifications_rtl')) {
					$this->output('
					<style type="text/css">
						#nfyReadClose {
							float:left !important;
						}
						.nfyWrap .nfyTop {
							text-align:right;
						}
						.nfyContainer {
							direction: rtl !important;
							text-align: right !important;
						}
						.nfyWrap .nfyFooter {
							text-align:left;
						}
						.nfyIcon {
							float:right;
						}
						.nfyWrap .nfyItemLine {
							float:right;
							margin-right:5px;
						}
						/* Snow Flat hacks */
						.qam-account-items-wrapper #osnbox {
							float: right;
							margin-right:-30px;
						}
						.qam-account-items-wrapper .nfyWrap {
							top: 31px;
							left: 0;
						}
					</style>
					');
				}

			} // end enabled
		} // end head_script

		function doctype() {
			/* The following code originates from aps plugin "History" by NoahY and has been modified by apspro.com
			 * It is licensed under GPLv3 http://www.gnu.org/licenses/gpl.html
			 * Link to plugin: https://github.com/NoahY/aps-history
			 */
			$userid = as_get_logged_in_userid();
			if(as_opt('as_notifications_enabled') && $userid) {

				$last_visit = $this->getLastVisitForUser($userid);

				// select and count all in_eventcount that are newer as last visit
				$eventcount = $this->getEventCount($last_visit, $userid);

				// apspro notification tooltip
				if ($eventcount > 0) {
					if ($eventcount == 1) {  // only one event
						$tooltip = as_lang('notifications_lang/one_notification');
					} else {
						$tooltip = $eventcount.' '.as_lang('notifications_lang/x_notifications');
					}
					$classSuffix = 'new';  // add notify bubble to user navigation highlighted
				}
				else {
					$tooltip = as_lang('notifications_lang/show_notifications');
					$eventcount = as_opt('as_notifications_nill');
					$classSuffix = 'nill';  // add notify bubble to user navigation
				}

				$html = '<div id="osnbox">
							<a class="osn-new-events-link" title="'.$tooltip.'"><span class="notifybub ntfy-event-'. $classSuffix.'">'.$eventcount.'</span></a>
						</div>';

				// add to user panel
				$this->content['loggedin']['suffix'] = @$this->content['loggedin']['suffix']. ' ' . $html;
			}

			as_html_theme_base::doctype();
		}

		/**
		 * @param int $last_visit
		 * @param mixed $userid
		 * @return int
		 */
		private function getEventCount($last_visit, $userid)
		{
			$currentTime = (int)as_opt('db_time');
			$maxageTime = $currentTime - (int)as_opt('as_notifications_maxage') * 86400;
			$fromTime = max($maxageTime, $last_visit);

			$eventlogCount = as_db_read_one_value(as_db_query_sub(
				'SELECT COUNT(event) FROM ^eventlog ' .
				'WHERE datetime >= FROM_UNIXTIME(#) AND (' .
				'(userid = # AND event LIKE "in_%") OR ' .
				'(event IN ("u_message", "u_wall_post") AND params LIKE "userid=#\t%")' .
				')',
				$last_visit,
				$userid,
				$userid
			));

			$pluginCount = as_db_read_one_value(as_db_query_sub(
				'SELECT COUNT(*) FROM ^notifications ' .
				'WHERE user_id = # AND created_at >= FROM_UNIXTIME(#)',
				$userid, $fromTime
			));

			return $eventlogCount + $pluginCount;
		}

		/**
		 * @param $userid
		 * @return int
		 */
		private function getLastVisitForUser($userid)
		{
			$last_visit = (int)as_db_read_one_value(
				as_db_query_sub(
					'SELECT UNIX_TIMESTAMP(meta_value) FROM ^usermeta WHERE user_id=# AND meta_key=$',
					$userid, 'visited_profile'
				),
				true
			);

			// first time visitor, we set the last visit manually in the past
			if (is_null($last_visit)) {
				$last_visit = 0;
			}
			return $last_visit;
		}

	} // end as_html_theme_layer
