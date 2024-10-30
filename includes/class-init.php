<?php

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'WPOffice' ) ) {

    /**
     * Main WPOffice Class
     *
     * @class WPOffice
     * @version    1.0.0
     *
     * @method WPOffice_Invoicing Invoicing()
     * @method WPOffice_Role_Customizer Role_Customizer()
     */
    final class WPOffice extends WPOffice_Functions {

        /**
         * @var WPOffice The single instance of the class
         * @since 1.0
         */
        protected static $_instance = null;

        /**
         * @var string plugin prefix
         */
        public $prefix = 'wpoffice';

        /**
         * @var string plugin dir path
         */
        public $plugin_dir;

        /**
         * @var string plugin URL
         */
        public $plugin_url;

        /**
         * @var string uploads DIR
         */
        public $upload_dir;


        /**
         * @var string queued JS
         */
        public $queued_js;


        /**
         * @var array Post Types
         */
        public $private_page_types;


        /**
         * @var array all plugin classes
         */
        private $classes = array();


        /**
         * @var object plugin features list
         */
        public $features;


        /**
         * @var array with plugin info
         */
        public $plugin = array();

        /**
         * @var array with flags of plugin
         */
        public $wpo_flags = array();

        /**
         * @var bool permalinks Enabled
         */
        public $permalinks = false;

        /**
         * Main WPOffice Instance
         *
         * Ensures only one instance of WPOffice is loaded or can be loaded.
         *
         * @since 1.0
         * @static
         * @see WO()
         * @return WPOffice - Main instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
                self::$_instance->_wpo_construct();
            }
            return self::$_instance;
        }

        /**
         * Create plugin classes - not sure if it needs!!!!!!!!!!!!!!!
         *
         * @since 1.0
         * @see WO()
         */
        public function __call( $name, array $params ) {

            if ( empty( $this->classes[ $name ] ) ) {
                    $this->classes[ $name ] = apply_filters( 'wpoffice_call_object_' . $name, false );
            }

            return $this->classes[ $name ];

            }



        public function set_class( $class_name, $instance = false ) {
            if ( empty( $this->classes[$class_name] ) ) {
                $class = 'WPOffice_' . $class_name;
                if ( $instance ) {
                    $this->classes[$class_name] = $class::instance();
                } else {
                    $this->classes[$class_name] = new $class;
                }

            }
        }

        /**
         * Cloning is forbidden.
         * @since 1.0
         */
        public function __clone() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WP_OFFICE_TEXT_DOMAIN ), '1.0' );
        }

        /**
         * Unserializing instances of this class is forbidden.
         * @since 1.0
         */
        public function __wakeup() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WP_OFFICE_TEXT_DOMAIN ), '1.0' );
        }

        /**
         * WPOffice Empty Constructor.
         */
        private function __construct() {
            //do not write here anything - use _wpo_construct()
        }

        /**
         * WPOffice Constructor.
         *
         * @return void
         */
        private function _wpo_construct() {

            $this->plugin_url = untrailingslashit( plugins_url( '/', WP_OFFICE_FILE ) ) . '/';
            $this->plugin_dir = untrailingslashit( plugin_dir_path( WP_OFFICE_FILE ) ) . '/';

            spl_autoload_register( array( $this, 'wpo__autoloader' ) );

            //set plugin data
            $this->_set_plugin_info();

            //include our files
            $this->includes();

            register_deactivation_hook( $this->plugin_dir . 'wp-office.php',  array( &$this, 'deactivation' ) );


            add_action( 'wp_loaded', array( &$this, 'register_scripts' ) );
            add_action( 'rest_api_init', array( &$this, 'rest_api_init' ) );
            

            if ( $this->is_request( 'admin' ) ) {
                add_action( 'init', array( &$this, 'activation' ), 1 );
                
            }

            if ( ! $this->is_request( 'ajax' ) ) {
                add_filter( 'wp_loaded', array( &$this, 'maybe_flush_rewrite_rules' ) );
            }

            if ( get_option( 'permalink_structure' ) )
                $this->permalinks = true;

            load_plugin_textdomain( WP_OFFICE_TEXT_DOMAIN, false, $this->plugin_dir . 'languages/' );

            

            /*wpo_hook_
                hook_name: wpoffice_loaded
                hook_title: WP Office loaded
                hook_description: Hook runs after include office files and added actions.
                hook_type: action
                hook_in: wp-office
                hook_location class-init.php
                hook_param:
                hook_since: 1.0.0
            */
            do_action( 'wpoffice_loaded' );
        }


        /**
         * auto load function
         *
         * @param string $class
         */
        function wpo__autoloader( $class ) {
            if( strpos( $class, 'wpo' ) === 0 ) {
                $array = explode( '\\', strtolower( $class ) );
                $array[ count( $array ) - 1 ] = 'class-'. end( $array );
                if ( strpos( $class, 'wpo_ext' ) === 0 ) {
                    $full_path = rtrim( $this->plugin_dir, '/' ) . '-' . str_replace( '_', '-', $array[1] ) . '/includes/';
                    unset( $array[0], $array[1] );
                    $path = implode( DIRECTORY_SEPARATOR, $array );
                    $path = str_replace( '_', '-', $path );
                    $full_path .= $path . '.php';
                } else {
                    $class = implode( '\\', $array );
                    $slash = DIRECTORY_SEPARATOR;
                    $path = str_replace(
                            array( 'wpo\\', '_', 'wpofeatures\\', '\\' ),
                            array( $slash, '-', $slash . 'features' . $slash, $slash ),
                            $class );
                    $full_path =  $this->plugin_dir . 'includes' . $path . '.php';
                }
                include_once $full_path;
            }

        }

        /**
         * Returns Validation Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Validation
         */
        function validation() {
            if ( empty( $this->classes['validation'] ) ) {
                $this->classes['validation'] = new wpo\core\Validation();
            }
            return $this->classes['validation'];
        }

        /**
         * Returns the Install Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Install
         */
        function install() {
            if ( empty( $this->classes['install'] ) ) {
                $this->classes['install'] = new wpo\core\Install();
            }
            return $this->classes['install'];
        }

        /**
         * Returns the Admin Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Admin_Metaboxes
         */
        function admin_metaboxes() {
            if ( empty( $this->classes['admin_metaboxes'] ) ) {
                $this->classes['admin_metaboxes'] = new wpo\core\Admin_Metaboxes();
            }
            return $this->classes['admin_metaboxes'];
        }

        /**
         * Returns the Common Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Common
         */
        function common() {
            if ( empty( $this->classes['common'] ) ) {
                $this->classes['common'] = new wpo\core\Common();
            }
            return $this->classes['common'];
        }

        /**
         * Returns the Admin Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Admin
         */
        function admin() {
            if ( empty( $this->classes['admin'] ) ) {
                $this->classes['admin'] = new wpo\core\Admin();
            }
            return $this->classes['admin'];
        }

        /**
         * Returns the Hooks Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Hooks
         */
        function hooks() {
            if ( empty( $this->classes['hooks'] ) ) {
                $this->classes['hooks'] = new wpo\core\Hooks();
            }
            return $this->classes['hooks'];
        }

        /**
         * Returns the Circles Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Profiles
         */
        function profiles() {
            if ( empty( $this->classes['profiles'] ) ) {
                $this->classes['profiles'] = new wpo\core\Profiles();
            }
            return $this->classes['profiles'];
        }


        /**
         * Returns the Assign Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Admin_Assign
         */
        function assign() {
            if ( empty( $this->classes['assign'] ) ) {
                $this->classes['assign'] = new wpo\core\Admin_Assign();
            }

            return $this->classes['assign'];
        }


        /**
         * Returns the Pages Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Hubs
         */
        function hubs() {
            if ( empty( $this->classes['hubs'] ) ) {
                $this->classes['hubs'] = new wpo\core\Hubs();
            }

            return $this->classes['hubs'];
        }

        /**
         * Returns the Pages Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Pages
         */
        function pages() {
            if ( empty( $this->classes['pages'] ) ) {
                $this->classes['pages'] = new wpo\core\Pages();
            }

            return $this->classes['pages'];
        }

        /**
         * Returns the Downloader Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Admin_Form
         */
        function admin_form() {
            if ( empty( $this->classes['admin_form'] ) ) {
                $this->classes['admin_form'] = new wpo\core\Admin_Form();
            }

            return $this->classes['admin_form'];
        }

        /**
         * Returns the Downloader Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Downloader
         */
        function downloader() {
            if ( empty( $this->classes['downloader'] ) ) {
                $this->classes['downloader'] = new wpo\core\Downloader();
            }

            return $this->classes['downloader'];
        }

        /**
         * Returns the Help Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Help
         */
        function help() {
            if ( empty( $this->classes['help'] ) ) {
                $this->classes['help'] = new wpo\core\Help();
            }

            return $this->classes['help'];
        }

        /**
         * Returns the Shortcodes Class of WP Office.
         *
         * @since  1.0
         * @return wpo\Shortcodes
         */
        function shortcodes() {
            if ( empty( $this->classes['shortcodes'] ) ) {
                $this->classes['shortcodes'] = new wpo\core\Shortcodes();
            }

            return $this->classes['shortcodes'];
        }

        /**
         * Returns the Members Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Members
         */
        function members() {
            if ( empty( $this->classes['members'] ) ) {
                $this->classes['members'] = new wpo\core\Members();
            }

            return $this->classes['members'];
        }


        /**
         * Returns the Dashboard Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Admin_Dashboard
         */
        function admin_dashboard() {
            if ( empty( $this->classes['admin_dashboard'] ) ) {
                $this->classes['admin_dashboard'] = new wpo\core\Admin_Dashboard();
            }
            return $this->classes['admin_dashboard'];
        }


        /***********************************************************************************
         *
         * INIT Classes Block
         *
         ***********************************************************************************/


        /**
         * Returns the Admin Menu Class of WP Office.
         *
         * @since  1.0
         * @return wpo\core\Admin_Menu
         */
        function admin_menu() {
            if ( empty( $this->classes['admin_menu'] ) ) {
                $this->classes['admin_menu'] = new wpo\core\Admin_Menu();
            }
            return $this->classes['admin_menu'];
        }

        


        


        

        /**
         * Inclide the correct AJAX Class of WP Office.
         *
         * @since  1.0
         *
         * @return void
         */
        function ajax_init() {

            $side = '';
            if ( ! empty( $_REQUEST['wpo_ajax_side'] ) ) {
                $side = $_REQUEST['wpo_ajax_side'];
            }

            switch( $side ) {
                case 'common' : {
                    new wpo\core\AJAX_Common();
                    break;
                }

                default : {
                    //our_hook
                    do_action( 'wpoffice_ajax_init_' . $side );
                    break;
                }


            }
        }

        /**
         * Include required frontend files.
         *
         * @return void
         */
        public function frontend_includes() {

            //include frontend init class
            new wpo\Frontend_Init();

            //include shortcodes class
            $this->shortcodes();
        }

        


        /**
         * Include required core files used in admin and on the frontend.
         *
         * @return void
         */
        public function includes() {

            //include common features Custom Post Types/

            $this->common();

            
            $this->features_includes();

            //include all payment gateways
            $this->include_folder( $this->plugin_dir . 'includes/gateways/' );

            if ( $this->is_request( 'ajax' ) ) {
                $this->ajax_init();
            } elseif ( $this->is_request( 'admin' ) ) {
                $this->admin_menu();
                $this->admin();
                $this->admin_metaboxes();
            }elseif ( $this->is_request( 'frontend' ) ) {
                $this->frontend_includes();
            }

        }

        /**
         * Reset Rewrite rules if need it.
         *
         * @return void
         */
        function maybe_flush_rewrite_rules() {
            if ( get_option( 'wpo_flush_rewrite_rules' ) ) {
                flush_rewrite_rules( false );
                delete_option( 'wpo_flush_rewrite_rules' );
            }
        }

        function register_scripts() {
            wp_enqueue_script('jquery');
            wp_enqueue_style('wpo-common-css', $this->plugin_url . 'assets/css/common.css', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-list_table-js-render', $this->plugin_url . 'assets/js/jsrender.min.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-list_table-js', $this->plugin_url . 'assets/js/classes/class-office-list-table.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-pulllayer-js', $this->plugin_url . 'assets/js/plugins/wpo_pulllayer/jquery.wpo_pulllayer.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-confirm-js', $this->plugin_url . 'assets/js/plugins/wpo_confirm/wpo_confirm.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-notice-js', $this->plugin_url . 'assets/js/plugins/wpo_notice/wpo_notice.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-base64-js', $this->plugin_url . 'assets/js/jquery.b_64.min.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-assign-js', $this->plugin_url . 'assets/js/classes/class-assign.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-tooltip-js', $this->plugin_url . 'assets/js/plugins/wpo_tooltip/jquery.wpo_tooltip.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-render-shortcode', $this->plugin_url . 'assets/js/render-shortcode.js', array('jquery'), WP_OFFICE_VER );

            wp_register_style( 'wpo-list_table-style', $this->plugin_url . 'assets/css/classes/class-office-list-table.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-admin-general-style', $this->plugin_url . 'assets/css/admin-general.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-admin-header-style', $this->plugin_url . 'assets/css/admin-header.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-admin-buttons-style', $this->plugin_url . 'assets/css/admin-buttons.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-pulllayer-style', $this->plugin_url . 'assets/js/plugins/wpo_pulllayer/jquery.wpo_pulllayer.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-confirm-style', $this->plugin_url . 'assets/js/plugins/wpo_confirm/wpo_confirm.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-notice-style', $this->plugin_url . 'assets/js/plugins/wpo_notice/wpo_notice.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-admin-form-style', $this->plugin_url . 'assets/css/classes/class-admin-office-form.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-assign-style', $this->plugin_url . 'assets/css/classes/class-assign.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-tooltip-style', $this->plugin_url . 'assets/js/plugins/wpo_tooltip/jquery.wpo_tooltip.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-button-switch-style', $this->plugin_url . 'assets/css/wpo.switch-button.css', array(), WP_OFFICE_VER );

            wp_localize_script( 'wpo-pulllayer-js', 'wpo_ajax_loader', array(
                'loader'    => $this->get_ajax_loader( 77 ),
                'line'      => $this->hr( '3px 0 0 0' ),
                'save_help_flag_url' => $this->get_ajax_route('wpo!core!Members', 'save_help_flag')
            ) );

            if ( is_admin() && !defined( 'WP_OFFICE_PRO' ) ) {
                wp_enqueue_style( 'wpo-admin-notpro-general-css', WO()->plugin_url . 'assets/css/admin-notpro-general.css', false, WP_OFFICE_VER );
                wp_enqueue_script( 'wpo-admin-notpro-js', WO()->plugin_url . 'assets/js/admin-notpro.js', false, WP_OFFICE_VER );
            }

        }

        /*
        * Set plugin information
        *
        * @return void
        */
        function _set_plugin_info() {
            $logo_img = !defined( 'WP_OFFICE_PRO' ) ? 'plugin_logo_lite.png' : 'plugin_logo.png';
            $this->plugin['logo_style'] = "<style type='text/css'>
                .wpo_admin_logo {
                    background: url( '" . $this->plugin_url . "/assets/images/{$logo_img}' ) no-repeat transparent;
                }
                @media (max-width: 782px) {
                    .wpo_admin_logo {
                        background: url( '" . $this->plugin_url . "/assets/images/plugin_logo_mobile.png' ) no-repeat transparent;
                    }
                }
            </style>";

            

            //default values
            $this->plugin['title']          = 'Office';
            $this->plugin['old_title']      = $this->plugin['title'];
            $this->plugin['logo_content']   = '';

            $this->plugin['icon_url'] = $this->plugin_url . '/assets/images/plugin_icon.png';
        }

        /*
        * Function deactivation
        *
        * @return void
        */
        function deactivation() {

            update_option( 'wpoffice_activation', '0' );

        }


        /*
        * Function activation
        *
        * @return void
        */
        function activation() {

            if ( '1' !== get_option( 'wpoffice_activation' ) ) {

                $this->install()->create_db();
                $this->admin()->add_objects_type_into_enum( array(
                    'user',
                    'new_message',
                    'circle',
                    'file',
                    'file_category',
                    'office_page',
                    'office_page_category',
                    'office_hub' )
                );

                //for update rewrite rules
                $this->reset_rewrite_rules();

                $this->admin()->update_roles();

                update_option( 'wpoffice_activation', '1' );
            }

            $ver = get_option( 'wpoffice_ver', '0.0.0' );
            if ( version_compare( $ver, WP_OFFICE_VER, '<' ) ) {
                $this->install()->update( $ver );
            }

        }

        


        function rest_api_init() {
            $api = new wpo\core\Api();
            $api->register_routes();
        }

	/**
	 * Get gateways class.
	 * @return wpo\gateways\Payment_Gateways
	 */
	public function payment_gateways() {
            return wpo\gateways\Payment_Gateways::instance();
	}


	/**
	 * Get gateways class.
	 * @return wpo\gateways\paypal\Paypal
	 */
	public function paypal() {
            return wpo\gateways\paypal\Paypal::instance();
	}

    } //end class
}


/**
 * Returns the main instance of WO to prevent the need to use globals.
 *
 * @since  1.0
 * @return WPOffice
 */
function WO() {
    return WPOffice::instance();
}

// Global for backwards compatibility.
$GLOBALS['wpoffice'] = WO();
