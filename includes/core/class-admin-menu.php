<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Menu {

    /**
     * @var Admin_Menu The single instance of the class
     * @since 1.0
     */
    protected static $_instance = null;


    var $plugin_submenus;

    /**
     * Main Admin_Menu Instance
     *
     * Ensures only one instance of Admin_Menu is loaded or can be loaded.
     *
     * @since 1.0
     * @static
     * @see WO()
     * @return Admin_Menu - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Menu constructor
     **/
    function __construct() {
        //admin menu
        add_action( 'admin_menu', array( &$this, 'adminmenu' ) );

        add_action( 'admin_body_class', array( &$this, 'hide_admin_submenu' ) );

        //include CSS/JS
        add_action( 'admin_enqueue_scripts', array( &$this, 'include_css_js' ), 99 );

        //include dynamic JS
        add_action( 'wpoffice_admin_footer', array( WO(), 'print_custom_js' ), 25 );

        add_action( 'admin_menu', array( &$this, 'remove_menus' ), 99999 );

        //neeed fix for TinyMCE
//                add_action( 'admin_head', array( $this, 'menu_active' ) );


        add_action( 'edit_form_top', array( $this, 'add_back_button' ) );
        add_action( 'post_submitbox_start', array( $this, 'add_move_to_trash_button' ) );

        add_action( 'lget_activation_logo_wp-office', array( $this, 'activation_logo' ) );

    }


    function our_admin_footer() {

    }


    /**
     * Get tabs for our admin pages
     *
     * @return string
     */
    function get_plugin_tabs_block() {

        $tabs = '';
        $page = !empty( $_GET['page'] ) ? $_GET['page'] : '';

        if ( !empty( $this->plugin_submenus[$page]['tabs'] ) ) {

            $i = 0;
            $current_tab = !empty( $_GET['tab'] ) ? $_GET['tab'] : '';

            foreach(  $this->plugin_submenus[$page]['tabs'] as $key => $value ) {
                if ( isset( $value['real'] ) && false === $value['real'] )
                    continue;

                if ( $i == 0 ) {
                    $tabs = '<div id="wpo_admin_page_tabs">';
                }

                if ( empty( $current_tab ) )
                    $current_tab = $key;

                $active_class = ( $current_tab == $key ) ? 'wpo_active_page_tab' : '';
                if ( empty( $current_tab ) )
                    $current_tab = $key;

                $tabs .= '<div class="wpo_admin_page_tab wpo_admin_tab_' . $key . ' ' . $active_class . ' ">
                    <a href="' . admin_url( 'admin.php?page=' . $page . '&tab=' . $key ) . '"><span class="dashicons dashicons-' . $value['icon'] . '"></span>' . $value['title'] . '</a>
                </div>';

                $i++;
            }
            if ( $i > 0 ) {
                $tabs .= '</div>';
            }

            if ( $i > 0 ) {
                $tabs .= '<div id="wpo_admin_page_tabs_mobile">';
                $active = '';
                $all_tabs = '';
                foreach(  $this->plugin_submenus[$page]['tabs'] as $key => $value ) {

                    if ( isset( $value['real'] ) && false === $value['real'] )
                        continue;

                    if ( empty( $current_tab ) )
                        $current_tab = $key;

                    $active_class = ( $current_tab == $key ) ? 'wpo_active_page_tab' : '';

                    if ( $current_tab == $key ) {
                        $active = '<div class="wpo_admin_page_tab wpo_admin_tab_' . $key . ' ' . $active_class . ' ">
                            <a href="javascript:void(0);"><span class="dashicons dashicons-' . $value['icon'] . '"></span>' . $value['title'] . '</a>
                        </div>';
                    }

                    if ( empty( $current_tab ) )
                        $current_tab = $key;

                    $all_tabs .= '<div class="wpo_admin_page_tab wpo_admin_tab_' . $key . ' ' . $active_class . ' ">
                        <a href="' . admin_url( 'admin.php?page=' . $page . '&tab=' . $key ) . '"><span class="dashicons dashicons-' . $value['icon'] . '"></span>' . $value['title'] . '</a>
                    </div>';
                }

                $tabs .= $active . '<div class="wpo_admin_page_tabs_dropdown">' . $all_tabs . '</div></div>';
            }

            if ( $i == 0 ) {
                $tabs = '<h1 id="wpo_admin_page_title">' . $this->plugin_submenus[$page]['page_title'] . '</h1>';
            }
        }
        ob_start();?>

        <script type="text/javascript">
            jQuery( document).ready( function() {
                jQuery('#wpo_admin_page_tabs_mobile').click( function(e) {
                    jQuery(this).toggleClass( 'wpo_dropdowned' );
                    e.stopPropagation();
                });

                jQuery('body').click( function() {
                    jQuery('#wpo_admin_page_tabs_mobile').removeClass( 'wpo_dropdowned' );
                })
            });
        </script>

        <?php $script = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }

        return $tabs . $script;
    }


    /**
     * Get tabs for our admin pages
     *
     * @return string
     */
    function get_plugin_header_buttons() {
        $flag = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
        $header_buttons = array(
            'help' => array(
                'title' => __( 'Help Mode', WP_OFFICE_TEXT_DOMAIN ),
                'icon'  => 'groups',
                'class' => $flag ? 'wpo_active' : '',
                'page' => '',
            ),
            'settings' => array(
                'title' => __( 'Settings', WP_OFFICE_TEXT_DOMAIN ),
                'icon'  => 'groups',
                'class' => '',
                'page' => 'wp-office-settings',
            ),
            //not for now
//            'tools' => array(
//                'title' => __( 'Tools', WP_OFFICE_TEXT_DOMAIN ),
//                'icon'  => 'groups',
//                'class' => '',
//                'page' => 'wp-office-tools',
//            ),

        );

        if ( !defined( 'WP_OFFICE_PRO' ) ) {
            $header_buttons ['get_pro'] = array(
                'title' => __('Get Pro', WP_OFFICE_TEXT_DOMAIN),
                'icon' => 'groups',
                'class' => '',
                'page' => '',
                'link' => 'https://officewp.com/pricing/',
            );
        }

        if( !current_user_can( 'administrator' ) ) {
            unset( $header_buttons['settings'] );
            unset( $header_buttons['tools'] );
        }

        if( !WO()->help()->get_page_help() ) {
            unset( $header_buttons['help'] );
        }

        //our_hook
        $header_buttons = apply_filters( 'wpoffice_admin_header_buttons', $header_buttons );


        $buttons = '';
        if ( !empty( $header_buttons ) ) {

            foreach(  $header_buttons as $key => $value ) {
                if ( empty( $current_tab ) )
                    $current_tab = $key;

                $active_class = ( !empty( $_GET['page'] ) && $_GET['page'] == $value['page'] ) ? 'wpo_active_page_tab' : '';
                if ( empty( $current_tab ) )
                    $current_tab = $key;


                if ( !empty( $value['link'] ) ) {
                    $link = $value['link'];
                } else {
                    $link = !empty( $value['page'] ) ? admin_url( 'admin.php?page=' . $value['page'] ) : 'javascript: void(0);';
                }


                $buttons .= '<a title="'. $value['title'] . '"
                href="' . $link . '"
                class="wpo_right_button wpo_' . $key . '_button ' . $value['class'] . ' ' . $active_class . '" ' . ( !empty( $value['link'] ) ? 'target="_blank"' : '' ) . '></a>';

            }
            ob_start();
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('#wpo_admin_header .wpo_help_button').click(function() {
                        jQuery.ajax({
                            type: "POST",
                            url: '<?php echo WO()->get_ajax_route('wpo!core!Members', 'save_help_flag'); ?>',
                            data: 'flag=' + ( jQuery(this).hasClass('wpo_active') ? '0' : '1' ),
                            dataType: 'json'
                        });
                        jQuery('.wpo_help_button').toggleClass('wpo_active');
                        jQuery('.wpo_help_box_wrap').toggleClass('visible');
                    });
                });
            </script>
            <?php
            $buttons .= ob_get_clean();
        }

        return $buttons;

    }

    /**
     * Get logo for our admin pages
     *
     * @return string
     */
    function get_plugin_logo_block() {
        $class = '';
        $settings = WO()->get_settings( 'common' );
        if ( !empty( $settings['enable_clouds_moves'] ) && 'yes' == $settings['enable_clouds_moves'] ) {
            $class= 'class="wpo_clouds_running"';
        }

        $html = WO()->plugin['logo_style'] . ' <div id="wpo_admin_header" ' . $class . '>
        <div class="wpo_admin_logo" id="wpo_admin_logo_block">' . WO()->plugin['logo_content'] . '</div>

        ' . $this->get_plugin_tabs_block() . '
        ' . $this->get_plugin_header_buttons() . '</div>' . WO()->hr( '0 0 10px 0' );

        if( $help = WO()->help()->get_page_help() ) {
            $flag = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
            $html .= '<div class="wpo_help_box_wrap ' . ( $flag ? 'visible' : '' ) . '"><div class="wpo_help_box">' . $help .'</div>' .
                WO()->hr( '0' ) . '</div>';
        }

        return $html . '<h2></h2>' ;
    }

    /**
     * Include scripts and styles
     */
    function include_css_js() {
        global $parent_file;

        //for enqueue default Wordpress scripts use no-conflict mode array
        // line 191 class.admin
        //scripts with start "wpo" symbols enqueue here
        if ( 'wp-office' == $parent_file || 'wp-office-act' == $parent_file ) {

            //no cashe for JS
            if ( !headers_sent() ) {
                header("Cache-Control: no-cache, no-store, must-revalidate");
                header("Pragma: no-cache");
                header("Expires: 0");
            }


            wp_enqueue_style( 'wpo-admin-general-style' );
            wp_enqueue_style( 'wpo-admin-header-style' );
            wp_enqueue_style( 'wpo-admin-buttons-style' );
            wp_enqueue_style( 'wpo-pulllayer-style' );
            wp_enqueue_style( 'wpo-confirm-style' );
            wp_enqueue_style( 'wpo-notice-style' );
            wp_enqueue_style( 'wpo-admin-form-style' );
            wp_enqueue_style( 'wpo-assign-style' );
            wp_enqueue_style( 'wpo-tooltip-style' );
            wp_enqueue_style( 'wpo-button-switch-style' );

            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wpo-pulllayer-js' );
            wp_enqueue_script( 'wpo-confirm-js' );
            wp_enqueue_script( 'wpo-notice-js' );
            wp_enqueue_script( 'wpo-base64-js' );
            wp_enqueue_script( 'wpo-assign-js' );
            wp_enqueue_script( 'wpo-tooltip-js' );

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
        }
    }

    /**
     * Show our admin pages
     */
    function wpoffice_menu_func() {
        if ( !empty( $this->plugin_submenus[$_GET['page']] ) ) {
            echo WO()->admin_menu()->get_plugin_logo_block();

            if ( empty( $_GET['tab'] ) ) {
                $tabs = array_values( array_keys( $this->plugin_submenus[$_GET['page']]['tabs'] ) );
                include $this->plugin_submenus[$_GET['page']]['tabs'][$tabs[0]]['file'];
            } else {
                if ( !empty( $this->plugin_submenus[$_GET['page']]['tabs'][$_GET['tab']] ) ) {
                    include $this->plugin_submenus[$_GET['page']]['tabs'][$_GET['tab']]['file'];
                }
            }
            add_action( 'wpoffice_admin_footer', array( &$this, 'our_admin_footer' ) );
        } else {
            wp_die( __( 'You do not have sufficient permissions to access this page.', WP_OFFICE_TEXT_DOMAIN ), 403 );
        }
    }

    /**
     * Sorting Menu array by order
     *
     * @param $a
     * @param $b
     * @return int
     */
    function sort_menu( $a, $b ) {
        //name of key for sort
        $key = 'order';

        if ( strtolower( $a[$key] ) == strtolower( $b[$key] ) )
            return 0;

        return ( strtolower( $a[$key] ) < strtolower( $b[$key] ) ) ? -1 : 1;
    }


    //maybe we do not need it?

    /**
     * Hide admin submenu from list of menu
     */
    function hide_admin_submenu() {
        global $submenu;

        //hide some menu
        if ( is_array( $this->plugin_submenus ) && count( $this->plugin_submenus ) ) {

            $n = ( isset( $submenu['wp-office'] ) ) ? count( $submenu['wp-office'] ) : 0;

            foreach ( $this->plugin_submenus as $key => $values ) {
                if ( isset( $values['hidden'] ) && true == $values['hidden'] ) {

                    for( $i = 0; $i < $n; $i++ ) {
                        if ( isset( $submenu['wp-office'][$i] ) && in_array( $key, $submenu['wp-office'][$i] ) )
                            unset( $submenu['wp-office'][$i] );
                    }
                }
            }
        }

    }


    /**
     * Create our Admin menu
     */
    function adminmenu() {
        global $submenu, $current_user;

        if ( !WO()->get_office_role( get_current_user_id() ) && !current_user_can( 'administrator' ) ) {
            return;
        }

        if( current_user_can( 'administrator' ) ) {
            $menu_cap = 'manage_options';
        } else {
            $menu_cap = $current_user->roles[0];
        }

        $this->plugin_submenus = array(
            'wp-office' => array(
                'tabs' => array(
                    'dashboard'   => array(
                        'title' => __( 'Dashboard', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'screenoptions',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/dashboard.php',
                        'visibility' => true
                    ),
                    'get_started'   => array(
                        'title' => __( 'Get Started', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'smiley',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/get_started.php',
                        'visibility' => true
                    ),
                    'features'   => array(
                        'title' => __( 'Features', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'forms',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/features.php',
                        'visibility' => true
                    ),
                    'system_status'   => array(
                        'title' => __( 'System Status', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'analytics',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/system_status.php',
                        'visibility' => true
                    ),
                ),
            ),
            'wp-office-members' => array(
                'page_title'        => __( 'Members', WP_OFFICE_TEXT_DOMAIN ),
                'menu_title'        => __( 'Members', WP_OFFICE_TEXT_DOMAIN ),
                'capability'        => $menu_cap,
                'function'          => array( &$this, 'wpoffice_menu_func' ),
                'hidden'            => false,
                'real'              => true,
                'order'             => 10,
                'tabs' => array(
                    'active'   => array(
                        'title' => __( 'Active', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'groups',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/members.php',
                        'visibility' => true
                    ),
                    'archive'   => array(
                        'title' => __( 'Archive', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'admin-users',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/members_archive.php',
                        'visibility' => true
                    ),
                ),
            ),
            'wp-office-contents' => array(
                'page_title'        => __( 'Contents', WP_OFFICE_TEXT_DOMAIN ),
                'menu_title'        => __( 'Contents', WP_OFFICE_TEXT_DOMAIN ),
                'capability'        => $menu_cap,
                'function'          => array( &$this, 'wpoffice_menu_func' ),
                'hidden'            => false,
                'real'              => true,
                'order'             => 20,
                'tabs' => array(
                    'office_pages'   => array(
                        'title' => __( 'Office Pages', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'admin-page',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/office_pages.php',
                        'visibility' => true
                    ),
                    'office_hubs'   => array(
                        'title' => __( 'Office HUBs', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'layout',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/office_hubs.php',
                        'visibility' => true
                    ),
                    'profiles'   => array(
                        'title' => __( 'Profiles', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'image-filter',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/profiles.php',
                        'visibility' => true
                    ),
                    'categories'   => array(
                        'title' => __( 'Categories', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => 'marker',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/categories.php',
                        'visibility' => true
                    ),
                ),
            ),
            'wp-office-payments' => array(
                'page_title'        => '',
                'menu_title'        => __( 'Payments', WP_OFFICE_TEXT_DOMAIN ),
                'capability'        => $menu_cap,
                'function'          => array( &$this, 'wpoffice_menu_func' ),
                'hidden'            => false,
                'real'              => true,
                'order'             => 25,
                'tabs' => array(
                    'paymens'   => array(
                        'file' => WO()->plugin_dir . 'includes/admin/payments.php',
                        'title' => __( 'Payments', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => '',
                        'class' => '',
                        'visibility' => true,
                        'real'       => false,
                    ),
                ),
            ),
            'wp-office-settings' => array(
                'page_title'        => '',
                'menu_title'        => __( 'Settings', WP_OFFICE_TEXT_DOMAIN ),
                'capability'        => $menu_cap,
                'function'          => array( &$this, 'wpoffice_menu_func' ),
                'hidden'            => false,
                'real'              => true,
                'order'             => 30,
                'tabs' => array(
                    'settings'   => array(
                        'title' => __( 'Settings', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => '',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/settings.php',
                        'visibility' => true,
                        'real'       => false,
                    ),
                ),
            ),
            //not for now
//            'wp-office-tools' => array(
//                'page_title'        => '',
//                'menu_title'        => __( 'Tools', WP_OFFICE_TEXT_DOMAIN ),
//                'capability'        => $menu_cap,
//                'function'          => array( &$this, 'wpoffice_menu_func' ),
//                'hidden'            => true,
//                'real'              => true,
//                'order'             => 1000,
//                'tabs' => array(
//                    'tools'   => array(
//                        'title' => __( 'Tools', WP_OFFICE_TEXT_DOMAIN ),
//                        'icon'  => '',
//                        'class' => '',
//                        'file' => WO()->plugin_dir . 'includes/admin/tools.php',
//                        'visibility' => true,
//                        'real'       => false,
//                    )
//                ),
//            ),
            'wp-office-help' => array(
                'page_title'        => '',
                'menu_title'        => __( 'Help', WP_OFFICE_TEXT_DOMAIN ),
                'capability'        => $menu_cap,
                'function'          => array( &$this, 'wpoffice_menu_func' ),
                'hidden'            => true,
                'real'              => true,
                'order'             => 1000,
                'tabs' => array(
                    'help'   => array(
                        'title' => __( 'Help', WP_OFFICE_TEXT_DOMAIN ),
                        'icon'  => '',
                        'class' => '',
                        'file' => WO()->plugin_dir . 'includes/admin/help.php',
                        'visibility' => false,
                        'real'       => false,
                    ),
                ),
            )

        );


        if ( !defined( 'WP_OFFICE_PRO' ) ) {
            $this->plugin_submenus['wp-office']['tabs']['features']['title'] = __( 'Pro Features', WP_OFFICE_TEXT_DOMAIN );
            $this->plugin_submenus['wp-office']['tabs']['features']['file']  = WO()->plugin_dir . 'includes/admin/features_pro.php';

            unset( $this->plugin_submenus['wp-office']['tabs']['get_started'] );
        }


        //check capabilities
        if ( !current_user_can( 'administrator' ) ) {

            $this->plugin_submenus['wp-office-members']['tabs']['active']['visibility'] = false;
            $this->plugin_submenus['wp-office-members']['tabs']['archive']['visibility'] = false;

            $child_roles_list = WO()->get_role_all_child( WO()->get_office_role( get_current_user_id() ) );
            foreach( $child_roles_list as $role ) {
                if ( WO()->current_member_can_manage( 'view_member', $role ) ) {
                    $this->plugin_submenus['wp-office-members']['tabs']['active']['visibility'] = true;

                    if ( WO()->current_member_can_manage( 'archive_member', $role ) ) {
                        $this->plugin_submenus['wp-office-members']['tabs']['archive']['visibility'] = true;
                    }
                }
            }

            //deny Office pages tab
            if ( !WO()->current_member_can( 'view_office_page' ) ) {
                $this->plugin_submenus['wp-office-contents']['tabs']['office_pages']['visibility'] = false;
            }

            //deny HUB pages tab
            if ( !WO()->current_member_can( 'view_office_hub' ) ) {
                $this->plugin_submenus['wp-office-contents']['tabs']['office_hubs']['visibility'] = false;
            }

            //deny Profiles tab
            if ( !WO()->current_member_can( 'view_profile' ) ) {
                $this->plugin_submenus['wp-office-contents']['tabs']['profiles']['visibility'] = false;
            }

            $cats_object = new \wpo\list_table\List_Table_Categories();
            $filters = $cats_object->get_filters_line();
            if ( empty( $filters ) ) {
                $this->plugin_submenus['wp-office-contents']['tabs']['categories']['visibility'] = false;
            }

            $this->plugin_submenus['wp-office']['tabs']['system_status']['visibility'] = false;
            $this->plugin_submenus['wp-office']['tabs']['get_started']['visibility'] = false;
            $this->plugin_submenus['wp-office']['tabs']['dashboard']['visibility'] = false;

            $this->plugin_submenus['wp-office-tools']['tabs']['tools']['visibility'] = false;
            $this->plugin_submenus['wp-office-settings']['tabs']['settings']['visibility'] = false;
        }

        //our_hook
        $this->plugin_submenus = apply_filters( 'wpoffice_admin_submenus', $this->plugin_submenus, $menu_cap );

        //clear not visibility menus/tabs
        if ( !empty( $this->plugin_submenus ) ) {
            foreach( $this->plugin_submenus as $submenu_key => $submenu_value ) {

                $submenu_visibility = false;
                if ( !empty( $submenu_value['tabs'] ) ) {
                    foreach( $submenu_value['tabs'] as $tab_key => $tab_value ) {

                        if ( !empty( $tab_value['visibility'] ) && true == $tab_value['visibility'] ) {
                            $submenu_visibility = true;
                        } else {
                            unset( $this->plugin_submenus[$submenu_key]['tabs'][$tab_key] );
                        }

                    }
                }

                if ( ! $submenu_visibility ) {
                    unset( $this->plugin_submenus[$submenu_key] );
                }

            }
        }

        @uasort( $this->plugin_submenus, array( &$this, 'sort_menu' ) );

        
        //add main plugin menu
        add_menu_page( WO()->plugin['title'], WO()->plugin['title'], $menu_cap, 'wp-office', array(&$this, 'wpoffice_menu_func'), WO()->plugin['icon_url'], '2,000000000006' );

        //add submenu
        if ( is_array( $this->plugin_submenus ) && count( $this->plugin_submenus ) ) {
            foreach ( $this->plugin_submenus as $key => $values ) {
                if ( isset( $values['real'] ) && true == $values['real'] && 'wp-office' != $key ) {
                add_submenu_page( 'wp-office', $values['page_title'], $values['menu_title'], $values['capability'], $key, $values['function'] );
                }
            }
        }
        

        //rename main menu
        if( isset( $submenu['wp-office'][0][0] ) ) {
            $submenu['wp-office'][0][0] = __( 'Dashboard', WP_OFFICE_TEXT_DOMAIN );
        }
    }

    function remove_menus() {
        if( !current_user_can( 'administrator' ) ) {
            remove_submenu_page( 'wp-office', 'wp-office' );    //Remove Dashboard
        }

        remove_menu_page( 'post-new.php?post_type=office_page' );
        remove_menu_page( 'edit.php?post_type=office_page' );

        remove_menu_page( 'post-new.php?post_type=office_hub' );
        remove_menu_page( 'edit.php?post_type=office_hub' );
    }


    /**
     * Function activate our menu when edit our post types
     *
     */
    function menu_active() {
        global $parent_file, $submenu_file, $post_type;

        switch ( $post_type ) {
            case 'office_hub' :
            case 'office_page' :
            //neeed fix for TinyMCE
//                        $parent_file = 'wp-office';
//                        $submenu_file = 'wp-office-contents';
            break;
        }
    }


    /**
     * Add back button to our post types add\edit pages
     *
     * @return void
     */
    function add_move_to_trash_button() {
        global $post;
        if ( !empty( $post->post_type ) ) {
            if ( 'office_page' == $post->post_type ) {
                //show trash link
                $show_trash_link = false;
                if ( current_user_can( 'administrator' ) || WO()->current_member_can( 'delete_office_page' ) == 'on' ) {
                    $show_trash_link = true;
                } else {
                    $delete_page_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page', 'delete' );
                    if ( in_array( $post->ID, $delete_page_ids ) ) {
                        $show_trash_link = true;
                    }
                }

                if ( $show_trash_link ) {

                    wp_enqueue_script( 'wpo-confirm-js' );
                    wp_enqueue_script( 'wpo-notice-js' );
                    wp_enqueue_style( 'wpo-confirm-style' );
                    wp_enqueue_style( 'wpo-notice-style' ); ?>

                    <div id="delete-action"><a class="submitdelete deletion" href="javascript:void(0);"><?php _e( 'Move to Trash', WP_OFFICE_TEXT_DOMAIN ) ?></a></div>

                    <style type="text/css">
                        #visibility {
                            display: none;
                        }
                    </style>

                    <script type="text/javascript">
                        jQuery(document).ready(function(){
                            var body = jQuery( 'body' );

                            body.on( 'click', '.submitdelete.deletion', function(e) {
                                var obj = jQuery(this);
                                jQuery.wpo_confirm({
                                    message : '<?php _e( 'Are you sure move to trash this page?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                                    onYes: function() {
                                        jQuery.ajax({
                                            type: "POST",
                                            url: '<?php echo WO()->get_ajax_route( 'wpo\list_table\List_Table_Pages', 'trash_page' ) ?>',
                                            data: 'id=<?php echo $post->ID ?>',
                                            dataType: 'json',
                                            timeout: 20000,
                                            success: function( data ) {
                                                if( data.status ) {
                                                    window.location = "<?php echo add_query_arg( array( 'page'=>'wp-office-contents' ), get_admin_url() . 'admin.php' ) ?>";
                                                } else {
                                                    jQuery( this ).wpo_notice({
                                                        message : data.message,
                                                        type : 'error'
                                                    });
                                                }
                                            }
                                        });
                                    },
                                    object: this
                                });
                            });
                        });
                    </script>
                <?php }
            } elseif ( 'office_hub' == $post->post_type ) {
                //show trash link
                $show_trash_link = false;
                if ( current_user_can( 'administrator' ) || WO()->current_member_can( 'delete_office_hub' ) == 'on' ) {
                    $show_trash_link = true;
                } else {
                    $delete_page_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_hub', 'delete' );
                    if ( in_array( $post->ID, $delete_page_ids ) ) {
                        $show_trash_link = true;
                    }
                }

                if ( $show_trash_link ) {

                    wp_enqueue_script( 'wpo-confirm-js' );
                    wp_enqueue_script( 'wpo-notice-js' );
                    wp_enqueue_style( 'wpo-confirm-style' );
                    wp_enqueue_style( 'wpo-notice-style' ); ?>

                    <div id="delete-action"><a class="submitdelete deletion" href="javascript:void(0);"><?php _e( 'Move to Trash', WP_OFFICE_TEXT_DOMAIN ) ?></a></div>

                    <style type="text/css">
                        #visibility {
                            display: none;
                        }
                    </style>

                    <script type="text/javascript">
                        jQuery(document).ready(function(){
                            var body = jQuery( 'body' );

                            body.on( 'click', '.submitdelete.deletion', function(e) {
                                var obj = jQuery(this);
                                jQuery.wpo_confirm({
                                    message : '<?php _e( 'Are you sure move to trash this page?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                                    onYes: function() {
                                        jQuery.ajax({
                                            type: "POST",
                                            url: '<?php echo WO()->get_ajax_route( 'wpo\list_table\List_Table_Hubs', 'trash_hub' ) ?>',
                                            data: 'id=<?php echo $post->ID ?>',
                                            dataType: 'json',
                                            timeout: 20000,
                                            success: function( data ) {
                                                if( data.status ) {
                                                    window.location = "<?php echo add_query_arg( array( 'page'=>'wp-office-contents' ), get_admin_url() . 'admin.php' ) ?>";
                                                } else {
                                                    jQuery( this ).wpo_notice({
                                                        message : data.message,
                                                        type : 'error'
                                                    });
                                                }
                                            }
                                        });
                                    },
                                    object: this
                                });
                            });
                        });
                    </script>
                <?php }
            }
        }
    }



    function add_back_button( $post ) {

        if ( !empty( $post->post_type ) ) {
            $back_url = '';
            $back_url_text = '';

            if ( 'office_hub' == $post->post_type ) {
                $back_url = get_admin_url() . 'admin.php?page=wp-office-contents&tab=office_hubs';
                $back_url_text = __( 'Back To Office HUBs', WP_OFFICE_TEXT_DOMAIN );
            } elseif ( 'office_page' == $post->post_type ) {
                $back_url = get_admin_url() . 'admin.php?page=wp-office-contents&tab=office_pages';
                $back_url_text = __( 'Back To Office Pages', WP_OFFICE_TEXT_DOMAIN );
            }

            if ( $back_url ) {
                ?>
                <a class="page-title-action" id="wpo_posttype_back_button" title="<?php echo $back_url_text ?>" href="<?php echo $back_url ?>"><span class="dashicons dashicons-arrow-left-alt2"></span></a>

                <script type="text/javascript">
                    if ( jQuery( '.wrap h1').length ) {
                        jQuery( '#wpo_posttype_back_button' ).appendTo( jQuery( '.wrap h1') ).show();
                    }
                </script>

                <?php
            }

        }

    }

    function activation_logo() {

        ?>

        <style type='text/css'>
            .wpo_activation_logo {
                background: url( '<?php echo WO()->plugin_url ?>assets/images/act_logo.png' ) no-repeat transparent;
                width: 190px;
                height: 50px;
                margin: 0 auto;
            }

        </style>

        <div class="wpo_activation_logo"></div>


        <?php

    }
    //end class
}
