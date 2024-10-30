<?php
namespace wpo\settings;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_Form;

/**
 * Settings_Forms
 */
class Settings_Forms extends Admin_Form {

    /**
     * key of setting
     * @var string
     */
    public $key = '';

    /**
     * Validation errors.
     * @var array
     */
    public $errors = array();

    /**
     * Sanitized fields after validation.
     * @var array
     */
    public $sanitized_fields = array();


    public function validate_email_field( $key ) {

        $text  = $this->get_option( $key );
        $field = $this->get_field_key( $key );

        if ( isset( $_POST['fields'][ $field ] ) ) {
            $text = wp_kses_post( trim( stripslashes( $_POST['fields'][ $field ] ) ) );

            if ( !is_email( $text ) ) {
                $this->errors[] = __( 'Field must be Email', WP_OFFICE_TEXT_DOMAIN );
            }
        }

        return $text;
    }

    /**
     * Validate Text Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @param  mixed $key
     * @return string
     */
    public function validate_text_field( $key ) {

        $text  = $this->get_option( $key );
        $field = $this->get_field_key( $key );

        if ( isset( $_POST['fields'][ $field ] ) ) {
            $text = wp_kses_post( trim( stripslashes( $_POST['fields'][ $field ] ) ) );
        }

        return $text;
    }


    public function validate_settings_fields() {

        $this->sanitized_fields = array();

        foreach ( $this->fields as $key => $field ) {


            //hardcode
            $this->sanitized_fields[$field['name']] = !empty( $_POST[$field['name']] ) ? $_POST[$field['name']] : '';

            continue;



            // Default to "text" field type.
            $type = empty( $field['type'] ) ? 'text' : $field['type'];

            // Look for a validate_FIELDID_field method for special handling
            if ( method_exists( $this, 'validate_' . $key . '_field' ) ) {
                $field = $this->{'validate_' . $key . '_field'}( $key );

                // Exclude certain types from saving
            } elseif ( in_array( $type, array( 'title' ) ) ) {
                continue;

                // Look for a validate_FIELDTYPE_field method
            } elseif ( method_exists( $this, 'validate_' . $type . '_field' ) ) {
                $field = $this->{'validate_' . $type . '_field'}( $key );

                // Fallback to text
            } else {
                $field = $this->validate_text_field( $key );
            }

            $this->sanitized_fields[ $key ] = $field;
        }

    }

    /**
     * Save settings form
     */
    function ajax_save_form() {

        $key = '';

        $this->validate_settings_fields();

        if ( count( $this->errors ) > 0 ) {
            exit( json_encode( array(
                'status' => false,
                'errors' => $this->errors,
            ) ) );
        } else {

            //our_hook
            $this->sanitized_fields = apply_filters( 'wpoffice_settings_api_sanitized_fields_' . $this->key, $this->sanitized_fields );

            WO()->set_settings( $this->key, $this->sanitized_fields );

            exit( json_encode( array(
                'status'    => true,
                'refresh'   => true,
                'message'   => __( 'Settings Updated!', WP_OFFICE_TEXT_DOMAIN ),
//                        'close' => true,
            ) ) );
        }
    }

    /**
     * Display settings form content
     */
    function ajax_display_settings_form() {
        ob_start();

        $this->display();

        $content = ob_get_clean();

        $data = array(
            'title' => '<span id="main_setting_title"></span>',
            'content' => $content
        );

        if ( $help = WO()->help()->get_layer_help() ) {
            $data['help'] = $help;
            $data['show_help'] = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
        }

        exit( json_encode( $data ) );
    }


    function submit_button( $text = '' ) {
        return WO()->get_button( __( 'Update Settings', WP_OFFICE_TEXT_DOMAIN ), array( 'class'=>'wpo_button_submit wpo_save_form' . $this->unique ), array('ajax' => true, 'primary' => true ), false );
    }

    //end of class
}