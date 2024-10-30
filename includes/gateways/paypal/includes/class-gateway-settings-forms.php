<?php
namespace wpo\gateways\paypal\includes;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\settings\Settings_Forms;

class Gateway_Settings_Forms extends Settings_Forms {

    function __construct() {

        parent::__construct();

        $this->key = 'paypal';

//        $gateway = filter_input( INPUT_POST, 'gateway' );
        $this->options['class'] = 'wpo_' . $this->key;

        $settings = WO()->get_settings( $this->key );

        $fields = array(
            'key' => array(
                'tag'   => 'hidden',
                'name'  => 'gateway',
                'value' => $this->key,
            ),
            'active' => array(
                'tag'   => 'checkbox',
                'label' => __( 'Enable/Disable', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'active',
                'value' => 1,
                'custom_attributes' => array(
                ),
                'items' => array(
                    '1' => array(
                        'label' => __( 'Enable PayPal', WP_OFFICE_TEXT_DOMAIN ),
                        'checked' => $settings['active'],
                    )
                ),
            ),
            'title' => array(
                'tag'   => 'input',
                'type'  => 'text',
                'label' => __( 'Title', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'title',
                'value' => $settings['title'],
                'description' => __( 'This controls the title which the user sees during checkout.', WP_OFFICE_TEXT_DOMAIN ),
            ),
            'description' => array(
                'tag'   => 'textarea',
                'label' => __( 'Description', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'description',
                'value' => $settings['description'],
                'description' => __( 'This controls the description which the user sees during checkout.', WP_OFFICE_TEXT_DOMAIN ),
            ),
            'email' => array(
                'tag'   => 'input',
                'type'  => 'email',
                'label' => __( 'PayPal Email', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'email',
                'value' => $settings['email'],
                'description' => __( 'Please enter your PayPal email address; this is needed in order to take payment.', WP_OFFICE_TEXT_DOMAIN ),
                'custom_attributes' => array(
                    'placeholder' => 'you@youremail.com',
                ),
            ),
            'testmode' => array(
                'tag'   => 'checkbox',
                'label' => __( 'Enable/Disable PayPal sandbox', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'testmode',
                'value' => 1,
                'description' => '',
                'custom_attributes' => array(
                ),
                'items' => array(
                    '1' => array(
                        'label' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.'
                            , WP_OFFICE_TEXT_DOMAIN ), 'https://developer.paypal.com/' ),
                        'checked' => $settings['testmode'],
                    ),
                ),
            ),
            'api_username' => array(
                'tag'   => 'input',
                'type'  => 'text',
                'label' => __( 'API Username', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'api_username',
                'value' => $settings['api_username'],
                'description' => __( 'Get your API credentials from PayPal.', WP_OFFICE_TEXT_DOMAIN ),
                'custom_attributes' => array(
                    'placeholder' => __( 'Optional', WP_OFFICE_TEXT_DOMAIN ),
                    'autocomplete' => 'off',
                ),
            ),
            'api_password' => array(
                'tag'   => 'input',
                'type'  => 'text',
                'label' => __( 'API Password', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'api_password',
                'value' => $settings['api_password'],
                'description' => __( 'Get your API credentials from PayPal.', WP_OFFICE_TEXT_DOMAIN ),
                'custom_attributes' => array(
                    'placeholder' => __( 'Optional', WP_OFFICE_TEXT_DOMAIN ),
                    'autocomplete' => 'off',
                ),
            ),
            'api_signature' => array(
                'tag'   => 'input',
                'type'  => 'text',
                'label' => __( 'API Signature', WP_OFFICE_TEXT_DOMAIN ),
                'name'  => 'api_signature',
                'value' => $settings['api_signature'],
                'description' => __( 'Get your API credentials from PayPal.', WP_OFFICE_TEXT_DOMAIN ),
                'custom_attributes' => array(
                    'placeholder' => __( 'Optional', WP_OFFICE_TEXT_DOMAIN ),
                    'autocomplete' => 'off',
                ),
            ),
        );

        $fields = apply_filters( 'wpoffice_' . $this->key . '_form_fields', $fields );

        $this->add_fields( $fields );
    }


    /**
     * Validate settings before saving
     */
    function validate_settings_fields() {
        $this->sanitized_fields = WO()->Paypal()->prepare_settings( $_POST );
    }


    /**
     * Init List Table reload after save add/edit notifications form
     */
    function js_on_save_success() {
        echo 'reset_template[list_table_uniqueid] = true; load_content( list_table_uniqueid );';
    }


    /**
     * Display settings form content
     */
    function ajax_display_settings_form() {
        ob_start();
            $this->display();
        $content = ob_get_clean();

        if ( isset( $_POST['id'] ) ) {
            $email_notification = WO()->get_object( $_POST['id'], 'email_notification' );
        }

        $data =  array(
            'title' => '<span id="sub_setting_title"></span>',
            'content' => $content,
//            'editor_content' => !empty( $email_notification['body'] ) ? $email_notification['body']: ''
        );

//        if( $help = WO()->help()->get_layer_help() ) {
//            $data['help'] = $help;
//            $data['show_help'] = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
//        }

        return $data;
    }


    /**
     * Render Submit button for Settings Form
     *
     * @param string $text
     * @return string
     */
    function submit_button( $text = '' ) {
        return WO()->get_button(
                __( 'Update Settings', WP_OFFICE_TEXT_DOMAIN ),
            array(
                'class'=>'wpo_button_submit wpo_save_form' . $this->unique
            ),
            array(
                'primary' => true,
                'ajax' => true ), false
        );
    }

    //end class
}