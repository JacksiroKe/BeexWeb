<?php
    if ( !defined( 'AS_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }
	
    @define( 'THEME_DIR', dirname( __FILE__ ) . '/' );
    @define( 'THEME_URL', as_opt('site_url') . 'as-theme/' . as_get_site_theme() . '/' );
    @define( 'THEME_DIR_NAME', basename( THEME_DIR ) ); 
    @define( 'THEME_TEMPLATES', THEME_DIR . 'templates/' );
    @define( 'THEME_VERSION', "1.2" );
	
    require_once 'as-functions.php';
    require_once THEME_DIR . '/options/options.php';
	as_register_layer('as-admin-options.php', 'BlueLTE Theme', THEME_DIR, THEME_URL );
    as_register_phrases( THEME_DIR.'as-bluelte-lang-*.php', 'bluelte' );
	
    class as_html_theme extends as_html_theme_base
    {   
        public $user = null;
        public $handle = 'handle';
        public $fullname = 'Beex User';
        public $userimage = './as-media/user.jpg';
        public $loginlevel = null;

        function doctype()
        {
            require_once AS_INCLUDE_DIR . 'db/users.php';
            if (as_is_logged_in())
            {
                $this->handle = as_get_logged_in_handle();
                $this->loginlevel = as_get_logged_in_level();
                $this->fullname = as_db_name_find_by_handle($this->handle);
                $this->user = as_db_select_with_pending(AS_FINAL_EXTERNAL_USERS ? null : as_db_user_account_selectspec($this->handle, false));
            }
            if ( !property_exists( 'as_html_theme_base', 'isRTL' ) ) {
                $this->isRTL = isset( $this->content['direction'] ) && $this->content['direction'] === 'rtl';
            }
            parent::doctype();
        }

		function head()
        {
            $this->output(
                    "\t<head>",
                    "\t\t".'<meta http-equiv="X-UA-Compatible" content="IE=edge"/>',
					"\t\t".'<meta charset="utf-8">',
                    "\t\t".'<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">'
            );
            $this->head_title();
            $this->head_metas();
            $this->head_css();
            $this->head_links();
            $this->head_lines();
            $this->head_script();
            $this->head_custom();
            $this->output( "\t</head>" );
        }
        
		function head_css()
        {
            parent::head_css();
            $css_paths = array(
                'bootstrap'     => 'bower_components/bootstrap/dist/css/bootstrap.min.css',
                'fonts'         => 'bower_components/font-awesome/css/font-awesome.min.css',
                'ionicons'      => 'bower_components/Ionicons/css/ionicons.min.css',
                'colorpicker'      => 'bower_components/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css',
                'bluelte'       => 'dist/css/BlueLTE.min.css',
                'icheck'       => 'plugins/iCheck/all.css',
                'timepicker'       => 'plugins/timepicker/bootstrap-timepicker.min.css',
                'allskins'      => 'dist/css/skins/_all-skins.min.css',
                'morris'        => 'bower_components/morris.js/morris.css',
                'jvectormap'    => 'bower_components/jvectormap/jquery-jvectormap.css',
                'datepicker'    => 'bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
                'rangepicker'   => 'bower_components/bootstrap-daterangepicker/daterangepicker.css',
                'bootstrap3'    => 'plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css',
                'select2'    => 'bower_components/select2/dist/css/select2.min.css',
                'dataTables'    => 'bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css'
            );
            
            $this->bluelte_resources( $css_paths, 'css' );
            $this->bluelte_resources( array( 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic' ) , 'css' , true );
        }
		
		function head_custom()
        {
            parent::head_custom();
			$this->output("\t\t".'<style>.accordian{position:relative;border-radius:0px;background:#ffffff;border-top:0px;margin-bottom:0px;width:100%;box-shadow:0 0px 0px rgba(0,0,0,0.1)}</style>');
		}
		
        function bluelte_resources( $paths, $type = 'css', $external = false )
        {
            if ( count( $paths ) ) {
                foreach ( $paths as $key => $path ) {
                    if ( $type === 'js' ) {
                        $this->bluelte_js( $path, $external );
                    } else if ( $type === 'css' ) {
                        $this->bluelte_css( $path, $external );
                    }
                }
            }
        }
        
        function bluelte_js( $path, $external = false )
        {
            if ( $external ) {
                $full_path = $path;
            } else {
                $full_path = THEME_URL . $path;
            }
            if ( !empty( $path ) ) {
                $this->output( "\t\t".'<script src="' . $full_path . '" type="text/javascript"></script>' );
            }
        }
        
        function bluelte_css( $path, $external = false )
        {
            if ( $external ) {
                $full_path = $path;
            } else {
                $full_path = THEME_URL . $path;
            }
            if ( !empty( $path ) ) {
                $this->output( "\t\t".'<link rel="stylesheet" type="text/css" href="' . $full_path . '"/>' );
            }
        }
        
        public function body_content()
        {
            if (as_is_logged_in()) {
                $this->output("\t\t".'<div class="wrapper">');
                $this->header();
                $this->sidepanel();
                $this->main();
                $this->footer();
                $this->controls();
                $this->output("\t\t".'</div> <!-- END body-wrapper -->');
            }
            else {
                $this->output("\t\t".'<div class="login-box">');
                $this->output("\t\t\t".'<div class="login-logo">', '<a href=".">'.$this->content['site_title'].'</a>', '</div>');
              
                $this->guest();
              
		        $this->output("\t\t".'</div> <!-- END body-wrapper -->');
            }          
        }

        function body_hidden() 
        {
            parent::body_hidden();
            $js_paths = array(
                    'select2'     => 'bower_components/select2/dist/js/select2.full.min.js',
                    'jquery'     => 'bower_components/jquery/dist/jquery.min.js',
                    'jquery_ui'     => 'bower_components/jquery-ui/jquery-ui.min.js',
                    'bootstrap'     => 'bower_components/bootstrap/dist/js/bootstrap.min.js',
                    'raphael'     => 'bower_components/raphael/raphael.min.js',
                    'morris'     => 'bower_components/morris.js/morris.min.js',
                    'sparkline'     => 'bower_components/jquery-sparkline/dist/jquery.sparkline.min.js',
                    'jvectormap'     => 'plugins/jvectormap/jquery-jvectormap-1.2.2.min.js',
                    'jvectormap_world'     => 'plugins/jvectormap/jquery-jvectormap-world-mill-en.js',
                    'jquery_knob'     => 'bower_components/jquery-knob/dist/jquery.knob.min.js',
                    'moment'     => 'bower_components/moment/min/moment.min.js',
                    'daterangepicker'     => 'bower_components/bootstrap-daterangepicker/daterangepicker.js',
                    'datepicker'     => 'bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
                    'wysihtml5'     => 'plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js',
                    'slimscroll'     => 'bower_components/jquery-slimscroll/jquery.slimscroll.min.js',
                    'fastclick'     => 'bower_components/fastclick/lib/fastclick.js',
                    'bluelte'     => 'dist/js/bluelte.min.js',
                    'demo'     => 'dist/js/demo.js',
                    'dataTables1'     => 'bower_components/datatables.net/js/jquery.dataTables.min.js',
                    'dataTables2'     => 'bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js',
            );
            
            $this->bluelte_resources( $js_paths, 'js' );
        }

        public function footer()
        {
            $this->output( "\t\t\t".'<footer class="main-footer">');
            $this->attribution();
            $this->output("\t\t\t".'</footer>');
        }
        
        public function attribution()
        {
            $this->output("\t\t\t\t".'<div class="pull-right hidden-xs">BeExpress <b>Version</b> 2.4.13 </div>'.
            '<strong>Copyright &copy; '.date('Y').' <a href="https://www.appsmata.com">AppSmata</a>.</strong>All rights reserved.'
            );
        }

        public function useraccount()
        {
            $this->output('<li class="dropdown user user-menu">');
            $this->output("\t\t".'<a href="#" class="dropdown-toggle" data-toggle="dropdown">'.as_avatar(20, 'user image', $this->user));
            $this->output("\t\t".'<span class="hidden-xs">'.$this->fullname.'</span>', '</a>');
            $this->output("\t\t".'<ul class="dropdown-menu">', '<li class="user-header">'.as_avatar(50, 'user image', $this->user));

            $gender = $this->user['gender'] == 1 ? ' ('.as_lang('users/gender_male').')' : ' ('.as_lang('users/gender_female').')';
            $this->output("\t\t".'<p>'.$this->fullname.$gender.' - '. AS_USER_TYPE_FULL);
            $this->output("\t\t".'<br>'.$this->user['mobile']. ', '. $this->user['country']);

            $usertime = as_time_to_string(as_opt('db_time') - $this->user['created']);
            $joindate = as_when_to_html($this->user['created'], 0);
            
            $this->output("\t\t".'<small>'.as_lang_html('users/user_for').' '.$usertime . ' (' . as_lang_sub('main/since_x', $joindate['data']) . ')</small>');
            $doconfirms = as_opt('confirm_user_emails') && $this->user['level'] < AS_USER_LEVEL_EXPERT;
            $isconfirmed = ($this->user['flags'] & AS_USER_FLAGS_EMAIL_CONFIRMED) > 0;
            $htmlemail = as_html(isset($inemail) ? $inemail : $this->user['email']);
            
            $this->output("\t\t".'<small>'.$htmlemail.''.($doconfirms ? (as_lang_html($isconfirmed ? 'users/email_confirmed' : 'users/email_not_confirmed') . 
                ' ') : '').'</small>');
    
            $this->output("\t\t".'</p>', '</li>');
            $this->output("\t\t".'<li class="user-body">', '<div class="row">');
            $this->output("\t\t".'<div class="col-xs-4 text-center">', '<a href="#">Link #1</a>', '</div>'); 
            $this->output("\t\t".'<div class="col-xs-4 text-center">', '<a href="#">Link #2</a>', '</div>'); 
            $this->output("\t\t".'<div class="col-xs-4 text-center">', '<a href="#">Link #3</a>', '</div>');                
            $this->output("\t\t".'</div>', '</li>');
            
            $this->output("\t\t".'<li class="user-footer">');
            $this->output("\t\t".'<div class="pull-left">', '<a href="'. as_opt('site_url') . 'user/'.$this->user['handle'].'" 
				class="btn btn-default btn-flat">Profile</a>', '</div>');
            $this->output("\t\t".'<div class="pull-right">', 
                '<a href="'. as_opt('site_url') . 'signout" class="btn btn-default btn-flat">Sign out</a>', 
            '</div>');
            $this->output("\t\t".'</li>', '</ul>', '</li>');
        }

        public function header()
        {
            $this->output("\t\t".'<header class="main-header">');
            $this->output("\t\t\t".'<a href="." class="logo">');
            $this->output("\t\t\t\t".'<span class="logo-mini"><b>Be</b>Ex</span>');
            $this->output("\t\t\t\t".'<span class="logo-lg"><b>Be</b>Express</span>');
            $this->output("\t\t\t".'</a>');
            $this->output("\t\t\t".'<nav class="navbar navbar-fixed-top" role="navigation">');
            $this->output("\t\t\t\t".'<a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">');
            $this->output("\t\t\t\t\t".'<span class="sr-only">Toggle navigation</span>');
            $this->output("\t\t\t\t".'</a>');
                
            $this->output("\t\t\t".'<div class="navbar-custom-menu">', '<ul class="nav navbar-nav">');
            $this->messages();
            $this->notifications();    
            $this->tasks();    
            $this->useraccount();
            $this->output("\t\t\t".'<li>', '<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>', '</li>');
            $this->output("\t\t\t\t\t".'</ul>', "\t\t\t\t".'</div>', "\t\t\t".'</nav>');
            $this->output("\t\t".'</header>');
        }

        public function sidepanel()
        {
            $this->output("\t\t".'<aside class="main-sidebar">');
            $this->output("\t\t".'<section class="sidebar">');
            $this->output("\t\t".'<div class="user-panel">');
            $this->output("\t\t".'<div class="pull-left image">', as_avatar(30, 'profile-user-img img-responsive', $this->user), '</div>');
            $this->output("\t\t".'<div class="pull-left info">');
            $this->output("\t\t".'<p>' . $this->fullname . '</p>');
            $this->output("\t\t".'<a href="#"><i class="fa fa-circle text-success"></i> '.AS_USER_TYPE_FULL.'</a>');
            $this->output("\t\t".'</div>', '</div>');

            $this->output("\t\t".'<form action="#" method="get" class="sidebar-form">');
            $this->output("\t\t".'<div class="input-group">', 
                '<input type="text" name="q" class="form-control" placeholder="Search...">',
                '<span class="input-group-btn">');
            $this->output("\t\t".'<button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>');
            $this->output("\t\t".'</button>', '</span>', '</div>', '</form>');
            $this->output("\t\t".'<ul class="sidebar-menu" data-widget="tree">');
            $this->output("\t\t".'<li class="header">MAIN NAVIGATION</li>');

            $this->nav_main_sub();

            $this->output("\t\t".'</ul>');

            $this->output("\t\t".'</section>', '</aside>');
        }

        public function controls()
        {
            $this->output("\t\t".'<aside class="control-sidebar control-sidebar-dark">
              <!-- Create the tabs -->
              <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
                <li class="active"><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
                <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
              </ul>
              <!-- Tab panes -->
              <div class="tab-content">
                <!-- Home tab content -->
                <div class="tab-pane active" id="control-sidebar-home-tab">
                  <h3 class="control-sidebar-heading">Recent Activity</h3>
                  <ul class="control-sidebar-menu">
                    <li>
                      <a href="javascript:;">
                        <i class="menu-icon fa fa-birthday-cake bg-red"></i>
          
                        <div class="menu-info">
                          <h4 class="control-sidebar-subheading">Langdons Birthday</h4>
          
                          <p>Will be 23 on April 24th</p>
                        </div>
                      </a>
                    </li>
                  </ul>
                  <!-- /.control-sidebar-menu -->
          
                  <h3 class="control-sidebar-heading">Tasks Progress</h3>
                  <ul class="control-sidebar-menu">
                    <li>
                      <a href="javascript:;">
                        <h4 class="control-sidebar-subheading">
                          Custom Template Design
                          <span class="pull-right-container">
                              <span class="label label-danger pull-right">70%</span>
                            </span>
                        </h4>
          
                        <div class="progress progress-xxs">
                          <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
                        </div>
                      </a>
                    </li>
                  </ul>
                  <!-- /.control-sidebar-menu -->
          
                </div>
                <!-- /.tab-pane -->
                <!-- Stats tab content -->
                <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>
                <!-- /.tab-pane -->
                <!-- Settings tab content -->
                <div class="tab-pane" id="control-sidebar-settings-tab">
                  <form method="post">
                    <h3 class="control-sidebar-heading">General Settings</h3>
          
                    <div class="form-group">
                      <label class="control-sidebar-subheading">
                        Report panel usage
                        <input type="checkbox" class="pull-right" checked>
                      </label>
          
                      <p>
                        Some information about this general settings option
                      </p>
                    </div>
                    <!-- /.form-group -->
                  </form>
                </div>
                <!-- /.tab-pane -->
              </div>
            </aside>');
        }

    }
/*
	Omit PHP closing tag to help avoid accidental output
*/