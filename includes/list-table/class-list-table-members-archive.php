<?php

namespace wpo\list_table;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;

class List_Table_Members_Archive extends Admin_List_Table {

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
            global $wpdb;
            $archived_members = WO()->members()->get_excluded_members( 'archived' );
            $archived = ' AND 1=0';
            if( count( $archived_members ) ) {
                $archived = ' AND u.' . $this->_ID_column . ' IN( "' . implode( '","', $archived_members ) . '" )';
            }

            foreach( $roles_list as $role_key => $value ) {
                if ( WO()->current_member_can_manage( 'view_member', $role_key ) && WO()->current_member_can_manage( 'archive_member', $role_key ) ) {
                    $include = '';
                    if( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $role_key ) == 'assigned' ) {
                        //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
                        $assigned_users = WO()->get_available_members_by_role( $role_key, get_current_user_id(), true );
                        $include = " AND u.ID IN('" . implode( "','", $assigned_users ) . "')";
                    }

                    $members_count = $wpdb->get_var(
                        "SELECT COUNT( DISTINCT u.ID )
                        FROM {$wpdb->users} u
                        LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                        WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND
                              um.meta_value LIKE '%:\"{$role_key}\";%'
                              $archived
                              $include"
                    );

                    $filter_line[$role_key] = array(
                        'title' => $value['title'],
                        'count' => ( !empty( $members_count ) ) ? $members_count: 0
                    );
                }
            }


            $bulk_actions = array(
                'restore'   => array( 'title' => __( 'Restore', WP_OFFICE_TEXT_DOMAIN ) ),
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
                    $bulk_actions['restore'] = array( 'title' => __( 'Restore', WP_OFFICE_TEXT_DOMAIN ) );
                }
                if( WO()->current_member_can_manage( 'delete_member', $current_role ) ) {
                    $bulk_actions['delete'] = array( 'title' => __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ) );
                }
            }

            $this->set_bulk_actions( $bulk_actions );
        }

        //our_hook
        $filter_line = apply_filters( 'wpoffice_list_table_members_archive_filters_line', $filter_line );
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
                        $assigned_users = WO()->get_available_members_by_role( $user_role, get_current_user_id(), true );
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
     * AJAX Restore Member
     */
    public function restore_members() {
        $restored = false;
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
                        $assigned_users = WO()->get_available_members_by_role( $user_role, get_current_user_id(), true );
                        $ids = array_intersect( $ids, $assigned_users );
                    }
                }
            }

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $restored = WO()->members()->restore_members( $ids );
        }

        exit( json_encode( array( 'status' => true, 'refresh' => $restored, 'message' => __( 'Member(s) was Restored!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    public function bulk_action() {
        if( !empty( $_REQUEST['bulk_action'] ) ) {

            if( !in_array( $_REQUEST['bulk_action'], array_keys( $this->get_bulk_actions() ) ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            switch( $_REQUEST['bulk_action'] ) {
                case 'restore':
                    $this->restore_members();
                    break;
                case 'delete':
                    $this->delete_members();
                    break;
                default:
                    /*wpo_hook_
                        hook_name: wpoffice_list_table_members_archive_bulk_action
                        hook_title: List Table Members Archive Bulk Action
                        hook_description: Hook runs for do custom bulk actions on members archive page.
                        hook_type: action
                        hook_in: wp-office
                        hook_location class-list-table-members-archive.php
                        hook_param:
                        hook_since: 1.0.0
                    */
                    do_action( 'wpoffice_list_table_members_archive_bulk_action' );
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

            if( !empty( $_REQUEST['filters_tab'] ) ) {
                $current_role = $_REQUEST['filters_tab'];
            } else {
                $current_role = array_keys( $roles_list );
                $current_role = $current_role[0];
            }

            if ( !( WO()->current_member_can_manage( 'view_member', $current_role ) && WO()->current_member_can_manage( 'archive_member', $current_role ) ) ) {
                exit( json_encode( array(
                    'status'        => true,
                    'data'          => $users,
                    'pagination'    => $pagination,
                    'filters_line'  => $filter_line,
                    'template'      => $template
                ) ) );
            }

            $archived_members = WO()->members()->get_excluded_members( 'archived' );
            $archived = ' AND 1=0';
            if( count( $archived_members ) ) {
                $archived = ' AND u.' . $this->_ID_column . ' IN( "' . implode( '","', $archived_members ) . '" )';
            }

            $include = '';
            if( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $current_role ) == 'assigned' ) {
                //$assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
                $assigned_users = WO()->get_available_members_by_role( $current_role, get_current_user_id(), true );
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
                    $archived
                    $include
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
                    $archived
                    $include
                    $search
                    $filter
                ORDER BY $order_string
                LIMIT ". ( ( $paged - 1 )*$per_page ). "," . $per_page,
                ARRAY_A );

            if( !empty( $users ) ) {
                foreach( $users as $k=>$user ) {
                    $users[$k]['user_avatar'] = WO()->members()->user_avatar( $user[$this->_ID_column] );
                    $users[$k]['view_rel'] = $user[$this->_ID_column] . '_' . md5( 'wpomemberview_' . $user[$this->_ID_column] );
                    $users[$k]['restore_rel'] = $user[$this->_ID_column] . '_' . md5( 'wpomemberrestore_' . $user[$this->_ID_column] );
                    $users[$k]['delete_rel'] = $user[$this->_ID_column] . '_' . md5( 'wpomemberdelete_' . $user[$this->_ID_column] );


                    $users[$k]['show_edit_link'] = false;
                    if( WO()->current_member_can_manage( 'edit_member', $current_role ) ) {
                        $users[$k]['show_edit_link'] = true;
                    }

                    $users[$k]['show_delete_link'] = false;
                    if( WO()->current_member_can_manage( 'delete_member', $current_role ) ) {
                        $users[$k]['show_delete_link'] = true;
                    }

                    $users[$k]['show_restore_link'] = false;
                    if( WO()->current_member_can_manage( 'archive_member', $current_role ) ) {
                        $users[$k]['show_restore_link'] = true;
                    }
                }
            }

            $this->set_pagination_args( array( 'total_items' => $items_count, 'per_page' => $per_page ) );

            $template = array();
            if( !empty( $_REQUEST['reset_template'] ) ) {
                $this->prepare_items();
                $template['content_sample'] = $this->sample();
                $template['headers_sample'] = $this->headers_sample();
            }
        }

        exit( json_encode( array(
            'status'        => true,
            'data'          => $users,
            'pagination'    => $pagination,
            'filters_line'  => $filter_line,
            'template'      => $template
        ) ) );
    }


    function column_username() {
        $actions = $hide_actions = array();

        $avatar = '<div class="wpo_user_avatar">{{:user_avatar}}</div>';

        $actions['edit']    = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" {{if !show_edit_link}}data-hide="1"{{/if}}>' . __( 'Edit', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['view']    = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" rel="{{>view_rel}}">' . __( 'View', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['restore'] = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" {{if !show_restore_link}}data-hide="1"{{/if}} rel="{{>restore_rel}}">' . __( 'Restore', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['delete']  = '<a href="javascript:void(0);" data-member_id="{{>' . $this->_ID_column . '}}" {{if !show_delete_link}}data-hide="1"{{/if}} rel="{{>delete_rel}}">' . __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ). '</a>';

        //our_hook
        $actions = apply_filters( 'wpoffice_list_table_members_actions', $actions );

        return sprintf('%1$s %2$s %3$s</div>',
            $avatar,
            '<div style="margin-left:7px; width:calc( 100% - 55px );float:left;"><span id="member_username_{{>' . $this->_ID_column . '}}"><strong>{{>user_login}} (#{{>' . $this->_ID_column . '}})</strong></span>',
            $this->row_actions( $actions )
        );
    }
    //end class
}