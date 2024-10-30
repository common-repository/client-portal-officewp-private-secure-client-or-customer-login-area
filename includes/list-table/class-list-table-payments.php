<?php

namespace wpo\list_table;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;
use wpo\gateways\Payment_Gateways;

class List_Table_Payments extends Admin_List_Table {

    function __construct() {
        $args = array(
            'singular'          => __( 'Payment', WP_OFFICE_TEXT_DOMAIN ),
            'plural'            => __( 'Payments', WP_OFFICE_TEXT_DOMAIN ),
            'no_items_message'  => ''
        );

        parent::__construct( $args );

        $this->_ID_column = 'order_id';

        $columns = array(
            'order_id' => array(
                'title'     => __( 'Order ID', WP_OFFICE_TEXT_DOMAIN ),
                'width'     => '30%'
            ),
            'member' => array(
                'title'     => __( 'Member', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'member',
                'width'     => '18%'
            ),
            'status' => array(
                'title' => __( 'Status', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'status',
                'width'     => '10%'
            ),
            'payment_method' => array(
                'title' => __( 'Payment Method', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'payment_method',
                'width'     => '16%'
            ),
            'amount' => array(
                'title' => __( 'Amount', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'total*1',
                'width'     => '10%'
            ),
            'date' => array(
                'title' => __( 'Date', WP_OFFICE_TEXT_DOMAIN ),
                'sortable'  => 'date',
                'width'     => '16%'
            ),
        );

        $this->set_columns_data( $columns );
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
                'om.meta_value',
                'om1.meta_value',
                'om5.meta_value',
                'u.user_login',
            ) );
        }

        $order_string = $this->get_order_string();

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT id )
            FROM {$wpdb->prefix}wpo_objects
            WHERE type = 'payment'"
        );

        $pagination = array(
            'current_page'  => $paged,
            'start'         => $per_page * ( $paged - 1 ) + 1,
            'end'           => ( $per_page * ( $paged - 1 ) + $per_page < $items_count ) ? $per_page * ( $paged - 1 ) + $per_page : $items_count,
            'count'         => $items_count,
            'pages_count'   => ceil( $items_count/$per_page )
        );

        $pagination = array(
            'current_page'  => $paged,
            'start'         => $per_page * ( $paged - 1 ) + 1,
            'end'           => ( $per_page * $paged < $items_count ) ? $per_page * $paged : $items_count,
            'count'         => $items_count,
            'pages_count'   => ceil( $items_count/$per_page )
        );


        $wpdb->query("SET SESSION SQL_BIG_SELECTS=1");
        $items = $wpdb->get_results(
            "SELECT o.id, o.title as order_id,
              u.user_login as member,
              o.creation_date as date,
              om.meta_value as status,
              om1.meta_value as payment_method,
              om2.meta_value as total,
              om3.meta_value as currency,
              om5.meta_value as function,
              om6.meta_value as status_note
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id AND om.meta_key = 'status'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om1 ON om1.object_id = o.id AND om1.meta_key = 'method'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om2 ON om2.object_id = o.id AND om2.meta_key = 'total'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om3 ON om3.object_id = o.id AND om3.meta_key = 'currency'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om4 ON om4.object_id = o.id AND om4.meta_key = 'date'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om5 ON om5.object_id = o.id AND om5.meta_key = 'function'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om6 ON om6.object_id = o.id AND om6.meta_key = 'status_note'
            LEFT JOIN {$wpdb->users} u ON o.author = u.ID
            WHERE o.type = 'payment'
                  $search
            ORDER BY $order_string
            LIMIT ". ( ( $paged - 1 )*$per_page ). "," . $per_page,
        ARRAY_A );

        foreach ( $items as $key => $item ) {
            $items[ $key ]['payment_method'] = Payment_Gateways::get_payment_method_name( $item['payment_method'] );
            $items[ $key ]['status'] = Payment_Gateways::get_status_name( $item['status'] );
            $items[ $key ]['date'] = WO()->date( $item['date'] );
            $items[ $key ]['amount'] = WO()->number_format( $item['total'], WO()->get_currency( $item['currency'] ) );
        }

        $this->set_pagination_args( array( 'total_items' => $items_count, 'per_page' => $per_page ) );

        if( !empty( $_REQUEST['reset_template'] ) ) {
            $this->prepare_items();
            $template['content_sample'] = $this->sample();
            $template['headers_sample'] = $this->headers_sample();
        }

        exit( json_encode( array(
            'status'        => true,
            'data'          => $items,
            'pagination'    => $pagination,
            'filters_line'  => array(),
            'template'      => $template,
            'can_create'    => true
        ) ) );
    }


    function column_status() {
        return '<span title="{{:status_note}}">{{:status}}</span>';
    }


    function column_order_id() {
        $actions = array();

        return '<div style="margin-left:7px; width:calc( 100% - 55px );float:left;">'
                . '<span id="order_{{>' . $this->_ID_column . '}}"><strong>{{>order_id}}</strong></span></div>'
        ;
    }

}