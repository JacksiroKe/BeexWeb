<?php
	require_once '../as-include/as-base.php';
	require_once '../as-include/db/users.php';
	require_once '../as-include/db/selects.php';
	require_once '../as-include/app/users.php';
	
	$userid = as_post_text('userid');
	$handle = as_post_text('handle');	
	$start = min(max(0, (int)as_get('start')), AS_MAX_LIMIT_START);
	
	$identifier = AS_FINAL_EXTERNAL_USERS ? $userid : $handle;

	list($useraccount, $userpoints, $items) = as_db_select_with_pending(
		AS_FINAL_EXTERNAL_USERS ? null : as_db_user_account_selectspec($handle, false),
		as_db_user_points_selectspec($identifier),
		as_db_user_recent_qs_selectspec($userid, $identifier, as_opt_if_loaded('page_size_qs'), $start)
	);

	$success = 0;
	$message = '';
	$total = count($items);
	$data = array();
	
	foreach( $items as $item ){
		array_push($data, array(
			'postid' 		=> $item['postid'],
			'categoryid' 	=> $item['categoryid'],
			'categoryname' 	=> $item['categoryname'],
			'title' 		=> $item['categoryname'] == $item['title'] ? $item['title'] : $item['title'] . ' ' .$item['categoryname'],
			'image' 		=> $item['icon'] ? $item['icon'] : 'category.jpg',
			'categoryicon' 	=> $item['caticon'] ? $item['caticon'] : 'category.jpg',
			'price' 		=> 'KSh '.$item['saleprice'],
			'details' 		=> '<b>'.$item['color'].'; <b>'.$item['texture'].'</b>; <b>'.$item['state'].'; '.$item['volume'].' cm</b>; <b>'.$item['weight'].' kgs</b>',			
			'manufacturer' 	=> '<b>'.$item['quantity'].'</b>  items from <b>'.$item['manufacturer'].'</b>',
			'categoryisscon' 	=> null)
		);	
	}
	
	$output = json_encode(array('success' => $success, 'message' => $message, 'total' => $total, 'data' => $data));
	
	echo $output;