<?php
    if ( !defined( 'AS_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }
    /**
     * This file will contain all the option names we are going to use in out theme
     */

    if ( !class_exists( 'BlueLTE_Option_Keys' ) ) {
        class BlueLTE_Option_Keys
        {
            const THEME_VERSION = 'bluelte_theme_ver';
            const INSTALLED_THEME_VERSION = 'bluelte_theme_ver_instaled';
            const CDN_ENABLED = 'bluelte_cdn_active';
            const BS_CSS_CDN = '//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css';
            const BS_THEME_CSS_CDN = '//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css';
            const FA_CDN = '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css';
            const BS_JS_CDN = '//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js';
        }
    }

    /**
     * Class BlueLTE_Options
     */
    class BlueLTE_Options
    {
        /**
         * @var
         */
        protected static $instance;

        protected $config;
        protected $systemConfig;
        protected $userConfig;

        /**
         * @return BlueLTE_Options
         */
        public static function getInstance()
        {
            return isset( self::$instance ) ? self::$instance : self::$instance = new self();
        }

        /**
         * Constructor function
         */
        final private function __construct()
        {
            self::init();
        }

        protected function init()
        {
            $this->systemConfig = require 'system-defaults-options.php';
            $this->userConfig = require 'user-options.php';

            $this->config = array_merge( $this->systemConfig, $this->userConfig );
        }

        public function getConfig( $key )
        {
            return isset( $this->config[ $key ] ) ? $this->config[ $key ] : '';
        }
    }

    /**
     *
     * Reads the configuration file
     *
     * @param $key
     *
     * @return string
     *
     * @deprecated
     */
    function bluelte_opt( $key )
    {
        return BlueLTE_Options::getInstance()->getConfig( strtolower($key) );
    }
