<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\list_table\List_Table_Pages;
$ListTable = new List_Table_Pages();

?>

<div class="wpo_admin_office_pages wpo_admin_wrapper">
    <?php $ListTable->display(); ?>
</div>
<script type="text/javascript">
    jQuery( document ).ready( function() {
        var body = jQuery( 'body' );

        body.on( 'click', '.wpo_create_page:not(.wpo_disabled)', function(e) {
            window.location = "post-new.php?post_type=" + jQuery('.wpo_list_table_filters_line').find('.wpo_list_table_filter_line_item.wpo_current').data('tab');
        });

        body.on( 'click', '.wpo_trash a', function(e) {
            var obj = jQuery(this);
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure move to trash this page?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'trash_page' ) ?>',
                        data: 'id=' + obj.data('page_id'),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                reset_template['<?php echo $ListTable->uniqid ?>'] = true;
                                load_content('<?php echo $ListTable->uniqid ?>');
                            }

                            jQuery( this ).wpo_notice({
                                message : data.status ? '<?php _e( 'Page was moved to Trash!', WP_OFFICE_TEXT_DOMAIN ) ?>' : data.message,
                                type : data.status ? 'update' : 'error'
                            });
                        }
                    });
                },
                object: this
            });
        });


        body.on( 'click', '.wpo_delete a', function(e) {
            var obj = jQuery(this);
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure you want to permanently delete this page?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'delete_page' ) ?>',
                        data: 'id=' + obj.data('page_id'),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                reset_template['<?php echo $ListTable->uniqid ?>'] = true;
                                load_content('<?php echo $ListTable->uniqid ?>');
                            }

                            jQuery( this ).wpo_notice({
                                message : data.status ? '<?php _e( 'Page was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ?>' : data.message,
                                type : data.status ? 'update' : 'error'
                            });
                        }
                    });
                },
                object: this
            });
        });

        body.on( 'click', '.wpo_restore a', function(e) {
            var obj = jQuery(this);
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure Restore this Page?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'restore_page' ) ?>',
                        data: 'id=' + obj.data('page_id'),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                reset_template['<?php echo $ListTable->uniqid ?>'] = true;
                                load_content('<?php echo $ListTable->uniqid ?>');
                            }

                            jQuery( this ).wpo_notice({
                                message : data.status ? '<?php _e( 'Page was Restored!', WP_OFFICE_TEXT_DOMAIN ) ?>' : data.message,
                                type : data.status ? 'update' : 'error'
                            });
                        }
                    });
                },
                object: this
            });
        });

        jQuery( document ).on( "wpo_list_table_data_loaded", function( event, data ) {
            if( data.can_create ) {
                jQuery('.wpo_create_page').removeClass('wpo_disabled');
            } else {
                jQuery('.wpo_create_page').addClass('wpo_disabled');
            }
        });
    });
</script>