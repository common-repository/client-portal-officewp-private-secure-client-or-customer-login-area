<?php

namespace wpo\list_table;

use wpo\form\Office_Page_Category_Form;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Table_Office_Pages_Categories extends List_Table_Categories {

    protected $edit_category_ids = array();
    protected $delete_category_ids = array();

    function __construct( $args = array() ) {
        $args = array(
            'singular'          => __( 'Office Page Category', WP_OFFICE_TEXT_DOMAIN ),
            'plural'            => __( 'Office Page Categories', WP_OFFICE_TEXT_DOMAIN ),
            'no_items_message'  => ''
        );
        parent::__construct( $args );

        $this->_ID_column = 'id';

        $this->edit_category_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page_category', 'edit' );
        $this->delete_category_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page_category', 'delete' );

        $bulk_actions = array();
        if ( count( $this->delete_category_ids ) ) {
            $bulk_actions['delete'] = array( 'title' => __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ) );
        }
        $this->set_bulk_actions( $bulk_actions );


        $columns = array(
            'pages_category_name' => array(
                'title'     => __( 'Category Name(#ID)', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'o.title'
            ),
            'pages' => array(
                'title' => __( 'Pages', WP_OFFICE_TEXT_DOMAIN ),
                'width'     => '100px',
                'text-align'    => 'center'
            )
        );

        if ( count( $this->edit_category_ids ) ) {
            $columns['assigns'] = array(
                'title'         => __( 'Assigned', WP_OFFICE_TEXT_DOMAIN ),
                'width'         => '100px',
                'text-align'    => 'center'
            );
        }

        $this->set_columns_data( $columns );
    }


    function before_filters_line() {
        $button_args = array();
        if( !WO()->current_member_can( 'create_office_page_category' ) ) {
            $button_args['disabled'] = true;
        }
        $sub_tab = isset( $_GET['sub_tab'] ) ? $_GET['sub_tab'] : '';
        return apply_filters( 'wpoffice_categories_create_button_' . $sub_tab, WO()->get_button( __( 'Create', WP_OFFICE_TEXT_DOMAIN ), array('class'=>'wpo_create_category wpo_layer_button wpo_button_create' ), $button_args, false ) );
    }


    /**
     * AJAX Delete Page Category
     */
    public function delete_categories() {
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
                WHERE o.type = 'office_page_category' AND
                      o.id IN( '" . implode( "','", array_intersect( $ids, $this->delete_category_ids ) ) . "' )"
            );

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $deleted = 0;
            foreach( $ids as $id ) {
                WO()->delete_all_object_assigns( 'office_page_category', $id );
                $deleted += WO()->delete_wpo_object( $id );
            }

            if ( $deleted > 0 ) {
                exit( json_encode( array( 'status' => true, 'refresh' => $deleted, 'message' => __( 'Page Category(ies) was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            } else {
                exit( json_encode( array( 'status' => true, 'refresh' => true, 'message' => __( 'Cannot delete Page Category(ies)', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }
        }

        exit( json_encode( array( 'status' => false, 'refresh' => false, 'message' => __( 'Something wrong', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    public function bulk_action() {
        if( !empty( $_REQUEST['bulk_action'] ) ) {

            if( !in_array( $_REQUEST['bulk_action'], array_keys( $this->get_bulk_actions() ) ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            switch( $_REQUEST['bulk_action'] ) {
                case 'delete':
                    $this->delete_categories();
                    break;
                default:
                    /*wpo_hook_
                        hook_name: wpoffice_list_table_office_page_categories_bulk_action
                        hook_title: List Table Office Page Categories Bulk Action
                        hook_description: Hook runs for do custom bulk actions on office page categories page.
                        hook_type: action
                        hook_in: wp-office
                        hook_location class-list-table-office-page-categories.php
                        hook_param:
                        hook_since: 1.0.0
                    */
                    do_action( 'wpoffice_list_table_office_page_categories_bulk_action' );
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
                'o.title',
            ) );
        }

        $order_string = $this->get_order_string();

        $filter = '';

        $filter_line = $this->get_filters_line();

        $include = '';
        if( !current_user_can( 'administrator' ) && WO()->current_member_can( 'view_office_page_category' ) != 'on' ) {
            $assigned_categories = WO()->get_access_content_ids( get_current_user_id(), 'office_page_category' );
            $include = ' AND o.id IN("' . implode( '","', $assigned_categories ) . '")';
        }

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT o.id )
            FROM {$wpdb->prefix}wpo_objects o
            WHERE o.type = 'office_page_category'
                $search
                $include
                $filter"
        );

        $pagination = array(
            'current_page'  => $paged,
            'start'         => $per_page * ( $paged - 1 ) + 1,
            'end'           => ( $per_page * ( $paged - 1 ) + $per_page < $items_count ) ? $per_page * ( $paged - 1 ) + $per_page : $items_count,
            'count'         => $items_count,
            'pages_count'   => ceil( $items_count/$per_page )
        );

        $categories = $wpdb->get_results(
            "SELECT DISTINCT o.id, o.title, COUNT(DISTINCT p.ID) as pages
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->postmeta} pm ON o.id = pm.meta_value AND pm.meta_key = 'category_id'
            LEFT JOIN {$wpdb->posts} p ON p.id = pm.post_id AND p.post_type = 'office_page' AND p.post_status='publish'
            WHERE o.type = 'office_page_category'
                $search
                $include
                $filter
            GROUP BY o.id
            ORDER BY $order_string",
        ARRAY_A );

        if( !empty( $categories ) ) {
            foreach( $categories as $k=>$category ) {

                $categories[$k]['delete_rel'] = $category[$this->_ID_column] . '_' . md5( 'wpocategorydelete_' . $category[$this->_ID_column] );

                $categories[$k]['show_edit_link'] = false;
                $categories[$k]['assign_link'] = '';
                if ( in_array( $category[ $this->_ID_column ], $this->edit_category_ids ) ) {
                    $categories[$k]['show_edit_link'] = true;
                    $categories[$k]['assign_link'] = WO()->assign()->build_assign_link( array( 'object' => 'office_page_category', 'object_id' => $category[$this->_ID_column] ) );
                }

                $categories[$k]['show_delete_link'] = false;
                if ( in_array( $category[ $this->_ID_column ], $this->delete_category_ids ) ) {
                    $categories[$k]['show_delete_link'] = true;
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

        $filters_line = $this->get_filters_line();
        $filters_line['current'] = !empty( $_REQUEST['filters_tab'] ) ? $_REQUEST['filters_tab'] : current( array_keys( $filters_line ) );

        exit( json_encode( array(
            'status'        => true,
            'data'          => $categories,
            'pagination'    => $pagination,
            'filters_line'  => $filter_line,
            'template'      => $template
        ) ) );
    }


    function column_assigns() {
        return '{{:assign_link}}';
    }


    function column_pages_category_name() {
        $actions = $hide_actions = array();

        $actions['edit']    = '<a href="javascript:void(0);" data-id="{{>' . $this->_ID_column . '}}" {{if !show_edit_link}}data-hide="1"{{/if}}>' . __('Edit', WP_OFFICE_TEXT_DOMAIN) . '</a>';
        $actions['delete']   = '<a href="javascript:void(0);" data-id="{{>' . $this->_ID_column . '}}" rel="{{>delete_rel}}" {{if !show_delete_link}}data-hide="1"{{/if}}>' . __('Delete Permanently', WP_OFFICE_TEXT_DOMAIN) . '</a>';

        $actions = apply_filters( 'wpoffice_list_table_office_page_categories_actions', $actions );

        return sprintf('%1$s %2$s</div>',
            '<div style="width:100%;float:left;"><span id="category_name_{{>' . $this->_ID_column . '}}"><strong>{{>title}} (#{{>' . $this->_ID_column . '}})</strong></span>',
            $this->row_actions( $actions )
        );
    }


    function categories_filters_line( $filters ) {
        global $wpdb;
        if( !WO()->current_member_can( 'view_office_page_category' ) ) {
            return $filters;
        }

        $include = '';
        if( !current_user_can( 'administrator' ) && WO()->current_member_can( 'view_office_page_category' ) != 'on' ) {
            $assigned_categories = WO()->get_access_content_ids( get_current_user_id(), 'office_page_category' );
            $include = ' AND o.id IN("' . implode( '","', $assigned_categories ) . '")';
        }

        $count = $wpdb->get_var(
            "SELECT COUNT(o.id)
            FROM {$wpdb->prefix}wpo_objects o
            WHERE o.type='office_page_category'
                  $include"
        );

        $filters['office_page_categories'] = array(
            'title' => __( 'Office Page Categories', WP_OFFICE_TEXT_DOMAIN ),
            'count' => $count,
            'href'  => get_admin_url() . 'admin.php?page=wp-office-contents&tab=categories'
        );

        $sub_tab = isset( $_REQUEST['sub_tab'] ) ? $_REQUEST['sub_tab'] : '';

        //fix for capabilities if hide office page categories
        if( empty( $sub_tab ) ) {
            $filter_keys = array_keys( $filters );
            $parse_url = parse_url( $filters[$filter_keys[0]]['href'] );
            $query = explode( '&', $parse_url['query'] );
            foreach( $query as $query_attr ) {
                $query_attr = explode( '=', $query_attr );
                if ( $query_attr[0] == 'sub_tab' ) {
                    $sub_tab = $query_attr[1];
                    break;
                }
            }
        }

        if( empty( $sub_tab ) ) {
            $filters['office_page_categories']['current'] = true;
            $filters['current'] = 'office_page_categories';
        }
        return $filters;
    }


    function edit_form() {
        $form = new Office_Page_Category_Form();
        $form->display();
    }

    //end class
}