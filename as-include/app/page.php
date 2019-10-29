<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Routing and utility functions for page requests


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

require_once AS_INCLUDE_DIR . 'app/cookies.php';
require_once AS_INCLUDE_DIR . 'app/format.php';
require_once AS_INCLUDE_DIR . 'app/users.php';
require_once AS_INCLUDE_DIR . 'app/options.php';
require_once AS_INCLUDE_DIR . 'db/selects.php';


define('AS_USER_TYPE', as_get_logged_in_type(false));
define('AS_USER_TYPE_FULL', as_get_logged_in_type(true));

/**
 * Queue any pending requests which are required independent of which page will be shown
 */
function as_page_queue_pending()
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	as_preload_options();
	$signinuserid = as_get_logged_in_userid();

	if (isset($signinuserid)) {
		if (!AS_FINAL_EXTERNAL_USERS)
			as_db_queue_pending_select('loggedinuser', as_db_user_account_selectspec($signinuserid, true));

		as_db_queue_pending_select('notices', as_db_user_notices_selectspec($signinuserid));
		as_db_queue_pending_select('favoritenonqs', as_db_user_favorite_non_qs_selectspec($signinuserid));
		as_db_queue_pending_select('userlimits', as_db_user_limits_selectspec($signinuserid));
		as_db_queue_pending_select('userlevels', as_db_user_levels_selectspec($signinuserid, true));
	}

	as_db_queue_pending_select('iplimits', as_db_ip_limits_selectspec(as_remote_ip_address()));
	as_db_queue_pending_select('navpages', as_db_pages_selectspec(array('B', 'M', 'O', 'F')));
	as_db_queue_pending_select('widgets', as_db_widgets_selectspec());
}


/**
 * Check the page state parameter and then remove it from the $_GET array
 */
function as_load_state()
{
	global $as_state;

	$as_state = as_get('state');
	unset($_GET['state']); // to prevent being passed through on forms
}


/**
 * If no user is logged in, call through to the signin modules to see if they want to log someone in
 */
function as_check_signin_modules()
{
	if (!AS_FINAL_EXTERNAL_USERS && !as_is_logged_in()) {
		$signinmodules = as_load_modules_with('signin', 'check_signin');

		foreach ($signinmodules as $signinmodule) {
			$signinmodule->check_signin();
			if (as_is_logged_in()) // stop and reload page if it worked
				as_redirect(as_request(), $_GET);
		}
	}
}


/**
 * React to any of the common buttons on a page for voting, favorites and closing a notice
 * If the user has Javascript on, these should come through Ajax rather than here.
 */
function as_check_page_clicks()
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_page_error_html;

	if (as_is_http_post()) {
		foreach ($_POST as $field => $value) {
			if (strpos($field, 'like_') === 0) { // voting...
				@list($dummy, $postid, $like, $anchor) = explode('_', $field);

				if (isset($postid) && isset($like)) {
					if (!as_check_form_security_code('like', as_post_text('code')))
						$as_page_error_html = as_lang_html('misc/form_security_again');

					else {
						require_once AS_INCLUDE_DIR . 'app/likes.php';
						require_once AS_INCLUDE_DIR . 'db/selects.php';

						$userid = as_get_logged_in_userid();

						$post = as_db_select_with_pending(as_db_full_post_selectspec($userid, $postid));
						$as_page_error_html = as_like_error_html($post, $like, $userid, as_request());

						if (!$as_page_error_html) {
							as_like_set($post, $userid, as_get_logged_in_handle(), as_cookie_get(), $like);
							as_redirect(as_request(), $_GET, null, null, $anchor);
						}
						break;
					}
				}

			} elseif (strpos($field, 'favorite_') === 0) { // favorites...
				@list($dummy, $entitytype, $entityid, $favorite) = explode('_', $field);

				if (isset($entitytype) && isset($entityid) && isset($favorite)) {
					if (!as_check_form_security_code('favorite-' . $entitytype . '-' . $entityid, as_post_text('code')))
						$as_page_error_html = as_lang_html('misc/form_security_again');

					else {
						require_once AS_INCLUDE_DIR . 'app/favorites.php';

						as_user_favorite_set(as_get_logged_in_userid(), as_get_logged_in_handle(), as_cookie_get(), $entitytype, $entityid, $favorite);
						as_redirect(as_request(), $_GET);
					}
				}

			} elseif (strpos($field, 'notice_') === 0) { // notices...
				@list($dummy, $noticeid) = explode('_', $field);

				if (isset($noticeid)) {
					if (!as_check_form_security_code('notice-' . $noticeid, as_post_text('code')))
						$as_page_error_html = as_lang_html('misc/form_security_again');

					else {
						if ($noticeid == 'visitor')
							setcookie('as_noticed', 1, time() + 86400 * 3650, '/', AS_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);

						elseif ($noticeid == 'welcome') {
							require_once AS_INCLUDE_DIR . 'db/users.php';
							as_db_user_set_flag(as_get_logged_in_userid(), AS_USER_FLAGS_WELCOME_NOTICE, false);

						} else {
							require_once AS_INCLUDE_DIR . 'db/notices.php';
							as_db_usernotice_delete(as_get_logged_in_userid(), $noticeid);
						}

						as_redirect(as_request(), $_GET);
					}
				}
			}
		}
	}
}


/**
 *	Run the appropriate /as-include/pages/*.php file for this request and return back the $as_content it passed
 */
function as_get_request_content()
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	$requestlower = strtolower(as_request());
	$requestparts = as_request_parts();
	$firstlower = strtolower($requestparts[0]);
	$routing = as_page_routing();

	if (isset($routing[$requestlower])) {
		as_set_template($firstlower);
		$as_content = require AS_INCLUDE_DIR . $routing[$requestlower];

	} elseif (isset($routing[$firstlower . '/'])) {
		as_set_template($firstlower);
		$as_content = require AS_INCLUDE_DIR . $routing[$firstlower . '/'];

	} elseif (is_numeric($requestparts[0])) {
		as_set_template('item');
		$as_content = require AS_INCLUDE_DIR . 'pages/item.php';

	} else {
		as_set_template(strlen($firstlower) ? $firstlower : 'as'); // will be changed later
		$as_content = require AS_INCLUDE_DIR . 'pages/default.php'; // handles many other pages, including custom pages and page modules
	}

	if ($firstlower == 'admin') {
		$_COOKIE['as_admin_last'] = $requestlower; // for navigation tab now...
		setcookie('as_admin_last', $_COOKIE['as_admin_last'], 0, '/', AS_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true); // ...and in future
	}

	if (isset($as_content))
		as_set_form_security_key();

	return $as_content;
}


/**
 *    Output the $as_content via the theme class after doing some pre-processing, mainly relating to Javascript
 * @param $as_content
 * @return mixed
 */
function as_output_content($as_content)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_template;

	$requestlower = strtolower(as_request());

	// Set appropriate selected flags for navigation (not done in as_content_prepare() since it also applies to sub-navigation)

	foreach ($as_content['navigation'] as $navtype => $navigation) {
		if (!is_array($navigation) || $navtype == 'cat') {
			continue;
		}

		foreach ($navigation as $navprefix => $navlink) {
			$selected =& $as_content['navigation'][$navtype][$navprefix]['selected'];
			if (isset($navlink['selected_on'])) {
				// match specified paths
				foreach ($navlink['selected_on'] as $path) {
					if (strpos($requestlower . '$', $path) === 0)
						$selected = true;
				}
			} elseif ($requestlower === $navprefix || $requestlower . '$' === $navprefix) {
				// exact match for array key
				$selected = true;
			}
		}
	}

	// Slide down notifications

	if (!empty($as_content['notices'])) {
		foreach ($as_content['notices'] as $notice) {
			$as_content['script_onloads'][] = array(
				"as_reveal(document.getElementById(" . as_js($notice['id']) . "), 'notice');",
			);
		}
	}

	// Handle maintenance mode

	if (as_opt('site_maintenance') && ($requestlower != 'signin')) {
		if (as_get_logged_in_level() >= AS_USER_LEVEL_ADMIN) {
			if (!isset($as_content['error'])) {
				$as_content['error'] = strtr(as_lang_html('admin/maintenance_admin_only'), array(
					'^1' => '<a href="' . as_path_html('admin/general') . '">',
					'^2' => '</a>',
				));
			}
		} else {
			$as_content = as_content_prepare();
			$as_content['error'] = as_lang_html('misc/site_in_maintenance');
		}
	}

	// Handle new users who must confirm their email now, or must be approved before continuing

	$userid = as_get_logged_in_userid();
	if (isset($userid) && $requestlower != 'confirm' && $requestlower != 'account') {
		$flags = as_get_logged_in_flags();

		if (($flags & AS_USER_FLAGS_MUST_CONFIRM) && !($flags & AS_USER_FLAGS_EMAIL_CONFIRMED) && as_opt('confirm_user_emails')) {
			$as_content = as_content_prepare();
			$as_content['title'] = as_lang_html('users/confirm_title');
			$as_content['error'] = strtr(as_lang_html('users/confirm_required'), array(
				'^1' => '<a href="' . as_path_html('confirm') . '">',
				'^2' => '</a>',
			));
		}

		// we no longer block access here for unapproved users; this is handled by the Permissions settings
	}

	// Combine various Javascript elements in $as_content into single array for theme layer

	$script = array('<script>');

	if (isset($as_content['script_var'])) {
		foreach ($as_content['script_var'] as $var => $value) {
			$script[] = 'var ' . $var . ' = ' . as_js($value) . ';';
		}
	}

	if (isset($as_content['script_lines'])) {
		foreach ($as_content['script_lines'] as $scriptlines) {
			$script[] = '';
			$script = array_merge($script, $scriptlines);
		}
	}

	$script[] = '</script>';

	if (isset($as_content['script_rel'])) {
		$uniquerel = array_unique($as_content['script_rel']); // remove any duplicates
		foreach ($uniquerel as $script_rel) {
			$script[] = '<script src="' . as_html(as_path_to_root() . $script_rel) . '"></script>';
		}
	}

	if (isset($as_content['script_src'])) {
		$uniquesrc = array_unique($as_content['script_src']); // remove any duplicates
		foreach ($uniquesrc as $script_src) {
			$script[] = '<script src="' . as_html($script_src) . '"></script>';
		}
	}

	// JS onloads must come after jQuery is loaded

	if (isset($as_content['focusid'])) {
		$as_content['script_onloads'][] = array(
			'$(' . as_js('#' . $as_content['focusid']) . ').focus();',
		);
	}

	if (isset($as_content['script_onloads'])) {
		$script[] = '<script>';
		$script[] = '$(window).on(\'load\', function() {';

		foreach ($as_content['script_onloads'] as $scriptonload) {
			foreach ((array)$scriptonload as $scriptline) {
				$script[] = "\t" . $scriptline;
			}
		}

		$script[] = '});';
		$script[] = '</script>';
	}

	if (!isset($as_content['script'])) {
		$as_content['script'] = array();
	}

	$as_content['script'] = array_merge($as_content['script'], $script);

	// Load the appropriate theme class and output the page

	$tmpl = substr($as_template, 0, 7) == 'custom-' ? 'custom' : $as_template;
	$themeclass = as_load_theme_class(as_get_site_theme(), $tmpl, $as_content, as_request());
	$themeclass->initialize();

	header('Content-type: ' . $as_content['content_type']);

	$themeclass->doctype();
	$themeclass->html();
	$themeclass->finish();
}


/**
 * Update any statistics required by the fields in $as_content, and return true if something was done
 * @param $as_content
 * @return bool
 */
function as_do_content_stats($as_content)
{
	if (!isset($as_content['inc_views_postid'])) {
		return false;
	}

	require_once AS_INCLUDE_DIR . 'db/hotness.php';

	$viewsIncremented = as_db_increment_views($as_content['inc_views_postid']);

	if ($viewsIncremented && as_opt('recalc_hotness_q_view')) {
		as_db_hotness_update($as_content['inc_views_postid']);
	}

	return true;
}


// Other functions which might be called from anywhere

/**
 * Return an array of the default APS requests and which /as-include/pages/*.php file implements them
 * If the key of an element ends in /, it should be used for any request with that key as its prefix
 */
function as_page_routing()
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	return array(
		'account' => 'pages/account.php',
		'activity/' => 'pages/activity.php',
		'admin/' => 'pages/admin/admin-default.php',
		'admin/approve' => 'pages/admin/admin-approve.php',
		'admin/categories' => 'pages/admin/admin-categories.php',
		'admin/products' => 'pages/admin/admin-products.php',
		'admin/flagged' => 'pages/admin/admin-flagged.php',
		'admin/hidden' => 'pages/admin/admin-hidden.php',
		'admin/layoutwidgets' => 'pages/admin/admin-widgets.php',
		'admin/moderate' => 'pages/admin/admin-moderate.php',
		'admin/pages' => 'pages/admin/admin-pages.php',
		'admin/plugins' => 'pages/admin/admin-plugins.php',
		'admin/points' => 'pages/admin/admin-points.php',
		'admin/recalc' => 'pages/admin/admin-recalc.php',
		'admin/stats' => 'pages/admin/admin-stats.php',
		'admin/userfields' => 'pages/admin/admin-userfields.php',
		'admin/usertitles' => 'pages/admin/admin-usertitles.php',
		'reviews/' => 'pages/reviews.php',
		'post' => 'pages/post.php',
		'categories/' => 'pages/categories.php',
		'comments/' => 'pages/comments.php',
		'confirm' => 'pages/confirm.php',
		'favorites' => 'pages/favorites.php',
		'favorites/items' => 'pages/favorites-list.php',
		'favorites/users' => 'pages/favorites-list.php',
		'favorites/tags' => 'pages/favorites-list.php',
		'feedback' => 'pages/feedback.php',
		'forgot' => 'pages/forgot.php',
		'hot/' => 'pages/hot.php',
		'ip/' => 'pages/ip.php',
		'signin' => 'pages/signin.php',
		'signout' => 'pages/signout.php',
		'messages/' => 'pages/messages.php',
		'message/' => 'pages/message.php',
		'business/' => 'pages/business.php',
		'department/' => 'pages/businessdept.php',
		'products/' => 'pages/products.php',
		'products/dashboard' => 'pages/products-dashboard.php',
		'signup' => 'pages/signup.php',
		'signup-supplier' => 'pages/signup-supplier.php',
		'signup-customer' => 'pages/signup-customer.php',
		'reset' => 'pages/reset.php',
		'search' => 'pages/search.php',
		'orders/' => 'pages/orders.php',
		'orders/dashboard' => 'pages/orders-dashboard.php',
		'tag/' => 'pages/tag.php',
		'tags' => 'pages/tags.php',
		'unreviewed/' => 'pages/unreviewed.php',
		'unsubscribe' => 'pages/unsubscribe.php',
		'updates' => 'pages/updates.php',
		'user/' => 'pages/user.php',
		'users' => 'pages/users.php',
		'users/blocked' => 'pages/users-blocked.php',
		'users/new' => 'pages/users-newest.php',
		'users/special' => 'pages/users-special.php',
	);
}


/**
 * Sets the template which should be passed to the theme class, telling it which type of page it's displaying
 * @param $template
 */
function as_set_template($template)
{
	global $as_template;
	$as_template = $template;
}


/**
 * Start preparing theme content in global $as_content variable, with or without $voting support,
 * in the context of the categories in $categoryids (if not null)
 * @param bool $voting
 * @param array $categoryids
 * @return array
 */
function as_content_prepare($voting = false, $categoryids = array())
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_template, $as_page_error_html;

	if (AS_DEBUG_PERFORMANCE) {
		global $as_usage;
		$as_usage->mark('control');
	}

	$request = as_request();
	$requestlower = as_request();
	$navpages = as_db_get_pending_result('navpages');
	$widgets = as_db_get_pending_result('widgets');

	if (!is_array($categoryids)) {
		// accept old-style parameter
		$categoryids = array($categoryids);
	}

	$lastcategoryid = count($categoryids) > 0 ? end($categoryids) : null;
	$charset = 'utf-8';
	$language = as_opt('site_language');
	$language = empty($language) ? 'en' : as_html($language);

	$as_content = array(
		'content_type' => 'text/html; charset=' . $charset,
		'charset' => $charset,

		'language' => $language,

		'direction' => as_opt('site_text_direction'),

		'options' => array(
			'minify_html' => as_opt('minify_html'),
		),

		'site_title' => as_html(as_opt('site_title')),

		'html_tags' => 'lang="' . $language . '"',

		'head_lines' => array(),

		'navigation' => array(
			'user' => array(),

			'main' => array(),

			'footer' => array(
				'feedback' => array(
					'url' => as_path_html('feedback'),
					'label' => as_lang_html('main/nav_feedback'),
				),
			),

		),

		'sidebar' => as_opt('show_custom_sidebar') ? as_opt('custom_sidebar') : null,
		'sidepanel' => as_opt('show_custom_sidepanel') ? as_opt('custom_sidepanel') : null,
		'widgets' => array(),
	);

	// add meta description if we're on the home page
	if ($request === '' || $request === array_search('', as_get_request_map())) {
		$as_content['description'] = as_html(as_opt('home_description'));
	}

	if (as_opt('show_custom_in_head'))
		$as_content['head_lines'][] = as_opt('custom_in_head');

	if (as_opt('show_custom_header'))
		$as_content['body_header'] = as_opt('custom_header');

	if (as_opt('show_custom_footer'))
		$as_content['body_footer'] = as_opt('custom_footer');

	$rules['superadmin'] = (AS_USER_TYPE == 'SA');
	$rules['baseadmin'] = (AS_USER_TYPE == 'A');
	$rules['employeenr'] = (AS_USER_TYPE == 'ENR');
	$rules['employeer'] = (AS_USER_TYPE == 'ER');
	$rules['physicalsp'] = (AS_USER_TYPE == 'SSP');
	$rules['mobilesp'] = (AS_USER_TYPE == 'MSP');
	$rules['bcustomer'] = (AS_USER_TYPE == 'BC');
	$rules['icustomer'] = (AS_USER_TYPE == 'IC');
	$rules['bsupplier'] = (AS_USER_TYPE == 'BS');
	$rules['isupplier'] = (AS_USER_TYPE == 'IS');
	$rules['baseuser'] = (AS_USER_TYPE == 'U');

	if (isset($categoryids))
		$as_content['categoryids'] = $categoryids;

	foreach ($navpages as $page) {
		if ($page['nav'] == 'B')
			as_navigation_add_page($as_content['navigation']['main'], $page);
	}

    $as_content['navigation']['main']['home'] = array(
        'label' => as_lang_html('main/nav_home'),
        'url' => as_path_html(''),
        'icon' => 'fa fa-dashboard',
    );

    if ($rules['baseuser']) {
        $as_content['navigation']['main']['business'] = array(
            'label' => as_lang_html('main/nav_bs'),
            'url' => as_path_html('business'),
            'icon' => 'fa fa-dashboard',
        );
    }

    /*if ($rules['baseuser']) {
        $as_content['navigation']['main']['sell'] = array(
            'label' => as_lang_html('main/nav_sell'),
            'url' => as_path_html('products'),
            'icon' => 'fa fa-dashboard',
        );
    }*/

    if ($rules['superadmin']) {
        require_once AS_INCLUDE_DIR . 'app/admin.php';
		$as_content['navigation']['main'] = as_admin_sub_navigation($as_content['navigation']['main']);
    }
	
	$as_content['search'] = array(
		'form_tags' => 'method="get" action="' . as_path_html('search') . '"',
		'form_extra' => as_path_form_html('search'),
		'title' => as_lang_html('main/search_title'),
		'field_tags' => 'name="q"',
		'button_label' => as_lang_html('main/search_button'),
	);

	if (!as_opt('feedback_enabled'))
		unset($as_content['navigation']['footer']['feedback']);

	foreach ($navpages as $page) {
		if ($page['nav'] == 'M' || $page['nav'] == 'O' || $page['nav'] == 'F') {
			$loc = ($page['nav'] == 'F') ? 'footer' : 'main';
			as_navigation_add_page($as_content['navigation'][$loc], $page);
		}
	}

	$regioncodes = array(
		'F' => 'full',
		'M' => 'main',
		'S' => 'side',
	);

	$placecodes = array(
		'T' => 'top',
		'H' => 'high',
		'L' => 'low',
		'B' => 'bottom',
	);

	foreach ($widgets as $widget) {
		$tagstring = ',' . $widget['tags'] . ',';
		$showOnTmpl = strpos($tagstring, ",$as_template,") !== false || strpos($tagstring, ',all,') !== false;
		// special case for user pages
		$showOnUser = strpos($tagstring, ',user,') !== false && preg_match('/^user(-.+)?$/', $as_template) === 1;

		if ($showOnTmpl || $showOnUser) {
			// widget has been selected for display on this template
			$region = @$regioncodes[substr($widget['place'], 0, 1)];
			$place = @$placecodes[substr($widget['place'], 1, 2)];

			if (isset($region) && isset($place)) {
				// region/place codes recognized
				$module = as_load_module('widget', $widget['title']);
				$allowTmpl = (substr($as_template, 0, 7) == 'custom-') ? 'custom' : $as_template;

				if (isset($module) &&
					method_exists($module, 'allow_template') && $module->allow_template($allowTmpl) &&
					method_exists($module, 'allow_region') && $module->allow_region($region) &&
					method_exists($module, 'output_widget')
				) {
					// if module loaded and happy to be displayed here, tell theme about it
					$as_content['widgets'][$region][$place][] = $module;
				}
			}
		}
	}

	$logoshow = as_opt('logo_show');
	$logourl = as_opt('logo_url');
	$logowidth = as_opt('logo_width');
	$logoheight = as_opt('logo_height');

	if ($logoshow) {
		$as_content['logo'] = '<a href="' . as_path_html('') . '" class="as-logo-link" title="' . as_html(as_opt('site_title')) . '">' .
			'<img src="' . as_html(is_numeric(strpos($logourl, '://')) ? $logourl : as_path_to_root() . $logourl) . '"' .
			($logowidth ? (' width="' . $logowidth . '"') : '') . ($logoheight ? (' height="' . $logoheight . '"') : '') .
			' alt="' . as_html(as_opt('site_title')) . '"/></a>';
	} else {
		$as_content['logo'] = '<a href="' . as_path_html('') . '" class="as-logo-link">' . as_html(as_opt('site_title')) . '</a>';
	}

	$topath = as_get('to'); // lets user switch between signin and signup without losing destination page

	$userlinks = as_get_signin_links(as_path_to_root(), isset($topath) ? $topath : as_path($request, $_GET, ''));

	$as_content['navigation']['user'] = array();

	if (as_is_logged_in()) {
		$as_content['signedin'] = as_lang_html_sub_split('main/logged_in_x', AS_FINAL_EXTERNAL_USERS
			? as_get_logged_in_user_html(as_get_logged_in_user_cache(), as_path_to_root(), false)
			: as_get_one_user_html(as_get_logged_in_handle(), false)
		);

		$as_content['navigation']['user']['updates'] = array(
			'url' => as_path_html('updates'),
			'label' => as_lang_html('main/nav_updates'),
		);

		if (!empty($userlinks['signout'])) {
			$as_content['navigation']['user']['signout'] = array(
				'url' => as_html(@$userlinks['signout']),
				'label' => as_lang_html('main/nav_signout'),
			);
		}

		if (!AS_FINAL_EXTERNAL_USERS) {
			$source = as_get_logged_in_source();

			if (strlen($source)) {
				$signinmodules = as_load_modules_with('signin', 'match_source');

				foreach ($signinmodules as $module) {
					if ($module->match_source($source) && method_exists($module, 'signout_html')) {
						ob_start();
						$module->signout_html(as_path('signout', array(), as_opt('site_url')));
						$as_content['navigation']['user']['signout'] = array('label' => ob_get_clean());
					}
				}
			}
		}

		$notices = as_db_get_pending_result('notices');
		foreach ($notices as $notice)
			$as_content['notices'][] = as_notice_form($notice['noticeid'], as_viewer_html($notice['content'], $notice['format']), $notice);

	} else {
		require_once AS_INCLUDE_DIR . 'util/string.php';

		if (!AS_FINAL_EXTERNAL_USERS) {
			$signinmodules = as_load_modules_with('signin', 'signin_html');

			foreach ($signinmodules as $tryname => $module) {
				ob_start();
				$module->signin_html(isset($topath) ? (as_opt('site_url') . $topath) : as_path($request, $_GET, as_opt('site_url')), 'menu');
				$label = ob_get_clean();

				if (strlen($label))
					$as_content['navigation']['user'][implode('-', as_string_to_words($tryname))] = array('label' => $label);
			}
		}

		if (!empty($userlinks['signin'])) {
			$as_content['navigation']['user']['signin'] = array(
				'url' => as_html(@$userlinks['signin']),
				'label' => as_lang_html('main/nav_signin'),
			);
		}

		if (!empty($userlinks['signup'])) {
			$as_content['navigation']['user']['signup'] = array(
				'url' => as_html(@$userlinks['signup']),
				'label' => as_lang_html('main/nav_signup'),
			);
		}
	}

	$notifications = as_db_notifications(as_get_logged_in_userid());
	if (count($notifications)) 
		$as_content['notifications'] = $notifications;
	
	if (AS_FINAL_EXTERNAL_USERS || !as_is_logged_in()) {
		if (as_opt('show_notice_visitor') && (!isset($topath)) && (!isset($_COOKIE['as_noticed'])))
			$as_content['notices'][] = as_notice_form('visitor', as_opt('notice_visitor'));

	} else {
		setcookie('as_noticed', 1, time() + 86400 * 3650, '/', AS_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true); // don't show first-time notice if a user has logged in

		if (as_opt('show_notice_welcome') && (as_get_logged_in_flags() & AS_USER_FLAGS_WELCOME_NOTICE)) {
			if ($requestlower != 'confirm' && $requestlower != 'account') // let people finish signuping in peace
				$as_content['notices'][] = as_notice_form('welcome', as_opt('notice_welcome'));
		}
	}

	$as_content['script_rel'] = array('as-content/jquery-3.3.1.min.js');
	$as_content['script_rel'][] = 'as-content/as-global.js?' . AS_VERSION;

	if ($voting)
		$as_content['error'] = @$as_page_error_html;

	$as_content['script_var'] = array(
		'as_root' => as_path_to_root(),
		'as_request' => $request,
	);

	return $as_content;
}

/**
 * Get the start parameter which should be used, as constrained by the setting in as-config.php
 * @return int
 */
function as_get_start()
{
	return min(max(0, (int)as_get('start')), AS_MAX_LIMIT_START);
}


/**
 * Get the state parameter which should be used, as set earlier in as_load_state()
 * @return string
 */
function as_get_state()
{
	global $as_state;
	return $as_state;
}


/**
 * Generate a canonical URL for the current request. Preserves certain GET parameters.
 * @return string The full canonical URL.
 */
function as_get_canonical()
{
	$params = array();

	// variable assignment intentional here
	if (($start = as_get_start()) > 0) {
		$params['start'] = $start;
	}
	if ($sort = as_get('sort')) {
		$params['sort'] = $sort;
	}
	if ($by = as_get('by')) {
		$params['by'] = $by;
	}

	return as_path_html(as_request(), $params, as_opt('site_url'));
}
