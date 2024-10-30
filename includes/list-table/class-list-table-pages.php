<?php

namespace wpo\list_table;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;

class List_Table_Pages extends Admin_List_Table {

    protected $edit_page_ids = array();
    protected $delete_page_ids = array();

    function __construct() {
        $args = array(
            'singular'          => __( 'Page', WP_OFFICE_TEXT_DOMAIN ),
            'plural'            => __( 'Pages', WP_OFFICE_TEXT_DOMAIN ),
            'no_items_message'  => ''
        );

        parent::__construct( $args );

        $this->_ID_column = 'ID';

        $this->edit_page_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page', 'edit' );
        $this->delete_page_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page', 'delete' );

        $bulk_actions = array();
        if ( count( $this->delete_page_ids ) ) {
            $bulk_actions = array(
                'restore'   => array( 'title' => __( 'Restore', WP_OFFICE_TEXT_DOMAIN ) ),
                'trash'     => array( 'title' => __( 'Move To Trash', WP_OFFICE_TEXT_DOMAIN ) ),
                'delete'    => array( 'title' => __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ) )
            );
        }

        $this->set_bulk_actions( $bulk_actions );

        $columns = array(
            'title' => array(
                'title'     => __( 'Title(#ID)', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'p.post_title'
            ),
            'author' => array(
                'title' => __( 'Author', WP_OFFICE_TEXT_DOMAIN ),
                'width'     => '15%',
                'text-align'    => 'center'
            ),
            'post_status' => array(
                'title' => __( 'Status', WP_OFFICE_TEXT_DOMAIN ),
                'width'     => '10%',
                'text-align'    => 'center'
            ),
            'post_date' => array(
                'title' => __( 'Date', WP_OFFICE_TEXT_DOMAIN ),
                'sortable' => 'p.post_date',
                'width'     => '16%'
            ),
        );

        if ( count( $this->edit_page_ids ) ) {
            $columns['assign'] = array(
                'title'         => __( 'Assigned', WP_OFFICE_TEXT_DOMAIN ),
                'width'         => '100px',
                'text-align'    => 'center'
            );
        }

        $this->set_columns_data( $columns );

        $filter_line = array();
        $private_post_types = WO()->get_private_page_types();
        if ( !empty( $private_post_types ) ) {
            $all_post_types = get_post_types( array(), 'objects' );
            if( WO()->current_member_can( 'view_office_page' ) == 'on' ) {
                foreach( $private_post_types as $post_type ) {
                    if( !empty( $all_post_types[$post_type] ) ) {
                        $count = (array)wp_count_posts( $post_type );
                        unset( $count['auto-draft'] );

                        $filter_line[$post_type] = array(
                            'title' => $all_post_types[$post_type]->label,
                            'count' => array_sum( array_values( $count ) )
                        );
                    }
                }
            } else {
                $assigned_pages = WO()->get_access_content_ids( get_current_user_id(), 'office_page' );

                foreach( $private_post_types as $post_type ) {
                    if( !empty( $all_post_types[$post_type] ) ) {
                        if( !empty( $assigned_pages ) ) {
                            $posts = get_posts(array(
                                'post_type' => $post_type,
                                'include' => $assigned_pages,
                                'posts_per_page' => -1,
                                'post_status' => 'publish,trash,draft',
                                'fields' => 'ids'
                            ));

                            $count = count( $posts );
                        } else {
                            $count = 0;
                        }

                        $filter_line[$post_type] = array(
                            'title' => $all_post_types[$post_type]->label,
                            'count' => $count
                        );
                    }
                }
            }
        }


        //our_hook
        $filter_line = apply_filters( 'wpoffice_list_table_members_filters_line', $filter_line );
        $this->set_filters_line( $filter_line );

        //our_hook
        $this->set_filters_block( apply_filters( 'wpoffice_list_table_members_filters_block', array(
            'author'    => __( 'Author', WP_OFFICE_TEXT_DOMAIN ),
            'status'    => __( 'Status', WP_OFFICE_TEXT_DOMAIN )
        ) ) );
    }


    public function parse_filter( $active_filters, $default_format ) {
        global $wpdb;

        $sql = '';
        foreach( $active_filters as $filter ) {
            if( !empty( $default_format[ $filter['filter_by']['value'] ] ) ) {
                $sql .= $wpdb->prepare( $default_format[ $filter['filter_by']['value'] ], $filter['filter_value'] );
            }
        }

        return $sql;
    }

    /**
     * AJAX Delete Permanently
     */
    public function delete_page() {
        if( !empty( $_REQUEST['id'] ) ) {
            if( !is_numeric( $_REQUEST['id'] ) ) {
                $ids = json_decode( $_REQUEST['id'] );
            } else {
                $ids = array( $_REQUEST['id'] );
            }

            global $wpdb;
            $ids = $wpdb->get_col(
                "SELECT p.ID
                FROM {$wpdb->posts} p
                WHERE p.post_status = 'trash' AND
                      p.ID IN( '" . implode( "','", array_intersect( $ids, $this->delete_page_ids ) ) . "' )"
            );

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $deleted = WO()->pages()->delete_page( $ids );
            if ( $deleted > 0 ) {
                exit( json_encode( array( 'status' => true, 'refresh' => $deleted, 'message' => __( 'Page(s) was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            } else {
                exit( json_encode( array( 'status' => true, 'refresh' => true, 'message' => __( 'Cannot delete published/drafted Page(s)', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }
        }

        exit( json_encode( array( 'status' => false, 'refresh' => false, 'message' => __( 'Something wrong', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }

    /**
     * AJAX Restore from Trash
     */
    public function restore_page() {
        if( !empty( $_REQUEST['id'] ) ) {
            if( !is_numeric( $_REQUEST['id'] ) ) {
                $ids = json_decode( $_REQUEST['id'] );
            } else {
                $ids = array( $_REQUEST['id'] );
            }

            global $wpdb;
            $ids = $wpdb->get_col(
                "SELECT p.ID
                FROM {$wpdb->posts} p
                WHERE p.post_status = 'trash' AND
                      p.ID IN( '" . implode( "','", array_intersect( $ids, $this->delete_page_ids ) ) . "' )"
            );

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $restored = WO()->pages()->restore_page( $ids );
            if ( $restored > 0 ) {
                exit( json_encode( array( 'status' => true, 'refresh' => $restored, 'message' => __( 'Page(s) was restored!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            } else {
                exit( json_encode( array( 'status' => true, 'refresh' => true, 'message' => __( 'Cannot restore published/drafted Page(s)', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }
        }

        exit( json_encode( array( 'status' => false, 'refresh' => false, 'message' => __( 'Something wrong', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    /**
     * AJAX Move to Trash
     */
    public function trash_page() {
        if( !empty( $_REQUEST['id'] ) ) {
            if( !is_numeric( $_REQUEST['id'] ) ) {
                $ids = json_decode( $_REQUEST['id'] );
            } else {
                $ids = array( $_REQUEST['id'] );
            }

            global $wpdb;
            $ids = $wpdb->get_col(
                "SELECT p.ID
                FROM {$wpdb->posts} p
                WHERE p.post_status != 'trash' AND
                      p.ID IN( '" . implode( "','", array_intersect( $ids, $this->delete_page_ids ) ) . "' )"
            );

            if( 0 == count( $ids ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            $trashed = WO()->pages()->trash_page( $ids );
            if ( $trashed > 0 ) {
                exit( json_encode( array( 'status' => true, 'refresh' => $trashed, 'message' => __( 'Page(s) was moved to trash!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            } else {
                exit( json_encode( array( 'status' => true, 'refresh' => true, 'message' => __( 'Cannot move to trash already trashed Page(s)', WP_OFFICE_TEXT_DOMAIN ) ) ) );
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
                case 'trash':
                    $this->trash_page();
                    break;
                case 'restore':
                    $this->restore_page();
                    break;
                case 'delete':
                    $this->delete_page();
                    break;
                default:
                    /*wpo_hook_
                        hook_name: wpoffice_list_table_office_pages_bulk_action
                        hook_title: List Table Office Pages Bulk Action
                        hook_description: Hook runs for do custom bulk actions on office pages page.
                        hook_type: action
                        hook_in: wp-office
                        hook_location class-list-table-pages.php
                        hook_param:
                        hook_since: 1.0.0
                    */
                    do_action( 'wpoffice_list_table_office_pages_bulk_action' );
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
                'p.post_title'
            ) );
        }

        $order_string = $this->get_order_string();

        $pages = array();
        $filter_line = array();
        $available_filters = array();

        $active_filters = $this->parse_active_filters();

        $filter = $this->parse_filter( $active_filters,
            array(
                'author'  => " AND u.ID=%d",
                'status'  => " AND p.post_status=%s"
            )
        );

        foreach( $active_filters as $k=>$fil ) {
            if( $fil['filter_by']['value'] == 'author' ) {
                $user = get_userdata( $fil['filter_value'] );
                $active_filters[$k]['filter_value'] = $user->user_login;
            } elseif( $fil['filter_by']['value'] == 'status' ) {
                $active_filters[$k]['filter_value'] = ucfirst( $active_filters[$k]['filter_value'] );
            }
        }

        $private_post_types = WO()->get_private_page_types();
        if ( !empty( $private_post_types ) ) {

            if( !empty( $_REQUEST['filters_tab'] ) ) {
                $current_post_type = $_REQUEST['filters_tab'];
            } else {
                $current_post_type = $private_post_types[0];
            }

            $filter_line = $this->get_filters_line();
            $filter_line['current'] = $current_post_type;

            $include = '';
            if( !current_user_can( 'administrator' ) && WO()->current_member_can( 'view_office_page' ) != 'on' ) {
                $assigned_pages = WO()->get_access_content_ids( get_current_user_id(), 'office_page' );
                $include = ' AND p.ID IN("' . implode( '","', $assigned_pages ) . '")';
            }

            $items_count = $wpdb->get_var(
                "SELECT COUNT( DISTINCT p.ID )
                FROM {$wpdb->posts} p,
                     {$wpdb->users} u
                WHERE p.post_type='$current_post_type' AND
                    p.post_status != 'auto-draft' AND
                    u.ID = p.post_author
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

            $pages = $wpdb->get_results(
                "SELECT p.*, u.user_login AS author
                FROM {$wpdb->posts} p,
                     {$wpdb->users} u
                WHERE p.post_type='$current_post_type' AND
                    p.post_status != 'auto-draft' AND
                    u.ID = p.post_author
                    $include
                    $search
                    $filter
                ORDER BY $order_string
                LIMIT ". ( ( $paged - 1 )*$per_page ). "," . $per_page,
            ARRAY_A );

            $authors = $wpdb->get_results(
                "SELECT DISTINCT( u.ID ) AS id,
                    u.user_login AS title
                FROM {$wpdb->posts} p,
                     {$wpdb->users} u
                WHERE p.post_type='$current_post_type' AND
                    p.post_status != 'auto-draft' AND
                    u.ID = p.post_author
                    $include
                    $search
                    $filter",
                ARRAY_A );

            if( count( $authors ) > 1 ) {
                $available_filters[] = 'author';
            }

            $statuses = $wpdb->get_results(
                "SELECT DISTINCT( p.post_status ) AS id,
                   p.post_status AS title
                FROM {$wpdb->posts} p,
                     {$wpdb->users} u
                WHERE p.post_type='$current_post_type' AND
                    p.post_status != 'auto-draft' AND
                    u.ID = p.post_author
                    $include
                    $search
                    $filter",
            ARRAY_A );

            if( count( $statuses ) > 1 ) {
                $available_filters[] = 'status';
            }
        }

        if( !empty( $pages ) ) {
            foreach( $pages as $k=>$page ) {
                $pages[$k]['edit_link'] = '';
                $post_type_object = get_post_type_object( $page['post_type'] );

                if ( $post_type_object && $post_type_object->_edit_link ) {
                    $pages[$k]['edit_link'] = admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=edit', $page[$this->_ID_column] ) );
                }

                $pages[$k]['view_link'] = get_permalink( $page[$this->_ID_column] );

                $pages[$k]['assign_link'] = '';
                if ( in_array( $page[ $this->_ID_column ], $this->edit_page_ids ) ) {
                    $pages[$k]['assign_link'] = WO()->assign()->build_assign_link( array( 'object' => 'office_page', 'object_id' => $page[$this->_ID_column] ) );
                }

                $pages[$k]['show_edit_link'] = false;
                if ( 'trash' != $page['post_status'] && in_array( $page[ $this->_ID_column ], $this->edit_page_ids ) ) {
                    $pages[$k]['show_edit_link'] = true;
                }

                $pages[$k]['show_delete_link'] = false;
                $pages[$k]['show_trash_link'] = false;
                $pages[$k]['show_restore_link'] = false;
                if ( in_array( $page[ $this->_ID_column ], $this->delete_page_ids ) ) {
                    $pages[$k]['show_delete_link'] = true;

                    if ( 'trash' != $page['post_status'] ) {
                        $pages[$k]['show_trash_link'] = true;
                    }

                    if ( 'trash' == $page['post_status'] ) {
                        $pages[$k]['show_restore_link'] = true;
                    }
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

        $can_create = WO()->current_member_can( 'create_office_page' ) ? true : false;

        exit( json_encode( array(
            'status'            => true,
            'data'              => $pages,
            'pagination'        => $pagination,
            'filters_line'      => $filter_line,
            'available_filters' => $available_filters,
            'active_filters'    => $active_filters,
            'template'          => $template,
            'can_create'        => $can_create
        ) ) );
    }


    public function get_filter() {
        global $wpdb;

        if( empty( $_REQUEST['by'] ) ) {
            exit( json_encode( array(
                'status'        => false,
                'message'         => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN )
            ) ) );
        }

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'u.user_login',
                'p.post_title'
            ) );
        }


        $active_filters = $this->parse_active_filters();
        $filter = $this->parse_filter( $active_filters,
            array(
                'author'  => " AND u.ID=%d",
                'status'  => " AND p.post_status=%s"
            )
        );

        $data = array();

        $private_post_types = WO()->get_private_page_types();
        if ( !empty( $private_post_types ) ) {

            if( !empty( $_REQUEST['filters_tab'] ) ) {
                $current_post_type = $_REQUEST['filters_tab'];
            } else {
                $current_post_type = $private_post_types[0];
            }

            switch( $_REQUEST['by'] ) {
                case 'author':
                    $data = $wpdb->get_results(
                        "SELECT DISTINCT( u.ID ) AS id,
                            u.user_login AS title
                        FROM {$wpdb->posts} p,
                             {$wpdb->users} u
                        WHERE p.post_type='$current_post_type' AND
                            p.post_status != 'auto-draft' AND
                            u.ID = p.post_author
                            $search
                            $filter
                        ORDER BY u.user_login ASC",
                    ARRAY_A );
                    break;
                case 'status':
                    $data = $wpdb->get_results(
                        "SELECT DISTINCT( p.post_status ) AS id,
                           CONCAT(UCASE(SUBSTRING(p.post_status, 1, 1)),LCASE(SUBSTRING(p.post_status, 2))) AS title
                        FROM {$wpdb->posts} p,
                             {$wpdb->users} u
                        WHERE p.post_type='$current_post_type' AND
                            p.post_status != 'auto-draft' AND
                            u.ID = p.post_author
                            $search
                            $filter
                        ORDER BY p.post_status ASC",
                    ARRAY_A );
                    break;
            }
        }

        exit( json_encode( array(
            'status'        => true,
            'data'          => $data,
        ) ) );
    }


    function extra_tablenav( $which ){
        if ( 'top' == $which ) {
            echo $this->build_filter_field( $this->filters_block );
        }
    }

    function column_post_status() {
        return '<div class="wpo_capitalize">{{>post_status}}</div>';
    }


    function column_author() {
        return '{{>author}}';
    }

    function row_actions( $actions, $always_visible = false ) {
        $action_count = count( $actions );
        $i = 0;

        if ( !$action_count )
            return '<div class="wpo_list_table_row_actions">&nbsp;</div>';

        $out = '<div class="' . ( $always_visible ? 'wpo_list_table_row_actions visible' : 'wpo_list_table_row_actions' ) . '">';
        foreach ( $actions as $action => $link ) {
            ++$i;
            ( $i == $action_count ) ? $sep = '' : $sep = '<span class="wpo_list_table_action_separator"> | </span>';
            if( $action == 'trash' || $action == 'view' ) {
                $out .= "{{if post_status != 'trash'}}<span class='wpo_$action'>$link$sep</span>{{/if}}";
            } elseif( $action == 'restore' || $action == 'delete' ) {
                $out .= "{{if post_status == 'trash'}}<span class='wpo_$action'>$link$sep</span>{{/if}}";
            } else {
                $out .= "<span class='wpo_$action'>$link$sep</span>";
            }
        }
        $out .= '&nbsp;</div>';
        return $out;
    }

    function column_assign() {
        return '{{:assign_link}}';
    }


    function column_title() {
        $actions = $hide_actions = array();

        $actions['edit']    = '<a href="{{:edit_link}}" {{if !show_edit_link}}data-hide="1"{{/if}}>' . __( 'Edit', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['trash']   = '<a href="javascript:void(0);" data-page_id="{{>' . $this->_ID_column . '}}" {{if !show_trash_link}}data-hide="1"{{/if}} rel="{{>trash_rel}}">' . __( 'Trash', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['restore'] = '<a href="javascript:void(0);" data-page_id="{{>' . $this->_ID_column . '}}" {{if !show_restore_link}}data-hide="1"{{/if}} rel="{{>restore_rel}}">' . __( 'Restore', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['delete']  = '<a href="javascript:void(0);" data-page_id="{{>' . $this->_ID_column . '}}" {{if !show_delete_link}}data-hide="1"{{/if}} rel="{{>delete_rel}}">' . __( 'Delete Permanently', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        $actions['view']    = '<a href="{{:view_link}}" target="_blank">' . __( 'Preview', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        //our_hook
        $actions = apply_filters( 'wpoffice_list_table_members_actions', $actions );

        return sprintf('%1$s %2$s</div>',
            '<div style="width:100%;float:left;"><span id="page_title_{{>' . $this->_ID_column . '}}"><strong>{{>post_title}} (#{{>' . $this->_ID_column . '}})</strong></span>',
            $this->row_actions( $actions )
        );
    }


    function before_filters_line() {
        return WO()->get_button( __( 'Create', WP_OFFICE_TEXT_DOMAIN ), array('class'=>'wpo_create_page wpo_layer_button wpo_button_create'), array( 'disabled' => true ) );
    }
    //end class
}
