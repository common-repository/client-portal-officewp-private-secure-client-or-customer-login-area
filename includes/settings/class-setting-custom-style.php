<?php

namespace wpo\settings;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'wpo\settings\Setting_Custom_Style' ) ) {

    class Setting_Custom_Style extends Settings_Forms {

        function __construct() {

            parent::__construct( array() );

            $this->key = 'custom_style';
            $this->options['id'] = 'wpo_custom_style_settings';

            $setting = WO()->get_settings( $this->key );

            $this->add_fields( apply_filters( 'wpoffice_custom_style_settings_fields', array(
                'disable_plugin_css' => array(
                    'tag'   => 'select',
                    'label' => sprintf( __( 'Disable Default Frontend %s CSS', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ),
                    'name'  => 'disable_plugin_css',
                    'value' => !empty( $setting['disable_plugin_css'] ) ? $setting['disable_plugin_css']: 'no',
                    'helptip' => __( 'Plugin frontend templates will use your current Theme styles if this option set to disable', WP_OFFICE_TEXT_DOMAIN ),
                    'items' => array(
                        'yes'   => __( 'Yes', WP_OFFICE_TEXT_DOMAIN ),
                        'no'    => __( 'No', WP_OFFICE_TEXT_DOMAIN )
                    )
                ),
                'custom_css' => array(
                    'tag'   => 'textarea',
                    'label' => __( 'Custom Frontend CSS', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'custom_css',
                    'value' => !empty( $setting['custom_css'] ) ? $setting['custom_css']: '',
                    'helptip' => __( 'This styles will be enqueue at any frontend page of your site', WP_OFFICE_TEXT_DOMAIN ),
                )
            ) ) );

        }
        //end class
    }
}