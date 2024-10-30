<?php

namespace wpo\settings;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'wpo\settings\Setting_Contact_Info' ) ) {

    class Setting_Contact_Info extends Settings_Forms {

        function __construct() {

            parent::__construct( array(
                'class' => 'wpo_contact_info'
            ) );
            $this->key = 'contact_info';

            $this->add_fields( apply_filters( 'wpoffice_contact_info_settings_fields', $this->get_fields() ) );
        }


        function settings_data( $settings_data ) {

            $settings_data['title'] = __( 'Contact Info', WP_OFFICE_TEXT_DOMAIN );
            $settings_data['button'] = true;

            return $settings_data;
        }

        function get_field_values() {
            $setting = WO()->get_settings( $this->key );
            return array(
                'name' => !empty( $setting['name'] ) ? $setting['name']: '',
                'mailing_address' => !empty( $setting['mailing_address'] ) ? $setting['mailing_address']: '',
                'website' => !empty( $setting['website'] ) ? $setting['website']: '',
                'email' => !empty( $setting['email'] ) ? $setting['email']: '',
                'phone' => !empty( $setting['phone'] ) ? $setting['phone']: ''
            );
        }

        function get_fields() {
            $field_values = $this->get_field_values();
            return array(
                'name' => array(
                    'tag'   => 'input',
                    'type'  => 'text',
                    'label' => __( 'Contact Name', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'name',
                    'value' => $field_values['name'],
                    'description' => '{contact_name}'
                ),
                'mailing_address' => array(
                    'tag'   => 'textarea',
                    'label' => __( 'Mailing Address', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'mailing_address',
                    'value' => $field_values['mailing_address'],
                    'description' => '{contact_mailing_address}'
                ),
                'website' => array(
                    'tag'   => 'input',
                    'type'  => 'text',
                    'label' => __( 'Website', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'website',
                    'value' => $field_values['website'],
                    'description' => '{contact_website}'
                ),
                'email' => array(
                    'tag'   => 'input',
                    'type'  => 'email',
                    'label' => __( 'Contact Email', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'email',
                    'value' => $field_values['email'],
                    'description' => '{contact_email}'
                ),
                'phone' => array(
                    'tag'   => 'input',
                    'type'  => 'text',
                    'label' => __( 'Contact Phone', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'phone',
                    'value' => $field_values['phone'],
                    'description' => '{contact_phone}'
                )
            );
        }

        //end class
    }
}