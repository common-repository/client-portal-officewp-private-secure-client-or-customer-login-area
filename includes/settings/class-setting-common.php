<?php

namespace wpo\settings;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'wpo\settings\Setting_Common' ) ) {

    class Setting_Common extends Settings_Forms {

        function __construct() {
            parent::__construct( array() );

            $this->key = 'common';
            $this->add_fields( apply_filters( 'wpoffice_common_settings_fields', $this->get_fields() ) );
        }


        function settings_data( $settings_data ) {

            $settings_data['title'] = __( 'Common', WP_OFFICE_TEXT_DOMAIN );
            $settings_data['button'] = true;

            return $settings_data;
        }

        function get_field_values() {
            $setting = WO()->get_settings( $this->key );
            return array(
                'no_conflict_mode' => !empty( $setting['no_conflict_mode'] ) ? $setting['no_conflict_mode']: 'no',
                'enable_clouds_moves' => !empty( $setting['enable_clouds_moves'] ) ? $setting['enable_clouds_moves']: 'no'
            );
        }

        function get_fields() {
            $field_values = $this->get_field_values();
            return array(
                'no_conflict_mode' => array(
                    'tag'   => 'select',
                    'label' => __( 'Enable No Conflicts Mode', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'no_conflict_mode',
                    'value' => $field_values['no_conflict_mode'],
                    'helptip' => __( 'With this set to "Yes", all CSS and JS from other plugins will be disabled in WP-Office admin pages. Turn this setting on if you believe a conflict is occurring in your installation that is causing problems with WP-Office.', WP_OFFICE_TEXT_DOMAIN ),
                    'items' => array(
                        'yes' => __( 'Yes', WP_OFFICE_TEXT_DOMAIN ),
                        'no' => __( 'No', WP_OFFICE_TEXT_DOMAIN )
                    )
                ),
                'enable_clouds_moves' => array(
                    'tag'   => 'select',
                    'label' => __( 'Run the Clouds Animation', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'enable_clouds_moves',
                    'value' => $field_values['enable_clouds_moves'],
                    'description' => __( 'If you have loading issues, you can turn off the animated clouds in the plugin header', WP_OFFICE_TEXT_DOMAIN ),
                    'helptip' => __( 'With this set to Yes, the clouds in the plugin header will be animated', WP_OFFICE_TEXT_DOMAIN ),
                    'items' => array(
                        'yes' => __( 'Yes', WP_OFFICE_TEXT_DOMAIN ),
                        'no' => __( 'No', WP_OFFICE_TEXT_DOMAIN )
                    )
                )
            );
        }

        //end class
    }
}