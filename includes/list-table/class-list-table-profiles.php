<?php

namespace wpo\list_table;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;

class List_Table_Profiles extends Admin_List_Table {

    protected $edit_profile_ids = array();
    protected $delete_profile_ids = array();

    function __construct(){
        $args = array(
            'singular'          => __( 'Profile', WP_OFFICE_TEXT_DOMAIN ),
            'plural'            => __( 'Profiles', WP_OFFICE_TEXT_DOMAIN ),
            'no_items_message'  => ''
        );

        parent::__construct( $args );

        $this->_ID_column = 'id';

        $this->edit_profile_ids = WO()->get_access_content_ids( get_current_user_id(), 'profile', 'edit' );
        $this->delete_profile_ids = WO()->get_access_content_ids( get_current_user_id(), 'profile', 'delete' );

        $bulk_actions = array();
        if ( count( $this->delete_profile_ids ) ) {
            $bulk_actions['delete'] = array( 'title' => __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ) );
        }

        $this->set_bulk_actions( $bulk_actions );

        $columns = array(
            'title' => array(
                'title'     => __( 'Title(#ID)', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'o.title'
            )
        );

        if ( count( $this->edit_profile_ids ) ) {
            $columns['assign'] = array(
                'title'         => __( 'Assigned', WP_OFFICE_TEXT_DOMAIN ),
                'width'         => '100px',
                'text-align'    => 'center'
            );
        }

        $this->set_columns_data( $columns );
    }


    public function delete_profiles() {
        $deleted = false;
        if( !empty( $_REQUEST['id'] ) ) {
            if( !is_numeric( $_REQUEST['id'] ) ) {
                $ids = json_decode( $_REQUEST['id'] );
            } else {
                $ids = array( $_REQUEST['id'] );
            }

            global $wpdb;
            $ids = $wpdb->get_col(
                "SELECT o.id
                FROM {$wpdb->prefix}wpo_objects o
                WHERE o.id IN( '" . implode( "','", array_intersect( $ids, $this->delete_profile_ids ) ) . "' )"
            );

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $deleted = WO()->profiles()->delete( $ids );
            if ( $deleted > 0 ) {
                exit( json_encode( array( 'status' => true, 'refresh' => $deleted, 'message' => __( 'Profile(s) was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            } else {
                exit( json_encode( array( 'status' => true, 'refresh' => true, 'message' => __( 'Cannot delete published/drafted Profile(s)', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }
        }

        exit( json_encode( array( 'status' => true, 'refresh' => $deleted, 'message' => __( 'Profile(s) was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    public function bulk_action() {
        if( !empty( $_REQUEST['bulk_action'] ) ) {

            if( !in_array( $_REQUEST['bulk_action'], array_keys( $this->get_bulk_actions() ) ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            switch( $_REQUEST['bulk_action'] ) {
                case 'delete':
                    $this->delete_profiles();
                    break;
                default:
                    /*wpo_hook_
                        hook_name: wpoffice_list_table_profiles_bulk_action
                        hook_title: List Table Profiles Bulk Action
                        hook_description: Hook runs for do custom bulk actions on profiles page.
                        hook_type: action
                        hook_in: wp-office
                        hook_location class-list-table-profiles.php
                        hook_param:
                        hook_since: 1.0.0
                    */
                    do_action( 'wpoffice_list_table_profiles_bulk_action' );
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

        $per_page   = $this->get_items_per_page( 'wpo_files_per_page' );
        $paged      = $this->get_pagenum();

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'o.title',
            ) );
        }

        $order_string = $this->get_order_string();

        $filter = '';

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

        $profiles = $wpdb->get_results(
            "SELECT o.*
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id
            WHERE o.type = 'profile'
                $include
                $search
                $filter
            ORDER BY $order_string
            LIMIT ". ( ( $paged - 1 )*$per_page ). "," . $per_page,
            ARRAY_A );

        if( !empty( $profiles ) ) {
            foreach( $profiles as $k=>$profile ) {
                $profiles[$k]['edit_rel'] = $profile[ $this->_ID_column ] . '_' . md5( 'wpoprofileedit_' . $profile[ $this->_ID_column ] );
                $profiles[$k]['delete_rel'] = $profile[ $this->_ID_column ] . '_' . md5( 'wpoprofiledelete_' . $profile[ $this->_ID_column ] );

                $profiles[$k]['show_edit_link'] = false;
                $profiles[$k]['assign_link'] = '';
                if ( in_array( $profile[ $this->_ID_column ], $this->edit_profile_ids ) ) {
                    $profiles[$k]['show_edit_link'] = true;
                    $profiles[$k]['assign_link'] = WO()->assign()->build_assign_link( array( 'object' => 'profile', 'object_id' => $profile[$this->_ID_column] ) );
                }

                $profiles[$k]['show_delete_link'] = false;
                if ( in_array( $profile[ $this->_ID_column ], $this->delete_profile_ids ) ) {
                    $profiles[$k]['show_delete_link'] = true;
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

        exit( json_encode( array(
            'status'        => true,
            'data'          => $profiles,
            'pagination'    => $pagination,
            'filters_line'  => array(),
            'template'      => $template
        ) ) );
    }


    function column_assign() {
        return '{{:assign_link}}';
    }

    function column_title() {
        $actions = $hide_actions = array();

        $actions['edit']    = '<a href="javascript:void(0);" data-profile_id="{{>' . $this->_ID_column . '}}" {{if !show_edit_link}}data-hide="1"{{/if}}>' . __( 'Edit', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['delete']  = '<a href="javascript:void(0);" data-profile_id="{{>' . $this->_ID_column . '}}" {{if !show_delete_link}}data-hide="1"{{/if}} rel="{{>delete_rel}}">' . __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        //our_hook
        $actions = apply_filters( 'wpoffice_list_table_profile_actions', $actions );

        return sprintf('%1$s %2$s</div>',
            '<div style="width:100%;float:left;"><span id="profile_title_{{>' . $this->_ID_column . '}}"><strong>{{>title}} (#{{>' . $this->_ID_column . '}})</strong></span>',
            $this->row_actions( $actions )
        );
    }


    function before_filters_line() {
        if ( WO()->current_member_can( 'create_profile' ) ) {
            return WO()->get_button( __( 'Create', WP_OFFICE_TEXT_DOMAIN ), array('class'=>'wpo_create_profile wpo_layer_button wpo_button_create') );
        }
        return '';
    }

    //end class
}