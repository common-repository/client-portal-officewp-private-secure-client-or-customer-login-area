<?php
namespace wpo;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Api;


    /**
 * Frontend_Init Class
     *
 * @class Frontend_Init
     * @version    1.0.0
     */
class Frontend_Init {

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
         * Constructor.
         */
        public function __construct() {

            add_filter( 'posts_request', array( &$this, 'our_query_pages' ) );

            add_action( 'pre_get_posts', array( $this, 'check_our_pages' ) );

            //protect our pages
            add_filter( 'the_posts', array( &$this, 'filter_posts' ), 99, 2 );

            //get template for Office Pages
            add_filter( 'template_include', array( &$this, 'get_office_page_template' ), 99 );


            add_filter( 'lostpassword_url', array( &$this, 'get_lost_password_url'), 99 );


            add_action( 'wp_loaded', array( &$this, 'process_login' ), 20 );
            add_filter( 'wp_authenticate_user', array( &$this, 'login_process_checks' ), 20, 2 );

            add_action( 'wp_loaded', array( &$this, 'register_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( &$this, 'include_js_css' ), 99 );

            add_action( 'wp_head', array( &$this, 'add_custom_style' ), 10 );

        }


        function include_js_css() {
            wp_enqueue_style( 'wpo-pulllayer-style' );
            wp_enqueue_style( 'wpo-confirm-style' );
            wp_enqueue_style( 'wpo-notice-style' );
            wp_enqueue_style( 'wpo-assign-style' );
            wp_enqueue_style( 'wpo-list_table-style' );
            wp_enqueue_style( 'wpo-buttons-style' );
            wp_enqueue_style( 'dashicons' );

            wp_enqueue_style( 'wpo-user-general-style' );
            wp_enqueue_style( 'wpo-user-forms-style' );

            $custom_style = WO()->get_settings( 'custom_style' );
            if ( empty( $custom_style['disable_plugin_css'] ) || 'yes' != $custom_style['disable_plugin_css'] ) {
                wp_enqueue_style( 'wpo-user-default-box-style' );
            }

            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wpo-pulllayer-js' );
            wp_enqueue_script( 'wpo-confirm-js' );
            wp_enqueue_script( 'wpo-notice-js' );
            wp_enqueue_script( 'wpo-base64-js' );
            wp_enqueue_script( 'wpo-assign-js' );

            wp_localize_script( 'wpo-assign-js', 'wpo_assign_data', array(
                'ajax_url'          => WO()->get_ajax_route( get_class( WO()->assign() ), 'load_assign_data' ),
                'ajax_reload_form'  => WO()->get_ajax_route( get_class( WO()->assign() ), 'load_assign_tab_content' ),
                'ajax_assign_items' => WO()->get_ajax_route( get_class( WO()->assign() ), 'assign_items' ),
                'texts'             => array(
                    'empty'         => __( 'Nothing found', WP_OFFICE_TEXT_DOMAIN ),
                    'categories'    => __( 'Categories', WP_OFFICE_TEXT_DOMAIN ),
                    'members'       => __( 'Members', WP_OFFICE_TEXT_DOMAIN )
                ),
            ) );

            wp_enqueue_script( 'wpo-validation-js' );
            wp_localize_script( 'wpo-validation-js', 'wpo_validation',
                apply_filters( 'wpoffice_validation_localize', array(
                    'error' => WO()->validation()->error_messages
                ))
            );

            wp_enqueue_script( 'password-strength-meter' );
        }


        function register_scripts() {
            wp_enqueue_style('wpo-common-css', WO()->plugin_url . 'assets/css/common.css', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-list_table-js-render', WO()->plugin_url . 'assets/js/jsrender.min.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-list_table-js', WO()->plugin_url . 'assets/js/classes/class-office-list-table.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-pulllayer-js', WO()->plugin_url . 'assets/js/plugins/wpo_pulllayer_user/jquery.wpo_pulllayer.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-confirm-js', WO()->plugin_url . 'assets/js/plugins/wpo_confirm/wpo_confirm.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-notice-js', WO()->plugin_url . 'assets/js/plugins/wpo_notice/wpo_notice.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-base64-js', WO()->plugin_url . 'assets/js/jquery.b_64.min.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-assign-js', WO()->plugin_url . 'assets/js/classes/class-assign.js', false, WP_OFFICE_VER );
            wp_register_script( 'wpo-validation-js', WO()->plugin_url . 'assets/js/plugins/jquery.wpo_validation.js', false, WP_OFFICE_VER );

            wp_register_style( 'wpo-buttons-style', WO()->plugin_url . 'assets/css/admin-buttons.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-list_table-style', WO()->plugin_url . 'assets/css/classes/user-class-office-list-table.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-pulllayer-style', WO()->plugin_url . 'assets/js/plugins/wpo_pulllayer_user/jquery.wpo_pulllayer.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-confirm-style', WO()->plugin_url . 'assets/js/plugins/wpo_confirm/wpo_confirm.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-notice-style', WO()->plugin_url . 'assets/js/plugins/wpo_notice/wpo_notice.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-assign-style', WO()->plugin_url . 'assets/css/classes/class-assign.css', array(), WP_OFFICE_VER );

            wp_register_style( 'wpo-user-general-style', WO()->plugin_url . 'assets/css/user-general.css', array(), WP_OFFICE_VER );
            wp_register_style( 'wpo-user-forms-style', WO()->plugin_url . 'assets/css/user-forms.css', array(), WP_OFFICE_VER );

            $custom_style = WO()->get_settings( 'custom_style' );
            if ( empty( $custom_style['disable_plugin_css'] ) || 'yes' != $custom_style['disable_plugin_css'] ) {
                wp_register_style( 'wpo-user-default-box-style', WO()->plugin_url . 'assets/css/user-default-box.css', array(), WP_OFFICE_VER );
            }
        }


        /*
        * process login form data
        *
        *
        * @return void
        */
        function process_login() {

            if ( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wpofficeloginform' ) ) {

                try {

                    $args['user_login'] = trim( $_POST['username'] );

                    if ( empty( $args['user_login'] ) ) {
                        throw new \Exception( '<strong>' . __( 'Error', WP_OFFICE_TEXT_DOMAIN ) . ':</strong> ' . __( 'Username is required.', WP_OFFICE_TEXT_DOMAIN ) );
                    }

                    if ( empty( $_POST['password'] ) ) {
                        throw new \Exception( '<strong>' . __( 'Error', WP_OFFICE_TEXT_DOMAIN ) . ':</strong> ' . __( 'Password is required.', WP_OFFICE_TEXT_DOMAIN ) ) ;
                    }

                    if ( is_email( $args['user_login'] ) ) {
                        $user = get_user_by( 'email', $args['user_login'] );

                        if ( isset( $user->user_login ) ) {
                            $args['user_login'] = $user->user_login;
                        } else {
                            throw new \Exception( '<strong>' . __( 'Error', WP_OFFICE_TEXT_DOMAIN ) . ':</strong> ' . __( 'A user could not be found with this email address.', WP_OFFICE_TEXT_DOMAIN ) );
                        }
                    }

                    $args['user_password'] = $_POST['password'];
                    $args['remember']      = isset( $_POST['rememberme'] );
                    $user                  = wp_signon( apply_filters( 'wpoffice_login_credentials', $args ), is_ssl() );

                    if ( is_wp_error( $user ) ) {
                        $message = $user->get_error_message();
                        $message = str_replace( '<strong>' . esc_html( $args['user_login'] ) . '</strong>', '<strong>' . esc_html( $_POST['username'] ) . '</strong>', $message );
                        throw new \Exception( $message );
                    } else {

                        if ( ! empty( $_POST['redirect'] ) ) {
                            $redirect = $_POST['redirect'];
                        } elseif ( wp_get_referer() ) {
                            $redirect = wp_get_referer();
                        } else {
                            $redirect = WO()->get_page_slug( 'hub_page' );
                        }

                        WO()->redirect( apply_filters( 'wpoffice_login_redirect', $redirect, $_REQUEST, $user ) );
                    }

                } catch ( \Exception $e ) {

                    $message = $e->getMessage();

                    $login_url = WO()->get_page_slug( 'login_page' );
                    $login_url = !empty( $login_url ) ? $login_url : wp_login_url();
                    WO()->redirect( add_query_arg( 'msg', urlencode( $message ), $login_url ) );

                }

            }

        }



        /**
         * Check member if it's archive login false
         *
         * @param $user
         * @param $password
         * @return \WP_Error|\WP_User
         */
        function login_process_checks( $user, $password ) {

            if ( is_wp_error( $user ) )
                return $user;

            if ( WO()->get_office_role( $user->ID ) === false )
                return $user;

            if ( wp_check_password( $password, $user->user_pass, $user->ID ) ) {

                //change valid password to wrong for dispalying default WP error about wrong password for archive member
                if ( get_user_meta( $user->ID, 'wpoffice_archived', true ) ) {
                    $user->user_pass = md5( time() ) . time();
                }

            }

            return $user;
        }

        /*
        * filter lost password URL on our login form
        *
        * @param string $url
        *
        * @return string
        */
        function get_lost_password_url( $url ) {
            if ( WO()->is_wp_login() ) {
                return $url;
            }

            $login_page = WO()->get_page_slug( 'login_page' );

            if ( $login_page ) {
                return add_query_arg( array( 'action' => 'lostpassword' ), $login_page );
            } else {
                return $url;
            }
        }

        /*
        * add meta to our pages
        *
        * @return void
        */
        function add_header_meta_to_our_pages() {
            echo '<meta name="robots" content="noindex"/>';
            echo '<meta name="robots" content="nofollow"/>';
            echo '<meta name="Cache-Control" content="no-cache"/>';
            echo '<meta name="Pragma" content="no-cache"/>';
            echo '<meta name="Expires" content="0"/>';
        }

        /**
         * Protect Cleint page and HUB from not logged user and Search Engine
         */

        /**
         * Filter posts array for Protect our pages
         *
         * @param array $posts
         * @param string $query
         *
         * @return array
         */
        function filter_posts( $posts, $query ) {

            if ( empty( $posts ) )
                return $posts;

            $filtered_array = array();

            foreach( $posts as $post ) {

                //for Office Pages
                if ( 'office_page' == $post->post_type ) {
                    if ( is_user_logged_in() ) {

                        if ( current_user_can( 'administrator' ) ) {
                            $filtered_array[] = $post;
                            continue;
                        }

                        $access_office_pages = WO()->get_access_content_ids( get_current_user_id(), 'office_page' );
                        if ( in_array( $post->ID, $access_office_pages ) ) {
                            $filtered_array[] = $post;
                            continue;
                        }
                    }
                    continue;
                } elseif ( 'office_hub' == $post->post_type ) {

                    global $wp_query;

                    $wpo_page_key = ( !empty( $wp_query->query_vars['wpo_page_key'] ) ) ? trim( $wp_query->query_vars['wpo_page_key'] ) : '';

                    if ( $wpo_page_key) {
                        switch( $wpo_page_key ) {
                            case 'hub_page':

                                $post->post_title = WO()->get_page_titles( $wpo_page_key );
                                if ( ! is_user_logged_in() ) {
                                    $post->post_content = '';
                                }
                                break;
                            default:
                                $endpoints = WO()->get_endpoints();
                                if( isset( $endpoints[ $wpo_page_key ] ) ) {
                                    $post->post_title = isset( $endpoints[ $wpo_page_key ]['title'] ) ?
                                        $endpoints[ $wpo_page_key ]['title'] : '';
                                    $post->post_content = isset( $endpoints[ $wpo_page_key ]['content'] ) ?
                                        $endpoints[ $wpo_page_key ]['content'] : '';
                                } else {
                                    $post->post_title = WO()->get_page_titles($wpo_page_key);

                                    /*wpo_hook_
                                        hook_name: wpoffice_hub_page_ + ['wpo_page_key'] + _shortcode
                                        hook_title: Show another pages on HUB page
                                        hook_description: Hook runs when open link of our pages (not HUB) and it's uses for run shortcodes of these pages.
                                        hook_type: action
                                        hook_in: wp-office
                                        hook_location class-shortcodes.php
                                        hook_param:
                                        hook_since: 1.0.0
                                    */
                                    $post->post_content = apply_filters('wpoffice_hub_page_' . $wp_query->query_vars['wpo_page_key'] . '_shortcode', '');
                                }
                                remove_all_filters( 'the_content' );
                                add_filter( 'the_content', 'do_shortcode', 999999999 );
                                break;
                        }

                        $wp_query->is_page      = true; //for use view as page
                        $wp_query->is_home      = false;
                        $wp_query->is_singular  = true; //shortcode sometimes not works if TRUE, why???
                        $wp_query->is_single    = false; // for not show links on next pages


//                        var_dump($wp_query->query_vars['wpo_page_key'] . '_shortcode');
                    } else {

                        //deny access for not logged in user
                        if ( ! is_user_logged_in() ) {
                            WO()->redirect( WO()->get_page_slug( 'login_page' ) );
                        }

//                        var_dump('hub shortcode');
                    }

                    $filtered_array[] = $post;
                    continue;
                }

                //add all other posts
                $filtered_array[] = $post;
            }


            return $filtered_array;
        }

        /**
         * Get template for Offile Pages
         *
         * @param string $template
         *
         * @return string
         */
        function get_office_page_template( $template ) {
            global $post;

            //for our pages
            if ( !empty( $post->post_type ) ) {
                 if  ( 'office_page' == $post->post_type || 'office_hub' == $post->post_type ) {

                    $page_template = get_post_meta( $post->ID, '_wp_page_template', true );

                    //use page templates
                    if ( !empty( $page_template ) && file_exists( get_stylesheet_directory() . "/{$page_template}" ) ) {

    //Maybe NEED add it for some themes
                        //use filter for change template - for some themes
    //                    add_filter( 'get_post_metadata', array( &$this, 'change_our_pages_template' ), 99, 4 );

                        return get_stylesheet_directory() . "/{$page_template}";
                    } else {
                        return get_page_template();
                    }
                }
            }
            return $template;
        }

        /**
         * Check query object on 'pre_get_posts' for our pages
         *
         * @param object $q
         *
         * @return object
         */
        public function check_our_pages( $q ) {
            global $wp_query, $wp_rewrite;
//var_dump($wp_rewrite); exit;
            //We need main query
            if ( $q->is_main_query() ) {

                //our pages
                if ( !empty( $wp_query->query_vars['wpo_page'] ) && !empty( $wp_query->query_vars['wpo_page_key'] ) ) {

                    $q->is_singular = true;
                    $q->is_page = true;

                    add_action( 'wp_head', array( &$this, 'add_header_meta_to_our_pages' ), 99 );

                }
                //office pages block
                elseif( !empty( $wp_query->query_vars['post_type'] ) && 'office_page' == $wp_query->query_vars['post_type'] ) {

                    add_action( 'wp_head', array( &$this, 'add_header_meta_to_our_pages' ), 99 );

                    if ( is_user_logged_in() ) {
                        $current_office_page = false;
                        if ( !empty( $wp_query->query_vars['office_page'] ) ) {
                            $current_office_page = get_page_by_path( $wp_query->query_vars['office_page'], object, 'office_page' );
                        } elseif ( !empty( $_GET['p'] ) ) {
                            $current_office_page = get_post( $_GET['p'] );
                        }

                        if ( $current_office_page && current_user_can( 'administrator' ) ) {
                            return $q;
                        }

                        $access_office_pages = WO()->get_access_content_ids( get_current_user_id(), 'office_page' );
                        if ( $current_office_page && in_array( $current_office_page->ID, $access_office_pages ) ) {
                            return $q;
                        }
                    }

                    //have no access
                    WO()->redirect( get_home_url() );

                }

            }

            return $q;
        }

        /**
         * Check query string on 'posts_request' for our pages
         *
         * @param string $q
         *
         * @return string
         */
        public function our_query_pages( $q ) {
            global $wp_query, $wpdb;

            //We need main query
            if ( $q == $wp_query->request ) {
//                var_dump($q);
                if ( !empty( $wp_query->query_vars['wpo_page'] ) ) {

                    if ( 'api' == $wp_query->query_vars['wpo_page'] ) {
                        $api = new Api();
                        $api->init_frontend_actions();
                    } elseif ( !empty( $wp_query->query_vars['wpo_page_key'] ) ) {


                        //do logout
                        if ( 'logout_page' == $wp_query->query_vars['wpo_page_key'] ) {
                            if ( is_user_logged_in() ) {
                                wp_logout();

                                //our_hook
                                $logout_redirect_url = apply_filters( 'wpoffice_logout_redirect_url', WO()->get_page_slug( 'login_page' )  );
                                WO()->redirect( $logout_redirect_url );

                            } else {
                                WO()->redirect( WO()->get_page_slug( 'hub_page' )  );
                            }

                        } elseif ( is_user_logged_in() || 'login_page' == $wp_query->query_vars['wpo_page_key'] || false !== strpos( $wp_query->query_vars['wpo_page_key'], 'registration_' ) ) {

                            $pages = WO()->get_settings( 'pages' );

                            if ( !empty( $pages['hub_page']['slug'] ) ) {

                                $circles_ids = WO()->get_assign_data_by_assign( 'circle', 'member', get_current_user_id() );
                                $circles_hub_ids = WO()->get_assign_data_by_assign( 'office_hub', 'circle', $circles_ids );
                                $hub_ids = WO()->get_assign_data_by_assign( 'office_hub', 'member', get_current_user_id() );

                                $hub_ids = array_unique( array_merge( $hub_ids, $circles_hub_ids ) );

                                if ( !empty( $hub_ids ) ) {

                                    if ( 1 < count( $hub_ids )) {
                                        $include = 'p.ID IN("' . implode( '","', $hub_ids ) . '")';

                                        $hub_id = $wpdb->get_var( "
                                        SELECT p.ID
                                        FROM {$wpdb->posts} p
                                        LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'hub_priority'
                                        WHERE " . $include . " AND p.post_status != 'auto-draft'

                                        ORDER BY pm.meta_value DESC
                                        LIMIT 1
                                        ");

                                    } else {
                                        $hub_id = $hub_ids[0];
                                    }
                                } else {
                                    $hub_id = WO()->get_hub_default();
                                }

                                $q = "SELECT * FROM {$wpdb->prefix}posts WHERE 1=1 AND ID = '" . $hub_id  . "' AND post_type = 'office_hub' ORDER BY post_date DESC ";
                            }
                        } else {
                            WO()->redirect( WO()->get_page_slug( 'login_page' )  );
                        }

                    }
                }

//                var_dump($q);
//                exit;

            }

            return $q;
        }


        function add_custom_style() {
            $custom_style = WO()->get_settings( 'custom_style' );
            if( !empty( $custom_style['custom_css'] ) ) { ?>
                <style>
                    <?php echo $custom_style['custom_css'] ?>
                </style>
            <?php }
        }

} //end class