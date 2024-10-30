<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use wpo\list_table\List_Table_Office_Pages_Categories;

$sub_tab = isset( $_GET['sub_tab'] ) ? $_GET['sub_tab'] : '';

//fix for capabilities if hide office page categories
if( empty( $sub_tab ) ) {
    $cats_object = new wpo\list_table\List_Table_Categories();
    $filters = $cats_object->get_filters_line();

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

$ListTable = '';
if( empty( $sub_tab ) ) {
    $ListTable = new List_Table_Office_Pages_Categories();
}
//our_hook
$ListTable = apply_filters( 'wpoffice_categories_list_table_object_' . $sub_tab, $ListTable );

if( !is_object( $ListTable ) ) {
    exit( __( 'List Table object is empty', WP_OFFICE_TEXT_DOMAIN ) );
} ?>

<div class="wpo_admin_categories wpo_admin_wrapper">
    <?php $ListTable->display(); ?>
</div>

<script type="text/javascript">
    jQuery( document ).ready( function() {
        var body = jQuery( 'body' );

        body.on( 'click', '.wpo_admin_categories .wpo_create_category:not(.wpo_disabled)', function(e) {
            var table_uniqueid = jQuery(this).parents('.wpo_admin_categories').find('.wpo_list_table_wrapper').data('id');

            jQuery.pulllayer({
                ajax_url        :  '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'edit_form' ) ?>',
                object          : this,
                custom_data : {
                    list_table_uniqueid : table_uniqueid
                }
            });
            e.stopPropagation();
        });

        //edit member layer
        body.on( 'click', '.wpo_admin_categories .wpo_edit a', function(e) {
            var id = jQuery(this).data('id');
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');

            jQuery.pulllayer({
                ajax_url        : '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'edit_form' ) ?>',
                ajax_data       : 'id=' + id,
                object          : this,
                custom_data : {
                    list_table_uniqueid : table_uniqueid
                }
            });
            e.stopPropagation();
        });

        body.on( 'click', '.wpo_admin_categories .wpo_delete a', function(e) {
            var obj = jQuery(this);
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');

            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure you want to delete this Category?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'delete_categories' ) ?>',
                        data: 'id=' + obj.data('id'),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                reset_template[table_uniqueid] = true;
                                load_content(table_uniqueid);
                            }

                            jQuery( this ).wpo_notice({
                                message : data.status ? '<?php _e( 'Category was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ?>' : data.message,
                                type : data.status ? 'update' : 'error'
                            });
                        }
                    });
                },
                object: this
            });
        });
    });
</script>
