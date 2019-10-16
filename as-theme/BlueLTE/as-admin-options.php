<?php
class as_html_theme_layer extends as_html_theme_base {	
	
	var $theme_directory;
	var $theme_url;
	function html_theme_layer($template, $content, $rooturl, $request)
	{
		global $as_layers;
		$this->theme_directory = $as_layers['BlueLTE Theme']['directory'];
		$this->theme_url = $as_layers['BlueLTE Theme']['urltoroot'];
		as_html_theme_base::as_html_theme_base($template, $content, $rooturl, $request);
	}
	
	function doctype(){
		global $p_path, $s_path,$p_url, $s_url;
		global $as_request;
		require_once AS_INCLUDE_DIR . '/app/admin.php';
		
		$categories = as_db_select_with_pending( as_db_category_nav_selectspec( null, true ) );

		//	For non-text options, lists of option types, minima and maxima

		$optiontype = array(
			'bluelte_activate_prod_mode'           => 'checkbox',
			'bluelte_use_local_font'               => 'checkbox',
			'bluelte_enable_top_bar'               => 'checkbox',
			'bluelte_show_top_social_icons'        => 'checkbox',
			'bluelte_enable_sticky_header'         => 'checkbox',
			'bluelte_enable_back_to_top_btn'       => 'checkbox',
			'bluelte_show_home_page_banner'        => 'checkbox',
			'bluelte_banner_closable'              => 'checkbox',
			'bluelte_banner_show_ask_box'          => 'checkbox',
			'bluelte_show_collapsible_btns'        => 'checkbox',
			'bluelte_show_breadcrumbs'             => 'checkbox',
			'bluelte_show_site_stats_above_footer' => 'checkbox',
			'bluelte_show_social_links_at_footer'  => 'checkbox',
			'bluelte_show_copyright_at_footer'     => 'checkbox',
			'bluelte_show_custom_404_page'         => 'checkbox',
			'bluelte_copyright_text'               => 'text',
			'bluelte_banner_head_text'             => 'text',
			'bluelte_banner_div1_text'             => 'text',
			'bluelte_banner_div1_icon'             => 'text',
			'bluelte_banner_div2_text'             => 'text',
			'bluelte_banner_div2_icon'             => 'text',
			'bluelte_banner_div3_text'             => 'text',
			'bluelte_banner_div3_icon'             => 'text',
			'bluelte_top_bar_left_text'            => 'text',
			'bluelte_top_bar_right_text'           => 'text',
			'bluelte_facebook_url'                 => 'text',
			'bluelte_twitter_url'                  => 'text',
			'bluelte_pinterest_url'                => 'text',
			'bluelte_google-plus_url'              => 'text',
			'bluelte_vk_url'                       => 'text',
			'bluelte_email_address'                => 'text',
			'bluelte_custom_404_text'              => 'text',
			'bluelte_general_settings_notice'      => 'custom',
			'bluelte_homepage_settings_notice'     => 'custom',
			'bluelte_footer_settings_notice'       => 'custom',
			'bluelte_social_settings_notice'       => 'custom',
		);

		$optionmaximum = array();

		$optionminimum = array();

		//	Define the options to show (and some other visual stuff) based on request

		$formstyle = 'tall';
		$checkboxtodisplay = null;
		
		if ( ($as_request == 'admin/bluelte') and (as_get_logged_in_level()>=AS_USER_LEVEL_ADMIN) ) {
		
			$showoptions = array( 'bluelte_general_settings_notice', 'bluelte_activate_prod_mode', 'bluelte_use_local_font','bluelte_enable_top_bar', 'bluelte_top_bar_left_text', 'bluelte_top_bar_right_text', 'bluelte_show_top_social_icons', 'bluelte_enable_sticky_header', 'bluelte_enable_back_to_top_btn' );
            array_push( $showoptions, 'bluelte_show_collapsible_btns' );
            array_push( $showoptions, 'bluelte_show_custom_404_page', 'bluelte_custom_404_text' );

            array_push( $showoptions, 'bluelte_homepage_settings_notice', 'bluelte_show_home_page_banner', 'bluelte_banner_head_text', 'bluelte_banner_div1_text', 'bluelte_banner_div1_icon', 'bluelte_banner_div2_text', 'bluelte_banner_div2_icon', 'bluelte_banner_div3_text', 'bluelte_banner_div3_icon', 'bluelte_banner_show_ask_box', 'bluelte_banner_closable' );

            if ( class_exists( 'Ami_Breadcrumb' ) ) {
                array_push( $showoptions, '', 'bluelte_show_breadcrumbs' );
            }

            array_push( $showoptions, 'bluelte_footer_settings_notice', 'bluelte_show_site_stats_above_footer', 'bluelte_show_social_links_at_footer', 'bluelte_show_copyright_at_footer', 'bluelte_copyright_text' );

            array_push( $showoptions, 'bluelte_social_settings_notice', 'bluelte_facebook_url', 'bluelte_twitter_url', 'bluelte_pinterest_url', 'bluelte_google-plus_url', 'bluelte_vk_url', 'bluelte_email_address' );
			
			$getoptions = array();
			foreach ( $showoptions as $optionname )
				if ( strlen( $optionname ) && ( strpos( $optionname, '/' ) === false ) ) // empties represent spacers in forms
					$getoptions[] = $optionname;


		//	Process user actions

			$errors = array();
			$securityexpired = false;

			$formokhtml = null;

			if ( as_clicked( 'doresetoptions' ) ) {
				if ( !as_check_form_security_code( 'admin/bluelte', as_post_text( 'code' ) ) )
					$securityexpired = true;

				else {
					bluelte_reset_options( $getoptions );
					$formokhtml = as_lang_html('admin/options_reset');
				}
			} elseif ( as_clicked( 'dosaveoptions' ) ) {
				if ( !as_check_form_security_code( 'admin/bluelte', as_post_text( 'code' ) ) )
					$securityexpired = true;

				else {
					foreach ( $getoptions as $optionname ) {
						$optionvalue = as_post_text( 'option_' . $optionname );

						if (
							( @$optiontype[$optionname] == 'number' ) ||
							( @$optiontype[$optionname] == 'checkbox' ) ||
							( ( @$optiontype[$optionname] == 'number-blank' ) && strlen( $optionvalue ) )
						)
							$optionvalue = (int) $optionvalue;

						if ( isset( $optionmaximum[$optionname] ) )
							$optionvalue = min( $optionmaximum[$optionname], $optionvalue );

						if ( isset( $optionminimum[$optionname] ) )
							$optionvalue = max( $optionminimum[$optionname], $optionvalue );

						as_set_option( $optionname, $optionvalue );
					}

					$formokhtml = as_lang_html( 'admin/options_saved' );
				}
			}

			//	Get the actual options

			$options = as_get_options( $getoptions );
	
			$p_path = $this->theme_directory . 'patterns';
			$s_path = $this->theme_directory . 'styles';
			$p_url = $this->theme_url . 'patterns';
			$s_url = $this->theme_url . 'styles';

            $formstyle = 'wide';

            $checkboxtodisplay = array(
                'bluelte_top_bar_left_text'     => 'option_bluelte_enable_top_bar',
                'bluelte_top_bar_right_text'    => 'option_bluelte_enable_top_bar',
                'bluelte_show_top_social_icons' => 'option_bluelte_enable_top_bar',
                'bluelte_banner_head_text'      => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_div1_text'      => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_div1_icon'      => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_div2_text'      => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_div2_icon'      => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_div3_text'      => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_div3_icon'      => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_show_ask_box'   => 'option_bluelte_show_home_page_banner',
                'bluelte_banner_closable'       => 'option_bluelte_show_home_page_banner',
                'bluelte_copyright_text'        => 'option_bluelte_show_copyright_at_footer',
                'bluelte_custom_404_text'       => 'option_bluelte_show_custom_404_page',
            );
			//$this->content['form']=$options;
			
			$this->template = "admin";
			$this->content['navigation']['sub'] = as_admin_sub_navigation();
			$this->content['suggest_next']="";
			$this->content['title']= as_lang_html('admin/admin_title') . ' - ' . as_lang('bluelte/bluelte_theme');
			$this->content['error'] = $securityexpired ? as_lang_html( 'admin/form_security_expired' ) : as_admin_page_error();

			$this->content['script_rel'][] = 'as-content/as-admin.js?' . AS_VERSION;

			$this->content['form'] = array(
				'ok'      => $formokhtml,

				'tags'    => 'method="post" action="' . as_self_html() . '" name="admin_form" onsubmit="document.forms.admin_form.has_js.value=1; return true;"',

				'style'   => $formstyle,

				'fields'  => array(),

				'buttons' => array(
					'save'  => array(
						'tags'  => 'id="dosaveoptions"',
						'label' => as_lang_html( 'admin/save_options_button' ),
					),

					'reset' => array(
						'tags'  => 'name="doresetoptions"',
						'label' => as_lang_html( 'admin/reset_options_button' ),
					),
				),

				'hidden'  => array(
					'dosaveoptions' => '1',
					'has_js'        => '0',
					'code'          => as_get_form_security_code( 'admin/bluelte' ),
				),
			);
			
			$indented = false;

			foreach ( $showoptions as $optionname )
				if ( empty( $optionname ) ) {
					$indented = false;

					$as_content['form']['fields'][] = array(
						'type' => 'blank',
					);

				} elseif ( strpos( $optionname, '/' ) !== false ) {
					$as_content['form']['fields'][] = array(
						'type'  => 'static',
						'label' => as_lang_html( $optionname ),
					);

					$indented = true;

				} else {
					$type = @$optiontype[$optionname];
					if ( $type == 'number-blank' )
						$type = 'number';

					$value = $options[$optionname];

					$optionfield = array(
						'id'    => $optionname,
						'label' => ( $indented ? '&ndash; ' : '' ) . as_lang( 'bluelte/'.$optionname ),
						'tags'  => 'name="option_' . $optionname . '" id="option_' . $optionname . '"',
						'value' => as_html( $value ),
						'type'  => $type,
						'error' => as_html( @$errors[$optionname] ),
					);

					if ( isset( $optionmaximum[$optionname] ) )
						$optionfield['note'] = as_lang_html_sub( 'admin/maximum_x', $optionmaximum[$optionname] );

					$feedrequest = null;
					$feedisexample = false;

					switch ( $optionname ) { // special treatment for certain options

						case 'special_opt': //not using for now
							$optionfield['note'] = bluelte_options_lang_html( $optionname . '_note' );
							break;

					}

					switch ( $optionname ) {
						case 'bluelte_activate_prod_mode':
						case 'bluelte_use_local_font':
						case 'bluelte_top_bar_left_text':
						case 'bluelte_top_bar_right_text':
						case 'bluelte_enable_top_bar':
						case 'bluelte_enable_sticky_header':
						case 'bluelte_enable_back_to_top_btn':
						case 'bluelte_show_home_page_banner':
						case 'bluelte_show_collapsible_btns':
						case 'bluelte_show_breadcrumbs':
						case 'bluelte_show_site_stats_above_footer':
						case 'bluelte_show_social_links_at_footer':
						case 'bluelte_show_copyright_at_footer':
						case 'bluelte_show_custom_404_page':
							$optionfield['style'] = 'tall';
							break;
					}

					$this->content['form']['fields'][$optionname] = $optionfield;
				}


			if ( isset( $checkboxtodisplay ) )
				as_set_display_rules( $this->content, $checkboxtodisplay );
		}
		as_html_theme_base::doctype();
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/