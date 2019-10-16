<?php
	require_once '../as-include/as-base.php';
	require_once '../as-include/db/users.php';
	require_once '../as-include/db/selects.php';
	require_once '../as-include/app/users.php';
	
	$categories = as_db_select_with_pending(as_db_category_selectspec());
	
	$data = array();
	$parents = array();
	
	foreach ($categories as $getparent) {
		if ($getparent['childcount']) $parents[$getparent['categoryid']] = $getparent['title'];
	}
			
	foreach( $categories as $category ){
		array_push($data, array(
			'categoryid' 	=> $category['categoryid'],
			'parentid' 		=> $category['parentid'],
			'title' 		=> $category['title'] . ($category['parentid'] ? ' ' . $parents[$category['parentid']] : '' ),
			'tags' 			=> $category['tags'],
			'pcount' 		=> $category['pcount'],
			'position' 		=> $category['position'],
			'childcount' 	=> $category['childcount'])
		);	
	}
	
	$output = json_encode(array('total' => count($categories), 'data' => $data));
	
	echo $output;