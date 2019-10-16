<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	File: as-plugin/wysiwyg-editor/as-plugin.php
	Description: Initiates WYSIWYG editor plugin


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

/*
	Plugin Name: WYSIWYG Editor
	Plugin URI:
	Plugin Description: Wrapper for CKEditor WYSIWYG rich text editor
	Plugin Version: 1.1.1
	Plugin Date: 2011-12-06
	Plugin Author: AppSmata
	Plugin Author URI: http://www.appsmata.org/
	Plugin License: GPLv2
	Plugin Minimum AppSmata Version: 1.3
	Plugin Update Check URI:
*/


if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


as_register_plugin_module('editor', 'as-wysiwyg-editor.php', 'as_wysiwyg_editor', 'WYSIWYG Editor');
as_register_plugin_module('page', 'as-wysiwyg-upload.php', 'as_wysiwyg_upload', 'WYSIWYG Upload');

as_register_plugin_module('page', 'as-wysiwyg-ajax.php', 'as_wysiwyg_ajax', 'WYSIWYG Editor AJAX handler');
