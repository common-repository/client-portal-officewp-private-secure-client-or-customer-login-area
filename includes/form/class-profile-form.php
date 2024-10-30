<?php
namespace wpo\form;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_Form;

if ( !class_exists('wpo\form\Profile_Form') ) {
    class Profile_Form extends Admin_Form {

        function __construct( $options = array() ) {
            parent::__construct( $options );

            $fields = $this->get_fields();
            if( is_wp_error( $fields ) ) {
                exit( $fields->get_error_message() );
            }

            $this->add_fields( apply_filters( 'wpoffice_profile_form_fields', $fields ) );
        }


        function js_on_save_success() {
            echo 'reset_template[list_table_uniqueid] = true; load_content( list_table_uniqueid );';
        }


        function ajax_save_form() {
            if( !empty( $_POST['title'] ) ) {
                global $wpdb;

                if( !empty( $_POST['profile_id'] ) ) {
                    $isset_profile = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT( id )
                        FROM {$wpdb->prefix}wpo_objects
                        WHERE type = 'profile' AND
                              id = %d",
                        $_POST['profile_id']
                    ) );
                    if( $isset_profile == 0 ) {
                        exit( json_encode( array( 'status' => false, 'message' => __( 'Profile does not exists', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                    }

                    $edit_profile_ids = WO()->get_access_content_ids( get_current_user_id(), 'profile', 'edit' );
                    if ( !in_array( $_POST['profile_id'], $edit_profile_ids ) ) {
                        exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                    }
                } else {
                    if ( !WO()->current_member_can( 'create_profile' ) ) {
                        exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                    }
                }

                $validation = $this->validate( array(
                    'profile_id' => !empty( $_POST['profile_id'] ) ? $_POST['profile_id'] : ''
                ) );
                if( !empty( $validation ) ) {
                    exit( json_encode( array(
                        'status'    => false,
                        'validation_message'   => $validation
                    ) ) );
                }

                if( !empty( $_POST['profile_id'] ) ) {
                    $wpdb->update(
                        "{$wpdb->prefix}wpo_objects",
                        array(
                            'title' => $_POST['title'],
                        ),
                        array(
                            'id'    => $_POST['profile_id'],
                        )
                    );

                    $profile_id = $_POST['profile_id'];
                } else {

                    $wpdb->insert(
                        "{$wpdb->prefix}wpo_objects",
                        array(
                            'title'             => $_POST['title'],
                            'type'              => 'profile',
                            'creation_date'     => time(),
                            'author'            => get_current_user_id()
                        ),
                        array(
                            '%s',
                            '%s',
                            '%s',
                            '%d'
                        )
                    );

                    $profile_id = $wpdb->insert_id;
                }

                if ( !empty( $_POST['assigns'] ) ) {
                    $items = WO()->assign()->parse_assign_value( $_POST['assigns'] );

                    if ( !current_user_can( 'administrator' ) ) {
                        $items['user'][] = get_current_user_id();
                    }

                    foreach( $items as $key=>$inner_items ) {
                        WO()->set_reverse_assign_data( $key, $inner_items, 'profile', $profile_id );
                    }
                } else {
                    if ( current_user_can( 'administrator' ) ) {
                        WO()->delete_all_assign_assigns( 'profile', $profile_id );
                    } else {
                        WO()->set_reverse_assign_data( 'user', array( get_current_user_id() ), 'profile', $profile_id );
                    }
                }

                if( !empty( $_POST['profile_id'] ) ) {
                    exit( json_encode( array( 'status' => true,  'close' => true, 'message' => __( 'Profile was updated', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                } else {
                    exit( json_encode( array( 'status' => true,  'close' => true, 'message' => __( 'Profile was created', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                }
            } else {
                exit( json_encode( array( 'status' => false, 'message' => __( 'Title is required', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }
        }

        function submit_button( $text = '' ) {
            if( isset( $this->input_data[ 'view_action' ] ) ) {
                return '';
            }

            return WO()->get_button(
                ( !empty( $_POST['profile_id'] ) ? __( 'Update', WP_OFFICE_TEXT_DOMAIN ) : __( 'Save', WP_OFFICE_TEXT_DOMAIN ) ),
                array(
                    'id'    => 'update_user',
                    'name'  => 'update_user',
                    'class' => 'wpo_save_form' . $this->unique . ' wpo_button_submit'
                ),
                array(
                    'ajax' => true,
                    'primary' => true
                ),
                false
            );
        }

        function get_field_values() {
            global $wpdb;
            $title = '';
            $profile_id = '';
            $assigned_profile = '';
            if( !empty( $_POST['profile_id'] ) ) {
                $profile_id = $_POST['profile_id'];

                $edit_profile_ids = WO()->get_access_content_ids( get_current_user_id(), 'profile', 'edit' );
                if ( !in_array( $profile_id, $edit_profile_ids ) ) {
                    return new \WP_Error('permission_denied',
                        __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) );
                }

                $profile = $wpdb->get_row( $wpdb->prepare(
                    "SELECT *
                    FROM {$wpdb->prefix}wpo_objects
                    WHERE id=%d",
                    $profile_id
                ), ARRAY_A );

                if( !empty( $profile ) ) {
                    $title = $profile['title'];
                }

                $assigned_profile = array();
                $profile_form_args = WO()->assign()->profile_form_args( array() );

                foreach( $profile_form_args['profile'] as $key=>$settigns ) {

                    $assigned = WO()->get_assign_data_by_assign( $key, 'profile', $profile_id );

                    if( $key == 'user' ) {
                        foreach( $settigns as $role=>$set ) {
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

                            $assigned_profile[$data] = $assigned_users;
                        }
                    } else {
                        $data = base64_encode( json_encode( array(
                            'key' => $key,
                            'type' => !empty( $settigns['type'] ) ? $settigns['type'] : 'checkbox'
                        ) ) );
                        $assigned_profile[$data] = $assigned;
                    }
                }

                $assigned_profile = base64_encode( json_encode( $assigned_profile ) );
            } else {
                if( !WO()->current_member_can( 'create_profile' )  ) {
                    return new \WP_Error('permission_denied',
                        __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) );
                }
            }

            return array(
                'title' => $title,
                'assign' => $assigned_profile,
                'profile_id' => $profile_id
            );
        }

        function get_fields() {
            $field_values = $this->get_field_values();
            if( is_wp_error( $field_values ) )
                return $field_values;

            $fields = array(
                'title' => array(
                    'tag' => 'input',
                    'type' => 'text',
                    'label' => __( 'Title', WP_OFFICE_TEXT_DOMAIN ),
                    'name' => 'title',
                    'value' => $field_values['title'],
                    'validation' => array(
                        'required',
                        'profile_exists',
                    ),
                    'description' => __( 'Profile Title must be unique', WP_OFFICE_TEXT_DOMAIN )
                ),
                'assign' => array(
                    'tag'       => 'assign_link',
                    'label'     => __( 'Assigned', WP_OFFICE_TEXT_DOMAIN ),
                    'ajax'      => false,
                    'object'    => 'profile',
                    'object_id' => $field_values['profile_id'],
                    'name'      => 'assigns',
                    'value' => $field_values['assign']
                )
            );

            if( !empty( $field_values['profile_id'] ) ) {
                $fields['profile_id'] = array(
                    'tag' => 'hidden',
                    'name' => 'profile_id',
                    'value' => $field_values['profile_id']
                );
            }

            return $fields;
        }

        function display() {
            ob_start();
            parent::display();
            $content = ob_get_clean();
            exit( json_encode( array(
                'title' => '<span>' . ( !empty( $_POST['profile_id'] ) ? __( 'Edit Profile', WP_OFFICE_TEXT_DOMAIN ) : __( 'Add new Profile', WP_OFFICE_TEXT_DOMAIN ) ) . '</span>',
                'content' => $content
            ) ) );
        }
    }
}