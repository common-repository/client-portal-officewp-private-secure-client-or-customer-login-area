<?php
namespace wpo\core;

class Help {
    protected $help;

    function __construct() {
        $this->init();
    }

    function init() {
        if( empty( $this->help ) ) {
            $this->help = apply_filters( 'wpoffice_help', array(
                'page' => array(
                    array(
                        'places' => array('wp-office-members', 'wp-office-members/active'),
                        'text' => '<p>On this page you will find all of the basic information about your
current Members, including their username, email address, and creation date. You can also
assign Members with children roles, Profiles and Circles, as well as Archive Members and perform other actions.</p>'
                    )
                ),
                'layer' => array(
                    array(
                        'places' => 'wpo!form!Member_Form/display_edit',
                        'text' => '<p>' . __( 'On this page you can change the Client\'s info, such as
updating their email address or phone number.', WP_OFFICE_TEXT_DOMAIN ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!form!Member_Form/display_add',
                        'text' => '<p>' . __( 'Create new WP Office Member and add them to this site', WP_OFFICE_TEXT_DOMAIN ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!settings!Setting_Common/ajax_display_settings_form',
                        'text' => '<p>' . __( 'General Plugin Settings', WP_OFFICE_TEXT_DOMAIN ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!settings!Setting_Capabilities/ajax_display_settings_form',
                        'text' => '<p>' . sprintf( __( 'Manage the member\'s capabilities to configure your system flexibility here', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!settings!Setting_Contact_Info/ajax_display_settings_form',
                        'text' => '<p>' . __( 'This information can be used in email notifications to your Members, using the provided placeholders', WP_OFFICE_TEXT_DOMAIN ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!settings!Setting_Custom_Style/ajax_display_settings_form',
                        'text' => '<p>' . sprintf( __( 'Frontend %s style settings', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!settings!Setting_Email_Notifications/ajax_display_settings_form',
                        'text' => '<p>' . sprintf( __( 'Create email notifications to notify your members about actions at your site', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!list_table!List_Table_Email_Notifications/ajax_display_settings_form',
                        'text' => '<p>' . sprintf( __( 'Create email notifications to notify your members about actions at your site', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ) . '</p>'
                    ),
                    array(
                        'places' => 'wpo!settings!Setting_Pages/ajax_display_settings_form',
                        'text' => '<p>' . sprintf( __( 'You can customize here your %s\'s permalinks and pages structure', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ) . '</p>'
                    )
                )
            ) );
        }
        return $this;
    }

    function get_layer_help() {
        $params_array = array();
        $get_params = array('wpo_resource', 'wpo_method');
        foreach( $get_params as $key ) {
            if( !empty( $_REQUEST[ $key ] ) ) {
                $params_array[] = $_GET[ $key ];
            }
        }
        $help_content = false;
        if( count( $params_array ) ) {
            $help_content = $this->get_content('layer', implode( '/', $params_array ) );
        }
        return $help_content;
    }

    function get_page_help() {
        $params_array = array();
        $get_params = array('page', 'tab', 'sub_tab');
        foreach( $get_params as $key ) {
            if( !empty( $_GET[ $key ] ) ) {
                $params_array[] = $_GET[ $key ];
            }
        }
        $help_content = false;
        if( count( $params_array ) ) {
            $help_content = $this->get_content('page', implode( '/', $params_array ) );
        }
        return $help_content;
    }

    function get_content( $type, $key ) {
        foreach( $this->help[ $type ] as $val ) {
            if( !is_array( $val['places'] ) ) {
                $val['places'] = array( $val['places'] );
            }
            if( in_array( $key, $val['places'] ) ) {
                return $val['text'];
            }
        }
        return false;
    }

    function get() {
        return $this->help;
    }

}