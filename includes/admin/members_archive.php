<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\list_table\List_Table_Members_Archive;
$ListTable = new List_Table_Members_Archive();

//field style
wp_register_style( 'wpo-client-avatar-style', WO()->plugin_url . 'assets/css/avatar.css', array(), WP_OFFICE_VER );
wp_enqueue_style( 'wpo-client-avatar-style', false, array(), WP_OFFICE_VER );

//file uploader
wp_register_style( 'wpo-uploadifive', WO()->plugin_url . 'assets/js/plugins/uploadifive/uploadifive.css', array(), WP_OFFICE_VER );
wp_enqueue_style( 'wpo-uploadifive', false, array(), WP_OFFICE_VER );

wp_enqueue_script( 'wpo-uploadifive', WO()->plugin_url . 'assets/js/plugins/uploadifive/jquery.uploadifive.min.js', array(), false, true );

wp_localize_script( 'wpo-uploadifive', 'wpo_flash_uploader', array(
    'cancelled' => ' ' . __( "- Cancelled", WP_OFFICE_TEXT_DOMAIN ),
    'completed' => ' ' . __( "- Completed", WP_OFFICE_TEXT_DOMAIN ),
    'error_1'   => __( "404 Error", WP_OFFICE_TEXT_DOMAIN ),
    'error_2'   => __( "403 Forbidden", WP_OFFICE_TEXT_DOMAIN ),
    'error_3'   => __( "Forbidden File Type", WP_OFFICE_TEXT_DOMAIN ),
    'error_4'   => __( "File Too Large", WP_OFFICE_TEXT_DOMAIN ),
    'error_5'   => __( "Unknown Error", WP_OFFICE_TEXT_DOMAIN )
));
wp_enqueue_script( 'password-strength-meter' );

?>

<div class="wpo_admin_archive_members wpo_admin_wrapper">
    <?php $roles_list = WO()->get_settings( 'roles' );

    if ( !empty( $roles_list ) ) {
        $ListTable->display();
    } else {
        _e( 'At first create roles', WP_OFFICE_TEXT_DOMAIN );
    } ?>
</div>

<script type="text/javascript">
    jQuery( document ).ready( function() {
        var body = jQuery( 'body' );

        //edit member layer
        body.on( 'click', '.wpo_admin_archive_members .wpo_edit a', function(e) {
            var id = jQuery(this).data('member_id');
            var current_role = jQuery('.wpo_list_table_filters_line').find('.wpo_current').parents('li').attr('class');
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');
            jQuery.pulllayer({
                ajax_url        : '<?php echo WO()->get_ajax_route( 'wpo\form\Member_Form', 'display_edit' ) ?>',
                ajax_data       : 'role=' + current_role + '&id=' + id,
                object          : this,
                custom_data : {
                    list_table_uniqueid : table_uniqueid
                }
            });
            e.stopPropagation();
        });

        //view member layer
        body.on( 'click', '.wpo_admin_archive_members .wpo_view a', function(e) {
            var id = jQuery( this ).data( 'member_id' );
            var current_role = jQuery('.wpo_list_table_filters_line').find('.wpo_current').parents('li').attr('class');
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');
            jQuery.pulllayer({
                ajax_url    : '<?php echo WO()->get_ajax_route( 'wpo\form\View_Member_Form', 'display' ) ?>',
                ajax_data   : 'role=' + current_role + '&id=' + id,
                object      : this,
                custom_data : {
                    list_table_uniqueid : table_uniqueid
                }
            });
            e.stopPropagation();
        });


        body.on( 'click', '.wpo_admin_archive_members .wpo_restore a', function(e) {
            var obj = jQuery(this);
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure restore this Member?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'restore_members' ) ?>',
                        data: 'id=' + obj.data('member_id'),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                reset_template[table_uniqueid] = true;
                                load_content(table_uniqueid);
                            }

                            jQuery( this ).wpo_notice({
                                message : data.status ? '<?php _e( 'Member was Restored!', WP_OFFICE_TEXT_DOMAIN ) ?>' : data.message,
                                type : data.status ? 'update' : 'error'
                            });
                        }
                    });
                },
                object: this
            });
        });


        body.on( 'click', '.wpo_admin_archive_members .wpo_delete a', function(e) {
            var obj = jQuery(this);
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure delete this Member?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( get_class( $ListTable ), 'delete_members' ) ?>',
                        data: 'id=' + obj.data('member_id'),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                reset_template[table_uniqueid] = true;
                                load_content(table_uniqueid);
                            }

                            jQuery( this ).wpo_notice({
                                message : data.status ? '<?php _e( 'Member was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ?>' : data.message,
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