<?php
	require_once '../as-include/as-base.php';
	require_once '../as-include/db/users.php';
	require_once '../as-include/db/selects.php';
	require_once '../as-include/app/users.php';

	$postid = as_get('postid') ? as_get('postid') : '';
	$userid = as_get_logged_in_userid();
	
	$success = 0;
	$message = '';
	$data = array();
	
	if (strlen($postid)) {
		$postinfo = as_db_select_with_pending(as_db_full_post_selectspec($userid, $postid, true));
		
		$data['postid'] 			= $postinfo['postid'];
		$data['image'] 				= $postinfo['icon'] ? $postinfo['icon'] : 'category.jpg';
		$data['categoryicon'] 		= $postinfo['caticon'] ? $postinfo['caticon'] : 'category.jpg';
		$data['categoryname'] 		= $postinfo['categoryname'];
		$data['title'] 				= $postinfo['categoryname'] == $postinfo['title'] ? $postinfo['title'] : $postinfo['title'] . ' ' .$postinfo['categoryname'];
		$data['volume'] 			= $postinfo['volume'].' cm';
		$data['weight'] 			= $postinfo['weight'].' kgs';
		$data['saleprice'] 			= $postinfo['saleprice'];
		$data['color'] 				= $postinfo['color'];
		$data['texture'] 			= $postinfo['texture'];
		$data['quantity'] 			= $postinfo['quantity'];
		$data['manufacturer'] 		= $postinfo['manufacturer'];
		$data['content'] 			= $postinfo['content'];
		$data['created'] 			= $postinfo['created'];
		$data['handle'] 			= $postinfo['handle'];
		
		$success = 1;
	} else {
		$success = 0;
		$message = 'the item was either deleted or hidden.';
	}
	
	$output = json_encode(array('success' => $success, 'message' => $message, 'data' => $data));	
	
	echo $output;