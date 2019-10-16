<?php
    if ( !defined( 'AS_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

	function site_navigation()
	{
		switch (AS_USER_TYPE) {
			case 'SA': return admin_navigation();
				
			case 'A': return admin_navigation();
			
			case 'ENR':
				break;	
			
			case 'ER':
				break;	
			
			case 'SSP':
				break;	
			
			case 'MSP':
				break;	
			
			case 'BC': return bc_navigation();
			
			case 'IC':
				break;	
			
			case 'BS': return bs_navigation();	
			
			case 'IS': return is_navigation();		
			
			default: return user_navigation();
		}
	}
	
	function user_navigation()
	{
		$navigation = array(
			'home' => array(
				'label' => as_lang_html('main/nav_home'),
				'url' => as_path_html(''),
				'icon' => 'fa fa-dashboard',
			),
			
			'sell' => array(
				'label' => as_lang_html('main/nav_sell'),
				'url' => as_path_html('products'),
				'icon' => 'fa fa-dashboard',
			),
		);
		
		return $navigation;	
	}
	
	function bc_navigation()
	{
		$navigation = array();
		$navigation['home'] = array(
				'label' => as_lang_html('main/nav_home'),
				'url' => as_path_html(''),
				'icon' => 'fa fa-dashboard',
			);
			
		$navigation['orders'] = array(
				'label' => as_lang_html('main/nav_orders'),
				'url' => as_path_html('orders'),
				'icon' => 'fa fa-dashboard',
				'sub' => array(
					'recent' => array(
						'label' => as_lang_html('main/all_my_orders'),
						'url' => as_path_html('orders'),
						'icon' => 'fa fa-cog',
					),
			
				),
			);
		
		return $navigation;	
	}
	
	function bs_navigation()
	{
		$navigation = array();
		$navigation['home'] = array(
				'label' => as_lang_html('main/nav_home'),
				'url' => as_path_html(''),
				'icon' => 'fa fa-dashboard',
			);
			
		$navigation['products'] = array(
				'label' => as_lang_html('main/nav_products'),
				'url' => as_path_html('products'),
				'icon' => 'fa fa-dashboard',
				'sub' => array(
					'recent' => array(
						'label' => as_lang_html('main/all_my_products'),
						'url' => as_path_html('products'),
						'icon' => 'fa fa-cog',
					),
			
				),
			);
			
		$navigation['orders'] = array(
				'label' => as_lang_html('main/nav_orders'),
				'url' => as_path_html('orders'),
				'icon' => 'fa fa-dashboard',
				'sub' => array(
					'recent' => array(
						'label' => as_lang_html('main/all_my_orders'),
						'url' => as_path_html('orders'),
						'icon' => 'fa fa-cog',
					),
			
				),
			);
		
		return $navigation;	
	}
	
	function is_navigation()
	{
		$navigation = array();
		$navigation['home'] = array(
				'label' => as_lang_html('main/nav_home'),
				'url' => as_path_html(''),
				'icon' => 'fa fa-dashboard',
			);
			
		$navigation['products'] = array(
				'label' => as_lang_html('main/nav_products'),
				'url' => as_path_html('products'),
				'icon' => 'fa fa-dashboard',
				'sub' => array(
					'recent' => array(
						'label' => as_lang_html('main/all_my_products'),
						'url' => as_path_html('products'),
						'icon' => 'fa fa-cog',
					),
			
				),
			);
			
		$navigation['orders'] = array(
				'label' => as_lang_html('main/nav_orders'),
				'url' => as_path_html('orders'),
				'icon' => 'fa fa-dashboard',
				'sub' => array(
					'recent' => array(
						'label' => as_lang_html('main/all_my_orders'),
						'url' => as_path_html('orders'),
						'icon' => 'fa fa-cog',
					),
			
				),
			);
		
		return $navigation;	
	}
