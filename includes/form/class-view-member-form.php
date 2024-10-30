<?php
namespace wpo\form;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_Form;

if ( !class_exists('wpo\form\View_Member_Form') ) {
    class View_Member_Form extends Admin_Form
    {
        function __construct() {
            parent::__construct( array(
                'id' => 'wpo_member_profile_form',
                'name' => 'wpo_member_profile_form'
            ) );

            $fields = $this->get_fields();
            if( is_wp_error( $fields ) ) {
                exit( $fields->get_error_message() );
            }

            $this->add_fields( apply_filters( 'wpoffice_view_member_form_fields', $fields ) );
        }

        function ajax_save_form() {
           return true;
        }

        function render_wpo_avatar_image( $field ) {
            echo '<div style="float:left;width:128px;font-size: 128px;">';
            echo $field['value'];
            echo '</div>';
        }

        function submit_button( $text = '' ) {
            return '';
        }

        function get_field_values() {
            $user_id = '';
            $member_data = array();
            if( !empty( $_REQUEST['id'] ) ) {
                $user_id = $_REQUEST['id'];

                if ( !current_user_can( 'administrator' ) ) {
                    $assigned_users = WO()->get_access_content_ids( get_current_user_id(), 'member' );
                    if ( !in_array( $user_id, $assigned_users ) ) {
                        return new \WP_Error( 'permission_denied',
                            __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) );
                    }
                }

                $member_data = WO()->members()->get_member_data( $user_id );
            }

            return array(
                'member_data' => $member_data,
                'user_id' => $user_id,
                'view_action' => true,
                'user_avatar' => WO()->members()->user_avatar( $user_id ),
                'user_login' => isset( $member_data->user_login ) ? $member_data->user_login : '',
                'user_email' => isset( $member_data->user_email ) ? $member_data->user_email : '',
                'user_first_name' => isset( $member_data->first_name ) ? $member_data->first_name : '',
                'user_last_name' => isset( $member_data->last_name ) ? $member_data->last_name : '',
            );
        }

        function get_fields() {
            $field_values = $this->get_field_values();
            if( is_wp_error( $field_values ) )
                return $field_values;

            return array(
                'user_avatar' => array(
                    'tag' => 'avatar_image',
                    'label' => __( 'Avatar', WP_OFFICE_TEXT_DOMAIN ),
                    'order' => 5,
                    'value' => $field_values['user_avatar']
                ),
                'user_login' => array(
                    'tag' => 'input',
                    'type' => 'text',
                    'label' => __( 'Username', WP_OFFICE_TEXT_DOMAIN ),
                    'id'    => 'user_login',
                    'name' => 'user_login',
                    'value' => $field_values['user_login'],
                    'custom_attributes' => array(
                        'disabled' => 'disabled'
                    )
                ),
                'user_email' => array(
                    'tag' => 'input',
                    'label' => __( 'Email', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'user_email',
                    'name' => 'user_email',
                    'value' => $field_values['user_email'],
                    'custom_attributes' => array(
                        'disabled' => 'disabled'
                    )
                ),
                'user_first_name' => array(
                    'tag' => 'input',
                    'label' => __( 'First Name', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'user_first_name',
                    'name' => 'user_first_name',
                    'value' => $field_values['user_first_name'],
                    'custom_attributes' => array(
                        'disabled' => 'disabled'
                    )
                ),
                'user_last_name' => array(
                    'tag' => 'input',
                    'label' => __( 'Last Name', WP_OFFICE_TEXT_DOMAIN ),
                    'id' => 'user_last_name',
                    'name' => 'user_last_name',
                    'value' => $field_values['user_last_name'],
                    'custom_attributes' => array(
                        'disabled' => 'disabled'
                    )
                )
            );
        }

        public function display() {
            $role = ( !empty( $_REQUEST['role'] ) && !empty( $roles_list[ $_REQUEST['role'] ]['title'] ) ) ?
            $roles_list[ $_REQUEST['role'] ]['title'] : '';

            ob_start();
            parent::display();
            $content = ob_get_clean();

            exit( json_encode( array(
                'title' => '<span>' . sprintf( __( '%s Profile', WP_OFFICE_TEXT_DOMAIN ), $role ) . '</span>',
                'content' => $content
            ) ) );
        }

    }
}