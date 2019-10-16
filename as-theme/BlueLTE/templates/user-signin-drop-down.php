<ul class="nav navbar-nav navbar-right login-nav">
    <li class="dropdown login-dropdown login active">
        <a href="#" data-toggle="dropdown" class="navbar-login-button">
            <span class="fa fa-sign-in text-muted"></span>
        </a>
        <ul class="dropdown-menu" role="menu" id="login-dropdown-menu">
            <?php
                if ( !empty( $this->content['navigation']['user'] ) ) {
                    $this->output( '<li class="open-login-buttons">' );
                    foreach ( $this->content['navigation']['user'] as $k => $custom ) {
                        if ( ( $k != 'login' ) && ( $k != 'register' ) ) {

                            if ( $k == 'facebook-login' ) {
                                //for the default facebook login plugin
                                $this->output( '<div class="text-center">' );
                                $this->output( $custom['label'] );
                                $this->output( '</div>' );
                                continue;
                            }

                            //support for open login plugin
                            $icon = '';
                            preg_match( '/class="([^"]+)"/', @$custom['label'], $class );

                            if ( $k == 'facebook' )
                                $icon = 'class="' . @$class[1] . ' fa fa-facebook"';
                            elseif ( $k == 'github' )
                                $icon = 'class="' . @$class[1] . ' fa fa-github"';
                            elseif ( $k == 'foursquare' )
                                $icon = 'class="' . @$class[1] . ' fa fa-foursquare"';
                            elseif ( $k == 'google' )
                                $icon = 'class="' . @$class[1] . ' fa fa-google"';
                            elseif ( $k == 'googleplus' )
                                $icon = 'class="' . @$class[1] . ' fa fa-google-plus"';
                            elseif ( $k == 'live' )
                                $icon = 'class="' . @$class[1] . ' fa fa-windows"';
                            elseif ( $k == 'tumblr' )
                                $icon = 'class="' . @$class[1] . ' fa fa-tumblr"';
                            elseif ( $k == 'yahoo' )
                                $icon = 'class="' . @$class[1] . ' fa fa-yahoo"';
                            elseif ( $k == 'twitter' )
                                $icon = 'class="' . @$class[1] . ' fa fa-twitter"';
                            elseif ( $k == 'linkedin' )
                                $icon = 'class="' . @$class[1] . ' fa fa-linkedin"';
                            elseif ( $k == 'vk' )
                                $icon = 'class="' . @$class[1] . ' fa fa-vk"';

                            $pattern = "/_(?=[^>]*<)/";

                            $custom['label'] = preg_replace( $pattern, $icon, $custom['label'] );
                            $this->output( str_replace( @$class[0], @$icon, @$custom['label'] ) );
                        }
                    }
                    $this->output( '</li>' );
                }
            ?>
            <?php if ( isset( $this->content['navigation']['user'] ) && count( $this->content['navigation']['user'] ) > 2 ): ?>
                <li>
                    <div class="login-or">
                        <hr class="hr-or colorgraph">
                        <span class="span-or">or</span>
                    </div>
                </li>
            <?php endif ?>
            <form role="form" action="<?php echo $this->content['navigation']['user']['login']['url']; ?>"
                  method="post">
                <li>
                    <label>
                        <?php echo trim( as_lang_html( 'users/email_handle_label' ), ':' ); ?>
                    </label>
                    <input type="text" class="form-control" id="as-userid" name="emailhandle"
                           placeholder="<?php echo trim( as_lang_html( 'users/email_handle_label' ), ':' ); ?>"/>
                </li>

                <li>
                    <label>
                        <?php echo trim( as_lang_html( 'users/password_label' ), ':' ); ?>
                    </label>
                    <input type="password" class="form-control" id="as-password" name="password"
                           placeholder="<?php echo trim( as_lang_html( 'users/password_label' ), ':' ); ?>"/>
                </li>
                <li>
                    <label class="checkbox inline">
                        <input type="checkbox" name="reuser" id="as-reuserme"
                               value="1"> <?php echo as_lang_html( 'users/reuser' ); ?>
                    </label>
                </li>
                <li class="hidden">
                    <input type="hidden" name="code"
                           value="<?php echo as_html( as_get_form_security_code( 'login' ) ); ?>"/>
                </li>
                <li>
                    <button type="submit" value="" id="as-login" name="dologin" class="btn btn-primary btn-block">
                        <?php echo $this->content['navigation']['user']['login']['label']; ?>
                    </button>
                </li>
                <li class="forgot-password">
                    <a href="<?php echo as_path_html( 'register' ); ?>"><?php echo as_lang_html( 'users/register_button' ); ?></a>
                    |
                    <a href="<?php echo as_path_html( 'forgot' ); ?>"><?php echo as_lang_html( 'users/forgot_link' ) ?></a>
                </li>
            </form>
        </ul>
    </li>
</ul>
<?php
    unset( $this->content['navigation']['user']['login'] );
    unset( $this->content['navigation']['user']['register'] );
