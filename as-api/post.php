<?php

	require_once '../as-include/as-base.php';
	require_once '../as-include/app/format.php';
	require_once '../as-include/app/limits.php';
	require_once '../as-include/app/cookies.php';
	require_once '../as-include/app/post-create.php';
	require_once '../as-include/db/selects.php';
	
	require_once '../as-include/util/sort.php';
	require_once '../as-include/util/image.php';
	require_once '../as-include/util/string.php';

	require_once '../as-include/db/users.php';
	require_once '../as-include/app/users.php';
	
	function as_trim($string)
	{
		return preg_replace('/(^[\"\']|[\"\']$)/', '', $string);		
	}
	
	$inuserid = as_post_text('userid');
	$inhandle = as_trim(as_post_text('handle'));
	$inemail = as_trim(as_post_text('email'));
	$inlength = as_post_text('length');
	$inwidth = as_post_text('width');
	$inheight = as_post_text('height');
	$inweight = as_post_text('weight');
	$inbuyprice = as_post_text('buyprice');
	$insaleprice = as_post_text('saleprice');
	$instate = as_trim(as_post_text('state'));
	$incolor = as_trim(as_post_text('color'));
	$intexture = as_trim(as_post_text('texture'));
	$inquantity = as_post_text('quantity');
	$inmanufacturer = as_trim(as_post_text('manufacturer'));
	$incontent = as_trim(as_post_text('content'));
	$incategoryid = as_post_text('categoryid');
		
	$data = array();
	$permiterror = as_user_maximum_permit_error('permit_post_q', AS_LIMIT_POSTS);
	
	if ($permiterror) {
		$data['success'] = 0;
		switch ($permiterror) {
			case 'signin':
				$data['message'] = as_lang_html('item/write_must_signin');
				break;

			case 'confirm':
				$data['message'] = as_lang_html('item/write_must_confirm');
				break;

			case 'limit':
				$data['message'] = as_lang_html('item/write_limit');
				break;

			case 'approve':
				$data['message'] = as_lang_html('item/write_must_be_approved');
				break;

			default:
				$data['message'] =  as_lang_html('users/no_permission');
				break;
		}
	}
	
	if (empty($inlength)) {
		$data['success'] = 3;
		$data['message'] = 'The length of the item appears to be invalid';
	}
	
	else if (empty($inwidth)) {
		$data['success'] = 3;
		$data['message'] = 'The width of the item appears to be invalid';
	}
	
	else if (empty($inheight)) {
		$data['success'] = 3;
		$data['message'] = 'The height of the item appears to be invalid';
	}
	
	else if (empty($inweight)) {
		$data['success'] = 3;
		$data['message'] = 'The weight of the item appears to be invalid';
	}
	
	else if (empty($inbuyprice)) {
		$data['success'] = 3;
		$data['message'] = 'The buying price of the item appears to be invalid';
	}
	
	else if (empty($insaleprice)) {
		$data['success'] = 3;
		$data['message'] = 'The selling price of the item appears to be invalid';
	}
	
	else if (empty($incolor)) {
		$data['success'] = 3;
		$data['message'] = 'The color of the item appears to be invalid';
	}

	else if (empty($intexture)) {
		$data['success'] = 3;
		$data['message'] = 'The texture of the item appears to be invalid';
	}
	
	else if (empty($incontent)) {
		$data['success'] = 3;
		$data['message'] = 'The content of the item appears to be invalid';
	}
	
	else {
		$errors = array();

		$posticon = '';
		if (empty($errors)) {
			if (isset($_FILES["imagefile"])) 
			{
				$upload = $_FILES['imagefile'];
				$raw_file_name = $upload["name"];
				$temp_file_name = $upload["tmp_name"];		
				$upload_file_ext = explode(".", $raw_file_name);
				$upload_file_name = preg_replace("/-$/","",preg_replace('/[^a-z0-9]+/i', "_", strtolower($upload_file_ext[0])));
				$finalname = 'post_'.time().'.'.$upload_file_ext[1];
				$target_file = "../as-media/" .  $finalname;
				
				if (copy($temp_file_name, $target_file)) $infilename = $finalname;
				else $infilename = 'default.png';
			}
			else $infilename = 'default.png';
	
			$cookieid = isset($userid) ? as_cookie_get() : as_cookie_get_create();
			$text = as_remove_utf8mb4(as_viewer_text($incontent, 'html'));
			$articleid = as_item_create(null, $inuserid, $inhandle, $cookieid, $infilename, $inlength . 'x' . $inwidth . 'x' . $inheight, $inweight, $inbuyprice, $insaleprice, $instate, $incolor, $intexture, $inquantity, $inmanufacturer, $incontent, 'html', $text, $inemail, $incategoryid, null, null);
			$data['success'] = 1;
			$data['message'] = 'Posted in successfully';
		}
	} 
	
	$output = json_encode(array('data' => $data));	
	echo $output;