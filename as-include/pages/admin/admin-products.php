<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Controller for admin page for editing categories


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
	header('Location: ../../../');
	exit;
}

require_once AS_INCLUDE_DIR . 'app/admin.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'db/admin.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'util/image.php';

$in = array();
$in['categoryid'] = as_clicked('dosaveproduct') ? as_get_category_field_value('category') : as_get('cat');

$editproductid = as_post_text('edit');
if (!isset($editproductid))
	$editproductid = as_get('edit');
if (!isset($editproductid))
	$editproductid = as_get('addsub');

$userid = as_get_logged_in_userid();
$selectsort = 'title';

list($categories, $products) = as_db_select_with_pending(
	as_db_category_nav_selectspec($editproductid, true, false, true),
	as_db_products_selectspec('title')
);

// Check admin privileges (do late to allow one DB query)

if (!as_admin_check_privileges($as_content)) return $as_content;


// Work out the appropriate state for the page

$editproduct = @$categories[$editproductid];

if (isset($editproduct)) {
	$parentid = as_get('addsub');
	if (isset($parentid))
		$editproduct = array('parentid' => $parentid);

} else {
	if (as_clicked('doaddproduct'))
		$editproduct = array();

	elseif (as_clicked('dosaveproduct')) {
		$parentid = as_post_text('parent');
		$editproduct = array('parentid' => strlen($parentid) ? $parentid : null);
	}
}

$setmissing = as_post_text('missing') || as_get('missing');

$setparent = !$setmissing && (as_post_text('setparent') || as_get('setparent')) && isset($editproduct['categoryid']);

$hassubcategory = false;
foreach ($categories as $category) {
	if (!strcmp($category['parentid'], $editproductid))
		$hassubcategory = true;
}


// Process saving options

$savedoptions = false;
$securityexpired = false;

if (as_clicked('dosaveoptions')) {
	if (!as_check_form_security_code('admin/products', as_post_text('code')))
		$securityexpired = true;

	else {
		as_set_option('allow_no_category', (int)as_post_text('option_allow_no_category'));
		as_set_option('allow_no_sub_category', (int)as_post_text('option_allow_no_sub_category'));
		$savedoptions = true;
	}
}


// Process saving an old or new category

if (as_clicked('docancel')) {
	if ($setmissing || $setparent)
		as_redirect(as_request(), array('edit' => $editproduct['categoryid']));
	elseif (isset($editproduct['categoryid']))
		as_redirect(as_request());
	else
		as_redirect(as_request(), array('edit' => @$editproduct['parentid']));

} elseif (as_clicked('dosetmissing')) {
	if (!as_check_form_security_code('admin/products', as_post_text('code')))
		$securityexpired = true;

	else {
		$inreassign = as_get_category_field_value('reassign');
		as_db_category_reassign($editproduct['categoryid'], $inreassign);
		as_redirect(as_request(), array('recalc' => 1, 'edit' => $editproduct['categoryid']));
	}

} elseif (as_clicked('dosaveproduct')) {
	if (!as_check_form_security_code('admin/products', as_post_text('code')))
		$securityexpired = true;

	elseif (as_post_text('dodelete')) {
		if (!$hassubcategory) {
			$inreassign = as_get_category_field_value('reassign');
			as_db_category_reassign($editproduct['categoryid'], $inreassign);
			as_db_category_delete($editproduct['categoryid']);
			as_redirect(as_request(), array('recalc' => 1, 'edit' => $editproduct['parentid']));
		}

	} else {
		require_once AS_INCLUDE_DIR . 'util/string.php';

		$inname = as_post_text('name');
		$incontent = as_post_text('content');
		$initemcode = as_post_text('itemcode');
		$involume = as_post_text('volume');
		$inmass = as_post_text('mass');
		$intexture = as_post_text('texture');
		$cookieid = isset($userid) ? as_cookie_get() : as_cookie_get_create();
		
		$errors = array();

		// Check the parent ID

		$incategories = as_db_select_with_pending(as_db_category_nav_selectspec($inparentid, true));

		// Verify the name is legitimate for that parent ID

		if (empty($inname))
			$errors['name'] = as_lang('main/field_required');
		elseif (as_strlen($inname) > AS_DB_MAX_CAT_PAGE_TITLE_LENGTH)
			$errors['name'] = as_lang_sub('main/max_length_x', AS_DB_MAX_CAT_PAGE_TITLE_LENGTH);
		else {
			foreach ($incategories as $category) {
				if (!strcmp($category['parentid'], $inparentid) &&
					strcmp($category['categoryid'], @$editproduct['categoryid']) &&
					as_strtolower($category['title']) == as_strtolower($inname)
				) {
					$errors['name'] = as_lang('admin/category_already_used');
				}
			}
		}

		// Verify the slug is legitimate for that parent ID

		for ($attempt = 0; $attempt < 100; $attempt++) {
			switch ($attempt) {
				case 0:
					$inslug = as_post_text('slug');
					if (!isset($inslug))
						$inslug = implode('-', as_string_to_words($inname));
					break;

				case 1:
					$inslug = as_lang_sub('admin/category_default_slug', $inslug);
					break;

				default:
					$inslug = as_lang_sub('admin/category_default_slug', $attempt - 1);
					break;
			}

			$matchcategoryid = as_db_category_slug_to_id($inparentid, $inslug); // query against DB since MySQL ignores accents, etc...

			if (!isset($inparentid))
				$matchpage = as_db_single_select(as_db_page_full_selectspec($inslug, false));
			else
				$matchpage = null;

			if (empty($inslug))
				$errors['slug'] = as_lang('main/field_required');
			elseif (as_strlen($inslug) > AS_DB_MAX_CAT_PAGE_TAGS_LENGTH)
				$errors['slug'] = as_lang_sub('main/max_length_x', AS_DB_MAX_CAT_PAGE_TAGS_LENGTH);
			elseif (preg_match('/[\\+\\/]/', $inslug))
				$errors['slug'] = as_lang_sub('admin/slug_bad_chars', '+ /');
			elseif (!isset($inparentid) && as_admin_is_slug_reserved($inslug)) // only top level is a problem
				$errors['slug'] = as_lang('admin/slug_reserved');
			elseif (isset($matchcategoryid) && strcmp($matchcategoryid, @$editproduct['categoryid']))
				$errors['slug'] = as_lang('admin/category_already_used');
			elseif (isset($matchpage))
				$errors['slug'] = as_lang('admin/page_already_used');
			else
				unset($errors['slug']);

			if (isset($editproduct['categoryid']) || !isset($errors['slug'])) // don't try other options if editing existing category
				break;
		}
		
		if (is_array(@$_FILES["file"])) {
			$iconfileerror = $_FILES["file"]['error'];
			if ($iconfileerror === 1) $errors['posticon'] = as_lang('main/file_upload_limit_exceeded');
			elseif ($iconfileerror === 0 && $_FILES["file"]['size'] > 0) {
				require_once AS_INCLUDE_DIR . 'app/limits.php';

				$toobig = as_image_file_too_big($_FILES["file"]['tmp_name'], 500);

				if ($toobig) $errors['posticon'] = as_lang_sub('main/image_too_big_x_pc', (int)($toobig * 100));
			}
		}
		
		// Perform appropriate database action

		if (empty($errors)) {
			if (isset($_FILES["file"]))
				$posticon = as_upload_file($_FILES["file"], 'category.jpg', 'icon');
			else $posticon = 'category.jpg';
			
			if (isset($editproduct['categoryid'])) { // changing existing category
				as_db_product_update($in['categoryid'], $posticon, $inname, $inslug, $initemcode, $involume, $inmass, $intexture, $incontent, $editproduct['postid']);
				
				$recalc = false;

				as_redirect(as_request(), array('edit' => $editproduct['postid'], 'saved' => true, 'recalc' => (int)$recalc));

			} else { // creating a new one
				$categoryid = as_db_product_create($in['categoryid'], $userid, $cookieid, as_remote_ip_address(), $posticon, $inname, $inslug, $initemcode, $involume, $inmass, $intexture, $incontent);
				//$editproduct = array();
				as_redirect(as_request(), array('edit' => 'null', 'added' => true));
			}
		}
	}
}


// Prepare content for theme

$as_content = as_content_prepare();

$as_content['title'] = as_lang_html('admin/admin_title') . ' - ' . as_lang_html('admin/products_title');
$as_content['error'] = $securityexpired ? as_lang_html('admin/form_security_expired') : as_admin_page_error();

if ($setmissing) {
	$as_content['form'] = array(
		'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',

		'style' => 'tall',
		'title' => 'Add a Category',

		'fields' => array(
			'reassign' => array(
				'label' => isset($editproduct)
					? as_lang_html_sub('admin/category_no_sub_to', as_html($editproduct['title']))
					: as_lang_html('admin/category_none_to'),
				'loose' => true,
			),
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'id="dosaveoptions"', // just used for as_recalc_click()
				'label' => as_lang_html('main/save_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => as_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'dosetmissing' => '1', // for IE
			'edit' => @$editproduct['categoryid'],
			'missing' => '1',
			'code' => as_get_form_security_code('admin/products'),
		),
	);

	as_set_up_category_field($as_content, $as_content['form']['fields']['reassign'], 'reassign',
		$categories, @$editproduct['categoryid']);


} elseif (isset($editproduct) || $editproductid == 'null') {
	$iconoptions[''] = as_lang_html('admin/icon_none');
	if ( isset($editproduct['icon']) && strlen($editproduct['icon'])){
		$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' . as_get_media_html($editproduct['icon'], 35, 35) .
			'</span> <input name="file" type="file">';
		$iconvalue = $iconoptions['uploaded'];
	} else {
		$iconoptions['uploaded'] = '<input name="file" type="file">';
		$iconvalue = $iconoptions[''];
	}
	
	$formtitle = (isset($editproduct['categoryid']) ? 'Edit Product: '.$editproduct['title'] : 'Add a Product' );
	
	$formcontent = array(
		'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
		'title' => $formtitle, 'type' => 'form', 'style' => 'tall',

		'ok' => as_get('saved') ? as_lang_html('admin/product_saved') : (as_get('added') ? as_lang_html('admin/product_added') : null),

		'fields' => array(
			'category' => array(
				'label' => as_lang_html('admin/category_select'),
			),
			
			'name' => array(
				'id' => 'name_display',
				'tags' => 'name="name" id="name"',
				'label' => as_lang_html(count($categories) ? 'admin/product_name' : 'admin/product_name_first') . ' (Optional)',
				'value' => as_html(isset($inname) ? $inname : @$editproduct['title']),
				'error' => as_html(@$errors['name']),
			),
			
			'itemcode' => array(
				'id' => 'itemcode_display',
				'tags' => 'name="itemcode"',
				'label' => as_lang_html('admin/product_itemcode'),
				'value' => as_html(isset($initemcode) ? $initemcode : @$editproduct['itemcode']),
				'error' => as_html(@$errors['itemcode']),
			),
			
			'content' => array(
				'id' => 'content_display',
				'tags' => 'name="content"',
				'label' => as_lang_html('admin/product_description'),
				'value' => as_html(isset($incontent) ? $incontent : @$editproduct['content']),
				'error' => as_html(@$errors['content']),
			),
			
			'volume' => array(
				'id' => 'volume_display',
				'tags' => 'name="volume"',
				'label' => as_lang_html('admin/product_volume'),
				'value' => as_html(isset($involume) ? $involume : @$editproduct['volume']),
				'error' => as_html(@$errors['volume']),
			),
			
			'mass' => array(
				'id' => 'mass_display',
				'tags' => 'name="mass"',
				'label' => as_lang_html('admin/product_mass'),
				'value' => as_html(isset($inmass) ? $inmass : @$editproduct['mass']),
				'error' => as_html(@$errors['mass']),
			),
			
			'texture' => array(
				'id' => 'texture_display',
				'tags' => 'name="texture"',
				'label' => as_lang_html('admin/product_texture'),
				'value' => as_html(isset($intexture) ? $intexture : @$editproduct['texture']),
				'error' => as_html(@$errors['texture']),
			),

			'items' => array(),

			'delete' => array(),

			'reassign' => array(),

			'slug' => array(
				'id' => 'slug_display',
				'tags' => 'name="slug"',
				'label' => as_lang_html('admin/category_slug'),
				'value' => as_html(isset($inslug) ? $inslug : @$editproduct['tags']),
				'error' => as_html(@$errors['slug']),
			),
			
			'posticon' => array(
				'type' => 'select-radio',
				'label' => as_lang_html('admin/product_icon'),
				'tags' => 'name="posticon"',
				'options' => $iconoptions,
				'value' => $iconvalue,
				'error' => as_html(@$errors['posticon']),
			),
			
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'id="dosaveoptions"', // just used for as_recalc_click
				'label' => as_lang_html(isset($editproduct['categoryid']) ? 'main/save_button' : 'admin/add_product_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => as_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'dosaveproduct' => '1', // for IE
			'edit' => @$editproduct['categoryid'],
			'parent' => @$editproduct['parentid'],
			'setparent' => (int)$setparent,
			'code' => as_get_form_security_code('admin/products'),
		),
	);
	
	as_set_up_category_field($as_content, $formcontent['fields']['category'], 'category', $categories, $in['categoryid'], true, as_opt('allow_no_sub_category'));

	if (isset($editproduct['categoryid'])) { // existing category
		if ($hassubcategory) {
			$formcontent['fields']['name']['note'] = as_lang_html('admin/category_no_delete_subs');
			unset($formcontent['fields']['delete']);
			unset($formcontent['fields']['reassign']);

		} else {
			$formcontent['fields']['delete'] = array(
				'tags' => 'name="dodelete" id="dodelete"',
				'label' =>
					'<span id="reassign_shown">' . as_lang_html('admin/delete_category_reassign') . '</span>' .
					'<span id="reassign_hidden" style="display:none;">' . as_lang_html('admin/delete_category') . '</span>',
				'value' => 0,
				'type' => 'checkbox',
			);

			$formcontent['fields']['reassign'] = array(
				'id' => 'reassign_display',
				'tags' => 'name="reassign"',
			);

			as_set_up_category_field($as_content, $formcontent['fields']['reassign'], 'reassign',
				$categories, $editproduct['parentid'], true, true, null, $editproduct['categoryid']);
		}

		$formcontent['fields']['items'] = array(
			'label' => as_lang_html('admin/total_qs'),
			'type' => 'static',
			'value' => '<a href="' . as_path_html('items/' . as_category_path_request($categories, $editproduct['categoryid'])) . '">' .
				($editproduct['pcount'] == 1
					? as_lang_html_sub('main/1_article', '1', '1')
					: as_lang_html_sub('main/x_articles', as_format_number($editproduct['pcount']))
				) . '</a>',
		);

		if ($hassubcategory && !as_opt('allow_no_sub_category')) {
			$nosubcount = as_db_count_categoryid_qs($editproduct['categoryid']);

			if ($nosubcount) {
				$formcontent['fields']['items']['error'] =
					strtr(as_lang_html('admin/category_no_sub_error'), array(
						'^q' => as_format_number($nosubcount),
						'^1' => '<a href="' . as_path_html(as_request(), array('edit' => $editproduct['categoryid'], 'missing' => 1)) . '">',
						'^2' => '</a>',
					));
			}
		}

		as_set_display_rules($as_content, array(
			'position_display' => '!dodelete',
			'slug_display' => '!dodelete',
			'content_display' => '!dodelete',
			'parent_display' => '!dodelete',
			'children_display' => '!dodelete',
			'reassign_display' => 'dodelete',
			'reassign_shown' => 'dodelete',
			'reassign_hidden' => '!dodelete',
		));

	} else { // new product
		unset($formcontent['fields']['delete']);
		unset($formcontent['fields']['reassign']);
		unset($formcontent['fields']['slug']);
		unset($formcontent['fields']['items']);

		$as_content['focusid'] = 'name';
	}
	$listcontent = array(
		'id' => 'latest_products',
		'type' => 'table',
		'title' => 'Recent Added Products (' . count($products) . ')', 
		'headers' => array('#', 'Product', 'Code', 'Volume', 'Mass', 'Texture'),
	);

	if (count($products)) {
		$p = 1;
		foreach ($products as $product) {
			$listcontent['rows'][] = array(
				'onclick' => ' title="Click on this product to edit or view"',
				'fields' => array(
					'id' => array( 'data' => as_get_media_html($product['icon'], 20, 20) ),
					'title' => array( 'data' => '<a href="' . as_path_html('admin/products', array('edit' => $product['postid'])) . '">' . 
					as_html($product['title'])  . ' ' . $product['category'] .'</a>' ),
					'itemcode' => array( 'data' => $product['itemcode']),
					'volume' => array( 'data' => $product['volume']),
					'mass' => array( 'data' => $product['mass']),
					'texture' => array( 'data' => $product['texture']),
				),
			);
			$p++;
		}

	}

	$as_content['row_view'][] = array(
		'colms' => array(
			0 => array('class' => 'col-lg-6 col-xs-12', 'c_items' => array($formcontent) ),
			1 => array('class' => 'col-lg-6 col-xs-12', 'c_items' => array($listcontent) ),
		),
	);

} else {
	$as_content['form'] = array(
		'tags' => 'method="post" action="' . as_path_html(as_request()) . '"',
		'title' => 'Recent Added Products',
		'ok' => $savedoptions ? as_lang_html('admin/options_saved') : null,

		'style' => 'tall',

		'table' => array( 'id' => 'allproducts', 'inline' => true,
			'headers' => array('#', 'ProductID', 'Category', 'Code', 'Length', 'Width', 'Height/Thickness', 
			'Fill', 'Fill-material', 'Weight', 'Color', 'Type/Pattern', '*') ),

		'tools' => array(
			'add' => array(
				'type' => 'submit', 
				'tags' => 'name="doaddproduct"',
				'label' => as_lang_html('admin/add_product_button'),
			),
		),
		
		'hidden' => array(
			'code' => as_get_form_security_code('admin/products'),
		),
	);

	if (count($products)) {
		$as_content['title'] .= ' ('.count($products).')';
		$navcategoryhtml = '';
		$p = 1;
		foreach ($products as $product) {
			$volume = explode('by', $product['volume']);
			$mass = explode(';', $product['mass']);
			$texture = explode(';', $product['texture']);

			$as_content['form']['table']['rows'][$p] = array(
				'onclick' => ' title="Click on this product to edit or view"',
				'fields' => array(
					'id' => array( 'data' => $p),
					'title' => array( 'data' => as_get_media_html($product['icon'], 20, 20) .'<a href="' . as_path_html('admin/products', array('edit' => $product['postid'])) . '">' . as_html($product['title']) .'</a>' ),
					'cat' => array( 'data' => $product['category']),
					'itemcode' => array( 'data' => $product['itemcode']),
					'length' => array( 'data' => isset($volume[0]) ? trim($volume[0]) : ''),
					'width' => array( 'data' => isset($volume[1]) ? trim($volume[1]) : ''),
					'height' => array( 'data' => isset($volume[2]) ? trim($volume[2]) : '' ),
					'fill' => array( 'data' => isset($mass[0]) ? trim($mass[0]) : ''),
					'fill-material' => array( 'data' => isset($mass[1]) ? trim($mass[1]) : ''),
					'weight' => array( 'data' => isset($mass[2]) ? trim($mass[2]) : ''),
					'color' => array( 'data' => isset($texture[0]) ? trim($texture[0]) : ''),
					'pattern' => array( 'data' => isset($texture[1]) ? trim($texture[1]) : ''),
					'*' => array( 'data' => '' ),
				),
			);
			$p++;

		}
		
		$as_content['script_onloads'][] = array(
			"$(function () { $('#allproducts').DataTable() })"
		  );
	} else unset($as_content['form']['buttons']['save']);
}

if (as_get('recalc')) {
	$as_content['form']['ok'] = '<span id="recalc_ok">' . as_lang_html('admin/recalc_categories') . '</span>';
	$as_content['form']['hidden']['code_recalc'] = as_get_form_security_code('admin/recalc');

	$as_content['script_rel'][] = 'as-content/as-admin.js?' . AS_VERSION;
	$as_content['script_var']['as_warning_recalc'] = as_lang('admin/stop_recalc_warning');

	$as_content['script_onloads'][] = array(
		"as_recalc_click('dorecalccategories', document.getElementById('dosaveoptions'), null, 'recalc_ok');"
	);
}

return $as_content;
