<?php

    $userid = as_get_logged_in_userid();
    if ( !defined( 'AS_WORDPRESS_INTEGRATE_PATH' ) ) {
        $useraccount = as_db_select_with_pending( as_db_user_account_selectspec( $userid, true ) );
    }

    $logged_in_user_avatar = bluelte_get_user_avatar( $userid, 30 );

    if ( isset( $this->content['navigation']['user']['updates'] ) ) {
        $this->content['navigation']['user']['updates']['icon'] = 'bell-o';
    }

?>
<ul class="nav navbar-nav navbar-right user-nav">
    <?php if (as_opt('q2apro_onsitenotifications_enabled') && !empty($this->content['signedin']['suffix'])): ?>
    <li class="notf-bubble visible-lg">
        <?php echo $this->content['signedin']['suffix'] ?>
    </li>
    <?php endif ?>
    <li class="dropdown user-dropdown">
        <a href="#" class="navbar-user-img dropdown-toggle" data-toggle="dropdown">
            <?php echo $logged_in_user_avatar; ?>
        </a>
        <ul class="dropdown-menu" role="menu" id="user-dropdown-menu">
            <li class="dropdown-header">Signed in as <?php echo as_get_logged_in_handle(); ?></li>
            <?php if ( as_get_logged_in_level() >= AS_USER_LEVEL_ADMIN ): ?>
                <li class="dropdown-header">Admin Section</li>
                <li>
                    <a href="<?php echo as_path_html( 'admin' ) ?>">
                        <span class="fa fa-cog"></span>
                        <?php echo as_lang_html( 'main/nav_admin' ); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo as_path_html( 'admin/bluelte-theme/general-settings' ) ?>">
                        <span class="fa fa-wrench"></span>
                        <?php echo as_lang( 'bluelte/bluelte_theme_settings' ); ?>
                    </a>
                </li>
                <li class="dropdown-header">Profile Section</li>
            <?php endif ?>
            <li>
                <a href="<?php echo as_path_html( as_get_logged_in_handle() ); ?>">
                    <span class="fa fa-user"></span>
                    <?php echo as_get_logged_in_handle(); ?>
                </a>
            </li>
            <?php if ( !defined( 'AS_WORDPRESS_INTEGRATE_PATH' ) ): ?>
                <?php if ( as_opt( 'allow_private_messages' ) && !( $useraccount['flags'] & AS_USER_FLAGS_NO_MESSAGES ) ): ?>
                    <li>
                        <a href="<?php echo as_path_html( 'messages' ) ?>">
                            <span class="fa fa-envelope"></span>
                            <?php echo as_lang_html( 'misc/nav_user_pms' ) ?>
                        </a>
                    </li>
                <?php endif ?>
                <li>
                    <a href="<?php echo as_path_html( 'user/' . as_get_logged_in_handle() ); ?>">
                        <span class="fa fa-money"></span>
                        <?php echo as_get_logged_in_points() . ' ' . as_lang_html( 'admin/points_title' ) ?>
                    </a>
                </li>
                <?php foreach ( $this->content['navigation']['user'] as $key => $user_nav ): ?>
                    <?php if ( $key !== 'logout' ): ?>
                        <li>
                            <a href="<?php echo @$user_nav['url']; ?>">
                                <?php if ( !empty( $user_nav['icon'] ) ): ?>
                                    <span class="fa fa-<?php echo $user_nav['icon']; ?>"></span>
                                <?php endif ?>
                                <?php echo @$user_nav['label']; ?>
                            </a>
                        </li>
                    <?php endif ?>
                <?php endforeach ?>
                <li>
                    <a href="<?php echo @$this->content['navigation']['user']['logout']['url'] ?>">
                        <span class="fa fa-sign-out"></span>
                        <?php echo @$this->content['navigation']['user']['logout']['label'] ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </li>
</ul>
