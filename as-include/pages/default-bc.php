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

function as_user_dashboard()
{
	$as_content = as_content_prepare();

	$as_content['title'] = 'Business Customer <small>Dashboard</small>';

	$smallbox1 = array( 'type' => 'small-box', 'theme' => 'aqua', 
		'count' => '150', 'title' => 'New Orders', 'icon' => 'bag', 'link' => '#');
	
	$smallbox2 = array( 'type' => 'small-box', 'theme' => 'green', 
		'count' => '53<sup style="font-size: 20px">%', 'title' => 'Bounce Rate', 
		'icon' => 'stats-bars', 'link' => '#');
		
	$smallbox3 = array( 'type' => 'small-box', 'theme' => 'yellow', 
		'count' => '44', 'title' => 'User Registrations', 
		'icon' => 'person-add', 'link' => '#');
		
	$smallbox4 = array( 'type' => 'small-box', 'theme' => 'red', 
		'count' => '65', 'title' => 'Unique Visitors', 
		'icon' => 'pie-graph', 'link' => '#');
	
	$as_content['row_view'][] = array(
		'colms' => array(
			1 => array('class' => 'col-lg-3 col-xs-6', 'c_items' => array($smallbox1) ),
			2 => array('class' => 'col-lg-3 col-xs-6', 'c_items' => array($smallbox2) ),
			3 => array('class' => 'col-lg-3 col-xs-6', 'c_items' => array($smallbox3) ),
			5 => array('class' => 'col-lg-3 col-xs-6', 'c_items' => array($smallbox4) ),
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
}