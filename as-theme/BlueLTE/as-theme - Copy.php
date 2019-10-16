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
        public $handle = 'handle';
        public $fullname = 'Beex User';

        function doctype()
        {
            $this->handle = as_get_logged_in_handle();
            $this->fullname = as_db_name_find_by_handle($this->handle);
            
            if ( !property_exists( 'as_html_theme_base', 'isRTL' ) ) {
                $this->isRTL = isset( $this->content['direction'] ) && $this->content['direction'] === 'rtl';
            }
            parent::doctype();
        }

        public function as_avatar($size, $class = null)
        { 
            $b_rad = 0.75 * $size;
            $avatar = '<img src="./as-media/user.jpg" width="'.$size.'" height="'.$size.'"';
            if (isset($class))  $avatar .= ' class="'.$class.'" alt="User Image"/>';
            else $avatar .= ' style="border-radius: '.$b_rad.'px" alt="User Image"/>';

            if (as_is_logged_in())
            {
                $user = as_db_select_with_pending(AS_FINAL_EXTERNAL_USERS ? null : as_db_user_account_selectspec(as_get_logged_in_handle(), false));
                $hasavatar = as_get_user_avatar_html($user['flags'], $user['email'], $user['handle'], $user['avatarblobid'], $size, $size, $size);
            }
            //$hasavatar ? $hasavatar  : $asavatar 
            return $hasavatar ? $hasavatar  : $avatar;
        }
        
		function head()
        {
            $this->output(
                    '<head>',
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge"/>',
					'<meta charset="utf-8">',
                    '<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">'
            );
            $this->head_title();
            $this->head_metas();
            $this->head_css();
            $this->head_links();
            $this->head_lines();
            $this->head_script();
            $this->head_custom();
            $this->output( '</head>' );
        }
        
		function head_css()
        {
            parent::head_css();
            $css_paths = array(
                'bootstrap'     => 'bower_components/bootstrap/dist/css/bootstrap.min.css',
                'fonts'         => 'bower_components/font-awesome/css/font-awesome.min.css',
                'ionicons'      => 'bower_components/Ionicons/css/ionicons.min.css',
                'bluelte'       => 'dist/css/BlueLTE.min.css',
                'allskins'      => 'dist/css/skins/_all-skins.min.css',
                'morris'        => 'bower_components/morris.js/morris.css',
                'jvectormap'    => 'bower_components/jvectormap/jquery-jvectormap.css',
                'datepicker'    => 'bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
                'rangepicker'   => 'bower_components/bootstrap-daterangepicker/daterangepicker.css"',
                'bootstrap3'    => 'plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css"'
            );
            
            $this->bluelte_resources( $css_paths, 'css' );
            $this->bluelte_resources( array( 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic' ) , 'css' , true );
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
                $this->output( '<script src="' . $full_path . '" type="text/javascript"></script>' );
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
                $this->output( '<link rel="stylesheet" type="text/css" href="' . $full_path . '"/>' );
            }
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
            $this->output('class="' . $class . ' as-body-js-off hold-transition skin-yellow sidebar-mini"');
        }
        
        public function body_contentx()
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

            $this->output('<div class="control-sidebar-bg"></div>');
            $this->output('</div> <!-- END body-wrapper -->');

            $this->body_suffix();
        }

        public function body_content()
        {
            $this->output('<div class="wrapper">');
            $this->header();
            $this->sidepanel();
            $this->output('<div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
            <h1>
            Dashboard
            <small>Control panel</small>
            </h1>
            <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Dashboard</li>
            </ol>
            </section>

            <!-- Main content -->
            <section class="content">
           
            </section>
            <!-- right col -->
            </div>
            <!-- /.row (main row) -->

            </section>
            <!-- /.content -->
            </div> <!-- /.content-wrapper -->');
            
            $this->footer();
            $this->control_panel();
            $this->output('</div>');
        }

        public function control_panel()
        {

        }

        public function header()
        {
            $this->output('<header class="main-header">');
            $this->output('<a href="." class="logo">',
                '<span class="logo-mini"><b>Be</b>Ex</span>',
                '<span class="logo-lg"><b>Be</b>Express</span>',
            '</a>');
            $this->output('<nav class="navbar navbar-static-top">',
                '<a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">',
                '<span class="sr-only">Toggle navigation</span></a>');

            $this->output('<div class="navbar-custom-menu">', '<ul class="nav navbar-nav">');
            //$this->messages();
            //$this->notifications();
            //$this->tasks();
            $this->useraccount();
            $this->output('</ul>', '</div>', '</nav>', '</header>');
        }

        public function useraccount()
        {
            $this->output('<li class="dropdown user user-menu">');
            $this->output('<a href="#" class="dropdown-toggle" data-toggle="dropdown">'.$this->as_avatar(0, "user-image"));
            $this->output('<span class="hidden-xs">'.$this->fullname.'</span>', '</a>');
            $this->output('<ul class="dropdown-menu">', '<li class="user-header">'.$this->as_avatar(0, "img-circle"));

            $this->output('<p>'.$this->fullname.' - Admin
            <small>Member since Nov. 2012</small>
            </p>
            </li>
            <!-- Menu Body -->
            <li class="user-body">
            <div class="row">
            <div class="col-xs-4 text-center">
            <a href="#">Followers</a>
            </div>
            <div class="col-xs-4 text-center">
            <a href="#">Sales</a>
            </div>
            <div class="col-xs-4 text-center">
            <a href="#">Friends</a>
            </div>
            </div>
            <!-- /.row -->
            </li>
            <!-- Menu Footer-->
            <li class="user-footer">
            <div class="pull-left">
            <a href="#" class="btn btn-default btn-flat">Profile</a>
            </div>
            <div class="pull-right">
            <a href="#" class="btn btn-default btn-flat">Sign out</a>
            </div>
            </li>
            </ul>
            </li>');
        }

        public function sidepanel()
        {
            /*$this->output('<aside class="main-sidebar">', '<section class="sidebar">');            
            $this->sidepanel_user();
            $this->sidepanel_search();
            $this->sidepanel_menu();
            $this->output('</section>', '</aside>');*/
            $this->output('<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

      <!-- Sidebar user panel (optional) -->
      <div class="user-panel">
        <div class="pull-left image">
          <img src="dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
          <p>Alexander Pierce</p>
          <!-- Status -->
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>

      <!-- search form (Optional) -->
      <form action="#" method="get" class="sidebar-form">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Search...">
          <span class="input-group-btn">
              <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
              </button>
            </span>
        </div>
      </form>
      <!-- /.search form -->

      <!-- Sidebar Menu -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">HEADER</li>
        <!-- Optionally, you can add icons to the links -->
        <li class="active"><a href="#"><i class="fa fa-link"></i> <span>Link</span></a></li>
        <li><a href="#"><i class="fa fa-link"></i> <span>Another Link</span></a></li>
        <li class="treeview">
          <a href="#"><i class="fa fa-link"></i> <span>Multilevel</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="#">Link in level 2</a></li>
            <li><a href="#">Link in level 2</a></li>
          </ul>
        </li>
      </ul>
      <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Page Header
        <small>Optional description</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content container-fluid">

      <!--------------------------
        | Your Page Content Here |
        -------------------------->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="pull-right hidden-xs">
      Anything you want
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; 2016 <a href="#">Company</a>.</strong> All rights reserved.
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
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
        
        private function sidepanel_user()
        {
            $this->output('<div class="user-panel">');
            
            $avatar = '<img src="./as-media/user_default.jpg" width="20" height="20" style="border-radius: 75%;"/>';

            $this->output('<div class="pull-left image">', $this->as_avatar(30), '</div>');
            $this->output('<div class="pull-left info">');
            
            $this->output('<p>' . $this->fullname . '</p>');
            $this->output('<a href="#"><i class="fa fa-circle text-success"></i> Admin</a>');
            $this->output('</div>', '</div>');
        }

        public function sidepanel_search()
        {
            $this->output('<form action="#" method="get" class="sidebar-form">');
            $this->output('<div class="input-group">', 
                '<input type="text" name="q" class="form-control" placeholder="Search...">',
                '<span class="input-group-btn">');
            $this->output('<button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>');
            $this->output('</button>', '</span>', '</div>', '</form>');
        }

        public function sidepanel_menu()
        {
            $this->output('<ul class="sidebar-menu" data-widget="tree">');
            $this->output('<li class="header">MAIN NAVIGATION</li>');

            foreach ( $this->content['navigation']['main'] as $key => $nav_item ) {
                $this->output('<li class="treeview">', '<a href="#">
                <i class="fa fa-dashboard"></i> <span> ' . $nav_item['label'] . ' </span>');
                $this->output('<span class="pull-right-container">', 
                '<i class="fa fa-angle-left pull-right"></i>', '</span>', '</a>');
                $this->output('<ul class="treeview-menu">');
                    $this->output('<li><a href=" ' . $nav_item['url'] . ' ">');
                    $this->output('<i class="fa fa-circle-o"></i> ' . $nav_item['label'] . ' </a>');
                    $this->output('</li>');
                $this->output('</ul>');
                $this->output('</li>');
            }
            $this->output('</ul>');
        }

        function body_footer() 
        {
            $js_paths = array(
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
                    'dashboard'     => 'dist/js/pages/dashboard.js',
                    'demo'     => 'dist/js/demo.js'
            );
            
            $this->bluelte_resources( $js_paths, 'js' );
        }

        public function footer()
        {
            $this->output( '<footer class="main-footer">');
            $this->attribution();
            $this->output('</footer>');
        }
        
        public function attribution()
        {
            $this->output('<div class="pull-right hidden-xs">BeExpress <b>Version</b> 2.4.13 </div>',
            '<strong>Copyright &copy; 2019 <a href="https://appsmata.github.io">AppSmata</a>.</strong>',
            'All rights reserved.'
            );
        }

    }
/*
	Omit PHP closing tag to help avoid accidental output
*/