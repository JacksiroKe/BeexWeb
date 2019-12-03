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
require_once AS_INCLUDE_DIR . 'db/post-create.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'util/image.php';

$in = array();
$in['locationid'] = as_clicked('dosavelocation') ? as_get_category_field_value('category') : as_get('cat');

$editproductid = as_post_text('edit');
if (!isset($editproductid))
	$editproductid = as_get('edit');
if (!isset($editproductid))
	$editproductid = as_get('addsub');

$userid = as_get_logged_in_userid();
$selectsort = 'title';

list($categories, $locations) = as_db_select_with_pending(
	as_db_category_nav_selectspec($editproductid, true, false, true),
	as_db_latest_locations('COUNTY')
);

// Check admin privileges (do late to allow one DB query)

if (!as_admin_check_privileges($as_content)) return $as_content;


// Work out the appropriate state for the page

$editlocation = @$categories[$editproductid];

if (isset($editlocation)) {
	$parentid = as_get('addsub');
	if (isset($parentid))
		$editlocation = array('parentid' => $parentid);

} else {
	if (as_clicked('doaddlocation'))
		$editlocation = array();

	elseif (as_clicked('dosavelocation')) {
		$parentid = as_post_text('parent');
		$editlocation = array('parentid' => strlen($parentid) ? $parentid : null);
	}
}

$setmissing = as_post_text('missing') || as_get('missing');

$setparent = !$setmissing && (as_post_text('setparent') || as_get('setparent')) && isset($editlocation['locationid']);

$hassubcategory = false;
foreach ($categories as $category) {
	if (!strcmp($category['parentid'], $editproductid))
		$hassubcategory = true;
}


// Process saving options

$savedoptions = false;
$securityexpired = false;

if (as_clicked('dosaveoptions')) {
	if (!as_check_form_security_code('admin/locations', as_post_text('code')))
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
		as_redirect(as_request(), array('edit' => $editlocation['locationid']));
	elseif (isset($editlocation['locationid']))
		as_redirect(as_request());
	else
		as_redirect(as_request(), array('edit' => @$editlocation['parentid']));

} elseif (as_clicked('dosetmissing')) {
	if (!as_check_form_security_code('admin/locations', as_post_text('code')))
		$securityexpired = true;

	else {
		$inreassign = as_get_category_field_value('reassign');
		as_db_category_reassign($editlocation['locationid'], $inreassign);
		as_redirect(as_request(), array('recalc' => 1, 'edit' => $editlocation['locationid']));
	}

} elseif (as_clicked('dosavelocation')) {
	if (!as_check_form_security_code('admin/locations', as_post_text('code')))
		$securityexpired = true;

	elseif (as_post_text('dodelete')) {
		if (!$hassubcategory) {
			$inreassign = as_get_category_field_value('reassign');
			as_db_category_reassign($editlocation['locationid'], $inreassign);
			as_db_category_delete($editlocation['locationid']);
			as_redirect(as_request(), array('recalc' => 1, 'edit' => $editlocation['parentid']));
		}

	} else {
		require_once AS_INCLUDE_DIR . 'util/string.php';
		
		$intitle = as_post_text('title');
		$incode = as_post_text('ccode');
		$indetails = as_post_text('details');
		$incontent = as_post_text('content');
		$insubcounties = explode(',', as_post_text('subcounties'));
		
		$errors = array();

		// Check the parent ID

		//$incategories = as_db_select_with_pending(as_db_category_nav_selectspec($inparentid, true));

		// Verify the name is legitimate for that parent ID

		if (empty($intitle))
			$errors['name'] = as_lang('main/field_required');
		elseif (as_strlen($intitle) > AS_DB_MAX_CAT_PAGE_TITLE_LENGTH)
			$errors['name'] = as_lang_sub('main/max_length_x', AS_DB_MAX_CAT_PAGE_TITLE_LENGTH);
		else {
			/*foreach ($incategories as $category) {
				if (!strcmp($category['parentid'], $inparentid) &&
					strcmp($category['locationid'], @$editlocation['locationid']) &&
					as_strtolower($category['title']) == as_strtolower($intitle)
				) {
					$errors['name'] = as_lang('admin/category_already_used');
				}
			}*/
		}

		if (empty($errors)) {
			//title, code, details, content, subcounties
			if (isset($editlocation['locationid'])) { // changing existing category
				//as_db_product_update($in['locationid'], $posticon, $intitle, $inslug, $incode, $involume, $inmass, $intexture, $incontent, $editlocation['postid']);
				
				$recalc = false;

				as_redirect(as_request(), array('edit' => $editlocation['postid'], 'saved' => true, 'recalc' => (int)$recalc));

			} else { // creating a new one
				$locationid = as_db_location_create('COUNTY', $intitle, $incode, $indetails, $incontent);
				if (count($insubcounties))
				{
					foreach ($insubcounties as $subcounty)
					{
						if (!empty($subcounty)) as_db_location_create('SUB-COUNTY', $subcounty, '', '', '', $locationid);
					}
				}
				//$editlocation = array();
				as_redirect(as_request(), array('edit' => 'null', 'added' => true));
			}
		}
	}
} elseif (as_clicked('dosavetowns')) {
	require_once AS_INCLUDE_DIR . 'util/string.php';
		
	$insubcounty = as_post_text('subcounty');
	$intowns = explode("\n", as_post_text('towns'));
	
	if (count($intowns))
	{
		foreach ($intowns as $town)
		{
			$townstr = explode(",", $town);
			as_db_location_create('TOWN', $townstr[0], '', $townstr[1], $townstr[2], $insubcounty);
		}
	}
	//$editlocation = array();
	as_redirect(as_request(), array('page' => 'towns', 'added' => true));
}



// Prepare content for theme

$as_content = as_content_prepare();

$as_content['title'] = as_lang_html('admin/admin_title') . ' - ' . as_lang_html('admin/locations_title');
$as_content['error'] = $securityexpired ? as_lang_html('admin/form_security_expired') : as_admin_page_error();

if ($setmissing) {
	$as_content['form'] = array(
		'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',

		'style' => 'tall',
		'title' => 'Add a County',


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
			'edit' => @$editlocation['locationid'],
			'missing' => '1',
			'code' => as_get_form_security_code('admin/locations'),
		),
	);

} elseif (as_get('page') != null) {
	
	$locations = as_db_select_with_pending(as_db_latest_locations('COUNTY'));
	
	$countieshtml = '<input type="hidden" id="townsfeedback" value="table"/><div class="row">';

	$countieshtml .= '<div class="col-lg-6 col-xs-12">
		<label>County:</label>
		<select name="county" id="county" onchange="as_select_county()" class="form-control">
		<option>Select County</option>';
	foreach ($locations as $location)
	{
		$countieshtml .= '<option value="'.$location['locationid'].'">'.$location['title'].'</option>';
	}
	$countieshtml .= '</select>
	</div>';

	$countieshtml .= '<div class="col-lg-6 col-xs-12" id="bs_subcounty"></div>';

	$countieshtml .= '</div>';

	$formcontent = array(
		'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
		'title' => 'Manage Towns of a Sub-County', 'type' => 'form', 'style' => 'tall',

		'ok' => as_get('saved') ? as_lang_html('admin/towns_saved') : (as_get('added') ? as_lang_html('admin/town_added') : null),

		'fields' => array(			
			'location' => array(
				'type' => 'custom',
				'label' => 'Location of the Town',
				'html' => $countieshtml,
			),
			
			'towns' => array(
				'id' => 'content_display',
				'tags' => 'name="towns"',
				'label' => 'Towns i.e One per Line',
				'error' => as_html(@$errors['towns']),
				'type' => 'textarea',
				'rows' => 10,
			),
			
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'id="dosavetowns"', // just used for as_recalc_click
				'label' => as_lang_html(isset($editlocation['locationid']) ? 'main/save_button' : 'admin/add_towns_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => as_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'dosavetowns' => '1', // for IE			
			'townsfeedback' => 'table',
			'code' => as_get_form_security_code('admin/locations'),
		),
	);
	
	$townscontent = array(
		'id' => 'latest_towns', 'theme' => 'primary',
		'type' => 'custom',
		'title' => 'Recent Towns for current Sub-County', 
		'body' => '<div id="bs_town"></div>',
	);

	$as_content['row_view'][] = array(
		'colms' => array(
			0 => array('class' => 'col-lg-6 col-xs-12', 'c_items' => array($formcontent) ),
			1 => array('class' => 'col-lg-6 col-xs-12', 'c_items' => array($townscontent) ),
		),
	);

} elseif (isset($editlocation)) {
	$iconoptions[''] = as_lang_html('admin/icon_none');
	if ( isset($editlocation['icon']) && strlen($editlocation['icon'])){
		$iconoptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' . as_get_media_html($editlocation['icon'], 35, 35) .
			'</span> <input name="file" type="file">';
		$iconvalue = $iconoptions['uploaded'];
	} else {
		$iconoptions['uploaded'] = '<input name="file" type="file">';
		$iconvalue = $iconoptions[''];
	}
	
	$formtitle = (isset($editlocation['locationid']) ? 'Edit Location: '.$editlocation['title'] : 'Add a Location' );
	
	$formcontent = array(
		'tags' => 'enctype="multipart/form-data" method="post" action="' . as_path_html(as_request()) . '"',
		'title' => $formtitle, 'type' => 'form', 'style' => 'tall',

		'ok' => as_get('saved') ? as_lang_html('admin/product_saved') : (as_get('added') ? as_lang_html('admin/product_added') : null),

		'fields' => array(
			'title' => array(
				'id' => 'name_display',
				'tags' => 'name="title" id="title"',
				'label' => as_lang_html('admin/location_name'),
				'value' => as_html(isset($intitle) ? $intitle : @$editlocation['title']),
				'error' => as_html(@$errors['title']),
			),
			
			'ccode' => array(
				'id' => 'itemcode_display',
				'tags' => 'name="ccode"',
				'label' => as_lang_html('admin/location_code') . ' (Optional)',
				'value' => as_html(isset($incode) ? $incode : @$editlocation['ccode']),
				'error' => as_html(@$errors['ccode']),
			),
			
			'details' => array(
				'id' => 'details_display',
				'tags' => 'name="details"',
				'label' => as_lang_html('admin/location_details') . ' (Optional)',
				'value' => as_html(isset($indetails) ? $indetails : @$editlocation['details']),
				'error' => as_html(@$errors['details']),
			),
			
			'content' => array(
				'id' => 'content_display',
				'tags' => 'name="content"',
				'label' => as_lang_html('admin/location_description') . ' (Optional)',
				'value' => as_html(isset($incontent) ? $incontent : @$editlocation['content']),
				'error' => as_html(@$errors['content']),
			),
			
			'subcounties' => array(
				'id' => 'content_display',
				'tags' => 'name="subcounties"',
				'label' => as_lang_html('admin/location_subcounties') . ' Separated by commas (,) (Optional)',
				'error' => as_html(@$errors['subcounties']),
				'type' => 'textarea',
				'rows' => 3,
			),
			
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'id="dosaveoptions"', // just used for as_recalc_click
				'label' => as_lang_html(isset($editlocation['locationid']) ? 'main/save_button' : 'admin/add_location_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => as_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'dosavelocation' => '1', // for IE
			'parent' => @$editlocation['parentid'],
			'setparent' => (int)$setparent,
			'code' => as_get_form_security_code('admin/locations'),
		),
	);
	
	$listcontent = array(
		'id' => 'latest_products',
		'type' => 'table',
		'title' => 'Recent Added Counties (' . count($locations) . ')', 
		'headers' => array('#', 'Title', 'Code', 'Sub-Counties', 'Created'),
	);

	if (count($locations)) {
		$p = 1;
		foreach ($locations as $location) {
			$listcontent['rows'][] = array(
				'onclick' => ' title="Click on this county to edit or view"',
				'fields' => array(
					'id' => array( 'data' => $p),
					'title' => array( 'data' => $location['title'] ),
					'code' => array( 'data' => $location['code']),
					'sub-counties' => array( 'data' => 0),
					'created' => array( 'data' => as_format_date($location['created'], true) ),
					'*' => array( 'data' => '' ),
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
		'title' => 'Recent Added locations',
		'ok' => $savedoptions ? as_lang_html('admin/options_saved') : null,

		'style' => 'tall',

		'table' => array( 'id' => 'allproducts', 'inline' => true,
			'headers' => array('', '#', 'Title', 'details', 'Created', 'Updated', '*') ),

		'tools' => array(
			'addlocation' => array(
				'type' => 'submit', 
				'tags' => 'name="doaddlocation"',
				'label' => as_lang_html('admin/add_location_button'),
			),
			'x' => array(
				'type' => 'link', 'label' => ' ',
				'url' => '#', 
				'class' => 'btn btn-tool',
			),
			'addtown' => array(
				'type' => 'link',
				'url' => as_path_html( 'admin/locations', array('page' => 'towns') ),
				'class' => 'btn btn-primary',
				'label' => as_lang_html('admin/add_towns_button'),
			),
		),
		
		'hidden' => array(
			'code' => as_get_form_security_code('admin/locations'),
		),
	);

	if (count($locations)) {
		$as_content['title'] .= ' ('.count($locations).')';
		$navcategoryhtml = '';
		$k = 1;
		foreach ($locations as $location) {

			$tabledata[$k] = array(
				'fields' => array(
					'*' => array( 'data' => ''),
					'id' => array( 'data' => $k),
					'title' => array( 'data' => (isset($location['code']) ? $location['code'] . ' - ' : '') . $location['title'] ),
					'details' => array( 'data' => $location['details']),
					'created' => array( 'data' => as_format_date($location['created'], true) ),
					'updated' => array( 'data' => as_format_date($location['updated'], true) ),
					'*x' => array( 'data' => '' ),
				),
			);
			
			if (!isset($location['parentid']))
			{
				$sublocations = as_db_select_with_pending( as_db_latest_locations('SUB-COUNTY', $location['locationid']) );
				$j = 1;
				foreach ($sublocations as $subloc) 
				{
					$tabledata[$k]['fields']['*']['data'] = ' (' . (count($sublocations)) . ' sub-counties)';
					$tabledata[$k]['sub'][$j] = array(
						'fields' => array(
							'*' => array( 'data' => ''),
							'id' => array( 'data' => strtoupper(as_num_to_let($j)) . '. '),
							'title' => array( 'data' => $subloc['title'] ),
							'details' => array( 'data' => $subloc['details']),
							'created' => array( 'data' => as_format_date($subloc['created'], true) ),
							'updated' => array( 'data' => as_format_date($subloc['updated'], true) ),
							'*x' => array( 'data' => '' ),
						),
					);
					$checkboxtodisplay['child_' . $k . '_' . $j] = 'parent_' . $k ;
					
					$minlocations = as_db_select_with_pending( as_db_latest_locations('TOWN', $subloc['locationid']) );
					$totaltowns = count($minlocations);
					$g = 1;
					foreach ($minlocations as $town) 
					{
						$tabledata[$k]['sub'][$j]['fields']['title']['data'] = $subloc['title'] .' (' . ($totaltowns) . ' towns)';
						$tabledata[$k]['sub'][$j . '_' . $g] = array(
							'fields' => array(
								'*' => array( 'data' => ''),
								'id' => array( 'data' => ''),
								'title' => array( 'data' => as_num_to_rom($g) . ".\t" . $town['title'] ),
								'details' => array( 'data' => $town['details']),
								'created' => array( 'data' => as_format_date($town['created'], true) ),
								'updated' => array( 'data' => as_format_date($town['updated'], true) ),
								'*x' => array( 'data' => '' ),
							),
						);
						$checkboxtodisplay[ 'child_' . $k . '_' . $j . '_' . $g ] = 'parent_' . $k ;
						$g++;
					}
					$tabledata[$k]['sub'][$j . '_' . $totaltowns]['fields']['*x']['data'] = '<br><br>';

					$j++;
				}
			}
			$k++;
		}

		$as_content['form']['table']['rows'] = $tabledata;

		$as_content['script_onloads'][] = array(
			"$(function () { $('#allproducts').DataTable() })"
		  );		
		if (isset($checkboxtodisplay)) as_set_display_rules($as_content, $checkboxtodisplay);
	} else unset($as_content['form']['buttons']['save']);
}

return $as_content;
