<?php

namespace wpo\gateways;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Payment Gateways class
 *
 * @class Payment_Gateways
 */
class Payment_Gateways {

    /**
     * @var array Array of payment gateway classes.
     */
    public $payment_gateways;

    /**
     * All Countries
     * @var array
     */
    private static $all_countries;

    /**
     * @var Payment_Gateways The single instance of the class
     */
    protected static $_instance = null;


    /**
     * Main Payment_Gateways Instance.
     *
     * Ensures only one instance of Payment_Gateways is loaded or can be loaded.
     *
     * @static
     * @return Payment_Gateways Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Cloning is forbidden.
     * @since 1.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WP_OFFICE_TEXT_DOMAIN ), '1.0' );
    }


    /**
     * Unserializing instances of this class is forbidden.
     * @since 1.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WP_OFFICE_TEXT_DOMAIN ), '1.0' );
    }


    /**
     * Initialize payment gateways.
     */
    public function __construct() {
        $this->init();
    }


    /**
     * Load gateways and hook in functions.
     */
    public function init() {
        $load_gateways = apply_filters( 'wpoffice_settings_gateways', array() );

        $gateways_ordered = $gateways_end = array();

        // Load gateways in order
        foreach ( $load_gateways as $data ) {
            $gateway = $data['class_name']::instance();
            if ( isset( $gateway->order ) && is_numeric( $gateway->order ) ) {
                // Add in position
                $gateways_ordered[ $gateway->order ] = $gateway;
            } else {
                // Add to end of the array
                $gateways_end[] = $gateway;
            }
        }

        ksort( $gateways_ordered );

        $this->payment_gateways = array_merge( $gateways_ordered, $gateways_end );

    }


    //Main method for all ipn
    function _ipn() {
        global $wp_query;

        if( !empty( $wp_query->query_vars['wpo_gateway'] ) ) {
            $order_id = isset( $wp_query->query_vars['wpo_id'] ) ? $wp_query->query_vars['wpo_id'] : '';
            $gateway = strtolower( $wp_query->query_vars['wpo_gateway'] );
            $class = __NAMESPACE__ . '\\' . $gateway . '\\' . ucfirst( $gateway );
            $class::instance()->ipn( $order_id );
        }

    }


    static function get_payment_method_name( $key ) {
        $statuses = array(
            'paypal' => __( 'Paypal', WP_OFFICE_TEXT_DOMAIN ),
        );

        $status_name = isset( $statuses[ $key ] ) ? $statuses[ $key ] : ucfirst( $key );

        return $status_name;
    }


    static function get_status_name( $key ) {
        $statuses = array(
            'completed' => __( 'Completed', WP_OFFICE_TEXT_DOMAIN ),
        );

        $status_name = isset( $statuses[ $key ] ) ? $statuses[ $key ] : ucfirst( $key );

        return $status_name;
    }


    public function get_html_states() {
        $html = '';
        $states = array();
        $current_state = get_user_meta( get_current_user_id(), 'billing_state', true );

        $country_code = filter_input( INPUT_POST, 'country' );

        $states = apply_filters( 'wpoffice_states', $this->get_states( $country_code ), $country_code );

        if ( $states ) {
            $html .= '<select data-wpo-valid="required" autocomplete="address-level1" name="billing_state" id="billing_state">';
            $html .= '<option value="">' . __( 'Select an option', WP_OFFICE_TEXT_DOMAIN ) . '</option>';
            foreach ( $states as $key => $value ) {
                $html .= '<option value="' . esc_attr( $key ) . '" '
                        . selected( $key, $current_state, false ) . '>'
                        . esc_html( $value ) . '</option>';
            }
            $html .= '</select>';
        } else {
            $html .= '<input type="text" data-wpo-valid="required" placeholder="" autocomplete="address-level1" '
                    . 'name="billing_state" id="billing_state" value="' . esc_attr( $current_state ) . '">';
        }

        exit(json_encode(array(
            'html' => $html,
        )));
    }


    /**
     * Get states by code of country
     *
     * @param string $country_code
     * @return array
     */
    public function get_states( $country_code ) {
        $states = array();

        if ( is_string( $country_code ) && !empty( $country_code )
                && file_exists( WO()->path() . '/includes/helpers/states/' . $country_code . '.php' ) ) {
            $states = include( WO()->path() . '/includes/helpers/states/' . $country_code . '.php' );
        }

        return $states;
    }


    private function save_billing_info( $data ) {
        $user_id = get_current_user_id();
        $fields = $this->get_billing_info_fields();
        $default = array_keys( $fields );

        foreach ( $default as $key ) {
            if ( isset( $data[ $key ] ) ) {
                update_user_meta( $user_id, $key, $data[ $key ] );
            }
        }
    }


    public function pay_process() {
        $_wpnonce = filter_input( INPUT_POST, '_wpnonce' );
        $return = array(
            'status' => false,
            'error_message' => __( 'Wrong Data!', WP_OFFICE_TEXT_DOMAIN ),
        );

        if ( $_wpnonce && wp_verify_nonce( $_wpnonce, 'wpoffice-process_checkout' ) ) {

            $validation_rules = $this->get_billing_info_fields();

            $validation_errors = WO()->validation()->process( $_POST, $validation_rules );

            if ( isset( $validation_errors ) && count( $validation_errors ) ) {
                exit( json_encode( array( 'status' => false, 'validation_message' => $validation_errors ) ) );
            }

            // Process Payment
            $available_gateways = $this->get_available_payment_gateways();
            $payment_method = filter_input( INPUT_POST, 'payment_method' );
            if ( isset( $available_gateways[ $payment_method ] ) ) {
                $this->save_billing_info( $_POST );
                $result = $available_gateways[ $payment_method ]->process_payment( $_POST );
                // Redirect to success/confirmation/payment page
                if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
                    $return = array(
                        'status' => true,
                        'redirect' => $result['redirect'],
                    );
                    unset( $return['error_message'] );
                }
            } else {
                $return['error_message'] = __( 'This payment gateway isn\'t available.', WP_OFFICE_TEXT_DOMAIN );
            }

        }
        die( json_encode( $return ) );
    }


    private function get_billing_info_fields() {
        return apply_filters( 'wpoffice_checkout_form_validations', array(
                'billing_first_name' => array( 'required' ),
                'billing_last_name' => array( 'required' ),
                'billing_email' => array( 'required', 'email' ),
                'billing_phone' => array( 'required', 'phone' ),
                'billing_country' => array( 'required', 'country_exists' ),
                'billing_address_1' => array( 'required' ),
                'billing_address_2' => array(),
                'billing_city' => array( 'required', 'city_exists' ),
                'billing_state' => array( 'required' ),
                'billing_postcode' => array( 'required', 'postcode' ),
                'payment_method' => array( 'required', 'payment_method_exists' ),
            ) );
    }


    public static function get_all_countries() {
        if ( empty( self::$all_countries ) ) {
            self::$all_countries = apply_filters( 'wpoffice_all_countries', include( WO()->path() . '/includes/helpers/countries.php' ) );
            asort( self::$all_countries );
        }
        return self::$all_countries;
    }


    private function clear_orders() {
        global $wpdb;

        $old_orders = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}wpo_objects WHERE type = 'order' AND creation_date < UNIX_TIMESTAMP() - 3600*24*5" );
        if ( $old_orders ) {
            WO()->delete_wpo_object( $old_orders );
        }
    }


    /**
     * Returns a new unique order id
     *
     * @global object $wpdb
     * @return string
     */
    private function generate_order_id() {
        global $wpdb;

        do { //make sure it's unique
          $order_id = substr( md5( uniqid() ), rand( 1, 17 ), 14 );
          $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpo_objects WHERE title = '{$order_id}'" );
        } while ( $count );

        return $order_id;
    }


    private function create_new_order( $data ) {
        $default = array(
            'order_title' => '',
            'user_id' => get_current_user_id(),
            'total' => 0,
            'nice_total' => '$0',
            'currency' => 'USD',
            'payment_type' => 'one_time',
        );

        $data = array_merge( $default, $data );

        //remove old blank orders
        $this->clear_orders();

        $order_id = $this->generate_order_id();
        $order_data = array(
            'title'     => $order_id,
            'type'      => 'order',
            'order_data' => $data,
        );
        WO()->update_object( $order_data );

        return $order_id;
    }


    /*
    * Start payment steps
    */
    function start_payment_steps( $data ) {
        //create new order
        $order_id = $this->create_new_order( $data );

        WO()->redirect( WO()->get_page_slug( 'checkout', array( 'order_id' => $order_id ) ) );
    }


    /**
     * Display settings form content
     */
    function ajax_display_settings_form() {
        $class = 'wpo\gateways\\' . strtolower( filter_input( INPUT_POST, 'gateway' ) ) . '\includes\Gateway_Settings_Forms';
        $object = new $class();
        $data = $object->ajax_display_settings_form();

        exit( json_encode( $data ) );
    }


    function needs_payment( $total ) {
        return $total > 0;
    }


    /**
     * Get available gateways.
     *
     * @return array
     */
    public function get_available_payment_gateways() {
        $_available_gateways = array();

        foreach ( $this->payment_gateways as $gateway ) {
            if ( $gateway->is_available() ) {
                $_available_gateways[$gateway->id] = $gateway;
            }
        }

        return $_available_gateways;
    }

}