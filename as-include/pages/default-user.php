<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for user's dashboard

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

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once AS_INCLUDE_DIR . 'db/users.php';
$as_content = as_content_prepare();

$as_content['title'] = 'Welcome to BeExpress';

$handle = as_get_logged_in_handle();
$defaulticon = as_opt('site_url') . '/as-media/appicon.png';
	
$smallbox1 = array( 'type' => 'small-box', 'theme' => 'yellow', 
	'count' => '44', 'title' => 'User Registrations', 
	'icon' => 'person-add', 'link' => '#');

$latestnews = array( 'type' => 'list', 'theme' => 'primary', 'title' => 'Latest News', 
	'body' => array(
		'type' => 'product',
		'items' => array(
			0 => array(
				'img' => $defaulticon, 'label' => 'Panel Doors', 'numbers' => 'Kshs. 2500',
				'description' => 'The best you need to be where ytou wanna be',
			),
			1 => array(
				'img' => $defaulticon, 'label' => 'MDF Panel Doors', 'numbers' => 'Kshs. 2000',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			2 => array(
				'img' => $defaulticon, 'label' => 'Flush Panel Doors', 'numbers' => 'Kshs. 2100',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			3 => array(
				'img' => $defaulticon, 'label' => 'Flush Doors', 'numbers' => 'Kshs. 1600',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			4 => array(
				'img' => $defaulticon, 'label' => 'Mahogany', 'numbers' => 'Kshs. 1800',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			5 => array(
				'img' => $defaulticon, 'label' => 'Panel Doors', 'numbers' => 'Kshs. 2500',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			6 => array(
				'img' => $defaulticon, 'label' => 'Block Boards', 'numbers' => 'Kshs. 3000',
				'description' => 'The best you need to be where ytou wanna be',
			),				
		),
	),
);

$latestoffers = array( 'type' => 'list', 'theme' => 'primary', 'title' => 'Latest Offers', 
	'body' => array(
		'type' => 'product',
		'items' => array(
			0 => array(
				'img' => $defaulticon, 'label' => 'Panel Doors', 'numbers' => 'Kshs. 2500',
				'description' => 'The best you need to be where ytou wanna be',
			),
			1 => array(
				'img' => $defaulticon, 'label' => 'MDF Panel Doors', 'numbers' => 'Kshs. 2000',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			2 => array(
				'img' => $defaulticon, 'label' => 'Flush Panel Doors', 'numbers' => 'Kshs. 2100',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			3 => array(
				'img' => $defaulticon, 'label' => 'Flush Doors', 'numbers' => 'Kshs. 1600',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			4 => array(
				'img' => $defaulticon, 'label' => 'Mahogany', 'numbers' => 'Kshs. 1800',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			5 => array(
				'img' => $defaulticon, 'label' => 'Panel Doors', 'numbers' => 'Kshs. 2500',
				'description' => 'The best you need to be where ytou wanna be',
			),	
			6 => array(
				'img' => $defaulticon, 'label' => 'Block Boards', 'numbers' => 'Kshs. 3000',
				'description' => 'The best you need to be where ytou wanna be',
			),				
		),
	),
);

$as_content['row_view'][] = array(
	'colms' => array(
		//1 => array('class' => 'col-lg-4 col-xs-6', 'c_items' => array($smallbox1) ),
		//2 => array('class' => 'col-lg-4 col-xs-6', 'c_items' => array($latestnews) ),
		//3 => array('class' => 'col-lg-4 col-xs-6', 'c_items' => array($latestoffers) ),
	),
);

$tabview = array( 'type' => 'nav-tabs-custom', 'right' => true, 
	'navs' => array('revenue-chart' => 'Area', 'sales-chart' => 'Donut', 'header' => 'Sales') );


$tabview['body']['charts'] = array(
	'revenue-chart' => 'position: relative; height: 300px;',
	'sales-chart' => 'position: relative; height: 300px;',
);

$as_content['row_view'][] = array(
	'section' => 'col-lg-7 connectedSortable',
	'colms' => array(
		//1 => array('class' => 'col-md-9', 'c_items' => array($tabview) ),
	),
);


return $as_content;