<?php
	require_once '../as-include/as-base.php';
	require_once '../as-include/db/users.php';
	require_once '../as-include/db/selects.php';
	require_once '../as-include/app/users.php';

	$categoryslugs = as_request_parts(1);
	$countslugs = count($categoryslugs);

	$sort = ($countslugs && !AS_ALLOW_UNINDEXED_QUERIES) ? null : as_get('sort');
	$start = min(max(0, (int)as_get('start')), AS_MAX_LIMIT_START);
	$userid = as_get_logged_in_userid();

	switch ($sort) {
		case 'hot':
			$selectsort = 'hotness';
			break;

		case 'likes':
			$selectsort = 'netlikes';
			break;

		case 'reviews':
			$selectsort = 'rcount';
			break;

		case 'views':
			$selectsort = 'views';
			break;

		default:
			$selectsort = 'created';
			break;
	}

	list($items, $categories, $categoryid) = as_db_select_with_pending(
		as_db_is_selectspec($userid, $selectsort, $start, $categoryslugs, null, false, false, as_opt_if_loaded('page_size_qs')),
		as_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? as_db_slugs_to_category_id_selectspec($categoryslugs) : null
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
			'manufacturer' 	=> '<b>'.$item['quantity'].'</b>  items from <b>'.$item['manufacturer'].'</b>'
			)
		);	
	}
	
	$output = json_encode(array('success' => $success, 'message' => $message, 'total' => $total, 'data' => $data));
	
	echo $output;