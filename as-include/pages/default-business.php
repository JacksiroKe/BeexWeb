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

$as_content = as_content_prepare();

$as_content['title'] = 'User Dashboard';

$aboutme = array( 'type' => 'box', 'theme' => 'primary', 'title' => 'About Me');

$aboutme['body'] = array('type' => 'box-body');
$aboutme['body']['items'][] = array(
	'tag' => array('strong'), 
	'itag' => array('book', 5), 
	'data' => array(
		'text' => 'Education',
	),
);
$aboutme['body']['items'][] = array(
	'tag' => array('p', 'text-muted'), 
	'data' => array(
		'text' => 'B.S. in Computer Science from the University of Tennessee at Knoxville',
	),
);
$aboutme['body']['items'][] = '';
$aboutme['body']['items'][] = array(
	'tag' => array('strong'), 
	'itag' => array('map-marker', 5), 
	'data' => array(
		'text' => 'Location',
	),
);
$aboutme['body']['items'][] = array(
	'tag' => array('p', 'text-muted'), 
	'data' => array(
		'text' => 'Malibu, California',
	),
);
$aboutme['body']['items'][] = '';
$aboutme['body']['items'][] = array(
	'tag' => array('strong'), 
	'itag' => array('pencil', 5), 
	'data' => array(
		'text' => 'Skills',
	),
);
$aboutme['body']['items'][] = array(
	'tag' => array('p'), 
	'data' => array(
		'sub-data' => array(
			'UI Design' => array('label', 'danger'),
			'Coding' => array('label', 'success'),
			'Javascript' => array('label', 'info', 'Javascript'),
			'PHP' => array('label', 'warning'),
			'Node.js' => array('label', 'primary'),
		),
	),
);
$aboutme['body']['items'][] = '';
$aboutme['body']['items'][] = array(
	'tag' => array('strong'), 
	'itag' => array('file-text-o', 5), 
	'data' => array(
		'text' => 'Notes',
	),
);
$aboutme['body']['items'][] = array(
	'tag' => array('p'), 
	'data' => array(
		'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam fermentum enim neque',
	),
);

$tabview = array( 'type' => 'nav-tabs-custom', 'navs' => array('activity' => 'Activity', 'timeline' => 'Timeline') );

$tabview['body']['activity']['posts'][]= array(
	'class' => 'post clearfix',
	'blocks' => array(
		'user-block' => array(
			'elem' => 'div',
			'user' => 'Diamond Ltd',
			'img' => 'http://localhost/beex/as-media/user.jpg',
			'text' => 'Posted a new deal - 3 days ago',
		),
		'para' => array(
			'elem' => 'p',
			'text' => 'New Mahogany products are now available at very affordable prices contact us today for quick sales.',
		),
	),
);

$tabview['body']['timeline']['tlines'][]= array(
	'class' => 'time-label', 'data' => array( 'text' => '10 Feb. 2014'),
);

$tabview['body']['timeline']['tlines'][]= array(
	'data' => array(
		'itag' => array('envelope', 'blue'),
		'sub-data' => array(
			'time' => '12:05',
			'header' => '<a href="#">Support Team</a> sent you an email',
			'body' => 'Etsy doostang zoodles disqus groupon greplin oooj voxy zoodles,
					weebly ning heekya handango imeem plugg dopplr jibjab, movity
					jajah plickers sifteo edmodo ifttt zimbra. Babblely odeo kaboodle
					quora plaxo ideeli hulu weebly balihoo...',
		),
	),
);

$tabview['body']['timeline']['tlines'][]= array(
	'data' => array('itag' => array('clock-o', 'gray')),
);

$as_content['row_view'] = array(
	'colm_0' => array('width' => 9, 'c_items' => array($tabview) ),
	'colm_1' => array('width' => 3, 'c_items' => array($aboutme) ),
);

return $as_content;