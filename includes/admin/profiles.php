<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\list_table\List_Table_Profiles;
$ListTable = new List_Table_Profiles();
?>

<div class="wpo_admin_profiles wpo_admin_wrapper">
    <?php $ListTable->display(); ?>
</div>


<script type="text/javascript">
    jQuery( document ).ready( function() {
        var body = jQuery( 'body' );

        body.on( 'click', '.wpo_admin_profiles .wpo_create_profile', function(e) {
            var table_uniqueid = jQuery(this).parents('.wpo_admin_profiles').find('.wpo_list_table_wrapper').data('id');
            jQuery.pulllayer({
                ajax_url        :  '<?php echo WO()->get_ajax_route( 'wpo\form\Profile_Form', 'display' ) ?>',
                object    : this,
                custom_data : {
                    list_table_uniqueid : table_uniqueid
                }
            });
            e.stopPropagation();
        });

        body.on( 'click', '.wpo_admin_profiles .wpo_edit a', function(e) {
            var id = jQuery(this).data('profile_id');
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');
            jQuery.pulllayer({
                ajax_url  : '<?php echo WO()->get_ajax_route( 'wpo\form\Profile_Form', 'display' ); ?>',
                ajax_data : 'profile_id=' + id,
                object    : this,
                custom_data : {
                    list_table_uniqueid : table_uniqueid
                }
            });
            e.stopPropagation();
        });


        body.on( 'click', '.wpo_admin_profiles .wpo_delete a', function(e) {
            var obj = jQuery(this);
            var table_uniqueid = jQuery(this).parents('.wpo_list_table_wrapper').data('id');
            jQuery.wpo_confirm({
                message : '<?php _e( 'Are you sure delete this Profile?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                onYes: function() {
                    jQuery.ajax({
                        type: "POST",
                        url: '<?php echo WO()->get_ajax_route( 'wpo\list_table\List_Table_Profiles', 'delete_profiles' ) ?>',
                        data: 'id=' + obj.data('profile_id'),
                        dataType: 'json',
                        timeout: 20000,
                        success: function( data ) {
                            if( data.refresh ) {
                                reset_template[table_uniqueid] = true;
                                load_content(table_uniqueid);
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
    });
</script>