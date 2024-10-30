<?php
namespace wpo\core;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    /**
     * PHP 5 constructor
     **/
    function __construct() {
        add_action( 'admin_notices', array( &$this, 'remove_other_notices' ), -10 );

        $general_settings = WO()->get_settings( 'common' );
        if ( !empty( $general_settings['no_conflict_mode'] ) && 'yes' == $general_settings['no_conflict_mode'] ) {
            //no conflict mode
            add_action( 'wp_print_scripts', array( &$this, 'no_conflict_mode_script' ), 1000 );
            add_action( 'wpoffice_admin_print_footer_scripts', array( &$this, 'no_conflict_mode_script' ), 9 );

            add_action( 'wp_print_styles', array( &$this, 'no_conflict_mode_style' ), 1000 );
            add_action( 'wpoffice_admin_print_styles', array( &$this, 'no_conflict_mode_style' ), 1 );
            add_action( 'wpoffice_admin_footer', array( &$this, 'no_conflict_mode_style' ), 1 );

            add_action( 'admin_head', array( &$this, 'remove_all_actions' ), -1000 );
            add_action( 'in_admin_header', array( &$this, 'remove_all_actions' ), 1 );
            add_action( 'in_admin_footer', array( &$this, 'remove_all_actions' ), -1000 );

            add_action( 'admin_footer', array( &$this, 'remove_all_actions' ), -1000 );
            add_action( 'admin_print_styles', array( &$this, 'remove_all_actions' ), -1000 );
            add_action( 'admin_print_scripts', array( &$this, 'remove_all_actions' ), -1000 );
            add_action( 'admin_print_footer_scripts', array( &$this, 'remove_all_actions' ), -1000 );
        }

        add_action( 'admin_init', array( &$this, 'request_action' ) );

        add_action( 'admin_init', array( &$this, 'admin_page_access_denied' ), 99 );

        //add_action( 'wpoffice_member_added', array( WO()->hooks(), 'wpo\core\Members->add_assigns_on_change' ), 10, 1 );
        add_action( 'wpoffice_member_added', array( &$this, 'add_assigns_on_change' ), 10 );

        add_filter( 'plugin_action_links_' . plugin_basename( WO()->plugin_dir . 'wp-office.php' ), array( &$this, 'plugin_manage_link' ), 10 );
        

        add_action( 'plugins_loaded', array( $this, 'do_actions') );

        add_filter( 'wp_enqueue_media', array( &$this, 'enqueue_media' ) );

        add_filter( 'mce_buttons', array( &$this, 'mce_buttons' ), 99, 2 );

    }

    


    /**
     * Update roles capabilities
     */
    function update_roles( $role = '' ) {
        global $wp_roles;

        $roles_list = WO()->get_settings( 'roles' );

        if ( !empty( $role ) ) {

            if ( !empty( $roles_list[$role] ) ) {
                $roles_list = array( $role => $roles_list[$role] );
            }

        }

        $capability_map = array(
            'publish_office_pages',
            'edit_office_page',
            'edit_office_pages',
            'edit_private_office_pages',
            'edit_others_office_pages',
            'edit_published_office_pages',
            'read_office_page',
            'read_office_pages',
            'read_private_office_pages',
            'view_others_office_pages',
            'create_office_pages',

            'publish_office_hubs',
            'edit_office_hub',
            'edit_office_hubs',
            'edit_private_office_hubs',
            'edit_others_office_hubs',
            'edit_published_office_hubs',
            'read_office_hub',
            'read_office_hubs',
            'read_private_office_hubs',
            'view_others_office_hubs',
            'create_office_hubs'
        );

        if ( $roles_list ) {
            foreach ( $roles_list as $key => $value ) {
                //remove old role
                $wp_roles->remove_role( $key );

                //add role
                $wp_roles->add_role( $key, $value['title']);

                $wp_roles->add_cap( $key, 'read', true );

                //set capability for Office Pages
                foreach ( $capability_map as $capability ) {
                    $wp_roles->add_cap( $key, $capability );
                }
                $wp_roles = new \WP_Roles();
            }
        }

        //set capability for Office Pages
        foreach ( $capability_map as $capability ) {
            $wp_roles->add_cap( 'administrator', $capability );
        }

        return true;
    }

    function remove_our_query_args_for_our_pages( $removable_query_args ) {
        $removable_query_args[] = 'msg';
        return $removable_query_args;
    }


    function request_action() {
        //skip this function for AJAX
        if ( defined( 'DOING_AJAX' ) )
            return '';

        if( isset( $_GET['action'] ) && 'wp-office-uninstall' == $_GET['action'] ) {
            WO()->install()->uninstall();
        }

        
        global $parent_file;
        if ( 'wp-office' === $parent_file ) {
            add_filter( 'removable_query_args', array( &$this, 'remove_our_query_args_for_our_pages' ) );
        }
        
    }

    


    private static function add_dependency_scripts( $registered, $scripts ) {

        //gets all dependent scripts linked to the $scripts array passed
        do {
            $dependents = array();
            foreach ( $scripts as $script ) {
                $deps = isset( $registered[ $script ] ) && is_array( $registered[ $script ]->deps ) ? $registered[ $script ]->deps : array();
                foreach ( $deps as $dep ) {
                        if ( ! in_array( $dep, $scripts ) && ! in_array( $dep, $dependents ) ) {
                                $dependents[] = $dep;
                        }
                }
            }
            $scripts = array_merge( $scripts, $dependents );
        } while ( ! empty( $dependents ) );

        return $scripts;
    }

    


    function no_conflict_mode_style() {
        global $parent_file;

        if ( 'wp-office' === $parent_file ) {
            global $wp_styles;

            $wprequired_scripts = array( 'admin-bar', 'colors', 'ie', 'wp-admin', 'editor-style', 'editor', 'dashicons', 'editor-buttons', 'wp-auth-check' );

            $queue = array();
            foreach ( $wp_styles->queue as $script ) {
                if ( in_array( $script, $wprequired_scripts ) || strpos( $script, 'wpo-' ) === 0 ) {
                    $queue[] = $script;
                }
            }

            $wp_styles->queue = $queue;

            $wprequired_scripts = self::add_dependency_scripts( $wp_styles->registered, $wprequired_scripts );

            //unregistering scripts
            $registered = array();
            foreach ( $wp_styles->registered as $script_name => $script_registration ) {
                if ( in_array( $script_name, $wprequired_scripts ) || strpos( $script_name, 'wpo-' ) === 0 ) {
                    $registered[ $script_name ] = $script_registration;
                }
            }
            $wp_styles->registered = $registered;
        }

    }

    function no_conflict_mode_script() {
        global $parent_file;
        if ( 'wp-office' === $parent_file ) {
            global $wp_scripts;

            $wprequired_scripts = array( 'admin-bar', 'common', 'jquery-color', 'utils', 'hoverIntent', 'heartbeat', 'wp-auth-check', 'svg-painter', 'plupload','jquery-ui-sortable','password-strength-meter','jquery-ui-tooltip','editor', 'editor-functions','quicktags','tiny_mce','jquery-ui-core','jquery-ui-widget','link','masonry' );

            $queue = array();
            foreach ( $wp_scripts->queue as $script ) {
                if ( in_array( $script, $wprequired_scripts ) || strpos( $script, 'wpo-' ) === 0 ) {
                    $queue[] = $script;
                }
            }

            $wp_scripts->queue = $queue;

            $wprequired_scripts = self::add_dependency_scripts( $wp_scripts->registered, $wprequired_scripts );

            //unregistering scripts
            $registered = array();
            foreach ( $wp_scripts->registered as $script_name => $script_registration ) {
                if ( in_array( $script_name, $wprequired_scripts ) || strpos( $script_name, 'wpo-' ) === 0 ) {
                    $registered[ $script_name ] = $script_registration;
                }
            }

            $wp_scripts->registered = $registered;
        }
    }


    function do_actions() {
        
    }


    function remove_other_notices() {
        global $parent_file;

        if ( 'wp-office' === $parent_file ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );

            /*wpo_hook_
                hook_name: wpoffice_admin_notices
                hook_title: Show Office Admin notices on our pages
                hook_description: Hook show admin notices only on our pages.
                hook_type: action
                hook_in: wp-office
                hook_location class-admin.php
                hook_param:
                hook_since: 1.0.0
            */
            do_action( 'wpoffice_admin_notices' ) ;
        }

        /*wpo_hook_
            hook_name: wpoffice_admin_notices_all_pages
            hook_title: Show Office Admin notices on all pages
            hook_description: Hook show Office admin notices on all pages.
            hook_type: action
            hook_in: wp-office
            hook_location class-admin.php
            hook_param:
            hook_since: 1.0.0
        */
        do_action( 'wpoffice_admin_notices_all_pages' ) ;
    }


    function remove_all_actions() {
        global $parent_file, $wp_filter;
        $action = current_filter();

        if ( 'wp-office' === $parent_file ) {
            //following priorities
            $array_wp_filter = array(
                'admin_head' => array (
                    10 => array (
                        'wp_admin_canonical_url',
                        'wp_color_scheme_settings',
                        'wp_site_icon',
                        '_ipad_meta',
                        'wp_admin_bar_header',
                    )
                ),
                'in_admin_header' => array (
                    0 => array (
                        'wp_admin_bar_render',
                    )
                ),
                'admin_print_styles' => array (
                    1 => array (
                        'wp_resource_hints',
                    ),
                    10 => array (
                        'print_emoji_styles',
                    ),
                    20 => array (
                        'print_admin_styles',
                    )
                ),
                'admin_print_scripts' => array (
                    10 => array (
                        'print_emoji_detection_script',
                    ),
                    20 => array (
                        'print_head_scripts',
                    )
                ),
                'admin_print_footer_scripts' => array (
                    1 => array (
                        '_WP_Editors::enqueue_scripts',
                    ),
                    5 => array (
                        'wp_auth_check_html',
                    ),
                    10 => array (
                        '_wp_footer_scripts',
                    ),
                    50 => array (
                        '_WP_Editors::editor_js',
                    ),
                ),
            );

            $saved_wp_filter = array();

            global $wp_version;

            if ( version_compare( $wp_version, '4.7', '<' ) ) {
                if ( isset( $array_wp_filter[ $action ] ) ) {
                    foreach ( $array_wp_filter[ $action ] as $priority => $hooks ) {
                        foreach ( $hooks as $hook ) {
                            if ( !empty( $wp_filter[ $action ][ $priority ][ $hook ] ) ) {
                                $saved_wp_filter[ $priority ][ $hook ] = $wp_filter[ $action ][ $priority ][ $hook ];
                            }
                        }
                    }
                }

                $saved_wp_filter = array_slice( $wp_filter[ $action ], 0, array_search( -1000, array_keys( $wp_filter[ $action ] ) ) + 1, true ) + $saved_wp_filter;

                if ( isset( $saved_wp_filter[-1000] ) ) {
                    $saved_wp_filter[-1000] = array();
                }

                remove_all_actions( $action );
                $wp_filter[ $action ] = $saved_wp_filter;
            } else {
                if ( isset( $array_wp_filter[ $action ] ) ) {
                    foreach ( $array_wp_filter[ $action ] as $priority => $hooks ) {
                        foreach ( $hooks as $hook ) {
                            if ( !empty( $wp_filter[ $action ]->callbacks[ $priority ][ $hook ] ) ) {
                                $saved_wp_filter[ $priority ][ $hook ] = $wp_filter[ $action ]->callbacks[ $priority ][ $hook ];
                            }
                        }
                    }
                }

                $saved_wp_filter = array_slice( $wp_filter[ $action ]->callbacks, 0, array_search( -1000, array_keys( $wp_filter[ $action ]->callbacks ) ) + 1, true ) + $saved_wp_filter;

                if ( isset( $saved_wp_filter[-1000] ) ) {
                    $saved_wp_filter[-1000] = array();
                }

                $callbacks = $wp_filter[ $action ]->callbacks;
                foreach ( $callbacks as $priority=>$filters ) {
                    foreach ( $filters as $function=>$data ) {
                        if ( empty( $saved_wp_filter[$priority][$function] ) ) {
                            $wp_filter[ $action ]->remove_filter( $action, $function, $priority );
                        }
                    }
                }
            }
        }

        /*wpo_hook_
            hook_name: wpoffice_ + ['action']
            hook_title: Custom actions
            hook_description:
            hook_type: action
            hook_in: wp-office
            hook_location class-admin.php
            hook_param:
            hook_since: 1.0.0
        */
        do_action( 'wpoffice_' . $action );
    }


    /**
     * Add type of objects for `object_type` field for `wpo_objects_assigns` table
     *
     * @global object $wpdb
     * @param array $new New types
     */
    function add_objects_type_into_enum( $new ) {
        global $wpdb;
        $field = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}wpo_objects_assigns WHERE Field = 'object_type'" );
        if ( isset( $field[0] ) && isset( $field[0]->Type ) ) {
            $type = $field[0]->Type;
        } else {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpo_objects_assigns ADD object_type text AFTER `id`" );
            $type = '';
        }
        $enum = array();

        //if this field exist some enum values in type
        preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
        if ( !empty( $matches[1] ) ) {
            $enum = explode("','", $matches[1]);
        }

        //if in table exist some values for this field
        $isset_values = $wpdb->get_col( "SELECT DISTINCT `object_type` FROM {$wpdb->prefix}wpo_objects_assigns" );

        $all_value = array_unique( array_merge( $enum, $isset_values, $new ) );
        $new_values = count( $all_value ) ? "'" . implode( "','", $all_value ) . "'" : '';
        if ( $new_values ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpo_objects_assigns MODIFY object_type ENUM({$new_values}) NOT NULL" );
        }
    }


    function admin_page_access_denied() {
        global $pagenow;

        if ( !empty( $pagenow ) ) {
            //hide default Wordpress list tables for Office Page & HUB
            if ( ( 'edit.php' == $pagenow ) && !empty( $_GET['post_type'] ) ) {
                if ( 'office_page' == $_GET['post_type'] ) {
                    WO()->redirect( add_query_arg( array( 'page'=>'wp-office-contents' ), get_admin_url() . 'admin.php' ) );
                } elseif ( 'office_hub' == $_GET['post_type'] ) {
                    WO()->redirect( add_query_arg( array( 'page'=>'wp-office-contents', 'tab' => 'office_hubs' ), get_admin_url() . 'admin.php' ) );
                }
            }

            //hide Wordpress add Office Page & HUB form if current user hasn't capabilities
            if ( ( 'post-new.php' == $pagenow ) && !empty( $_GET['post_type'] ) ) {
                if ( 'office_page' == $_GET['post_type'] && !WO()->current_member_can( 'create_office_page' ) ) {
                    wp_die( __( 'You do not have sufficient permissions to access this page.', WP_OFFICE_TEXT_DOMAIN ), 403 );
                } elseif ( 'office_hub' == $_GET['post_type'] && !WO()->current_member_can( 'create_office_hub' ) ) {
                    wp_die( __( 'You do not have sufficient permissions to access this page.', WP_OFFICE_TEXT_DOMAIN ), 403 );
                }
            }

            //hide Wordpress edit Office Page & HUB form if current user hasn't capabilities
            if ( 'post.php' == $pagenow && !empty( $_GET['post'] ) ) {
                if ( get_post_type( $_GET['post'] ) == 'office_page' ) {
                    if ( !current_user_can( 'administrator' ) && !WO()->current_member_can( 'edit_office_page', 'on' ) ) {
                        $edit_page_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page', 'edit' );
                        if ( !in_array( $_GET['post'], $edit_page_ids ) ) {
                            wp_die( __( 'You do not have sufficient permissions to access this page.', WP_OFFICE_TEXT_DOMAIN ), 403 );
                        }
                    }
                } elseif ( get_post_type( $_GET['post'] ) == 'office_hub' ) {
                    if ( !current_user_can( 'administrator' ) && !WO()->current_member_can( 'edit_office_hub', 'on' ) ) {
                        $edit_hub_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_hub', 'edit' );
                        if ( !in_array( $_GET['post'], $edit_hub_ids ) ) {
                            wp_die( __( 'You do not have sufficient permissions to access this page.', WP_OFFICE_TEXT_DOMAIN ), 403 );
                        }
                    }
                }
            }
        }
    }

    /**
     * Add assigns to member on add/edit
     *
     * @param $member_id
     */
    function add_assigns_on_change( $member_id ) {
        if ( !empty( $_POST['assigns'] ) ) {
            $items = WO()->assign()->parse_assign_value( $_POST['assigns'] );

            foreach( $items as $key=>$inner_items ) {
                WO()->set_reverse_assign_data( $key, $inner_items, 'member', $member_id );
            }
        }
    }

    function plugin_manage_link( $actions ) {
        if ( 'valid' == get_option( 'wp-office_license_status' ) ) {
            $actions['settings'] = sprintf( '<a href="admin.php?page=wp-office-settings">%s</a>', __( 'Settings', WP_OFFICE_TEXT_DOMAIN ) );
            $actions['uninstall'] = sprintf( '<a onclick=\'return confirm("' . __( 'Are you sure? It will remove all data as Members, HUB Pages, Office Pages, Messages, Files and Settings', WP_OFFICE_TEXT_DOMAIN ) . '");\' href="admin.php?action=wp-office-uninstall" style="color: red;">%s</a>', __( 'Reset Data', WP_OFFICE_TEXT_DOMAIN ) );
        }
        return $actions;
    }
    function enqueue_media() {
        wp_enqueue_script('wpo-pulllayer-js');
        wp_enqueue_style('wpo-pulllayer-style');
        wp_enqueue_style( 'wpo-admin-form-style' );
        wp_enqueue_style( 'wpo-admin-buttons-style' );

        wp_enqueue_script('wpo-shortcode-popup-js', WO()->plugin_url . 'assets/js/shortcodes_popup.js', array('wpo-pulllayer-js') );
        wp_localize_script('wpo-shortcode-popup-js', 'shortcodes_popup', array(
            'ajax_url' => WO()->get_ajax_route( 'wpo\core\Shortcodes', 'list_popup' ),
            'plugin_url' => WO()->plugin_url,
            'button_text' => __( 'Add Shortcode', WP_OFFICE_TEXT_DOMAIN )
        ));
    }


    function mce_buttons( $mce_buttons, $editor_id ) {
        if ( $editor_id == 'wpo_notification_body' ) {
            $mce_buttons = array_diff( $mce_buttons, array( 'fullscreen' ) );
        }
        return $mce_buttons;
    }

//end class
}