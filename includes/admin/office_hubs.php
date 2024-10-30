<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\list_table\List_Table_Hubs;
$ListTable = new List_Table_Hubs(); ?>


<style>
    .wpo_hub_priority {
        width: 60px !important;
    }
</style>
<div class="wpo_admin_office_pages wpo_admin_wrapper">
    <?php $ListTable->display(); ?>
</div>
<script type="text/javascript">
    jQuery( document ).ready( function() {
        var body = jQuery( 'body' );

        body.on( 'click', '.wpo_create_hub:not(.wpo_disabled)', function(e) {
            window.location = "post-new.php?post_type=office_hub";
        });

        body.on( 'click', '.wpo_trash a', function(e) {
            var obj = jQuery(this);
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure you want to move this HUB to the Trash?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'trash_hub' ) ?>',
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
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'delete_hub' ) ?>',
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
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'restore_hub' ) ?>',
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

        body.on( 'click', '.wpo_default a', function(e) {
            var obj = jQuery(this);
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure you want to set this HUB page as Default?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'default_page' ) ?>',
                        data: 'id=' + obj.data( 'page_id' ),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                load_content('<?php echo $ListTable->uniqid ?>');
                            }

                            jQuery( this ).wpo_notice({
                                message : data.message,
                                type : data.status ? 'update' : 'error'
                            });
                        }
                    });
                },
                object: this
            });
        });


        var wpo_hub_priority_timeout = {};
        body.on( 'change', '.wpo_hub_priority', function(e) {
            var obj = jQuery(this);

            if( typeof wpo_hub_priority_timeout[ obj.data( 'page_id' ) ] != 'undefined' ) {
                clearTimeout( wpo_hub_priority_timeout[ obj.data( 'page_id' ) ] );
            }

            wpo_hub_priority_timeout[ obj.data( 'page_id' ) ] = setTimeout( function() {

                jQuery.ajax({
                    type: "POST",
                    url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'change_hub_priority' ) ?>',
                    data: 'id=' + obj.data( 'page_id' ) + '&priority=' + obj.val(),
                    dataType: 'json',
                    timeout: 20000,
                    success: function( data ) {
                        jQuery( this ).wpo_notice({
                            message : data.message,
                            type : data.status ? 'update' : 'error'
                        });
                    }
                });

            }, 1000 );

        });

        jQuery( document ).on( "wpo_list_table_data_loaded", function( event, data ) {
            if( data.can_create ) {
                jQuery('.wpo_create_hub').removeClass('wpo_disabled');
            } else {
                jQuery('.wpo_create_hub').addClass('wpo_disabled');
            }
        });
    });
</script>