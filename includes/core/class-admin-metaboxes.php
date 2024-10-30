<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Metaboxes {

    /**
     * PHP 5 constructor
     **/
    function __construct() {
        add_action( 'admin_init', array( &$this, 'meta_init' ) );
        add_action( 'save_post', array( &$this, 'save_meta' ) );

        //adding profiles assigned to office page from category
        add_filter( 'wpoffice_profiles_assigned_to_office_page', array( $this, 'add_office_page_profiles' ), 10, 2 );
    }


    function save_meta( $post_id ) {
        //for quick edit

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if ( isset( $_POST ) && 0 < count( $_POST ) ) {
            $post = get_post( $post_id );

            if ( 'office_page' == $post->post_type ) {
                if( !empty( $_POST['wpo_assigns'] ) ) {
                    $items = WO()->assign()->parse_assign_value( $_POST['wpo_assigns'] );

                    foreach( $items as $key=>$inner_items ) {
                        WO()->set_assign_data( 'office_page', $post_id, $key, $inner_items );
                    }
                }


                //update Office page file template
                if ( isset( $_POST['wpo_office_page_template'] ) && '' != $_POST['wpo_office_page_template'] ) {
                    update_post_meta( $post_id, '_wp_page_template', $_POST['wpo_office_page_template'] );
                } else {
                    delete_post_meta( $post_id, '_wp_page_template' );
                }

                //update Office page file template
                if ( isset( $_POST['wpo_office_page_category'] ) && '' != $_POST['wpo_office_page_category'] ) {
                    update_post_meta( $post_id, 'category_id', $_POST['wpo_office_page_category'] );
                } else {
                    delete_post_meta( $post_id, 'category_id' );
                }

                WO()->send_notification(
                    'update_office_page',
                    array(
                        'doer' => get_current_user_id(),
                        'object_author' => $post->post_author
                    ),
                    array(
                        'object_id' => $post_id,
                        'object_type' => 'office_page',
                        'post_id' => $post_id
                    )
                );

            } elseif ( 'office_hub' == $post->post_type ) {
                if( !empty( $_POST['wpo_assigns'] ) ) {
                    $items = WO()->assign()->parse_assign_value( $_POST['wpo_assigns'] );

                    foreach( $items as $key=>$inner_items ) {
                        WO()->set_assign_data( 'office_hub', $post_id, $key, $inner_items );
                    }
                }


                //update Office HUB file template
                if ( isset( $_POST['wpo_office_hub_template'] ) && '' != $_POST['wpo_office_hub_template'] ) {
                    update_post_meta( $post_id, '_wp_page_template', $_POST['wpo_office_hub_template'] );
                } else {
                    delete_post_meta( $post_id, '_wp_page_template' );
                }

            }
        }

        return '';
    }


    function office_hub_meta() {
        global $post;
        wp_enqueue_script( 'wpo-list_table-js-render' );
        wp_enqueue_script( 'wpo-base64-js' );
        wp_enqueue_script( 'wpo-pulllayer-js' );

        wp_enqueue_script( 'wpo-assign-js' );
        wp_localize_script( 'wpo-assign-js', 'wpo_assign_data', array(
            'ajax_url'          => WO()->get_ajax_route( get_class( WO()->assign() ), 'load_assign_data' ),
            'ajax_reload_form'  => WO()->get_ajax_route( get_class( WO()->assign() ), 'load_assign_tab_content' ),
            'ajax_assign_items' => WO()->get_ajax_route( get_class( WO()->assign() ), 'assign_items' ),
            'texts'             => array(
                'empty'         => __( 'Nothing found', WP_OFFICE_TEXT_DOMAIN ),
                'categories'    => __( 'Categories', WP_OFFICE_TEXT_DOMAIN ),
                'members'       => __( 'Members', WP_OFFICE_TEXT_DOMAIN )
            ),
        ) );

        wp_enqueue_style( 'wpo-admin-buttons-style', false, array(), WP_OFFICE_VER );
        wp_enqueue_style( 'wpo-pulllayer-style', false, array(), WP_OFFICE_VER );
        wp_enqueue_style( 'wpo-assign-style', false, array(), WP_OFFICE_VER );

        $assigns = '';
        if( !empty( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
            $assigned_office_hubs = array();

            $office_hub_form_args = WO()->assign()->office_hub_form_args( array() );

            if ( !empty( $office_hub_form_args["office_hub"] ) ) {
                foreach ( $office_hub_form_args["office_hub"] as $key => $settigns ) {

                    $assigned = WO()->get_assign_data_by_object( 'office_hub', $_GET['post'], $key );

                    if ( $key == 'member' ) {
                        foreach ( $settigns as $role => $set ) {
                            $data = base64_encode( json_encode( array(
                                'key' => $key,
                                'role' => $role,
                                'type' => !empty( $set['type'] ) ? $set['type'] : 'checkbox'
                            ) ) );

                            $assigned_users = array();
                            if ( count( $assigned ) ) {
                                $assigned_users = get_users( array(
                                    'role' => $role,
                                    'include' => $assigned,
                                    'fields' => 'ids'
                                ) );
                            }

                            $assigned_office_hubs[$data] = $assigned_users;
                        }
                    } else {
                        $data = base64_encode( json_encode( array(
                            'key' => $key,
                            'type' => !empty( $settigns['type'] ) ? $settigns['type'] : 'checkbox'
                        ) ) );
                        $assigned_office_hubs[$data] = $assigned;
                    }
                }
            }

            $assigns = base64_encode( json_encode( $assigned_office_hubs ) );
        }

        $template = get_post_meta( $post->ID, '_wp_page_template', true );

        ?>

            <p>
                <strong><?php _e( 'Template', WP_OFFICE_TEXT_DOMAIN ) ?></strong>
            </p>

            <label class="screen-reader-text" for="wpo_office_hub_template"><?php _e( 'Page Template', WP_OFFICE_TEXT_DOMAIN ) ?></label>
            <select name="wpo_office_hub_template" id="wpo_office_hub_template">
                <option value='default' <?php echo ( isset( $template ) && 'default' == $template ) ? 'selected' : '' ?> ><?php _e( 'Default Template', WP_OFFICE_TEXT_DOMAIN ); ?></option>
                <?php page_template_dropdown( $template ); ?>
            </select>


        <p>
            <strong><?php _e( 'Access to Page', WP_OFFICE_TEXT_DOMAIN ) ?></strong>
        </p>

        <label class="screen-reader-text" for="wpo_office_hub_template"><?php _e( 'Page Access', WP_OFFICE_TEXT_DOMAIN ) ?></label>

        <?php
            echo WO()->assign()->build_assign_link( array( 'object' => 'office_hub', 'object_id' => $post->ID, 'ajax' => false, 'name' => 'wpo_assigns', 'value' => $assigns ) );
        ?>


        <?php

    }


    function office_page_meta() {
        global $wpdb, $post;
        wp_enqueue_script( 'wpo-list_table-js-render' );
        wp_enqueue_script( 'wpo-base64-js' );
        wp_enqueue_script( 'wpo-pulllayer-js' );

        wp_enqueue_script( 'wpo-assign-js' );
        wp_localize_script( 'wpo-assign-js', 'wpo_assign_data', array(
            'ajax_url'          => WO()->get_ajax_route( get_class( WO()->assign() ), 'load_assign_data' ),
            'ajax_reload_form'  => WO()->get_ajax_route( get_class( WO()->assign() ), 'load_assign_tab_content' ),
            'ajax_assign_items' => WO()->get_ajax_route( get_class( WO()->assign() ), 'assign_items' ),
            'texts'             => array(
                'empty'         => __( 'Nothing found', WP_OFFICE_TEXT_DOMAIN ),
                'categories'    => __( 'Categories', WP_OFFICE_TEXT_DOMAIN ),
                'members'       => __( 'Members', WP_OFFICE_TEXT_DOMAIN )
            ),
        ) );

        wp_enqueue_style( 'wpo-admin-buttons-style' );
        wp_enqueue_style( 'wpo-pulllayer-style' );
        wp_enqueue_style( 'wpo-assign-style' );

        $page_category = '';
        $assigns = '';
        if( !empty( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
            $assigned_office_pages = array();
            $page_category = get_post_meta( $_GET['post'], 'category_id', true );

            $office_page_form_args = WO()->assign()->office_page_form_args( array() );

            if ( isset( $office_page_form_args["office_page"] ) ) {
                foreach( $office_page_form_args["office_page"] as $key=>$settigns ) {

                    $assigned = WO()->get_assign_data_by_object( 'office_page', $_GET['post'], $key );

                    if( $key == 'member' ) {
                        foreach( $settigns as $role=>$set ) {
                            $data = base64_encode( json_encode( array(
                                'key' => $key,
                                'role' => $role,
                                'type' => !empty( $set['type'] ) ? $set['type'] : 'checkbox'
                            ) ) );

                            $assigned_users = array();
                            if ( count( $assigned ) ) {
                                $assigned_users = get_users( array(
                                    'role'      => $role,
                                    'include'   => $assigned,
                                    'fields'    => 'ids'
                                ) );
                            }

                            $assigned_office_pages[$data] = $assigned_users;
                        }
                    } else {
                        $data = base64_encode( json_encode( array(
                            'key' => $key,
                            'type' => !empty( $settigns['type'] ) ? $settigns['type'] : 'checkbox'
                        ) ) );
                        $assigned_office_pages[$data] = $assigned;
                    }
                }
            }

            $assigns = base64_encode( json_encode( $assigned_office_pages ) );
        }

        $template = get_post_meta( $post->ID, '_wp_page_template', true );

        ?>

            <p>
                <strong><?php _e( 'Template', WP_OFFICE_TEXT_DOMAIN ) ?></strong>
            </p>

            <label class="screen-reader-text" for="wpo_office_page_template"><?php _e( 'Page Template', WP_OFFICE_TEXT_DOMAIN ) ?></label>
            <select name="wpo_office_page_template" id="wpo_office_page_template">
                <option value='default' <?php echo ( isset( $template ) && 'default' == $template ) ? 'selected' : '' ?> ><?php _e( 'Default Template', WP_OFFICE_TEXT_DOMAIN ); ?></option>
                <?php page_template_dropdown( $template ); ?>
            </select>

        <p>
            <strong><?php _e( 'Office Page Category', WP_OFFICE_TEXT_DOMAIN ) ?></strong>
        </p>
        <?php
        $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpo_objects WHERE type='office_page_category'", ARRAY_A );
        if ( !empty( $categories ) ) { ?>
            <label class="screen-reader-text" for="wpo_office_page_category"><?php _e( 'Page Category', WP_OFFICE_TEXT_DOMAIN ) ?></label>
            <select name="wpo_office_page_category" id="wpo_office_page_category">
                <option value="" <?php selected( empty( $page_category ) ) ?>><?php _e( 'None', WP_OFFICE_TEXT_DOMAIN ); ?></option>
                <?php foreach ( $categories as $category ) { ?>
                    <option value="<?php echo $category['id'] ?>" <?php selected( $category['id'], $page_category ) ?>><?php echo $category['title'] ?></option>
                <?php } ?>
            </select>
        <?php } else { ?>
            <p>
                <?php _e( 'No Office Page Categories Created', WP_OFFICE_TEXT_DOMAIN ) ?>
            </p>
        <?php } ?>

        <p>
            <strong><?php _e( 'Access to Page', WP_OFFICE_TEXT_DOMAIN ) ?></strong>
        </p>

        <label class="screen-reader-text" for="wpo_office_page_template"><?php _e( 'Page Access', WP_OFFICE_TEXT_DOMAIN ) ?></label>

        <?php
            echo WO()->assign()->build_assign_link( array( 'object' => 'office_page', 'object_id' => $post->ID, 'ajax' => false, 'name' => 'wpo_assigns', 'value' => $assigns ) );
        ?>


        <?php

    }


    /*
    * Add meta box
    */
    function meta_init() {
        //meta box for office page
        add_meta_box( 'wpo_office_page_settings', __( 'Office Page Settings', WP_OFFICE_TEXT_DOMAIN ),  array( &$this, 'office_page_meta' ), 'office_page', 'side', 'default' );

        add_meta_box( 'wpo_office_hub_settings', __( 'Office HUB Settings', WP_OFFICE_TEXT_DOMAIN ),  array( &$this, 'office_hub_meta' ), 'office_hub', 'side', 'default' );
    }


    /**
     * Filter office_page profiles which adding profiles assigned to office page from category
     *
     * @param array $profiles
     * @param int $object_id
     * @return array
     */
    function add_office_page_profiles( $profiles, $object_id  ) {
        $office_page_categories = get_post_meta( $object_id, 'category_id', true );
        if ( !empty( $office_page_categories ) ) {
            $profiles = array_merge( $profiles, WO()->get_assign_data_by_object( 'office_page_category', $office_page_categories, 'profile' ) );
        }

        return $profiles;
    }

    //end class
}