<?php
/*
	Osam by Jackson Siro
	https://www.github.com/AppSmata/Osam/

	Description: Controller for stocks page


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: https://www.github.com/AppSmata/Osam/license.php
*/

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once AS_INCLUDE_DIR . 'db/users.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/posts.php';

$page = as_request_part(1);
$pagestate = as_get_state();
$as_content = as_content_prepare();
$userid = as_get_logged_in_userid();
$start = as_get_start();

$categoryslugs = as_request_parts(1);
$countslugs = count($categoryslugs);

$in = array();
$menuItems = array();

$menuItems['stocks$'] = array(
	'label' => as_lang_html('main/dashboard'),
	'url' => as_path_html('stocks'),
);

/*$menuItems['stocks/past'] = array(
	'label' => as_lang_html('main/past_stockitems'),
	'url' => as_path_html('stocks/past'),
);*/

if (as_clicked('doview')) {
	as_redirect( 'stocks/paper/'. as_request_part(2) );
}

if (is_numeric($page)) {
	$postid = $page;
	list($stockitem, $epapers) = as_db_select_with_pending(
		as_db_stockitems_selectspec($userid, $postid),
		as_db_list_examspapers_selectspec($postid)
	);
	$papercount = count($epapers);
	
	$menuItems['stocks/classes'] = array(
		'label' => as_lang_html('main/view_class_list'),
		'url' => as_path_html('stocks/classes/'.$postid),
	);
	
	$as_content['title'] = ($papercount == 1) ? as_lang_html_sub('main/paper_in_x', $stockitem['title'], '1') : 
		$papercount . ' ' . as_lang_html_sub('main/papers_in_x', $stockitem['title']);
	$as_content['script_src'][] = '../as-content/as-tables.js?'.AS_VERSION;
	$as_content['script'][] = "\t\t".'<script>
$(\'#exam_change\').change(funtion() {
	window.location.href = $(this).val();
});
</script>';
			
	$as_content['listing'] = array(
		'items' => array(),
		'checker' => '',
		'headers' => array(
			as_lang('options/label_group'),
			as_lang('options/label_unit'),
			as_lang('options/label_score'),
			as_lang('main/unit_grade'),
			as_lang('main/paper_attempts'),
			as_lang('main/created'),
		),
	);
	
	if ($papercount) {
		foreach ($epapers as $epaper => $paper) {
			$paperid = $paper['paperid'];
			if ($paper['attempts'] == 0) $paperscore = 0;
			else $paperscore = round($paper['score'] / $paper['attempts'], 2);
			
			$as_content['listing']['items'][] = array(
				'onclick' => ' title="Click on this item to edit or view" onclick="location=\''.as_path_html('stocks/paper/'.$paperid).'\'"',
				'fields' => array(
					'checkthis' => array( 'data' => '<label><input id="chk-item-'. $paper['paperid'] . '" class="chk-item" name="chk-item-checked[]" type="checkbox" value="'.$paperid. '"> '. $paperid . '</label>' ),
					'group' => array( 'data' => $paper['groupcode']),
					'unit' => array( 'data' => $paper['unitcode']),
					'score' => array( 'data' => $paperscore ),
					'grade' => array( 'data' => as_grading($paperscore) ),
					'attempts' => array( 'data' => $paper['attempts'] ),
					'created' => array( 'data' => as_lang_html_sub('main/x_ago', as_html(as_time_to_string(as_opt('db_time') - $paper['created'])))),
				),
			);		
		}
	} else $as_content['title'] = as_lang_html_sub('main/no_papers_in_x', $stockitem['title']);
} elseif ( $page == 'classes' ) {
	$postid = as_request_part(2);
	list($stockitem, $epapers) = as_db_select_with_pending(
		as_db_stockitems_selectspec($userid, $postid),
		as_db_list_examspapers_selectspec($postid)
	);
	
	$menuItems['stocks/papers'] = array(
		'label' => as_lang_html('main/view_paper_list'),
		'url' => as_path_html('stocks/'.$postid),
	);
	
	$as_content['script_src'][] = '../as-content/as-tables.js?'.AS_VERSION;
	$as_content['script'][] = '<script>
$(\'#exam_change\').change(funtion() {
	window.location.href = $(this).val();
});
</script>';
			
	$as_content['listing'] = array(
		'items' => array(),
		'checker' => '',
		'headers' => array(
			as_lang('options/label_group'),
			as_lang('options/label_units'),
			as_lang('main/created'),
		),
	);
	
	if (count($epapers)) {
		foreach ($epapers as $epaper => $paper) {
			$paperid = $paper['paperid'];
			$groupid = $paper['groupid'];
			if ($paper['attempts'] != 0) $paperscore = round($paper['score'] / $paper['attempts'], 2);
			else $paperscore = $paper['score'];
			
			$units = as_db_select_with_pending(as_db_exam_group_units($groupid));
			$unitcode = '';
			foreach ( $units as $unit ) $unitcode .= $unit['code'] .', ';
			
			$as_content['listing']['items'][$groupid] = array(
				'onclick' => ' title="Click on this item to edit or view" onclick="location=\''.as_path_html('stocks/class/'.$groupid).'\'"',
				'fields' => array(
					'checkthis' => array( 'data' => '<label><input id="chk-item-'. $paper['paperid'] . '" class="chk-item" name="chk-item-checked[]" type="checkbox" value="'.$paperid. '"> '. $paperid . '</label>' ),
					'group' => array( 'data' => $paper['grouptitle'] . ' - ' . $paper['groupcode']),
					'unit' => array( 'data' => $unitcode ),
					'created' => array( 'data' => as_lang_html_sub('main/x_ago', as_html(as_time_to_string(as_opt('db_time') - $paper['created'])))),
				),
			);		
		}
		
		$papercount = count($as_content['listing']['items']);	
		$as_content['title'] = ($papercount == 1) ? as_lang_html_sub('main/class_in_x', $stockitem['title'], '1') : 
			$papercount . ' ' . as_lang_html_sub('main/classes_in_x', $stockitem['title']);
			
	} else $as_content['title'] = as_lang_html_sub('main/no_papers_in_x', $stockitem['title']);
} elseif ( $page == 'paper' ) {
	$thispaper = as_request_part(2);
	
	if (is_numeric($thispaper)) {
		$paper = as_db_select_with_pending(as_db_examspapers_selectspec($userid, $thispaper));
		
		$menuItems['stocks/papers'] = array(
			'label' => as_lang_html('main/other_papers'),
			'url' => as_path_html('stocks/' . $paper['postid'] ),
		);

		$menuItems['stocks/enter'] = array(
			'label' => as_lang_html('main/enter_marks'),
			'url' => as_path_html('stocks/marks/'.$thispaper),
		);
		
		list($marks, $students) = as_db_select_with_pending(
			as_db_list_marks_selectspec($thispaper),
			as_db_list_users_selectspec('STUDENT', $start, $paper['groupid'])
		);
		
		$as_content['title'] = as_lang_html('main/paper_perfomance');
		$as_content['script_src'][] = '../../as-content/as-tables.js?'.AS_VERSION;
		$markscount = count($marks);
		if ($markscount) {
			$as_content['listing'] = array(
				'infor' => array('<table id="infort">
					<tr><td><b>'.as_lang('main/group_label').'</b></td><td>'.$paper['groupname'].' - '.$paper['groupcode'].'</td>
					<td><b>'.as_lang('main/unit_label').'</b></td><td>'.$paper['unitname'].' - '.$paper['unitcode'].'</td></tr>
					</table>', 
					date('Y-m-d H:i:s')
				),
				'items' => array(),
				'headers' => array(
					'<span style="float:right">NO.</span>',
					as_lang('main/student_name'), 'G',
					as_lang('users/admno_no'),
					as_lang('main/unit_score'),
					as_lang('main/unit_grade'),
					as_lang('main/unit_remark'),'',
				),
				
			);
			$item = 1;
			foreach ($marks as $mark => $mk) {
				$as_content['listing']['items'][]['fields'] = array(
					'check' => array( 'data' => ''),
					'rowid' => array( 'data' => '<span style="float:right">'.$item.'. </span>' ),
					'student' => array( 'data' => $mk['fullname']),
					'sex' => array( 'data' => (($mk['gender'] == '1') ? 'M' : 'F' ) ),
					'number' => array( 'data' => $mk['admno'] ),
					'score' => array( 'data' => $mk['score'] ),
					'grade' => array( 'data' => $mk['grade'] ),
					'remark' => array( 'data' => $mk['remark'] ),
					'checki' => array( 'data' => '' ),
				);
				$item++;		
			}
			if ($paper['attempts'] != 0) $paperscore = round($paper['score'] / $paper['attempts'], 2);
			else $paperscore = $paper['score'];
			$as_content['listing']['bottom'] = array(
				'','', '', '','', $paperscore, as_grading($paperscore), '', '',
			);
				
		} else {
			require_once AS_INCLUDE_DIR.'db/post-create.php';
			if (count($students)) {
				foreach ($students as $student) {
					as_db_marks_create($paper['postid'], $thispaper, $paper['groupid'], $paper['unitid'], $student['userid']);
				}
			}
			as_redirect( 'stocks/marks/'. $thispaper );			
		}
	}
} elseif ( $page == 'class' ) {
	$thisgroup = as_request_part(2);
	if (is_numeric($thisgroup)) {
		$group = as_db_select_with_pending(as_db_group_selectspec($thisgroup));
		$as_content['title'] = as_lang_html('main/group_perfomance').': '.$group['code'];
		$as_content['script_src'][] = '../../as-content/as-tables.js?'.AS_VERSION;
		$unitcode = '';
			
		list($units, $students) = as_db_select_with_pending(
			as_db_exam_group_units($thisgroup),
			as_db_list_users_selectspec('STUDENT', $start, $thisgroup)
		);
		
		if (count($students)) {
			$unitsid = array();
			$marks = array();
			$totals = array();
			$as_content['listing'] = array(
				'infor' => array('<table id="infort">
					<tr><td><b>'.as_lang('main/group_label').'</b></td><td>'.$group['title'].' - '.$group['code'].'</td><td></td></tr></table>', 
					date('Y-m-d H:i:s')
				),
				'items' => array(),
				'headers' => array(
						'<span style="float:right">NO.</span>',
						as_lang('main/student_name'), 'G',
						as_lang('users/admno_no'),
				),				
			);
			
			$unit_count = 1;
			foreach ($units as $unititem) {
				$unitsid[] = $unititem['unitid'];
				$as_content['listing']['headers'][] = $unititem['code'];
				$marks[$unit_count] = as_db_list_marks_selectonly($unititem['paperid']);
				$unit_count++;
			}
			$as_content['listing']['headers'][] = as_lang('main/total_score');
			$as_content['listing']['headers'][] = as_lang('main/mean_score');
			$as_content['listing']['headers'][] = as_lang('main/unit_grade');
			
			foreach ($students as $student) {
				$total_score = 0;
				for ($ak=1; $ak < $unit_count; $ak++) {
					$total_score = $total_score + $marks[$ak][$studentid];
				}
				
			}
			
			$item = 1;
			$overall_score = $overall_mean = 0;
			foreach ($students as $student) {
				$studentid = $student['userid'];
				$as_content['listing']['items'][$studentid]['fields'] = array(
					'check' => array( 'data' => ''),
					'rowid' => array( 'data' => '<span style="float:right">'.$item.'. </span>' ),
					'name' => array( 'data' => $student['firstname'].' '.$student['lastname']),
					'sex' => array( 'data' => (($student['sex'] == '1') ? 'M' : 'F' ) ),
					'adm' => array( 'data' => $student['code'] )
				);
				$total_score = 0;
				for ($ak=1; $ak < $unit_count; $ak++) {
					$as_content['listing']['items'][$studentid]['fields'][] = array( 'data' => '<span style="float:right">'.$marks[$ak][$studentid].'</span>' );
					$total_score = $total_score + $marks[$ak][$studentid];
				}
				
				$overall_score = $overall_score + $total_score;
				$mean_score = round($total_score / ($unit_count - 1), 2);
				$overall_mean = $overall_mean + $mean_score;
				$as_content['listing']['items'][$studentid]['fields']['total'] = array( 'data' => '<span style="float:right">'.$total_score.'</span>' );
				$as_content['listing']['items'][$studentid]['fields']['mean'] = array( 'data' => '<span style="float:right">'.$mean_score.'</span>'  );
				$as_content['listing']['items'][$studentid]['fields']['grade'] = array( 'data' => '<center>'.as_grading($mean_score ).'</center>' );
				$item++;
			}
			
			$as_content['listing']['bottom'] = array( null, null, null, null, '<span style="float:right">'.as_lang('main/total_score').'</span>');
			for ($ak=1; $ak < $unit_count; $ak++) {
				$as_content['listing']['bottom'][] = '<span style="float:right">'.$marks[$ak]['totals'].'</span>';
			}
			$as_content['listing']['bottom'][] = '<span style="float:right">'.$overall_score.'</span>';
			$as_content['listing']['bottom'][] = '<span style="float:right">'.$overall_mean.'</span>';
			$as_content['listing']['bottom'][] = '<center></center>';
			
			$student_count = count($students);
			$final_mean = (round($overall_mean / $student_count, 2));
			$as_content['listing']['bottomi'] = array( null, null, null, null, '<span style="float:right">'.as_lang('main/mean_score').'</span>');
			for ($ak=1; $ak < $unit_count; $ak++) {
				$as_content['listing']['bottomi'][] = '<span style="float:right">'.(round($marks[$ak]['totals'] / $student_count, 2)).'</span>';
			}
			$as_content['listing']['bottomi'][] = '<span style="float:right">'.(round($overall_score / $student_count, 2)).'</span>';
			$as_content['listing']['bottomi'][] = '<span style="float:right">'.(round($overall_mean / $student_count, 2)).'</span>';
			$as_content['listing']['bottomi'][] = '<center>'.as_grading($final_mean).'</center>';
			
		}
	}
} elseif ( $page == 'marks' ) {
	$thispaper = as_request_part(2);
	
	$menuItems['stocks/enter'] = array(
		'label' => as_lang_html('main/view_marks'),
		'url' => as_path_html('stocks/paper/'.$thispaper),
	);
	

	if (is_numeric($thispaper)) {
		$paper = as_db_select_with_pending(as_db_examspapers_selectspec($userid, $thispaper));
		
		list($marks, $students) = as_db_select_with_pending(
			as_db_list_marks_selectspec($thispaper),
			as_db_list_users_selectspec('STUDENT', $start, $paper['groupid'])
		);
		$as_content['title'] = as_lang('main/enter_marks_for').' '.$paper['groupcode'] . ' ' .$paper['unitcode'] . '; ' . $paper['title'];
		$as_content['entermarks'] = array();
		
		if (count($students)) {
			$i = 1;	
			$studentscount = count($students);		
			foreach ($students as $student) {	
				$as_content['entermarks']['e_form'][] = array(
					'tags' => 'method="post" action="' . as_self_html() . '"',
					'id' => 'marks_'.$i,
					'collapse' => $i == 1 ? false : true,
					'style' => 'tall',

					'fields' => array(
						'title' => array(
							'label' => '',
							'type' => 'custom',
							'html' => '<h3 style="font-size:20px;">Student '.$i . ' of ' . $studentscount.': <u>'.$student['code'] . '</u> ' . $student['firstname'] . ' ' . $student['lastname'] . ' - ' . (($student['sex'] == '1') ? 'M' : 'F' ) . '</h3>',
						),
						
						'score' => array(
							'label' => $paper['unitname'] . ' (' . $paper['unitcode'] . ') ' . as_lang_html('main/unit_score'),
							'tags' => 'name="score_'.$i.'" id="score_'.$i.'" dir="auto" min="0" max="100"',
							'value' => $marks[$i-1]['score'],
							'type' => 'updown',
							'suffix' => as_lang_html('main/marks_suffix'),
							'error' => as_html(@$errors['score_'.$i.'']),
						),
						
						'remark' => array(
							'label' => as_lang_html('main/remark_label'),
							'tags' => 'name="remark_'.$i.'" id="remark_'.$i.'" dir="auto"',
							'value' => $marks[$i-1]['remark'],
							'error' => as_html(@$errors['remark_'.$i.'']),
						),
					),

					'buttons' => array(
						'save' => array(
							'tags' => 'name="dosave" onclick="as_show_waiting_after(this, false); return as_submit_marks('.$student['userid'].', '.$paper['postid'].', '.
							$thispaper.', '.$paper['groupid'].', '.$paper['unitid'].', '.$i.', '.(($i != $studentscount) ? $i+1 : 0).', this);"',
							'label' => as_lang_html('main/marks_button'),
						),
						'previous' => array(
							'tags' => 'name="doprevious" onclick="return as_previous_marks('.$i.', '.(($i != 1) ? $i-1 : 0).', this);"',
							'label' => ' << ' .as_lang_html('main/previous_button'),
						),
						'skip' => array(
							'tags' => 'name="doskip" onclick="return as_skip_marks('.$i.', '.(($i != $studentscount) ? $i+1 : 0).', this);"',
							'label' => as_lang_html('main/skip_button').' >> ',
						),
					),
				);
				$i++;		
			}
			$as_content['custom'] = '<div id="finish_up" style="display:none;"><h3>Done Entering Marks;</h3>';
			$as_content['custom'] .= '<form method="post" action="' . as_self_html() . '" >';
			$as_content['custom'] .= '<input name="doview" onclick="as_show_waiting_after(this, false);" value="View Marklist >>" type="submit" class="as-form-tall-button">';
			$as_content['custom'] .= '</form></div>';
		} else {
			
		}
	}
} elseif ( $page == 'past' ) {
	$as_content['script_src'][] = '../as-content/as-tables.js?'.AS_VERSION;
	$stocks = as_db_select_with_pending(as_db_list_stockitems_selectspec(date('Y-m-d'), true));

	$as_content['title'] = as_lang_html('main/past_stockitems');

	$as_content['listing'] = array(
		'items' => array(),
		'checker' => '',
		'headers' => array(
			as_lang('options/label_title'),
			as_lang('options/label_papers'),
			as_lang('options/label_from'),
			as_lang('options/label_to'),
			as_lang('main/created'),
		),
	);
		
	if (count($stocks)) {
		foreach ($stocks as $stockitem => $item) {
			$postid = $item['postid'];
			$as_content['listing']['items'][] = array(
				'onclick' => ' title="Click on this item to edit or view" onclick="location=\''.as_path_html('stocks/'.$postid).'\'"',
				'fields' => array(
					'checkthis' => array( 'data' => '<label><input id="chk-item-'. $item['postid'] . '" class="chk-item" name="chk-item-checked[]" type="checkbox" value="'.$postid. '">
					'. $postid . '</label>' ),
					'label' => array( 'data' => $item['title']),
					'papers' => array( 'data' => $item['papers'] ),
					'testfrom' => array( 'data' => $item['testfrom'] ),
					'testto' => array( 'data' => $item['testto'] ),
					'created' => array( 'data' => as_lang_html_sub('main/x_ago', 
						as_html(as_time_to_string(as_opt('db_time') - $item['created'])))),
				),
			);		
		}
	} else $as_content['title'] = as_lang_html('main/no_past_stockitems');
} else {
	$as_content['script_src'][] = 'as-content/as-tables.js?'.AS_VERSION;
	
	list($items, $categories, $categoryid) = as_db_select_with_pending(
		//as_db_question_selectspec($userid, $selectsort, $start, $categoryslugs, null, false, false, as_opt_if_loaded('page_size_qs')),
		as_db_question_selectspec($userid, 'created', $start, $categoryslugs, null, false, false, as_opt_if_loaded('page_size_qs')),
		as_db_category_nav_selectspec($categoryslugs, false, false, true),
		$countslugs ? as_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);

	if (count($items)) {
		$as_content['title'] = as_lang_html('main/dashboard_stock');

		$as_content['listing'] = array(
			'items' => array(),
			'checker' => '',
			'headers' => array(
				as_lang('options/label_title'),
				as_lang('options/label_icon'),
				as_lang('options/label_from'),
				as_lang('options/label_to'),
				as_lang('main/created'),
			),
		);
			
		foreach ($items as $stockitem => $item) {
			$postid = $item['postid'];
			$as_content['listing']['items'][] = array(
				'onclick' => ' title="Click on this item to edit or view" onclick="location=\''.as_path_html('stocks/'.$postid).'\'"',
				'fields' => array(
					'checkthis' => array( 'data' => '<label><input id="chk-item-'. $item['postid'] . '" class="chk-item" name="chk-item-checked[]" type="checkbox" value="'.$postid. '">
					'. $postid . '</label>' ),
					'label' => array( 'data' => $item['title']),
					//'papers' => array( 'data' => $item['papers'] ),
					//'testfrom' => array( 'data' => $item['testfrom'] ),
					//'testto' => array( 'data' => $item['testto'] ),
					'created' => array( 'data' => as_lang_html_sub('main/x_ago', 
						as_html(as_time_to_string(as_opt('db_time') - $item['created'])))),
				),
			);	
			
		}
	} else $as_content['title'] = as_lang_html('main/no_articles_found');
}

$as_content['navigation']['sub'] = $menuItems;
as_set_template('osam');

return $as_content;
