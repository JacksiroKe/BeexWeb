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

			if ($this->minifyHtml) {
				if (strlen($line))
					echo $line . "\n";
			} else {
				$delta = substr_count($element, '<') - substr_count($element, '<!') - 2 * substr_count($element, '</') - substr_count($element, '/>');

				if ($delta < 0) {
					$this->indent += $delta;
				}

				echo str_repeat("\t", max(0, $this->indent)) . $line . "\n";

				if ($delta > 0) {
					$this->indent += $delta;
				}
			}

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
			$this->output('<div class="as-widgets-' . $region . ' as-widgets-' . $region . '-' . $place . '">');

			foreach ($widgetsHere as $module) {
				$this->output('<div class="as-widget-' . $region . ' as-widget-' . $region . '-' . $place . '">');
				$module->output_widget($region, $place, $this, $this->template, $this->request, $this->content);
				$this->output('</div>');
			}

			$this->output('</div>', '');
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
		$attribution = '<!-- Powered by AppSmata - http://www.appsmata.org/ -->';
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

		$this->output('</head>');
	}

	public function head_title()
	{
		$pagetitle = strlen($this->request) ? strip_tags(@$this->content['title']) : '';
		$headtitle = (strlen($pagetitle) ? "$pagetitle - " : '') . $this->content['site_title'];

		$this->output('<title>' . $headtitle . '</title>');
	}

	public function head_metas()
	{
		if (strlen(@$this->content['description'])) {
			$this->output('<meta name="description" content="' . $this->content['description'] . '"/>');
		}

		if (strlen(@$this->content['keywords'])) {
			// as far as I know, meta keywords have zero effect on search rankings or listings
			$this->output('<meta name="keywords" content="' . $this->content['keywords'] . '"/>');
		}
	}

	public function head_links()
	{
		if (isset($this->content['canonical'])) {
			$this->output('<link rel="canonical" href="' . $this->content['canonical'] . '"/>');
		}

		if (isset($this->content['feed']['url'])) {
			$this->output('<link rel="alternate" type="application/rss+xml" href="' . $this->content['feed']['url'] . '" title="' . @$this->content['feed']['label'] . '"/>');
		}

		// convert page links to rel=prev and rel=next tags
		if (isset($this->content['page_links']['items'])) {
			foreach ($this->content['page_links']['items'] as $page_link) {
				if (in_array($page_link['type'], array('prev', 'next')))
					$this->output('<link rel="' . $page_link['type'] . '" href="' . $page_link['url'] . '" />');
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
		$this->output('<link rel="stylesheet" href="' . $this->rooturl . $this->css_name() . '"/>');

		if (isset($this->content['css_src'])) {
			foreach ($this->content['css_src'] as $css_src) {
				$this->output('<link rel="stylesheet" href="' . $css_src . '"/>');
			}
		}

		if (!empty($this->content['notices'])) {
			$this->output(
				'<style>',
				'.as-body-js-on .as-notice {display:none;}',
				'</style>'
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
		$this->output('<body');
		$this->body_tags();
		$this->output('>');

		$this->body_script();
		$this->body_header();
		$this->body_content();	
		$this->body_footer();
		$this->body_hidden();

		$this->output('</body>');
	}

	public function body_hidden()
	{
		$this->output('<div style="position:absolute;overflow:hidden;clip:rect(0 0 0 0);height:0;width:0;margin:0;padding:0;border:0;">');
		$this->waiting_template();
		$this->output('</div>');
	}

	public function waiting_template()
	{
		$this->output('<span id="as-waiting-template" class="as-waiting">...</span>');
	}

	public function body_script()
	{
		$this->output(
			'<script>',
			"var b = document.getElementsByTagName('body')[0];",
			"b.className = b.className.replace('as-body-js-off', 'as-body-js-on');",
			'</script>'
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
		$this->output('<div class="as-body-wrapper"' . $extratags . '>', '');

		$this->widgets('full', 'top');
		$this->header();
		$this->dashboard();
		$this->widgets('full', 'high');
		$this->sidepanel();
		$this->main();
		$this->widgets('full', 'low');
		$this->footer();
		$this->widgets('full', 'bottom');

		$this->output('</div> <!-- END body-wrapper -->');

		$this->body_suffix();
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

		$this->output('class="' . $class . ' as-body-js-off"');
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
		$this->output('<div class="as-notice" id="' . $notice['id'] . '">');

		if (isset($notice['form_tags']))
			$this->output('<form ' . $notice['form_tags'] . '>');

		$this->output_raw($notice['content']);

		$this->output('<input ' . $notice['close_tags'] . ' type="submit" value="X" class="as-notice-close-button"/> ');

		if (isset($notice['form_tags'])) {
			$this->form_hidden_elements(@$notice['form_hidden']);
			$this->output('</form>');
		}

		$this->output('</div>');
	}

	public function header()
	{
		$this->output('<div class="as-header">');

		$this->logo();
		$this->nav_user_search();
		$this->nav_main_sub();
		$this->header_clear();

		$this->output('</div> <!-- END as-header -->', '');
	}

	public function dashboard()
	{
		if (!(isset($this->content['user']) ? $this->content['user'] : null)) return;
		$user = $this->content['user'];
		$fields = $this->content['dashboard']['fields'];
		$handle = $user['handle'];
		$userid = $user['userid'];
		
		$this->output('<style>.as-main-heading{display:none;} .as-main{width: 100%!important;}</style>');
		$this->output('
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
		$this->output('
								</ul>
							</div> 
						</div>
						<div class="sp_item">
							<div class="desc"> 
								<h3>Your Notifications</h3>
								<ul>');
			if (isset($fields['notify']))
			foreach ($fields['notify'] as $notifications ) $this->show_detail( $notification, 'li' );
		$this->output('
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
		$this->output('<input type="text" ' . $search['field_tags'] . ' value="' . @$search['value'] . '" class="as-search-field"/>');
	}

	public function search_button($search)
	{
		$this->output('<input type="submit" value="' . $search['button_label'] . '" class="as-search-button"/>');
	}

	public function nav($navtype, $level = null)
	{
		$navigation = @$this->content['navigation'][$navtype];

		if ($navtype == 'user') {
			$this->output('<div class="as-nav-' . $navtype . '">');

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

			$this->output('</div>');
		}
		else if ($navtype == 'main') {
            foreach ( $navigation as $key => $item ) {
                if (isset($item['sub'])) {
                    $this->output('<li class="treeview">', '<a href="#">');
                    $this->output('<i class="' . (isset($item['icon']) ? $item['icon'] : 'fa fa-link') . '"></i>');
                    $this->output('<span> ' . $item['label'] . ' </span>');
                    $this->output('<span class="pull-right-container">',
                        '<i class="fa fa-angle-left pull-right"></i>', '</span>', '</a>');
                    $this->output('<ul class="treeview-menu">');
                    foreach ( $item['sub'] as $k => $sub ) {
                        $this->output('<li>', '<a href="'.$sub['url'].'">');
                        $this->output('<i class="' . (isset($sub['icon']) ? $sub['icon'] : 'fa fa-link') . '"></i>');
                        $this->output('<span>' . $sub['label'] . '</span></a>', '</li>');
                    }
                    $this->output('</ul>');
                    $this->output('</li>');
                } else {
                    if (isset($item['url'])) {
                        $this->output('<li>', '<a href="'.$item['url'].'">');
                        $this->output('<i class="' . (isset($item['icon']) ? $item['icon'] : 'fa fa-link') . '"></i>');
                        $this->output('<span>' . $item['label'] . '</span></a>', '</li>');
                    }
                    else $this->output('<li class="header">'.strtoupper($item['label']).'</li>');
                }
            }
        }
	}

	public function nav_list($navigation, $class, $level = null)
	{
		$this->output('<ul class="as-' . $class . '-list' . (isset($level) ? (' as-' . $class . '-list-' . $level) : '') . '">');

		$index = 0;

		foreach ($navigation as $key => $navlink) {
			$this->set_context('nav_key', $key);
			$this->set_context('nav_index', $index++);
			$this->nav_item($key, $navlink, $class, $level);
		}

		$this->clear_context('nav_key');
		$this->clear_context('nav_index');

		$this->output('</ul>');
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

		$this->output('<li class="as-' . $class . '-item' . (@$navlink['opposite'] ? '-opp' : '') .
			(@$navlink['state'] ? (' as-' . $class . '-' . $navlink['state']) : '') . ' as-' . $class . '-' . $suffix . '">');
			
		if (strlen(@$navlink['icon'])) $this->output($navlink['icon']);
		
		$this->nav_link($navlink, $class);

		$subnav = isset($navlink['subnav']) ? $navlink['subnav'] : array();
		if (is_array($subnav) && count($subnav) > 0) {
			$this->nav_list($subnav, $class, 1 + $level);
		}

		$this->output('</li>');
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
			$this->output('<span class="as-' . $class . '-note">' . $navlink['note'] . '</span>');
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
		$this->output('<style>.as-main-heading{display:none;}</style>');
		$this->output('<div class="user-top clearfix">');
		//if ( $isowner ) $this->output('<a id="upload-cover" class="btn btn-default">Change cover</a>');
		$this->output('<div class="user-bar"><div class="avatar pull-left">' . $avatar . '</div></div>');
		$this->output('<div class="user-bar-holder">
					<div class="user-stat pull-right">
						<ul>
							<li class="points">' . $points . '</li>
							<li class="followers">0 <span>Followers</span></li>
						</ul>
					</div>');
		$this->output('<div class="user-nag"><div class="user-buttons pull-right"></div>
						<span class="full-name">'.$name.' ('.$handle. ')</span>');
	}
	
	public function show_detail( $profile, $htag, $class = null, $tags = null )
	{
		$this->output('<'.$htag . (isset($class) ? ' class="' . $class . '"' : '') .
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
			$this->output('<form class="pull-right" ' . $actionbtns['form_tags'] . '>');
			
			if (isset($user['private_message'])){
				$this->output('<span class="send_private_message">'.$user['private_message'].'</span>');
			}
			$this->action_buttons($actionbtns);
			$formhidden = isset($actionbtns['form_hidden']) ? $actionbtns['form_hidden'] : null;
			$this->form_hidden_elements($formhidden);
			$this->output('</form>');
		}
		
		$this->output('</div>','</div>','</div>');			
		$this->output('<div class="as-profile-side">');
		$this->output('<ul class="as-profile-item">');
		if (isset($user['user_info']))
			foreach ($user['user_info'] as $userinfo ) $this->show_detail( $userinfo, 'li' );
		$this->output('</ul>');
		$this->output('</div>');
		
		$this->output('<div class="as-profile-wall">');
		$this->p_list_and_form($listposts);
		$this->output('</div>');
	}
	
	public function action_buttons($actionbtns)
	{
		$actionbtntags = isset($actionbtns['action_tags']) ? $actionbtns['action_tags'] : '';
		$this->output('<span ' . $actionbtntags . '>');
		foreach ($actionbtns['buttons'] as $button => $btn) {
			$this->output('<input type="submit" value="'.$btn['label'].'" '.$btn['tags'].' class="as-form-profile-btn as-form-profile-' . $button . '"/> ');
		}
		$this->output('</span>');
	}

	public function sidepanel()
	{
		$this->output('<div class="as-sidepanel">');
		$this->widgets('side', 'top');
		$this->sidebar();
		$this->widgets('side', 'high');
		$this->widgets('side', 'low');
		$this->output_raw(@$this->content['sidepanel']);
		$this->feed();
		$this->widgets('side', 'bottom');
		$this->output('</div>', '');
	}

	public function sidebar()
	{
		$sidebar = @$this->content['sidebar'];

		if (!empty($sidebar)) {
			$this->output('<div class="as-sidebar">');
			$this->output_raw($sidebar);
			$this->output('</div>', '');
		}
	}

	public function feed()
	{
		$feed = @$this->content['feed'];

		if (!empty($feed)) {
			$this->output('<div class="as-feed">');
			$this->output('<a href="' . $feed['url'] . '" class="as-feed-link">' . @$feed['label'] . '</a>');
			$this->output('</div>');
		}
	}

	public function main()
	{
		$content = $this->content;
		$hidden = !empty($content['hidden']) ? ' as-main-hidden' : '';
		
		$this->output('<div class="content-wrapper fixed-cw">');
		$this->output('<section class="content-header">');
		$this->output('<h1>'.@$content['title'].'</h1>');
		
		if (strlen($this->request)) {
			$this->output('<ol class="breadcrumb">');
			$this->output('<li><a href="'.as_opt('site_url') . '"><i class="fa fa-dashboard"></i> Home</a></li>');
			//$this->output('<li><a href="#">Examples</a></li>');
			$this->output('<li class="active">'.@$content['title'].'</li>');
			$this->output('</ol>');
		}
		
		$this->output('</section>');
	  
		$this->output('<section class="content container-fluid"> <!-- Main Content -->');	
		$this->main_parts($content);
		$this->output('</section> <!-- End Main Content -->');
		$this->output('</div> <!-- END as-main -->', '');
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
				$this->output('<form ' . $favorite['form_tags'] . '>');

			$this->output('<div class="as-main-heading">');
			$this->favorite();
			$this->output('<h1>');
			$this->output($this->template == 'item' ? $this->content['icon'] : '');
			$this->title();
			$this->output('</h1>');
			$this->output('</div>');

			if (isset($favorite)) {
				$formhidden = isset($favorite['form_hidden']) ? $favorite['form_hidden'] : null;
				$this->form_hidden_elements($formhidden);
				$this->output('</form>');
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
			$this->output('<span class="as-favoriting" ' . $favoritetags . '>');
			$this->favorite_inner_html($favorite);
			$this->output('</span>');
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
			$this->output(' [' . $p_view['closed']['state'] . ']');
	}

	public function favorite_inner_html($favorite)
	{
		$this->favorite_button(@$favorite['favorite_add_tags'], 'as-favorite');
		$this->favorite_button(@$favorite['favorite_remove_tags'], 'as-unfavorite');
	}

	public function favorite_button($tags, $class)
	{
		if (isset($tags))
			$this->output('<input ' . $tags . ' type="submit" value="" class="' . $class . '-button"/> ');
	}

	public function error($error)
	{
		if (strlen($error)) {
			$this->output(
				'<div class="as-error">',
				$error,
				'</div>'
			);
		}
	}

	public function success($message)
	{
		if (strlen($message)) {
			$this->output(
				'<div class="as-success">',
				$message,
				'</div>'
			);
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
		$this->output('<div class="as-footer">');

		$this->nav('footer');
		$this->attribution();
		$this->footer_clear();

		$this->output('</div> <!-- END as-footer -->', '');
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
			$this->output('<h2' . rtrim(' ' . @$part['title_tags']) . '>' . @$part['title'] . '</h2>');
	}

	public function part_footer($part)
	{
		if (isset($part['footer']))
			$this->output($part['footer']);
	}

	public function box_title($box)
	{
		$this->output('<div class="box-header with-border">');
		$this->output('<h3 class="box-title">'.$box['title'].'</h3>');
		if (isset($box['tools'])) {					
			$this->output('<div class="box-tools">');
			foreach ($box['tools'] as $tl => $tool)  {
				switch ($tool['type']) {
					case 'submit':
						$this->output(' <input' . rtrim(' ' . @$tool['tags']) . ' value="' . @$tool['label'] . '" title="' . @$tool['popup'] . '" type="submit" class="btn btn-info"/> ');
						break;
						
					case 'button':
						$this->output('<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>');
						break;
						
					case 'link':
						$this->output('<a href="'.$tool['url'].'" class="'.$tool['class'].'" style="float:right">'.$tl.'</a>');
						break;
					
					case 'buttonx':
						$this->output('<button type="button" class="btn btn-box-'.$tool['class'].'" data-widget="'.$tool['data-widget'].'"><i class="fa fa-'.$tl.'"></i></button>');
						break;
						
					case 'label':
						$this->output('<span class="label label-' . @$tool['theme'] . '">' . @$tool['label'] . '</span>');
						break;
					
				}
			}
			$this->output('</div>');
		}
		$this->output('</div>');
	}
	
	public function form($form)
	{
		if (!empty($form)) {
			$this->output('<div class="box box-info">');
			
			if (isset($form['tags']))
				$this->output('<form class="form-horizontal" ' . $form['tags'] . '>');
			if (isset($form['title'])) $this->box_title($form);

			$this->form_body($form);

			if (isset($form['tags']))
				$this->output('</form>');
			$this->output('</div>');
		}
	}

	public function minimal($form)
	{
		if (!empty($form)) {
			$this->output('<div class="box box-info">');
			
			if (isset($form['tags']))
				$this->output('<form class="form-horizontal" ' . $form['tags'] . '>');
			if (isset($form['title'])) $this->box_title($form);

			$this->form_body($form);

			if (isset($form['tags']))
				$this->output('</form>');
			$this->output('</div>');
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
			$this->output('<div class="row">');
			if (isset($row['section'])) $this->output('<section class="'.$row['section'].'">');
							
			foreach ($row['colms'] as $bx => $column) {
				$this->output('<div class="'.$column['class'].'">');
				if (isset($column['extras'])) 
					foreach ($column['extras'] as $xt => $extras) $this->output($extras);
				
				if (isset($column['c_items']))
					$this->column_view($column['c_items']);
				$this->output('</div>');
			}
			if (isset($row['section'])) $this->output('</section>');
			$this->output('</div>');
		}
	}

	public function column_view($content)
	{	
		foreach ($content as $ci => $c_item) {
			switch ($c_item['type'])
			{
				case 'box':
					$this->box_view($c_item);
					break;
					
				case 'list':
					$this->list_view($c_item);
					break;
					
				case 'small-box':
					$this->smallbox_view($c_item);
					break;
					
				case 'btn-app':
					$this->btnapp_view($c_item);
					break;
				
				case 'nav-tabs-custom':
					$this->tabs_view($c_item);
					break;
					
				case 'form':
					$this->form($c_item);
					break;
					
				case 'table':
					$this->table($c_item);
					break;
						
				case 'carousel':
					$this->carousel($c_item);
					break;
			}
		}
	}

	public function tabs_view($tabs)
	{
		if (!empty($tabs)) {
			$this->output('<div class="nav-tabs-custom">');
			$this->output('<ul class="nav nav-tabs'.(isset($tabs['right']) ? ' pull-right' : '').'">');
			$i = 0;
			foreach ($tabs['navs'] as $nv => $nav) {
				if ($nv == 'header')
					$this->output('<li class="pull-left header"><i class="fa fa-inbox"></i> '.$nav.'</li>');
						//$tabs['body']['header']['icon'].'"></i> '.$nav.'</li>');					
				else
					$this->output('<li'.($i==0 ? ' class="active"' : '').'><a href="#'.$nv.'" data-toggle="tab">'.$nav.'</a></li>');					
				$i++;
			}
            $this->output('</ul>');
			
            $this->output(' <div class="tab-content">');
			$t = 0;
			foreach ($tabs['body'] as $tb => $tabpane) {
				$this->output('<div class="'.($t==0 ? 'active ' : '').'tab-pane" id="'.$tb.'">');
				foreach ($tabpane as $tp) {
					$this->tab_pane($tp);
				}
				$this->output('</div>');
				$t++;				
			}
			$this->output('</div>', '</div>');		
		}		
	}
	
	public function tab_pane($tab_pane)
	{
		foreach ($tab_pane as $view) {
			switch ($view['type']) {
				case 'posts':
					$this->post_view($view);	
					break;
				case 'tlines':
					$this->tline_view($view);	
					break;
				case 'form':
					$this->form($view);	
					break;
			}
		}
	}
	
	public function post_view($post)
	{
		foreach ($post['blocks'] as $blk => $block) {
			$this->output('<div class="'.$post['class'].'">');
			if ($blk == 'user-block') {
				$this->user_block($block);
			}
			else {
				$this->output('<'.$block['elem'].' class="'.$blk.'">');
				switch ($block['elem']) 
				{
					case 'p':
						$this->output($block['text']);
						break;
				}	
				$this->output('</'.$block['elem'].'>');			
			}
			$this->output('</div>');
		}
	}
	
	public function tline_view($tline)
	{
		//print_r($timeline);
		$this->output('<ul class="timeline timeline-inverse">');
		$this->output('<li');					
			if (isset($tline['class'])) {							
				$this->output('class="'.$tline['class'].'">');
				if ($tline['class'] == 'time-label')
					$this->output('<span class="bg-red">'.$tline['data']['text'].'</span>');
				
			}
			else {
				$this->output('>');
				$this->tline_data($tline['data']);
			}
			$this->output('</li>');
		$this->output('</ul>');
	}
	
	public function user_block($user)
	{
		$this->output('<div class="user-block">');
		$this->output('<img class="img-circle img-bordered-sm" src="'.$user['img'].'" alt="user image">');
		$this->output('<span class="username">',
			'<a href="#">'.$user['user'].'</a>',
			'<a href="#" class="pull-right btn-box-tool"><i class="fa fa-times"></i></a>',
			'</span>');
		$this->output('<span class="description">'.$user['text'].'</span>');
		$this->output('</div>');
	}
	
	public function carousel($carousel)
	{
		if (!empty($carousel)) {
			$this->output('<div class="box box-primary">');
			
			if (isset($carousel['title'])) $this->box_title($carousel);
			
			$this->output('<div class="box-body">');
			$this->output('<div class="carousel slide" id="'.$carousel['id'].'" data-ride="carousel">');
			$this->output('<ol class="carousel-indicators">');
            foreach ($carousel['body']['indicators']['slides'] as $is => $slider) {
				$this->output('<li data-target="#'.$carousel['body']['indicators']['data-target'].
				'" data-slide-to="'.$is.'" class="'.$slider.'"></li>');				
			}
			$this->output('</ol>');
			
			$this->output('<div class="carousel-inner">');
			foreach ($carousel['body']['slides'] as $bs => $slide) {
				$this->output('<div class="item '.$slide['class'].'">');
                $this->output('<img src="'.$slide['image'][0].'" alt="'.$slide['image'][1].'">');
				$this->output('<div class="carousel-caption">'.$slide['caption'].'</div>', '</div>');
			}
			$this->output('</div>');
			
			$this->output('<a class="left carousel-control" href="#'.$carousel['body']['indicators']['data-target'].
				'" data-slide="prev">
			<span class="fa fa-angle-left"></span>
			</a>');
			$this->output('<a class="right carousel-control" href="#'.$carousel['body']['indicators']['data-target'].
				'" data-slide="next">
			<span class="fa fa-angle-right"></span>
			</a>');
            $this->output('</div>', '</div>');
			
            $this->output('</div>', '</div>');
		}
	}
	
	public function box_view($box)
	{
		if (!empty($box)) {
			$this->output('<div class="box box-'.$box['theme'].'">');
			
			if (isset($box['title'])) $this->box_title($box);
				
			if (isset($box['body'])){
				$this->output('<div class="'.$box['body']['type'].'">');
				foreach ($box['body']['items'] as $bi => $item) {
					if ($item == '') $this->output('<hr>');
					else {
						switch ($item['tag'][0]) {
							case 'avatar':
								$this->output($item['img']);
								break;
								
							case 'link':
								$this->output('<a href="'.$item['href'].'" class="'.$item['tag'][1].'"><b>'.$item['label'].'</b></a>');
								break;
								
							case 'list':
								$this->output('<'.$item['tag'][0].
									(isset($item['tag'][1]) ? ' class="'.$item['tag'][1].'"' : '').'>');
								foreach ($item['data'] as $dt => $ditem) 
								{
									$this->output('<li class="list-group-item">');
									$this->output('<b>'. $dt . '</b> <a class="pull-right">'.$ditem.'</a>');
									$this->output('</li>');
								}									
								$this->output('</'.$item['tag'][0].'>');
								break;
								
							default:
								$this->output('<'.$item['tag'][0].
									(isset($item['tag'][1]) ? ' class="'.$item['tag'][1].'"' : '').'>');
								
								if (isset($item['itag']))
									$this->output('<i class="fa fa-'.$item['itag'][0].' margin-r-'.$item['itag'][1].'"></i>');
								$this->item_data($item['data']);							
								$this->output('</'.$item['tag'][0].'>');
								break;
						}
					}
				}
				$this->output('</div>');
			}
			
            $this->output('</div>');
		}
	}
	
	public function smallbox_view($sbox)
	{
		if (!empty($sbox)) {
			$this->output('<div class="small-box bg-'.$sbox['theme'].'">');
			$this->output('<div class="inner">');
			$this->output('<h3>'.$sbox['count'].'</h3>');
			$this->output('<p>'.$sbox['title'].'</p>');
			$this->output('</div>');
			$this->output('<div class="icon">', '<i class="ion ion-'.$sbox['icon'].'"></i>', '</div>');
			$this->output('<a href="'.$sbox['link'].'" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>');
            $this->output('</div>');
		}
	}
	
	public function btnapp_view($btnapp)
	{
		if (!empty($btnapp)) {
			$this->output('<a class="btn btn-app" style="height:200px;width:200px;" href="'.(isset($btnapp['link'][1]) ? 
				$btnapp['link'] : '#').'">');
			if (isset($btnapp['updates'])) 
				$this->output('<span class="badge '.$btnapp['updates'][0].'" style="font-size:20px;">'.$btnapp['updates'][1].'</span>');
			if (isset($btnapp['img'])) $this->output($btnapp['img']);
			else $this->output('<div class="icon">', '<i class="fa fa-'.$btnapp['icon'].'" style="font-size:100px;"></i>', '</div>');
			$this->output('<h3>'.$btnapp['title'].'</h3>');
			$this->output('</a>');
		}
	}
	
	public function list_view($box)
	{
		if (!empty($box)) {
			$this->output('<div class="box box-'.$box['theme'].'">');
			
			if (isset($box['title'])) $this->box_title($box);
				
			if (isset($box['body'])){
				$this->output('<div class="box-body">');
				switch ($box['body']['type']) {
					case 'product':
						$this->output('<ul class="products-list product-list-in-box">');
						if (isset($box['body']['items']))
						foreach ($box['body']['items'] as $bi => $item) {
							$this->output('<li class="item">
							<div class="product-img">
							<img src="'.$item['img'].'" alt="Item Image">
							</div>
							<div class="product-info">
							<a href="javascript:void(0)" class="product-title">'.$item['label'].'
							<span class="label label-warning pull-right">'.$item['price'].'</span></a>
							<span class="product-description">'.$item['description'].'</span>
							</div>
							</li>');
						}
						$this->output('</ul>');
						break;					
				}
				$this->output('</div>');
			}
			
            $this->output('</div>');
		}
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
						$this->output('<li'.($k==0 ? ' class="active"' : '').'>');
						$this->item_list($sd, $item[1], isset($item[2]) ? $item[2] : '');
						$this->output('</li>');
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
			$this->output('<i class="fa fa-'.$data['itag'][0].' bg-'.
			(isset($data['itag'][1]) ? $data['itag'][1] : 'primary').'"></i>');
		}
		if (isset($data['sub-data'])) {
			$this->output('<div class="timeline-item">');
			$this->output('<span class="time"><i class="fa fa-clock-o"></i> '.$data['sub-data']['time'].'</span>');
			$this->output('<h3 class="timeline-header">'.$data['sub-data']['header'].'</h3>');
			$this->output('<div class="timeline-body">'.$data['sub-data']['body'].'</div>');
			$this->output('<div class="timeline-footer">',
				'<a class="btn btn-primary btn-xs">Read more</a>',
				'<a class="btn btn-danger btn-xs">Delete</a>',
				'</div>');
			$this->output('</div>');
		}
	}

	public function item_label($item, $class)
	{
		$this->output('<span  class="label label-'.$class.'">'.$item.'</span>');
	}
	
	public function item_list($item, $class, $extras = '')
	{
		$this->output('<a href="#"><i class="'.$class.'"></i> '.$item);
		if (isset($extras)) 
			$this->output(' <span class="label label-primary pull-right">'.$extras.'</span>');
		$this->output('</a>');
	}
	
	public function table($table)
	{
		if (!empty($table)) {
			$this->output('<div class="box box-info">');
			if (isset($table['title'])) $this->box_title($table);
			
			$this->output('<div class="box-body">');
            $this->output('<table id="'.$table['id'].'" class="table table-bordered table-striped">');
			
			if (isset($table['headers'])) {
				$this->output('<thead>', '<tr>');
				foreach ($table['headers'] as $header) 
					$this->output('<th valign="top">'.$header.'</th>');
				$this->output('</tr>', '</thead>');
			}
			
			if (isset($table['rows'])) {
				$this->output('<tbody>');
				foreach ($table['rows'] as $row) {
					$this->output('<tr'.(isset($row['onclick']) ? $row['onclick'] : '' ).'>');
					foreach ($row['fields'] as $row) 
						$this->output('<td valign="top">'.$row['data'].'</td>');
					$this->output('</tr>');
				}
				$this->output('</tbody>');
			}
			
			if (isset($table['headers'])) {
				$this->output('<tfoot>', '<tr>');
				foreach ($table['headers'] as $header) 
					$this->output('<th valign="top">'.$header.'</th>');
				$this->output('</tr>', '</tfoot>');
			}
			
			$this->output('</table>');
			$this->output('</div>');
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
		$this->output('<div class="box-body">');

		$columns = $this->form_columns($form);
		$this->form_ok($form, $columns);
		$this->form_fields($form, $columns);
		$this->output('</div>');
		if (isset($form['table'])) $this->table($form['table']);
		$this->form_buttons($form, $columns);

		$this->form_hidden($form);

	}

	public function form_ok($form, $columns)
	{
		if (!empty($form['ok'])) {
			$this->output(
				'<tr>',
				'<td colspan="' . $columns . '" class="as-form-' . $form['style'] . '-ok">',
				$form['ok'],
				'</td>',
				'</tr>'
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
			foreach ($form['fields'] as $key => $field) {
				$this->output('<div class="form-group">');
				if ($columns == 1) 
				{
					$prefixed = (@$field['type'] == 'checkbox') && !empty($field['label']);
					$suffixed = (@$field['type'] == 'select' || @$field['type'] == 'number') && !empty($field['label']);
					
					$this->output('<div class="col-sm-12">');
					
					if ($prefixed) $this->output('<label>');
					else if (!empty($field['label']))
						$this->output('<label for="'.$key.'">'.$field['label'].'</label>');
					
					$this->form_field($field);

					if ($prefixed) {
						$this->output(@$field['label']);						
						$this->output('</label>');
					}
					$this->output('</div>');
				}
				else
				{
					if (!empty($field['label']))
						$this->output('<label for="'.$key.'" class="col-sm-4 control-label">'.$field['label'].'</label>');
					$this->output('<div class="col-sm-8">');
					$this->form_field($field);                  
					$this->output('</div>');
				}
				$this->output('</div>');
			}
		}
	}

	public function form_label($field, $style, $columns, $prefixed, $suffixed, $colspan)
	{
		$extratags = '';

		if ($columns > 1 && (@$field['type'] == 'select-radio' || @$field['rows'] > 1))
			$extratags .= ' style="vertical-align:top;"';

		if (isset($colspan))
			$extratags .= ' colspan="' . $colspan . '"';

		$this->output('<td class="as-form-' . $style . '-label"' . $extratags . '>');

		if ($prefixed) {
			$this->output('<label>');
			$this->form_field($field, $style);
		}

		$this->output(@$field['label']);

		if ($prefixed)
			$this->output('</label>');

		if ($suffixed) {
			$this->output('&nbsp;');
			$this->form_field($field, $style);
		}

		$this->output('</td>');
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

			$this->output('</td>');
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
				$this->output('<tbody id="' . $field['id'] . '">', '<tr>');
			else
				$this->output('<tr id="' . $field['id'] . '">');
		} else
			$this->output('<tr>');

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

		$this->output('</tr>');

		if ($columns == 1 && isset($field['id']))
			$this->output('</tbody>');
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
			$this->output('<div class="box-footer">');
			
			foreach ($form['buttons'] as $key => $button) {
				$this->form_button_data($button, $key, $style);
				$this->form_button_note($button, $style);
			}

			$this->output('</div>');
		}
	}

	public function form_button_data($button, $key, $style)
	{
		if (isset($button['link'])){
			$this->output('<hr>');
			if ($button['link'] == '#') $this->output('<h4>' . @$button['label'] . '</h4>');
			else $this->output('<a href="' . @$button['link'] . '">' . @$button['label'] . '</a><br><br>');
		}			
		elseif (isset($button['split']))
			$this->output('<hr> <input' . rtrim(' ' . @$button['tags']) . ' value="' . @$button['label'] . '" title="' . @$button['popup'] . '" type="submit" class="btn btn-info"/> ');  
		else
			$this->output(' <input' . rtrim(' ' . @$button['tags']) . ' value="' . @$button['label'] . '" title="' . @$button['popup'] . '" type="submit" class="btn btn-info"/> ');
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
		$this->output('<span class="as-form-' . $style . '-buttons-spacer">&nbsp;</span>');
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
				$this->output('<input ' . @$value['tags'] . ' type="hidden" value="' . @$value['value'] . '"/>');
			} else {
				// old method
				$this->output('<input name="' . $name . '" type="hidden" value="' . $value . '"/>');
			}
		}
	}

	public function form_prefix($field, $style)
	{
		if (!empty($field['prefix']))
			$this->output('<span class="as-form-' . $style . '-prefix">' . $field['prefix'] . '</span>');
	}

	public function form_suffix($field, $style)
	{
		if (!empty($field['suffix']))
			$this->output('<span class="as-form-' . $style . '-suffix">' . $field['suffix'] . '</span>');
	}

	public function form_checkbox($field, $style)
	{
		$this->output('<input ' . @$field['tags'] . ' type="checkbox" value="1"' . (@$field['value'] ? ' checked' : '') . '/>');
	}

	public function form_static($field, $style)
	{
		$this->output('<span>' . @$field['value'] . '</span>');
	}

	public function form_password($field, $style)
	{
		$this->output('<input ' . @$field['tags'] . ' type="password" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_email($field, $style)
	{
		$this->output('<input ' . @$field['tags'] . ' type="email" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_phone($field, $style)
	{
		$this->output('<input ' . @$field['tags'] . ' type="phone" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_number($field, $style)
	{
		$this->output('<input ' . @$field['tags'] . ' type="text" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_file($field, $style)
	{
		$this->output('<input ' . @$field['tags'] . ' type="file" class="form-control"/>');
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
		$this->output('<select ' . (isset($field['tags']) ? $field['tags'] : '') . ' class="form-control">');

		// Only match by key if it is explicitly specified. Otherwise, for backwards compatibility, match by value
		$matchbykey = isset($field['match_by']) && $field['match_by'] === 'key';

		foreach ($field['options'] as $key => $value) {
			$selected = isset($field['value']) && (
				($matchbykey && $key === $field['value']) ||
				(!$matchbykey && $value === $field['value'])
			);
			$this->output('<option value="' . $key . '"' . ($selected ? ' selected' : '') . '>' . $value . '</option>');
		}

		$this->output('</select>');
	}

	public function form_select_radio($field, $style)
	{
		$radios = 0;

		foreach ($field['options'] as $tag => $value) {
			//if ($radios++)
				$this->output('<br/>');

			$this->output('<input ' . @$field['tags'] . ' type="radio" value="' . $tag . '"' . (($value == @$field['value']) ? ' checked' : '') . '/> ' . $value);
		}
	}

	public function form_radio($field, $style)
	{
		$radios = 0;

		foreach ($field['options'] as $tag => $value) {
			if ($radios++) $this->output(' ');

			$this->output('&nbsp;&nbsp;<label> <input ' . @$field['tags'] . ' type="radio" value="' . $tag . '"' . (($value == @$field['value']) ? ' checked' : '') . ' /> ' . $value . '</label>');
		}
	}

	public function form_image($field, $style)
	{
		$this->output('<div class="form-control">' . @$field['html'] . '</div>');
	}

	public function form_text_single_row($field, $style)
	{
		$this->output('<input ' . @$field['tags'] . ' type="text" value="' . @$field['value'] . '" class="form-control"/>');
	}

	public function form_text_multi_row($field, $style)
	{
		$this->output('<textarea ' . @$field['tags'] . ' rows="' . (int)$field['rows'] . '" cols="40" class="form-control">' . @$field['value'] . '</textarea>');
	}

	public function form_error($field, $style, $columns)
	{
		$tag = ($columns > 1) ? 'span' : 'div';

		$this->output('<' . $tag . ' class="as-form-' . $style . '-error">' . $field['error'] . '</' . $tag . '>');
	}

	public function form_note($field, $style, $columns)
	{
		$tag = ($columns > 1) ? 'span' : 'div';

		$this->output('<' . $tag . ' class="as-form-' . $style . '-note">' . @$field['note'] . '</' . $tag . '>');
	}

	public function ranking($ranking)
	{
		$this->output('<div class="box box-info">');
			
		if (isset($ranking['title'])) $this->box_title($ranking);
		
		$this->output('<div class="box-body no-padding">');
		$this->output('<ul class="users-list clearfix">');
		
		foreach ($ranking['items'] as $item) {
			$this->output('<li>');
			if (isset($item['avatar']))
				$this->output('<a href="#">'.$item['avatar'].'</a>');
			$this->output('<a class="users-list-name" href="#">'.$item['label']);
			if (isset($item['lasttype'])) $this->output(' ('.$item['lasttype'].')');
			$this->output('</a>');
			if (isset($item['score']))
				$this->output('<span class="users-list-date">'.$item['score'].'</span>');
			$this->output('</li>');
		}
		
		$this->output('</ul>');
		$this->output('</div>');

		//$this->part_footer($ranking);
		
		$this->output('</div>');
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
		$this->output('<' . $tag . ' class="' . $class . '">' . $content . '</' . $tag . '>');
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
			$this->output('<table class="' . $class . '-table">');
			$columns = ceil(count($ranking['items']) / $rows);

			for ($row = 0; $row < $rows; $row++) {
				$this->set_context('ranking_row', $row);
				$this->output('<tr>');

				for ($column = 0; $column < $columns; $column++) {
					$this->set_context('ranking_column', $column);
					$this->ranking_table_item(@$ranking['items'][$column * $rows + $row], $class, $column > 0);
				}

				$this->clear_context('ranking_column');
				$this->output('</tr>');
			}
			$this->clear_context('ranking_row');
			$this->output('</table>');
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
		$this->output('<td class="' . $class . '-spacer">&nbsp;</td>');
	}

	public function gridlayout($grids)
	{
		$this->part_title($grids);
		//$this->part_subtitle($grids);
		
		$this->output('<div class="app_view">');
		
		foreach ($grids['items'] as $appview ){			
			$this->output('<a href="' . $appview['url'] . '">');
			$this->output('<div class="app_item">',
				'<div class="app_img" style="background: url(' . $appview['img']. '); background-size: cover; background-repeat: no-repeat; background-position: center center;">', '</div>');
			$this->output('<span class="app_title">'.$appview['name'].'</span>', '</div>', '</a>');
		}
		
		$this->output('</div>');		
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
		
		$this->output('<table id="as-table">');
		$this->listing_headers($listing['headers'], $checker);
		$this->output('<tbody>');
		foreach ($listing['items'] as $item) {
			$this->output('<tr'.(isset($item['onclick']) ? $item['onclick'] : '' ).'>');
			foreach ($item['fields'] as $field) $this->output('<td valign="top">'.$field['data'].'</td>');
			$this->output('</tr>');
		}
		$this->output('</tbody>');
		if (isset($listing['bottom'])) {
			$this->output('<thead>');
			$this->output('<tr>');
			foreach ($listing['bottom'] as $bottom) $this->output('<th valign="top">'.$bottom.'</th>');
			$this->output('</tr>');
			$this->output('</thead>');			
		}
		if (isset($listing['bottomi'])) {
			$this->output('<thead>');
			$this->output('<tr>');
			foreach ($listing['bottomi'] as $bottomi) $this->output('<th valign="top">'.$bottomi.'</th>');
			$this->output('</tr>');
			$this->output('</thead>');			
		}
		$this->output('</table>', '</form>');
		$this->part_footer($listing);
	}
	
	public function listing_top( $select = null, $links = null, $extras = null )
	{
		$this->output('<form name="listing" action="'.as_self_html().'" method="post">');
		$this->output('<div id="as-table-tools">');
		if (isset($select)) {
			$this->output('<select id="as-action" class="as-form-select" name="as-action">');
			$this->output('<option selected="" value="none">'.as_lang('options/select').'</option>');
			foreach ( $select as $slct ) 
				$this->output('<option value="'.$slct['value'].'">'.$slct['label'].'</option>');
			$this->output('</select>');
			$this->output('<input id="do_action" class="as-form-tall-button as-form-tall-button-save btn btn-default" type="submit" name="do_action" value="'.as_lang('options/apply').'" />');
		}
		if (isset($links)) {
			$this->output('<div style="float:right">');
			foreach ( $links as $link ) 
				$this->output('<a class="as-form-tall-button as-form-tall-button-cancel btn btn-default" href=' . as_path_html($link['url']) . '>'.$link['label'].'</a>');
			$this->output('</div>');
		}
		if (isset($extras)) $this->output($extras);
		$this->output('</div>');
	}
	
	public function listing_infor($items)
	{
		$this->output('<table id="if-table">');	
		$this->output('<tr>');
		$this->output('<td>'.$items[0].'</td>');
		$this->output('<td valign="bottom" style="text-align:right;">'.$items[1].'</td>');
		$this->output('</tr>');	
		$this->output('</table>');	
	}
	
	public function listing_headers($headers, $checker)
	{
		$this->output('<thead>');
		$this->output('<tr class="as-table-top">');
		if (isset($checker)) { 
			$this->output('<th valign="top"><label><input id="check-button" class="chk-item" type="checkbox">');
			$this->output('<input id="uncheck-button" class="chk-item" style="display: none;" type="checkbox">'.$checker.'</label></th>');
		} 
		else $this->output('<th valign="top"></th>');
		foreach ($headers as $thlabel) $this->output('<th valign="top">'.$thlabel.'</th>');
		$this->output('</tr>');
		$this->output('</thead>');
	}
		
	public function listing_th($headers)
	{
		foreach ($headers as $thlabel) $this->output('<th valign="top">'.$thlabel.'</th>');
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
				$this->output('<tbody id="' . $field['id'] . '">');
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
			$this->output('</tbody>');
	}

	public function table_viewer($size, $visible = true)
	{	
		$this->output('<span id="tableviewer"></span>');
		$this->output('<div id="lazydata" style="' . ( $visible ? 'relative' : 'none' ). '"><center>');
		$this->output('<table><tr><td>');
		if ($size == 'small') {
			$this->output('<img src="'.as_path_to_root().'as-media/cyclic.gif" />');
			$this->output('</td><td>');
			$this->output('<img src="'.as_path_to_root().'as-media/cyclic.gif" />');
			$this->output('</td><td>');
			$this->output('<img src="'.as_path_to_root().'as-media/cyclic.gif" />');
		} else {
			$this->output('<img src="'.as_path_to_root().'as-media/loading-gears1.gif" />');
			$this->output('</td><td>');
			$this->output('<img src="'.as_path_to_root().'as-media/loading-gears2.gif" />');
		}
		$this->output('</td></tr></table>');
		$this->output('<h1>Just a minute ... Working ... </h1>');
		$this->output('</center></div>');
	}
	

	public function message_list_and_form($list)
	{
		if (!empty($list)) {
			$this->part_title($list);

			$this->error(@$list['error']);

			if (!empty($list['form'])) {
				$this->output('<form ' . $list['form']['tags'] . '>');
				unset($list['form']['tags']); // we already output the tags before the messages
				$this->message_list_form($list);
			}

			$this->message_list($list);

			if (!empty($list['form'])) {
				$this->output('</form>');
			}
		}
	}

	public function message_list_form($list)
	{
		if (!empty($list['form'])) {
			$this->output('<div class="as-message-list-form">');
			$this->form($list['form']);
			$this->output('</div>');
		}
	}

	public function message_list($list)
	{
		if (isset($list['messages'])) {
			$this->output('<div class="as-message-list" ' . @$list['tags'] . '>');

			foreach ($list['messages'] as $message) {
				$this->message_item($message);
			}

			$this->output('</div> <!-- END as-message-list -->', '');
		}
	}

	public function message_item($message)
	{
		$this->output('<div class="as-message-item" ' . @$message['tags'] . '>');
		$this->message_content($message);
		$this->post_avatar_meta($message, 'as-message');
		$this->message_buttons($message);
		$this->output('</div> <!-- END as-message-item -->', '');
	}

	public function message_content($message)
	{
		if (!empty($message['content'])) {
			$this->output('<div class="as-message-content">');
			$this->output_raw($message['content']);
			$this->output('</div>');
		}
	}

	public function message_buttons($item)
	{
		if (!empty($item['form'])) {
			$this->output('<div class="as-message-buttons">');
			$this->form($item['form']);
			$this->output('</div>');
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
			$this->output('<form ' . $p_list['form']['tags'] . '>');

		$this->p_list($p_list);

		if (!empty($p_list['form'])) {
			unset($p_list['form']['tags']); // we already output the tags before the qs
			$this->p_list_form($p_list);
			$this->output('</form>');
		}

		$this->part_footer($p_list);
	}

	public function p_list_form($p_list)
	{
		if (!empty($p_list['form'])) {
			$this->output('<div class="as-p-list-form">');
			$this->form($p_list['form']);
			$this->output('</div>');
		}
	}

	public function p_list($p_list)
	{
		if (isset($p_list['ps'])) {
			$this->output('<div class="as-p-list' . ($this->list_like_disabled($p_list['ps']) ? ' as-p-list-like-disabled' : '') . '">', '');
			$this->p_list_items($p_list['ps']);
			$this->output('</div> <!-- END as-p-list -->', '');
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
		$this->output('<div class="as-p-list-item' . rtrim(' ' . @$p_item['classes']) . '" ' . @$p_item['tags'] . '>');

		//$this->p_item_stats($p_item);		
		$this->p_item_image($p_item);
		$this->p_item_main($p_item);
		$this->p_item_clear();

		$this->output('</div> <!-- END as-p-list-item -->', '');
	}

	public function p_item_stats($p_item)
	{
		$this->output('<div class="as-p-item-stats">');

		$this->voting($p_item);
		$this->a_count($p_item);

		$this->output('</div>');
	}

	public function p_item_image($p_item)
	{
		$this->output('<div class="as-p-item-image" style="background: url('.$p_item['icon'].'); background-size: cover; background-repeat: no-repeat; background-position: center center;">');
		$this->output('</div>');
	}

	public function p_item_main($p_item)
	{
		$this->output('<div class="as-p-item-main">');

		$this->view_count($p_item);
		$this->p_item_title($p_item);
		$this->p_item_details($p_item);
		$this->p_item_content($p_item);

		//$this->post_avatar_meta($p_item, 'as-p-item');
		//$this->post_tags($p_item, 'as-p-item');
		$this->p_item_buttons($p_item);

		$this->output('</div>');
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
		$this->output('<div class="as-p-item-title">');
		$this->output(as_get_media_html($p_item['caticon'], 20, 20));
		$this->output('<a href="' . $p_item['url'] . '">' . $p_item['title'] . '</a>');
		if (!empty($p_item['totalqty'])) {
			$this->output(' ('.$p_item['totalqty'].') ');
		}
		if (!empty($p_item['isorder'])) {
			$this->output('KSh '.$p_item['totalprice'].' ',
			empty($p_item['closed']['state']) ? '' : ' [' . $p_item['closed']['state'] . ']',
			'</div>'
		);
		}
		else {
			$this->output('KSh '.$p_item['saleprice'].' ',
			empty($p_item['closed']['state']) ? '' : ' [' . $p_item['closed']['state'] . ']',
			'</div>'
		);
		}
		
	}

	public function p_item_details($p_item)
	{
		$this->output('<p>', '<b>'.$p_item['quantity'].'</b> items; each @ KSh '.$p_item['saleprice']); 
		if (!empty($p_item['isorder'])) {	
			$this->output('; Total Price: '.$p_item['totalprice'].'<br>');
		}
		else {
			$this->output('<br>');
		}
		$this->output('Color: <b>'.$p_item['color'].'</b>; Texture: <b>'.$p_item['texture'].'</b>; ');
		$this->output('Volume: <b>'.$p_item['volume'].' cm</b>; Total Weight: <b>'.$p_item['weight'].' kgs</b><br>');
		if (!empty($p_item['address'])) {
			$this->output('Delivery Address: <b>'.$p_item['address'].'</b><br>');
		}
		if (!empty($p_item['isorder'])) {			
			$this->output('Ordered ');
			if (!empty($p_item['customer'])) {
				$this->output('by: <b>'.$p_item['customer'].'</b>; ');
			}
		}
		else {			
			$this->output('Posted ');
			if (!empty($p_item['manufacturer'])) {
				$this->output('by: <b>'.$p_item['manufacturer'].'</b>; ');
			}
		}
		$this->post_meta_when($p_item, 'as-q-view');
	}

	public function p_item_content($p_item)
	{
		if (!empty($p_item['content'])) {
			$this->output('<div class="as-p-item-content">');
			$this->output_raw($p_item['content']);
			$this->output('</div>');
		}
	}

	public function p_item_buttons($p_item)
	{
		if (!empty($p_item['form'])) {
			$this->output('<div class="as-p-item-buttons">');
			$this->form($p_item['form']);
			$this->output('</div>');
		}
	}

	public function voting($post)
	{
		if (isset($post['like_view'])) {
			$this->output('<div class="as-voting ' . (($post['like_view'] == 'updown') ? 'as-voting-updown' : 'as-voting-net') . '" ' . @$post['like_tags'] . '>');
			$this->voting_inner_html($post);
			$this->output('</div>');
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
		$this->output('<div class="as-like-buttons ' . (($post['like_view'] == 'updown') ? 'as-like-buttons-updown' : 'as-like-buttons-net') . '">');

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

		$this->output('</div>');
	}

	public function like_count($post)
	{
		// You can also use $post['positivelikes_raw'], $post['negativelikes_raw'], $post['netlikes_raw'] to get
		// raw integer like counts, for graphing or showing in other non-textual ways

		$this->output('<div class="as-like-count ' . (($post['like_view'] == 'updown') ? 'as-like-count-updown' : 'as-like-count-net') . '"' . @$post['like_count_tags'] . '>');

		if ($post['like_view'] == 'updown') {
			$this->output_split($post['positivelikes_view'], 'as-positivelike-count');
			$this->output_split($post['negativelikes_view'], 'as-negativelike-count');
		} else {
			$this->output_split($post['netlikes_view'], 'as-netlike-count');
		}

		$this->output('</div>');
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
		$this->output('<div class="as-a-selection">');

		if (isset($post['select_tags']))
			$this->post_hover_button($post, 'select_tags', '', 'as-a-select');
		elseif (isset($post['unselect_tags']))
			$this->post_hover_button($post, 'unselect_tags', '', 'as-a-unselect');
		elseif ($post['selected'])
			$this->output('<div class="as-a-selected">&nbsp;</div>');

		if (isset($post['select_text']))
			$this->output('<div class="as-a-selected-text">' . @$post['select_text'] . '</div>');

		$this->output('</div>');
	}

	public function post_hover_button($post, $element, $value, $class)
	{
		if (isset($post[$element]))
			$this->output('<input ' . $post[$element] . ' type="submit" value="' . $value . '" class="' . $class . '-button"/> ');
	}

	public function post_disabled_button($post, $element, $value, $class)
	{
		if (isset($post[$element]))
			$this->output('<input ' . $post[$element] . ' type="submit" value="' . $value . '" class="' . $class . '-disabled" disabled="disabled"/> ');
	}

	public function post_avatar_meta($post, $class, $avatarprefix = null, $metaprefix = null, $metaseparator = '<br/>')
	{
		$this->output('<span class="' . $class . '-avatar-meta">');
		$this->avatar($post, $class, $avatarprefix);
		$this->post_meta($post, $class, $metaprefix, $metaseparator);
		$this->output('</span>');
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
		$this->output('<span class="' . $class . '-meta">');

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
						$this->output('<span class="' . $class . '-what">' . $post['what_2'] . '</span>');
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

		$this->output('</span>');
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
				$this->output('<a href="' . $post['what_url'] . '" class="' . $classes . '"' . $tags . '>' . $post['what'] . '</a>');
			} else {
				$this->output('<span class="' . $classes . '">' . $post['what'] . '</span>');
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
			$this->output('<span class="' . $class . '-who">');

			if (strlen(@$post['who']['prefix']))
				$this->output('<span class="' . $class . '-who-pad">' . $post['who']['prefix'] . '</span>');

			if (isset($post['who']['data']))
				$this->output('<span class="' . $class . '-who-data">' . $post['who']['data'] . '</span>');

			if (isset($post['who']['title']))
				$this->output('<span class="' . $class . '-who-title">' . $post['who']['title'] . '</span>');

			// You can also use $post['level'] to get the author's privilege level (as a string)

			if (isset($post['who']['points'])) {
				$post['who']['points']['prefix'] = '(' . $post['who']['points']['prefix'];
				$post['who']['points']['suffix'] .= ')';
				$this->output_split($post['who']['points'], $class . '-who-points');
			}

			if (strlen(@$post['who']['suffix']))
				$this->output('<span class="' . $class . '-who-pad">' . $post['who']['suffix'] . '</span>');

			$this->output('</span>');
		}
	}

	public function post_meta_flags($post, $class)
	{
		$this->output_split(@$post['flags'], $class . '-flags');
	}

	public function post_tags($post, $class)
	{
		if (!empty($post['q_tags'])) {
			$this->output('<div class="' . $class . '-tags">');
			$this->post_tag_list($post, $class);
			$this->output('</div>');
		}
	}

	public function post_tag_list($post, $class)
	{
		$this->output('<ul class="' . $class . '-tag-list">');

		foreach ($post['q_tags'] as $taghtml) {
			$this->post_tag_item($taghtml, $class);
		}

		$this->output('</ul>');
	}

	public function post_tag_item($taghtml, $class)
	{
		$this->output('<li class="' . $class . '-tag-item">' . $taghtml . '</li>');
	}

	public function page_links()
	{
		$page_links = @$this->content['page_links'];

		if (!empty($page_links)) {
			$this->output('<div class="as-page-links">');

			$this->page_links_label(@$page_links['label']);
			$this->page_links_list(@$page_links['items']);
			$this->page_links_clear();

			$this->output('</div>');
		}
	}

	public function page_links_label($label)
	{
		if (!empty($label))
			$this->output('<span class="as-page-links-label">' . $label . '</span>');
	}

	public function page_links_list($page_items)
	{
		if (!empty($page_items)) {
			$this->output('<ul class="as-page-links-list">');

			$index = 0;

			foreach ($page_items as $page_link) {
				$this->set_context('page_index', $index++);
				$this->page_links_item($page_link);

				if ($page_link['ellipsis'])
					$this->page_links_item(array('type' => 'ellipsis'));
			}

			$this->clear_context('page_index');

			$this->output('</ul>');
		}
	}

	public function page_links_item($page_link)
	{
		$this->output('<li class="as-page-links-item">');
		$this->page_link_content($page_link);
		$this->output('</li>');
	}

	public function page_link_content($page_link)
	{
		$label = @$page_link['label'];
		$url = @$page_link['url'];

		switch ($page_link['type']) {
			case 'this':
				$this->output('<span class="as-page-selected">' . $label . '</span>');
				break;

			case 'prev':
				$this->output('<a href="' . $url . '" class="as-page-prev">&laquo; ' . $label . '</a>');
				break;

			case 'next':
				$this->output('<a href="' . $url . '" class="as-page-next">' . $label . ' &raquo;</a>');
				break;

			case 'ellipsis':
				$this->output('<span class="as-page-ellipsis">...</span>');
				break;

			default:
				$this->output('<a href="' . $url . '" class="as-page-link">' . $label . '</a>');
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
			$this->output('<div class="as-suggest-next">');
			$this->output($suggest);
			$this->output('</div>');
		}
	}

	public function p_view($p_view)
	{
		if (!empty($p_view)) {
			$this->output('<div class="as-q-view' . (@$p_view['hidden'] ? ' as-q-view-hidden' : '') . rtrim(' ' . @$p_view['classes']) . '"' . rtrim(' ' . @$p_view['tags']) . '>');

			if (isset($p_view['main_form_tags'])) {
				$this->output('<form ' . $p_view['main_form_tags'] . '>'); // form for item voting buttons
			}

			$this->q_view_stats($p_view);

			if (isset($p_view['main_form_tags'])) {
				$this->form_hidden_elements(@$p_view['voting_form_hidden']);
				$this->output('</form>');
			}

			$this->q_view_main($p_view);
			$this->q_view_order($p_view);
			
			$this->q_view_clear();
			
			
			$this->output('</div> <!-- END as-q-view -->', '');
		}
	}

	public function q_view_stats($p_view)
	{
		$this->output('<div class="as-q-view-stats">');

		$this->a_count($p_view);

		$this->output('</div>');
	}

	public function q_view_main($p_view)
	{
		$this->output('<div class="as-q-view-main">');

		if (isset($p_view['main_form_tags'])) {
			$this->output('<form ' . $p_view['main_form_tags'] . '>'); // form for buttons on item
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
			$this->output('</form>');
		}

		$this->c_list(@$p_view['c_list'], 'as-q-view');
		$this->c_form(@$p_view['c_form']);

		$this->output('</div> <!-- END as-q-view-main -->');
	}

	public function q_view_vitals($p_view)
	{
		$this->output('<table style="width: 100%;"><tr><td valign="top">');
		$this->output('<img src="'.$p_view['icon'].'" width="200"/>');
		$this->output('</td><td valign="top">');
		$this->output('<div class="as-details">');
		$this->output('<table class="as-details-tt">');
		$this->output('<tr><td valign="top"> Price </td><td valign="top"> : </td><td> KSh. '.$p_view['saleprice'].'</td></tr>');
		$this->output('<tr><td valign="top"> Volume </td><td valign="top"> : </td><td> '.$p_view['volume'].' cm</td></tr>');
		$this->output('<tr><td valign="top"> Weight </td><td valign="top"> : </td><td> '.$p_view['weight'].' kgs</td></tr>');
		$this->output('<tr><td valign="top"> Quantity </td><td valign="top"> : </td><td> '.$p_view['quantity'].' items</td></tr>');
		$this->output('<tr><td valign="top"> Supplier </td><td valign="top"> : </td><td> '.$p_view['manufacturer'].' </td></tr>');
		$this->output('</table>');
		$this->output('</div>');
		$this->output('</td></tr></table>');
		$this->q_view_clear();
	}
	
	public function q_view_order($p_view)
	{
		$onsale = isset($this->content['onsale']) ? $this->content['onsale'] : null;
		if (isset($onsale)) {
			$this->output($onsale['placing']);
			
			$this->output('<form id="as-buying" class="as-buying" ' . $onsale['tags'] . '>');
			$this->output('<h3>Place an order</h3>');	
			
			$this->output('<label>'.$onsale['quantity']['label'] .'</label> ');
			$this->output('<input type="number" ' . $onsale['quantity']['tags'] . ' class="as-buying-amount" value="1"><br>');
			
			$this->output('<label>'.$onsale['address']['label'] .'</label><br>');
			$this->output('<textarea ' . $onsale['address']['tags'] . ' row="2" class="as-buying-address"></textarea><br>');
			
			$this->output('<center><input ' . $onsale['order']['tags'] . ' value="' . $onsale['order']['label'] . '" class="as-form-tall-button as-buying-button" type="submit"></center>');
			
			//$this->form_hidden_elements($onsale['hidden']);
			
			$this->output('</form>');
		}
	}

	public function q_view_content($p_view)
	{
		$content = isset($p_view['content']) ? $p_view['content'] : '';

		$this->output('<div class="as-q-view-content as-post-content">');
		$this->output_raw($content);
		$this->output('</div>');
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
			$this->output('<div class="as-q-view-buttons">');
			$this->form($p_view['form']);
			$this->output('</div>');
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
		$this->output('<div class="as-a-form"' . (isset($a_form['id']) ? (' id="' . $a_form['id'] . '"') : '') .
			(@$a_form['collapse'] ? ' style="display:none;"' : '') . '>');

		$this->form($a_form);
		$this->c_list(@$a_form['c_list'], 'as-a-item');

		$this->output('</div> <!-- END as-a-form -->', '');
	}

	public function a_list($a_list)
	{
		if (!empty($a_list)) {
			$this->part_title($a_list);

			$this->output('<div class="as-a-list' . ($this->list_like_disabled($a_list['as']) ? ' as-a-list-like-disabled' : '') . '" ' . @$a_list['tags'] . '>', '');
			$this->a_list_items($a_list['as']);
			$this->output('</div> <!-- END as-a-list -->', '');
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

		$this->output('<div class="as-a-list-item ' . $extraclass . '" ' . @$a_item['tags'] . '>');

		if (isset($a_item['main_form_tags'])) {
			$this->output('<form ' . $a_item['main_form_tags'] . '>'); // form for review voting buttons
		}

		$this->voting($a_item);

		if (isset($a_item['main_form_tags'])) {
			$this->form_hidden_elements(@$a_item['voting_form_hidden']);
			$this->output('</form>');
		}

		$this->a_item_main($a_item);
		$this->a_item_clear();

		$this->output('</div> <!-- END as-a-list-item -->', '');
	}

	public function a_item_main($a_item)
	{
		$this->output('<div class="as-a-item-main">');

		if (isset($a_item['main_form_tags'])) {
			$this->output('<form ' . $a_item['main_form_tags'] . '>'); // form for buttons on review
		}

		if ($a_item['hidden'])
			$this->output('<div class="as-a-item-hidden">');
		elseif ($a_item['selected'])
			$this->output('<div class="as-a-item-selected">');

		$this->a_selection($a_item);
		$this->error(@$a_item['error']);
		$this->a_item_content($a_item);
		$this->post_avatar_meta($a_item, 'as-a-item');

		if ($a_item['hidden'] || $a_item['selected'])
			$this->output('</div>');

		$this->a_item_buttons($a_item);

		if (isset($a_item['main_form_tags'])) {
			$this->form_hidden_elements(@$a_item['buttons_form_hidden']);
			$this->output('</form>');
		}

		$this->c_list(@$a_item['c_list'], 'as-a-item');
		$this->c_form(@$a_item['c_form']);

		$this->output('</div> <!-- END as-a-item-main -->');
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

		$this->output('<div class="as-a-item-content as-post-content">');
		$this->output_raw($a_item['content']);
		$this->output('</div>');
	}

	public function a_item_buttons($a_item)
	{
		if (!empty($a_item['form'])) {
			$this->output('<div class="as-a-item-buttons">');
			$this->form($a_item['form']);
			$this->output('</div>');
		}
	}

	public function c_form($c_form)
	{
		$this->output('<div class="as-c-form"' . (isset($c_form['id']) ? (' id="' . $c_form['id'] . '"') : '') .
			(@$c_form['collapse'] ? ' style="display:none;"' : '') . '>');

		$this->form($c_form);

		$this->output('</div> <!-- END as-c-form -->', '');
	}

	public function c_list($c_list, $class)
	{
		if (!empty($c_list)) {
			$this->output('', '<div class="' . $class . '-c-list"' . (@$c_list['hidden'] ? ' style="display:none;"' : '') . ' ' . @$c_list['tags'] . '>');
			$this->c_list_items($c_list['cs']);
			$this->output('</div> <!-- END as-c-list -->', '');
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

		$this->output('<div class="as-c-list-item ' . $extraclass . '" ' . @$c_item['tags'] . '>');

		if (isset($c_item['like_view']) && isset($c_item['main_form_tags'])) {
			// form for comment voting buttons
			$this->output('<form ' . $c_item['main_form_tags'] . '>');
			$this->voting($c_item);
			$this->form_hidden_elements(@$c_item['voting_form_hidden']);
			$this->output('</form>');
		}

		$this->c_item_main($c_item);
		$this->c_item_clear();

		$this->output('</div> <!-- END as-c-item -->');
	}

	public function c_item_main($c_item)
	{
		if (isset($c_item['main_form_tags'])) {
			$this->output('<form ' . $c_item['main_form_tags'] . '>'); // form for buttons on comment
		}

		$this->error(@$c_item['error']);

		if (isset($c_item['expand_tags']))
			$this->c_item_expand($c_item);
		elseif (isset($c_item['url']))
			$this->c_item_link($c_item);
		else
			$this->c_item_content($c_item);

		$this->output('<div class="as-c-item-footer">');
		$this->post_avatar_meta($c_item, 'as-c-item');
		$this->c_item_buttons($c_item);
		$this->output('</div>');

		if (isset($c_item['main_form_tags'])) {
			$this->form_hidden_elements(@$c_item['buttons_form_hidden']);
			$this->output('</form>');
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

		$this->output('<div class="as-c-item-content as-post-content">');
		$this->output_raw($c_item['content']);
		$this->output('</div>');
	}

	public function c_item_buttons($c_item)
	{
		if (!empty($c_item['form'])) {
			$this->output('<div class="as-c-item-buttons">');
			$this->form($c_item['form']);
			$this->output('</div>');
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
		$this->output('<ul class="as-q-title-list">');
		foreach ($p_list as $q) {
			$this->output(
				'<li class="as-q-title-item">',
				'<a href="' . as_q_path_html($q['postid'], $q['title']) . '" ' . $attrs . '>' . as_html($q['title']) . '</a>',
				'</li>'
			);
		}
		$this->output('</ul>');
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

		$this->output('<div class="as-write-similar">');

		if (strlen($pretext) > 0)
			$this->output('<p class="as-write-similar-title">' . $pretext . '</p>');
		$this->q_title_list($p_list, 'target="_blank"');

		$this->output('</div>');
	}
}
