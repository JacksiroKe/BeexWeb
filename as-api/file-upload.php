<?php	
	$upload = $_FILES['file'];
	$raw_file_name = $upload["name"];
	$temp_file_name = $upload["tmp_name"];		
	$upload_file_ext = explode(".", $raw_file_name);
	$upload_file_name = preg_replace("/-$/","",preg_replace('/[^a-z0-9]+/i', "_", strtolower($upload_file_ext[0])));
	$finalname = 'post_'.time().'.'.$upload_file_ext[1];
	$target_file = "../as-media/" .  $finalname;
	$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
	
	if (copy($temp_file_name, $target_file)) $data['filename'] = $finalname;
	else $data['filename'] = 'default.jpg';
	
	$output = json_encode(array('data' => $data));	
	echo $output;