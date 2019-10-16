<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for post a item page


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


require_once AS_INCLUDE_DIR.'app/format.php';
require_once AS_INCLUDE_DIR.'app/limits.php';
require_once AS_INCLUDE_DIR.'db/selects.php';
require_once AS_INCLUDE_DIR.'util/sort.php';
require_once AS_INCLUDE_DIR . 'util/image.php';


// Check whether this is a follow-on item and get some info we need from the database

$in = array();

$followpostid = as_get('follow');
$in['categoryid'] = as_clicked('dopost') ? as_get_category_field_value('category') : as_get('cat');
$userid = as_get_logged_in_userid();

list($categories, $followreview, $completetags) = as_db_select_with_pending(
	as_db_category_nav_selectspec($in['categoryid'], true),
	isset($followpostid) ? as_db_full_post_selectspec($userid, $followpostid) : null,
	as_db_popular_tags_selectspec(0, AS_DB_RETRIEVE_COMPLETE_TAGS)
);

if (!isset($categories[$in['categoryid']])) {
	$in['categoryid'] = null;
}

if (@$followreview['basetype'] != 'R') {
	$followreview = null;
}

// Check for permission error

$permiterror = as_user_maximum_permit_error('permit_post_q', AS_LIMIT_POSTS);

if ($permiterror) {
	$as_content = as_content_prepare();

	// The 'approve', 'signin', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the menu option being shown, in as_content_prepare(...)

	switch ($permiterror) {
		case 'signin':
			$as_content['error'] = as_insert_signin_links(as_lang_html('item/write_must_signin'), as_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
			break;

		case 'confirm':
			$as_content['error'] = as_insert_signin_links(as_lang_html('item/write_must_confirm'), as_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
			break;

		case 'limit':
			$as_content['error'] = as_lang_html('item/write_limit');
			break;

		case 'approve':
			$as_content['error'] = strtr(as_lang_html('item/write_must_be_approved'), array(
				'^1' => '<a href="' . as_path_html('account') . '">',
				'^2' => '</a>',
			));
			break;

		default:
			$as_content['error'] = as_lang_html('users/no_permission');
			break;
	}

	return $as_content;
}


// Process input

$captchareason = as_user_captcha_reason();

if (as_using_tags()) {
	$in['tags'] = as_get_tags_field_value('tags');
}

if (as_clicked('dopost')) {
	require_once AS_INCLUDE_DIR.'app/post-create.php';
	require_once AS_INCLUDE_DIR.'util/string.php';

	$categoryids = array_keys(as_category_path($categories, @$in['categoryid']));
	$userlevel = as_user_level_for_categories($categoryids);

	$in['length'] = as_post_text('length');
	$in['width'] = as_post_text('width');
	$in['height'] = as_post_text('height');
	$in['weight'] = as_post_text('weight');
	$in['state'] = as_post_text('state');
	$in['color'] = as_post_text('color');
	$in['texture'] = as_post_text('texture');
	$in['quantity'] = as_post_text('quantity');
	$in['bprice'] = as_post_text('bprice');
	$in['sprice'] = as_post_text('sprice');
	$in['manufacturer'] = as_post_text('manufacturer');
	
	$in['name'] = as_opt('allow_anonymous_naming') ? as_post_text('name') : null;
	$in['email'] = as_post_text('email');
	$in['queued'] = as_user_moderation_reason($userlevel) !== false;

	as_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

	$errors = array();

	if (!as_check_form_security_code('post', as_post_text('code'))) {
		$errors['page'] = as_lang_html('misc/form_security_again');
	}
	else {
		$filtermodules = as_load_modules_with('filter', 'filter_article');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_article($in, $errors, null);
			as_update_post_text($in, $oldin);
		}

		if (as_using_categories() && count($categories) && (!as_opt('allow_no_category')) && !isset($in['categoryid'])) {
			// check this here because we need to know count($categories)
			$errors['categoryid'] = as_lang_html('item/category_required');
		}
		elseif (as_user_permit_error('permit_post_q', null, $userlevel)) {
			$errors['categoryid'] = as_lang_html('item/category_write_not_allowed');
		}

		if ($captchareason) {
			require_once AS_INCLUDE_DIR.'app/captcha.php';
			as_captcha_validate_post($errors);
		}

		if (is_array(@$_FILES["icon"])) {
			$iconfileerror = $_FILES["icon"]['error'];
			if ($iconfileerror === 1) $errors['posticon'] = as_lang('main/file_upload_limit_exceeded');
			elseif ($iconfileerror === 0 && $_FILES["icon"]['size'] > 0) {
				require_once AS_INCLUDE_DIR . 'app/limits.php';

				$toobig = as_image_file_too_big($_FILES["icon"]['tmp_name'], 500);

				if ($toobig) $errors['posticon'] = as_lang_sub('main/image_too_big_x_pc', (int)($toobig * 100));
			}
		}
		
		if (empty($errors)) {
			$cookieid = isset($userid) ? as_cookie_get() : as_cookie_get_create();
		
			$posticon = as_upload_file($_FILES["icon"], 'category.jpg', 'icon');
			$articleid = as_item_create($followreview, $userid, as_get_logged_in_handle(), $cookieid, $posticon, $in['length'].'x'.$in['width'].'x'.$in['height'], $in['weight'], $in['bprice'], $in['sprice'], $in['state'], $in['color'], $in['texture'], $in['quantity'], $in['manufacturer'], $in['content'], $in['format'], $in['text'], $in['email'], $in['categoryid'], $in['queued'], $in['name']);
			
			as_redirect('');
			
			//as_redirect(as_q_request($articleid, $in['title'])); // our work is done here
		}
	}
}


// Prepare content for theme

$as_content = as_content_prepare(false, array_keys(as_category_path($categories, @$in['categoryid'])));

$as_content['title'] = as_lang_html(isset($followreview) ? 'item/write_follow_title' : 'item/write_title');
$as_content['error'] = @$errors['page'];

$editorname = isset($in['editor']) ? $in['editor'] : as_opt('editor_for_qs');
$editor = as_load_editor(@$in['content'], @$in['format'], $editorname);

$field = as_editor_load_field($editor, $as_content, @$in['content'], @$in['format'], 'content', 12, false);
$field['label'] = as_lang_html('item/i_content_label');
$field['error'] = as_html(@$errors['content']);

$custom = as_opt('show_custom_write') ? trim(as_opt('custom_write')) : '';

$as_content['form'] = array(
	'tags' => 'enctype="multipart/form-data" name="write" method="post" action="'.as_self_html().'"',

	'style' => 'wide',

	'fields' => array(
		'custom' => array(
			'type' => 'custom',
			'note' => $custom,
		),

		'category' => array(
			'label' => as_lang_html('item/i_category_label'),
			'error' => as_html(@$errors['categoryid']),
		),
		
		'icon' => array(
			'label' => as_lang_html('item/i_icon_label'),
			'tags' => 'name="icon" id="icon" autocomplete="on"',
			'value' => as_html(@$in['icon']),
			'type' => 'file',
			'error' => as_html(@$errors['icon']),
		),

		'specs' => array(
			'label' => 'Volume & Weight:',
			'type' => 'custom',
			'html' => '<table style="width: 100%;"><tr>
					<td>Length (cm): <input name="length" type="number" required /></td>
					<td>Width (cm): <input name="width" type="number" required /></td>
					<td>Height (cm): <input name="height" type="number" required /></td>
					<td>Weight (kg): <input name="weight" type="number" required /></td>
					</tr></table>',
		),
		
		'specs1' => array(
			'label' => 'Other Specs:',
			'type' => 'custom',
			'html' => '<table style="width: 100%;"><tr>
					<td>State: <select name="state" required><option value="New">New</option><option value="Good">Good</option><option value="Used">Used</option></select></td>
					<td>Color: <select name="color" required><option value="White">White</option><option value="Black">Black</option><option value="Red">Red</option><option value="Red">Red</option><option value="Brown">Brown</option><option value="Orange">Orange</option><option value="Yellow">Yellow</option><option value="Maroon">Maroon</option><option value="Blue">Blue</option><option value="Green">Green</option><option value="Purple">Purple</option><option value="Undefined">Undefined</option>
					</select>
					</td>
					<td>Texture: <select name="state" required><option value="Smooth">Smooth</option><option value="Rough">Rough</option><option value="Shinny">Shinny</option></select></td>
					<td>Quantity: <input name="quantity" type="number" required /></td>
					</tr></table>',
		),
			
		'price' => array(
			'label' => 'Item Prices:',
			'type' => 'custom',
			'html' => '<table style="width: 100%;"><tr>
					<td>Buying Price (KSh): <input name="bprice" type="number" required /></td>
					<td>Selling Price (KSh): <input name="sprice" type="number" required /></td>
					</tr></table>',
		),
			
		'manufacturer' => array(
			'label' => as_lang_html('item/i_manufacturer_label'),
			'tags' => 'name="manufacturer" id="manufacturer" autocomplete="on"',
			'value' => as_html(@$in['manufacturer']),
			'error' => as_html(@$errors['manufacturer']),
		),
		
		'content' => $field,
	),

	'buttons' => array(
		'write' => array(
			'tags' => 'onclick="as_show_waiting_after(this, false); '.
				(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
			'label' => as_lang_html('item/write_button'),
		),
	),

	'hidden' => array(
		'editor' => as_html($editorname),
		'code' => as_get_form_security_code('post'),
		'dopost' => '1',
	),
);

as_set_up_category_field($as_content, $as_content['form']['fields']['category'], 'category', $categories, $in['categoryid'], true, as_opt('allow_no_sub_category'));
if (!as_opt('allow_no_category')) $field['options'][''] = '';

if (!strlen($custom)) {
	unset($as_content['form']['fields']['custom']);
}

/*if (as_opt('do_write_check_qs') || as_opt('do_example_tags')) {
	if (strlen(@$in['title'])) {
		$as_content['script_onloads'][] = 'as_title_change('.as_js($in['title']).');';
	}
}*/

if (isset($followreview)) {
	$viewer = as_load_viewer($followreview['content'], $followreview['format']);

	//as_array_insert($as_content['form']['fields'], 'title', array('follows' => $field));
}

if (!isset($userid) && as_opt('allow_anonymous_naming')) {
	as_set_up_name_field($as_content, $as_content['form']['fields'], @$in['name']);
}

if ($captchareason) {
	require_once AS_INCLUDE_DIR.'app/captcha.php';
	as_set_up_captcha_field($as_content, $as_content['form']['fields'], @$errors, as_captcha_reason_note($captchareason));
}

//$as_content['focusid'] = 'title';

return $as_content;
