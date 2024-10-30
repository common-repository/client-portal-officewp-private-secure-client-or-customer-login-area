<?php
namespace wpo\form;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_Form;

if ( !class_exists('wpo\form\Member_Form') ) {
    class Member_Form extends Admin_Form
    {
        function __construct() {
            parent::__construct( array(
                'id' => 'wpo_member_profile_form',
                'name' => 'wpo_member_profile_form',
            ) );
            $fields = $this->get_fields();
            if( is_wp_error( $fields ) ) {
                exit( $fields->get_error_message() );
            }

            $this->add_fields( apply_filters( 'wpoffice_member_form_fields', $fields ) );

            add_action( 'wpoffice_member_added', array( &$this, 'add_assigns_on_change' ), 10 );
        }


        function js_on_save_success() {
            echo 'reset_template[list_table_uniqueid] = true; load_content( list_table_uniqueid );';
        }

        function ajax_save_form() {
            $roles_list = WO()->get_roles_list_member_main_cap( get_current_user_id() );
            $current_role = '';
            if ( !empty( $_POST['user_role'] ) && !empty( $roles_list ) && in_array( $_POST['user_role'], array_keys( $roles_list ) ) ) {
                $current_role = $_POST['user_role'];
            }

            //check role
            if ( empty( $_POST['id'] ) && ( empty( $current_role ) || ! WO()->is_our_role( $current_role ) ) ) {
                exit( json_encode( array(
                    'status'    => false,
                    'message'     => __( 'Wrong Member role!', WP_OFFICE_TEXT_DOMAIN )
                ) ) );
            }

            //check capabilities
            if( !empty( $_POST['id'] ) ) {
                $edit_member_ids = WO()->get_access_content_ids( get_current_user_id(), 'member', 'edit' );

                if( !current_user_can( 'administrator' ) && !in_array( $_POST['id'], $edit_member_ids ) ) {
                    exit( json_encode( array(
                        'status'    => false,
                        'message'   => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN )
                    ) ) );
                }
            } else if( !current_user_can( 'administrator' ) && !WO()->current_member_can_manage( 'add_member', $current_role ) ) {
                exit( json_encode( array(
                    'status'    => false,
                    'message'   => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN )
                ) ) );
            }

            if( empty( $_POST['pass1'] ) && !empty( $_POST['id'] ) ) {
                $fields = $this->get_form_fields();
                $fields['pass1']['validation'] = array();
                $this->add_fields( $fields );
            }

            $validation = $this->validate( array(
                'user_id' => !empty( $_POST['id'] ) ? $_POST['id'] : '',
            ) );
            if( !empty( $validation ) ) {
                exit( json_encode( array(
                    'status'    => false,
                    'validation_message'   => $validation
                ) ) );
            }

            $userdata = array(
                'user_pass'     => !empty( $_POST['pass1'] ) ? trim( $_POST['pass1'] ) : '',
                'user_email'    => !empty( $_POST['user_email'] ) ? trim( $_POST['user_email'] ) : '',
                'first_name'    => !empty( $_POST['user_first_name'] ) ? trim( $_POST['user_first_name'] ) : '',
                'last_name'     => !empty( $_POST['user_last_name'] ) ? trim( $_POST['user_last_name'] ) : '',
                'send_password' => !empty( $_POST['send_password'] ) ? true : false,
                'user_avatar'   => !empty( $_POST['user_avatar'] ) ? $_POST['user_avatar'] : '',
                '_form'         => 'creation'
            );

            if( !empty( $current_role ) ) {
                $userdata['role'] = $current_role;
                $userdata['user_login'] = !empty( $_POST['user_login'] ) ? trim( $_POST['user_login'] ) : '';
            } else {
                $userdata['ID'] = !empty( $_POST['id'] ) ? trim( $_POST['id'] ) : false;
            }

            $member_id = WO()->members()->save_userdata( $userdata );

            if ( ! is_numeric( $member_id ) ) {

                if ( is_array( $member_id )) {
                    die ( json_encode( array( 'status' => false, 'errors' => $member_id ) ) );
                }

                die ( json_encode( array( 'status' => false, 'error_message' => $member_id ) ) );
            } else {

                /*wpo_hook_
                    hook_name: wpoffice_member_added
                    hook_title: Member Added
                    hook_description: Hook runs after member was added by creation form.
                    hook_type: action
                    hook_in: wp-office
                    hook_location class-member-form.php
                    hook_param: int member_id
                    hook_since: 1.0.0
                */
                do_action( 'wpoffice_member_added', $member_id );

                $message = empty( $_POST['id'] ) ? __( 'Member was Created!', WP_OFFICE_TEXT_DOMAIN ) : __( 'Member was Updated!', WP_OFFICE_TEXT_DOMAIN );

                //send notification
                if ( !empty( $_POST['send_password'] ) ) {
                    if ( empty( $_POST['id'] ) ) {
                        WO()->send_notification(
                            'create_' . $current_role,
                            array(
                                'doer' => get_current_user_id(),
                                'member' => $member_id,
                            ),
                            array(
                                'member_id' => $member_id,
                                'member_password' => !empty( $_POST['pass1'] ) ? trim( $_POST['pass1'] ) : '',
                                'object_type' => 'member',
                            )
                        );
                    } else {
                        WO()->send_notification(
                            'update_' . $current_role . '_profile',
                            array(
                                'doer' => get_current_user_id(),
                                'member' => $member_id,
                            ),
                            array(
                                'member_id' => $member_id,
                                'member_password' => !empty( $_POST['pass1'] ) ? trim( $_POST['pass1'] ) : '',
                                'object_type' => 'member',
                            )
                        );
                    }
                }

                die( json_encode( array( 'status' => true, 'close' => true, 'message' => $message ) ) );
            }
        }


        function render_wpo_send_update( $field ) {
            ?>
            <label>
                <input type="checkbox" id="send_password" name="send_password" value="1" checked="checked" />
                <?php _e( 'Send member an email about their account was updated.', WP_OFFICE_TEXT_DOMAIN ); ?>
            </label>
            <?php
        }

        function render_wpo_send_password( $field ) {
            ?>
            <label>
                <input type="checkbox" id="send_password" name="send_password" value="1" checked="checked" />
                <?php _e( 'Send the new user an email about their account.', WP_OFFICE_TEXT_DOMAIN ); ?>
            </label>
            <?php
        }

        function render_wpo_password( $field ) {
            echo WO()->members()->backend_build_password_form( $field['edit'], 'pass1',
                isset( $field['custom_attributes']['data-wpo-valid'] ) ? $field['custom_attributes']['data-wpo-valid'] : '' );
        }

        function render_wpo_avatar( $field ) {
            if( !empty( $field['value'] ) ) {
                echo WO()->members()->build_avatar_field( 'user_avatar', $field['value'], $field['user_id'] );
            } else {
                echo WO()->members()->build_avatar_field( 'user_avatar', false, $field['user_id'] );
            }
        }

        function submit_button( $text = '' ) {
            return WO()->get_button(
                ( ( isset( $_REQUEST['method'] ) && 'edit_member_form' == $_REQUEST['method'] ) ? __( 'Update', WP_OFFICE_TEXT_DOMAIN ) : __( 'Save', WP_OFFICE_TEXT_DOMAIN ) ),
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

        /**
         * Add assigns to member on add/edit
         *
         * @param $member_id
         */
        function add_assigns_on_change( $member_id ) {
            if ( !empty( $_POST['assigns'] ) ) {
                $items = WO()->assign()->parse_assign_value( $_POST['assigns'] );

                $assign_arguments = apply_filters( 'wpoffice_user_assign_form_args', array(), $member_id );

                if( !current_user_can( 'administrator' ) ) {
                    $assign_arguments['user']['user']['reverse'] = true;
                    if ( !empty( $items['user'] ) ) {
                        $items['user'] = array_merge( $items['user'], array( get_current_user_id() ) );
                    } else {
                        $items['user'] = array( get_current_user_id() );
                    }
                }

                foreach ( $items as $key=>$inner_items ) {
                    if ( !empty( $assign_arguments['user'][$key]['reverse'] ) ) {
                        WO()->set_reverse_assign_data( $key, $inner_items, 'member', $member_id );
                    } else {
                        WO()->set_assign_data( 'user', $member_id , $key, $inner_items );
                    }
                }
            } else {
                if( !current_user_can( 'administrator' ) ) {
                    $items['user'] = array( get_current_user_id() );

                    foreach ( $items as $key=>$inner_items ) {
                        WO()->set_reverse_assign_data( $key, $inner_items, 'member', $member_id );
                    }
                }
            }
        }

        function get_field_values() {
            $user_id = '';
            $member_data = array();
            $assigned_users = '';
            if( !empty( $_REQUEST['id'] ) ) {
                $user_id = $_REQUEST['id'];
                $member_data = WO()->members()->get_member_data( $user_id );
                $assigned_users = array();
                $user_form_args = apply_filters( 'wpoffice_user_assign_form_args', array(), $user_id );

                foreach( $user_form_args['user'] as $key=>$settings ) {
                    if( !empty( $settings['reverse'] ) ) {
                        $assigned = WO()->get_assign_data_by_assign( $key, 'member', $user_id );
                    } else {
                        $assigned = WO()->get_assign_data_by_object( 'user', $user_id, $key );
                    }

                    if( $key == 'member' ) {
                        foreach( $settings as $role=>$set ) {
                            $data = base64_encode( json_encode( array(
                                'key' => $key,
                                'role' => $role,
                                'type' => !empty( $set['type'] ) ? $set['type'] : 'checkbox'
                            ) ) );

                            $users = array();
                            if ( count( $assigned ) ) {
                                $users = get_users( array(
                                    'role' => $role,
                                    'include' => $assigned,
                                    'fields' => 'ids'
                                ) );
                            }

                            $assigned_users[$data] = $users;
                        }
                    } else {
                        $data = base64_encode( json_encode( array(
                            'key' => $key,
                            'type' => !empty( $settings['type'] ) ? $settings['type'] : 'checkbox'
                        ) ) );

                        $assigned_users[$data] = $assigned;
                    }
                }

                $assigned_users = base64_encode( json_encode( $assigned_users ) );
            }

            return array(
                'member_data' => $member_data,
                'edit' => !empty( $_REQUEST['id'] ),
                'user_id' => $user_id,
                'user_avatar' => isset( $member_data->user_avatar ) ? $member_data->user_avatar : '',
                'user_login' => isset( $member_data->user_login ) ? $member_data->user_login : '',
                'user_email' => isset( $member_data->user_email ) ? $member_data->user_email : '',
                'user_first_name' => isset( $member_data->first_name ) ? $member_data->first_name : '',
                'user_last_name' => isset( $member_data->last_name ) ? $member_data->last_name : '',
                'user_role' => !empty( $_REQUEST['role'] ) ? $_REQUEST['role'] : '',
                'assign' => $assigned_users
            );
        }

        function get_fields() {
            $field_values = $this->get_field_values();

            if( is_wp_error( $field_values ) ) {
                return $field_values;
            }

            $fields = array(
                'user_avatar' => array(
                    'tag' => 'avatar',
                    'id' => 'wpo_form_avatar',
                    'label' => __( 'Avatar', WP_OFFICE_TEXT_DOMAIN ),
                    'value' => $field_values['user_avatar'],
                    'user_id' => isset( $field_values['user_id'] ) ? $field_values['user_id'] : '',
                    'helptip' => __( 'By default Gravatar or WordPress Default Avatars is used.', WP_OFFICE_TEXT_DOMAIN ),
                ),
                'user_login' => array(
                    'tag' => 'input',
                    'type' => 'text',
                    'label' => __( 'Username', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'wpo_form_user_login',
                    'name' => 'user_login',
                    'value' => $field_values['user_login'],
                    'custom_attributes' => array(
                        'disabled' => is_numeric( $field_values['user_id'] ) ? 'disabled' : ''
                    ),
                    'validation' => array(
                        'required' => 'required',//this option deletes for Edit form; key needs for it
                        'username_exists' => 'username_exists',//this option deletes for Edit form; key needs for it
                    ),
                    'description' => ( is_numeric( $field_values['user_id'] ) ? __( "(can't be changed)", WP_OFFICE_TEXT_DOMAIN ) : '' )
                ),
                'user_email' => array(
                    'tag' => 'input',
                    'label' => __( 'Email', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'wpo_form_user_email',
                    'name' => 'user_email',
                    'value' => $field_values['user_email'],
                    'custom_attributes' => array(
                        'autocomplete' => 'off'
                    ),
                    'validation' => array(
                        'required' => 'required',
                        'email',
                        'email_exists'
                    )
                ),
                'user_first_name' => array(
                    'tag' => 'input',
                    'label' => __( 'First Name', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'wpo_form_user_first_name',
                    'name' => 'user_first_name',
                    'value' => $field_values['user_first_name'],
                    'custom_attributes' => array(
                        'autocomplete' => 'off'
                    )
                ),
                'user_last_name' => array(
                    'tag' => 'input',
                    'label' => __( 'Last Name', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'wpo_form_user_last_name',
                    'name' => 'user_last_name',
                    'value' => $field_values['user_last_name'],
                    'custom_attributes' => array(
                        'autocomplete' => 'off'
                    )
                ),
                'pass1' => array(
                    'tag' => 'password',
                    'label' => __( 'Password', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'wpo_form_user_pass',
                    'name' => 'pass1',
                    'edit' => $field_values['edit'],
                    'validation' => array(
                        'required',
                    )
                )
            );

            if( !$field_values['edit'] ) {
                $fields['send_password'] = array(
                    'tag' => 'send_password',
                    'label' => ''
                );
                $fields['user_role'] = array(
                    'tag' => 'hidden',
                    'name' => 'user_role',
                    'value' => $field_values['user_role']
                );
            } else {
                $fields['send_update'] = array(
                    'tag' => 'send_update',
                    'label' => ''
                );
                $fields['id'] = array(
                    'tag' => 'hidden',
                    'name' => 'id',
                    'value' => $field_values['user_id']
                );

                //delete some validation rule
                unset(
                    $fields['user_login']['validation']['required'],
                    $fields['user_login']['validation']['username_exists']
                );
            }

            $fields['assign'] = array(
                'tag'       => 'assign_link',
                'label'     => __( 'Assigned', WP_OFFICE_TEXT_DOMAIN ),
                'ajax'      => false,
                'object'    => 'user',
                'object_id' => $field_values['edit'] ? $field_values['user_id'] : $field_values['user_role'],
                'name'      => 'assigns',
                'value'     => $field_values['assign']
            );
            return $fields;
        }

        function display_edit() {
            if( empty( $_REQUEST['role'] ) ) {
                exit( json_encode( array(
                    'title' => '',
                    'content' => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN )
                ) ) );
            }

            $edit_member_ids = WO()->get_access_content_ids( get_current_user_id(), 'member', 'edit' );
            if ( !current_user_can('administrator') && !in_array( $_REQUEST['id'], $edit_member_ids ) ) {
                exit( json_encode( array(
                    'title' => '',
                    'content' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN )
                ) ) );
            }

            $roles_list = WO()->get_settings( 'roles' );
            $role = !empty( $roles_list[ $_REQUEST['role'] ]['title'] ) ? $roles_list[ $_REQUEST['role'] ]['title'] : '';
            ob_start();
            parent::display();
            $content = ob_get_clean();

            $data = array(
                'title' => '<span>' . sprintf( __( 'Edit %s', WP_OFFICE_TEXT_DOMAIN ), $role ) . '</span>',
                'content' => $content
            );
            if( $help = WO()->help()->get_layer_help() ) {
                $data['help'] = $help;
                $data['show_help'] = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
            }

            exit( json_encode( $data ) );
        }

        function display_add() {
            if( empty( $_REQUEST['role'] ) ) {
                exit( json_encode( array(
                    'title' => '',
                    'content' => __( 'Wrong data', WP_OFFICE_TEXT_DOMAIN )
                ) ) );
            }

            if ( !WO()->current_member_can_manage( 'add_member', $_REQUEST['role'], 'on' ) ) {
                exit( json_encode( array(
                    'title' => '',
                    'content' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN )
                ) ) );
            }

            $roles_list = WO()->get_settings( 'roles' );
            $role = !empty( $roles_list[ $_REQUEST['role'] ]['title'] ) ? $roles_list[ $_REQUEST['role'] ]['title'] : '';
            ob_start();
            parent::display();
            $content = ob_get_clean();

            $data = array(
                'title' => '<span>' . sprintf( __( 'Create %s', WP_OFFICE_TEXT_DOMAIN ), $role ) . '</span>',
                'content' => $content
            );
            if( $help = WO()->help()->get_layer_help() ) {
                $data['help'] = $help;
                $data['show_help'] = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
            }

            exit( json_encode( $data ) );
        }

    }
}