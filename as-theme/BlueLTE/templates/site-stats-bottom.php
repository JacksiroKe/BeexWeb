<div class="bluelte-site-stats-bottom">
    <div class="container">
        <div class="row">
            <div class="stats-wrap">
                <?php
                    bluelte_stats_output( as_opt( 'cache_qcount' ), 'main/1_question', 'main/x_questions' );
                    bluelte_stats_output( as_opt( 'cache_acount' ), 'main/1_answer', 'main/x_answers' );

                    if ( as_opt( 'comment_on_qs' ) || as_opt( 'comment_on_as' ) )
                        bluelte_stats_output( as_opt( 'cache_ccount' ), 'main/1_comment', 'main/x_comments' );

                    bluelte_stats_output( as_opt( 'cache_userpointscount' ), 'main/1_user', 'main/x_users' );
                ?>
            </div>
        </div>
    </div>
</div>