<?php

namespace wpo\settings;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'wpo\settings\Setting_Pages' ) ) {

    class Setting_Pages extends Settings_Forms {

        public $page_settings = array();

        function __construct() {

            parent::__construct( array() );

            $this->key = 'pages';

//            var_dump( WO()->get_page_slug( 'office_page' ) );
//            exit;
            $this->page_settings = WO()->get_settings( $this->key );

            $fields = array(
                 'main_page' => array(
                    'tag'   => 'custom_main_page',
                    'label' => __( 'Main Office Page', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'wpo_hub_page',
                    'id'    => 'wpo_settings_office_page',
                    'class' => 'wpo_setting_pages_inputs',
                    'value' => ( !empty( $this->page_settings['hub_page']['slug'] ) ) ? $this->page_settings['hub_page']['slug'] : '',
                    'description' => '',
                ),
                'pages_endpoints_title' => array(
                    'type'          => 'title',
                    'label'         => __( 'Office Page Endpoints', WP_OFFICE_TEXT_DOMAIN ),
                    'description'   => 'These link settings tell WP-Office where to send users for the appropriate page functionality (such as the registration form, login page, etc).',
                )
            );

            $endpoints = WO()->get_endpoints();
            foreach( $endpoints as $key=>$val ) {
                $fields[ $key ] = array_merge( $val, array(
                    'tag'   => 'custom_page_slug',
                    'label' =>  $val['title'],
                    'page_key' => $key,
                    'value' => $val['slug'],
                    'name'  => $key,
                    'class' => 'wpo_setting_pages_inputs'
                ));
            }

            $this->add_fields( apply_filters( 'wpoffice_pages_settings_fields', $fields ) );

            add_filter( 'wpoffice_settings_get_field_type_custom_page_slug', array( $this, 'custom_page_slug' ), 10, 3 );
        }


        function delete_wp_page() {

            if ( !empty( $_REQUEST['args']['_nonce'] ) && wp_verify_nonce( $_REQUEST['args']['_nonce'],  'delete_wppage_' . $_REQUEST['args']['page_key'] . get_current_user_id() ) ) {

                wp_delete_post( $_REQUEST['args']['page_id'], true );

                die ( json_encode( array( 'status' => true ) ) );

            }

            die ( json_encode( array( 'status' => false, 'errors' => __( 'Wrong Data!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
        }


        function create_wp_page() {

            if ( !empty( $_REQUEST['args']['_nonce'] ) && wp_verify_nonce( $_REQUEST['args']['_nonce'],  'createwppage_' . $_REQUEST['args']['page_key'] . get_current_user_id() ) ) {

                $content = !empty( $this->fields[$_REQUEST['args']['page_key']]['content'] ) ? $this->fields[$_REQUEST['args']['page_key']]['content'] : '';

                $args = array(
                    'post_title'        => $_POST['args']['title'],
                    'post_type'         => 'page',
                    'post_status'       => 'publish',
                    'post_author'       => get_current_user_id(),
                    'post_content'      => $content,
                );

                $page_id = wp_insert_post( $args );

                if ( $page_id ) {

                    $page_url = get_permalink( $page_id );

                    die ( json_encode( array( 'status' => true, 'page_id' => $page_id, 'page_url' => $page_url ) ) );
                }

                die ( json_encode( array( 'status' => false, 'errors' => __( 'Something Wrong!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

            die ( json_encode( array( 'status' => false, 'errors' => __( 'Wrong Data!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
        }


        /**
         * Save settings form
         */
        function ajax_save_form() {

            if ( !empty( $_REQUEST['wpo_hub_page'] ) && !empty( $_REQUEST['wpo_pages_nonce'] ) && wp_verify_nonce(  $_REQUEST['wpo_pages_nonce'],  'save_page_settings__' . get_current_user_id() ) ) {

                $pages = array(
                    'hub_page' => $_REQUEST['wpo_hub_page'],
                );

                if ( !empty( $_REQUEST['endpoints'] ) ) {
                    foreach( $_REQUEST['endpoints'] as $key => $val ) {


                        if ( !empty( $val['slug'] ) ) {
                            $pages[$key]['slug'] = $val['slug'];
                        }

                        if ( !empty( $val['id'] ) ) {
                            $pages[$key]['id'] = $val['id'];
                        }
                    }

                }


                WO()->update_settings( $this->key, $pages );

                //for update rewrite rules
                WO()->reset_rewrite_rules();

                exit( json_encode( array(
                    'status'    => true,
                    'refresh'   => true,
                    'message'   => __( 'Pages was saved!', WP_OFFICE_TEXT_DOMAIN ),
                ) ) );

            }

            exit( json_encode( array(
                'status' => false,
                'message' => __( 'Wrong Data!', WP_OFFICE_TEXT_DOMAIN ) ,
            ) ) );
        }


        function render_wpo_custom_page_slug( $field ) {

            $is_page = 0;

            $create_item_args = array(
                'page_key'      => $field['page_key'],
                'title'     => esc_attr( wp_kses_post( $field['title'] ) ),
                '_nonce'    => wp_create_nonce( 'createwppage_' . $field['page_key'] . get_current_user_id() ),
            );


            $delete_item_args = array(
                'page_key'  => $field['page_key'],
                '_nonce' => wp_create_nonce( 'delete_wppage_' . $field['page_key'] . get_current_user_id() ),
            );

            $page_slug = !empty( $this->page_settings[$field['page_key']]['slug'] ) ? $this->page_settings[$field['page_key']]['slug'] : $field['value'];

//            $page_url = ( !empty( $this->page_settings['hub_page']['slug'] ) ) ? get_permalink( $this->page_settings['hub_page']['id'] ) . $page_slug : '';
            $page_url = WO()->get_page_slug( $field['page_key'] );


            ?>

            <?php if ( !empty( $this->page_settings[$field['page_key']]['id'] ) ) {
                $is_page = 1;

                $page = get_post( $this->page_settings[$field['page_key']]['id'] );

                $page_title = ( $page ) ? $page->post_title : '';
                $page_url  = ( $page ) ? get_permalink( $page->ID ) : '';




                ?>

                <input type="hidden" name="endpoints[<?php echo $field['page_key'] ?>][id]" value="<?php echo $this->page_settings[$field['page_key']]['id'] ?>" >
                <select class="<?php echo $field['class'] ?>" disabled >
                    <option><?php echo $page_title ?></option>
                </select>

            <?php } ?>


                <input style="float: left; <?php echo $is_page ? 'display: none;' : '' ?>" class="<?php echo $field['class'] ?>" type="text" name="endpoints[<?php echo esc_attr( $field['page_key'] ) ?>][slug]" id="<?php echo esc_attr( $field['page_key'] ) ?>" value="<?php echo $page_slug ?>" />

                <?php if ( ! isset( $field['no_page'] ) ) { ?>

                    <span style="<?php echo $is_page ? 'display: none;' : '' ?>" class="wpo_settings_pages_create_page" data-args="<?php echo esc_attr( json_encode( $create_item_args ) ) ?>" title="<?php _e( 'Create WP page for Slug', WP_OFFICE_TEXT_DOMAIN ) ?>"><span class="dashicons dashicons-welcome-add-page"></span></span>

                    <span style="<?php echo $is_page ? '' : 'display: none;' ?>" data-args="<?php echo esc_attr( json_encode( $delete_item_args ) ) ?>" title="<?php _e( 'Delete', WP_OFFICE_TEXT_DOMAIN ) ?>" class="wpo_delete_page"></span>

                <?php } ?>

                <br />
                <span class="wpo_page_url"><?php echo $page_url ?></span>

            <?php

        }

        function render_wpo_custom_main_page( $field ) {

            $args = array(
                'name'             => esc_attr( $field['name'] ) . '[id]',
                'id'               => ( !empty( $field['id'] ) ) ? $field['id'] : '',
                'sort_column'      => 'menu_order',
                'sort_order'       => 'ASC',
                'show_option_none' => false,
                'class'            => $field['class'],
                'echo'             => false,
                'selected'         => ( $field['value'] ) ? $field['value'] : ''
            );

//            echo str_replace(' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', WP_OFFICE_TEXT_DOMAIN ) .  "' style='" . ( !empty( $field['css'] ) ? $field['css'] : '' ) . "' class='" . $field['class'] . "' id=", wp_dropdown_pages( $args ) );

            echo '<input type="text" name="wpo_hub_page[slug]" value="' . $field['value'] . '" class="' . $field['class'] . '" />';


            echo '<input type="hidden" name="wpo_pages_nonce" value="' . wp_create_nonce( 'save_page_settings__' . get_current_user_id() ) . '" />';

            ?>

            <script type="text/javascript">

                jQuery( '.wpo_settings_pages_create_page' ).wpo_confirm({
                    message : '<?php _e( 'Are you sure to create WP page for this Slug?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                    onYes: function() {

                        var $obj = jQuery( this );
                        var $args = $obj.data( 'args' );

                        jQuery.ajax({
                            type: "POST",
                            url: '<?php echo WO()->get_ajax_route( 'wpo\settings\Setting_Pages', 'create_wp_page' ) ?>',
                            data : {
                                args : $args
                            },
                            dataType: "json",
                            success: function( data ){


                                if ( data.status ) {

                                    $obj.prev( 'input' ).hide();
                                    $obj.parent().find( '.wpo_page_url' ).html( data.page_url );

                                    $obj.before( '<input type="hidden" name="endpoints[' + $args.page_key + '][id]" value="' + data.page_id + '" >' );
                                    $obj.before( '<select class="<?php echo $field['class'] ?>" disabled ><option>' + $args.title + '</option></select>' );
                                    $obj.hide();
                                    $obj.parent().find( '.wpo_delete_page' ).show();


                                    jQuery( this ).wpo_notice({
                                        message : '<?php _e( 'Page was Create!', WP_OFFICE_TEXT_DOMAIN ) ?>',
                                        type : 'update'
                                    });
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
                    }
                });


                jQuery( '.wpo_delete_page' ).wpo_confirm({
                    message : '<?php _e( 'Are you sure to delete Page?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                    onYes: function() {

                        var $obj = jQuery( this );
                        var $args = $obj.data( 'args' );
                        $args.page_id = $obj.parent().find( 'input[type="hidden"]').val();

                        jQuery.ajax({
                            type: "POST",
                            url: '<?php echo WO()->get_ajax_route( get_class( $this ), 'delete_wp_page' ) ?>',
                            data : {
                                args : $args
                            },
                            dataType: "json",
                            success: function( data ){

                                if ( data.status ) {


                                    $obj.parent().find( 'select' ).remove();
                                    $obj.parent().find( 'input[type="hidden"]' ).remove();
                                    $obj.parent().find( '.wpo_setting_pages_inputs' ).show();
                                    $obj.hide();
                                    $obj.parent().find( '.wpo_settings_pages_create_page' ).show();

                                    $obj.parent().find( '.wpo_page_url' ).html( '' );

                                    jQuery( this ).wpo_notice({
                                        message : '<?php _e( 'Page was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ?>',
                                        type : 'update'
                                    });
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
                    }
                });



            </script>

            <?php


        }

        //end class
    }

}

return new Setting_Pages();