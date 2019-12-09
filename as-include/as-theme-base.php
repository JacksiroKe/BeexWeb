<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Default theme class, broken into lots of little functions for easy overriding


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
	header('Location: ../');
	exit;
}


/*
	How do I make a theme which goes beyond CSS to actually modify the HTML output?

	Create a file named as-theme.php in your new theme directory which defines a class as_html_theme
	that extends this base class as_html_theme_base. You can then override any of the methods below,
	referring back to the default method using double colon (as_html_theme_base::) notation.

	Plugins can also do something similar by using a layer. For more information and to see some example
	code, please consult the online APS documentation.
*/

class as_html_theme_base
{
	public $template;
	public $content;
	public $rooturl;
	public $request;
	public $isRTL; // (boolean) whether text direction is Right-To-Left

	protected $minifyHtml; // (boolean) whether to indent the HTML
	protected $indent = 0;
	protected $lines = 0;
	protected $context = array();

	// whether to use new block layout in rankings (true) or fall back to tables (false)
	protected $ranking_block_layout = false;
	// theme 'slug' to use as CSS class
	protected $theme;


	/**
	 * Initialize the object and assign local variables.
	 * @param $template
	 * @param $content
	 * @param $rooturl
	 * @param $request
	 */
	public function __construct($template, $content, $rooturl, $request)
	{
		$this->template = $template;
		$this->content = $content;
		$this->rooturl = $rooturl;
		$this->request = $request;
		$this->isRTL = isset($content['direction']) && $content['direction'] === 'rtl';
		$this->minifyHtml = !empty($content['options']['minify_html']);
	}

	/**
	 * @deprecated PHP4-style constructor deprecated from 1.7; please use proper `__construct`
	 * function instead.
	 * @param $template
	 * @param $content
	 * @param $rooturl
	 * @param $request
	 */
	public function as_html_theme_base($template, $content, $rooturl, $request)
	{
		self::__construct($template, $content, $rooturl, $request);
	}


	/**
	 * Output each element in $elements on a separate line, with automatic HTML indenting.
	 * This should be passed markup which uses the <tag/> form for unpaired tags, to help keep
	 * track of indenting, although its actual output converts these to <tag> for W3C validation.
	 * @param $elements
	 */
	public function output_array($elements)
	{
		foreach ($elements as $element) {
			$line = str_replace('/>', '>', $element);
			if (strlen($line)) echo $line . "\n";			
			$this->lines++;
		}
	}


	/**
	 * Output each passed parameter on a separate line - see output_array() comments.
	 */
	public function output() // other parameters picked up via func_get_args()
	{
		$args = func_get_args();
		$this->output_array($args);
	}


	/**
	 * Output $html at the current indent level, but don't change indent level based on the markup within.
	 * Useful for user-entered HTML which is unlikely to follow the rules we need to track indenting.
	 * @param $html
	 */
	public function output_raw($html)
	{
		if (strlen($html))
			echo str_repeat("\t", max(0, $this->indent)) . $html . "\n";
	}


	/**
	 * Output the three elements ['prefix'], ['data'] and ['suffix'] of $parts (if they're defined),
	 * with appropriate CSS classes based on $class, using $outertag and $innertag in the markup.
	 * @param $parts
	 * @param $class
	 * @param string $outertag
	 * @param string $innertag
	 * @param string $extraclass
	 */
	public function output_split($parts, $class, $outertag = 'span', $innertag = 'span', $extraclass = null)
	{
		if (empty($parts) && strtolower($outertag) != 'td')
			return;

		$this->output(
			'<' . $outertag . ' class="' . $class . (isset($extraclass) ? (' ' . $extraclass) : '') . '">',
			(strlen(@$parts['prefix']) ? ('<' . $innertag . ' class="' . $class . '-pad">' . $parts['prefix'] . '</' . $innertag . '>') : '') .
			(strlen(@$parts['data']) ? ('<' . $innertag . ' class="' . $class . '-data">' . $parts['data'] . '</' . $innertag . '>') : '') .
			(strlen(@$parts['suffix']) ? ('<' . $innertag . ' class="' . $class . '-pad">' . $parts['suffix'] . '</' . $innertag . '>') : ''),
			'</' . $outertag . '>'
		);
	}


	/**
	 * Set some context, which be accessed via $this->context for a function to know where it's being used on the page.
	 * @param $key
	 * @param $value
	 */
	public function set_context($key, $value)
	{
		$this->context[$key] = $value;
	}


	/**
	 * Clear some context (used at the end of the appropriate loop).
	 * @param $key
	 */
	public function clear_context($key)
	{
		unset($this->context[$key]);
	}

	/**
	 * Reorder the parts of the page according to the $parts array which contains part keys in their new order. Call this
	 * before main_parts(). See the docs for as_array_reorder() in util/sort.php for the other parameters.
	 * @param $parts
	 * @param string $beforekey
	 * @param bool $reorderrelative
	 */
	public function reorder_parts($parts, $beforekey = null, $reorderrelative = true)
	{
		require_once AS_INCLUDE_DIR . 'util/sort.php';

		as_array_reorder($this->content, $parts, $beforekey, $reorderrelative);
	}


	/**
	 * Output the widgets (as provided in $this->content['widgets']) for $region and $place.
	 * @param $region
	 * @param $place
	 */
	public function widgets($region, $place)
	{
		$widgetsHere = isset($this->content['widgets'][$region][$place]) ? $this->content['widgets'][$region][$place] : array();
		if (is_array($widgetsHere) && count($widgetsHere) > 0) {
			$this->output("\t\t".'<div class="as-widgets-' . $region . ' as-widgets-' . $region . '-' . $place . '">');

			foreach ($widgetsHere as $module) {
				$this->output("\t\t".'<div class="as-widget-' . $region . ' as-widget-' . $region . '-' . $place . '">');
				$module->output_widget($region, $place, $this, $this->template, $this->request, $this->content);
				$this->output("\t\t".'</div>');
			}

			$this->output("\t\t".'</div>', '');
		}
	}

	/**
	 * Pre-output initialization. Immediately called after loading of the module. Content and template variables are
	 * already setup at this point. Useful to perform layer initialization in the earliest and safest stage possible.
	 */
	public function initialize()
	{
		// abstract method
	}

	/**
	 * Post-output cleanup. For now, check that the indenting ended right, and if not, output a warning in an HTML comment.
	 */
	public function finish()
	{
		if ($this->indent !== 0 && !$this->minifyHtml) {
			echo "<!--\nIt's no big deal, but your HTML could not be indented properly. To fix, please:\n" .
				"1. Use this->output() to output all HTML.\n" .
				"2. Balance all paired tags like <td>...</td> or <div>...</div>.\n" .
				"3. Use a slash at the end of unpaired tags like <img/> or <input/>.\n" .
				"Thanks!\n-->\n";
		}
	}


	// From here on, we have a large number of class methods which output particular pieces of HTML markup
	// The calling chain is initiated from as-page.php, or ajax/*.php for refreshing parts of a page,
	// For most HTML elements, the name of the function is similar to the element's CSS class, for example:
	// search() outputs <div class="as-search">, p_list() outputs <div class="as-p-list">, etc...

	public function doctype()
	{
		$this->output('<!DOCTYPE html>');
	}

	public function html()
	{
		$attribution = "\t<!-- Powered by AppSmata - http://www.appsmata.org/ -->";
		$extratags = isset($this->content['html_tags']) ? $this->content['html_tags'] : '';

		$this->output(
			'<html ' . $extratags . '>',
			$attribution
		);

		$this->head();
		$this->body();

		$this->output(
			$attribution,
			'</html>'
		);
	}

	public function head()
	{
		$this->output(
			'<head>',
			'<meta charset="' . $this->content['charset'] . '"/>'
		);

		$this->head_title();
		$this->head_metas();
		$this->head_css();
		$this->head_links();
		$this->head_lines();
		$this->head_script();
		$this->head_custom();

		$this->output("\t\t".'</head>');
	}

	public function head_title()
	{
		$pagetitle = strlen($this->request) ? strip_tags(@$this->content['title']) : '';
		$headtitle = (strlen($pagetitle) ? "$pagetitle - " : '') . $this->content['site_title'];

		$this->output("\t\t<title>" . $headtitle . "</title>");
	}

	public function head_metas()
	{
		if (strlen(@$this->content['description'])) {
			$this->output("\t\t".'<meta name="description" content="' . $this->content['description'] . '"/>');
		}

		if (strlen(@$this->content['keywords'])) {
			// as far as I know, meta keywords have zero effect on search rankings or listings
			$this->output("\t\t".'<meta name="keywords" content="' . $this->content['keywords'] . '"/>');
		}
	}

	public function head_links()
	{
		if (isset($this->content['canonical'])) {
			$this->output("\t\t".'<link rel="canonical" href="' . $this->content['canonical'] . '"/>');
		}

		if (isset($this->content['feed']['url'])) {
			$this->output("\t\t".'<link rel="alternate" type="application/rss+xml" href="' . $this->content['feed']['url'] . '" title="' . @$this->content['feed']['label'] . '"/>');
		}

		// convert page links to rel=prev and rel=next tags
		if (isset($this->content['page_links']['items'])) {
			foreach ($this->content['page_links']['items'] as $page_link) {
				if (in_array($page_link['type'], array('prev', 'next')))
					$this->output("\t\t".'<link rel="' . $page_link['type'] . '" href="' . $page_link['url'] . '" />');
			}
		}
	}

	public function head_script()
	{
		if (isset($this->content['script'])) {
			foreach ($this->content['script'] as $scriptline) {
				$this->output_raw($scriptline);
			}
		}
	}

	public function head_css()
	{
		$this->output("\t\t".'<link rel="stylesheet" href="' . $this->rooturl . $this->css_name() . '"/>');

		if (isset($this->content['css_src'])) {
			foreach ($this->content['css_src'] as $css_src) {
				$this->output("\t\t".'<link rel="stylesheet" href="' . $css_src . '"/>');
			}
		}

		if (!empty($this->content['notices'])) {
			$this->output(
				"\t\t<style>",
				"\t\t".'.as-body-js-on .as-notice {display:none;}',
				"\t\t</style>"
			);
		}
	}

	public function css_name()
	{
		return 'as-styles.css?' . AS_VERSION;
	}

	public function head_lines()
	{
		if (isset($this->content['head_lines'])) {
			foreach ($this->content['head_lines'] as $line) {
				$this->output_raw($line);
			}
		}
	}

	public function head_custom()
	{
		// abstract method
	}

	public function body()
	{
		$this->output("\t".'<body class="as-body-js-off hold-transition skin-yellow sidebar-mini">');

		$this->body_script();
		$this->body_header();
		$this->body_content();	
		$this->body_footer();
		$this->body_hidden();

		$this->output("\t</body>");
	}

	public function body_tags()
	{
		$class = 'as-template-' . as_html($this->template);
		$class .= empty($this->theme) ? '' : ' as-theme-' . as_html($this->theme);

		if (isset($this->content['categoryids'])) {
			foreach ($this->content['categoryids'] as $categoryid) {
				$class .= ' as-category-' . as_html($categoryid);
			}
		}

		$this->output("\t\t".'class="' . $class . ' as-body-js-off"');
	}
	public function body_hidden()
	{
		$this->output("\t\t".'<div style="position:absolute;overflow:hidden;clip:rect(0 0 0 0);height:0;width:0;margin:0;padding:0;border:0;">');
		$this->waiting_template();
		$this->output("\t\t".'</div>');
	}

	public function waiting_template()
	{
		$this->output("\t\t".'<span id="as-waiting-template" class="as-waiting">...</span>');
	}

	public function body_script()
	{
		$this->output(
			"\t\t".'<script>',
			"var b = document.getElementsByTagName('body')[0];",
			"b.className = b.className.replace('as-body-js-off', 'as-body-js-on');",
			"\t\t".'</script>'
		);
	}

	public function body_header()
	{
		if (isset($this->content['body_header'])) {
			$this->output_raw($this->content['body_header']);
		}
	}

	public function body_footer()
	{
		if (isset($this->content['body_footer'])) {
			$this->output_raw($this->content['body_footer']);
		}
	}

	public function body_content()
	{
		$this->body_prefix();
		$this->notices();

		$extratags = isset($this->content['wrapper_tags']) ? $this->content['wrapper_tags'] : '';
		$this->output("\t\t".'<div class="as-body-wrapper"' . $extratags . '>');

		$this->widgets('full', 'top');
		$this->header();
		$this->dashboard();
		$this->widgets('full', 'high');
		$this->sidepanel();
		$this->main();
		$this->widgets('full', 'low');
		$this->footer();
		$this->widgets('full', 'bottom');

		$this->output("\t\t".'</div> <!-- END body-wrapper -->');

		$this->body_suffix();
	}

	public function body_prefix()
	{
		// abstract method
	}

	public function body_suffix()
	{
		// abstract method
	}

	public function notices()
	{
		if (!empty($this->content['notices'])) {
			foreach ($this->content['notices'] as $notice) {
				$this->notice($notice);
			}
		}
	}

	public function notice($notice)
	{
		$this->output("\t\t".'<div class="as-notice" id="' . $notice['id'] . '">');

		if (isset($notice['form_tags']))
			$this->output("\t\t".'<form ' . $notice['form_tags'] . '>');

		$this->output_raw($notice['content']);

		$this->output("\t\t".'<input ' . $notice['close_tags'] . ' type="submit" value="X" class="as-notice-close-button"/> ');

		if (isset($notice['form_tags'])) {
			$this->form_hidden_elements(@$notice['form_hidden']);
			$this->output("\t\t".'</form>');
		}

		$this->output("\t\t".'</div>');
	}

	public function header()
	{
		$this->output("\t\t".'<div class="as-header">');

		$this->logo();
		$this->nav_user_search();
		$this->nav_main_sub();
		$this->header_clear();

		$this->output("\t\t".'</div> <!-- END as-header -->', '');
	}

	public function messages()
	{
		if (!(isset($this->content['messages']) ? $this->content['messages'] : null)) return;
		$this->output("\t\t".'<li class="dropdown messages-menu">
		<!-- Menu toggle button -->
		<a href="#" class="dropdown-toggle" data-toggle="dropdown">
		<i class="fa fa-envelope-o"></i>
		<span class="label label-success">4</span>
		</a>
		<ul class="dropdown-menu">
		<li class="header">You have 4 messages</li>
		<li>
		<!-- inner menu: contains the messages -->
		<ul class="menu">
		<li><!-- start message -->
		<a href="#">
		<div class="pull-left">
		<!-- User Image -->
		<img src="'.$this->userimage.'" class="img-circle" alt="User Image">
		</div>
		<!-- Message title and timestamp -->
		<h4>
		Support Team
		<small><i class="fa fa-clock-o"></i> 5 mins</small>
		</h4>
		<!-- The message -->
		<p>Why not buy a new awesome theme?</p>
		</a>
		</li>
		<!-- end message -->
		</ul>
		<!-- /.menu -->
		</li>
		<li class="footer"><a href="#">See All Messages</a></li>
		</ul>
		</li>');
	}

	public function notifications()
	{
		$notifys = $this->content['notifications'];

		$this->output("\t\t".'<li class="dropdown notifications-menu">');
		$this->output("\t\t".'<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-bell-o"></i>');
		$this->output("\t\t".'<span class="label label-warning">'.count($notifys).'</span></a>');
		$this->output("\t\t".'<ul class="dropdown-menu">', '<li class="header">You have '.count($notifys));
		$this->output("\t\t".' notification'.(count($notifys) == 1 ? '' : 's').'</li>');
		foreach ($notifys as $notify) {
			$this->output("\t\t".'<li>', '<ul class="menu">', '<li>');
			if (isset($notify['link'])) $this->output("\t\t".'<a href="'.$notify['link'].'">');
			else $this->output("\t\t".'<a href="#">');
			//$this->output("\t\t".'<i class="fa fa-users text-aqua"></i>');
			$this->output($notify['message'].'</a>');
			$this->output("\t\t".'</li>', '</ul>', '</li>');
		}
		$this->output("\t\t".'<li class="footer"><a href="#">View all</a></li>');
		$this->output("\t\t".'</ul>', '</li>');
	}

	public function tasks()
	{
		if (!(isset($this->content['tasks']) ? $this->content['tasks'] : null)) return;
		$this->output("\t\t".'<li class="dropdown tasks-menu">
		<!-- Menu Toggle Button -->
		<a href="#" class="dropdown-toggle" data-toggle="dropdown">
		<i class="fa fa-flag-o"></i>
		<span class="label label-danger">9</span>
		</a>
		<ul class="dropdown-menu">
		<li class="header">You have 9 tasks</li>
		<li>
		<!-- Inner menu: contains the tasks -->
		<ul class="menu">
		<li><!-- Task item -->
		<a href="#">
		<!-- Task title and progress text -->
		<h3>
		Design some buttons
		<small class="pull-right">20%</small>
		</h3>
		<!-- The progress bar -->
		<div class="progress xs">
		<!-- Change the css width attribute to simulate progress -->
		<div class="progress-bar progress-bar-aqua" style="width: 20%" role="progressbar"
		aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
		<span class="sr-only">20% Complete</span>
		</div>
		</div>
		</a>
		</li>
		<!-- end task item -->
		</ul>
		</li>
		<li class="footer">
		<a href="#">View all tasks</a>
		</li>
		</ul>
		</li>');
	}

	public function dashboard()
	{
		if (!(isset($this->content['user']) ? $this->content['user'] : null)) return;
		$user = $this->content['user'];
		$fields = $this->content['dashboard']['fields'];
		$handle = $user['handle'];
		$userid = $user['userid'];
		
		$this->output("\t\t".'<style>.as-main-heading{display:none;} .as-main{width: 100%!important;}</style>');
		$this->output("\t\t".'
			<div class="sp_content">
				<div class="container">
					<div class="row">
						<div class="sp_header"> 
							<p>'.$user['avatar'].' '.$user['name'].', Your Dashboard!</p>
						</div> 
						<!--<div class="sp_item"> 
							<div class="desc"> 
								'.$user['avatar'].'
								<h3>'.$user['grouped'].' @'.$user['handle'].'</h3>
								<ul>
									<li>'.$user['email'].'</li>
									<li>'.$user['mobile'].'</li>');
			if (isset($user['info']))
			foreach ($user['info'] as $userinfo ) $this->show_detail( $userinfo, 'li' );
		$this->output("\t\t".'
								</ul>
							</div> 
						</div>
						<div class="sp_item">
							<div class="desc"> 
								<h3>Your Notifications</h3>
								<ul>');
			if (isset($fields['notify']))
			foreach ($fields['notify'] as $notifications ) $this->show_detail( $notification, 'li' );
		$this->output("\t\t".'
								</ul>
							</div>
						</div> 
						<div class="sp_item">
							<div class="desc"> 
								<h3>Your Achievements</h3>
								<ul><li>'.$user['points'].'</li></ul>
							</div>
						</div>-->
					</div>
				</div>
			</div>
			');
	}
	
	public function nav_user_search()
	{
		$this->nav('user');
		$this->search();
	}

	public function nav_main_sub()
	{
		$this->nav('main');
		$this->nav('sub');
	}

	public function logo()
	{
		$this->output(
			'<div class="as-logo">',
			$this->content['logo'],
			'</div>'
		);
	}

	public function search()
	{
		$search = $this->content['search'];

		$this->output(
			'<div class="as-search">',
			'<form ' . $search['form_tags'] . '>',
			@$search['form_extra']
		);

		$this->search_field($search);
		$this->search_button($search);

		$this->output(
			'</form>',
			'</div>'
		);
	}

	public function search_field($search)
	{
		$this->output("\t\t".'<input type="text" ' . $search['field_tags'] . ' value="' . @$search['value'] . '" class="as-search-field"/>');
	}

	public function search_button($search)
	{
		$this->output("\t\t".'<input type="submit" value="' . $search['button_label'] . '" class="as-search-button"/>');
	}

	public function nav($navtype, $level = null)
	{
		$navigation = @$this->content['navigation'][$navtype];
		switch ($navtype) {
			case'user':
				$this->output("\t\t".'<div class="as-nav-' . $navtype . '">');

				if ($navtype == 'user')
					$this->signed_in();

				// reverse order of 'opposite' items since they float right
				foreach (array_reverse($navigation, true) as $key => $navlink) {
					if (@$navlink['opposite']) {
						unset($navigation[$key]);
						$navigation[$key] = $navlink;
					}
				}

				$this->set_context('nav_type', $navtype);
				$this->nav_list($navigation, 'nav-' . $navtype, $level);
				$this->nav_clear($navtype);
				$this->clear_context('nav_type');

				$this->output("\t\t".'</div>');
				break;
				
			case 'main':
				foreach ( $navigation as $key => $item ) {
					if (isset($item['sub'])) {
						$this->output("\t\t".'<li class="treeview">', '<a href="#">');
						$this->output("\t\t".'<i class="' . (isset($item['icon']) ? $item['icon'] : 'fa fa-link') . '"></i>');
						$this->output("\t\t".'<span style="overflow: hidden;"> ' . $item['label'] . ' </span>');
						$this->output("\t\t".'<span class="pull-right-container">',
							'<i class="fa fa-angle-left pull-right"></i>', '</span>', '</a>');
						$this->output("\t\t".'<ul class="treeview-menu">');
						foreach ( $item['sub'] as $k => $sub ) {
							$this->output("\t\t".'<li>', '<a href="'.$sub['url'].'">');
							$this->output("\t\t".'<i class="' . (isset($sub['icon']) ? $sub['icon'] : 'fa fa-link') . '"></i>');
							$this->output("\t\t".'<span>' . $sub['label'] . '</span></a>', '</li>');
						}
						$this->output("\t\t".'</ul>');
						$this->output("\t\t".'</li>');
					} else {
						if (isset($item['url'])) {
							$this->output("\t\t".'<li>', '<a href="'.$item['url'].'">');
							$this->output("\t\t".'<i class="' . (isset($item['icon']) ? $item['icon'] : 'fa fa-link') . '"></i>');
							$this->output("\t\t".'<span>' . $item['label'] . '</span></a>', '</li>');
						}
						else $this->output("\t\t".'<li class="header">'.strtoupper($item['label']).'</li>');
					}
				}
				break;
		}
	}

	public function nav_list($navigation, $class, $level = null)
	{
		$this->output("\t\t".'<ul class="as-' . $class . '-list' . (isset($level) ? (' as-' . $class . '-list-' . $level) : '') . '">');

		$index = 0;

		foreach ($navigation as $key => $navlink) {
			$this->set_context('nav_key', $key);
			$this->set_context('nav_index', $index++);
			$this->nav_item($key, $navlink, $class, $level);
		}

		$this->clear_context('nav_key');
		$this->clear_context('nav_index');

		$this->output("\t\t".'</ul>');
	}

	public function nav_clear($navtype)
	{
		$this->output(
			'<div class="as-nav-' . $navtype . '-clear">',
			'</div>'
		);
	}

	public function nav_item($key, $navlink, $class, $level = null)
	{
		$suffix = strtr($key, array( // map special character in navigation key
			'$' => '',
			'/' => '-',
		));

		$this->output("\t\t".'<li class="as-' . $class . '-item' . (@$navlink['opposite'] ? '-opp' : '') .
			(@$navlink['state'] ? (' as-' . $class . '-' . $navlink['state']) : '') . ' as-' . $class . '-' . $suffix . '">');
			
		if (strlen(@$navlink['icon'])) $this->output($navlink['icon']);
		
		$this->nav_link($navlink, $class);

		$subnav = isset($navlink['subnav']) ? $navlink['subnav'] : array();
		if (is_array($subnav) && count($subnav) > 0) {
			$this->nav_list($subnav, $class, 1 + $level);
		}

		$this->output("\t\t".'</li>');
	}

	public function nav_link($navlink, $class)
	{
		if (isset($navlink['url'])) {
			$this->output(
				'<a href="' . $navlink['url'] . '" class="as-' . $class . '-link' .
				(@$navlink['selected'] ? (' as-' . $class . '-selected') : '') .
				(@$navlink['favorited'] ? (' as-' . $class . '-favorited') : '') .
				'"' . (strlen(@$navlink['popup']) ? (' title="' . $navlink['popup'] . '"') : '') .
				(isset($navlink['target']) ? (' target="' . $navlink['target'] . '"') : '') . '>' . $navlink['label'] .
				'</a>'
			);
		} else {
			$this->output(
				'<span class="as-' . $class . '-nolink' . (@$navlink['selected'] ? (' as-' . $class . '-selected') : '') .
				(@$navlink['favorited'] ? (' as-' . $class . '-favorited') : '') . '"' .
				(strlen(@$navlink['popup']) ? (' title="' . $navlink['popup'] . '"') : '') .
				'>' . $navlink['label'] . '</span>'
			);
		}

		if (strlen(@$navlink['note']))
			$this->output("\t\t".'<span class="as-' . $class . '-note">' . $navlink['note'] . '</span>');
	}

	public function signed_in()
	{
		$this->output_split(@$this->content['signedin'], 'as-signed-in', 'div');
	}

	public function header_clear()
	{
		$this->output(
			'<div class="as-header-clear">',
			'</div>'
		);
	}
	
	public function cover_page( $name, $handle, $avatar, $isowner, $points )
	{
		$this->output("\t\t".'<style>.as-main-heading{display:none;}</style>');
		$this->output("\t\t".'<div class="user-top clearfix">');
		//if ( $isowner ) $this->output("\t\t".'<a id="upload-cover" class="btn btn-default">Change cover</a>');
		$this->output("\t\t".'<div class="user-bar"><div class="avatar pull-left">' . $avatar . '</div></div>');
		$this->output("\t\t".'<div class="user-bar-holder">
					<div class="user-stat pull-right">
						<ul>
							<li class="points">' . $points . '</li>
							<li class="followers">0 <span>Followers</span></li>
						</ul>
					</div>');
		$this->output("\t\t".'<div class="user-nag"><div class="user-buttons pull-right"></div>
						<span class="full-name">'.$name.' ('.$handle. ')</span>');
	}
	
	public function show_detail( $profile, $htag, $class = null, $tags = null )
	{
		$this->output("\t\t".'<'.$htag . (isset($class) ? ' class="' . $class . '"' : '') .
			 (isset($id) ? ' ' . $tags . '"' : '') . '>'. $profile.'</'. $htag .'>');
	}
		
	public function profile_page($user)
	{
		$favorite = isset($this->content['favorite']) ? $this->content['favorite'] : null;
		$actionbtns = isset($this->content['profile_actions']) ? $this->content['profile_actions'] : null;
		$listposts = isset($this->content['list_posts']) ? $this->content['list_posts'] : null;

		$this->cover_page($user['user_title'].$this->content['title'], $user['handle'], $user['user_avatar'], 
		(($user['logedin_user'] == $user['userid']) ? true : false ), $user['user_points']);
		
		if (isset($actionbtns)) {
			$this->output("\t\t".'<form class="pull-right" ' . $actionbtns['form_tags'] . '>');
			
			if (isset($user['private_message'])){
				$this->output("\t\t".'<span class="send_private_message">'.$user['private_message'].'</span>');
			}
			$this->action_buttons($actionbtns);
			$formhidden = isset($actionbtns['form_hidden']) ? $actionbtns['form_hidden'] : null;
			$this->form_hidden_elements($formhidden);
			$this->output("\t\t".'</form>');
		}
		
		$this->output("\t\t".'</div>','</div>','</div>');			
		$this->output("\t\t".'<div class="as-profile-side">');
		$this->output("\t\t".'<ul class="as-profile-item">');
		if (isset($user['user_info']))
			foreach ($user['user_info'] as $userinfo ) $this->show_detail( $userinfo, 'li' );
		$this->output("\t\t".'</ul>');
		$this->output("\t\t".'</div>');
		
		$this->output("\t\t".'<div class="as-profile-wall">');
		$this->p_list_and_form($listposts);
		$this->output("\t\t".'</div>');
	}
	
	public function action_buttons($actionbtns)
	{
		$actionbtntags = isset($actionbtns['action_tags']) ? $actionbtns['action_tags'] : '';
		$this->output("\t\t".'<span ' . $actionbtntags . '>');
		foreach ($actionbtns['buttons'] as $button => $btn) {
			$this->output("\t\t".'<input type="submit" value="'.$btn['label'].'" '.$btn['tags'].' class="as-form-profile-btn as-form-profile-' . $button . '"/> ');
		}
		$this->output("\t\t".'</span>');
	}

	public function sidepanel()
	{
		$this->output("\t\t".'<div class="as-sidepanel">');
		$this->widgets('side', 'top');
		$this->sidebar();
		$this->widgets('side', 'high');
		$this->widgets('side', 'low');
		$this->output_raw(@$this->content['sidepanel']);
		$this->feed();
		$this->widgets('side', 'bottom');
		$this->output("\t\t".'</div>', '');
	}

	public function sidebar()
	{
		$sidebar = @$this->content['sidebar'];

		if (!empty($sidebar)) {
			$this->output("\t\t".'<div class="as-sidebar">');
			$this->output_raw($sidebar);
			$this->output("\t\t".'</div>', '');
		}
	}

	public function feed()
	{
		$feed = @$this->content['feed'];

		if (!empty($feed)) {
			$this->output("\t\t".'<div class="as-feed">');
			$this->output("\t\t".'<a href="' . $feed['url'] . '" class="as-feed-link">' . @$feed['label'] . '</a>');
			$this->output("\t\t".'</div>');
		}
	}

	public function main()
	{
		$content = $this->content;
		$hidden = !empty($content['hidden']) ? ' as-main-hidden' : '';
		
		$this->output("\t\t".'<div class="content-wrapper fixed-cw">');
		$this->output("\t\t".'<section class="content-header">');

		if (isset($this->content['error'])) $this->output("\t\t".'<h1>'.as_opt('site_title').'</h1>');
		else $this->output("\t\t".'<h1>'.@$content['title'].'</h1>');
		
		if (strlen($this->request)) {
			$this->output("\t\t".'<ol class="breadcrumb">');
			$this->output("\t\t".'<li><a href="'.as_opt('site_url') . '"><i class="fa fa-dashboard"></i> Home</a></li>');
			//$this->output("\t\t".'<li><a href="#">Examples</a></li>');
			$this->output("\t\t".'<li class="active">'.@$content['title'].'</li>');
			$this->output("\t\t".'</ol>');
		}
		
		$this->output("\t\t".'</section>');
	  
		$this->output("\t\t".'<section class="content container-fluid"> <!-- Main Content -->');
		
		if (isset($this->content['success'])) $this->success($this->content['success']);
		if (isset($this->content['error'])) $this->error($this->content['error']);	

		$this->main_parts($content);
		$this->output("\t\t".'</section> <!-- End Main Content -->');
		$this->output("\t\t".'</div> <!-- END as-main -->', '');
	}
	
	public function guest()
	{
		$content = $this->content;
		
		foreach ($content as $key => $part) {
			if (strpos($key, 'minimal') === 0)
				$this->minimal($part);
		}
	}
	
	public function page_title_error()
	{
		if (isset($this->content['title'])) {
			$favorite = isset($this->content['favorite']) ? $this->content['favorite'] : null;

			if (isset($favorite))
				$this->output("\t\t".'<form ' . $favorite['form_tags'] . '>');

			$this->output("\t\t".'<div class="as-main-heading">');
			$this->favorite();
			$this->output("\t\t".'<h1>');
			$this->output($this->template == 'item' ? $this->content['icon'] : '');
			$this->title();
			$this->output("\t\t".'</h1>');
			$this->output("\t\t".'</div>');

			if (isset($favorite)) {
				$formhidden = isset($favorite['form_hidden']) ? $favorite['form_hidden'] : null;
				$this->form_hidden_elements($formhidden);
				$this->output("\t\t".'</form>');
			}
		}

		if (isset($this->content['success']))
			$this->success($this->content['success']);
		if (isset($this->content['error']))
			$this->error($this->content['error']);
	}

	public function favorite()
	{
		$favorite = isset($this->content['favorite']) ? $this->content['favorite'] : null;
		if (isset($favorite)) {
			$favoritetags = isset($favorite['favorite_tags']) ? $favorite['favorite_tags'] : '';
			$this->output("\t\t".'<span class="as-favoriting" ' . $favoritetags . '>');
			$this->favorite_inner_html($favorite);
			$this->output("\t\t".'</span>');
		}
	}

	public function title()
	{
		$p_view = @$this->content['p_view'];

		// link title where appropriate
		$url = isset($p_view['url']) ? $p_view['url'] : false;

		if (isset($this->content['title'])) {
			$this->output(
				$url ? '<a href="' . $url . '">' : '',
				$this->content['title'],
				$url ? '</a>' : ''
			);
		}

		// add closed note in title
		if (!empty($p_view['closed']['state']))
			$this->output("\t\t".' [' . $p_view['closed']['state'] . ']');
	}

	public function favorite_inner_html($favorite)
	{
		$this->favorite_button(@$favorite['favorite_add_tags'], 'as-favorite');
		$this->favorite_button(@$favorite['favorite_remove_tags'], 'as-unfavorite');
	}

	public function favorite_button($tags, $class)
	{
		if (isset($tags))
			$this->output("\t\t".'<input ' . $tags . ' type="submit" value="" class="' . $class . '-button"/> ');
	}

	public function error($error)
	{
		if (strlen($error)) {
			$this->output("\t\t".'<div class="callout callout-danger">');
            $this->output("\t\t".'<h2>'.$this->content['title'].'!</h2>');
			$this->output("\t\t".'<h4>'.$error.'</h4>');
			$this->output("\t\t".'</div>');
		}
	}

	public function success($message)
	{
		if (strlen($message)) {
			$this->output("\t\t".'<div class="callout callout-success">');
            $this->output("\t\t".'<h2>'.$this->content['title'].'!</h2>');
			$this->output("\t\t".'<h4>'.$message.'</h4>');
			$this->output("\t\t".'</div>');
		}
	}

	public function main_parts($content)
	{
		foreach ($content as $key => $part) {
			$this->set_context('part', $key);
			$this->main_part($key, $part);
		}

		$this->clear_context('part');
	}

	public function main_part($key, $part)
	{
		$isRanking = strpos($key, 'ranking') === 0;

		$partdiv = (
			strpos($key, 'custom') === 0 ||
			strpos($key, 'form') === 0 ||
			strpos($key, 'p_list') === 0 ||
			(strpos($key, 'p_view') === 0 && !isset($this->content['form_q_edit'])) ||
			strpos($key, 'a_form') === 0 ||
			strpos($key, 'a_list') === 0 ||
			$isRanking ||
			strpos($key, 'message_list') === 0 ||
			strpos($key, 'nav_list') === 0
		);

		if (strpos($key, 'custom') === 0)
			$this->output_raw($part);

		elseif (strpos($key, 'form') === 0)
			$this->form($part);

		elseif (strpos($key, 'p_list') === 0)
			$this->p_list_and_form($part);

		elseif (strpos($key, 'p_view') === 0)
			$this->p_view($part);

		elseif (strpos($key, 'a_form') === 0)
			$this->a_form($part);

		elseif (strpos($key, 'a_list') === 0)
			$this->a_list($part);

		elseif (strpos($key, 'listing') === 0)
			$this->listing($part);

		elseif (strpos($key, 'gridlayout') === 0)
			$this->gridlayout($part);
			
		elseif (strpos($key, 'ranking') === 0)
			$this->ranking($part);

		elseif (strpos($key, 'message_list') === 0)
			$this->message_list_and_form($part);

		elseif (strpos($key, 'profile_page') === 0)
			$this->profile_page($part);

		elseif (strpos($key, 'nav_list') === 0) {
			$this->part_title($part);
			$this->nav_list($part['nav'], $part['type'], 1);
		}

		elseif (strpos($key, 'row_view') === 0)
			$this->row_view($part);

	}

	public function footer()
	{
		$this->output("\t\t".'<div class="as-footer">');

		$this->nav('footer');
		$this->attribution();
		$this->footer_clear();

		$this->output("\t\t".'</div> <!-- END as-footer -->', '');
	}

	public function attribution()
	{
		$this->output(
			'<div class="as-attribution">',
			'Powered by <a href="http://www.appsmata.org/">AppSmata</a>',
			'</div>'
		);
	}

	public function footer_clear()
	{
		$this->output(
			'<div class="as-footer-clear">',
			'</div>'
		);
	}

	public function section($title)
	{
		$this->part_title(array('title' => $title));
	}

	public function part_title($part)
	{
		if (strlen(@$part['title']) || strlen(@$part['title_tags']))
			$this->output("\t\t".'<h2' . rtrim(' ' . @$part['title_tags']) . '>' . @$part['title'] . '</h2>');
	}

	public function part_footer($part)
	{
		if (isset($part['footer']))
			$this->output($part['footer']);
	}

	public function box_title($box)
	{
		$this->output("\t\t".'<div class="box-header with-border">');
		if (isset($box['icon'])) {
			if (isset($box['icon']['url'])) $this->output("\t\t".'<a href="'.$box['icon']['url'].'" class="'.$box['icon']['class'].'">');
			$this->output("\t\t".'<i class="fa fa-'.$box['icon']['fa'].'"></i>');
			if (isset($box['icon']['label'])) $this->output($box['icon']['label']);
			if (isset($box['icon']['url'])) $this->output("\t\t".'</a>');
		}
		$this->output("\t\t".'<h3 class="box-title">'.$box['title'].'</h3>');
		if (isset($box['tools'])) {			
			$this->output("\t\t".'<div class="box-tools">');
			foreach ($box['tools'] as $tl => $tool)  {
				switch ($tool['type']) {
					case 'submit':
						$this->output("\t\t".' <input' . rtrim(' ' . @$tool['tags']) . ' value="' . @$tool['label'] . '" title="' . @$tool['popup'] . '" type="submit" class="btn btn-info"/> ');
						break;
						
					case 'button':
						$this->output("\t\t".'<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>');
						break;
							
					case 'button_md':
						$this->output("\t\t".'<button type="button" class="'.$tool['class'].'" data-toggle="modal" data-target="'.$tool['url'].'">'.$tool['label'].'</button>');
						break;
						
					case 'link':
						$this->output("\t\t".'<a href="'.$tool['url'].'" class="'.$tool['class'].'">' . @$tool['label'] . '</a>');
						break;
					
					case 'buttonx':
						$this->output("\t\t".'<button type="button" class="btn btn-box-'.$tool['class'].'" data-widget="'.$tool['data-widget'].'"><i class="fa fa-'.$tl.'"></i></button>');
						break;
						
					case 'label':
						$this->output("\t\t".'<span class="label label-' . @$tool['theme'] . '">' . @$tool['label'] . '</span>');
						break;
					
					case 'small':
						$this->output("\t\t".'<small class="pull-right">' . @$tool['label'] . '</small>');
						break;
				}
			}
			$this->output("\t\t".'</div>');
		}
		if (isset($box['modals'])) $this->modal_view($box['modals']);
		$this->output("\t\t".'</div>');
	}

	public function modal_view($modals)
	{
		foreach ($modals as $md => $modal)  {
			$this->output("\t".'<!-- Beginning of a modal -->');
			$this->output("\t\t".'<div class="'.$modal['class'].'" id="'.$md.'">');
			$this->output("\t\t\t".'<div class="modal-dialog">');
			$this->output("\t\t\t\t".'<div class="modal-content">');

			$this->output("\t\t\t\t\t".'<div class="modal-header">');
			$this->output("\t\t\t\t\t\t".'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-close"></i></button>');
			$this->output("\t\t\t\t\t\t".'<h4 class="modal-title">'.$modal['header']['title'].'</h4>');
			$this->output("\t\t\t\t\t".'</div>');
			
			$this->output("\t\t\t\t\t".'<div class="modal-body">');
			
			switch ($modal['view']['type']) {
				case 'form':
					$this->form($modal['view']);
					break;
				default:
					$this->output($modal['view']['html']);
					break;
			}

			$this->output("\t\t\t\t\t\t".'</div>');
			$this->output("\t\t\t\t\t".'</div>');
			$this->output("\t\t\t\t".'</div>');
			$this->output("\t\t\t".'</div>');
			$this->output("\t\t".'</div>', "\t".'<!-- End of this modal -->');
		}
	}
	
	public function user_search($search)
	{
		if (isset($search)) {
			$this->output("\t\t".'<form role="form" id="user_modal_search">');
			$this->output("\t\t".'<div class="box-body">');
			$this->output("\t\t".'<div class="form-group">');
			$this->output("\t\t".'<label>Search for a User</label>');
			$this->output("\t\t".'<input type="text" class="form-control" id="usersearch" name="usersearch" placeholder="Enter an email address or name">');
			$this->output("\t\t".'</div>');
			$this->output("\t\t".'<div class="form-group">');
			$this->output("\t\t".'<div id="userresults"></div>');
			$this->output("\t\t".'</div>');
			$this->output("\t\t".'</div>');

			$this->output("\t\t".'<div class="box-footer">');
			$this->output("\t\t".'<input class="btn btn-primary" value="ADD AS A MANAGER" 
				name="doaddmanager" onclick="as_show_waiting_after(this, false); return as_add_manager(this);">');
			$this->output("\t\t".'</div>', '</form>');
		}
	}

	public function form($form)
	{
		if (!empty($form)) {
			if (isset($form['title'])) $this->output("\t\t".'<div class="box box-info">');
			
			if (isset($form['tags']))
				$this->output("\t\t".'<form class="form-horizontal" ' . $form['tags'] . '>');
			if (isset($form['title'])) $this->box_title($form);

			$this->form_body($form);

			if (isset($form['tags']))
				$this->output("\t\t".'</form>');
			if (isset($form['title'])) $this->output("\t\t".'</div>');
		}
	}

	public function minimal($form)
	{
		if (!empty($form)) {
			$this->output("\t\t".'<div class="box box-info">');
			
			if (isset($form['tags']))
				$this->output("\t\t".'<form class="form-horizontal" ' . $form['tags'] . '>');
			if (isset($form['title'])) $this->box_title($form);

			$this->form_body($form);

			if (isset($form['tags']))
				$this->output("\t\t".'</form>');
			$this->output("\t\t".'</div>');
		}
	}

	public function form_columns($form)
	{
		if (isset($form['ok']) || !empty($form['fields']))
			$columns = ($form['style'] == 'wide') ? 2 : 1;
		else
			$columns = 0;

		return $columns;
	}

	public function row_view($content)
	{
		foreach ($content as $bx => $row) {
			$this->output("\t\t".'<div class="row">');
			if (isset($row['section'])) 
				$this->output("\t\t".'<section class="'.$row['section'].'">');
							
			foreach ($row['colms'] as $bx => $column) {
				$this->output("\t\t".'<div class="'.$column['class'].'"'.
					(isset($column['id']) ? ' id="'.$column['id'].'"' : '').
					(isset($column['tags']) ? ' '.$column['tags'] : '').
					'>');
				
				if (isset($column['extras'])) 
					foreach ($column['extras'] as $xt => $extras) $this->output($extras);
				
				if (isset($column['c_items'])) $this->column_view($column['c_items']);
				if (isset($column['modals'])) $this->modal_view($column['modals']);
				$this->output("\t\t".'</div>');
			}
			if (isset($row['section'])) $this->output("\t\t".'</section>');
			$this->output("\t\t".'</div>');
		}
	}

	public function column_view($content)
	{	
		foreach ($content as $ci => $c_item) {
			if (isset($c_item['type']))
				
				if (isset($c_item['alert_view'])) $this->alert_view($c_item['alert_view']['type'], $c_item['alert_view']['message']);
				if (isset($c_item['callout_view'])) $this->callout_view($c_item['callout_view']['type'], $c_item['callout_view']['message']);
				
				switch ($c_item['type'])
				{
					case 'box':
						$this->box_view($c_item);
						break;
						
					case 'list':
						$this->list_view($c_item);
						break;
							
					case 'dashlist':
						$this->dashlist_view($c_item);
						break;

					case 'bslist':
						$this->bslist_view($c_item);
						break;
						
					case 'small-box':
						$this->smallbox_view($c_item);
						break;
						
					case 'btn-app':
						$this->btnapp_view($c_item);
						break;
					
					case 'tabs':
						$this->tabs_view($c_item);
						break;
						
					case 'form':
						$this->form($c_item);
						break;
						
					case 'custom':
						$this->custom($c_item);
						break;
							
					case 'table':
						$this->table($c_item);
						break;

					case 'modals':
						$this->modal_view($c_item);
						break;
							
					case 'carousel':
						$this->carousel($c_item);
						break;
				}
		}
	}

	public function custom($html)
	{
		if (!empty($html)) {
			$this->output("\t\t".'<div class="box box-'.$html['theme'].'"'.(isset($html['id']) ? ' id="'.$html['id'].'"' : '').
			(isset($html['tags']) ? ' '.$html['tags'] : '').'>');
			
			if (isset($html['title'])) $this->box_title($html);
				
			if (isset($html['body'])) $this->output("\t\t".$html['body']);
            $this->output("\t\t".'</div>');
		}	
	}
	
	public function tabs_view($tabs)
	{
		if (isset($tabs)) {
			$this->output("\t\t".'<div class="nav-tabs-custom">');
			$this->output("\t\t".'<ul class="nav nav-tabs'.(isset($tabs['right']) ? ' pull-right' : '').'">');
			$i = 0;
			foreach ($tabs['navs'] as $nv => $nav) {
				$this->output("\t\t".'<li'.($i==0 ? ' class="active"' : '').'><a href="#'.$nv.'" data-toggle="tab">'.$nav.'</a></li>');					
				$i++;
			}
            $this->output("\t\t".'</ul>');
			
            $this->output("\t\t".' <div class="tab-content">');
			$t = 0;
			if (isset($tabs['pane']))
				foreach ($tabs['pane'] as $tb => $tabpane) {
					$this->output("\t\t".'<div class="'.($t==0 ? 'active ' : '').'tab-pane" id="'.$tb.'">');
					$this->tab_pane($tabpane);
					$this->output("\t\t".'</div>');
					$t++;				
				}
			$this->output("\t\t".'</div>', '</div>');		
		}		
	}
	
	public function tab_pane($pane)
	{
		switch ($pane['type']) {
			case 'box':
				$this->box_view($pane);	
				break;
			case 'posts':
				$this->post_view($pane);	
				break;
			case 'tlines':
				$this->tline_view($pane);	
				break;
			case 'form':
				$this->form($pane);	
				break;
		}
	}
	
	public function post_view($post)
	{
		foreach ($post['blocks'] as $blk => $block) {
			$this->output("\t\t".'<div class="'.$post['class'].'">');
			if ($blk == 'user-block') {
				$this->user_block($block);
			}
			else {
				$this->output("\t\t".'<'.$block['elem'].' class="'.$blk.'">');
				switch ($block['elem']) 
				{
					case 'p':
						$this->output($block['text']);
						break;
				}	
				$this->output("\t\t".'</'.$block['elem'].'>');			
			}
			$this->output("\t\t".'</div>');
		}
	}
	
	public function tline_view($tline)
	{
		$this->output("\t\t".'<ul class="timeline timeline-inverse">');
		$this->output("\t\t".'<li');					
			if (isset($tline['class'])) {							
				$this->output("\t\t".'class="'.$tline['class'].'">');
				if ($tline['class'] == 'time-label')
					$this->output("\t\t".'<span class="bg-red">'.$tline['data']['text'].'</span>');
				
			}
			else {
				$this->output("\t\t".'>');
				$this->tline_data($tline['data']);
			}
			$this->output("\t\t".'</li>');
		$this->output("\t\t".'</ul>');
	}
	
	public function user_block($user)
	{
		$this->output("\t\t".'<div class="user-block">');
		$this->output("\t\t".'<img class="img-circle img-bordered-sm" src="'.$user['img'].'" alt="user image">');
		$this->output("\t\t".'<span class="username">',
			'<a href="#">'.$user['user'].'</a>',
			'<a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>',
			'</span>');
		$this->output("\t\t".'<span class="description">'.$user['text'].'</span>');
		$this->output("\t\t".'</div>');
	}
	
	public function carousel($carousel)
	{
		if (!empty($carousel)) {
			$this->output("\t\t".'<div class="box box-primary"'.(isset($carousel['id']) ? ' id="'.$carousel['id'].'"' : '').
			(isset($carousel['tags']) ? ' '.$carousel['tags'] : '').'>');
			
			if (isset($carousel['title'])) $this->box_title($carousel);
			
			$this->output("\t\t".'<div class="box-body">');
			$this->output("\t\t".'<div class="carousel slide" id="'.$carousel['id'].'" data-ride="carousel">');
			$this->output("\t\t".'<ol class="carousel-indicators">');
            foreach ($carousel['body']['indicators']['slides'] as $is => $slider) {
				$this->output("\t\t".'<li data-target="#'.$carousel['body']['indicators']['data-target'].
				'" data-slide-to="'.$is.'" class="'.$slider.'"></li>');				
			}
			$this->output("\t\t".'</ol>');
			
			$this->output("\t\t".'<div class="carousel-inner">');
			foreach ($carousel['body']['slides'] as $bs => $slide) {
				$this->output("\t\t".'<div class="item '.$slide['class'].'">');
                $this->output("\t\t".'<img src="'.$slide['image'][0].'" alt="'.$slide['image'][1].'">');
				$this->output("\t\t".'<div class="carousel-caption">'.$slide['caption'].'</div>', '</div>');
			}
			$this->output("\t\t".'</div>');
			
			$this->output("\t\t".'<a class="left carousel-control" href="#'.$carousel['body']['indicators']['data-target'].
				'" data-slide="prev">
			<span class="fa fa-angle-left"></span>
			</a>');
			$this->output("\t\t".'<a class="right carousel-control" href="#'.$carousel['body']['indicators']['data-target'].
				'" data-slide="next">
			<span class="fa fa-angle-right"></span>
			</a>');
            $this->output("\t\t".'</div>', '</div>');
			
            $this->output("\t\t".'</div>', '</div>');
		}
	}
	
	public function box_view($box)
	{
		if (isset($box)) {
			if (isset($box['theme'])) $this->output("\t\t".'<div class="box box-'.$box['theme'].'"'.(isset($box['id']) ? ' id="'.$box['id'].'"' : '').
			(isset($box['tags']) ? ' '.$box['tags'] : '').'>');
			
			if (isset($box['title'])) $this->box_title($box);
				
			if (isset($box['body'])){
				$this->output("\t\t".'<div class="'.$box['body']['type'].'">');
				foreach ($box['body']['items'] as $bi => $item) {
					if ($item == '') $this->output("\t\t".'<hr>');
					else {
						switch ($item['tag'][0]) {
							case 'avatar':
								$this->output($item['img'], '<hr>');
								break;
								
							case 'link':
								$this->output("\t\t".'<a href="'.$item['href'].'" class="'.$item['tag'][1].'"><b>'.$item['label'].'</b></a>');
								break;
									
							case 'button':
								$this->output("\t\t".'<button type="button" class="'.$item['tag'][1].' data-toggle="modal" data-target="#modal-default">'.$item['label'].'</button>');
								break;
										
							case 'modalbtn':
								$this->output("\t\t".'<button type="button" class="'.$item['tag'][1].'" data-toggle="modal" data-target="#'.$bi.'"><b>'.$item['label'].'</b></button>');
								break;
									
							case 'list':
								$this->output("\t\t".'<'.$item['tag'][0].
									(isset($item['tag'][1]) ? ' class="'.$item['tag'][1].'"' : '').'>');
								foreach ($item['data'] as $dt => $ditem) 
								{
									$this->output("\t\t".'<li class="list-group-item">');
									$this->output("\t\t".'<b>'. $dt . '</b> <a class="pull-right">'.$ditem.'</a>');
									$this->output("\t\t".'</li>');
								}									
								$this->output("\t\t".'</'.$item['tag'][0].'>');
								break;
								
							default:
								$this->output("\t\t".'<'.$item['tag'][0].
									(isset($item['tag'][1]) ? ' class="'.$item['tag'][1].'"' : '').'>');
								
								if (isset($item['itag']))
									$this->output("\t\t".'<i class="fa fa-'.$item['itag'][0].' margin-r-'.$item['itag'][1].'"></i>');
								$this->item_data($item['data']);							
								$this->output("\t\t".'</'.$item['tag'][0].'>');
								break;
						}
					}
				}
				$this->output("\t\t".'</div>');
			}
			
            if (isset($box['theme'])) $this->output("\t\t".'</div>');
		}
	}
	
	public function smallbox_view($sbox)
	{
		if (!empty($sbox)) {
			$this->output("\t\t".'<div class="small-box bg-'.$sbox['theme'].'">');
			$this->output("\t\t".'<div class="inner">');
			$this->output("\t\t".'<h3>'.$sbox['count'].'</h3>');
			$this->output("\t\t".'<p>'.$sbox['title'].'</p>');
			$this->output("\t\t".'</div>');
			$this->output("\t\t".'<div class="icon">', '<i class="ion ion-'.$sbox['icon'].'"></i>', '</div>');
			$this->output("\t\t".'<a href="'.$sbox['link'].'" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>');
            $this->output("\t\t".'</div>');
		}
	}
	
	public function btnapp_view($btnapp)
	{
		if (!empty($btnapp)) {
			$this->output("\t\t".'<a class="btn btn-app" style="height:200px;width:200px;" href="'.(isset($btnapp['link'][1]) ? 
				$btnapp['link'] : '#').'">');
			if (isset($btnapp['updates'])) 
				$this->output("\t\t".'<span class="badge '.$btnapp['updates'][0].'" style="font-size:20px;">'.$btnapp['updates'][1].'</span>');
			if (isset($btnapp['img'])) $this->output($btnapp['img']);
			else $this->output("\t\t".'<div class="icon">', '<i class="fa fa-'.$btnapp['icon'].'" style="font-size:100px;"></i>', '</div>');
			$this->output("\t\t".'<h3>'.$btnapp['title'].'</h3>');
			$this->output("\t\t".'</a>');
		}
	}
	
	public function list_view($box)
	{
		if (!empty($box)) {
			$this->output("\t\t".'<div class="box box-'.$box['theme'].'"'.(isset($box['id']) ? ' id="'.$box['id'].'"' : '').
			(isset($box['tags']) ? ' '.$box['tags'] : '').'>');
			
			if (isset($box['title'])) $this->box_title($box);
				
			if (isset($box['body'])){
				$this->output("\t\t".'<div class="box-body">');
				switch ($box['body']['type']) {
					case 'product':
						$this->output("\t\t".'<ul class="products-list product-list-in-box">');
						if (isset($box['body']['items']))
						foreach ($box['body']['items'] as $bi => $item) {
							$this->output("\t\t".'<li class="item">
							<div class="product-img">
							<img src="'.$item['img'].'" alt="Item Image">
							</div>
							<div class="product-info">
							<a href="javascript:void(0)" class="product-title" style="font-size: 20px;">'.$item['label'].'
							<span class="label label-warning pull-right">'.$item['numbers'].'</span></a>
							<span class="product-description">'.$item['description'].'</span>
							</div>
							</li>');
						}
						$this->output("\t\t".'</ul>');
						break;					
				}
				$this->output("\t\t".'</div>');
			}
			
            $this->output("\t\t".'</div>');
		}
	}
	
	public function dashlist_view($dashlist)
	{
		if (!empty($dashlist)) {
			$this->output("\t\t".'<div class="box box-'.$dashlist['theme'].'"'.(isset($dashlist['id']) ? ' id="'.$dashlist['id'].'"' : '').
			(isset($dashlist['tags']) ? ' '.$dashlist['tags'] : '').'>');
			
			if (isset($dashlist['title'])) $this->box_title($dashlist);
			
			if (isset($dashlist['items'])) {
				$this->output("\t\t".'<div class="box-body">');
				$this->output("\t\t".'<ul class="products-list product-list-in-box">');
				
				foreach ($dashlist['items'] as $bi => $item) {
					$this->output("\t\t".'<li class="item">');
					$this->output("\t\t".'<div class="product-img">'.$item['img'].'</div>');
					$this->output("\t\t".'<div class="product-info">');
					$labels = explode('|', $item['label']);
					$this->output("\t\t".'<a href="'.$item['link'].'" class="product-title" style="font-size: 20px;">'.$labels[0].'</a> ');
					if (isset($labels[1])) $this->output( strtoupper($labels[1]));
					if (isset($item['description'])) $this->output("\t\t".'<span class="product-description">'.$item['description'].'</span>');
					$this->output("\t\t".'</div><br>');

					if (isset($item['infors'])) {
						foreach ($item['infors'] as $info) {
							$this->output("\t\t".'<a class="btn btn-app" style="height: 100px; margin-right:10px;">');
							if (isset($info['inew'])) $this->output("\t\t".'<span class="badge bg-green">'.$info['inew'].'</span>');
							$this->output("\t\t".'<i class="fa fa-'.$info['ibadge'].'"></i><h4>'.$info['icount'].'</h4>'.$info['ilabel'].'</a>');
						}
					}
					$this->output("\t\t".'</li>');
				}
				$this->output("\t\t".'</ul>');
			}
			
            $this->output("\t\t".'</div>');
		}
	}
	
	public function bslist_view($dashlist)
	{
		if (!empty($dashlist)) {
			$this->output("\t\t".'<div class="box box-'.$dashlist['theme'].'"'.(isset($dashlist['id']) ? ' id="'.$dashlist['id'].'"' : '').
			(isset($dashlist['tags']) ? ' '.$dashlist['tags'] : '').'>');
			
			if (isset($dashlist['title'])) $this->box_title($dashlist);
			
			if (isset($dashlist['items'])) {
				$this->output("\t\t".'<div class="box-body">');
				$this->output("\t\t".'<ul class="products-list product-list-in-box">');
				
				foreach ($dashlist['items'] as $bi => $item) {
					$labels = explode('|', $item['label']);
					$this->output("\t\t".'<div class="row">');

					$this->output("\t\t".'<div class="col-lg-4 col-xs-12">');

					$this->output("\t\t".'<div class="box box-widget widget-user">');
					$this->output("\t\t".'<div class="widget-user-header bg-aqua-active">
					  <h3 class="widget-user-username">'.$labels[0].'</h3>
					  <h5 class="widget-user-desc">'.$labels[1].'</h5>
					</div>');
					$this->output("\t\t".'<div class="widget-user-image">
					  <img class="img-circle" src="'.$item['img'].'" alt="Business Icon">
					</div>');
					$this->output("\t\t".'<div class="box-footer">');
					$this->output("\t\t".'<div class="row">');
					if (isset($item['numbers'])) {						
						foreach ($item['numbers'] as $nitem) {
							$this->output("\t\t".'<div class="col-sm-'.(12 / count($item['numbers'])).' border-right" '.$nitem['tags'].' style="cursor:pointer;">');
							$this->output("\t\t".'<div class="description-block">');
							$this->output("\t\t".'<h5 class="description-header">'.$nitem['ncount'].'</h5>');
							$this->output("\t\t".'<span class="description-text">'.$nitem['nlabel'].'</span>');
							$this->output("\t\t".'</div>', '</div>');
						}
					}
					$this->output("\t\t".'</div>', '</div>','</div>');
					$this->output("\t\t".'</div>');

					$this->output("\t\t".'<div class="col-lg-8 col-xs-12">');
					if (isset($item['parts'])) {
						$this->output("\t\t".'<div class="row" style="background: #eee; margin:5px;padding: 5px;border-radius: 5px;">');		
						foreach ($item['parts'] as $part) {
							$this->output("\t\t".'<a href="'.$part['link'].'"><div class="col-md-3">');
							$this->output("\t\t".'<div class="box box-widget widget-user-2">');
							$this->output("\t\t".'<div class="widget-user-header bg-yellow" style="padding:5px;">');
							$this->output("\t\t".'<h3 class="widget-user-username" style="margin-left:0px;">'.$part['label'].'</h3>');
							$this->output("\t\t".'<h5 class="widget-user-desc" style="margin-left:0px;">'.$part['description'].'</h5>');
							$this->output("\t\t".'</div>');
							$this->output("\t\t".'<div class="box-footer no-padding">', '<ul class="nav nav-stacked">');
							$this->output("\t\t".'<li><a href="#">Managers <span class="pull-right badge bg-blue">'.$part['managers'].'</span></a></li>');
							$this->output("\t\t".'</ul>', '</div>', '</div>');
							$this->output("\t\t".'</div></a>');
						}
						$this->output("\t\t".'</div>');
					}
					$this->output("\t\t".'</div>');

					$this->output("\t\t".'</div>');
					/*$this->output("\t\t".'<li class="item">');
					$this->output("\t\t".'<div class="product-img">'.$item['img'].'</div>');
					$this->output("\t\t".'<div class="product-info">');
					$labels = explode('|', $item['label']);
					$this->output("\t\t".'<a href="'.$item['link'].'" class="product-title" style="font-size: 20px;">'.$labels[0].'</a> ');
					if (isset($labels[1])) $this->output( strtoupper($labels[1]));
					if (isset($item['description'])) $this->output("\t\t".'<span class="product-description">'.$item['description'].'</span>');

					if (isset($item['infors'])) {
						foreach ($item['infors'] as $info) {
							$this->output("\t\t".'<a class="btn btn-app" style="height: 100px; margin-right:10px;">');
							if (isset($info['inew'])) $this->output("\t\t".'<span class="badge bg-green">'.$info['inew'].'</span>');
							$this->output("\t\t".'<i class="fa fa-'.$info['ibadge'].'"></i><h4>'.$info['icount'].'</h4>'.$info['ilabel'].'</a>');
						}
					}
					$this->output("\t\t".'</div><br>');*/

					/*if (isset($item['infors'])) {
						foreach ($item['infors'] as $info) {
							$this->output("\t\t".'<a class="btn btn-app" style="height: 100px; margin-right:10px;">');
							if (isset($info['inew'])) $this->output("\t\t".'<span class="badge bg-green">'.$info['inew'].'</span>');
							$this->output("\t\t".'<i class="fa fa-'.$info['ibadge'].'"></i><h4>'.$info['icount'].'</h4>'.$info['ilabel'].'</a>');
						}
					}*/
					$this->output("\t\t".'</li>');
				}
				$this->output("\t\t".'</ul>');
			}
			
            $this->output("\t\t".'</div>');
		}
	}

	public function alert_view($type, $message, $title = null)
	{
		$this->output("\t\t".'<div class="alert alert-'.$type.' alert-dismissible">');
		$this->output("\t\t".'<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
		if (isset($title)) $this->output("\t\t".'<h4><i class="icon fa fa-ban"></i>'.$title.'</h4>');
		$this->output($message, '</div>');
	}

	public function callout_view($type, $message, $title = null)
	{
		$this->output("\t\t".'<div class="callout callout-'.$type.'">');
		if (isset($title)) $this->output("\t\t".'<h4>'.$title.'</h4>');
		$this->output("\t\t".'<p>'.$message.'</p>', '</div>');
	}

	public function item_data($data)
	{
		if (isset($data['text'])) $this->output($data['text']);
		if (isset($data['sub-data'])) {
			$k = 0;
			foreach ($data['sub-data'] as $sd => $item)
			{
				switch ($item[0])
				{
					case 'label':
						$this->item_label($sd, $item[1]);
						break;
						
					case 'litem':
						$this->output("\t\t".'<li'.($k==0 ? ' class="active"' : '').'>');
						$this->item_list($sd, $item[1], isset($item[2]) ? $item[2] : '');
						$this->output("\t\t".'</li>');
						break;
					
					default:
						break;
				}
				$k++;
			}
		}
	}

	public function tline_data($data)
	{
		if (isset($data['text'])) $this->output($data['text']);
		if (isset($data['itag'])) {
			$this->output("\t\t".'<i class="fa fa-'.$data['itag'][0].' bg-'.
			(isset($data['itag'][1]) ? $data['itag'][1] : 'primary').'"></i>');
		}
		if (isset($data['sub-data'])) {
			$this->output("\t\t".'<div class="timeline-item">');
			$this->output("\t\t".'<span class="time"><i class="fa fa-clock-o"></i> '.$data['sub-data']['time'].'</span>');
			$this->output("\t\t".'<h3 class="timeline-header">'.$data['sub-data']['header'].'</h3>');
			$this->output("\t\t".'<div class="timeline-body">'.$data['sub-data']['body'].'</div>');
			$this->output("\t\t".'<div class="timeline-footer">',
				'<a class="btn btn-primary btn-xs">Read more</a>',
				'<a class="btn btn-danger btn-xs">Delete</a>',
				'</div>');
			$this->output("\t\t".'</div>');
		}
	}

	public function item_label($item, $class)
	{
		$this->output("\t\t".'<span  class="label label-'.$class.'">'.$item.'</span>');
	}
	
	public function item_list($item, $class, $extras = '')
	{
		$this->output("\t\t".'<a href="#"><i class="'.$class.'"></i> '.$item);
		if (isset($extras)) 
			$this->output("\t\t".' <span class="label label-primary pull-right">'.$extras.'</span>');
		$this->output("\t\t".'</a>');
	}
	
	public function navlist($navigation)
	{		
		$this->output("\t\t".'<table class="table table-bordered table-striped" style="margin:0px;">');

		if (isset($navigation['headers'])) {
			$tdw = 80 / (count($navigation['headers']) - 3);
		}
		else $tdw = 40;

		if (isset($navigation['headers'])) {
			$this->output("\t\t".'<thead>', '<tr>');
			foreach ($navigation['headers'] as $header) {
				switch ($header)
				{
					case '*': case 'x':
						$this->output("\t\t".'<th valign="top" style="width:50px;"></th>');
						break;
					case '#':
						$this->output("\t\t".'<th valign="top" style="width:50px;">'.$header.'</th>');
						break;
					default:
						$this->output("\t\t".'<th valign="top">'.$header.'</th>');
						break;
				}
			}
			$this->output("\t\t".'</tr>', '</thead>');
		}
		$this->output("\t\t".'</table>');
		
		foreach ( $navigation['items'] as $key => $item ) {
			$this->output("\t\t".'<div class="box collapsed-box accordian" style="padding:0px;">');
			$this->output("\t\t".'<div class="box-header with-border" style="padding:0px;">');
			
			$this->output("\t\t".'<table class="table table-bordered table-striped" style="margin:0px;">');
			$this->output("\t\t".'<tbody>', '<tr>');
			foreach ($item['fields'] as $ri => $row) {
				switch ($ri)
				{
					case '*':
						if (isset($item['sub'])) {
							$this->output("\t\t".'<td valign="top" style="width:50px;">');
							$this->output("\t\t".'<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i> </button>');
							$this->output("\t\t".'</td>');
						}
						else $this->output("\t\t".'<td valign="top" style="width:50px;"></td>');
						break;
					case '#': case 'id':
						$this->output("\t\t".'<td valign="top" style="width:50px;">'.$row['data'].'</td>');
						break;
					case 'x':
						$this->output("\t\t".'<td valign="top" style="width:50px;"></td>');
						break;
					default:
						$this->output("\t\t".'<td valign="top" style="width:'.$tdw.'%;">'.$row['data'].'</td>');
						break;
				}
			}
			$this->output("\t\t".'</tr>', '</tbody>', '</table>');
			$this->output("\t\t".'</div>');

			if (isset($item['sub'])) {
				$this->output("\t\t".'<div class="box-body">');
				foreach ( $item['sub'] as $k => $sub ) {
					$this->output("\t\t".'<div class="box collapsed-box accordian" style="padding:0px;">');
					$this->output("\t\t".'<div class="box-header with-border" style="padding:0px;">');
					
					$this->output("\t\t".'<table class="table table-bordered table-striped" style="margin:0px;">');
					$this->output("\t\t".'<tbody>', '<tr>');
					foreach ($sub['fields'] as $rx => $rw) {
						switch ($rx)
						{
							case '*':
								if (isset($rw['sub'])) {
									$this->output("\t\t".'<td valign="top" style="width:50px;">');
									$this->output("\t\t".'<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i> </button>');
									$this->output("\t\t".'</td>');
								}
								else $this->output("\t\t".'<td valign="top" style="width:50px;"></td>');
								break;
							case '#': case 'id':
								$this->output("\t\t".'<td valign="top" style="width:50px;">'.$rw['data'].'</td>');
								break;
							case 'x':
								$this->output("\t\t".'<td valign="top" style="width:50px;"></td>');
								break;
							default:
								$this->output("\t\t".'<td valign="top" style="width:'.$tdw.'%;">'.$rw['data'].'</td>');
								break;
						}
					}
					$this->output("\t\t".'</tr>', '</tbody>', '</table>');
					$this->output("\t\t".'</div>');
				}
				$this->output("\t\t".'</div>', '</div>');
			}
			$this->output("\t\t".'</div>');
		}
		$this->output("\t\t".'<table class="table table-bordered table-striped">');
		if (isset($navigation['headers'])) {
			$this->output("\t\t".'<thead>', '<tr>');
			foreach ($navigation['headers'] as $header) {
				switch ($header)
				{
					case '*': case 'x':
						$this->output("\t\t".'<th valign="top" style="width:50px;"></th>');
						break;
					case '#':
						$this->output("\t\t".'<th valign="top" style="width:50px;">'.$header.'</th>');
						break;
					default:
						$this->output("\t\t".'<th valign="top">'.$header.'</th>');
						break;
				}
			}
			$this->output("\t\t".'</tr>', '</thead>');
		}
		$this->output("\t\t".'</table>');
	}

	public function table($table)
	{
		if (!empty($table)) {
			if (!isset($table['inline'])) {
				$this->output('<div class="box box-info">');
				if (isset($table['title'])) $this->box_title($table);
			}
			
			$this->output('<div class="box-body">');
            $this->output('<table id="'.$table['id'].'" class="table table-bordered">');
			
			if (isset($table['headers'])) {
				$this->output('<thead>', '<tr>');
				foreach ($table['headers'] as $header) {
					switch ($header)
					{
						case '*': case 'x':
							$this->output('<th valign="top" style="width:50px;"></th>');
							break;
						case '#':
							$this->output('<th valign="top" style="width:50px;">'.$header.'</th>');
							break;
						default:
							$this->output('<th valign="top">'.$header.'</th>');
							break;
					}
				}
				$this->output('</tr>', '</thead>');
			}
			
			if (isset($table['rows'])) {
				$this->output('<tbody>');
				foreach ($table['rows'] as $ra => $row) 
				{
					$this->table_row($ra, $row);
					$this->table_sub_row($ra, $row);
				}
				$this->output('</tbody>');
			}
			
			if (isset($table['headers'])) {
				$this->output('<tfoot>', '<tr>');
				foreach ($table['headers'] as $header) {
					switch ($header)
					{
						case '*': case 'x':
							$this->output('<th valign="top" style="width:50px;"></th>');
							break;
						case '#':
							$this->output('<th valign="top" style="width:50px;">'.$header.'</th>');
							break;
						default:
							$this->output('<th valign="top">'.$header.'</th>');
							break;
					}
				}				
				$this->output('</tr>', '</tfoot>');
			}
			
			$this->output('</table>', '</div>');
			if (!isset($table['inline'])) $this->output('</div>');
		}
	}

	public function table_row($ra, $row)
	{
		$this->output('<tr'.
		(isset($row['tags']) ? ' '.$row['tags'] : ''). 
		(isset($row['title']) ? ' title="'.$row['title'].'"' : '').' class="row-item">');
		foreach ($row['fields'] as $rb => $rd) 
		{
			$this->output('<td valign="top"'.($rb == '*' ? ' style="width: 150px;"' : '').'>');
			if (isset($row['sub']) && $rb == '*')
				$this->output('<label><input id="parent_'.$ra.'" type="checkbox" value="1"> ' . $rd['data'] . '</label>');
			else $this->output($rd['data']);
			$this->output('</td>');
		}
		$this->output('</tr>');
	}

	public function table_sub_row($ra, $row)
	{
		if (isset($row['sub'])) {
			foreach ( $row['sub'] as $rk => $subrow ) 
			{
				$this->output('<tr id="child_'.$ra.'_'.$rk.'" class="sub-item-first">');
				foreach ($subrow['fields'] as $rq => $rw) {
					if ($rq == '#') $this->output('<td valign="top" style="text-align:right;">'.$rw['data'].'</td>');
					else $this->output('<td valign="top">'.$rw['data'].'</td>');
				}
				$this->output('</tr>');
			};
		}
	}

	public function form_spacer($form, $columns)
	{
		$this->output(
			'<tr>',
			'<td colspan="' . $columns . '" class="as-form-' . $form['style'] . '-spacer">',
			'&nbsp;',
			'</td>',
			'</tr>'
		);
	}

	public function form_body($form)
	{
		$columns = $this->form_columns($form);
		$this->form_ok($form, $columns);

		$this->form_fields($form, $columns);
		if (isset($form['table'])) $this->table($form['table']);
		if (isset($form['navlist'])) $this->navlist($form['navlist']);
		else if (isset($form['dash'])) $this->dashlist_view($form['dash']);
		$this->form_buttons($form, $columns);

		$this->form_hidden($form);

	}

	public function form_ok($form, $columns)
	{
		if (!empty($form['ok'])) {
			$this->output(
				'<div class="alert alert-success alert-dismissible">',
				'<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>',
				$form['ok'],
				'</div>'
			);
		}
	}

	/**
	 * Reorder the fields of $form according to the $keys array which contains the field keys in their new order. Call
	 * before any fields are output. See the docs for as_array_reorder() in util/sort.php for the other parameters.
	 * @param $form
	 * @param $keys
	 * @param mixed $beforekey
	 * @param bool $reorderrelative
	 */
	public function form_reorder_fields(&$form, $keys, $beforekey = null, $reorderrelative = true)
	{
		require_once AS_INCLUDE_DIR . 'util/sort.php';

		if (is_array($form['fields']))
			as_array_reorder($form['fields'], $keys, $beforekey, $reorderrelative);
	}

	public function form_fields($form, $columns)
	{
		if (!empty($form['fields'])) {			
			$this->output("\t\t".'<div class="box-body">');
			foreach ($form['fields'] as $key => $field) {
				$this->output("\t\t".'<div class="form-group">');
				if ($columns == 1) 
				{
					$prefixed = (@$field['type'] == 'checkbox') && !empty($field['label']);
					$suffixed = (@$field['type'] == 'select' || @$field['type'] == 'number') && !empty($field['label']);
					
					$this->output("\t\t".'<div class="col-sm-12">');
					
					if ($prefixed) $this->output("\t\t".'<label>');
					else if (!empty($field['label']))
						$this->output("\t\t".'<label for="'.$key.'">'.$field['label'].'</label>');
					
					$this->form_field($field);

					if ($prefixed) {
						$this->output(@$field['label']);						
						$this->output("\t\t".'</label>');
					}
					$this->output("\t\t".'</div>');
				}
				else
				{
					if (!empty($field['label']))
						$this->output("\t\t".'<label for="'.$key.'" class="col-sm-4 control-label">'.$field['label'].'</label>');
					$this->output("\t\t".'<div class="col-sm-8">');
					$this->form_field($field);                  
					$this->output("\t\t".'</div>');
				}
				$this->output("\t\t".'</div>');
			}
			$this->output("\t\t".'</div>');
		}
	}

	public function form_label($field, $style, $columns, $prefixed, $suffixed, $colspan)
	{
		$extratags = '';

		if ($columns > 1 && (@$field['type'] == 'select-radio' || @$field['rows'] > 1))
			$extratags .= ' style="vertical-align:top;"';

		if (isset($colspan))
			$extratags .= ' colspan="' . $colspan . '"';

		$this->output("\t\t".'<td class="as-form-' . $style . '-label"' . $extratags . '>');

		if ($prefixed) {
			$this->output("\t\t".'<label>');
			$this->form_field($field, $style);
		}

		$this->output(@$field['label']);

		if ($prefixed)
			$this->output("\t\t".'</label>');

		if ($suffixed) {
			$this->output("\t\t".'&nbsp;');
			$this->form_field($field, $style);
		}

		$this->output("\t\t".'</td>');
	}

	public function form_data($field, $style, $columns, $showfield, $colspan)
	{
		if ($showfield || (!empty($field['error'])) || (!empty($field['note']))) {
			$this->output(
				'<td class="as-form-' . $style . '-data"' . (isset($colspan) ? (' colspan="' . $colspan . '"') : '') . '>'
			);

			if ($showfield)
				$this->form_field($field, $style);

			if (!empty($field['error'])) {
				if (@$field['note_force'])
					$this->form_note($field, $style, $columns);

				$this->form_error($field, $style, $columns);
			} elseif (!empty($field['note']))
				$this->form_note($field, $style, $columns);

			$this->output("\t\t".'</td>');
		}
	}

	public function form_field($field, $style = null)
	{
		switch (@$field['type']) {
			case 'checkbox':
				$this->form_checkbox($field, $style);
				break;

			case 'static':
				$this->form_static($field, $style);
				break;

			case 'password':
				$this->form_password($field, $style);
				break;

			case 'number':
				$this->form_number($field, $style);
				break;

			case 'phone':
				$this->form_phone($field, $style);
				break;

			case 'email':
				$this->form_email($field, $style);
				break;

			case 'file':
				$this->form_file($field, $style);
				break;

			case 'select':
				$this->form_select($field, $style);
				break;

			case 'select-radio':
				$this->form_select_radio($field, $style);
				break;

			case 'radio':
				$this->form_radio($field, $style);
				break;

			case 'image':
				$this->form_image($field, $style);
				break;

			case 'custom':
				$this->output_raw(@$field['html']);
				break;

			default:
				if (@$field['type'] == 'textarea' || @$field['rows'] > 1)
					$this->form_text_multi_row($field, $style);
				else
					$this->form_text_single_row($field, $style);
				break;
		}
		//$this->form_suffix($field, $style);
	}

	public function form_field_rows($form, $columns, $field)
	{
		$style = $form['style'];

		if (isset($field['style'])) { // field has different style to most of form
			$style = $field['style'];
			$colspan = $columns;
			$columns = ($style == 'wide') ? 2 : 1;
		} else
			$colspan = null;

		$prefixed = (@$field['type'] == 'checkbox') && ($columns == 1) && !empty($field['label']);
		$suffixed = (@$field['type'] == 'select' || @$field['type'] == 'number') && $columns == 1 && !empty($field['label']) && !@$field['loose'];
		$skipdata = @$field['tight'];
		$tworows = ($columns == 1) && (!empty($field['label'])) && (!$skipdata) &&
			((!($prefixed || $suffixed)) || (!empty($field['error'])) || (!empty($field['note'])));

		if (isset($field['id'])) {
			if ($columns == 1)
				$this->output("\t\t".'<tbody id="' . $field['id'] . '">', '<tr>');
			else
				$this->output("\t\t".'<tr id="' . $field['id'] . '">');
		} else
			$this->output("\t\t".'<tr>');

		if ($columns > 1 || !empty($field['label']))
			$this->form_label($field, $style, $columns, $prefixed, $suffixed, $colspan);

		if ($tworows) {
			$this->output(
				'</tr>',
				'<tr>'
			);
		}

		if (!$skipdata)
			$this->form_data($field, $style, $columns, !($prefixed || $suffixed), $colspan);

		$this->output("\t\t".'</tr>');

		if ($columns == 1 && isset($field['id']))
			$this->output("\t\t".'</tbody>');
	}

	/**
	 * Reorder the buttons of $form according to the $keys array which contains the button keys in their new order. Call
	 * before any buttons are output. See the docs for as_array_reorder() in util/sort.php for the other parameters.
	 * @param $form
	 * @param $keys
	 * @param mixed $beforekey
	 * @param bool $reorderrelative
	 */
	public function form_reorder_buttons(&$form, $keys, $beforekey = null, $reorderrelative = true)
	{
		require_once AS_INCLUDE_DIR . 'util/sort.php';

		if (is_array($form['buttons']))
			as_array_reorder($form['buttons'], $keys, $beforekey, $reorderrelative);
	}

	public function form_buttons($form, $columns)
	{
		if (!empty($form['buttons'])) {			
			$style = @$form['style'];
			$this->output("\t\t".'<div class="box-footer">');
			
			foreach ($form['buttons'] as $key => $button) {
				$this->form_button_data($button, $key, $style);
				$this->form_button_note($button, $style);
			}

			$this->output("\t\t".'</div>');
		}
	}

	public function form_button_data($button, $key, $style)
	{
		if (isset($button['link'])){
			$this->output("\t\t".'<hr>');
			if ($button['link'] == '#') $this->output("\t\t".'<h4>' . @$button['label'] . '</h4>');
			else $this->output("\t\t".'<a href="' . @$button['link'] . '">' . @$button['label'] . '</a><br><br>');
		}			
		elseif (isset($button['split']))
			$this->output("\t\t".'<hr> <input' . rtrim(' ' . @$button['tags']) . ' value="' . @$button['label'] . '" title="' . @$button['popup'] . '" type="submit" class="btn btn-info"/> ');  
		else
			$this->output("\t\t".' <input' . rtrim(' ' . @$button['tags']) . ' value="' . @$button['label'] . '" title="' . @$button['popup'] . '" type="submit" class="btn btn-info"/> ');
	}

	public function form_button_note($button, $style)
	{
		if (!empty($button['note'])) {
			$this->output(
				'<span class="as-form-' . $style . '-note">',
				$button['note'],
				'</span>',
				'<br/>'
			);
		}
	}

	public function form_button_spacer($style)
	{
		$this->output("\t\t".'<span class="as-form-' . $style . '-buttons-spacer">&nbsp;</span>');
	}

	public function form_hidden($form)
	{
		$this->form_hidden_elements(@$form['hidden']);
	}

	public function form_hidden_elements($hidden)
	{
		if (empty($hidden))
			return;

		foreach ($hidden as $name => $value) {
			if (is_array($value)) {
				// new method of outputting tags
				$this->output("\t\t".'<input ' . @$value['tags'] . ' type="hidden" value="' . @$value['value'] . '"/>');
			} else {
				// old method
				$this->output("\t\t".'<input name="' . $name . '" type="hidden" value="' . $value . '"/>');
			}
		}
	}

	public function form_prefix($field, $style)
	{
		if (!empty($field['prefix']))
			$this->output("\t\t".'<span class="as-form-' . $style . '-prefix">' . $field['prefix'] . '</span>');
	}

	public function form_suffix($field, $style)
	{
		if (!empty($field['suffix']))
			$this->output("\t\t".'<span class="as-form-' . $style . '-suffix">' . $field['suffix'] . '</span>');
	}

	public function form_checkbox($field, $style)
	{
		$this->output("\t\t".'<input ' . @$field['tags'] . ' type="checkbox" value="1"' . (@$field['value'] ? ' checked' : '') . '/>');
	}

	public function form_static($field, $style)
	{
		$this->output("\t\t".'<span>' . @$field['value'] . '</span>');
	}

	public function form_password($field, $style)
	{
		$this->output("\t\t".'<input ' . @$field['tags'] . ' type="password" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_email($field, $style)
	{
		$this->output("\t\t".'<input ' . @$field['tags'] . ' type="email" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_phone($field, $style)
	{
		$this->output("\t\t".'<input ' . @$field['tags'] . ' type="phone" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_number($field, $style)
	{
		$this->output("\t\t".'<input ' . @$field['tags'] . ' type="text" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_file($field, $style)
	{
		$this->output("\t\t".'<input ' . @$field['tags'] . ' type="file" class="form-control"/>');
	}

	/**
	 * Output a <select> element. The $field array may contain the following keys:
	 *   options: (required) a key-value array containing all the options in the select.
	 *   tags: any attributes to be added to the select.
	 *   value: the selected value from the 'options' parameter.
	 *   match_by: whether to match the 'value' (default) or 'key' of each option to determine if it is to be selected.
	 * @param $field
	 * @param $style
	 */
	public function form_select($field, $style)
	{
		$this->output("\t\t".'<select ' . (isset($field['tags']) ? $field['tags'] : '') . ' class="form-control">');

		// Only match by key if it is explicitly specified. Otherwise, for backwards compatibility, match by value
		$matchbykey = isset($field['match_by']) && $field['match_by'] === 'key';

		foreach ($field['options'] as $key => $value) {
			$selected = isset($field['value']) && (
				($matchbykey && $key === $field['value']) ||
				(!$matchbykey && $value === $field['value'])
			);
			$this->output("\t\t".'<option value="' . $key . '"' . ($selected ? ' selected' : '') . '>' . $value . '</option>');
		}

		$this->output("\t\t".'</select>');
	}

	public function form_select_radio($field, $style)
	{
		$radios = 0;

		foreach ($field['options'] as $tag => $value) {
			//if ($radios++)
				$this->output("\t\t".'<br/>');

			$this->output("\t\t".'<input ' . @$field['tags'] . ' type="radio" value="' . $tag . '"' . (($value == @$field['value']) ? ' checked' : '') . '/> ' . $value);
		}
	}

	public function form_radio($field, $style)
	{
		$radios = 0;

		foreach ($field['options'] as $tag => $value) {
			if ($radios++) $this->output("\t\t".' ');

			$this->output("\t\t".'&nbsp;&nbsp;<label> <input ' . @$field['tags'] . ' type="radio" value="' . $tag . '"' . (($value == @$field['value']) ? ' checked' : '') . ' /> ' . $value . '</label>');
		}
	}

	public function form_image($field, $style)
	{
		$this->output("\t\t".'<div class="form-control">' . @$field['html'] . '</div>');
	}

	public function form_text_single_row($field, $style)
	{
		$this->output("\t\t".'<input ' . @$field['tags'] . ' type="text" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_text_multi_row($field, $style)
	{
		$this->output("\t\t".'<textarea ' . @$field['tags'] . ' rows="' . (int)$field['rows'] . '" cols="40" class="form-control">' . @$field['value'] . '</textarea>');
	}

	public function form_error($field, $style, $columns)
	{
		$tag = ($columns > 1) ? 'span' : 'div';

		$this->output("\t\t".'<' . $tag . ' class="as-form-' . $style . '-error">' . $field['error'] . '</' . $tag . '>');
	}

	public function form_note($field, $style, $columns)
	{
		$tag = ($columns > 1) ? 'span' : 'div';

		$this->output("\t\t".'<' . $tag . ' class="as-form-' . $style . '-note">' . @$field['note'] . '</' . $tag . '>');
	}

	public function ranking($ranking)
	{
		$this->output("\t\t".'<div class="box box-info">');
			
		if (isset($ranking['title'])) $this->box_title($ranking);
		
		$this->output("\t\t".'<div class="box-body no-padding">');
		$this->output("\t\t".'<ul class="users-list clearfix">');
		
		foreach ($ranking['items'] as $item) {
			$this->output("\t\t".'<li>');
			if (isset($item['avatar']))
				$this->output("\t\t".'<a href="#">'.$item['avatar'].'</a>');
			$this->output("\t\t".'<a class="users-list-name" href="#">'.$item['label']);
			if (isset($item['lasttype'])) $this->output("\t\t".' ('.$item['lasttype'].')');
			$this->output("\t\t".'</a>');
			if (isset($item['score']))
				$this->output("\t\t".'<span class="users-list-date">'.$item['score'].'</span>');
			$this->output("\t\t".'</li>');
		}
		
		$this->output("\t\t".'</ul>');
		$this->output("\t\t".'</div>');

		//$this->part_footer($ranking);
		
		$this->output("\t\t".'</div>');
	}

	public function ranking_item($item, $class, $spacer = false) // $spacer is deprecated
	{
		if (!$this->ranking_block_layout) {
			// old table layout
			$this->ranking_table_item($item, $class, $spacer);
			return;
		}

		if (isset($item['count']))
			$this->ranking_count($item, $class);

		if (isset($item['avatar']))
			$this->avatar($item, $class);

		$this->ranking_label($item, $class);

		if (isset($item['score']))
			$this->ranking_score($item, $class);
	}

	public function ranking_cell($content, $class)
	{
		$tag = $this->ranking_block_layout ? 'span' : 'td';
		$this->output("\t\t".'<' . $tag . ' class="' . $class . '">' . $content . '</' . $tag . '>');
	}

	public function ranking_count($item, $class)
	{
		$this->ranking_cell($item['count'] . ' &#215;', $class . '-count');
	}

	public function ranking_label($item, $class)
	{
		$this->ranking_cell($item['label'], $class . '-label');
	}

	public function ranking_score($item, $class)
	{
		$this->ranking_cell($item['score'], $class . '-score');
	}

	/**
	 * @deprecated Table-based layout of users/tags is deprecated from 1.7 onwards and may be
	 * removed in a future version. Themes can switch to the new layout by setting the user
	 * variable $ranking_block_layout to false.
	 * @param $ranking
	 * @param $class
	 */
	public function ranking_table($ranking, $class)
	{
		$rows = min($ranking['rows'], count($ranking['items']));

		if ($rows > 0) {
			$this->output("\t\t".'<table class="' . $class . '-table">');
			$columns = ceil(count($ranking['items']) / $rows);

			for ($row = 0; $row < $rows; $row++) {
				$this->set_context('ranking_row', $row);
				$this->output("\t\t".'<tr>');

				for ($column = 0; $column < $columns; $column++) {
					$this->set_context('ranking_column', $column);
					$this->ranking_table_item(@$ranking['items'][$column * $rows + $row], $class, $column > 0);
				}

				$this->clear_context('ranking_column');
				$this->output("\t\t".'</tr>');
			}
			$this->clear_context('ranking_row');
			$this->output("\t\t".'</table>');
		}
	}

	/**
	 * @deprecated See ranking_table above.
	 * @param $item
	 * @param $class
	 * @param $spacer
	 */
	public function ranking_table_item($item, $class, $spacer)
	{
		if ($spacer)
			$this->ranking_spacer($class);

		if (empty($item)) {
			$this->ranking_spacer($class);
			$this->ranking_spacer($class);

		} else {
			if (isset($item['count']))
				$this->ranking_count($item, $class);

			if (isset($item['avatar']))
				$item['label'] = $item['avatar'] . ' ' . $item['label'];

			$this->ranking_label($item, $class);

			if (isset($item['score']))
				$this->ranking_score($item, $class);
		}
	}

	/**
	 * @deprecated See ranking_table above.
	 * @param $class
	 */
	public function ranking_spacer($class)
	{
		$this->output("\t\t".'<td class="' . $class . '-spacer">&nbsp;</td>');
	}

	public function gridlayout($grids)
	{
		$this->part_title($grids);
		//$this->part_subtitle($grids);
		
		$this->output("\t\t".'<div class="app_view">');
		
		foreach ($grids['items'] as $appview ){			
			$this->output("\t\t".'<a href="' . $appview['url'] . '">');
			$this->output("\t\t".'<div class="app_item">',
				'<div class="app_img" style="background: url(' . $appview['img']. '); background-size: cover; background-repeat: no-repeat; background-position: center center;">', '</div>');
			$this->output("\t\t".'<span class="app_title">'.$appview['name'].'</span>', '</div>', '</a>');
		}
		
		$this->output("\t\t".'</div>');		
	}
	
	public function listing($listing)
	{
		$this->part_title($listing);
		//$this->part_subtitle($listing);
		if (!isset($listing['type'])) $listing['type'] = 'items';
		$select = isset($listing['select']) ? $listing['select'] : null;
		$links = isset($listing['links']) ? $listing['links'] : null;
		$extras = isset($listing['extras']) ? $listing['extras'] : null;
		$checker = isset($listing['checker']) ? $listing['checker'] : null;
		$class = 'as-listing-' . $listing['type'];
		
		$this->listing_top(  $select, $links, $extras );		
		if (isset($listing['infor'])) $this->listing_infor($listing['infor']);
		
		$this->output("\t\t".'<table id="as-table">');
		$this->listing_headers($listing['headers'], $checker);
		$this->output("\t\t".'<tbody>');
		foreach ($listing['items'] as $item) {
			$this->output("\t\t".'<tr'.(isset($item['onclick']) ? $item['onclick'] : '' ).'>');
			foreach ($item['fields'] as $field) $this->output("\t\t".'<td valign="top">'.$field['data'].'</td>');
			$this->output("\t\t".'</tr>');
		}
		$this->output("\t\t".'</tbody>');
		if (isset($listing['bottom'])) {
			$this->output("\t\t".'<thead>');
			$this->output("\t\t".'<tr>');
			foreach ($listing['bottom'] as $bottom) $this->output("\t\t".'<th valign="top">'.$bottom.'</th>');
			$this->output("\t\t".'</tr>');
			$this->output("\t\t".'</thead>');			
		}
		if (isset($listing['bottomi'])) {
			$this->output("\t\t".'<thead>');
			$this->output("\t\t".'<tr>');
			foreach ($listing['bottomi'] as $bottomi) $this->output("\t\t".'<th valign="top">'.$bottomi.'</th>');
			$this->output("\t\t".'</tr>');
			$this->output("\t\t".'</thead>');			
		}
		$this->output("\t\t".'</table>', '</form>');
		$this->part_footer($listing);
	}
	
	public function listing_top( $select = null, $links = null, $extras = null )
	{
		$this->output("\t\t".'<form name="listing" action="'.as_self_html().'" method="post">');
		$this->output("\t\t".'<div id="as-table-tools">');
		if (isset($select)) {
			$this->output("\t\t".'<select id="as-action" class="as-form-select" name="as-action">');
			$this->output("\t\t".'<option selected="" value="none">'.as_lang('options/select').'</option>');
			foreach ( $select as $slct ) 
				$this->output("\t\t".'<option value="'.$slct['value'].'">'.$slct['label'].'</option>');
			$this->output("\t\t".'</select>');
			$this->output("\t\t".'<input id="do_action" class="as-form-tall-button as-form-tall-button-save btn btn-default" type="submit" name="do_action" value="'.as_lang('options/apply').'" />');
		}
		if (isset($links)) {
			$this->output("\t\t".'<div style="float:right">');
			foreach ( $links as $link ) 
				$this->output("\t\t".'<a class="as-form-tall-button as-form-tall-button-cancel btn btn-default" href=' . as_path_html($link['url']) . '>'.$link['label'].'</a>');
			$this->output("\t\t".'</div>');
		}
		if (isset($extras)) $this->output($extras);
		$this->output("\t\t".'</div>');
	}
	
	public function listing_infor($items)
	{
		$this->output("\t\t".'<table id="if-table">');	
		$this->output("\t\t".'<tr>');
		$this->output("\t\t".'<td>'.$items[0].'</td>');
		$this->output("\t\t".'<td valign="bottom" style="text-align:right;">'.$items[1].'</td>');
		$this->output("\t\t".'</tr>');	
		$this->output("\t\t".'</table>');	
	}
	
	public function listing_headers($headers, $checker)
	{
		$this->output("\t\t".'<thead>');
		$this->output("\t\t".'<tr class="as-table-top">');
		if (isset($checker)) { 
			$this->output("\t\t".'<th valign="top"><label><input id="check-button" class="chk-item" type="checkbox">');
			$this->output("\t\t".'<input id="uncheck-button" class="chk-item" style="display: none;" type="checkbox">'.$checker.'</label></th>');
		} 
		else $this->output("\t\t".'<th valign="top"></th>');
		foreach ($headers as $thlabel) $this->output("\t\t".'<th valign="top">'.$thlabel.'</th>');
		$this->output("\t\t".'</tr>');
		$this->output("\t\t".'</thead>');
	}
		
	public function listing_th($headers)
	{
		foreach ($headers as $thlabel) $this->output("\t\t".'<th valign="top">'.$thlabel.'</th>');
	}
	
	public function table_field_rows($timetable, $columns, $field, $tstyle)
	{
		$style = $timetable['style'];
		if (isset($field['style'])) { // field has different style to most of timetable
			$style = $field['style'];
			$colspan = $columns;
			$columns = ($style == 'wide') ? 2 : 1;
		} else $colspan = null;

		$prefixed = (@$field['type'] == 'checkbox') && ($columns == 1) && !empty($field['label']);
		$suffixed = (@$field['type'] == 'select' || @$field['type'] == 'number') && $columns == 1 && !empty($field['label']) && !@$field['loose'];
		$skipdata = @$field['tight'];
		$tworows = ($columns == 1) && (!empty($field['label'])) && (!$skipdata) &&
			((!($prefixed || $suffixed)) || (!empty($field['error'])) || (!empty($field['note'])));

		if (isset($field['id'])) {
			if ($columns == 1) {
				$this->output("\t\t".'<tbody id="' . $field['id'] . '">');
				$this->output($tstyle == 'long' ? '' : '<tr>');
			}
			else $this->output($tstyle == 'long' ? '' : '<tr id="' . $field['id'] . '">');
		} else $this->output($tstyle == 'long' ? '' : '<tr>');

		if (!empty($field['label']))
			$this->form_label($field, $style, $columns, $prefixed, $suffixed, $colspan);

		if ($tworows) {
			$this->output(
				'</tr>',
				'<tr>'
			);
		}

		if (!$skipdata)
			$this->form_data($field, $style, $columns, !($prefixed || $suffixed), $colspan, $tstyle);

		$this->output($tstyle == 'long' ? '' : '</tr>');

		if ($columns == 1 && isset($field['id']))
			$this->output("\t\t".'</tbody>');
	}

	public function table_viewer($size, $visible = true)
	{	
		$this->output("\t\t".'<span id="tableviewer"></span>');
		$this->output("\t\t".'<div id="lazydata" style="' . ( $visible ? 'relative' : 'none' ). '"><center>');
		$this->output("\t\t".'<table><tr><td>');
		if ($size == 'small') {
			$this->output("\t\t".'<img src="'.as_path_to_root().'as-media/cyclic.gif" />');
			$this->output("\t\t".'</td><td>');
			$this->output("\t\t".'<img src="'.as_path_to_root().'as-media/cyclic.gif" />');
			$this->output("\t\t".'</td><td>');
			$this->output("\t\t".'<img src="'.as_path_to_root().'as-media/cyclic.gif" />');
		} else {
			$this->output("\t\t".'<img src="'.as_path_to_root().'as-media/loading-gears1.gif" />');
			$this->output("\t\t".'</td><td>');
			$this->output("\t\t".'<img src="'.as_path_to_root().'as-media/loading-gears2.gif" />');
		}
		$this->output("\t\t".'</td></tr></table>');
		$this->output("\t\t".'<h1>Just a minute ... Working ... </h1>');
		$this->output("\t\t".'</center></div>');
	}
	

	public function message_list_and_form($list)
	{
		if (!empty($list)) {
			$this->part_title($list);

			$this->error(@$list['error']);

			if (!empty($list['form'])) {
				$this->output("\t\t".'<form ' . $list['form']['tags'] . '>');
				unset($list['form']['tags']); // we already output the tags before the messages
				$this->message_list_form($list);
			}

			$this->message_list($list);

			if (!empty($list['form'])) {
				$this->output("\t\t".'</form>');
			}
		}
	}

	public function message_list_form($list)
	{
		if (!empty($list['form'])) {
			$this->output("\t\t".'<div class="as-message-list-form">');
			$this->form($list['form']);
			$this->output("\t\t".'</div>');
		}
	}

	public function message_list($list)
	{
		if (isset($list['messages'])) {
			$this->output("\t\t".'<div class="as-message-list" ' . @$list['tags'] . '>');

			foreach ($list['messages'] as $message) {
				$this->message_item($message);
			}

			$this->output("\t\t".'</div> <!-- END as-message-list -->', '');
		}
	}

	public function message_item($message)
	{
		$this->output("\t\t".'<div class="as-message-item" ' . @$message['tags'] . '>');
		$this->message_content($message);
		$this->post_avatar_meta($message, 'as-message');
		$this->message_buttons($message);
		$this->output("\t\t".'</div> <!-- END as-message-item -->', '');
	}

	public function message_content($message)
	{
		if (!empty($message['content'])) {
			$this->output("\t\t".'<div class="as-message-content">');
			$this->output_raw($message['content']);
			$this->output("\t\t".'</div>');
		}
	}

	public function message_buttons($item)
	{
		if (!empty($item['form'])) {
			$this->output("\t\t".'<div class="as-message-buttons">');
			$this->form($item['form']);
			$this->output("\t\t".'</div>');
		}
	}

	public function list_like_disabled($items)
	{
		$disabled = false;

		if (count($items)) {
			$disabled = true;

			foreach ($items as $item) {
				if (@$item['like_on_page'] != 'disabled')
					$disabled = false;
			}
		}

		return $disabled;
	}

	public function p_list_and_form($p_list)
	{
		if (empty($p_list))
			return;

		$this->part_title($p_list);

		if (!empty($p_list['form']))
			$this->output("\t\t".'<form ' . $p_list['form']['tags'] . '>');

		$this->p_list($p_list);

		if (!empty($p_list['form'])) {
			unset($p_list['form']['tags']); // we already output the tags before the qs
			$this->p_list_form($p_list);
			$this->output("\t\t".'</form>');
		}

		$this->part_footer($p_list);
	}

	public function p_list_form($p_list)
	{
		if (!empty($p_list['form'])) {
			$this->output("\t\t".'<div class="as-p-list-form">');
			$this->form($p_list['form']);
			$this->output("\t\t".'</div>');
		}
	}

	public function p_list($p_list)
	{
		if (isset($p_list['ps'])) {
			$this->output("\t\t".'<div class="as-p-list' . ($this->list_like_disabled($p_list['ps']) ? ' as-p-list-like-disabled' : '') . '">', '');
			$this->p_list_items($p_list['ps']);
			$this->output("\t\t".'</div> <!-- END as-p-list -->', '');
		}
	}

	public function p_list_items($p_items)
	{
		foreach ($p_items as $p_item) {
			$this->p_list_item($p_item);
		}
	}

	public function p_list_item($p_item)
	{
		$this->output("\t\t".'<div class="as-p-list-item' . rtrim(' ' . @$p_item['classes']) . '" ' . @$p_item['tags'] . '>');

		//$this->p_item_stats($p_item);		
		$this->p_item_image($p_item);
		$this->p_item_main($p_item);
		$this->p_item_clear();

		$this->output("\t\t".'</div> <!-- END as-p-list-item -->', '');
	}

	public function p_item_stats($p_item)
	{
		$this->output("\t\t".'<div class="as-p-item-stats">');

		$this->voting($p_item);
		$this->a_count($p_item);

		$this->output("\t\t".'</div>');
	}

	public function p_item_image($p_item)
	{
		$this->output("\t\t".'<div class="as-p-item-image" style="background: url('.$p_item['icon'].'); background-size: cover; background-repeat: no-repeat; background-position: center center;">');
		$this->output("\t\t".'</div>');
	}

	public function p_item_main($p_item)
	{
		$this->output("\t\t".'<div class="as-p-item-main">');

		$this->view_count($p_item);
		$this->p_item_title($p_item);
		$this->p_item_details($p_item);
		$this->p_item_content($p_item);

		//$this->post_avatar_meta($p_item, 'as-p-item');
		//$this->post_tags($p_item, 'as-p-item');
		$this->p_item_buttons($p_item);

		$this->output("\t\t".'</div>');
	}

	public function p_item_clear()
	{
		$this->output(
			'<div class="as-p-item-clear">',
			'</div>'
		);
	}

	public function p_item_title($p_item)
	{
		$this->output("\t\t".'<div class="as-p-item-title">');
		$this->output(as_get_media_html($p_item['caticon'], 20, 20));
		$this->output("\t\t".'<a href="' . $p_item['url'] . '">' . $p_item['title'] . '</a>');
		if (!empty($p_item['totalqty'])) {
			$this->output("\t\t".' ('.$p_item['totalqty'].') ');
		}
		if (!empty($p_item['isorder'])) {
			$this->output("\t\t".'KSh '.$p_item['totalprice'].' ',
			empty($p_item['closed']['state']) ? '' : ' [' . $p_item['closed']['state'] . ']',
			'</div>'
		);
		}
		else {
			$this->output("\t\t".'KSh '.$p_item['saleprice'].' ',
			empty($p_item['closed']['state']) ? '' : ' [' . $p_item['closed']['state'] . ']',
			'</div>'
		);
		}
		
	}

	public function p_item_details($p_item)
	{
		$this->output("\t\t".'<p>', '<b>'.$p_item['quantity'].'</b> items; each @ KSh '.$p_item['saleprice']); 
		if (!empty($p_item['isorder'])) {	
			$this->output("\t\t".'; Total Price: '.$p_item['totalprice'].'<br>');
		}
		else {
			$this->output("\t\t".'<br>');
		}
		$this->output("\t\t".'Color: <b>'.$p_item['color'].'</b>; Texture: <b>'.$p_item['texture'].'</b>; ');
		$this->output("\t\t".'Volume: <b>'.$p_item['volume'].' cm</b>; Total Weight: <b>'.$p_item['weight'].' kgs</b><br>');
		if (!empty($p_item['address'])) {
			$this->output("\t\t".'Delivery Address: <b>'.$p_item['address'].'</b><br>');
		}
		if (!empty($p_item['isorder'])) {			
			$this->output("\t\t".'Ordered ');
			if (!empty($p_item['customer'])) {
				$this->output("\t\t".'by: <b>'.$p_item['customer'].'</b>; ');
			}
		}
		else {			
			$this->output("\t\t".'Posted ');
			if (!empty($p_item['manufacturer'])) {
				$this->output("\t\t".'by: <b>'.$p_item['manufacturer'].'</b>; ');
			}
		}
		$this->post_meta_when($p_item, 'as-q-view');
	}

	public function p_item_content($p_item)
	{
		if (!empty($p_item['content'])) {
			$this->output("\t\t".'<div class="as-p-item-content">');
			$this->output_raw($p_item['content']);
			$this->output("\t\t".'</div>');
		}
	}

	public function p_item_buttons($p_item)
	{
		if (!empty($p_item['form'])) {
			$this->output("\t\t".'<div class="as-p-item-buttons">');
			$this->form($p_item['form']);
			$this->output("\t\t".'</div>');
		}
	}

	public function voting($post)
	{
		if (isset($post['like_view'])) {
			$this->output("\t\t".'<div class="as-voting ' . (($post['like_view'] == 'updown') ? 'as-voting-updown' : 'as-voting-net') . '" ' . @$post['like_tags'] . '>');
			$this->voting_inner_html($post);
			$this->output("\t\t".'</div>');
		}
	}

	public function voting_inner_html($post)
	{
		$this->like_buttons($post);
		$this->like_count($post);
		$this->like_clear();
	}

	public function like_buttons($post)
	{
		$this->output("\t\t".'<div class="as-like-buttons ' . (($post['like_view'] == 'updown') ? 'as-like-buttons-updown' : 'as-like-buttons-net') . '">');

		switch (@$post['like_state']) {
			case 'liked_up':
				$this->post_hover_button($post, 'like_up_tags', '+', 'as-like-one-button as-liked-up');
				break;

			case 'liked_up_disabled':
				$this->post_disabled_button($post, 'like_up_tags', '+', 'as-like-one-button as-like-up');
				break;

			case 'liked_down':
				$this->post_hover_button($post, 'like_down_tags', '&ndash;', 'as-like-one-button as-liked-down');
				break;

			case 'liked_down_disabled':
				$this->post_disabled_button($post, 'like_down_tags', '&ndash;', 'as-like-one-button as-like-down');
				break;

			case 'up_only':
				$this->post_hover_button($post, 'like_up_tags', '+', 'as-like-first-button as-like-up');
				$this->post_disabled_button($post, 'like_down_tags', '', 'as-like-second-button as-like-down');
				break;

			case 'enabled':
				$this->post_hover_button($post, 'like_up_tags', '+', 'as-like-first-button as-like-up');
				$this->post_hover_button($post, 'like_down_tags', '&ndash;', 'as-like-second-button as-like-down');
				break;

			default:
				$this->post_disabled_button($post, 'like_up_tags', '', 'as-like-first-button as-like-up');
				$this->post_disabled_button($post, 'like_down_tags', '', 'as-like-second-button as-like-down');
				break;
		}

		$this->output("\t\t".'</div>');
	}

	public function like_count($post)
	{
		// You can also use $post['positivelikes_raw'], $post['negativelikes_raw'], $post['netlikes_raw'] to get
		// raw integer like counts, for graphing or showing in other non-textual ways

		$this->output("\t\t".'<div class="as-like-count ' . (($post['like_view'] == 'updown') ? 'as-like-count-updown' : 'as-like-count-net') . '"' . @$post['like_count_tags'] . '>');

		if ($post['like_view'] == 'updown') {
			$this->output_split($post['positivelikes_view'], 'as-positivelike-count');
			$this->output_split($post['negativelikes_view'], 'as-negativelike-count');
		} else {
			$this->output_split($post['netlikes_view'], 'as-netlike-count');
		}

		$this->output("\t\t".'</div>');
	}

	public function like_clear()
	{
		$this->output(
			'<div class="as-like-clear">',
			'</div>'
		);
	}

	public function a_count($post)
	{
		// You can also use $post['reviews_raw'] to get a raw integer count of reviews

		$this->output_split(@$post['reviews'], 'as-a-count', 'span', 'span',
			@$post['review_selected'] ? 'as-a-count-selected' : (@$post['reviews_raw'] ? null : 'as-a-count-zero'));
	}

	public function view_count($post)
	{
		// You can also use $post['views_raw'] to get a raw integer count of views

		$this->output_split(@$post['views'], 'as-view-count');
	}

	public function avatar($item, $class, $prefix = null)
	{
		if (isset($item['avatar'])) {
			if (isset($prefix))
				$this->output($prefix);

			$this->output(
				'<span class="' . $class . '-avatar">',
				$item['avatar'],
				'</span>'
			);
		}
	}

	public function a_selection($post)
	{
		$this->output("\t\t".'<div class="as-a-selection">');

		if (isset($post['select_tags']))
			$this->post_hover_button($post, 'select_tags', '', 'as-a-select');
		elseif (isset($post['unselect_tags']))
			$this->post_hover_button($post, 'unselect_tags', '', 'as-a-unselect');
		elseif ($post['selected'])
			$this->output("\t\t".'<div class="as-a-selected">&nbsp;</div>');

		if (isset($post['select_text']))
			$this->output("\t\t".'<div class="as-a-selected-text">' . @$post['select_text'] . '</div>');

		$this->output("\t\t".'</div>');
	}

	public function post_hover_button($post, $element, $value, $class)
	{
		if (isset($post[$element]))
			$this->output("\t\t".'<input ' . $post[$element] . ' type="submit" value="' . $value . '" class="' . $class . '-button"/> ');
	}

	public function post_disabled_button($post, $element, $value, $class)
	{
		if (isset($post[$element]))
			$this->output("\t\t".'<input ' . $post[$element] . ' type="submit" value="' . $value . '" class="' . $class . '-disabled" disabled="disabled"/> ');
	}

	public function post_avatar_meta($post, $class, $avatarprefix = null, $metaprefix = null, $metaseparator = '<br/>')
	{
		$this->output("\t\t".'<span class="' . $class . '-avatar-meta">');
		$this->avatar($post, $class, $avatarprefix);
		$this->post_meta($post, $class, $metaprefix, $metaseparator);
		$this->output("\t\t".'</span>');
	}

	/**
	 * @deprecated Deprecated from 1.7; please use avatar() instead.
	 * @param $post
	 * @param $class
	 * @param string $prefix
	 */
	public function post_avatar($post, $class, $prefix = null)
	{
		$this->avatar($post, $class, $prefix);
	}

	public function post_meta($post, $class, $prefix = null, $separator = '<br/>')
	{
		$this->output("\t\t".'<span class="' . $class . '-meta">');

		if (isset($prefix))
			$this->output($prefix);

		$order = explode('^', @$post['meta_order']);

		foreach ($order as $element) {
			switch ($element) {
				case 'what':
					$this->post_meta_what($post, $class);
					break;

				case 'when':
					$this->post_meta_when($post, $class);
					break;

				case 'where':
					$this->post_meta_where($post, $class);
					break;

				case 'who':
					$this->post_meta_who($post, $class);
					break;
			}
		}

		$this->post_meta_flags($post, $class);

		if (!empty($post['what_2'])) {
			$this->output($separator);

			foreach ($order as $element) {
				switch ($element) {
					case 'what':
						$this->output("\t\t".'<span class="' . $class . '-what">' . $post['what_2'] . '</span>');
						break;

					case 'when':
						$this->output_split(@$post['when_2'], $class . '-when');
						break;

					case 'who':
						$this->output_split(@$post['who_2'], $class . '-who');
						break;
				}
			}
		}

		$this->output("\t\t".'</span>');
	}

	public function post_meta_what($post, $class)
	{
		if (isset($post['what'])) {
			$classes = $class . '-what';
			if (isset($post['what_your']) && $post['what_your']) {
				$classes .= ' ' . $class . '-what-your';
			}

			if (isset($post['what_url'])) {
				$tags = isset($post['what_url_tags']) ? $post['what_url_tags'] : '';
				$this->output("\t\t".'<a href="' . $post['what_url'] . '" class="' . $classes . '"' . $tags . '>' . $post['what'] . '</a>');
			} else {
				$this->output("\t\t".'<span class="' . $classes . '">' . $post['what'] . '</span>');
			}
		}
	}

	public function post_meta_when($post, $class)
	{
		$this->output_split(@$post['when'], $class . '-when');
	}

	public function post_meta_where($post, $class)
	{
		$this->output_split(@$post['where'], $class . '-where');
	}

	public function post_meta_who($post, $class)
	{
		if (isset($post['who'])) {
			$this->output("\t\t".'<span class="' . $class . '-who">');

			if (strlen(@$post['who']['prefix']))
				$this->output("\t\t".'<span class="' . $class . '-who-pad">' . $post['who']['prefix'] . '</span>');

			if (isset($post['who']['data']))
				$this->output("\t\t".'<span class="' . $class . '-who-data">' . $post['who']['data'] . '</span>');

			if (isset($post['who']['title']))
				$this->output("\t\t".'<span class="' . $class . '-who-title">' . $post['who']['title'] . '</span>');

			// You can also use $post['level'] to get the author's privilege level (as a string)

			if (isset($post['who']['points'])) {
				$post['who']['points']['prefix'] = '(' . $post['who']['points']['prefix'];
				$post['who']['points']['suffix'] .= ')';
				$this->output_split($post['who']['points'], $class . '-who-points');
			}

			if (strlen(@$post['who']['suffix']))
				$this->output("\t\t".'<span class="' . $class . '-who-pad">' . $post['who']['suffix'] . '</span>');

			$this->output("\t\t".'</span>');
		}
	}

	public function post_meta_flags($post, $class)
	{
		$this->output_split(@$post['flags'], $class . '-flags');
	}

	public function post_tags($post, $class)
	{
		if (!empty($post['q_tags'])) {
			$this->output("\t\t".'<div class="' . $class . '-tags">');
			$this->post_tag_list($post, $class);
			$this->output("\t\t".'</div>');
		}
	}

	public function post_tag_list($post, $class)
	{
		$this->output("\t\t".'<ul class="' . $class . '-tag-list">');

		foreach ($post['q_tags'] as $taghtml) {
			$this->post_tag_item($taghtml, $class);
		}

		$this->output("\t\t".'</ul>');
	}

	public function post_tag_item($taghtml, $class)
	{
		$this->output("\t\t".'<li class="' . $class . '-tag-item">' . $taghtml . '</li>');
	}

	public function page_links()
	{
		$page_links = @$this->content['page_links'];

		if (!empty($page_links)) {
			$this->output("\t\t".'<div class="as-page-links">');

			$this->page_links_label(@$page_links['label']);
			$this->page_links_list(@$page_links['items']);
			$this->page_links_clear();

			$this->output("\t\t".'</div>');
		}
	}

	public function page_links_label($label)
	{
		if (!empty($label))
			$this->output("\t\t".'<span class="as-page-links-label">' . $label . '</span>');
	}

	public function page_links_list($page_items)
	{
		if (!empty($page_items)) {
			$this->output("\t\t".'<ul class="as-page-links-list">');

			$index = 0;

			foreach ($page_items as $page_link) {
				$this->set_context('page_index', $index++);
				$this->page_links_item($page_link);

				if ($page_link['ellipsis'])
					$this->page_links_item(array('type' => 'ellipsis'));
			}

			$this->clear_context('page_index');

			$this->output("\t\t".'</ul>');
		}
	}

	public function page_links_item($page_link)
	{
		$this->output("\t\t".'<li class="as-page-links-item">');
		$this->page_link_content($page_link);
		$this->output("\t\t".'</li>');
	}

	public function page_link_content($page_link)
	{
		$label = @$page_link['label'];
		$url = @$page_link['url'];

		switch ($page_link['type']) {
			case 'this':
				$this->output("\t\t".'<span class="as-page-selected">' . $label . '</span>');
				break;

			case 'prev':
				$this->output("\t\t".'<a href="' . $url . '" class="as-page-prev">&laquo; ' . $label . '</a>');
				break;

			case 'next':
				$this->output("\t\t".'<a href="' . $url . '" class="as-page-next">' . $label . ' &raquo;</a>');
				break;

			case 'ellipsis':
				$this->output("\t\t".'<span class="as-page-ellipsis">...</span>');
				break;

			default:
				$this->output("\t\t".'<a href="' . $url . '" class="as-page-link">' . $label . '</a>');
				break;
		}
	}

	public function page_links_clear()
	{
		$this->output(
			'<div class="as-page-links-clear">',
			'</div>'
		);
	}

	public function suggest_next()
	{
		$suggest = @$this->content['suggest_next'];

		if (!empty($suggest)) {
			$this->output("\t\t".'<div class="as-suggest-next">');
			$this->output($suggest);
			$this->output("\t\t".'</div>');
		}
	}

	public function p_view($p_view)
	{
		if (!empty($p_view)) {
			$this->output("\t\t".'<div class="as-q-view' . (@$p_view['hidden'] ? ' as-q-view-hidden' : '') . rtrim(' ' . @$p_view['classes']) . '"' . rtrim(' ' . @$p_view['tags']) . '>');

			if (isset($p_view['main_form_tags'])) {
				$this->output("\t\t".'<form ' . $p_view['main_form_tags'] . '>'); // form for item voting buttons
			}

			$this->q_view_stats($p_view);

			if (isset($p_view['main_form_tags'])) {
				$this->form_hidden_elements(@$p_view['voting_form_hidden']);
				$this->output("\t\t".'</form>');
			}

			$this->q_view_main($p_view);
			$this->q_view_order($p_view);
			
			$this->q_view_clear();
			
			
			$this->output("\t\t".'</div> <!-- END as-q-view -->', '');
		}
	}

	public function q_view_stats($p_view)
	{
		$this->output("\t\t".'<div class="as-q-view-stats">');

		$this->a_count($p_view);

		$this->output("\t\t".'</div>');
	}

	public function q_view_main($p_view)
	{
		$this->output("\t\t".'<div class="as-q-view-main">');

		if (isset($p_view['main_form_tags'])) {
			$this->output("\t\t".'<form ' . $p_view['main_form_tags'] . '>'); // form for buttons on item
		}

		$this->view_count($p_view);
		$this->q_view_vitals($p_view);
		$this->q_view_content($p_view);
		//$this->q_view_follows($p_view);
		$this->q_view_closed($p_view);
		//$this->post_tags($p_view, 'as-q-view');
		//$this->post_avatar_meta($p_view, 'as-q-view');
		$this->q_view_buttons($p_view);

		if (isset($p_view['main_form_tags'])) {
			$this->form_hidden_elements(@$p_view['buttons_form_hidden']);
			$this->output("\t\t".'</form>');
		}

		$this->c_list(@$p_view['c_list'], 'as-q-view');
		$this->c_form(@$p_view['c_form']);

		$this->output("\t\t".'</div> <!-- END as-q-view-main -->');
	}

	public function q_view_vitals($p_view)
	{
		$this->output("\t\t".'<table style="width: 100%;"><tr><td valign="top">');
		$this->output("\t\t".'<img src="'.$p_view['icon'].'" width="200"/>');
		$this->output("\t\t".'</td><td valign="top">');
		$this->output("\t\t".'<div class="as-details">');
		$this->output("\t\t".'<table class="as-details-tt">');
		$this->output("\t\t".'<tr><td valign="top"> Price </td><td valign="top"> : </td><td> KSh. '.$p_view['saleprice'].'</td></tr>');
		$this->output("\t\t".'<tr><td valign="top"> Volume </td><td valign="top"> : </td><td> '.$p_view['volume'].' cm</td></tr>');
		$this->output("\t\t".'<tr><td valign="top"> Weight </td><td valign="top"> : </td><td> '.$p_view['weight'].' kgs</td></tr>');
		$this->output("\t\t".'<tr><td valign="top"> Quantity </td><td valign="top"> : </td><td> '.$p_view['quantity'].' items</td></tr>');
		$this->output("\t\t".'<tr><td valign="top"> Supplier </td><td valign="top"> : </td><td> '.$p_view['manufacturer'].' </td></tr>');
		$this->output("\t\t".'</table>');
		$this->output("\t\t".'</div>');
		$this->output("\t\t".'</td></tr></table>');
		$this->q_view_clear();
	}
	
	public function q_view_order($p_view)
	{
		$onsale = isset($this->content['onsale']) ? $this->content['onsale'] : null;
		if (isset($onsale)) {
			$this->output($onsale['placing']);
			
			$this->output("\t\t".'<form id="as-buying" class="as-buying" ' . $onsale['tags'] . '>');
			$this->output("\t\t".'<h3>Place an order</h3>');	
			
			$this->output("\t\t".'<label>'.$onsale['quantity']['label'] .'</label> ');
			$this->output("\t\t".'<input type="number" ' . $onsale['quantity']['tags'] . ' class="as-buying-amount" value="1"><br>');
			
			$this->output("\t\t".'<label>'.$onsale['address']['label'] .'</label><br>');
			$this->output("\t\t".'<textarea ' . $onsale['address']['tags'] . ' row="2" class="as-buying-address"></textarea><br>');
			
			$this->output("\t\t".'<center><input ' . $onsale['order']['tags'] . ' value="' . $onsale['order']['label'] . '" class="as-form-tall-button as-buying-button" type="submit"></center>');
			
			//$this->form_hidden_elements($onsale['hidden']);
			
			$this->output("\t\t".'</form>');
		}
	}

	public function q_view_content($p_view)
	{
		$content = isset($p_view['content']) ? $p_view['content'] : '';

		$this->output("\t\t".'<div class="as-q-view-content as-post-content">');
		$this->output_raw($content);
		$this->output("\t\t".'</div>');
	}

	public function q_view_follows($p_view)
	{
		if (!empty($p_view['follows']))
			$this->output(
				'<div class="as-q-view-follows">',
				$p_view['follows']['label'],
				'<a href="' . $p_view['follows']['url'] . '" class="as-q-view-follows-link">' . $p_view['follows']['title'] . '</a>',
				'</div>'
			);
	}

	public function q_view_closed($p_view)
	{
		if (!empty($p_view['closed'])) {
			$haslink = isset($p_view['closed']['url']);

			$this->output(
				'<div class="as-q-view-closed">',
				$p_view['closed']['label'],
				($haslink ? ('<a href="' . $p_view['closed']['url'] . '"') : '<span') . ' class="as-q-view-closed-content">',
				$p_view['closed']['content'],
				$haslink ? '</a>' : '</span>',
				'</div>'
			);
		}
	}

	public function q_view_buttons($p_view)
	{
		if (!empty($p_view['form'])) {
			$this->output("\t\t".'<div class="as-q-view-buttons">');
			$this->form($p_view['form']);
			$this->output("\t\t".'</div>');
		}
	}

	public function q_view_clear()
	{
		$this->output(
			'<div class="as-q-view-clear">',
			'</div>'
		);
	}

	public function a_form($a_form)
	{
		$this->output("\t\t".'<div class="as-a-form"' . (isset($a_form['id']) ? (' id="' . $a_form['id'] . '"') : '') .
			(@$a_form['collapse'] ? ' style="display:none;"' : '') . '>');

		$this->form($a_form);
		$this->c_list(@$a_form['c_list'], 'as-a-item');

		$this->output("\t\t".'</div> <!-- END as-a-form -->', '');
	}

	public function a_list($a_list)
	{
		if (!empty($a_list)) {
			$this->part_title($a_list);

			$this->output("\t\t".'<div class="as-a-list' . ($this->list_like_disabled($a_list['as']) ? ' as-a-list-like-disabled' : '') . '" ' . @$a_list['tags'] . '>', '');
			$this->a_list_items($a_list['as']);
			$this->output("\t\t".'</div> <!-- END as-a-list -->', '');
		}
	}

	public function a_list_items($a_items)
	{
		foreach ($a_items as $a_item) {
			$this->a_list_item($a_item);
		}
	}

	public function a_list_item($a_item)
	{
		$extraclass = @$a_item['classes'] . ($a_item['hidden'] ? ' as-a-list-item-hidden' : ($a_item['selected'] ? ' as-a-list-item-selected' : ''));

		$this->output("\t\t".'<div class="as-a-list-item ' . $extraclass . '" ' . @$a_item['tags'] . '>');

		if (isset($a_item['main_form_tags'])) {
			$this->output("\t\t".'<form ' . $a_item['main_form_tags'] . '>'); // form for review voting buttons
		}

		$this->voting($a_item);

		if (isset($a_item['main_form_tags'])) {
			$this->form_hidden_elements(@$a_item['voting_form_hidden']);
			$this->output("\t\t".'</form>');
		}

		$this->a_item_main($a_item);
		$this->a_item_clear();

		$this->output("\t\t".'</div> <!-- END as-a-list-item -->', '');
	}

	public function a_item_main($a_item)
	{
		$this->output("\t\t".'<div class="as-a-item-main">');

		if (isset($a_item['main_form_tags'])) {
			$this->output("\t\t".'<form ' . $a_item['main_form_tags'] . '>'); // form for buttons on review
		}

		if ($a_item['hidden'])
			$this->output("\t\t".'<div class="as-a-item-hidden">');
		elseif ($a_item['selected'])
			$this->output("\t\t".'<div class="as-a-item-selected">');

		$this->a_selection($a_item);
		$this->error(@$a_item['error']);
		$this->a_item_content($a_item);
		$this->post_avatar_meta($a_item, 'as-a-item');

		if ($a_item['hidden'] || $a_item['selected'])
			$this->output("\t\t".'</div>');

		$this->a_item_buttons($a_item);

		if (isset($a_item['main_form_tags'])) {
			$this->form_hidden_elements(@$a_item['buttons_form_hidden']);
			$this->output("\t\t".'</form>');
		}

		$this->c_list(@$a_item['c_list'], 'as-a-item');
		$this->c_form(@$a_item['c_form']);

		$this->output("\t\t".'</div> <!-- END as-a-item-main -->');
	}

	public function a_item_clear()
	{
		$this->output(
			'<div class="as-a-item-clear">',
			'</div>'
		);
	}

	public function a_item_content($a_item)
	{
		if (!isset($a_item['content'])) {
			$a_item['content'] = '';
		}

		$this->output("\t\t".'<div class="as-a-item-content as-post-content">');
		$this->output_raw($a_item['content']);
		$this->output("\t\t".'</div>');
	}

	public function a_item_buttons($a_item)
	{
		if (!empty($a_item['form'])) {
			$this->output("\t\t".'<div class="as-a-item-buttons">');
			$this->form($a_item['form']);
			$this->output("\t\t".'</div>');
		}
	}

	public function c_form($c_form)
	{
		$this->output("\t\t".'<div class="as-c-form"' . (isset($c_form['id']) ? (' id="' . $c_form['id'] . '"') : '') .
			(@$c_form['collapse'] ? ' style="display:none;"' : '') . '>');

		$this->form($c_form);

		$this->output("\t\t".'</div> <!-- END as-c-form -->', '');
	}

	public function c_list($c_list, $class)
	{
		if (!empty($c_list)) {
			$this->output("\t\t".'', '<div class="' . $class . '-c-list"' . (@$c_list['hidden'] ? ' style="display:none;"' : '') . ' ' . @$c_list['tags'] . '>');
			$this->c_list_items($c_list['cs']);
			$this->output("\t\t".'</div> <!-- END as-c-list -->', '');
		}
	}

	public function c_list_items($c_items)
	{
		foreach ($c_items as $c_item) {
			$this->c_list_item($c_item);
		}
	}

	public function c_list_item($c_item)
	{
		$extraclass = @$c_item['classes'] . (@$c_item['hidden'] ? ' as-c-item-hidden' : '');

		$this->output("\t\t".'<div class="as-c-list-item ' . $extraclass . '" ' . @$c_item['tags'] . '>');

		if (isset($c_item['like_view']) && isset($c_item['main_form_tags'])) {
			// form for comment voting buttons
			$this->output("\t\t".'<form ' . $c_item['main_form_tags'] . '>');
			$this->voting($c_item);
			$this->form_hidden_elements(@$c_item['voting_form_hidden']);
			$this->output("\t\t".'</form>');
		}

		$this->c_item_main($c_item);
		$this->c_item_clear();

		$this->output("\t\t".'</div> <!-- END as-c-item -->');
	}

	public function c_item_main($c_item)
	{
		if (isset($c_item['main_form_tags'])) {
			$this->output("\t\t".'<form ' . $c_item['main_form_tags'] . '>'); // form for buttons on comment
		}

		$this->error(@$c_item['error']);

		if (isset($c_item['expand_tags']))
			$this->c_item_expand($c_item);
		elseif (isset($c_item['url']))
			$this->c_item_link($c_item);
		else
			$this->c_item_content($c_item);

		$this->output("\t\t".'<div class="as-c-item-footer">');
		$this->post_avatar_meta($c_item, 'as-c-item');
		$this->c_item_buttons($c_item);
		$this->output("\t\t".'</div>');

		if (isset($c_item['main_form_tags'])) {
			$this->form_hidden_elements(@$c_item['buttons_form_hidden']);
			$this->output("\t\t".'</form>');
		}
	}

	public function c_item_link($c_item)
	{
		$this->output(
			'<a href="' . $c_item['url'] . '" class="as-c-item-link">' . $c_item['title'] . '</a>'
		);
	}

	public function c_item_expand($c_item)
	{
		$this->output(
			'<a href="' . $c_item['url'] . '" ' . $c_item['expand_tags'] . ' class="as-c-item-expand">' . $c_item['title'] . '</a>'
		);
	}

	public function c_item_content($c_item)
	{
		if (!isset($c_item['content'])) {
			$c_item['content'] = '';
		}

		$this->output("\t\t".'<div class="as-c-item-content as-post-content">');
		$this->output_raw($c_item['content']);
		$this->output("\t\t".'</div>');
	}

	public function c_item_buttons($c_item)
	{
		if (!empty($c_item['form'])) {
			$this->output("\t\t".'<div class="as-c-item-buttons">');
			$this->form($c_item['form']);
			$this->output("\t\t".'</div>');
		}
	}

	public function c_item_clear()
	{
		$this->output(
			'<div class="as-c-item-clear">',
			'</div>'
		);
	}


	/**
	 * Generic method to output a basic list of item links.
	 * @param array $p_list
	 * @param string $attrs
	 */
	public function q_title_list($p_list, $attrs = null)
	{
		$this->output("\t\t".'<ul class="as-q-title-list">');
		foreach ($p_list as $q) {
			$this->output(
				'<li class="as-q-title-item">',
				'<a href="' . as_q_path_html($q['postid'], $q['title']) . '" ' . $attrs . '>' . as_html($q['title']) . '</a>',
				'</li>'
			);
		}
		$this->output("\t\t".'</ul>');
	}

	/**
	 * Output block of similar items when writeing.
	 * @param array $p_list
	 * @param string $pretext
	 */
	public function q_write_similar($p_list, $pretext = '')
	{
		if (!count($p_list))
			return;

		$this->output("\t\t".'<div class="as-write-similar">');

		if (strlen($pretext) > 0)
			$this->output("\t\t".'<p class="as-write-similar-title">' . $pretext . '</p>');
		$this->q_title_list($p_list, 'target="_blank"');

		$this->output("\t\t".'</div>');
	}
}
