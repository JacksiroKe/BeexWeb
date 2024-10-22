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

	class as_notifications_page {

		var $directory;
		var $urltoroot;

		function load_module($directory, $urltoroot)
		{
			$this->directory = $directory;
			$this->urltoroot = $urltoroot;
		}

		// for display in admin interface under admin/pages
		function suggest_requests()
		{
			return array(
				array(
					'title' => 'Notifications Page', // title of page
					'request' => 'eventnotify', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}

		// for url query
		function match_request($request)
		{
			if ($request=='eventnotify') {
				return true;
			}
			return false;
		}

		function process_request($request)
		{

			// we received post data, it is the ajax call!
			$transferString = as_post_text('ajax');
			if( $transferString !== null ) {

				// prevent empty userid
				$userid = as_get_logged_in_userid();
				if(empty($userid)) {
					echo 'Userid is empty!';
					return;
				}

				// ajax return all user events
				if(isset($userid) && $transferString=='receiveNotify'){
					$last_visit = as_db_read_one_value(
						as_db_query_sub(
							'SELECT UNIX_TIMESTAMP(meta_value) FROM ^usermeta WHERE user_id=# AND meta_key="visited_profile"',
							$userid
						), true
					);

					$event_query = $this->getEventsForUser($userid);

					$events = array();
					$postids = array();
					$count = 0;
					while ( ($event=as_db_read_one_assoc($event_query,true)) !== null ) {
						if(preg_match('/postid=([0-9]+)/',$event['params'],$m) === 1) {
							$event['postid'] = (int)$m[1];
							$postids[] = (int)$m[1];
							$events[$m[1].'_'.$count++] = $event;
						}
						// private message
						if($event['event']=='u_message') {
							// example of $event['params']: userid=1  handle=admin  messageid=4  message=hi admin, how are you?
							$ustring = $event['params'];

							// get messageid
							if(preg_match('/messageid=([0-9]+)/',$ustring,$m) === 1) {
								$event['messageid'] = (int)$m[1];
							}

							// needed for function as_post_userid_to_handle()
							require_once AS_INCLUDE_DIR.'app/posts.php';
							// get handle from userid, memo: userid from receiver is saved in params (the acting userid is the sender)
							$event['handle'] = as_post_userid_to_handle($event['userid']);

							// get message preview by cutting out the string
							$event['message'] = substr($ustring,strpos($ustring,'message=')+8, strlen($ustring)-strpos($ustring,'message=')+8);

							$events[$m[1].'_'.$count++] = $event;
						}
						// wall post
						else if($event['event']=='u_wall_post') {
							// example of $event['params']: userid=1	handle=admin	messageid=8	content=hi admin!	format=	text=hi admin!
							$ustring = $event['params'];

							// get messageid
							if(preg_match('/messageid=([0-9]+)/',$ustring,$m) === 1) {
								$event['messageid'] = (int)$m[1];
							}

							// needed for function as_post_userid_to_handle()
							require_once AS_INCLUDE_DIR.'app/posts.php';
							// get handle from userid, memo: userid from receiver is saved in params (the acting userid is the sender)
							$event['handle'] = as_post_userid_to_handle($event['userid']);

							// get message preview by cutting out the string
							$event['message'] = substr($ustring,strpos($ustring,'text=')+5, strlen($ustring)-strpos($ustring,'text=')+5);

							$events[$m[1].'_'.$count++] = $event;
						}
						else if($event['event'] === 'notifications_plugin') {
							$events['_' . $count++] = $event;
						}
					}

					// get post info, also make sure that post exists
					$posts = null;
					if(!empty($postids)) {
						$post_query = as_db_read_all_assoc(
							as_db_query_sub(
								'SELECT postid, type, parentid, BINARY title as title FROM ^posts 
									WHERE postid IN ('.implode(',',$postids).')'
							)
						);
						foreach($post_query as $post) {
							// save postids as index in array $posts with the $post content
							$posts[(string)$post['postid']] = $post;
						}
					}

					// List all events
					$notifyBoxEvents = '<div id="nfyWrap" class="nfyWrap">
						<div class="nfyTop">'.as_lang('notifications_lang/my_notifications').' <a id="nfyReadClose">'.as_lang('notifications_lang/close').' | × |</a> </div>
						<div class="nfyContainer">
							<div id="nfyContainerInbox">
						';

					foreach($events as $postid_string => $event) {
						// $postid_string, e.g. 32_1 (32 is postid, 1 is global event count)
						$type = $event['event'];

						if($type=='u_message') {
							$eventName = as_lang('notifications_lang/you_received').' ';
							$itemIcon = '<div class="nicon nmessage"></div>';
							$activity_url = as_path_absolute('message').'/'.$event['handle'];
							$linkTitle = as_lang('notifications_lang/message_from').' '.$event['handle'];
						}
						else if($type=='u_wall_post') {
							$eventName = as_lang('notifications_lang/you_received').' ';
							$itemIcon = '<div class="nicon nwallpost"></div>';
							// create link to own wall, needs handle
							require_once AS_INCLUDE_DIR.'app/posts.php';
							$userhandle = as_post_userid_to_handle($userid);
							// from v1.7 require_once AS_INCLUDE_DIR.'app/users.php'; and as_userid_to_handle($userid);
							$activity_url = as_path_absolute('user').'/'.$userhandle.'/wall';
							$linkTitle = as_lang('notifications_lang/wallpost_from').' '.$event['handle'];
						}
						else if($type=='notifications_plugin') {
							$eventName = ''; // Just to make compiler happy
							$itemIcon = '<div class="nicon ' . $event['icon_class'] . '"></div>';
							$activity_url = ''; // Just to make compiler happy
							$linkTitle = ''; // Just to make compiler happy
						}
						else {
							// a_post, c_post, q_vote_up, a_vote_up, q_vote_down, a_vote_down
							$postid = preg_replace('/_.*/','', $postid_string);

							// assign post content (postid,type,parentid,title) if available
							$post = @$posts[$postid];

							$params = $this->getParamsAsArray($event);

							$activity_url = '';
							$linkTitle = '';

							// comment or answer
							if(isset($post) && strpos($event['event'],'q_') !== 0 && strpos($event['event'],'in_q_') !== 0) {
								if(!isset($params['parentid'])) {
									$params['parentid'] = $post['parentid'];
								}

								$parent = as_db_select_with_pending(as_db_full_post_selectspec($userid, $params['parentid']));
								if($parent['type'] === 'A') {
									$parent = as_db_select_with_pending(as_db_full_post_selectspec($userid, $parent['parentid']));
								}

								$anchor = as_anchor((strpos($event['event'],'a_') === 0 || strpos($event['event'],'in_a_') === 0?'A':'C'), $params['postid']);
								$activity_url = as_path_absolute(as_q_request($parent['postid'], $parent['title']), null, $anchor);
								$linkTitle = $parent['title'];
							}
							else if(isset($post)) { // question
								if(!isset($params['title'])) {
									$params['title'] = $posts[$params['postid']]['title'];
								}
								if($params['title'] !== null) {
									$qTitle = as_db_read_one_value( as_db_query_sub("SELECT title FROM `^posts` WHERE `postid` = ".$params['postid']." LIMIT 1"), true );
									if (!isset($qTitle)) {
										$qTitle = '';
									}
									$activity_url = as_path_absolute(as_q_request($params['postid'], $qTitle), null, null);
									$linkTitle = $qTitle;
								}
							}

							// event name
							if($type=='in_c_question' || $type=='in_c_answer' || $type=='in_c_comment') { // added in_c_comment
								$eventName = as_lang('notifications_lang/in_comment');
								$itemIcon = '<div class="nicon ncomment"></div>';
							}
							else if($type=='in_q_vote_up' || $type=='in_a_vote_up') {
								$eventName = as_lang('notifications_lang/in_upvote');
								$itemIcon = '<div class="nicon nvoteup"></div>';
							}
							else if($type=='in_q_vote_down' || $type=='in_a_vote_down') {
								$eventName = as_lang('notifications_lang/in_downvote');
								$itemIcon = '<div class="nicon nvotedown"></div>';
							}
							else if($type=='in_a_question') {
								$eventName = as_lang('notifications_lang/in_answer');
								$itemIcon = '<div class="nicon nanswer"></div>';
							}
							else if($type=='in_a_select') {
								$eventName = as_lang('notifications_lang/in_bestanswer');
								$itemIcon = '<div class="nicon nbestanswer"></div>';
							}
							else {
								// ignore other events such as in_c_flag
								continue;
							}

						} // end a_post, c_post, q_vote_up, a_vote_up, q_vote_down, a_vote_down

						$eventtime = $event['datetime'];

						$whenhtml = as_html(as_time_to_string(as_opt('db_time')-$eventtime));
						$when = as_lang_html_sub('main/x_ago', $whenhtml);

						// extra CSS for highlighting new events
						$cssNewEv = '';
						if($eventtime > $last_visit) {
							$cssNewEv = '-new';
						}

						// if post has been deleted there is no link, dont output
						if($activity_url == '' && $type !== 'notifications_plugin') {
							continue;
						} else {
							$eventHtml = $type === 'notifications_plugin'
								? $event['event_text']
								: $eventName . ' <a ' . ($type == 'u_message' || $type == 'u_wall_post' ? 'title="' . $event['message'] . '" ' : '') . 'href="' . $activity_url . '"' . (as_opt('as_notifications_newwindow') ? ' target="_blank"' : '') . '>' . htmlentities($linkTitle) . '</a>';

							$notifyBoxEvents .= '<div class="itemBox'.$cssNewEv.'">
								'.$itemIcon.'
								<div class="nfyItemLine">
									<p class="nfyWhat">
										'.$eventHtml . '
									</p>
									<p class="nfyTime">'.$when.'</p>
								</div>
							</div>';
						}
					} // END FOREACH

					$notifyBoxEvents .= '</div>
						</div>
						<div class="nfyFooter">
							<a href="http://www.apspro.com/">by apspro.com</a>
						</div>
					</div>
					';

					header('Access-Control-Allow-Origin: '.as_path(null));
					header("Content-type: text/html; charset=utf-8");
					echo $notifyBoxEvents;

					$this->markAsReadForUserId($userid);

					exit();
				} // END AJAX RETURN
				else {
					echo 'Unexpected problem detected! No userid, no transfer string.';
					exit();
				}
			}


			/* start */
			$as_content = as_content_prepare();

			$as_content['title'] = ''; // page title

			// return if not admin!
			if(as_get_logged_in_level() < AS_USER_LEVEL_ADMIN) {
				$as_content['error'] = '<p>Access denied</p>';
				return $as_content;
			}
			else {
				$as_content['custom'] = '<p>Hi Admin, it actually makes no sense to call the Ajax URL directly.</p>';
			}

			return $as_content;
		}

		/**
		 * Update database entry so that all user notifications are seen as read
		 *
		 * @param $userid
		 */
		private function markAsReadForUserId($userid)
		{
			as_db_query_sub(
				'INSERT INTO ^usermeta (user_id,meta_key,meta_value) VALUES(#,$,NOW()) ON DUPLICATE KEY UPDATE meta_value=NOW()',
				$userid, 'visited_profile'
			);
		}

		/**
		 * @param $event
		 * @return array
		 */
		private function getParamsAsArray($event)
		{
			$params = array();
			// explode string to array with values (memo: leave "\t", '\t' will cause errors)
			$paramsa = explode("\t", $event['params']);
			foreach ($paramsa as $param) {
				$parama = explode('=', $param);
				if (isset($parama[1])) {
					$params[$parama[0]] = $parama[1];
				} else {
					$params[$param] = $param;
				}
			}

			return $params;
		}

		/**
		 * @param $userid
		 * @return mixed
		 */
		private function getEventsForUser($userid)
		{
			$maxEvents = as_opt('as_notifications_maxevshow'); // maximal events to show

			$currentTime = (int)as_opt('db_time');
			$maxageTime = $currentTime - (int)as_opt('as_notifications_maxage') * 86400;

			$event_query = as_db_query_sub(
				'(
					SELECT
						e.event,
						e.userid,
						BINARY e.params as params,
						UNIX_TIMESTAMP(e.datetime) AS datetime,
						"" `icon_class`,
						"" event_text
					FROM ^eventlog e
					WHERE
						FROM_UNIXTIME(#) <= datetime AND
						(e.userid = # AND e.event LIKE "in_%") OR
						(e.event IN ("u_message", "u_wall_post") AND e.params LIKE "userid=#\t%")
				) UNION (
					SELECT
						"notifications_plugin" `event`,
						`user_id` `userid`,
						"" `params`,
						UNIX_TIMESTAMP(`created_at`) `datetime`,
						`icon_class`,
						`event_text`
					FROM ^notifications
					WHERE FROM_UNIXTIME(#) <= `created_at` AND `user_id` = #
				)
				ORDER BY datetime DESC
				LIMIT #', // Limit
				$maxageTime, // events of last x days
				$userid,
				$userid,
				$maxageTime, // events of last x days
				$userid,
				$maxEvents
			);
			return $event_query;
		}

	}; // end class

/*
	Omit PHP closing tag to help avoid accidental output
*/
