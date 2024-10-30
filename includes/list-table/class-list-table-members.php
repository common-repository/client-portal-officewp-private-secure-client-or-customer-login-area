<?php

namespace wpo\list_table;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;

class List_Table_Members extends Admin_List_Table {

    function __construct() {
        $args = array(
            'singular'          => __( 'Member', WP_OFFICE_TEXT_DOMAIN ),
            'plural'            => __( 'Members', WP_OFFICE_TEXT_DOMAIN ),
            'no_items_message'  => ''
        );

        parent::__construct( $args );

        $this->_ID_column = 'ID';

        $filter_line = array();

        $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );

        if ( !empty( $roles_list ) ) {
            if( !current_user_can( 'administrator' ) ) {
                foreach( $roles_list as $role_key => $value ) {
                    if ( WO()->current_member_can_manage( 'view_member', $role_key ) ) {
                        if ( WO()->current_member_main_manage_cap( $role_key ) ) {
                            $exclude = '';
                            $excluded_members = WO()->members()->get_excluded_members( false, $role_key );
                            if ( !empty( $excluded_members ) ) {
                                $exclude = " AND u.ID NOT IN('" . implode( "','", $excluded_members ) . "')";
                            }

                            $include = '';
                            if ( WO()->current_member_main_manage_cap( $role_key ) == 'assigned' ) {
                                //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
                                $assigned_users = WO()->get_available_members_by_role( $role_key, get_current_user_id() );
                                $include = " AND u.ID IN('" . implode( "','", $assigned_users ) . "')";
                            }

                            global $wpdb;
                            $members_count = $wpdb->get_var(
                                "SELECT COUNT( DISTINCT u.ID )
                                FROM {$wpdb->users} u
                                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                                WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND
                                      um.meta_value LIKE '%:\"{$role_key}\";%'
                                      $include
                                      $exclude"
                            );

                            $filter_line[$role_key] = array(
                                'title' => $value['title'],
                                'count' => ( !empty( $members_count ) ) ? $members_count : 0
                            );
                        }
                    }
                }
            } else {
                $count_users = count_users();

                foreach ( $roles_list as $role_key=>$value ) {
                    $excluded_members = WO()->members()->get_excluded_members( false, $role_key );

                    $filter_line[$role_key] = array(
                        'title' => $value['title'],
                        'count' => ( !empty( $count_users['avail_roles'][$role_key] ) ) ? max( 0, $count_users['avail_roles'][$role_key] - count( $excluded_members ) ) : 0,
                    );
                }
            }


            $bulk_actions = array(
                'archive'   => array( 'title' => __( 'Archive', WP_OFFICE_TEXT_DOMAIN ) ),
                'delete'    => array( 'title' => __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ) )
            );

            if( !current_user_can( 'administrator' ) ) {
                if( !empty( $_REQUEST['filters_tab'] ) ) {
                    $current_role = $_REQUEST['filters_tab'];
                } else {
                    $current_role = array_keys( $roles_list );
                    $current_role = $current_role[0];
                }

                $bulk_actions = array();

                if( WO()->current_member_can_manage( 'archive_member', $current_role ) ) {
                    $bulk_actions['archive'] = array( 'title' => __( 'Archive', WP_OFFICE_TEXT_DOMAIN ) );
                }
                if( WO()->current_member_can_manage( 'delete_member', $current_role ) ) {
                    $bulk_actions['delete'] = array( 'title' => __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ) );
                }
            }

            $this->set_bulk_actions( $bulk_actions );
        }

        //our_hook
        $filter_line = apply_filters( 'wpoffice_list_table_members_filters_line', $filter_line );
        $this->set_filters_line( $filter_line );

        $columns = array(
            'username' => array(
                'title'     => __( 'Username (#ID)', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'u.user_login'
            ),
            'user_email' => array(
                'title'     => __( 'E-mail', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'u.user_email',
                'width'     => '33%'
            ),
            'user_registered' => array(
                'title' => __( 'Creation Date', WP_OFFICE_TEXT_DOMAIN ),
                'width'     => '16%'
            ),
        );

        $assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member', 'edit' );
        if ( count( $assigned_users ) ) {
            $columns['assign'] = array(
                'title'         => __( 'Assigned', WP_OFFICE_TEXT_DOMAIN ),
                'width'         => '100px',
                'text-align'    => 'center'
            );
        }

        $this->set_columns_data( $columns );
    }


    /**
     * AJAX Delete Member
     */
    public function delete_members() {
        $deleted = false;
        if( !empty( $_REQUEST['id'] ) ) {
            if( !is_numeric( $_REQUEST['id'] ) ) {
                $_REQUEST['id'] = json_decode( $_REQUEST['id'] );
            }

            $ids = $_REQUEST['id'];
            if( is_numeric( $ids ) ) {
                $ids = array( $ids );
            }
            $temp_ids = $ids;
            foreach( $temp_ids as $id ) {
                $userdata = get_userdata( $id );
                foreach( $userdata->roles as $user_role ) {
                    if ( !WO()->current_member_can_manage( 'delete_member', $user_role ) ) {
                        exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                    }

                    if( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $user_role ) == 'assigned' ) {
                        //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
                        $assigned_users = WO()->get_available_members_by_role( $user_role, get_current_user_id() );
                        $ids = array_intersect( $ids, $assigned_users );
                    }
                }
            }

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $deleted = WO()->members()->delete_members( $ids );
        }

        exit( json_encode( array( 'status' => true, 'refresh' => $deleted, 'message' => __( 'Member(s) was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    /**
     * AJAX Archive Member
     */
    public function archive_members() {
        $archived = false;
        if( !empty( $_REQUEST['id'] ) ) {
            if( !is_numeric( $_REQUEST['id'] ) ) {
                $_REQUEST['id'] = json_decode( $_REQUEST['id'] );
            }

            $ids = $_REQUEST['id'];
            if( is_numeric( $ids ) ) {
                $ids = array( $ids );
            }

            $temp_ids = $ids;
            foreach( $temp_ids as $id ) {
                $userdata = get_userdata( $id );
                foreach( $userdata->roles as $user_role ) {
                    if ( !WO()->current_member_can_manage( 'archive_member', $user_role ) ) {
                        exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                    }

                    if( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $user_role ) == 'assigned' ) {
                        //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
                        $assigned_users = WO()->get_available_members_by_role( $user_role, get_current_user_id() );
                        $ids = array_intersect( $ids, $assigned_users );
                    }
                }
            }

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $archived = WO()->members()->archive_members( $ids );
        }

        exit( json_encode( array( 'status' => true, 'refresh' => $archived, 'message' => __( 'Member(s) was Archived!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    public function bulk_action() {
        if( !empty( $_REQUEST['bulk_action'] ) ) {

            if( !in_array( $_REQUEST['bulk_action'], array_keys( $this->get_bulk_actions() ) ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            switch( $_REQUEST['bulk_action'] ) {
                case 'archive':
                    $this->archive_members();
                    break;
                case 'delete':
                    $this->delete_members();
                    break;
                default:
                    /*wpo_hook_
                        hook_name: wpoffice_list_table_members_active_bulk_action
                        hook_title: List Table Members Active Bulk Action
                        hook_description: Hook runs for do custom bulk actions on members active page.
                        hook_type: action
                        hook_in: wp-office
                        hook_location class-list-table-members.php
                        hook_param:
                        hook_since: 1.0.0
                    */
                    do_action( 'wpoffice_list_table_members_active_bulk_action' );
                    break;
            }
        }

        exit( json_encode( array( 'status' => false, 'message' => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    /**
     * AJAX Build ListTable Data
     */
    public function list_table_data() {
        global $wpdb;

        $per_page   = $this->get_items_per_page( 'users_per_page' );
        if( (int)$per_page > 100 ) {
            $per_page = 20;
        }
        $paged      = $this->get_pagenum();

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'u.user_login',
                'u.user_email'
            ) );
        }

        $order_string = $this->get_order_string();

        $filter = '';

        $users = array();
        $filter_line = array();
        $template = array();
        $pagination = array();

        $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );
        if ( !empty( $roles_list ) ) {
            if( !current_user_can( 'administrator' ) ) {
                foreach( $roles_list as $role_key => $value ) {
                    if ( !WO()->current_member_can_manage( 'view_member', $role_key ) ) {
                        unset( $roles_list[$role_key] );
                    }
                }
            }
        }

        $can_create = false;

        if ( !empty( $roles_list ) ) {

            if( !empty( $_REQUEST['filters_tab'] ) ) {
                $current_role = $_REQUEST['filters_tab'];
            } else {
                $current_role = array_keys( $roles_list );
                $current_role = $current_role[0];
            }

            if ( !WO()->current_member_main_manage_cap( $current_role ) ) {
                exit( json_encode( array(
                    'status'        => true,
                    'data'          => $users,
                    'pagination'    => $pagination,
                    'filters_line'  => $filter_line,
                    'template'      => $template,
                    'can_create'    => $can_create
                ) ) );
            }

            $excluded_members = WO()->members()->get_excluded_members( false, $current_role );
            $exclude = '';
            if( count( $excluded_members ) ) {
                $exclude .= ' AND u.' . $this->_ID_column . ' NOT IN( "' . implode( '","', $excluded_members ) . '" )';
            }

            $include = '';
            if( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $current_role ) == 'assigned' ) {
                //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
                $assigned_users = WO()->get_available_members_by_role( $current_role, get_current_user_id() );
                $include = " AND u.ID IN('" . implode( "','", $assigned_users ) . "')";
            }

            $filter_line = $this->get_filters_line();
            $filter_line['current'] = $current_role;

            $items_count = $wpdb->get_var(
                "SELECT COUNT( DISTINCT u.ID )
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                    AND um.meta_value LIKE '%:\"{$current_role}\";%'
                    $include
                    $exclude
                    $search
                    $filter"
            );

            $pagination = array(
                'current_page'  => $paged,
                'start'         => $per_page * ( $paged - 1 ) + 1,
                'end'           => ( $per_page * ( $paged - 1 ) + $per_page < $items_count ) ? $per_page * ( $paged - 1 ) + $per_page : $items_count,
                'count'         => $items_count,
                'pages_count'   => ceil( $items_count/$per_page )
            );

            $users = $wpdb->get_results(
                "SELECT u.*
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                    AND um.meta_value LIKE '%:\"{$current_role}\";%'
                    $include
                    $exclude
                    $search
                    $filter
                ORDER BY $order_string
                LIMIT ". ( ( $paged - 1 )*$per_page ). "," . $per_page,
                ARRAY_A );

            if( !empty( $users ) ) {
                foreach( $users as $k=>$user ) {
                    $users[$k]['user_avatar'] = WO()->members()->user_avatar( $user[$this->_ID_column] );
                    $users[$k]['view_rel'] = $user[$this->_ID_column] . '_' . md5( 'wpomemberview_' . $user[$this->_ID_column] );
                    $users[$k]['archive_rel'] = $user[$this->_ID_column] . '_' . md5( 'wpomemberarchive_' . $user[$this->_ID_column] );
                    $users[$k]['delete_rel'] = $user[$this->_ID_column] . '_' . md5( 'wpomemberdelete_' . $user[$this->_ID_column] );


                    $users[$k]['show_edit_link'] = false;
                    $users[$k]['assign_link'] = '';
                    if( WO()->current_member_can_manage( 'edit_member', $current_role ) ) {
                        $users[$k]['show_edit_link'] = true;
                        $users[$k]['assign_link'] = WO()->assign()->build_assign_link( array( 'object' => 'user', 'object_id' => $user[$this->_ID_column] ) );
                    }

                    $users[$k]['show_delete_link'] = false;
                    if( WO()->current_member_can_manage( 'delete_member', $current_role ) ) {
                        $users[$k]['show_delete_link'] = true;
                    }

                    $users[$k]['show_archive_link'] = false;
                    if( WO()->current_member_can_manage( 'archive_member', $current_role ) ) {
                        $users[$k]['show_archive_link'] = true;
                    }
                }
            }

            $this->set_pagination_args( array( 'total_items' => $items_count, 'per_page' => $per_page ) );

            if( !empty( $_REQUEST['reset_template'] ) ) {
                $this->prepare_items();
                $template['content_sample'] = $this->sample();
                $template['headers_sample'] = $this->headers_sample();
            }


            if( current_user_can( 'administrator' ) || WO()->current_member_can_manage( 'add_member', $current_role ) ) {
                $can_create = true;
            }
        }

        exit( json_encode( array(
            'status'        => true,
            'data'          => $users,
            'pagination'    => $pagination,
            'filters_line'  => $filter_line,
            'template'      => $template,
            'can_create'    => $can_create
        ) ) );
    }


    function column_assign() {
        return '{{:assign_link}}';
    }


    function column_username() {
        $actions = $hide_actions = array();

        $avatar = '<div class="wpo_user_avatar">{{:user_avatar}}</div>';

        $actions['edit']    = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" {{if !show_edit_link}}data-hide="1"{{/if}}>' . __( 'Edit', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['view']    = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" rel="{{>view_rel}}">' . __( 'View', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['archive'] = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" {{if !show_archive_link}}data-hide="1"{{/if}} rel="{{>archive_rel}}">' . __( 'Archive', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['delete']  = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" {{if !show_delete_link}}data-hide="1"{{/if}} rel="{{>delete_rel}}">' . __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ). '</a>';

        //our_hook
        $actions = apply_filters( 'wpoffice_list_table_members_actions', $actions );

        return sprintf('%1$s %2$s %3$s</div>',
            $avatar,
            '<div style="margin-left:7px; width:calc( 100% - 55px );float:left;"><span id="member_username_{{>' . $this->_ID_column . '}}"><strong>{{>user_login}} (#{{>' . $this->_ID_column . '}})</strong></span>',
            $this->row_actions( $actions )
        );
    }


    function before_filters_line() {
        return WO()->get_button( __( 'Create', WP_OFFICE_TEXT_DOMAIN ), array('class'=>'wpo_create_member wpo_layer_button wpo_button_create'), array( 'disabled' => true ) );
    }
    //end class
}