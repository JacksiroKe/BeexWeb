<?php
	require_once '../as-include/as-base.php';
	require_once '../as-include/db/users.php';
	require_once '../as-include/db/selects.php';
	require_once '../as-include/app/users.php';

	$categoryslugs = as_request_parts(1);
	$countslugs = count($categoryslugs);
	$userid = as_get_logged_in_userid();

	list($articles, $categories, $categoryid) = as_db_select_with_pending(
		as_db_recent_a_qs_selectspec($userid, 0, $categoryslugs),
		as_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? as_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);
	
	$total = count($articles);
	$data = array();
	foreach( $articles as $article ){
		array_push($data, array(
			'postid' 			=> $article['postid'],
			'categoryid' 		=> $article['categoryid'],
			'type' 				=> $article['type'],
			'basetype' 			=> $article['basetype'],
			'hidden' 			=> $article['hidden'],
			'queued' 			=> $article['queued'],
			'rcount' 			=> $article['rcount'],
			'selchildid' 		=> $article['selchildid'],
			'closedbyid' 		=> $article['closedbyid'],
			'positivelikes' 	=> $article['positivelikes'],
			'negativelikes' 	=> $article['negativelikes'],
			'netlikes' 			=> $article['netlikes'],
			'views' 			=> $article['views'],
			'hotness' 			=> $article['hotness'],
			'flagcount' 		=> $article['flagcount'],
			'title' 			=> $article['title'],
			'tags' 				=> $article['tags'],
			'created' 			=> $article['created'],
			'name' 				=> $article['name'],
			'categoryname' 		=> $article['categoryname'],
			'categorybackpath' 	=> $article['categorybackpath'],
			'categoryids' 		=> $article['categoryids'],
			'userlike' 			=> $article['userlike'],
			'userflag' 			=> $article['userflag'],
			'userfavoriteq' 	=> $article['userfavoriteq'],
			'userid' 			=> $article['userid'],
			'cookieid' 			=> $article['cookieid'],
			'createip' 			=> $article['createip'],
			'points' 			=> $article['points'],
			'flags' 			=> $article['flags'],
			'level' 			=> $article['level'],
			'email' 			=> $article['email'],
			'handle' 			=> $article['handle'],
			'avatarblobid' 		=> $article['avatarblobid'],
			'avatarwidth' 		=> $article['avatarwidth'],
			'avatarheight' 		=> $article['avatarheight'],
			'itemorder' 		=> $article['_order_'])
		);	
	}
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;