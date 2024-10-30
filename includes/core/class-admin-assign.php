<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Assign {

    /**
     * Various information needed for displaying the pagination
     *
     * @var array
     * @access protected
     */
    protected $_pagination_args = array();


    /**
     * Assign popup tabs array
     *
     * @var array
     * @access public
     */
    public $_assign_tabs = array();


    /**
     * Pagination items Per Page variable
     *
     * @var int
     * @access public
     */
    public $per_page = 25;


    /**
     * PHP 5 constructor
     **/
    function __construct() {
        //assign popup titles
        add_filter( 'wpoffice_assign_form_title_office_page', array( &$this, 'office_page_title' ), 99 );
        add_filter( 'wpoffice_assign_form_title_office_hub', array( &$this, 'office_hub_title' ), 99 );
        add_filter( 'wpoffice_assign_form_title_user', array( &$this, 'member_title' ), 99 );
        add_filter( 'wpoffice_assign_form_title_office_page_category', array( &$this, 'office_page_category_title' ), 99 );


        //assign popup arguments (tabs) for different objects
        add_filter( 'wpoffice_user_assign_form_args', array( &$this, 'user_form_args' ), 99, 2 );
        add_filter( 'wpoffice_office_page_assign_form_args', array( &$this, 'office_page_form_args' ), 99 );
        add_filter( 'wpoffice_office_hub_assign_form_args', array( &$this, 'office_hub_form_args' ), 99 );
        add_filter( 'wpoffice_profile_assign_form_args', array( &$this, 'profile_form_args' ), 99 );
        add_filter( 'wpoffice_office_page_category_assign_form_args', array( &$this, 'office_page_category_form_args'), 99 );

        //assign popup content for different tabs
        add_filter( 'wpoffice_load_assign_tab_member_content', array( &$this, 'load_assign_tab_member' ), 99 );
        add_filter( 'wpoffice_load_assign_tab_user_content', array( &$this, 'load_assign_tab_user' ), 99 );
        add_filter( 'wpoffice_load_assign_tab_profile_content', array( &$this, 'load_assign_tab_profile' ), 99 );
        add_filter( 'wpoffice_load_assign_tab_office_page_content', array( &$this, 'load_assign_tab_office_page' ), 99 );
        add_filter( 'wpoffice_load_assign_tab_office_page_category_content', array( &$this, 'load_assign_tab_office_page_category' ), 99 );
        add_filter( 'wpoffice_load_assign_tab_roles_content', array( &$this, 'load_assign_tab_roles' ), 99 );
        add_filter( 'wpoffice_load_assign_tab_plugin_roles_content', array( &$this, 'load_assign_tab_plugin_roles' ), 99 );


        add_action( 'wpoffice_assign_items_profile', array( &$this, 'reverse_assign_items' ), 99 );
    }


    /**
     * Parse assign value
     *
     * @param string $assigns
     * @return array
     */
    public function parse_assign_value( $assigns ) {
        $assigns = json_decode( base64_decode( $assigns ) );

        $items = array();
        if ( !empty( $assigns ) ) {
            foreach( $assigns as $data=>$inner_items ) {
                $data = json_decode( base64_decode( $data ) );

                if( !empty( $items[$data->key] ) ) {
                    $items[$data->key] = array_merge( $items[$data->key], $inner_items );
                } else {
                    $items[$data->key] = $inner_items;
                }
            }
        }

        return $items;
    }


    /**
     * Function for getting pagination data
     * when ajax pagination, search worked in Assign Popup
     *
     * @return array
     */
    public function pagination_data() {
        $paged          = $this->get_pagenum();
        $items_count    = $this->get_pagination_arg( 'total_items' );
        $pagination     = array(
            'current_page'  => $paged,
            'start'         => $this->per_page * ( $paged - 1 ) + 1,
            'end'           => ( $this->per_page * ( $paged - 1 ) + $this->per_page < $items_count ) ? $this->per_page * ( $paged - 1 ) + $this->per_page : $items_count,
            'count'         => (string)$items_count,
            'pages_count'   => ceil( $items_count/$this->per_page )
        );

        return $pagination;
    }


    /**
     * Access the pagination args.
     *
     * @param string $key Pagination argument to retrieve. Common values include 'total_items',
     *                    'total_pages', 'per_page', or 'infinite_scroll'.
     * @return int Number of items that correspond to the given pagination argument.
     */
    function get_pagination_arg( $key ) {
        if ( 'page' == $key )
            return $this->get_pagenum();

        if ( isset( $this->_pagination_args[$key] ) )
            return $this->_pagination_args[$key];

        return false;
    }


    /**
     * An internal method that sets all the necessary pagination arguments
     *
     * @param array $args An associative array with information about the pagination
     * @param array|string $args
     */
    function set_pagination_args( $args ) {
        $args = wp_parse_args( $args, array(
            'total_items' => 0,
            'total_pages' => 0,
            'per_page' => $this->per_page,
        ) );

        if ( !$args['total_pages'] && $args['per_page'] > 0 )
            $args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );

        $this->_pagination_args = $args;
    }


    /**
     * Function for getting current page
     * of Assign Popup
     *
     * @return mixed
     */
    public function get_pagenum() {
        $pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

        if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
            $pagenum = $this->_pagination_args['total_pages'];

        return max( 1, $pagenum );
    }


    /**
     * Function for reverse set assign items
     * is different for different objects
     *
     * User, Page, File, etc....
     *
     * @param $items
     */
    function reverse_assign_items( $items ) {
        //default assign
        $items = WO()->assign()->parse_assign_value( $items );

        $assigned = 0;
        foreach( $items as $key=>$inner_items ) {
            $assigned += WO()->set_reverse_assign_data( $key, $inner_items, $_REQUEST['object'], $_REQUEST['object_id'] );
        }

        exit( json_encode( array(
            'status'    => true,
            'count'     => $assigned,
            'message'   => sprintf( __( 'Assigned %s items', WP_OFFICE_TEXT_DOMAIN ), $assigned )
        ) ) );
    }


    /**
     * Function for set assign items
     * is different for different objects
     *
     * User, Page, File, etc....
     */
    function assign_items() {
        /*wpo_hook_
            hook_name: wpoffice_assign_items_ + ['object']
            hook_title: Assign Items
            hook_description: Hook runs before assign items to object.
            hook_type: action
            hook_in: wp-office
            hook_location class-admin-assign.php
            hook_param: string items
            hook_since: 1.0.0
        */
        do_action( 'wpoffice_assign_items_' . $_REQUEST['object'], $_REQUEST['items'] );

        $items = WO()->assign()->parse_assign_value( $_REQUEST['items'] );

        $args_list = ( !empty( $_REQUEST['args_list'] ) && in_array( $_REQUEST['args_list'], array( 'reverse', 'direct' ) ) ) ? $_REQUEST['args_list'] : 'all';
        $this->_assign_tabs = apply_filters( 'wpoffice_' . $_REQUEST['object'] . '_assign_form_args', array(), $_REQUEST['object_id'], $args_list );

        //default assign
        $assigned = 0;
        foreach( $items as $key=>$inner_items ) {
            if( !empty( $this->_assign_tabs[$_REQUEST['object']][$key]['reverse'] ) ) {
                $object = ( 'user' == $_REQUEST['object'] ) ? 'member' : $_REQUEST['object'];
                $assigned += WO()->set_reverse_assign_data( $key, $inner_items, $object, $_REQUEST['object_id'] );
            } else {
                $assigned += WO()->set_assign_data( $_REQUEST['object'], $_REQUEST['object_id'], $key, $inner_items );
            }
        }

        exit( json_encode( array(
            'status'    => true,
            'count'     => $assigned,
            'message'   => sprintf( __( 'Assigned %s items', WP_OFFICE_TEXT_DOMAIN ), $assigned )
        ) ) );
    }


    /**
     * Function which load items roles
     * when roles tab clicked in popup
     *
     * @param $result
     * @return array
     */
    function load_assign_tab_plugin_roles( $result ) {

        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );

        global $wp_roles;
        $roles = WO()->get_settings( 'roles' );

        $plugin_roles = array();
        foreach( $wp_roles->roles as $role_key=>$role ) {
            if ( in_array( $role_key, array_keys( $roles ) ) || 'administrator' == $role_key )
                $plugin_roles[$role_key] = $role;
        }
        $roles = $plugin_roles;

        //search roles by name and exclude
        if ( !empty( $_REQUEST['search'] ) ) {
            $role_names = array();
            foreach ( $roles as $role ) {
                $role_names[] = strtolower( $role['name'] );
            }

            $role_names = preg_grep("/" . strtolower( trim( $_REQUEST['search'] ) ) . "/", $role_names );

            foreach ( $roles as $key=>$role ) {
                if ( !in_array( strtolower( $role['name'] ), $role_names ) ) {
                    unset( $roles[$key] );
                }
            }
        }

        $all_ids = array_keys( $roles );

        //set pagination arguments and get pagination data
        WO()->assign()->set_pagination_args( array( 'total_items' => count( $roles ) ) );
        $pagination = WO()->assign()->pagination_data();

        $assigned = WO()->get_assign_data_by_object( $_REQUEST['object'], $_REQUEST['object_id'], 'profile' );

        $data = array();
        $roles = array_slice( $roles, $pagination['start'] - 1, WO()->assign()->per_page );
        foreach( $roles as $role_key=>$value ) {
            $data['items'][] = array(
                'title' => $value['name'],
                'value' => $role_key,
                'checked' => in_array( $role_key, $assigned )
            );
        }

        $data['type'] = ( !empty( $additional_data['type'] ) && 'radio' == $additional_data['type'] ) ? 'radio' : 'checkbox';
        $data['assigned'] = $assigned;

        $result = array(
            'status'        => true,
            'data'          => $data,
            'pagination'    => $pagination,
            'all_ids'       => $all_ids
        );

        return $result;
    }


    /**
     * Function which load items roles
     * when roles tab clicked in popup
     *
     * @param $result
     * @return array
     */
    function load_assign_tab_roles( $result ) {

        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );
        $exclude_roles = apply_filters( 'wpoffice_assign_tab_roles_exclude', array(), $additional_data );

        global $wp_roles;
        $roles = $wp_roles->roles;

        //exclude already assigned to other redirects roles
        if ( !empty( $exclude_roles ) ) {
            foreach ( $exclude_roles as $role ) {
                if ( isset( $roles[$role] ) )
                    unset( $roles[$role] );
            }
        }

        //search roles by name and exclude
        if ( !empty( $_REQUEST['search'] ) ) {
            $role_names = array();
            foreach ( $roles as $role ) {
                $role_names[] = strtolower( $role['name'] );
            }

            $role_names = preg_grep("/" . strtolower( trim( $_REQUEST['search'] ) ) . "/", $role_names );

            foreach ( $roles as $key=>$role ) {
                if ( !in_array( strtolower( $role['name'] ), $role_names ) ) {
                    unset( $roles[$key] );
                }
            }
        }

        $all_ids = array_keys( $roles );

        //set pagination arguments and get pagination data
        WO()->assign()->set_pagination_args( array( 'total_items' => count( $roles ) ) );
        $pagination = WO()->assign()->pagination_data();

        $assigned = WO()->get_assign_data_by_object( $_REQUEST['object'], $_REQUEST['object_id'], 'profile' );

        $data = array();
        $roles = array_slice( $roles, $pagination['start'] - 1, WO()->assign()->per_page );
        foreach( $roles as $role_key=>$value ) {
            $data['items'][] = array(
                'title' => $value['name'],
                'value' => $role_key,
                'checked' => in_array( $role_key, $assigned )
            );
        }

        $data['type'] = ( !empty( $additional_data['type'] ) && 'radio' == $additional_data['type'] ) ? 'radio' : 'checkbox';
        $data['assigned'] = $assigned;

        $result = array(
            'status'        => true,
            'data'          => $data,
            'pagination'    => $pagination,
            'all_ids'       => $all_ids
        );

        return $result;
    }


    /**
     * Function which load items categories
     * when category page tab clicked in popup
     *
     * @param $result
     * @return array
     */
    function load_assign_tab_office_page_category( $result ) {
        global $wpdb;

        $paged      = $this->get_pagenum();

        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'o.title'
            ) );
        }

        $include = '';
        if( !current_user_can( 'administrator' ) && WO()->current_member_can( 'view_office_page_category' ) != 'on' ) {
            $assigned_categories = WO()->get_access_content_ids( get_current_user_id(), 'office_page_category' );
            $include = " AND o.id IN('" . implode( "','", $assigned_categories ) . "')";
        }

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT o.id )
            FROM {$wpdb->prefix}wpo_objects o
            WHERE o.type = 'office_page_category'
                $search
                $include"
        );

        $all_ids = $wpdb->get_col(
            "SELECT DISTINCT o.id
            FROM {$wpdb->prefix}wpo_objects o
            WHERE o.type = 'office_page_category'
                $search
                $include"
        );

        $this->set_pagination_args( array( 'total_items' => $items_count ) );

        $order_by = 'o.title';
        $order = 'ASC';

        $items = $wpdb->get_results(
            "SELECT o.*
            FROM {$wpdb->prefix}wpo_objects o
            WHERE o.type = 'office_page_category'
                $search
                $include
            ORDER BY $order_by $order
            LIMIT ". ( ( $paged - 1 )*$this->per_page ). "," . $this->per_page,
        ARRAY_A );


        $assigned = WO()->get_assign_data_by_assign( 'office_page_category', $_REQUEST['object'], $_REQUEST['object_id'] );

        $data = array();
        foreach( $items as $item ) {
            $data['items'][] = array(
                'title' => $item['title'],
                'value' => $item['id'],
                'checked' => in_array( $item['id'], $assigned )
            );
        }
        $data['type'] = ( !empty( $additional_data['type'] ) && 'radio' == $additional_data['type'] ) ? 'radio' : 'checkbox';
        $data['assigned'] = $assigned;

        $result = array(
            'status'        => true,
            'data'          => $data,
            'pagination'    => $this->pagination_data(),
            'all_ids'       => $all_ids
        );

        return $result;
    }


    /**
     * Function which load items office page
     * when office page tab clicked in popup
     *
     * @param $result
     * @return array
     */
    function load_assign_tab_office_page( $result ) {
        global $wpdb;

        $paged      = $this->get_pagenum();

        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'p.post_title'
            ) );
        }

        $include = '';
        if( !current_user_can( 'administrator' ) && WO()->current_member_can( 'view_office_page' ) != 'on' ) {
            $assigned_pages = WO()->get_access_content_ids( get_current_user_id(), 'office_page' );
            $include = " AND p.ID IN('" . implode( "','", $assigned_pages ) . "')";
        }

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT p.ID )
            FROM {$wpdb->posts} p
            WHERE p.post_type='office_page' AND
                p.post_status != 'auto-draft'
                $include
                $search"
        );

        $all_ids = $wpdb->get_col(
            "SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            WHERE p.post_type='office_page' AND
                p.post_status != 'auto-draft'
                $include
                $search"
        );

        $this->set_pagination_args( array( 'total_items' => $items_count ) );

        $order_by = 'p.post_title';
        $order = 'ASC';

        $items = $wpdb->get_results(
            "SELECT p.*
            FROM {$wpdb->posts} p
            WHERE p.post_type='office_page' AND
                p.post_status != 'auto-draft'
                $include
                $search
            ORDER BY $order_by $order
            LIMIT ". ( ( $paged - 1 )*$this->per_page ). "," . $this->per_page,
        ARRAY_A );


        $assigned = WO()->get_assign_data_by_assign( 'office_page', $_REQUEST['object'], $_REQUEST['object_id'] );

        $data = array();
        foreach( $items as $item ) {
            $data['items'][] = array(
                'title' => $item['post_title'],
                'value' => $item['ID'],
                'checked' => in_array( $item['ID'], $assigned )
            );
        }
        $data['type'] = ( !empty( $additional_data['type'] ) && 'radio' == $additional_data['type'] ) ? 'radio' : 'checkbox';
        $data['assigned'] = $assigned;

        $result = array(
            'status'        => true,
            'data'          => $data,
            'pagination'    => $this->pagination_data(),
            'all_ids'       => $all_ids
        );

        return $result;
    }


    /**
     * Function which load items profile
     * when profile tab clicked in popup
     *
     * @param $result
     * @return array
     */
    function load_assign_tab_profile( $result ) {
        global $wpdb;

        $paged      = $this->get_pagenum();

        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'o.title'
            ) );
        }

        $include = '';
        if( !current_user_can( 'administrator' ) && WO()->current_member_can( 'view_profile' ) != 'on' ) {
            $assigned_profiles = WO()->get_access_content_ids( get_current_user_id(), 'profile' );
            $include = ' AND o.id IN("' . implode( '","', $assigned_profiles ) . '")';
        }

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT o.id )
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id
            WHERE o.type = 'profile'
                $include
                $search"
        );

        $all_ids = $wpdb->get_col(
            "SELECT DISTINCT o.id
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id
            WHERE o.type = 'profile'
                $include
                $search"
        );

        $this->set_pagination_args( array( 'total_items' => $items_count ) );

        $order_by = 'o.title';
        $order = 'ASC';

        $items = $wpdb->get_results(
            "SELECT o.*
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id
            WHERE o.type = 'profile'
                $include
                $search
            ORDER BY $order_by $order
            LIMIT ". ( ( $paged - 1 )*$this->per_page ). "," . $this->per_page,
        ARRAY_A );


        $assigned = WO()->get_assign_data_by_object( $_REQUEST['object'], $_REQUEST['object_id'], 'profile' );

        $data = array();
        foreach( $items as $item ) {
            $data['items'][] = array(
                'title' => $item['title'],
                'value' => $item['id'],
                'checked' => in_array( $item['id'], $assigned )
            );
        }
        $data['type'] = ( !empty( $additional_data['type'] ) && 'radio' == $additional_data['type'] ) ? 'radio' : 'checkbox';
        $data['assigned'] = $assigned;

        $result = array(
            'status'        => true,
            'data'          => $data,
            'pagination'    => $this->pagination_data(),
            'all_ids'       => $all_ids
        );

        return $result;
    }


    /**
     * Function which load items members
     * when members tabs clicked in popup
     *
     * @param $result
     * @return array
     */
    function load_assign_tab_user( $result ) {
        global $wpdb;

        $paged      = $this->get_pagenum();

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'u.user_login',
                'u.user_email'
            ) );
        }

        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );

        $current_role = '';
        if( !empty( $additional_data['role'] ) ) {
            $current_role = $additional_data['role'];
        }

        $include = '';
        if( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $current_role ) == 'assigned' ) {
            //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
            $assigned_users = WO()->get_available_members_by_role( $current_role, get_current_user_id() );
            $include = " AND u.ID IN('" . implode( "','", $assigned_users ) . "')";
        }

        $excluded_members = WO()->members()->get_excluded_members( false, $current_role );
        $exclude = '';
        if( count( $excluded_members ) ) {
            $exclude .= ' AND u.ID NOT IN( "' . implode( '","', $excluded_members ) . '" )';
        }

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT u.ID )
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%:\"{$current_role}\";%'
                $include
                $exclude
                $search"
        );

        $all_ids = $wpdb->get_col(
            "SELECT DISTINCT u.ID
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%:\"{$current_role}\";%'
                $include
                $exclude
                $search"
        );

        $this->set_pagination_args( array( 'total_items' => $items_count ) );

        $order_by = 'u.user_login';
        $order = 'ASC';

        $items = $wpdb->get_results(
            "SELECT u.*
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%:\"{$current_role}\";%'
                $include
                $exclude
                $search
            ORDER BY $order_by $order
            LIMIT ". ( ( $paged - 1 )*$this->per_page ). "," . $this->per_page,
        ARRAY_A );

        $assigned = WO()->get_assign_data_by_assign( 'user', $_REQUEST['object'], $_REQUEST['object_id'] );

        $assigned_users = array();
        if ( count( $assigned ) ) {
            $assigned_users = get_users( array(
                'role'      => $current_role,
                'include'   => $assigned,
                'fields'    => 'ids'
            ) );
        }

        $data = array();
        foreach( $items as $item ) {
            $data['items'][] = array(
                'title' => $item['user_login'],
                'value' => $item['ID'],
                'checked' => in_array( $item['ID'], $assigned_users )
            );
        }
        $data['type'] = ( !empty( $additional_data['type'] ) && 'radio' == $additional_data['type'] ) ? 'radio' : 'checkbox';
        $data['key'] = $current_role;
        $data['assigned'] = $assigned_users;

        $result = array(
            'status'        => true,
            'data'          => $data,
            'pagination'    => $this->pagination_data(),
            'all_ids'       => $all_ids
        );

        return $result;
    }


    /**
     * Function which load items members
     * when members tabs clicked in popup
     *
     * @param $result
     * @return array
     */
    function load_assign_tab_member( $result ) {
        global $wpdb;

        $paged      = $this->get_pagenum();

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'u.user_login',
                'u.user_email'
            ) );
        }

        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );

        $current_role = '';
        if( !empty( $additional_data['role'] ) ) {
            $current_role = $additional_data['role'];
        }

        $include = '';
        if( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $current_role ) == 'assigned' ) {
            //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
            $assigned_users = WO()->get_available_members_by_role( $current_role, get_current_user_id() );
            $include = " AND u.ID IN('" . implode( "','", $assigned_users ) . "')";
        }

        $excluded_members = WO()->members()->get_excluded_members( false, $current_role );
        $excluded_members = apply_filters( 'wpoffice_assign_tab_member_exclude',  $excluded_members, $additional_data );
        $exclude = '';
        if( count( $excluded_members ) ) {
            $exclude .= ' AND u.ID NOT IN( "' . implode( '","', $excluded_members ) . '" )';
        }

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT u.ID )
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%:\"{$current_role}\";%'
                $include
                $exclude
                $search"
        );

        $all_ids = $wpdb->get_col(
            "SELECT DISTINCT u.ID
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%:\"{$current_role}\";%'
                $include
                $exclude
                $search"
        );

        $this->set_pagination_args( array( 'total_items' => $items_count ) );

        $order_by = 'u.user_login';
        $order = 'ASC';

        $items = $wpdb->get_results(
            "SELECT u.*
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%:\"{$current_role}\";%'
                $include
                $exclude
                $search
            ORDER BY $order_by $order
            LIMIT ". ( ( $paged - 1 )*$this->per_page ). "," . $this->per_page,
        ARRAY_A );


        $assigned = WO()->get_assign_data_by_object( $_REQUEST['object'], $_REQUEST['object_id'], 'member' );

        $assigned_users = array();
        if ( count( $assigned ) ) {
            $assigned_users = get_users( array(
                'role'      => $current_role,
                'include'   => $assigned,
                'fields'    => 'ids'
            ) );
        }

        $data = array();
        foreach( $items as $item ) {
            $data['items'][] = array(
                'title' => $item['user_login'],
                'value' => $item['ID'],
                'checked' => in_array( $item['ID'], $assigned_users )
            );
        }

        $data['type'] = ( !empty( $additional_data['type'] ) && 'radio' == $additional_data['type'] ) ? 'radio' : 'checkbox';
        $data['key'] = $current_role;
        $data['assigned'] = $assigned_users;

        $result = array(
            'status'        => true,
            'data'          => $data,
            'pagination'    => $this->pagination_data(),
            'all_ids'       => $all_ids
        );

        return $result;
    }


    /**
     * Load data by AJAX with items to assign popup
     * is different for different assign popup tabs
     *
     * Member(one for all roles), Profile, etc....
     */
    function load_assign_tab_content() {
        $additional_data = json_decode( base64_decode( $_REQUEST['additional_data'] ), true );
        $result = apply_filters( 'wpoffice_load_assign_tab_' . $additional_data['key'] . '_content', array(), $_REQUEST['object'] );
        exit( json_encode( $result ) );
    }


    /**
     * Function for set Assign Popup Tabs
     * Category assign popup
     *
     * @param $args
     * @return mixed
     */
    function office_page_category_form_args( $args ) {

        if ( WO()->current_member_can( 'view_profile' ) ) {
            $args['office_page_category']['profile'] = array(
                'title' => __( 'Profile', WP_OFFICE_TEXT_DOMAIN )
            );
        }

        return $args;
    }

    /**
     * Function for set Assign Popup Tabs
     * Profile assign popup
     *
     * @param $args
     * @return mixed
     */
    function profile_form_args( $args ) {
        //$roles_list = WO()->get_settings( 'roles' );
        $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );
        if ( !empty( $roles_list ) ) {
            foreach( $roles_list as $role=>$role_data ) {
                $args['profile']['user'][$role] = array(
                    'title' => $roles_list[$role]['title'],
                );
            }
            $args['profile']['user']['reverse'] = true;
        }

        if ( WO()->current_member_can( 'view_office_page' ) ) {
            $args['profile']['office_page'] = array(
                'title' => __( 'Page', WP_OFFICE_TEXT_DOMAIN ),
                'reverse' => true
            );
        }

        if ( WO()->current_member_can( 'view_office_page_category' ) ) {
            $args['profile']['office_page_category'] = array(
                'title' => __( 'Page Category', WP_OFFICE_TEXT_DOMAIN ),
                'reverse' => true
            );
        }

        return $args;
    }

    /**
     * Function for set Assign Popup Tabs
     * Office HUB assign popup
     *
     * @param $args
     * @return mixed
     */
    function office_hub_form_args( $args ) {
        $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );

        foreach( $roles_list as $role=>$role_data ) {
            $args['office_hub']['member'][$role] = array(
                'title' => $roles_list[$role]['title'],
            );
        }

        return $args;
    }

    /**
     * Function for set Assign Popup Tabs
     * Office Page assign popup
     *
     * @param $args
     * @return mixed
     */
    function office_page_form_args( $args ) {
        $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );

        foreach( $roles_list as $role=>$role_data ) {
            $args['office_page']['member'][$role] = array(
                'title' => $roles_list[$role]['title'],
            );
        }

        if ( WO()->current_member_can( 'view_profile' ) ) {
            $args['office_page']['profile'] = array(
                'title' => __( 'Profile', WP_OFFICE_TEXT_DOMAIN )
            );
        }

        return $args;
    }


    /**
     * Function for set Assign Popup Tabs
     * Member assign popup
     *
     * @param $args
     * @param $user_id
     * @return mixed
     */
    function user_form_args( $args, $user_id = false ) {
        $roles_list = WO()->get_settings( 'roles' );

        if ( !$user_id ) {
            if ( !empty( $_REQUEST['object_id'] ) ) {
                $user_id = $_REQUEST['object_id'];
                $user = get_userdata( $_REQUEST['object_id'] );
            }
        } else {
            $user = get_userdata( $user_id );
        }

        if ( !empty( $user ) ) {
            //block for edit user or ajax load assign popup
            $child_roles_list = array();
            foreach( $user->roles as $user_role ) {
                $child_roles_list = array_merge( $child_roles_list, WO()->get_role_all_child( $user_role ) );
            }
            $child_roles_list = array_unique( $child_roles_list );

            foreach( $child_roles_list as $role ) {
                if( WO()->member_main_manage_cap( $user_id, $role ) ) {
                    $args['user']['member'][$role] = array(
                        'title' => $roles_list[$role]['title'],
                    );
                }
            }
        } else {
            //block for add user without user ID using only role
            if ( !$user_id ) {
                $current_role = $_REQUEST['object_id'];
            } else {
                $current_role = $user_id;
            }

            $child_roles_list = WO()->get_role_all_child( $current_role );
            $child_roles_list = array_unique( $child_roles_list );

            foreach( $child_roles_list as $role ) {
                $visible = false;
                if ( !$user_id ) {
                    if ( WO()->member_main_manage_cap( get_current_user_id(), $role ) ) {
                        $visible = true;
                    }
                } else {
                    if ( WO()->role_main_manage_cap( $current_role, $role ) ) {
                        $visible = true;
                    }
                }

                if( $visible ) {
                    $args['user']['member'][$role] = array(
                        'title' => $roles_list[$role]['title'],
                    );
                }
            }
        }

        if ( WO()->current_member_can( 'view_profile' ) ) {
            $args['user']['profile'] = array(
                'title' => __( 'Profile', WP_OFFICE_TEXT_DOMAIN )
            );
        }

        return $args;
    }


    /**
     * Function for getting Assign Value in AJAX
     * using $_REQUEST data
     *
     * @return string
     */
    function get_assign_value_ajax() {
        $assign_value = array();

        if ( isset( $this->_assign_tabs[$_REQUEST['object']] ) ) {
            foreach ( $this->_assign_tabs[$_REQUEST['object']] as $key=>$settings ) {
                if ( !empty( $settings['reverse'] ) ) {
                    $object = ( 'user' == $_REQUEST['object'] ) ? 'member' : $_REQUEST['object'];
                    $assigned = WO()->get_assign_data_by_assign( $key, $object, $_REQUEST['object_id'] );
                } else {
                    $assigned = WO()->get_assign_data_by_object( $_REQUEST['object'], $_REQUEST['object_id'], $key );
                }

                if ( !current_user_can( 'administrator' ) ) {
                    if ( $key == 'user' || $key == 'member' ) {
                        $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );
                        $roles_list = array_keys( $roles_list );

                        $object_ids = array();
                        foreach ( $roles_list as $role ) {
                            $object_ids = array_merge( WO()->get_available_members_by_role( $role, get_current_user_id() ) );
                        }
                    } else {
                        $object_ids = WO()->get_access_content_ids( get_current_user_id(), $key );
                    }

                    $assigned = array_values( array_intersect( $assigned, $object_ids ) );
                }

                unset( $settings['reverse'] );
                if ( $key == 'member' || $key == 'user' ) {
                    foreach ( $settings as $role=>$set ) {
                        $data = base64_encode( json_encode( array(
                            'key' => $key,
                            'role' => $role,
                            'type' => !empty( $set['type'] ) ? $set['type'] : 'checkbox'
                        ) ) );

                        $assigned_users = array();
                        if ( count( $assigned ) ) {
                            $assigned_users = get_users( array(
                                'role' => $role,
                                'include' => $assigned,
                                'fields' => 'ids'
                            ) );
                        }

                        $assign_value[$data] = $assigned_users;
                    }
                } else {
                    $data = base64_encode( json_encode( array(
                        'key' => $key,
                        'type' => !empty( $settings['type'] ) ? $settings['type'] : 'checkbox'
                    ) ) );

                    $assign_value[$data] = $assigned;
                }
            }
        }

        return base64_encode( json_encode( $assign_value ) );
    }


    /**
     * Function for rendering assign popup with tabs
     * search and pagination
     *
     * @return string Assign Popup HTML
     */
    function render_assign_form() {
        // our hook
        $args_list = ( !empty( $_REQUEST['args_list'] ) && in_array( $_REQUEST['args_list'], array( 'reverse', 'direct' ) ) ) ? $_REQUEST['args_list'] : 'all';
        $this->_assign_tabs = apply_filters( 'wpoffice_' . $_REQUEST['object'] . '_assign_form_args', $this->_assign_tabs, $_REQUEST['object_id'], $args_list );

        $html = '';
        $i = 0;

        if ( isset( $this->_assign_tabs[$_REQUEST['object']] ) ) {
            $count = count( $this->_assign_tabs[$_REQUEST['object']] );

            $html .= '<div class="wpo_assign_filters_line">';

            foreach( $this->_assign_tabs[$_REQUEST['object']] as $key=>$settings ) {
                if( !empty( $settings['reverse'] ) ) {
                    $object = ( 'user' == $_REQUEST['object'] ) ? 'member' : $_REQUEST['object'];
                    $assigned = WO()->get_assign_data_by_assign( $key, $object, $_REQUEST['object_id'] );
                } else {
                    $assigned = WO()->get_assign_data_by_object( $_REQUEST['object'], $_REQUEST['object_id'], $key );
                }

                if ( !current_user_can( 'administrator' ) ) {
                    if ( 'user' == $_REQUEST['object'] ) {
                        $assigned = array_diff( $assigned, array( get_current_user_id() ) );
                    }

                    if ( $key == 'user' || $key == 'member' ) {
                        $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );
                        $roles_list = array_keys( $roles_list );

                        $object_ids = array();
                        foreach ( $roles_list as $role ) {
                            $object_ids = array_merge( WO()->get_available_members_by_role( $role, get_current_user_id() ) );
                        }
                    } else {
                        $object_ids = WO()->get_access_content_ids( get_current_user_id(), $key );
                    }

                    $assigned = array_values( array_intersect( $assigned, $object_ids ) );
                }

                unset( $settings['reverse'] );
                if( $key == 'member' || $key == 'user' ) {
                    $count += count( $settings ) - 1;

                    foreach( $settings as $role=>$set ) {
                        $tab_args = apply_filters( 'wpoffice_' . $_REQUEST['object'] . '_assign_tabs_args', array(
                            'key' => $key,
                            'role' => $role,
                            'type' => !empty( $set['type'] ) ? $set['type'] : 'checkbox'
                        ) );
                        $data = base64_encode( json_encode( $tab_args ) );

                        $assigned_users = array();
                        if ( count( $assigned ) ) {
                            $assigned_users = get_users( array(
                                'role' => $role,
                                'include' => $assigned,
                                'fields' => 'ids'
                            ) );
                        }

                        $html .= '<a href="javascript:void(0);" class="wpo_assign_filters_line_link" data-data="' . $data . '">' . $set['title'] . ' <span class="wpo_assign_count">(' . count( $assigned_users ) . ')</span></a>';

                        $i++;
                        if( $i <= $count - 1 ) {
                            $html .= ' | ';
                        }
                    }
                } else {
                    $tab_args = apply_filters( 'wpoffice_' . $_REQUEST['object'] . '_assign_tabs_args', array(
                        'key' => $key,
                        'type' => !empty( $settings['type'] ) ? $settings['type'] : 'checkbox'
                    ) );
                    $data = base64_encode( json_encode( $tab_args ) );

                    $html .= '<a href="javascript:void(0);" class="wpo_assign_filters_line_link" data-data="' . $data . '">' . $settings['title'] . ' <span class="wpo_assign_count">(' . count( $assigned ) . ')</span></a>';
                    $i++;
                    if( $i <= $count - 1 ) {
                        $html .= ' | ';
                    }
                }
            }

            $html .= '</div>';
        }

        ob_start(); ?>
        <input type="hidden" class="wpo_assign_object" value="<?php echo $_REQUEST['object'] ?>">
        <input type="hidden" class="wpo_assign_object_id" value="<?php echo $_REQUEST['object_id'] ?>">
        <input type="hidden" class="wpo_assign_args_list" value="<?php echo $args_list ?>">
        <script class="wpo_assign_form_sample" type="text/x-jsrender">
            <div class="half">
            <ul>
            {{props ~root.items}}
                {{if key > 0 && ~indexes.toString().split(",").indexOf(key) != -1 }}
                    {{if ~indexes.toString().split(",").indexOf(key) == 2}}
                        </ul></div><div class="half"><ul>
                    {{else}}
                        </ul><ul>
                    {{/if}}
                {{/if}}
                <li><label class="wpo_assign_form_values">
                    {{if ~root.type=='radio'}}
                        <input type="radio" name="wpo_assign_value[{{>~root.key}}]" value="{{>prop.value}}" {{if prop.checked}}checked="checked"{{/if}} />
                    {{else}}
                        <input type="checkbox" name="wpo_assign_value[{{>~root.key}}][]" value="{{>prop.value}}" {{if prop.checked}}checked="checked"{{/if}} />
                    {{/if}}
                    {{>prop.title}}
                </label></li>
            {{/props}}
            </ul>
            </div>
        </script>
        <div class="wpo_assign_form">
            <div class="wpo_assign_search">
                <input type="search" placeholder="<?php _e( 'Search', WP_OFFICE_TEXT_DOMAIN ) ?>" value="" />
            </div>
            <div class="wpo_assign_bulk_actions">
                <label>
                    <input type="checkbox" name="check_all" value="1" class="wpo_assign_all" />
                    <strong><?php _e( 'Check All', WP_OFFICE_TEXT_DOMAIN ) ?></strong>
                </label>
                <label>
                    <input type="checkbox" name="check_all_page" value="1" class="wpo_assign_all_page" />
                    <strong><?php _e( 'Check All at this Page', WP_OFFICE_TEXT_DOMAIN ) ?></strong>
                </label>
            </div>
            <script class="wpo_assign_pagination_sample" type="text/x-jsrender">
                <span class="wpo_assign_pagination_num">{{>count}}&nbsp;{{if count == 1 }}<?php _e( 'item', WP_OFFICE_TEXT_DOMAIN ) ?>{{else}}<?php _e( 'items', WP_OFFICE_TEXT_DOMAIN ) ?>{{/if}}</span>
                {{if pages_count > 1 }}
                    <div class="wpo_assign_pagination">
                        {{if current_page < 3 }}
                            <?php WO()->get_button( '&laquo;', array( 'class' => 'wpo_assign_first-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&laquo;', array( 'class' => 'wpo_assign_first-page', 'data-page' => '1' ), array( 'only_text' => true ) ) ?>
                        {{/if}}

                        {{if current_page < 2 }}
                            <?php WO()->get_button( '&lsaquo;', array( 'class' => 'wpo_assign_prev-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&lsaquo;', array( 'class' => 'wpo_assign_prev-page', 'data-page' => '{{>current_page*1 - 1}}' ), array( 'only_text' => true ) ) ?>
                        {{/if}}

                        <span class="wpo_assign_pagination_pages">
                            <input class="wpo_assign_pagination_current_page" type="text" name="wpo_assign_pagination_current_page" value="{{>current_page}}" data-max_page="{{>pages_count}}" size="{{>pages_count.toString().length}}" />
                            &nbsp;<?php _e( 'of', WP_OFFICE_TEXT_DOMAIN ) ?>&nbsp;
                            <span class="wpo_assign_pagination_total_pages">{{>pages_count}}</span>
                        </span>

                        {{if current_page == pages_count }}
                            <?php WO()->get_button( '&rsaquo;', array( 'class' => 'wpo_assign_next-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&rsaquo;', array( 'class' => 'wpo_assign_next-page', 'data-page' => '{{>current_page*1 + 1}}' ), array( 'only_text' => true ) ) ?>
                        {{/if}}

                        {{if current_page >= pages_count - 1 }}
                            <?php WO()->get_button( '&raquo;', array( 'class' => 'wpo_assign_last-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&raquo;', array( 'class' => 'wpo_assign_last-page', 'data-page' => '{{>pages_count}}' ), array( 'only_text' => true ) ) ?>
                        {{/if}}
                    </div>
                {{/if}}
            </script>
            <div class="wpo_assign_pagination_wrapper"></div>
            <div class="wpo_assign_form_wrapper">
                <div class="wpo_assign_items"></div>
                <div class="wpo_assign_items_ajax_loader_wrapper">
                    <div class="wpo_assign_form_ajax_loader">
                        <?php echo WO()->get_ajax_loader( 77 ) ?>
                    </div>
                </div>
            </div>
            <div class="wpo_assign_items_ajax_loader_wrapper">
                <div class="wpo_assign_form_ajax_loader">
                    <?php echo WO()->get_ajax_loader( 77 ) ?>
                </div>
            </div>
        </div>

        <?php $html .= ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }

        return $html;
    }


    /**
     * Filter Assign Popup Title for Office Page Categories
     *
     * @param string $title
     * @return string
     */
    function office_page_category_title( $title ) {
        return __( 'Assign Office Page Category', WP_OFFICE_TEXT_DOMAIN );
    }


    /**
     * Filter Assign Popup Title for Members
     *
     * @param string $title
     * @return string
     */
    function member_title( $title ) {
        return __( 'Assign Member', WP_OFFICE_TEXT_DOMAIN );
    }


    /**
     * Filter Assign Popup Title for Office HUBs
     *
     * @param string $title
     * @return string
     */
    function office_hub_title( $title ) {
        return __( 'Assign Office HUB', WP_OFFICE_TEXT_DOMAIN );
    }


    /**
     * Filter Assign Popup Title for Office Pages
     *
     * @param string $title
     * @return string
     */
    function office_page_title( $title ) {
        return __( 'Assign Office Page', WP_OFFICE_TEXT_DOMAIN );
    }


    /**
     * Render Assign Popup Title
     *
     * @return string
     */
    function render_assign_form_title() {
        if( !empty( $_REQUEST['object'] ) ) {
            $object = ucfirst( $_REQUEST['object'] );

            $class = '';
            $button_args = array();
            if( !empty( $_REQUEST['is_ajax'] ) && $_REQUEST['is_ajax'] != 'false' ) {
                $button_args['primary'] = true;
                $label = __( 'Update', WP_OFFICE_TEXT_DOMAIN );
            } else {
                $class = 'wpo_layer_button';
                $label = __( 'Assign', WP_OFFICE_TEXT_DOMAIN );
            }

            //our hook
            return '<span>' .
                apply_filters( 'wpoffice_assign_form_title_' . $_REQUEST['object'], __( 'Assign ', WP_OFFICE_TEXT_DOMAIN ) . $object ) .
            '</span>' . WO()->get_button( $label, array( 'class' => $class . ' wpo_assign_button' ), $button_args, false );
        }

        return '';
    }


    /**
     * Popup AJAX handler after click on "Assigns" link
     */
    function load_assign_data() {
        exit( json_encode( array(
            'title'     => $this->render_assign_form_title(),
            'content'   => $this->render_assign_form(),
            'value'     => $this->get_assign_value_ajax()
        ) ) );
    }


    /**
     * Function for building assign link
     *
     * @param array $data
     * @param bool $custom_data
     * @return string
     */
    function build_assign_link( $data = array(), $custom_data = false, $validation = '' ) {
        $assigns_count = 0;

        $ajax_save = true;
        if( isset( $data['ajax'] ) && $data['ajax'] === false ) {
            $ajax_save = false;
        }

        $link_title = __( 'Assigned', WP_OFFICE_TEXT_DOMAIN );
        if( !empty( $data['link_title'] ) ) {
            $link_title = $data['link_title'];
        }

        $style = !empty( $data['style'] ) ? $data['style'] : '';

        //can be reverse or direct
        $args_list = ( !empty( $data['args_list'] ) && in_array( $data['args_list'], array( 'reverse', 'direct' ) ) ) ? $data['args_list'] : 'all';

        $this->_assign_tabs = apply_filters( 'wpoffice_' . $data['object'] . '_assign_form_args', array(), $data['object_id'], $args_list );

        if ( empty( $this->_assign_tabs ) ) {
            $html = '<a class="wpo_assign_link wpo_disabled" href="javascript:void(0);" data-object="' . $data['object'] . '" data-object_id="' . $data['object_id'] . '" ' . ( $ajax_save ? 'data-ajax="1"' : '' ) . ' ' . ( 'all' != $args_list ? 'data-args_list="' . $args_list . '"' : '' ) . ' style="' . $style . '">' .
                $link_title .
                '<span class="wpo_assign_link_count"> (' . $assigns_count . ')</span>' .
                '</a>';

            if( !$ajax_save ) {
                $html .= '<input type="hidden" disabled="disabled" name="' . $data['name'] . '" value="' . ( !empty( $data['value'] ) ? $data['value'] : '' ) . '">';
            }
        } else {
            $tooltip_html = '';
            if ( isset( $this->_assign_tabs[$data['object']] ) ) {
                foreach( $this->_assign_tabs[$data['object']] as $key=>$settings ) {
                    if ( !$custom_data ) {
                        if( !empty( $settings['reverse'] ) ) {
                            $object = ( 'user' == $data['object'] ) ? 'member' : $data['object'];
                            $assigned = WO()->get_assign_data_by_assign( $key, $object, $data['object_id'] );
                        } else {
                            $assigned = WO()->get_assign_data_by_object( $data['object'], $data['object_id'], $key );
                        }

                        if( $key == 'member' || $key == 'user' ) {
                            $excluded_members = WO()->members()->get_excluded_members();
                            if ( !current_user_can( 'administrator' ) ) {
                                $user_id = get_current_user_id();
                                $user = get_userdata( $user_id );
                                $child_roles_list = array();
                                foreach( $user->roles as $user_role ) {
                                    $child_roles_list = array_merge( $child_roles_list, WO()->get_role_all_child( $user_role ) );
                                }
                                $child_roles_list = array_unique( $child_roles_list );

                                $user_ids = array();
                                foreach( $child_roles_list as $role ) {
                                    if( WO()->member_main_manage_cap( $user_id, $role ) ) {
                                        $result = get_users( array(
                                            'role' => $role,
                                            'fields' => 'ids'
                                        ) );
                                        $user_ids = array_merge( $user_ids, $result );
                                    }
                                }

                                $assigned = array_values( array_intersect( $assigned, $user_ids ) );
                            }
                            $assigned = array_diff( $assigned, $excluded_members );

                            $title = __( 'Members', WP_OFFICE_TEXT_DOMAIN );
                        } else {
                            if ( !current_user_can( 'administrator' ) ) {
                                $object_ids = WO()->get_access_content_ids( get_current_user_id(), $key );
                                $assigned = array_values( array_intersect( $assigned, $object_ids ) );
                            }

                            $title = $settings['title'] . 's';
                        }
                        $tooltip_html .= $title . ' - ' . count( $assigned ) . '<br />';

                        $assigns_count += count( $assigned );
                    } else {
                        if( !$ajax_save && !empty( $data['value'] ) ) {
                            $decode_value = json_decode( base64_decode( $data['value'] ), true );

                            if ( !empty( $decode_value ) ) {
                                $title = $settings['title'] . 's';

                                foreach ( $decode_value as $k=>$values ) {
                                    $decode_key = json_decode( base64_decode( $k ), true );
                                    if ( isset( $decode_key['key'] ) && $decode_key['key'] == $key ) {
                                        $assigns_count += count( $values );
                                        $tooltip_html .= $title . ' - ' . count( $values ) . '<br />';
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ( !empty( $tooltip_html ) ) {
                $tooltip_html = 'data-wpo_tooltip="' . esc_attr( $tooltip_html ) . '"';
            }

            $html = '<a class="wpo_assign_link" href="javascript:void(0);" data-object="' . $data['object'] . '" data-object_id="' . $data['object_id'] . '" ' . ( $ajax_save ? 'data-ajax="1"' : '' ) . ' ' . ( 'all' != $args_list ? 'data-args_list="' . $args_list . '"' : '' ) . ' style="' . $style . '">' .
                $link_title .
                '<span class="wpo_assign_link_count" ' . $tooltip_html . '> (' . $assigns_count . ')</span>' .
                '</a>';

            if( !$ajax_save ) {
                $html .= '<input type="hidden" name="' . $data['name'] . '" value="' . ( !empty( $data['value'] ) ? $data['value'] : '' ) . '" data-wpo-valid="' . $validation . '">';
            }

        }

        return $html;
    }
    //end class
}