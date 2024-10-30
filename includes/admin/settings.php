<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_print_scripts( array(
    'wpo-list_table-js-render',
    'wpo-list_table-js'
) );

$settings_links = apply_filters( 'wpoffice_settings_get_links', array(
    'general' => array(
        'title' => __( 'General', WP_OFFICE_TEXT_DOMAIN ),
        'cap'   => 'manage_options',
        'order'   => '100',
        'items' => array(
            'general'     => array(
                'item_title'    => __( 'Common', WP_OFFICE_TEXT_DOMAIN ),
                'item_cap'      => 'manage_options',
                'class_name'    => 'wpo/settings/Setting_Common',
            ),
//            'payment_gateways' => array(
//                'item_title'    => __( 'Payment Gateways (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
//            'email_sending' => array(
//                'item_title'    => __( 'Email Sending (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
            'pages' => array(
                'item_title'    => __( 'Link Endpoints', WP_OFFICE_TEXT_DOMAIN ),
                'item_cap'      => 'manage_options',
                'class_name'    => 'wpo/settings/Setting_Pages',
            ),
            'contact_info'     => array(
                'item_title'    => __( 'Contact Info', WP_OFFICE_TEXT_DOMAIN ),
                'item_cap'      => 'manage_options',
                'class_name'    => 'wpo/settings/Setting_Contact_Info',
            ),
        ),
    ),
//    'members' => array(
//        'title' => __( 'Members', WP_OFFICE_TEXT_DOMAIN ),
//        'cap'   => 'manage_options',
//        'items' => array(
//            'clients_staff' => array(
//                'item_title'    => __( 'Clients/Staff (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
//            'convert_users' => array(
//                'item_title'    => __( 'Convert Users (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
//            'private_messages' => array(
//                'item_title'    => __( 'Private Messages (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
//        ),
//    ),
//    'secure' => array(
//        'title' => __( 'Secure', WP_OFFICE_TEXT_DOMAIN ),
//        'cap'   => 'manage_options',
//        'items' => array(
//            'ip_access_restriction' => array(
//                'item_title'    => __( 'IP Access Restriction (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
//            'login_alerts' => array(
//                'item_title'    => __( 'Login Alerts (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
//        ),
//    ),
    'customization' => array(
        'title' => __( 'Customization', WP_OFFICE_TEXT_DOMAIN ),
        'cap'   => 'manage_options',
        'order'   => '1000',
        'items' => array(
            'custom_style' => array(
                'item_title'    => __( 'Custom Style', WP_OFFICE_TEXT_DOMAIN ),
                'item_cap'      => 'manage_options',
                'class_name'    => 'wpo/settings/Setting_Custom_Style',
            ),
//            'custom_login' => array(
//                'item_title'    => __( 'Custom Login (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
//            'custom_titles' => array(
//                'item_title'    => __( 'Custom Titles (fake)', WP_OFFICE_TEXT_DOMAIN ),
//                'item_cap'      => 'manage_options',
//                'class_name'    => 'wpo/settings',
//            ),
        ),
    ),

    'templates' => array(
        'title' => __( 'Templates', WP_OFFICE_TEXT_DOMAIN ),
        'cap'   => 'manage_options',
        'order'   => '900',
        'items' => array(
            'email' => array(
                'item_title'    => __( 'Email Notifications', WP_OFFICE_TEXT_DOMAIN ),
                'item_cap'      => 'manage_options',
                'class_name'    => 'wpo/list_table/List_Table_Email_Notifications'
            ),
        ),
    ),

    'payments' => array(
        'title' => __( 'Payments', WP_OFFICE_TEXT_DOMAIN ),
        'cap'   => 'manage_options',
        'order'   => '910',
        'items' => array(
            'gateways' => array(
                'item_title'    => __( 'Gateways', WP_OFFICE_TEXT_DOMAIN ),
                'item_cap'      => 'manage_options',
                'class_name'    => 'wpo/gateways/List_Table_Gateways'
            ),
        ),
    ),
) );

@uasort( $settings_links,'wpo_sort_settings' );



/**
 * Sorting settings array by order
 *
 * @param $a
 * @param $b
 * @return int
 */
function wpo_sort_settings( $a, $b ) {
    //name of key for sort
    $key = 'order';

    if ( strtolower( $a[$key] ) == strtolower( $b[$key] ) )
        return 0;

    return ( strtolower( $a[$key] ) < strtolower( $b[$key] ) ) ? -1 : 1;
}


remove_all_actions( 'mce_external_plugins' );
remove_all_actions( 'mce_buttons' );
?>

<div class="wpo_admin_settings">
    <div style="display: none;">
        <?php wp_editor( '', 'wpo_notification_body', array(
            'textarea_name' => 'content',
            'media_buttons' => false,
            'wpautop' => false,
            'textarea_rows' => 7,
        ) ); ?>
    </div>
    <?php if ( !empty( $settings_links ) && is_array( $settings_links ) ) {
        foreach( $settings_links as $section_key => $section ) { ?>
            <div class="wpo_settings_item" id="wpo_settings_item_<?php echo $section_key ?>">
                <ul>
                    <li><h2><?php echo $section['title'] ?></h2></li>
                    <?php if ( !empty( $section['items'] ) && is_array( $section['items'] ) ) {
                        foreach( $section['items'] as $item_key => $item ) {
                            $class_name= ( !empty( $item['class_name'] )  ) ? $item['class_name'] : '';

                            $item_args = array(
                                'title' => $item['item_title'],
                                'key' => $item_key,
                            ); ?>

                            <li><a class="wpo_settings_links" id="wpo_settings_<?php echo $item_key ?>" data-ajax_url="<?php echo WO()->get_ajax_route( $class_name, 'ajax_display_settings_form' ) ?>" href="javascript:void(0);" data-args="<?php echo esc_attr( json_encode( $item_args ) ) ?>"><?php echo $item['item_title'] ?></a></li>
                        <?php }
                    } ?>
                </ul>
            </div>
        <?php }
    } ?>
</div>

<script type="text/javascript">
    jQuery( document ).ready( function() {
        var body = jQuery( 'body' );

        body.on( 'click', '.wpo_settings_links', function( e ) {
            var $ajax_data      = 'key=' + jQuery( this ).data( 'args' ).key;
            var $title          = jQuery( this ).data( 'args' ).title;
            var $ajax_url     = jQuery( this ).data( 'ajax_url' );

            jQuery.pulllayer({
                ajax_url    : $ajax_url,
                ajax_data   : $ajax_data,
                object      : this,
                custom_class: 'wpo_settings_layer',
                onOpen      : function() {
                    //list_table
                    //clear_hash(true);
                    jQuery( '.wpo_admin_settings' ).animate({
                        width: '20%'
                    }, 800 );
                },
                onClose     : function() {
                    jQuery( '.wpo_admin_settings' ).animate({
                        width: '100%'
                    }, 800, function() {
                        jQuery( '#main_setting_title' ).html( '' );
                    } );
                },
                beforeContentLoad     : function() {
                    clear_hash(true);
                },
                onCloseAnimateEnd     : function() {
                    clear_hash(true);
                },
                onOpenContentLoad : function() {
                    jQuery( '#main_setting_title' ).html( $title );
                }
            });

            e.stopPropagation();
        });


        body.on( 'click', '#update_settings', function() {
            var $class_key  = jQuery( this ).data( 'class_key' );
            var $args   = jQuery( '.wpo_settings_links[data-class_key="' + $class_key + '"]' ).data( 'args' );
            var $fields_data = jQuery( '#wpo_settings_form' ).serialize();

            jQuery.ajax({
                type: "POST",
                url: '<?php echo WO()->get_ajax_url( 'settings_save_data', 'admin' ) ?>',
                data : {
                    fields : $fields_data,
                    class_key : $class_key,
                    key : $args.key
                },
                dataType: "json",
                success: function( data ){

                    if ( data.status ) {

                        if ( data.message ) {
                            jQuery( this ).wpo_notice({
                                message : data.message,
                                type : 'update'
                            });
                        } else {
                            jQuery( this ).wpo_notice({
                                message : '<?php _e( 'Settings Updated!', WP_OFFICE_TEXT_DOMAIN ) ?>',
                                type : 'update'
                            });

                        }

                        if ( data.close ) {
                            jQuery( '.wpo_settings_links[data-class_key="' + $class_key + '"]').pulllayer( 'close' );
                        } else if ( data.refresh ) {
                            jQuery( '.wpo_settings_links[data-class_key="' + $class_key + '"]').pulllayer( 'refresh' );
                        }
                    } else if ( data.errors ) {
                        jQuery( this ).wpo_notice({
                            message : '<?php _e( 'Some Errors:', WP_OFFICE_TEXT_DOMAIN ) ?> ' + data.errors,
                            type : 'error'
                        });
                    }


                },
                error: function(data) {

                }
            });

        });
    });
</script>
