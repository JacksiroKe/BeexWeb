<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Theme layer class for viewing likers and flaggers


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

class as_html_theme_layer extends as_html_theme_base
{
	private $as_likers_flaggers_queue = array();
	private $as_likers_flaggers_cache = array();


	// Collect up all required postids for the entire page to save DB queries - common case where whole page output

	public function main()
	{
		foreach ($this->content as $key => $part) {
			if (strpos($key, 'p_list') === 0) {
				if (isset($part['ps']))
					$this->queue_raw_posts_likers_flaggers($part['ps']);

			} elseif (strpos($key, 'p_view') === 0) {
				$this->queue_post_likers_flaggers($part['raw']);
				$this->queue_raw_posts_likers_flaggers($part['c_list']['cs']);

			} elseif (strpos($key, 'a_list') === 0) {
				if (!empty($part)) {
					$this->queue_raw_posts_likers_flaggers($part['as']);

					foreach ($part['as'] as $a_item) {
						if (isset($a_item['c_list']['cs']))
							$this->queue_raw_posts_likers_flaggers($a_item['c_list']['cs']);
					}
				}
			}
		}

		parent::main();
	}


	// Other functions which also collect up required postids for lists to save DB queries - helps with widget output and Ajax calls

	public function p_list_items($q_items)
	{
		$this->queue_raw_posts_likers_flaggers($q_items);

		parent::p_list_items($q_items);
	}

	public function a_list_items($a_items)
	{
		$this->queue_raw_posts_likers_flaggers($a_items);

		parent::a_list_items($a_items);
	}

	public function c_list_items($c_items)
	{
		$this->queue_raw_posts_likers_flaggers($c_items);

		parent::c_list_items($c_items);
	}


	// Actual output of the likers and flaggers

	public function like_count($post)
	{
		$postid = isset($post['like_opostid']) && $post['like_opostid'] ? $post['raw']['opostid'] : $post['raw']['postid'];
		$likersflaggers = $this->get_post_likers_flaggers($post['raw'], $postid);

		if (isset($likersflaggers)) {
			$uphandles = array();
			$downhandles = array();

			foreach ($likersflaggers as $likerflagger) {
				if ($likerflagger['like'] != 0) {
					$newflagger = as_html($likerflagger['handle']);
					if ($likerflagger['like'] > 0)
						$uphandles[] = $newflagger;
					else  // if ($likerflagger['like'] < 0)
						$downhandles[] = $newflagger;
				}
			}

			$tooltip = trim(
				(empty($uphandles) ? '' : '&uarr; ' . implode(', ', $uphandles)) . "\n\n" .
				(empty($downhandles) ? '' : '&darr; ' . implode(', ', $downhandles))
			);

			$post['like_count_tags'] = sprintf('%s title="%s"', isset($post['like_count_tags']) ? $post['like_count_tags'] : '', $tooltip);
		}

		parent::like_count($post);
	}

	public function post_meta_flags($post, $class)
	{
		if (isset($post['raw']['opostid']))
			$postid = $post['raw']['opostid'];
		elseif (isset($post['raw']['postid']))
			$postid = $post['raw']['postid'];

		$flaggers = array();

		if (isset($postid)) {
			$likersflaggers = $this->get_post_likers_flaggers($post, $postid);

			if (isset($likersflaggers)) {
				foreach ($likersflaggers as $likerflagger) {
					if ($likerflagger['flag'] > 0)
						$flaggers[] = as_html($likerflagger['handle']);
				}
			}
		}

		if (!empty($flaggers))
			$this->output('<span title="&#9873; ' . implode(', ', $flaggers) . '">');

		parent::post_meta_flags($post, $class);

		if (!empty($flaggers))
			$this->output('</span>');
	}


	// Utility functions for this layer

	private function queue_post_likers_flaggers($post)
	{
		if (!as_user_post_permit_error('permit_view_likers_flaggers', $post)) {
			$postkeys = array('postid', 'opostid');
			foreach ($postkeys as $key) {
				if (isset($post[$key]) && !isset($this->as_likers_flaggers_cache[$post[$key]]))
					$this->as_likers_flaggers_queue[$post[$key]] = true;
			}
		}
	}

	private function queue_raw_posts_likers_flaggers($posts)
	{
		if (is_array($posts)) {
			foreach ($posts as $post) {
				if (isset($post['raw']))
					$this->queue_post_likers_flaggers($post['raw']);
			}
		}
	}

	private function retrieve_queued_likers_flaggers()
	{
		if (count($this->as_likers_flaggers_queue)) {
			require_once AS_INCLUDE_DIR . 'db/likes.php';

			$postids = array_keys($this->as_likers_flaggers_queue);

			foreach ($postids as $postid) {
				$this->as_likers_flaggers_cache[$postid] = array();
			}

			$newlikersflaggers = as_db_userlikeflag_posts_get($postids);

			if (AS_FINAL_EXTERNAL_USERS) {
				$keyuserids = array();
				foreach ($newlikersflaggers as $likerflagger) {
					$keyuserids[$likerflagger['userid']] = true;
				}

				$useridhandles = as_get_public_from_userids(array_keys($keyuserids));
				foreach ($newlikersflaggers as $index => $likerflagger) {
					$newlikersflaggers[$index]['handle'] = isset($useridhandles[$likerflagger['userid']]) ? $useridhandles[$likerflagger['userid']] : null;
				}
			}

			foreach ($newlikersflaggers as $likerflagger) {
				$this->as_likers_flaggers_cache[$likerflagger['postid']][] = $likerflagger;
			}

			$this->as_likers_flaggers_queue = array();
		}
	}

	private function get_post_likers_flaggers($post, $postid)
	{
		require_once AS_INCLUDE_DIR . 'util/sort.php';

		if (!isset($this->as_likers_flaggers_cache[$postid])) {
			$this->queue_post_likers_flaggers($post);
			$this->retrieve_queued_likers_flaggers();
		}

		$likersflaggers = isset($this->as_likers_flaggers_cache[$postid]) ? $this->as_likers_flaggers_cache[$postid] : null;

		if (isset($likersflaggers))
			as_sort_by($likersflaggers, 'handle');

		return $likersflaggers;
	}
}
