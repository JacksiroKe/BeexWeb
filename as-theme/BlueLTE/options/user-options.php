<?php
    if ( !defined( 'AS_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

    return array(
        'key' => 'value',
    );