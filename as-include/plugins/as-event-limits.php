<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Event module for updating per-user limits


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

class as_event_limits
{
	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		// Don't increment limits or report user actions for events that were delayed. For example, a 'q_post'
		// event sent when a post is approved by the admin, for which a 'q_queue' event was already sent.

		if (isset($params['delayed'])) {
			return;
		}

		require_once AS_INCLUDE_DIR . 'app/limits.php';

		switch ($event) {
			case 'q_queue':
			case 'q_post':
			case 'q_claim':
				as_limits_increment($userid, AS_LIMIT_POSTS);
				break;

			case 'a_queue':
			case 'a_post':
			case 'a_claim':
				as_limits_increment($userid, AS_LIMIT_REVIEWS);
				break;

			case 'c_queue':
			case 'c_post':
			case 'c_claim':
			case 'a_to_c':
				as_limits_increment($userid, AS_LIMIT_COMMENTS);
				break;

			case 'q_like_up':
			case 'q_like_down':
			case 'q_like_nil':
			case 'a_like_up':
			case 'a_like_down':
			case 'a_like_nil':
			case 'c_like_up':
			case 'c_like_down':
			case 'c_like_nil':
				as_limits_increment($userid, AS_LIMIT_VOTES);
				break;

			case 'q_flag':
			case 'a_flag':
			case 'c_flag':
				as_limits_increment($userid, AS_LIMIT_FLAGS);
				break;

			case 'u_message':
				as_limits_increment($userid, AS_LIMIT_MESSAGES);
				break;

			case 'u_wall_post':
				as_limits_increment($userid, AS_LIMIT_WALL_POSTS);
				break;
		}

		$writeactions = array(
			'_approve', '_claim', '_clearflags', '_delete', '_edit', '_favorite', '_flag', '_hide',
			'_post', '_queue', '_reject', '_reshow', '_unfavorite', '_unflag', '_like_down', '_like_nil', '_like_up',
			'a_select', 'a_to_c', 'a_unselect',
			'q_close', 'q_move', 'q_reopen',
			'u_block', 'u_edit', 'u_level', 'u_message', 'u_password', 'u_save', 'u_unblock',
		);

		if (is_numeric(array_search(strstr($event, '_'), $writeactions)) ||
			is_numeric(array_search($event, $writeactions))
		) {
			if (isset($userid)) {
				require_once AS_INCLUDE_DIR . 'app/users.php';
				as_user_report_action($userid, $event);

			} elseif (isset($cookieid)) {
				require_once AS_INCLUDE_DIR . 'app/cookies.php';
				as_cookie_report_action($cookieid, $event);
			}
		}
	}
}
