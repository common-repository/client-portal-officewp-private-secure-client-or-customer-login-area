<?php

namespace wpo\gateways;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;

class List_Table_Gateways extends Admin_List_Table {

    function __construct(){
        $args = array(
            'singular'          => __( 'Gateway', WP_OFFICE_TEXT_DOMAIN ),
            'plural'            => __( 'Gateways', WP_OFFICE_TEXT_DOMAIN ),
            'no_items_message'  => ''
        );

        parent::__construct( $args );

        $this->_ID_column = 'id';
        $this->uniqid = uniqid();

        $this->set_bulk_actions( array() );

        $columns = array(
            'gateway' => array(
                'title'     => '',
            )
        );

        $this->set_columns_data( $columns );
    }


    /**
     * AJAX Build ListTable Data
     */
    public function list_table_data() {
        $paged = 1;
        $per_page = 99999;

        $items = apply_filters( 'wpoffice_settings_gateways', array() );

        //filtering by search
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = strtolower( (string)$_REQUEST['search'] );

            foreach ( $items as $key => $value ) {
                if ( false === strpos( strtolower( $value['title'] ), $search )
                        && false === strpos( strtolower( $value['description'] ), $search ) ) {
                    unset( $items[ $key ] );
                }

            }
        }

        $items_count = count( $items );

        $pagination = array(
            'current_page'  => $paged,
            'start'         => $per_page * ( $paged - 1 ) + 1,
            'end'           => ( $per_page * ( $paged - 1 ) + $per_page < $items_count ) ? $per_page * ( $paged - 1 ) + $per_page : $items_count,
            'count'         => $items_count,
            'pages_count'   => ceil( $items_count/$per_page )
        );

        $this->set_pagination_args( array( 'total_items' => $items_count, 'per_page' => $per_page ) );

        $template = array();
        if ( !empty( $_REQUEST['reset_template'] ) ) {
            $this->prepare_items();
            $template['content_sample'] = $this->sample();
            $template['headers_sample'] = $this->headers_sample();
        }

        exit( json_encode( array(
            'status'        => true,
            'data'          => $items,
            'pagination'    => $pagination,
            'filters_line'  => array(),
            'template'      => $template
        ) ) );
    }


    /**
     * Build HTML for subject column
     *
     * @return string
     */
    function column_gateway() {
        $actions = $hide_actions = array();
        $actions['edit']    = '<a href="javascript:void(0);" data-gateway="{{>gateway}}">' . __( 'Edit', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        //our_hook
        $actions = apply_filters( 'wpoffice_list_table_gateways_actions', $actions, $this->_ID_column );

        return sprintf('%1$s %2$s</div></div>',
            '<div class="wpo_line_{{>active}}" title="{{>active}}">&nbsp;</div>'
                . '<div style="width:calc( 100% - 30px );float:left;">'
                . '<div class="wpo_bold">{{>title}}</div>'
                . '<div>{{>description}}</div>',
            $this->row_actions( $actions )
        );
    }


    /**
     * Build HTML for pulllayer settings layer
     */
    function ajax_display_settings_form() {
        ob_start();
        ?>
        <style>
            .wpo_list_table_footer {
                display: none;
            }

            .wpo_tablenav {
                display: none;
            }

            .wpo_line_inactive_row {
                opacity: 0.5;
            }
        </style>
        <div class="wpo_gateways wpo_admin_wrapper">
            <?php $this->display(); ?>
        </div>


        <script type="text/javascript">
            function wpo_load_content_callback() {
                jQuery('.wpo_gateways .wpo_single_row').removeClass('wpo_line_inactive_row');
                jQuery('.wpo_line_inactive').parents('.wpo_single_row').addClass('wpo_line_inactive_row');
            }

            jQuery( document ).ready( function() {
                var body = jQuery( 'body' );

                body.off( 'click', '.wpo_gateways .wpo_edit a' ).on( 'click', '.wpo_gateways .wpo_edit a', function(e) {
                    var obj = jQuery(this);

                    jQuery.pulllayer({
                        ajax_url  : '<?php echo WO()->get_ajax_route( 'wpo\gateways\Payment_Gateways', 'ajax_display_settings_form' ); ?>',
                        ajax_data : 'gateway=' + obj.data('gateway'),
                        custom_data : {
                            list_table_uniqueid : '<?php echo $this->uniqid ?>'
                        },
                        object    : this,
                    });
                    e.stopPropagation();
                });
            });
        </script>
        <?php
        $content = ob_get_clean();

        $data = array(
            'title' => '<span>' . __( 'Gateways', WP_OFFICE_TEXT_DOMAIN ) . '</span>',
            'content' => $content
        );

//        if( $help = WO()->help()->get_layer_help() ) {
//            $data['help'] = $help;
//            $data['show_help'] = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
//        }

        exit( json_encode( $data ) );
    }

    //end class
}